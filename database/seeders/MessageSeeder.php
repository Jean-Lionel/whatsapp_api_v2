<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Message;
use Illuminate\Support\Str;

class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $burundiPrefix = '+257';
        
        for ($i = 0; $i < 100; $i++) {
            // Generate Burundi numbers (8 digits, typically starting with 6, 7, 3, or 2)
            $fromNumber = $burundiPrefix . rand(6, 7) . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            $toNumber = $burundiPrefix . rand(6, 7) . str_pad(rand(0, 9999999), 7, '0', STR_PAD_LEFT);
            
            $createdAt = now()->subMinutes(rand(1, 10000));
            $sentAt = $createdAt->copy()->addSeconds(rand(1, 10));
            $readAt = rand(0, 1) ? $sentAt->copy()->addSeconds(rand(10, 300)) : null;

            Message::create([
                'wa_message_id' => 'wamid.' . Str::random(20),
                'conversation_id' => Str::uuid(), // Mock conversation ID for now
                'direction' => rand(0, 1) ? 'in' : 'out',
                'from_number' => $fromNumber,
                'to_number' => $toNumber,
                'type' => 'text',
                'body' => 'Test message content ' . Str::random(10),
                'payload' => null,
                'status' => 'read', // Simplified for testing
                'sent_at' => $sentAt,
                'read_at' => $readAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }
    }
}
