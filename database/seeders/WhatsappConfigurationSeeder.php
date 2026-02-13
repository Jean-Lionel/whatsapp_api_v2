<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\WhatsappConfiguration;
use Illuminate\Database\Seeder;

class WhatsappConfigurationSeeder extends Seeder
{
    /**
     * Seed the WhatsApp configurations from .env values.
     */
    public function run(): void
    {
        // Get the first user or create one
        $user = User::first();

        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Admin',
                'email' => 'admin@advanceditb.com',
                'password' => bcrypt('Advanced2026'),
            ]);
        }

        // Create the WhatsApp configuration from .env values
        WhatsappConfiguration::updateOrCreate(
            [
                'user_id' => $user->id,
                'phone_id' => config('services.whatsapp.phone_id'),
            ],
            [
                'name' => 'Production WhatsApp',
                'api_url' => config('services.whatsapp.api_url', 'https://graph.facebook.com'),
                'api_version' => config('services.whatsapp.api_version', 'v22.0'),
                'api_token' => config('services.whatsapp.api_token'),
                'phone_id' => config('services.whatsapp.phone_id'),
                'phone_number' => config('services.whatsapp.phone_number'),
                'business_id' => config('services.whatsapp.business_id'),
                'verify_token' => config('services.whatsapp.verify_token'),
                'is_active' => true,
                'is_default' => true,
            ]
        );

        $this->command->info('WhatsApp configuration seeded successfully!');
    }
}
