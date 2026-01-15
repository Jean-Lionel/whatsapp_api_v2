<?php

namespace Database\Seeders;

use App\Models\Message;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $burundiPrefix = '+257';
        $adminNumber = '+25779000001';

        // Noms réalistes pour les contacts
        $contactNames = [
            'Jean Ndayisaba', 'Marie Niyonzima', 'Pierre Habimana', 'Claire Uwimana',
            'Patrick Nkurunziza', 'Diane Irakoze', 'Eric Ndikumana', 'Sandrine Bukuru',
            'David Niyongabo', 'Alice Nshimirimana', 'François Bizimana', 'Jeanne Havyarimana',
            'Michel Ntakirutimana', 'Christine Ndayizeye', 'Joseph Hakizimana', 'Aline Niyibizi',
            'Emmanuel Nsengiyumva', 'Béatrice Niyonkuru', 'André Ntahompagaze', 'Claudine Niyonsaba',
            'Gabriel Nduwimana', 'Sophie Ntakarutimana', 'Olivier Niyomwungere', 'Pascaline Nzeyimana',
            'Daniel Sindayigaya', 'Evelyne Nkeshimana', 'Innocent Niyonizigiye', 'Gaudence Nibigira',
            'Salvator Nzigamasabo', 'Josiane Nimbona', 'Léonidas Baranyanka', 'Goreth Ndayiragije',
            'Tharcisse Nibaruta', 'Espérance Niyokwizera', 'Philibert Ntirampeba', 'Joséphine Nyandwi',
            'Sylvestre Niyonkuru', 'Révérien Nduwayo', 'Annick Ntiranyibagira', 'Désiré Habonimana',
            'Fiacre Niyibigira', 'Clémence Nibizi', 'Evariste Ntaconayigize', 'Générose Nyabenda',
            'Apollinaire Niyongere', 'Jeanine Bizimungu', 'Vénuste Niyonzima', 'Marcelline Nsabimana',
            'Prosper Niyibizi', 'Dancille Ntihemuka',
        ];

        // Messages réalistes en français
        $messages = [
            'Bonjour, comment vas-tu ?',
            'Salut ! Tu es disponible demain ?',
            'Merci beaucoup pour ton aide !',
            'On se voit ce weekend ?',
            'J\'ai bien reçu ton message',
            'Peux-tu m\'envoyer le document ?',
            'Je serai en retard de 15 minutes',
            'C\'est noté, merci !',
            'Tu as des nouvelles de Marie ?',
            'La réunion est reportée à demain',
            'Félicitations pour ta promotion !',
            'Je t\'appelle dans 5 minutes',
            'Désolé, j\'étais occupé',
            'On peut discuter maintenant ?',
            'J\'ai une question importante',
            'Le projet avance bien',
            'N\'oublie pas la réunion de 14h',
            'Je te confirme pour samedi',
            'Bonne journée à toi aussi !',
            'Je rentre vers 18h',
            'Tu peux m\'aider avec ça ?',
            'C\'est parfait, merci beaucoup',
            'J\'attends ta réponse',
            'On fait comment pour demain ?',
            'Je suis d\'accord avec toi',
            'C\'est urgent, appelle-moi',
            'Je t\'envoie les détails par email',
            'Pas de problème !',
            'À quelle heure tu arrives ?',
            'Je te tiens au courant',
        ];

        // Générer 50 contacts uniques avec des conversations
        $contacts = [];
        for ($i = 0; $i < 50; $i++) {
            $contacts[] = [
                'name' => $contactNames[$i],
                'phone' => $burundiPrefix.rand(6, 7).str_pad(rand(1000000, 9999999), 7, '0', STR_PAD_LEFT),
            ];
        }

        // Pour chaque contact, créer plusieurs messages
        foreach ($contacts as $index => $contact) {
            $conversationId = Str::uuid();
            $messageCount = rand(3, 15);

            // Créer des messages espacés dans le temps pour ce contact
            $baseTime = now()->subDays(rand(0, 30))->subHours(rand(0, 23));

            for ($j = 0; $j < $messageCount; $j++) {
                $isIncoming = rand(0, 1);
                $createdAt = $baseTime->copy()->addMinutes($j * rand(5, 120));
                $sentAt = $createdAt->copy()->addSeconds(rand(1, 5));

                // Varier les statuts pour les messages non lus
                $statuses = ['sent', 'delivered', 'read'];
                $status = $j === $messageCount - 1 && rand(0, 3) === 0 ? 'delivered' : 'read';
                $readAt = $status === 'read' ? $sentAt->copy()->addSeconds(rand(10, 300)) : null;

                Message::create([
                    'wa_message_id' => 'wamid.'.Str::random(20),
                    'conversation_id' => $conversationId,
                    'direction' => $isIncoming ? 'in' : 'out',
                    'from_number' => $isIncoming ? $contact['phone'] : $adminNumber,
                    'to_number' => $isIncoming ? $adminNumber : $contact['phone'],
                    'type' => 'text',
                    'body' => $messages[array_rand($messages)],
                    'payload' => null,
                    'status' => $status,
                    'sent_at' => $sentAt,
                    'read_at' => $readAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);
            }
        }
    }
}
