<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WhatsappConfiguration;

class WhatsappConfigurationPolicy
{
    public function view(User $user, WhatsappConfiguration $configuration): bool
    {
        return $user->id === $configuration->user_id;
    }

    public function update(User $user, WhatsappConfiguration $configuration): bool
    {
        return $user->id === $configuration->user_id;
    }

    public function delete(User $user, WhatsappConfiguration $configuration): bool
    {
        return $user->id === $configuration->user_id;
    }
}
