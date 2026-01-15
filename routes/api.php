<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::apiResource('whatsapp-data', WhatsappDataController::class);
    Route::apiResource('contacts', ContactController::class);
    Route::apiResource('messages', MessageController::class);
    // Side Bar Contact
    Route::get('side_bar_contacts', [ContactController::class, 'sideBarContacts']);

    Route::get('message_phone/{phone}', [MessageController::class, 'messagePhone']);
    Route::post('send_whatsapp', [MessageController::class, 'sendWhatsApp']);
});

Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'handle']);
