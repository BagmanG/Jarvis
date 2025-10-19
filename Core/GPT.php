<?php

class GPT {
    public static $api_key = "";
    private static $api_url = 'https://api.aitunnel.ru/v1/chat/completions';
    private static $model = 'gpt-4o-mini'; // Обновляем модель для поддержки изображений
    private static $max_tokens = 5000;
    private static $system_prompt = '';

    public static function Init(string $key){
        self::$api_key = $key;
        self::initializeSystemPrompt();
    }
    
    /**
     * Инициализация системного промпта с актуальной датой
     */
    private static function initializeSystemPrompt() {
        require_once __DIR__ . '/DateTimeHelper.php';
        $dateInfo = DateTimeHelper::getDateInfoForGPT();
        
        self::$system_prompt = 'Ты — Джарвис, искуственный интеллект, созданный для помощи в достижении целей. Ты отвечаешь вежливо, лаконично и по делу. 

' . $dateInfo . '

Ты можешь работать с задачами пользователя. Если пользователь просит добавить задачу, используй функцию add_task. Если просит удалить задачу - delete_task. Если просит показать задачи - list_tasks.

При добавлении задач:
- Если пользователь говорит "сегодня" или "today" - используй сегодняшнюю дату
- Если говорит "завтра" или "tomorrow" - используй завтрашнюю дату
- Если не указано время - используй 12:00 по умолчанию
- Если не указан приоритет - используй "medium" по умолчанию

Ты также можешь работать с изображениями:

АНАЛИЗ изображений (когда пользователь отправляет изображение):
- Если к изображению есть текст/вопрос - отвечай на вопрос, анализируя изображение
- Если изображение без текста - дай подробное описание того, что видишь
- Можешь распознавать текст на изображениях, объекты, людей, сцены
- Если на изображении есть задачи или планы - можешь предложить добавить их в систему задач

ГЕНЕРАЦИЯ изображений (когда пользователь просит нарисовать):
- Если пользователь просит "нарисуй", "создай изображение", "покажи как выглядит" и т.п. - система автоматически создаст изображение
- Ты не участвуешь в генерации, система делает это сама
- Просто отвечай обычным текстом, объясняя что будет нарисовано или что ты понял из запроса

Всегда будь дружелюбным и подтверждай выполнение действий.';
    }
    
    /**
     * Обновление системного промпта с актуальной датой
     */
    private static function updateSystemPromptWithCurrentDate() {
        require_once __DIR__ . '/DateTimeHelper.php';
        $dateInfo = DateTimeHelper::getDateInfoForGPT();
        
        // Обновляем системный промпт с актуальной датой
        self::$system_prompt = 'Ты — Джарвис, искуственный интеллект, созданный для помощи в достижении целей. Ты отвечаешь вежливо, лаконично и по делу. 

' . $dateInfo . '

Ты можешь работать с задачами пользователя. Если пользователь просит добавить задачу, используй функцию add_task. Если просит удалить задачу - delete_task. Если просит показать задачи - list_tasks.

При добавлении задач:
- Если пользователь говорит "сегодня" или "today" - используй сегодняшнюю дату
- Если говорит "завтра" или "tomorrow" - используй завтрашнюю дату
- Если не указано время - используй 12:00 по умолчанию
- Если не указан приоритет - используй "medium" по умолчанию

Ты также можешь работать с изображениями:

АНАЛИЗ изображений (когда пользователь отправляет изображение):
- Если к изображению есть текст/вопрос - отвечай на вопрос, анализируя изображение
- Если изображение без текста - дай подробное описание того, что видишь
- Можешь распознавать текст на изображениях, объекты, людей, сцены
- Если на изображении есть задачи или планы - можешь предложить добавить их в систему задач

ГЕНЕРАЦИЯ изображений (когда пользователь просит нарисовать):
- Если пользователь просит "нарисуй", "создай изображение", "покажи как выглядит" и т.п. - система автоматически создаст изображение
- Ты не участвуешь в генерации, система делает это сама
- Просто отвечай обычным текстом, объясняя что будет нарисовано или что ты понял из запроса

Всегда будь дружелюбным и подтверждай выполнение действий.';
    }

    public static function InitUserData(string $name, string $about){
        require_once __DIR__ . '/DateTimeHelper.php';
        $dateInfo = DateTimeHelper::getDateInfoForGPT();
        
        self::$system_prompt = "Ты — Джарвис, искуственный интеллект, созданный для помощи в достижении целей. Ты отвечаешь вежливо, лаконично и по делу. Ты всегда отвечаешь без форматирования, только текст! Пользователя зовут '$name' поэтому всегда обращайся к нему по имени. Вот информация о пользователе: $about

" . $dateInfo . "
Пользователя зовут '$name' поэтому всегда обращайся к нему по имени. Вот информация о пользователе: $about!!!!!
Ты можешь работать с изображениями:

АНАЛИЗ изображений:
- Если к изображению есть текст/вопрос - отвечай на вопрос, анализируя изображение
- Если изображение без текста - дай подробное описание того, что видишь
- Можешь распознавать текст на изображениях, объекты, людей, сцены
- Если на изображении есть задачи или планы - можешь предложить добавить их в систему задач

ГЕНЕРАЦИЯ изображений:
- Когда пользователь просит нарисовать что-то - система автоматически создаст изображение
- Ты просто отвечай обычным текстом, объясняя что понял из запроса";
    }

    public static function GetMessage(string $userMessage, array $history = [], int $chat_id = null, array $images = []): array {
        // Проверка, что API ключ установлен
        if (empty(self::$api_key)) {
            throw new Exception('API key is not set. Please call GPT::Init() first.');
        }
        
        // Обновляем системный промпт с актуальной датой
        self::updateSystemPromptWithCurrentDate();

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

        // Формируем контент сообщения пользователя
        $userContent = [];
        
        // Добавляем текст, если есть
        if (!empty($userMessage)) {
            $userContent[] = [
                'type' => 'text',
                'text' => $userMessage
            ];
        }
        
        // Добавляем изображения, если есть
        if (!empty($images)) {
            foreach ($images as $image) {
                $userContent[] = [
                    'type' => 'image_url',
                    'image_url' => [
                        'url' => $image['url'],
                        'detail' => 'high' // Можно изменить на 'low' для экономии токенов
                    ]
                ];
            }
        }
        
        // Добавляем текущее сообщение пользователя
        $messages[] = [
            'role' => 'user',
            'content' => $userContent
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
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // Проверяем ошибки cURL
        if ($curl_error) {
            throw new Exception('cURL error: ' . $curl_error);
        }

        // Декодируем JSON ответ
        $responseData = json_decode($response, true);
        
        // Проверяем на ошибки декодирования
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to decode JSON response: ' . $response);
        }
        
        // Проверяем HTTP код ответа
        if ($http_code !== 200) {
            $error_message = isset($responseData['error']['message']) ? $responseData['error']['message'] : 'Unknown API error';
            throw new Exception('API error (HTTP ' . $http_code . '): ' . $error_message);
        }
        
        // Проверяем, есть ли вызов функции
        if (isset($responseData['choices'][0]['message']['tool_calls'])) {
            $toolCalls = $responseData['choices'][0]['message']['tool_calls'];
            $functionResults = [];
            
            // Отправляем отладочную информацию в Telegram
            if (function_exists('sendMessage') && isset($chat_id)) {
                ///DEBUG
                //sendMessage($chat_id, "🔍 Обнаружены вызовы функций: " . json_encode($toolCalls));
            }
            
            foreach ($toolCalls as $toolCall) {
                if (isset($toolCall['function'])) {
                    $functionName = $toolCall['function']['name'];
                    $arguments = json_decode($toolCall['function']['arguments'], true);
                    
                    // Получаем user_id из текущего контекста
                    $userId = self::getCurrentUserId();
                    
                    // Отправляем отладочную информацию в Telegram
                    if (function_exists('sendMessage') && $chat_id) {
                        ///DEBUG
                        //sendMessage($chat_id, "🔧 Вызываю функцию: $functionName с userId: $userId");
                    }
                    
                    // Вызываем функцию
                    $result = TaskHandler::handleFunctionCall($functionName, $arguments, $userId);
                    
                    // Отправляем результат в Telegram
                    if (function_exists('sendMessage') && $chat_id) {
                        //sendMessage($chat_id, "📊 Результат функции: " . json_encode($result));
                    }
                    
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

    // Новый метод для обработки только изображений
    public static function AnalyzeImage(string $imageUrl, string $prompt = "", int $chat_id = null): array {
        // Проверка, что API ключ установлен
        if (empty(self::$api_key)) {
            throw new Exception('API key is not set. Please call GPT::Init() first.');
        }
        
        // Обновляем системный промпт с актуальной датой
        self::updateSystemPromptWithCurrentDate();

        // Формируем массив сообщений
        $messages = [
            [
                'role' => 'system',
                'content' => self::$system_prompt
            ]
        ];

        // Формируем контент сообщения пользователя
        $userContent = [];
        
        // Добавляем текст, если есть
        if (!empty($prompt)) {
            $userContent[] = [
                'type' => 'text',
                'text' => $prompt
            ];
        }
        
        // Добавляем изображение
        $userContent[] = [
            'type' => 'image_url',
            'image_url' => [
                'url' => $imageUrl,
                'detail' => 'high'
            ]
        ];
        
        // Добавляем текущее сообщение пользователя
        $messages[] = [
            'role' => 'user',
            'content' => $userContent
        ];

        $data_chat = [
            'model' => self::$model,
            'max_tokens' => self::$max_tokens,
            'messages' => $messages
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
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        // Проверяем ошибки cURL
        if ($curl_error) {
            throw new Exception('cURL error: ' . $curl_error);
        }

        // Декодируем JSON ответ
        $responseData = json_decode($response, true);
        
        // Проверяем на ошибки декодирования
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to decode JSON response: ' . $response);
        }
        
        // Проверяем HTTP код ответа
        if ($http_code !== 200) {
            $error_message = isset($responseData['error']['message']) ? $responseData['error']['message'] : 'Unknown API error';
            throw new Exception('API error (HTTP ' . $http_code . '): ' . $error_message);
        }
        
        // Извлекаем content из ответа
        if (isset($responseData['choices'][0]['message']['content'])) {
            return [
                'content' => $responseData['choices'][0]['message']['content'],
                'has_function_call' => false
            ];
        }
        
        throw new Exception('Invalid response structure');
    }
    
    // Получение текущего user_id
    private static function getCurrentUserId(): int {
        // Пытаемся получить из Vars, если доступен
        if (class_exists('Vars')) {
            $userId = Vars::getUserId();
            if ($userId && $userId > 0) {
                return $userId;
            }
        }
        
        // Если не удалось получить user_id, отправляем предупреждение в Telegram
        if (function_exists('sendMessage') && isset($GLOBALS['debug_chat_id'])) {
            sendMessage($GLOBALS['debug_chat_id'], "⚠️ Не удалось получить user_id из класса Vars. User ID: " . (Vars::getUserId() ?? 'null'));
        }
        
        // Возвращаем 1 как fallback для тестирования
        return 1;
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