<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\WhatsappData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Vérification du webhook par Meta (GET request)
     * Nécessaire lors de la configuration initiale du webhook
     */
    public function verify(Request $request)
    {
        $verifyToken = config('services.whatsapp.verify_token');

        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        Log::info('Webhook verification attempt', [
            'mode' => $mode,
            'token' => $token,
            'challenge' => $challenge,
        ]);

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::info('Webhook verified successfully');

            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('Webhook verification failed', [
            'expected_token' => $verifyToken,
            'received_token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    /**
     * Handle the incoming request (POST).
     *
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request)
    {
        $data = $request->all();
        Log::info('Webhook received', ['data' => $data]);

        // Stocker le payload brut dans whatsapp_data
        $whatsappData = WhatsappData::create([
            'body' => $data,
            'status' => 'received',
        ]);

        // Parser et stocker les messages
        $this->processMessages($data, $whatsappData);

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Parse le payload WhatsApp et insère les messages dans la table messages
     */
    protected function processMessages(array $data, WhatsappData $whatsappData)
    {
        // Les données peuvent être dans data.entry ou directement dans entry
        $payload = $data['data'] ?? $data;

        if (! isset($payload['entry'])) {
            return;
        }

        $data = $payload;

        foreach ($data['entry'] as $entry) {
            if (! isset($entry['changes'])) {
                continue;
            }

            foreach ($entry['changes'] as $change) {
                $value = $change['value'] ?? [];

                // Récupérer le numéro de téléphone business (to_number pour les messages entrants)
                $businessPhone = $value['metadata']['display_phone_number'] ?? null;

                // Traiter les messages entrants
                if (isset($value['messages'])) {
                    foreach ($value['messages'] as $message) {
                        $this->storeIncomingMessage($message, $businessPhone, $whatsappData->id);
                    }
                }

                // Traiter les mises à jour de statut des messages sortants
                if (isset($value['statuses'])) {
                    foreach ($value['statuses'] as $status) {
                        $this->updateMessageStatus($status);
                    }
                }
            }
        }

        // Marquer le webhook comme traité
        $whatsappData->update(['status' => 'processed']);
    }

    /**
     * Stocke un message entrant dans la table messages
     */
    protected function storeIncomingMessage(array $messageData, ?string $businessPhone, int $whatsappDataId)
    {
        $type = $messageData['type'] ?? 'text';
        $body = $this->extractMessageBody($messageData, $type);

        // Vérifier si le message existe déjà (éviter les doublons)
        $existingMessage = Message::where('wa_message_id', $messageData['id'])->first();
        if ($existingMessage) {
            Log::info('Message already exists', ['wa_message_id' => $messageData['id']]);

            return;
        }

        $sentAt = isset($messageData['timestamp'])
            ? Carbon::createFromTimestamp($messageData['timestamp'])
            : now();

        Message::create([
            'wa_message_id' => $messageData['id'],
            'direction' => 'in',
            'from_number' => $messageData['from'],
            'to_number' => $businessPhone ?? '',
            'type' => $this->mapMessageType($type),
            'body' => $body,
            'payload' => $messageData,
            'status' => 'delivered',
            'sent_at' => $sentAt,
        ]);

        Log::info('Message stored', ['wa_message_id' => $messageData['id']]);
    }

    /**
     * Extrait le contenu du message selon son type
     */
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

    /**
     * Mappe le type WhatsApp vers les types supportés par la table messages
     */
    protected function mapMessageType(string $type): string
    {
        $supportedTypes = ['text', 'image', 'video', 'audio', 'document', 'location', 'template', 'interactive'];

        if (in_array($type, $supportedTypes)) {
            return $type;
        }

        // Mapper les types non supportés vers 'text'
        return match ($type) {
            'button', 'sticker', 'contacts', 'reaction' => 'text',
            default => 'text',
        };
    }

    /**
     * Met à jour le statut d'un message sortant
     */
    protected function updateMessageStatus(array $statusData)
    {
        $message = Message::where('wa_message_id', $statusData['id'])->first();

        if (! $message) {
            Log::info('Message not found for status update', ['wa_message_id' => $statusData['id']]);

            return;
        }

        $status = $statusData['status'] ?? null;

        if ($status && in_array($status, ['sent', 'delivered', 'read', 'failed'])) {
            $message->update(['status' => $status]);

            if ($status === 'read' && ! $message->read_at) {
                $message->update(['read_at' => now()]);
            }

            Log::info('Message status updated', [
                'wa_message_id' => $statusData['id'],
                'status' => $status,
            ]);
        }
    }
}
