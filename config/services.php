<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
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

    // Servicio SOAP heredado MeteoPoloNorteService (lo proporciona el docente; hay un mock en mock-soap/).
    'meteo' => [
        'url' => env('METEO_SOAP_URL', 'http://127.0.0.1:8089/meteo'),
        'timeout' => env('METEO_SOAP_TIMEOUT', 3),
        'intentos' => env('METEO_SOAP_INTENTOS', 2),
        'backoff_ms' => env('METEO_SOAP_BACKOFF_MS', 300),
    ],

];
