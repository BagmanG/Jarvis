<?php
return [
    'app' => [
        'name' => 'Telegram Calendar Mini App',
        'env' => 'production',
        'debug' => false,
        'base_url' => '',
        'timezone' => 'Europe/Moscow',
        'token_secret' => 'b5730d1a-fddc-43b4-b993-69b38eb57b4c',
        'max_avatar_size' => 5 * 1024 * 1024,
        'allowed_avatar_mime' => [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ],
    ],
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'bagmanov13_jarv',
        'user' => 'bagmanov13_jarv',
        'pass' => 'Gadel2001%',
        'charset' => 'utf8mb4',
    ],
    'telegram' => [
        'bot_token' => '8228911470:AAGDl22PDWAXj6xFnCra2xUM3zXN_wPglPA',
    ],
    'cors' => [
        'allowed_origins' => ['*'],
    ],
];
