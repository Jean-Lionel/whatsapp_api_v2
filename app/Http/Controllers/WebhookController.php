<?php

namespace App\Http\Controllers;

use App\Models\WhatsappData;
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
          $data = $request->all();
          Log::info("Message received");
          WhatsappData::create([
              'body' => $data
          ]);
    }

}
