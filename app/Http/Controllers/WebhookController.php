<?php

namespace App\Http\Controllers;

use App\Models\Contact;
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
    /**
     * Handle the incoming request (POST).
     *
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $data = $request->all();
        
        // Generate a unique hash for the payload to prevent duplicate WhatsappData entries
        $payloadHash = md5(json_encode($data));
        
        if (cache()->has("webhook_processed_{$payloadHash}")) {
            Log::info('Webhook duplicate skipped', ['hash' => $payloadHash]);
            return response()->json(['status' => 'duplicate_skipped'], 200);
        }

        // Cache the hash for 10 minutes
        cache()->put("webhook_processed_{$payloadHash}", true, 600);

        Log::info('Webhook received', ['data' => $data]);

        // Stocker le payload brut dans whatsapp_data
        $whatsappData = WhatsappData::create([
            'body' => $data,
            'status' => 'received',
        ]);

        // Parser et stocker les messages
        $this->processMessages($whatsappData);

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Parse le payload WhatsApp et insère les messages dans la table messages
     */
    protected function processMessages(WhatsappData $whatsappData)
    {
        // Les données peuvent être dans data.entry ou directement dans entry
        $payload = $whatsappData->body;

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

            // Optional: Update the payload if needed, or just ignore
            return;
        }

        $sentAt = isset($messageData['timestamp'])
            ? Carbon::createFromTimestamp($messageData['timestamp'])
            : now();

        // Rechercher le contact par numéro de téléphone
        $fromNumber = $messageData['from'];
        $contact = $this->findContactByPhone($fromNumber);

        // Si le contact n'existe pas, on pourrait le créer automatiquement ici si désiré
        // Pour l'instant on laisse null si pas trouvé, ou on dépend de la logique existante

        $message = Message::create([
            'contact_id' => $contact?->id,
            'wa_message_id' => $messageData['id'],
            'direction' => 'in',
            'from_number' => $fromNumber,
            'to_number' => $businessPhone ?? '',
            'type' => $this->mapMessageType($type),
            'body' => $body,
            'payload' => $messageData, // Contient toutes les infos du fichier (id, url, mime_type, etc)
            'status' => 'delivered',
            'sent_at' => $sentAt,
        ]);

        Log::info('Message stored', [
            'wa_message_id' => $messageData['id'],
            'contact_id' => $contact?->id,
            'type' => $type
        ]);
    }

    /**
     * Recherche un contact par son numéro de téléphone
     */
    protected function findContactByPhone(string $phone): ?Contact
    {
        $cleanPhone = ltrim($phone, '+');
        // Extract significant number (last 9 digits is usually safe for international matches without country code)
        // But better is to try exact match first
        
        // Match exact (with or without +) or match ending with phone
        return Contact::where(function ($query) use ($cleanPhone) {
             $query->whereRaw("REPLACE(CONCAT(IFNULL(country_code, ''), IFNULL(phone, '')), '+', '') = ?", [$cleanPhone])
                   ->orWhere('phone', $cleanPhone);
        })->first();
    }

    /**
     * Extrait le contenu du message selon son type
     */
    protected function extractMessageBody(array $messageData, string $type): ?string
    {
        return match ($type) {
            'text' => $messageData['text']['body'] ?? null,
            'image' => ($messageData['image']['caption'] ?? '') !== '' 
                        ? '📷 ' . $messageData['image']['caption'] 
                        : '📷 Photo',
            'video' => ($messageData['video']['caption'] ?? '') !== '' 
                        ? '🎥 ' . $messageData['video']['caption'] 
                        : '🎥 Video',
            'audio' => '🎵 Audio',
            'voice' => '🎤 Voice Message',
            'document' => ($messageData['document']['caption'] ?? '') !== '' 
                        ? '📄 ' . $messageData['document']['caption'] 
                        : '📄 ' . ($messageData['document']['filename'] ?? 'Document'),
            'location' => sprintf(
                '📍 Location (Lat: %s, Long: %s)',
                $messageData['location']['latitude'] ?? '',
                $messageData['location']['longitude'] ?? ''
            ),
            'interactive' => isset($messageData['interactive']['list_reply']) 
                                ? '📋 ' . $messageData['interactive']['list_reply']['title']
                                : (isset($messageData['interactive']['button_reply']) 
                                    ? '🔘 ' . $messageData['interactive']['button_reply']['title'] 
                                    : 'Interactive Message'),
            'button' => '🔘 ' . ($messageData['button']['text'] ?? 'Button'),
            'sticker' => '💟 Sticker',
            'reaction' => '👍 Reaction', // Or extract emoji
            default => 'Message (' . $type . ')',
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

        // Map voice to audio
        if ($type === 'voice') return 'audio';

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
            // Pas de log d'erreur car on peut recevoir des statuts pour des vieux messages
            return;
        }

        $status = $statusData['status'] ?? null;

        if ($status && in_array($status, ['sent', 'delivered', 'read', 'failed'])) {
            $updateData = ['status' => $status];
            
            if ($status === 'read' && ! $message->read_at) {
                $updateData['read_at'] = now();
            }

            $message->update($updateData);

            Log::info('Message status updated', [
                'wa_message_id' => $statusData['id'],
                'status' => $status,
            ]);
        }
    }
}
