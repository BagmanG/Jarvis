<?php

class GPT {
    public static $api_key = "";
    private static $api_url = 'https://api.aitunnel.ru/v1/chat/completions';
    private static $model = 'gpt-4.1-mini';
    private static $max_tokens = 5000;
    private static $system_prompt = 'Ты — Джарвис, искуственный интеллект, созданный для помощи в достижении целей. Ты отвечаешь вежливо, лаконично и по делу. 

Ты можешь работать с задачами пользователя. Если пользователь просит добавить задачу, используй функцию add_task. Если просит удалить задачу - delete_task. Если просит показать задачи - list_tasks.

При добавлении задач:
- Если пользователь говорит "сегодня" или "today" - используй сегодняшнюю дату
- Если говорит "завтра" или "tomorrow" - используй завтрашнюю дату
- Если не указано время - используй 12:00 по умолчанию
- Если не указан приоритет - используй "medium" по умолчанию

Всегда будь дружелюбным и подтверждай выполнение действий.';

    public static function Init(string $key){
        self::$api_key = $key;
    }

    public static function InitUserData(string $name, string $about){
        self::$system_prompt = "Ты — Джарвис, искуственный интеллект, созданный для помощи в достижении целей. Ты отвечаешь вежливо, лаконично и по делу. Ты всегда отвечаешь без форматирования, только текст! Пользователя зовут '$name' поэтому всегда обращайся к нему по имени. Вот информация о пользователе: $about";
    }

    public static function GetMessage(string $userMessage, array $history = []): array {
        // Проверка, что API ключ установлен
        if (empty(self::$api_key)) {
            throw new Exception('API key is not set. Please call GPT::Init() first.');
        }

        // Формируем массив сообщений
        $messages = [
            [
                'role' => 'system',
                'content' => self::$system_prompt
            ]
        ];

        // Добавляем историю сообщений, если есть
        if (!empty($history)) {
            foreach ($history as $message) {
                $messages[] = $message;
            }
        }

        // Добавляем текущее сообщение пользователя
        $messages[] = [
            'role' => 'user',
            'content' => $userMessage
        ];

        // Подключаем TaskHandler для получения доступных функций
        require_once __DIR__ . '/TaskHandler.php';
        $tools = TaskHandler::getAvailableFunctions();

        $data_chat = [
            'model' => self::$model,
            'max_tokens' => self::$max_tokens,
            'messages' => $messages,
            'tools' => $tools,
            'tool_choice' => 'auto'
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
        
        // Проверяем, есть ли вызов функции
        if (isset($responseData['choices'][0]['message']['tool_calls'])) {
            $toolCalls = $responseData['choices'][0]['message']['tool_calls'];
            $functionResults = [];
            
            foreach ($toolCalls as $toolCall) {
                if (isset($toolCall['function'])) {
                    $functionName = $toolCall['function']['name'];
                    $arguments = json_decode($toolCall['function']['arguments'], true);
                    
                    // Получаем user_id из текущего контекста
                    $userId = self::getCurrentUserId();
                    
                    // Вызываем функцию
                    $result = TaskHandler::handleFunctionCall($functionName, $arguments, $userId);
                    $functionResults[] = [
                        'tool_call_id' => $toolCall['id'],
                        'role' => 'tool',
                        'content' => json_encode($result)
                    ];
                }
            }
            
            // Если есть результаты функций, отправляем их обратно в GPT
            if (!empty($functionResults)) {
                $messages[] = $responseData['choices'][0]['message'];
                $messages = array_merge($messages, $functionResults);
                
                // Отправляем второй запрос с результатами функций
                $data_chat['messages'] = $messages;
                
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
                
                $responseData = json_decode($response, true);
            }
        }
        
        // Извлекаем content из ответа
        if (isset($responseData['choices'][0]['message']['content'])) {
            return [
                'content' => $responseData['choices'][0]['message']['content'],
                'has_function_call' => isset($responseData['choices'][0]['message']['tool_calls'])
            ];
        }
        
        throw new Exception('Invalid response structure');
    }
    
    // Получение текущего user_id
    private static function getCurrentUserId(): int {
        // Пытаемся получить из Vars, если доступен
        if (class_exists('Vars')) {
            return Vars::getUserId() ?? 0;
        }
        return 0;
    }

    // Функция для добавления сообщения в историю
    public static function AddToHistory(string $role, string $content, array &$history, int $maxMessages = 10): array {
        $history[] = [
            'role' => $role,
            'content' => $content
        ];

        // Ограничиваем размер истории
        if (count($history) > $maxMessages * 2) { // *2 потому что пары user/assistant
            $history = array_slice($history, -($maxMessages * 2));
        }

        return $history;
    }
}