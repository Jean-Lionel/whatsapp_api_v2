<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'wa_message_id',
        'conversation_id',
        'direction',
        'from_number',
        'to_number',
        'type',
        'body',
        'payload',
        'status',
        'sent_at',
        'read_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'sent_at' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }


}
