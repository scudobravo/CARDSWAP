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

    /*
    |--------------------------------------------------------------------------
    | AfterShip Tracking (CardSwap V1 - unica fonte tracking)
    |--------------------------------------------------------------------------
    | API: https://api.aftership.com/tracking/2026-01
    | Header: as-api-key
    | Webhook: verifica header aftership-hmac-sha256 con AFTERSHIP_WEBHOOK_SECRET
    */
    'aftership' => [
        'api_key' => env('AFTERSHIP_API_KEY'),
        'webhook_secret' => env('AFTERSHIP_WEBHOOK_SECRET'),
        'api_version' => '2026-01',
        'base_url' => 'https://api.aftership.com',
    ],

    // ============================================
    // SHIPPO CONFIG - DEPRECATED
    // ============================================
    // ATTENZIONE: Shippo è DEPRECATO e NON fa parte di CardSwap Shipping V1.
    // 
    // Shippo NON viene più utilizzato per:
    // - Pricing (usa CardSwap Shipping V1: shipping_price_tables)
    // - Checkout (usa POST /api/shipping/v1/calculate-rates)
    // - Tracking (usa AfterShip)
    // - Post-ordine (usa AfterShip webhook)
    // 
    // Questa configurazione è mantenuta solo per compatibilità legacy.
    // Le variabili SHIPPO_* in .env sono LEGACY e NON richieste per CardSwap V1.
    // ============================================
    'shippo' => [
        'key' => env('SHIPPO_API_KEY'), // LEGACY - NON richiesto per CardSwap V1
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
        // Basato sui corrieri effettivamente disponibili nel tuo account SHIPPO
        'carriers' => [
            'domestic' => [
                // Poste Italiane per spedizioni domestiche IT → IT
                // Object ID fornito da Shippo Support: a25aee94fb0f4e86ab160ecb29b55420
                'poste_italiane' => [
                    'name' => 'Poste Italiane',
                    'code' => 'poste_italiane',
                    'domestic_only' => true,
                    'countries' => ['IT'],
                    'priority' => 1,
                    'available' => true,
                    'account_id' => 'a25aee94fb0f4e86ab160ecb29b55420' // UUID reale da Shippo
                ]
            ],
            'international' => [
                // Corrieri disponibili nel tuo account SHIPPO
                'chronopost' => [
                    'name' => 'Chronopost',
                    'code' => 'chronopost',
                    'domestic_only' => false,
                    'countries' => ['*'], // Tutti i paesi
                    'priority' => 1,
                    'available' => true,
                    'account_id' => '0d42a59dfa95443cb18b0acef4557a47'
                ],
                'colissimo' => [
                    'name' => 'Colissimo',
                    'code' => 'colissimo',
                    'domestic_only' => false,
                    'countries' => ['*'], // Tutti i paesi
                    'priority' => 2,
                    'available' => true,
                    'account_id' => '2c68c6e7487040de80f11f3b3fa84abb'
                ],
                'deutsche_post' => [
                    'name' => 'Deutsche Post',
                    'code' => 'deutsche_post',
                    'domestic_only' => false,
                    'countries' => ['*'], // Tutti i paesi
                    'priority' => 3,
                    'available' => true,
                    'account_id' => '084d73f39a564ba791a7974fb930cfe5'
                ],
                'correos' => [
                    'name' => 'Correos',
                    'code' => 'correos',
                    'domestic_only' => false,
                    'countries' => ['*'], // Tutti i paesi
                    'priority' => 4,
                    'available' => true,
                    'account_id' => 'ef37c4123d7c47558179ec50e83a9dc4'
                ],
                'couriersplease' => [
                    'name' => 'CouriersPlease',
                    'code' => 'couriersplease',
                    'domestic_only' => false,
                    'countries' => ['*'], // Tutti i paesi
                    'priority' => 5,
                    'available' => true,
                    'account_id' => '74292fffa8e14fc29dd29ac0a7d5b875'
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

    /*
    |--------------------------------------------------------------------------
    | CardSwap — costo di gestione (commissione acquirente in checkout)
    |--------------------------------------------------------------------------
    | Percentuale applicata al totale merce + spedizione (non alla sola merce).
    | Override: BUYER_MANAGEMENT_FEE_RATE nel .env (es. 0.035).
    */
    'cardswap' => [
        'buyer_management_fee_rate' => (float) env('BUYER_MANAGEMENT_FEE_RATE', 0.035),
    ],

];
