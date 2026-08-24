<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Padrón Central del IYEM
    |--------------------------------------------------------------------------
    |
    | Credenciales que este proyecto usa para consumir la API del padrón
    | cuando actúa como cliente (por ejemplo, en pruebas de integración).
    | Los sistemas satélite declaran estas mismas claves en su propio .env.
    |
    */

    'padron' => [
        'url' => env('IYEM_PADRON_URL', env('APP_URL').'/api/v1'),
        'token' => env('IYEM_PADRON_TOKEN'),
    ],

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
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
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

];
