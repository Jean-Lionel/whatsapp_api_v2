<?php

namespace App\Services;

use App\Models\Message;
use App\Models\WhatsappConfiguration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class WhatsAppService
{
    protected string $apiUrl;

    protected string $apiToken;

    protected string $phoneId;

    protected string $phoneNumber;

    protected string $businessAccountId;

    protected ?int $configurationId = null;

    public function __construct(?array $config = null)
    {
        if ($config) {
            $this->apiUrl = $config['api_url'] ?? config('services.whatsapp.api_url');
            $this->apiToken = $config['api_token'] ?? config('services.whatsapp.api_token');
            $this->phoneId = $config['phone_id'] ?? config('services.whatsapp.phone_id');
            $this->phoneNumber = $config['phone_number'] ?? config('services.whatsapp.phone_number');
            $this->businessAccountId = $config['business_id'] ?? config('services.whatsapp.business_account_id');
            $this->configurationId = $config['configuration_id'] ?? null;
        } else {
            $this->apiUrl = config('services.whatsapp.api_url');
            $this->apiToken = config('services.whatsapp.api_token');
            $this->phoneId = config('services.whatsapp.phone_id');
            $this->phoneNumber = config('services.whatsapp.phone_number');
            $this->businessAccountId = config('services.whatsapp.business_account_id');
        }
    }

    public static function forUser(int $userId): self
    {
        $config = WhatsappConfiguration::getDefaultForUser($userId);

        if ($config) {
            $serviceConfig = $config->toServiceConfig();
            $serviceConfig['configuration_id'] = $config->id;

            return new self($serviceConfig);
        }

        return new self;
    }

    public static function forConfiguration(WhatsappConfiguration $config): self
    {
        $serviceConfig = $config->toServiceConfig();
        $serviceConfig['configuration_id'] = $config->id;

        return new self($serviceConfig);
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
     * Upload media file to WhatsApp
     *
     * @param  \Illuminate\Http\UploadedFile  $file
     * @return string|null Media ID
     */
    public function uploadMedia($file): ?string
    {
        $url = $this->apiUrl.$this->phoneId.'/media';

        try {
            $response = Http::withToken($this->apiToken)
                ->attach(
                    'file',
                    file_get_contents($file->getRealPath()),
                    $file->getClientOriginalName()
                )
                ->post($url, [
                    'messaging_product' => 'whatsapp',
                ]);

            if ($response->successful()) {
                return $response->json()['id'] ?? null;
            }

            Log::error('WhatsApp upload media error', [
                'response' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('WhatsApp upload media error', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Envoyer une image
     */
    public function sendImage(string $to, string $imageIdentifier, ?string $caption = null, bool $isUrl = true): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'image',
            'image' => [
                $isUrl ? 'link' : 'id' => $imageIdentifier,
            ],
        ];

        if ($caption) {
            $payload['image']['caption'] = $caption;
        }

        return $this->sendRequest($payload, $to, $caption ?? 'Image Sent', 'image');
    }

    /**
     * Envoyer un document
     */
    public function sendDocument(string $to, string $docIdentifier, ?string $filename = null, ?string $caption = null, bool $isUrl = true): array
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $this->formatPhoneNumber($to),
            'type' => 'document',
            'document' => [
                $isUrl ? 'link' : 'id' => $docIdentifier,
            ],
        ];

        if ($filename) {
            $payload['document']['filename'] = $filename;
        }

        if ($caption) {
            $payload['document']['caption'] = $caption;
        }

        return $this->sendRequest($payload, $to, $caption ?? $filename ?? 'Document Sent', 'document');
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
                    'whatsapp_configuration_id' => $this->configurationId,
                ]);

                Log::info('WhatsApp message sent', [
                    'to' => $to,
                    'message_id' => $message->id,
                    'wa_message_id' => $message->wa_message_id,
                    'configuration_id' => $this->configurationId,
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

    /**
     * Récupérer les templates disponibles depuis WhatsApp
     */
    public function getAvaliableTemplate(): array
    {
        $url = $this->apiUrl.$this->businessAccountId.'/message_templates';

        try {
            $response = Http::withToken($this->apiToken)
                ->get($url);

            $responseData = $response->json();

            if ($response->successful()) {
                $templates = [];

                if (isset($responseData['data']) && is_array($responseData['data'])) {
                    foreach ($responseData['data'] as $template) {
                        $templates[] = [
                            'id' => $template['id'] ?? null,
                            'name' => $template['name'] ?? null,
                            'status' => $template['status'] ?? null,
                            'category' => $template['category'] ?? null,
                            'language' => $template['language'] ?? null,
                            'components' => $template['components'] ?? [],
                            'created_at' => $template['created_at'] ?? null,
                        ];
                    }
                }

                Log::info('WhatsApp templates retrieved', ['count' => count($templates)]);

                return [
                    'success' => true,
                    'templates' => $templates,
                    'paging' => $responseData['paging'] ?? null,
                ];
            }

            Log::error('WhatsApp API error getting templates', [
                'response' => $responseData,
            ]);

            return [
                'success' => false,
                'error' => $responseData['error']['message'] ?? 'Unknown error',
                'templates' => [],
            ];
        } catch (\Exception $e) {
            Log::error('WhatsApp get templates error', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'templates' => [],
            ];
        }
    }

    public function getConfigurationId(): ?int
    {
        return $this->configurationId;
    }
}
