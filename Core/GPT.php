<?php

class GPT {
    public static $api_key = "";
    private static $api_url = 'https://api.aitunnel.ru/v1/chat/completions';
    private static $model = 'deepseek-r1';
    private static $max_tokens = 5000;

    public static function Init(string $key){
        self::$api_key = $key;
    }

    public static function GetMessage(string $userMessage): string {
        $data_chat = [
            'model' => self::$model,
            'max_tokens' => self::$max_tokens,
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $userMessage
                ]
            ]
        ];

        $ch = curl_init(self::$api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . self::$api_key
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data_chat));

        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }
}
