<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'whatsapp' => [
        'api_url' => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v22.0/'),
        'api_version' => env('WHATSAPP_API_VERSION', 'v22.0'),
        'api_token' => env('WHATSAPP_API_TOKEN'),
        'phone_id' => env('WHATSAPP_API_PHONE_ID'),
        'phone_number' => env('WHATSAPP_API_PHONE_NUMBER'),
        'business_id' => env('WHATSAPP_API_BUSINESS_ID'),
        'business_account_id' => env('WHATSAPP_API_BUSINESS_ID'),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'whatsapp_webhook_token'),
    ],

];
