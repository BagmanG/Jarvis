<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once 'Core/Init.php';
require_once 'Core/GPT.php';
require_once 'Core/Images.php';
require_once 'Core/Vars.php';
require_once 'Core/Events.php';
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    // Получено неверное обновление
    exit;
}
GPT::Init(AI_TOKEN);
Events::Init(DB_PASSWORD,DB_NAME);

// Проверяем, есть ли сообщение в обновлении
if (isset($update["message"])) {
     try{
    Vars::initFromUpdate($update);
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
                
                // Получаем историю сообщений
                $history = self::getMessageHistory();
                
                GPT::InitUserData(Events::GetParam('name'), Events::GetParam('about'));
                $response = GPT::GetMessage($transcription, $history);
                
                // Добавляем сообщения в историю
                $history = GPT::AddToHistory('user', $transcription, $history);
                $history = GPT::AddToHistory('assistant', $response, $history);
                
                // Сохраняем обновленную историю
                self::saveMessageHistory($history);
                
                sendMessage($chat_id, $response);
                return;
            } catch (Exception $e) {
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
        // Отправка текста помощи
        $help_text = "Это справочное сообщение.\nДоступные команды:\n/start - начать работу\n/help - получить помощь\n/clear - очистить историю диалога";
        sendMessage($chat_id, $help_text);
    } 
    elseif (strpos($text, "/test") === 0) {
        // Отправка текста помощи
        $help_text = Vars::getUserId()."/".Vars::getUsername();
        sendMessage($chat_id, $help_text);
    }
    elseif (strpos($text, "/testVoice") === 0) {
        // Отправка текста помощи
        sendMessage($chat_id, "test voice");
    }
    elseif (strpos($text, "/support") === 0) {
        // Отправка текста помощи
        sendMessage($chat_id, "🛠 Техническая поддержка Jarvis

Если у вас возник вопрос, проблема или предложение по работе бота, напишите сообщение ниже.

📌 Как оставить запрос?

- Опишите ваш вопрос или проблему максимально подробно.

- Укажите, если нужны скриншоты или дополнительные данные.

- Отправьте сообщение — наша команда поддержка ответит вам в ближайшее время.

⚡ Мы работаем быстро! Обычно ответ приходит в течение 12 часов.

Спасибо, что пользуетесь Jarvis! 🤖💙");
    }
    elseif (strpos($text, "/clear") === 0) {
        // Очистка истории сообщений
        self::clearMessageHistory();
        sendMessage($chat_id, "История диалога очищена. Я забыл наш предыдущий разговор, но помню основную информацию о вас.");
    }
    elseif (stripos($text, "скажи") !== false) {
        // Если в тексте есть слово "скажи" (регистронезависимо)
        sendMessage($chat_id, "не скажу");
    } else {
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
        
        sendMessage($chat_id, "Думаю....");
        sendMessage($chat_id, "0");
        // Получаем историю сообщений
            sendMessage($chat_id, "00");
        $history = self::getMessageHistory();
        sendMessage($chat_id, "1");
        GPT::InitUserData(Events::GetParam('name'), Events::GetParam('about'));
        sendMessage($chat_id, "2");
        $response = GPT::GetMessage($text, $history);
        sendMessage($chat_id, "3");
        // Добавляем сообщения в историю
        $history = GPT::AddToHistory('user', $text, $history);
        sendMessage($chat_id, "4");
        $history = GPT::AddToHistory('assistant', $response, $history);
        sendMessage($chat_id, "5");
        // Сохраняем обновленную историю
        self::saveMessageHistory($history);
        sendMessage($chat_id, "6");
        sendMessage($chat_id, $response);
    }
    }catch(Exception $e){
            sendMessage($chat_id, $e);
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
    $messagesJson = Events::GetParam('messages');
    if ($messagesJson) {
        $history = json_decode($messagesJson, true);
        return is_array($history) ? $history : [];
    }
    return [];
}

// Функция для сохранения истории сообщений
function saveMessageHistory(array $history): void {
    Events::SetParam('messages', json_encode($history));
}

// Функция для очистки истории сообщений
function clearMessageHistory(): void {
    Events::SetParam('messages', json_encode([]));
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
?>