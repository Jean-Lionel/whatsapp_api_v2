<?php

namespace App\Console\Commands;

use App\Models\Contact;
use Illuminate\Console\Command;

class HackerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:hacker-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //
        for ($i = 0; $i < 10; $i++) {
            $this->info('');
            Contact::create([
                
            ]);
        }
        $this->info('Hacker command executed');
    }
}
