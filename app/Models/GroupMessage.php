<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'whatsapp_group_id',
        'user_id',
        'type',
        'body',
        'template_name',
        'template_parameters',
        'delivery_status',
        'total_recipients',
        'delivered_count',
        'failed_count',
    ];

    protected function casts(): array
    {
        return [
            'template_parameters' => 'array',
            'delivery_status' => 'array',
        ];
    }

    public function group()
    {
        return $this->belongsTo(WhatsappGroup::class, 'whatsapp_group_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
