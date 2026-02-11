<?php

namespace App\Policies;

use App\Models\ClientWebhook;
use App\Models\User;

class ClientWebhookPolicy
{
    public function view(User $user, ClientWebhook $webhook): bool
    {
        return $user->id === $webhook->user_id;
    }

    public function update(User $user, ClientWebhook $webhook): bool
    {
        return $user->id === $webhook->user_id;
    }

    public function delete(User $user, ClientWebhook $webhook): bool
    {
        return $user->id === $webhook->user_id;
    }
}
