<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKeyUsage extends Model
{
    public $timestamps = false;

    protected $table = 'api_key_usage';

    protected $fillable = [
        'api_key_id',
        'endpoint',
        'method',
        'status_code',
        'ip_address',
        'response_time_ms',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function apiKey(): BelongsTo
    {
        return $this->belongsTo(ApiKey::class);
    }
}
