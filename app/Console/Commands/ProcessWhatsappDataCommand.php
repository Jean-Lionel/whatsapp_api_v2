<?php

namespace App\Console\Commands;

use App\Models\WhatsappData;
use App\Models\Message;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ProcessWhatsappDataCommand extends Command
{
    protected $signature = 'whatsapp:process-data {--id= : Process specific WhatsappData ID}';
    protected $description = 'Process WhatsappData entries and create messages';

    public function handle()
    {
        $id = $this->option('id');

        if ($id) {
            $whatsappData = WhatsappData::find($id);
            if (!$whatsappData) {
                $this->error("WhatsappData with ID {$id} not found");
                return 1;
            }
            $this->processEntry($whatsappData);
        } else {
            $entries = WhatsappData::whereNull('status')
                ->orWhere('status', '!=', 'processed')
                ->get();

            $this->info("Found {$entries->count()} entries to process");

            foreach ($entries as $entry) {
                $this->processEntry($entry);
            }
        }

        $this->info('Done!');
        $this->newLine();
        $this->info('Messages in database: ' . Message::count());

        return 0;
    }

    protected function processEntry(WhatsappData $whatsappData)
    {
        $this->info("Processing WhatsappData ID: {$whatsappData->id}");

        $body = $whatsappData->body;

        // Les données peuvent être dans body.data ou directement dans body
        $data = $body['data'] ?? $body;

        if (!isset($data['entry'])) {
            $this->warn("  No entry found, skipping");
            return;
        }

        $messagesCreated = 0;
        $statusesUpdated = 0;

        foreach ($data['entry'] as $entry) {
            if (!isset($entry['changes'])) {
                continue;
            }

            foreach ($entry['changes'] as $change) {
                $value = $change['value'] ?? [];
                $businessPhone = $value['metadata']['display_phone_number'] ?? null;

                // Traiter les messages
                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $result = $this->storeMessage($message, $businessPhone);
                        if ($result) {
                            $messagesCreated++;
                        }
                    }
                }

                // Traiter les statuts
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $result = $this->updateStatus($status);
                        if ($result) {
                            $statusesUpdated++;
                        }
                    }
                }
            }
        }

        $whatsappData->update(['status' => 'processed']);

        $this->info("  Messages created: {$messagesCreated}");
        $this->info("  Statuses updated: {$statusesUpdated}");
    }

    protected function storeMessage(array $messageData, ?string $businessPhone): bool
    {
        $waMessageId = $messageData['id'] ?? null;

        if (!$waMessageId) {
            $this->warn("  No message ID found");
            return false;
        }

        // Vérifier si le message existe déjà
        if (Message::where('wa_message_id', $waMessageId)->exists()) {
            $this->line("  Message {$waMessageId} already exists, skipping");
            return false;
        }

        $type = $messageData['type'] ?? 'text';
        $body = $this->extractMessageBody($messageData, $type);

        $sentAt = isset($messageData['timestamp'])
            ? Carbon::createFromTimestamp($messageData['timestamp'])
            : now();

        Message::create([
            'wa_message_id' => $waMessageId,
            'direction' => 'in',
            'from_number' => $messageData['from'] ?? '',
            'to_number' => $businessPhone ?? '',
            'type' => $this->mapMessageType($type),
            'body' => $body,
            'payload' => $messageData,
            'status' => 'delivered',
            'sent_at' => $sentAt,
        ]);

        $this->info("  Created message: {$waMessageId} - {$body}");
        return true;
    }

    protected function updateStatus(array $statusData): bool
    {
        $waMessageId = $statusData['id'] ?? null;
        $status = $statusData['status'] ?? null;

        if (!$waMessageId || !$status) {
            return false;
        }

        $message = Message::where('wa_message_id', $waMessageId)->first();

        if (!$message) {
            $this->line("  Message {$waMessageId} not found for status update");
            return false;
        }

        if (in_array($status, ['sent', 'delivered', 'read', 'failed'])) {
            $updateData = ['status' => $status];

            if ($status === 'read' && !$message->read_at) {
                $updateData['read_at'] = now();
            }

            $message->update($updateData);
            $this->info("  Updated status: {$waMessageId} -> {$status}");
            return true;
        }

        return false;
    }

    protected function extractMessageBody(array $messageData, string $type): ?string
    {
        return match ($type) {
            'text' => $messageData['text']['body'] ?? null,
            'image' => $messageData['image']['caption'] ?? '[Image]',
            'video' => $messageData['video']['caption'] ?? '[Video]',
            'audio' => '[Audio]',
            'document' => $messageData['document']['filename'] ?? '[Document]',
            'location' => sprintf(
                'Lat: %s, Long: %s',
                $messageData['location']['latitude'] ?? '',
                $messageData['location']['longitude'] ?? ''
            ),
            'interactive' => $messageData['interactive']['button_reply']['title']
                ?? $messageData['interactive']['list_reply']['title']
                ?? '[Interactive]',
            'button' => $messageData['button']['text'] ?? '[Button]',
            'sticker' => '[Sticker]',
            default => null,
        };
    }

    protected function mapMessageType(string $type): string
    {
        $supportedTypes = ['text', 'image', 'video', 'audio', 'document', 'location', 'template', 'interactive'];

        if (in_array($type, $supportedTypes)) {
            return $type;
        }

        return 'text';
    }
}
