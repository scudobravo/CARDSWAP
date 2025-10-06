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

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'connect_client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
        'identity_enabled' => env('STRIPE_IDENTITY_ENABLED', false),
    ],

    'shippo' => [
        'key' => env('SHIPPO_API_KEY'),
        'sender' => [
            'name' => env('SHIPPO_SENDER_NAME', 'CardSwap Marketplace'),
            'company' => env('SHIPPO_SENDER_COMPANY', 'CardSwap'),
            'street1' => env('SHIPPO_SENDER_STREET1'),
            'city' => env('SHIPPO_SENDER_CITY'),
            'state' => env('SHIPPO_SENDER_STATE'),
            'zip' => env('SHIPPO_SENDER_ZIP'),
            'country' => env('SHIPPO_SENDER_COUNTRY', 'IT'),
            'phone' => env('SHIPPO_SENDER_PHONE'),
            'email' => env('SHIPPO_SENDER_EMAIL'),
        ],
    ],

];
