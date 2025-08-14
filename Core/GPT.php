<?php

class GPT {
    public static $api_key = "";
    private static $api_url = 'https://api.aitunnel.ru/v1/chat/completions';
    private static $model = 'gpt-4.1-mini';
    private static $max_tokens = 5000;
    private static $system_prompt = 'Ты — Джарвис, искуственный интеллект, созданный для помощи в достижении целей. Ты отвечаешь вежливо, лаконично и по делу.';

    public static function Init(string $key){
        self::$api_key = $key;
    }

    public static function GetMessage(string $userMessage): string {
        // Проверка, что API ключ установлен
        if (empty(self::$api_key)) {
            throw new Exception('API key is not set. Please call GPT::Init() first.');
        }

        $data_chat = [
            'model' => self::$model,
            'max_tokens' => self::$max_tokens,
            'messages' => [
                [
                    'role' => 'system',  // Системное сообщение задаёт поведение модели
                    'content' => self::$system_prompt
                ],
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

        // Декодируем JSON ответ
        $responseData = json_decode($response, true);
        
        // Проверяем на ошибки декодирования
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to decode JSON response');
        }
        
        // Извлекаем content из ответа
        if (isset($responseData['choices'][0]['message']['content'])) {
            return $responseData['choices'][0]['message']['content'];
        }
        
        throw new Exception('Invalid response structure');
    }
}