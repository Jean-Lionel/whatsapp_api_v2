<?php

namespace App\Services;

use App\Models\Message;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppService
{
    protected string $apiUrl;

    protected string $apiToken;

    protected string $phoneId;

    protected string $phoneNumber;

    public function __construct()
    {
        $this->apiUrl = config('services.whatsapp.api_url');
        $this->apiToken = config('services.whatsapp.api_token');
        $this->phoneId = config('services.whatsapp.phone_id');
        $this->phoneNumber = config('services.whatsapp.phone_number');
    }

    /**
     * Envoyer un message texte via l'API WhatsApp
     */
    public function sendTextMessage(string $to, string $text): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text,
            ],
        ];

        return $this->sendRequest($payload, $to, $text);
    }

    /**
     * Envoyer un template de message
     */
    public function sendTemplate(string $to, string $templateName, string $languageCode = 'fr', array $components = []): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $languageCode,
                ],
            ],
        ];

        if (! empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $this->sendRequest($payload, $to, "Template: {$templateName}", 'template');
    }

    /**
     * Envoyer une image
     */
    public function sendImage(string $to, string $imageUrl, ?string $caption = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'image',
            'image' => [
                'link' => $imageUrl,
            ],
        ];

        if ($caption) {
            $payload['image']['caption'] = $caption;
        }

        return $this->sendRequest($payload, $to, $caption ?? $imageUrl, 'image');
    }

    /**
     * Envoyer un document
     */
    public function sendDocument(string $to, string $documentUrl, ?string $filename = null, ?string $caption = null): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'document',
            'document' => [
                'link' => $documentUrl,
            ],
        ];

        if ($filename) {
            $payload['document']['filename'] = $filename;
        }

        if ($caption) {
            $payload['document']['caption'] = $caption;
        }

        return $this->sendRequest($payload, $to, $caption ?? $filename ?? $documentUrl, 'document');
    }

    /**
     * Marquer un message comme lu
     */
    public function markAsRead(string $messageId): bool
    {
        $url = $this->apiUrl.$this->phoneId.'/messages';

        $payload = [
            'messaging_product' => 'whatsapp',
            'status' => 'read',
            'message_id' => $messageId,
        ];

        try {
            $response = Http::withToken($this->apiToken)
                ->post($url, $payload);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('WhatsApp markAsRead error', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Envoyer la requête à l'API WhatsApp et sauvegarder le message
     */
    protected function sendRequest(array $payload, string $to, string $body, string $type = 'text'): array
    {
        $url = $this->apiUrl.$this->phoneId.'/messages';

        try {
            $response = Http::withToken($this->apiToken)
                ->post($url, $payload);

            $responseData = $response->json();

            if ($response->successful()) {
                // Sauvegarder le message en base de données
                $message = Message::create([
                    'wa_message_id' => $responseData['messages'][0]['id'] ?? 'wamid.'.Str::random(20),
                    'conversation_id' => null,
                    'direction' => 'out',
                    'from_number' => '+'.$this->phoneNumber,
                    'to_number' => $this->formatPhoneNumber($to),
                    'type' => $type,
                    'body' => $body,
                    'payload' => $type !== 'text' ? $payload : null,
                    'status' => 'sent',
                    'sent_at' => now(),
                ]);

                Log::info('WhatsApp message sent', [
                    'to' => $to,
                    'message_id' => $message->id,
                    'wa_message_id' => $message->wa_message_id,
                ]);

                return [
                    'success' => true,
                    'message' => $message,
                    'wa_response' => $responseData,
                ];
            }

            Log::error('WhatsApp API error', [
                'to' => $to,
                'response' => $responseData,
            ]);

            return [
                'success' => false,
                'error' => $responseData['error']['message'] ?? 'Unknown error',
                'wa_response' => $responseData,
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp send error', [
                'to' => $to,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Formater le numéro de téléphone (enlever le + si présent)
     */
    protected function formatPhoneNumber(string $phone): string
    {
        return ltrim($phone, '+');
    }

    /**
     * Obtenir le numéro de téléphone WhatsApp Business
     */
    public function getPhoneNumber(): string
    {
        return '+'.$this->phoneNumber;
    }
}
