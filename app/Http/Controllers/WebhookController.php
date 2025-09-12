<?php

namespace App\Http\Controllers;

use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
   // private $verifyToken = ; // le même que tu as configuré
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function handle(Request $request)
    {
        $mode = $request->query('hub.mode');
        $challenge = $request->query('hub.challenge');
        $token = $request->query('hub.verify_token');

        Log::info("Request: ", var_dump($request));

        Log::info("Mode: " . $mode);
        Log::info("Challenge: " . $challenge);
        Log::info("Token: " . $token);
        Log::info("WHATSAPP_VERIFY_TOKEN: " . env('WHATSAPP_VERIFY_TOKEN'));


        if ($mode === 'subscribe' && $token === env('WHATSAPP_VERIFY_TOKEN')) {
            Log::info("WEBHOOK VERIFIED: " . $challenge);
            return response($challenge, 200);
        } else {
            return response('', 403);
        }
    }



}
