<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappGroupContact extends Model
{
    protected $fillable = [
        'whatsapp_group_id',
        'contact_id',
        'is_admin'
    ];

    public function group()
    {
        return $this->belongsTo(WhatsappGroup::class, 'whatsapp_group_id');
    }

    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }
}
