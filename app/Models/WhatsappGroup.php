<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'wa_group_id',
        'invite_link',
        'icon_url',
        'user_id'
    ];

    /**
     * Get the contacts that belong to the group.
     */
    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'whatsapp_group_contacts')
                    ->withPivot('is_admin')
                    ->withTimestamps();
    }

    /**
     * Add a contact to the group.
     */
    public function addContact(Contact $contact, bool $isAdmin = false)
    {
        return $this->contacts()->syncWithoutDetaching([
            $contact->id => ['is_admin' => $isAdmin]
        ]);
    }

    /**
     * Remove a contact from the group.
     */
    public function removeContact(Contact $contact)
    {
        return $this->contacts()->detach($contact->id);
    }

    /**
     * Get the admins of the group.
     */
    public function getAdmins()
    {
        return $this->contacts()->wherePivot('is_admin', true)->get();
    }

    /**
     * Check if a contact is in the group.
     */
    public function hasContact($contactId)
    {
        return $this->contacts()->where('contact_id', $contactId)->exists();
    }
}
