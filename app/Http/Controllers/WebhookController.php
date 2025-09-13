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
          $data = $request->all();
          Log::info($data);

          return response()->json([
            "msg" => "Bonjour je suis en", 
            "data" => $data
          ]);
    }



}
