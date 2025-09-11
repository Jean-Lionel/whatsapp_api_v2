<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WebhookController extends Controller
{
    private $verifyToken = "MON_TOKEN_SECRET"; // le même que tu as configuré
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

        \Log::info("Webhook verification: mode=$mode, challenge=$challenge, token=$token");

        if ($mode === 'subscribe' && $token === $this->verifyToken) {
            \Log::info("WEBHOOK VERIFIED: " . $challenge);
            return response($challenge, 200);
        } else {
            return response('', 403);
        }
    }



}
