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

    // 'slack' => [
    //     'notifications' => [
    //         'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
    //         'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
    //     ],
    // ],

    'payments' => [
        'driver' => env('PAYMENT_DRIVER', 'local'),
    ],

    'campay' => [
        'base_url' => env('CAMPAY_BASE_URL', 'https://demo.campay.net'),
        'token' => env('CAMPAY_TOKEN'),
        'username' => env('CAMPAY_USERNAME'),
        'password' => env('CAMPAY_PASSWORD'),
        'timeout' => env('CAMPAY_TIMEOUT', 20),
        'connect_timeout' => env('CAMPAY_CONNECT_TIMEOUT', 10),
        'fallback_to_local' => env('CAMPAY_FALLBACK_TO_LOCAL', false),
    ],

];
