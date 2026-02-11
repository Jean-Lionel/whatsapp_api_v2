<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappConfiguration extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'api_url',
        'api_version',
        'api_token',
        'phone_id',
        'phone_number',
        'business_id',
        'verify_token',
        'is_active',
        'is_default',
    ];

    protected $hidden = [
        'api_token',
        'verify_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'api_token' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function getDefaultForUser(int $userId): ?self
    {
        return static::where('user_id', $userId)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first()
            ?? static::where('user_id', $userId)
                ->where('is_active', true)
                ->first();
    }

    public static function getFromEnv(): array
    {
        return [
            'api_url' => config('services.whatsapp.api_url'),
            'api_version' => config('services.whatsapp.api_version'),
            'api_token' => config('services.whatsapp.api_token'),
            'phone_id' => config('services.whatsapp.phone_id'),
            'phone_number' => config('services.whatsapp.phone_number'),
            'business_id' => config('services.whatsapp.business_id'),
            'verify_token' => config('services.whatsapp.verify_token'),
        ];
    }

    public function setAsDefault(): void
    {
        static::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        $this->update(['is_default' => true]);
    }

    public function toServiceConfig(): array
    {
        return [
            'api_url' => $this->api_url,
            'api_version' => $this->api_version,
            'api_token' => $this->api_token,
            'phone_id' => $this->phone_id,
            'phone_number' => $this->phone_number,
            'business_id' => $this->business_id,
            'verify_token' => $this->verify_token,
        ];
    }
}
