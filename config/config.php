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
        'name' => 'jarvis_db',
        'user' => 'jarvis_user',
        'pass' => 'Gadel2001%',
        'charset' => 'utf8mb4',
    ],
    'telegram' => [
        'bot_token' => '8228911470:AAEzHxOT_20UipKSWVsxk_XPWuvHpWmhsNk',
    ],

    'ai' => [
        'api_key' => 'sk-aitunnel-ZZcmQfH8aw7aehBDSmBOBLYFodIY8VaB',
        'api_url' => 'https://api.aitunnel.ru/v1',
        'chat_model' => 'gpt-4o-mini',
        'transcription_model' => 'whisper-1',
    ],
    'cors' => [
        'allowed_origins' => ['*'],
    ],
];
