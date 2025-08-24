<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once 'Core/Init.php';
require_once 'Core/GPT.php';
require_once 'Core/Images.php';
require_once 'Core/Vars.php';
require_once 'Core/Events.php';

// Функция для логирования ошибок
function logError($message) {
    error_log(date('Y-m-d H:i:s') . ' - ' . $message . PHP_EOL, 3, 'error.log');
}

$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    logError('Invalid update received: ' . $content);
    exit;
}

try {
    GPT::Init(AI_TOKEN);
    Events::Init(DB_PASSWORD, DB_NAME);
} catch (Exception $e) {
    logError('Initialization error: ' . $e->getMessage());
    exit;
}

// Проверяем, есть ли сообщение в обновлении
if (isset($update["message"]) && $update["message"]["chat"]["id"] != SUPPORT_CHAT_ID) {
    Vars::initFromUpdate($update);
    Events::SetParam("chat_id",Vars::getChatId());
    $message = $update["message"];
    $chat_id = $message["chat"]["id"];
    $text = isset($message["text"]) ? $message["text"] : "";
    
    // Обработка голосового сообщения
    if (isset($message["voice"])) {
        $voice = $message["voice"];
        $file_id = $voice["file_id"];
        
        // Получаем информацию о файле
        $file_info_url = "https://api.telegram.org/bot" . BOT_TOKEN . "/getFile?file_id=" . $file_id;
        $file_info = json_decode(file_get_contents($file_info_url), true);
        
        if ($file_info && isset($file_info["result"]["file_path"])) {
            $file_path = $file_info["result"]["file_path"];
            $voice_file_url = "https://api.telegram.org/file/bot" . BOT_TOKEN . "/" . $file_path;
            
            // Сохраняем временный файл
            $temp_file = tempnam(sys_get_temp_dir(), 'voice_') . '.ogg';
            file_put_contents($temp_file, file_get_contents($voice_file_url));
            
            // Используем API для распознавания речи
            try {
                $transcription = transcribeAudio($temp_file);
                sendMessage($chat_id, "Думаю...");
                
                // Получаем историю сообщений - ИСПРАВЛЕНО: убрано self::
                $history = getMessageHistory();
                
                GPT::InitUserData(Events::GetParam('name'), Events::GetParam('about'));
                $response = GPT::GetMessage($transcription, $history);
                
                // Добавляем сообщения в историю
                $history = GPT::AddToHistory('user', $transcription, $history);
                $history = GPT::AddToHistory('assistant', $response['content'], $history);
                
                // Сохраняем обновленную историю
                saveMessageHistory($history);
                
                sendMessage($chat_id, $response['content']);
                
                // Debug: если была вызвана функция, логируем это
                if ($response['has_function_call']) {
                    sendMessage($chat_id, "🔧 Функция была выполнена успешно!");
                    error_log('index.php - Voice function was executed successfully for chat_id: ' . $chat_id);
                }
                return;
            } catch (Exception $e) {
                logError('Voice transcription error: ' . $e->getMessage());
                sendMessage($chat_id, "Ошибка при расшифровке голосового сообщения: " . $e->getMessage());
            }
            
            // Удаляем временный файл
            unlink($temp_file);
        } else {
            sendMessage($chat_id, "Не удалось получить голосовое сообщение.");
        }
    }
    // Обработка команд
    elseif (strpos($text, "/start") === 0) {
        $photo_url = Images::$start;
        $caption = "Привет! Я — Джарвис, твой персональный голосовой помощник.\nМоя задача — помочь тебе достичь целей и организовать день.\nДавай познакомимся.";
        Events::OnStart();
        Events::SetState("start");
        // Создаем inline клавиатуру с кнопкой "Пройти тест"
        $keyboard = [
            'inline_keyboard' => [
                [
                    [
                        'text' => 'Пройти тест',
                        'callback_data' => 'start_test'
                    ]
                ]
            ]
        ];
        
        $encodedKeyboard = json_encode($keyboard);
        
        $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendPhoto";
        $data = [
            'chat_id' => $chat_id,
            'photo' => $photo_url,
            'caption' => $caption,
            'parse_mode' => 'HTML',
            'reply_markup' => $encodedKeyboard
        ];
        
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
    elseif (strpos($text, "/help") === 0) {
        $help_text = "Это справочное сообщение.\nДоступные команды:\n/start - начать работу\n/help - получить помощь\n/clear - очистить историю диалога";
        sendMessage($chat_id, $help_text);
    } 
    elseif (strpos($text, "/test") === 0) {
        $help_text = Vars::getUserId()."/".Vars::getChatId();
        sendMessage($chat_id, $help_text);
    }
    elseif (strpos($text, "/support") === 0) {
    // Устанавливаем состояние, что пользователь обратился в поддержку
    Events::SetState("support_requested");
    
    $support_message = "🛠 Техническая поддержка Jarvis

Если у вас возник вопрос, проблема или предложение по работе бота, напишите сообщение ниже.

📌 Как оставить запрос?

- Опишите ваш вопрос или проблему максимально подробно.

- Укажите, если нужны скриншоты или дополнительные данные.

- Отправьте сообщение — наша команда поддержка ответит вам в ближайшее время.

⚡ Мы работаем быстро! Обычно ответ приходит в течение 12 часов.

Спасибо, что пользуетесь Jarvis! 🤖💙";
    
    sendMessage($chat_id, $support_message);
}
    elseif (strpos($text, "/clear") === 0) {
        // Очистка истории сообщений - ИСПРАВЛЕНО: убрано self::
        try {
            clearMessageHistory();
            sendMessage($chat_id, "✅ История диалога очищена. Я забыл наш предыдущий разговор, но помню основную информацию о вас.");
        } catch (Exception $e) {
            logError('Clear history error: ' . $e->getMessage());
            sendMessage($chat_id, "❌ Произошла ошибка при очистке истории. Попробуйте позже.");
        }
    }
    else {
        $state = Events::GetState();
        if($state == "start"){
            Events::SetState("aboutMe");
            Events::SetParam("name",$text);
            sendMessage($chat_id,"Красивое имя, $text! Я запомнил). Расскажи немного о себе, чем ты занимаешься и какая у тебя самая глобальная цель.");
            return;
        }
        if($state == "aboutMe"){
            Events::SetState("menu");
            Events::SetParam('about',$text);
            sendMessage($chat_id,"Отлично. Теперь ты можешь пользоваться ботом. Ты можешь спрашивать у меня что угодно, а я тебе с радостью отвечу. Дополнительно ты можешь узнать введя команду /help.");
            return;
        }
        
        if ($state === "support_requested" && !empty($text)) {
        // Пользователь отправил сообщение в поддержку
        try {
            // Формируем сообщение для поддержки
            $user_info = "👤 Пользователь: " . Vars::getUsername() . " (ID: " . Vars::getUserId() . ")";
            $support_text = "✉️ Новое обращение в поддержку:\n\n" . $user_info . "\n\nСообщение:\n" . $text;
            
            // Отправляем сообщение в чат поддержки
            sendMessage(SUPPORT_CHAT_ID, $support_text);
            
            sendMessage($chat_id, "✅ Ваше сообщение отправлено в поддержку. Ожидайте ответа в ближайшее время.");
            
        } catch (Exception $e) {
            logError('Support message error: ' . $e->getMessage());
            sendMessage($chat_id, "❌ Произошла ошибка при отправке сообщения в поддержку.");
        }
        return;
    }
        
        sendMessage($chat_id, "Думаю...");
        
        try {
            // Получаем историю сообщений - ИСПРАВЛЕНО: убрано self::
            $history = getMessageHistory();
            
            GPT::InitUserData(Events::GetParam('name'), Events::GetParam('about'));
            $response = GPT::GetMessage($text, $history);
            
            // Добавляем сообщения в историю
            $history = GPT::AddToHistory('user', $text, $history);
            $history = GPT::AddToHistory('assistant', $response['content'], $history);
            
            // Сохраняем обновленную историю - ИСПРАВЛЕНО: убрано self::
            saveMessageHistory($history);
            
            // Отправляем ответ пользователю
            sendMessage($chat_id, $response['content']);
            
            // Debug: если была вызвана функция, логируем это
            if ($response['has_function_call']) {
                sendMessage($chat_id, "🔧 Функция была выполнена успешно!");
                error_log('index.php - Function was executed successfully for chat_id: ' . $chat_id);
            }
            
        } catch (Exception $e) {
            logError('GPT processing error: ' . $e->getMessage());
            sendMessage($chat_id, "❌ Извините, произошла ошибка при обработке запроса. Попробуйте позже или обратитесь в поддержку /support");
        }
    }
}

// Обработка callback запросов от inline кнопок
if (isset($update["callback_query"])) {
    Vars::initFromUpdate($update);
    $callback_query = $update["callback_query"];
    $chat_id = $callback_query["message"]["chat"]["id"];
    $data = $callback_query["data"];
    
    if ($data == 'start_test') {
        sendMessage($chat_id, "Приступим. Как тебя зовут?");
    }
}

// Функция для получения истории сообщений
function getMessageHistory(): array {
    try {
        $messagesJson = Events::GetParam('messages');
        if ($messagesJson) {
            $history = json_decode($messagesJson, true);
            return is_array($history) ? $history : [];
        }
        return [];
    } catch (Exception $e) {
        logError('Get message history error: ' . $e->getMessage());
        return [];
    }
}

// Функция для сохранения истории сообщений
function saveMessageHistory(array $history): void {
    try {
        Events::SetParam('messages', json_encode($history));
    } catch (Exception $e) {
        logError('Save message history error: ' . $e->getMessage());
    }
}

// Функция для очистки истории сообщений
function clearMessageHistory(): void {
    try {
        Events::SetParam('messages', json_encode([]));
    } catch (Exception $e) {
        logError('Clear message history error: ' . $e->getMessage());
        throw $e;
    }
}

// Функция для транскрибации аудио
function transcribeAudio($audio_file_path) {
    $api_key = AI_TOKEN;
    
    $url = 'https://api.aitunnel.ru/v1/audio/transcriptions';
    
    $headers = [
        'Authorization: Bearer ' . $api_key,
    ];
    
    $post_fields = [
        'file' => new CURLFile($audio_file_path),
        'model' => 'whisper-1',
        'response_format' => 'text'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('Ошибка cURL: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($http_code != 200) {
        throw new Exception('Ошибка API: ' . $response);
    }
    
    return $response;
}

// Функция отправки текстового сообщения
function sendMessage($chat_id, $text) {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendMessage";
    $data = [
        'chat_id' => $chat_id,
        'text' => $text,
        'parse_mode' => 'HTML'
    ];
    
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

// Функция отправки фото с подписью
function sendPhoto($chat_id, $photo_url, $caption = "") {
    $url = "https://api.telegram.org/bot" . BOT_TOKEN . "/sendPhoto";
    $data = [
        'chat_id' => $chat_id,
        'photo' => $photo_url,
        'caption' => $caption,
        'parse_mode' => 'HTML'
    ];
    
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

if (isset($update["message"]) && $update["message"]["chat"]["id"] == SUPPORT_CHAT_ID) {
    // Это сообщение из чата поддержки
    $message = $update["message"];
    
    // Проверяем, является ли это ответом на сообщение (reply)
    if (isset($message["reply_to_message"]) && isset($message["reply_to_message"]["text"])) {
        $reply_text = $message["reply_to_message"]["text"];
        $response_text = isset($message["text"]) ? $message["text"] : "";
        
        // Парсим ID пользователя из текста ответа
        if (preg_match('/ID: (\d+)/', $reply_text, $matches)) {
            $user_id = $matches[1];
            
            // Отправляем ответ пользователю
            if (!empty($response_text)) {
                $response_message = "📨 Ответ от поддержки:\n\n" . $response_text . "\n\nЕсли у вас остались вопросы, напишите /support";
                sendMessage($user_id, $response_message);
                
                // Сбрасываем статус поддержки у пользователя
                try {
                    // Находим пользователя в базе и сбрасываем состояние
                    $reset_query = "UPDATE Users SET state = 'menu' WHERE userId = '$user_id'";
                    Events::Execute($reset_query);
                    
                    // Отправляем подтверждение в чат поддержки
                    sendMessage(SUPPORT_CHAT_ID, "✅ Ответ отправлен пользователю ID: " . $user_id);
                    
                } catch (Exception $e) {
                    logError('Support response error: ' . $e->getMessage());
                    sendMessage(SUPPORT_CHAT_ID, "❌ Ошибка при отправке ответа пользователю: " . $e->getMessage());
                }
            }
        }
    }else{
         sendMessage(SUPPORT_CHAT_ID, "Чтобы ответить на вопрос пользователя, нужно отправить сообщение как ответ.");
    }
}
?>