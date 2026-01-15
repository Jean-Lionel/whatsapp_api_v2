<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'country_code', 'phone', 'email' ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullPhoneAttribute(): ?string
    {
        if (!$this->phone) {
            return null;
        }

        $countryCode = $this->country_code ? ltrim($this->country_code, '+') : '';
        $phone = ltrim($this->phone, '0');

        return $countryCode . $phone;
    }

    public function whatsappGroups()
    {
        return $this->belongsToMany(WhatsappGroup::class, 'whatsapp_group_contacts')
                    ->withPivot('is_admin')
                    ->withTimestamps();
    }

    protected $appends = ['full_phone'];
}
