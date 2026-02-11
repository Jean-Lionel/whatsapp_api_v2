<?php

namespace App\Services;

use App\Models\ClientWebhook;
use Illuminate\Support\Facades\Log;

class WebhookDispatcher
{
    public function dispatch(int $userId, string $event, array $payload): void
    {
        $webhooks = ClientWebhook::where('user_id', $userId)
            ->where('is_active', true)
            ->get();

        foreach ($webhooks as $webhook) {
            if ($webhook->shouldTriggerFor($event)) {
                try {
                    dispatch(function () use ($webhook, $event, $payload) {
                        $webhook->trigger($event, $payload);
                    })->afterResponse();
                } catch (\Exception $e) {
                    Log::error("Failed to dispatch webhook {$webhook->id}: ".$e->getMessage());
                }
            }
        }
    }

    public function dispatchMessageReceived(int $userId, array $messageData): void
    {
        $this->dispatch($userId, 'message.received', [
            'event' => 'message.received',
            'timestamp' => now()->toIso8601String(),
            'data' => $messageData,
        ]);
    }

    public function dispatchMessageSent(int $userId, array $messageData): void
    {
        $this->dispatch($userId, 'message.sent', [
            'event' => 'message.sent',
            'timestamp' => now()->toIso8601String(),
            'data' => $messageData,
        ]);
    }

    public function dispatchMessageFailed(int $userId, array $messageData, string $error): void
    {
        $this->dispatch($userId, 'message.failed', [
            'event' => 'message.failed',
            'timestamp' => now()->toIso8601String(),
            'data' => $messageData,
            'error' => $error,
        ]);
    }

    public function dispatchContactCreated(int $userId, array $contactData): void
    {
        $this->dispatch($userId, 'contact.created', [
            'event' => 'contact.created',
            'timestamp' => now()->toIso8601String(),
            'data' => $contactData,
        ]);
    }

    public function dispatchContactUpdated(int $userId, array $contactData): void
    {
        $this->dispatch($userId, 'contact.updated', [
            'event' => 'contact.updated',
            'timestamp' => now()->toIso8601String(),
            'data' => $contactData,
        ]);
    }
}
