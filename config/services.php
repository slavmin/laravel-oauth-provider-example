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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'auth' => [
        'esia' => [
            'auth_url' => env('AUTH_PROVIDER_URL', 'https://esia-portal1.test.gosuslugi.ru') . '/aas/oauth2/v2/ac',
            'token_url' => env('AUTH_PROVIDER_URL', 'https://esia-portal1.test.gosuslugi.ru') . '/aas/oauth2/v3/te',
            'user_info_url' => env('AUTH_PROVIDER_URL', 'https://esia-portal1.test.gosuslugi.ru') . '/rs/prns',
            'client_id' => env('AUTH_PROVIDER_CLIENT_ID'),
            'client_secret' => env('AUTH_PROVIDER_CLIENT_SECRET'),
            'cert_hash' => env('AUTH_PROVIDER_CERT_HASH'),
            'cert_path' => storage_path() . env('AUTH_PROVIDER_CERT_PATH'),
            'cert_pass_phrase' => env('AUTH_PROVIDER_CERT_PASS_PHRASE'),
            'private_key_path' => storage_path() . env('AUTH_PROVIDER_PRIVATE_KEY'),
            'private_key_phrase' => env('AUTH_PROVIDER_PRIVATE_KEY_PHRASE'),
            'redirect' => env('APP_URL') . '/auth/login/esia/callback',
        ],
    ]

];
