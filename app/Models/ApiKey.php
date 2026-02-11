<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'key',
        'scopes',
        'rate_limit',
        'expires_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'expires_at' => 'datetime',
            'last_used_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function usageLogs(): HasMany
    {
        return $this->hasMany(ApiKeyUsage::class);
    }

    public static function generateKey(): string
    {
        return 'wapi_'.Str::random(48);
    }

    public function hasScope(string $scope): bool
    {
        $scopes = $this->scopes ?? ['read', 'write', 'send_messages'];

        return in_array($scope, $scopes);
    }

    public function isValid(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function recordUsage(string $endpoint, string $method, int $statusCode, ?string $ip = null, ?int $responseTime = null): void
    {
        $this->usageLogs()->create([
            'endpoint' => $endpoint,
            'method' => $method,
            'status_code' => $statusCode,
            'ip_address' => $ip,
            'response_time_ms' => $responseTime,
            'created_at' => now(),
        ]);

        $this->update(['last_used_at' => now()]);
    }
}
