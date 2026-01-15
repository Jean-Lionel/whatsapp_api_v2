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
    Route::get('whatsapp-templates', [WhatsappDataController::class, 'getTemplates']);
    Route::apiResource('contacts', ContactController::class);
    Route::apiResource('messages', MessageController::class);

    // Side Bar Contact (ancien endpoint)
    Route::get('side_bar_contacts', [ContactController::class, 'sideBarContacts']);
    // Sidebar combinée (contacts + groupes)
    Route::get('sidebar', [ContactController::class, 'sidebar']);

    Route::get('message_phone/{phone}', [MessageController::class, 'messagePhone']);
    Route::post('send_whatsapp', [MessageController::class, 'sendWhatsApp']);

    // Groupes WhatsApp
    Route::apiResource('groups', WhatsappGroupController::class);
    Route::post('groups/{group}/contacts', [WhatsappGroupController::class, 'addContacts']);
    Route::delete('groups/{group}/contacts/{contact}', [WhatsappGroupController::class, 'removeContact']);
    Route::get('groups/{group}/messages', [WhatsappGroupController::class, 'messages']);
    Route::post('groups/{group}/send', [WhatsappGroupController::class, 'sendMessage']);
});

Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'handle']);
