<?php
return [
    'app' => [
        'name' => 'Telegram Calendar Mini App',
        'env' => 'production',
        'debug' => false,
        'base_url' => 'https://your-domain.com',
        'timezone' => 'Europe/Moscow',
        'token_secret' => 'CHANGE_ME_TO_A_LONG_RANDOM_SECRET_64_CHARS',
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
        'name' => 'telegram_calendar',
        'user' => 'root',
        'pass' => '',
        'charset' => 'utf8mb4',
    ],
    'telegram' => [
        'bot_token' => 'PASTE_BOT_TOKEN_HERE',
    ],
    'cors' => [
        'allowed_origins' => ['*'],
    ],
];
