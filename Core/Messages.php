<?php
namespace Bot\Core;

class Messages {
    public static function sendText(int $chatId, string $text): void {
        self::send('sendMessage', [
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }
    
    public static function sendPhoto(int $chatId, string $photoUrl, string $caption = ''): void {
        self::send('sendPhoto', [
            'chat_id' => $chatId,
            'photo' => $photoUrl,
            'caption' => $caption
        ]);
    }
    
    public static function sendTextAndPhoto(int $chatId, string $text, string $photoUrl): void {
        self::sendPhoto($chatId, $photoUrl, $text);
    }
    
    private static function send(string $method, array $data): void {
        $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/{$method}";
        $options = [
            'http' => [
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        file_get_contents($url, false, $context);
    }
}