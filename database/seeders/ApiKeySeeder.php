<?php

namespace Database\Seeders;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ApiKeySeeder extends Seeder
{
    /**
     * Seed test API keys.
     */
    public function run(): void
    {
        $user = User::first();

        if (! $user) {
            $this->command->warn('No user found. Please run UserSeeder first.');

            return;
        }

        // Create a full-access API key for testing
        $fullAccessKey = ApiKey::updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Test Full Access',
            ],
            [
                'key' => 'whatsapp_test_' . Str::random(32),
                'scopes' => ['read', 'write', 'send_messages'],
                'rate_limit' => 100,
                'is_active' => true,
            ]
        );

        // Create a read-only API key
        $readOnlyKey = ApiKey::updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Test Read Only',
            ],
            [
                'key' => 'whatsapp_readonly_' . Str::random(32),
                'scopes' => ['read'],
                'rate_limit' => 50,
                'is_active' => true,
            ]
        );

        $this->command->info('API keys seeded successfully!');
        $this->command->info('Full Access Key: ' . $fullAccessKey->key);
        $this->command->info('Read Only Key: ' . $readOnlyKey->key);
    }
}
