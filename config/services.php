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
        // Corrieri disponibili per spedizioni dall'Italia
        'carriers' => [
            'domestic' => [
                'poste_italiane' => [
                    'name' => 'Poste Italiane',
                    'code' => 'poste_italiane',
                    'domestic_only' => true,
                    'countries' => ['IT']
                ]
            ],
            'international' => [
                'dhl_express' => [
                    'name' => 'DHL Express',
                    'code' => 'dhl_express',
                    'domestic_only' => false,
                    'countries' => ['*'], // Tutti i paesi
                    'priority' => 1
                ],
                'ups' => [
                    'name' => 'UPS',
                    'code' => 'ups',
                    'domestic_only' => false,
                    'countries' => ['*'], // Tutti i paesi
                    'priority' => 2
                ],
                'fedex' => [
                    'name' => 'FedEx',
                    'code' => 'fedex',
                    'domestic_only' => false,
                    'countries' => ['*'], // Tutti i paesi
                    'priority' => 3
                ],
                'gls' => [
                    'name' => 'GLS',
                    'code' => 'gls',
                    'domestic_only' => false,
                    'countries' => ['IT', 'FR', 'DE', 'ES', 'NL', 'BE', 'AT', 'CH', 'SE', 'NO', 'DK', 'FI', 'PL', 'CZ', 'HU', 'PT', 'GR', 'RO', 'BG', 'HR', 'SI', 'SK', 'LT', 'LV', 'EE', 'IE', 'LU', 'MT', 'CY'], // Solo UE
                    'priority' => 4
                ]
            ]
        ],
        // Configurazione per calcolo tariffe
        'pricing' => [
            'markup' => 1.60, // €1,60 markup fisso
            'management_fee' => 0.90, // €0,90 spese gestione
            'min_weight' => 0.1, // Peso minimo 100g
            'max_weight' => 30.0, // Peso massimo 30kg
            'default_parcel' => [
                'length' => 22,
                'width' => 15,
                'height' => 3,
                'distance_unit' => 'cm',
                'weight' => 0.1,
                'mass_unit' => 'kg'
            ]
        ]
    ],

];
