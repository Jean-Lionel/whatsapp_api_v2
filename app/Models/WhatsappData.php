<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappData extends Model
{
    /** @use HasFactory<\Database\Factories\WhatsappDataFactory> */
    use HasFactory;

    protected $fillable = [
        'body',
        'status',
    ];

    protected $casts = [
        'body' => 'array',
    ];
}
