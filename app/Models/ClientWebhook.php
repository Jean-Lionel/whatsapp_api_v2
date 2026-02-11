<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ClientWebhook extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'url',
        'secret',
        'events',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_triggered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(WebhookLog::class);
    }

    public static function generateSecret(): string
    {
        return 'whsec_'.Str::random(32);
    }

    public function shouldTriggerFor(string $event): bool
    {
        if (! $this->is_active) {
            return false;
        }

        return in_array($event, $this->events ?? []) || in_array('*', $this->events ?? []);
    }

    public function trigger(string $event, array $payload): WebhookLog
    {
        $startTime = microtime(true);
        $signature = hash_hmac('sha256', json_encode($payload), $this->secret);

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => $event,
                    'Content-Type' => 'application/json',
                ])
                ->post($this->url, $payload);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            $log = $this->logs()->create([
                'event' => $event,
                'payload' => $payload,
                'status_code' => $response->status(),
                'response' => substr($response->body(), 0, 1000),
                'response_time_ms' => $responseTime,
                'success' => $response->successful(),
                'created_at' => now(),
            ]);

            if ($response->successful()) {
                $this->update([
                    'last_triggered_at' => now(),
                    'failure_count' => 0,
                ]);
            } else {
                $this->increment('failure_count');
                $this->disableIfTooManyFailures();
            }

            return $log;
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            $log = $this->logs()->create([
                'event' => $event,
                'payload' => $payload,
                'status_code' => null,
                'response' => $e->getMessage(),
                'response_time_ms' => $responseTime,
                'success' => false,
                'created_at' => now(),
            ]);

            $this->increment('failure_count');
            $this->disableIfTooManyFailures();

            return $log;
        }
    }

    protected function disableIfTooManyFailures(): void
    {
        if ($this->failure_count >= 10) {
            $this->update(['is_active' => false]);
        }
    }
}
