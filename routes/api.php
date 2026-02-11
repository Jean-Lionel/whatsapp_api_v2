<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes (No Authentication)
|--------------------------------------------------------------------------
*/

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// WhatsApp Webhooks (Meta verification)
Route::get('/webhook', [WebhookController::class, 'verify']);
Route::post('/webhook', [WebhookController::class, 'handle']);

/*
|--------------------------------------------------------------------------
| API Key Routes (Public API Access)
|--------------------------------------------------------------------------
| These routes are accessible via X-API-Key header or api_key query param
*/

Route::middleware('api.key')->group(function () {
    // Contacts - Read
    Route::middleware('api.key:read')->group(function () {
        Route::get('/contacts', [ContactController::class, 'index']);
        Route::get('/contacts/{contact}', [ContactController::class, 'show']);
        Route::get('/sidebar', [ContactController::class, 'sidebar']);
        Route::get('/side_bar_contacts', [ContactController::class, 'sideBarContacts']);
    });

    // Contacts - Write
    Route::middleware('api.key:write')->group(function () {
        Route::post('/contacts', [ContactController::class, 'store']);
        Route::put('/contacts/{contact}', [ContactController::class, 'update']);
        Route::delete('/contacts/{contact}', [ContactController::class, 'destroy']);
    });

    // Messages - Read
    Route::middleware('api.key:read')->group(function () {
        Route::get('/messages', [MessageController::class, 'index']);
        Route::get('/messages/{message}', [MessageController::class, 'show']);
        Route::get('/message_phone/{phone}', [MessageController::class, 'messagePhone']);
    });

    // Messages - Send
    Route::middleware('api.key:send_messages')->group(function () {
        Route::post('/send_whatsapp', [MessageController::class, 'sendWhatsApp']);
        Route::post('/messages', [MessageController::class, 'store']);
    });

    // Groups - Read
    Route::middleware('api.key:read')->group(function () {
        Route::get('/groups', [WhatsappGroupController::class, 'index']);
        Route::get('/groups/{group}', [WhatsappGroupController::class, 'show']);
        Route::get('/groups/{group}/messages', [WhatsappGroupController::class, 'messages']);
    });

    // Groups - Write
    Route::middleware('api.key:write')->group(function () {
        Route::post('/groups', [WhatsappGroupController::class, 'store']);
        Route::put('/groups/{group}', [WhatsappGroupController::class, 'update']);
        Route::delete('/groups/{group}', [WhatsappGroupController::class, 'destroy']);
        Route::post('/groups/{group}/contacts', [WhatsappGroupController::class, 'addContacts']);
        Route::delete('/groups/{group}/contacts/{contact}', [WhatsappGroupController::class, 'removeContact']);
    });

    // Groups - Send Messages
    Route::middleware('api.key:send_messages')->group(function () {
        Route::post('/groups/{group}/send', [WhatsappGroupController::class, 'sendMessage']);
    });

    // WhatsApp Data & Templates
    Route::middleware('api.key:read')->group(function () {
        Route::get('/whatsapp-data', [WhatsappDataController::class, 'index']);
        Route::get('/whatsapp-data/{whatsapp_datum}', [WhatsappDataController::class, 'show']);
        Route::get('/whatsapp-templates', [WhatsappDataController::class, 'getTemplates']);
    });

    Route::middleware('api.key:write')->group(function () {
        Route::post('/whatsapp-data', [WhatsappDataController::class, 'store']);
        Route::put('/whatsapp-data/{whatsapp_datum}', [WhatsappDataController::class, 'update']);
        Route::delete('/whatsapp-data/{whatsapp_datum}', [WhatsappDataController::class, 'destroy']);
    });
});

/*
|--------------------------------------------------------------------------
| Sanctum Routes (Dashboard & Key Management)
|--------------------------------------------------------------------------
| These routes require Sanctum token authentication (login required)
*/

Route::middleware('auth:sanctum')->group(function () {
    // User
    Route::get('/user', fn (Request $request) => $request->user());
    Route::post('/logout', [AuthController::class, 'logout']);

    // API Keys Management
    Route::prefix('api-keys')->group(function () {
        Route::get('/', [ApiKeyController::class, 'index']);
        Route::post('/', [ApiKeyController::class, 'store']);
        Route::get('/{apiKey}', [ApiKeyController::class, 'show']);
        Route::put('/{apiKey}', [ApiKeyController::class, 'update']);
        Route::post('/{apiKey}/revoke', [ApiKeyController::class, 'revoke']);
        Route::delete('/{apiKey}', [ApiKeyController::class, 'destroy']);
        Route::get('/{apiKey}/usage', [ApiKeyController::class, 'usage']);
    });

    // Client Webhooks Management
    Route::prefix('webhooks')->group(function () {
        Route::get('/', [ClientWebhookController::class, 'index']);
        Route::post('/', [ClientWebhookController::class, 'store']);
        Route::get('/{webhook}', [ClientWebhookController::class, 'show']);
        Route::put('/{webhook}', [ClientWebhookController::class, 'update']);
        Route::delete('/{webhook}', [ClientWebhookController::class, 'destroy']);
        Route::post('/{webhook}/regenerate-secret', [ClientWebhookController::class, 'regenerateSecret']);
        Route::get('/{webhook}/logs', [ClientWebhookController::class, 'logs']);
        Route::post('/{webhook}/test', [ClientWebhookController::class, 'test']);
    });

    // WhatsApp Configurations Management
    Route::prefix('whatsapp-configurations')->group(function () {
        Route::get('/', [WhatsappConfigurationController::class, 'index']);
        Route::post('/', [WhatsappConfigurationController::class, 'store']);
        Route::get('/{configuration}', [WhatsappConfigurationController::class, 'show']);
        Route::put('/{configuration}', [WhatsappConfigurationController::class, 'update']);
        Route::delete('/{configuration}', [WhatsappConfigurationController::class, 'destroy']);
        Route::post('/{configuration}/set-default', [WhatsappConfigurationController::class, 'setDefault']);
        Route::post('/{configuration}/test', [WhatsappConfigurationController::class, 'testConnection']);
    });
});
