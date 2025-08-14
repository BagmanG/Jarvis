<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once 'Core/Init.php';
require_once 'Core/GPT.php';
require_once 'Core/Images.php';
require_once 'Core/Vars.php';

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
    Vars::initFromUpdate($update);
    $message = $update["message"];
    $chat_id = $message["chat"]["id"];
    $text = isset($message["text"]) ? $message["text"] : "";
    
    // Обработка команд
    if (strpos($text, "/start") === 0) {
        $photo_url = Images::$start;
        $caption = "Привет! Я — Джарвис, твой персональный голосовой помощник.\nМоя задача — помочь тебе достичь целей и организовать день.\nДавай познакомимся.";
        Events::OnStart();
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
    } elseif (strpos($text, "/help") === 0) {
        // Отправка текста помощи
        $help_text = "Это справочное сообщение.\nДоступные команды:\n/start - начать работу\n/help - получить помощь";
        sendMessage($chat_id, $help_text);
    } 
    elseif (strpos($text, "/test") === 0) {
        // Отправка текста помощи
        $help_text = Vars::getUserId()."/".Vars::getUsername();
        sendMessage($chat_id, $help_text);
    }
    elseif (stripos($text, "скажи") !== false) {
        // Если в тексте есть слово "скажи" (регистронезависимо)
        sendMessage($chat_id, "не скажу");
    } else {
        // Если ни одно условие не выполнено
        sendMessage($chat_id, "Думаю...");
        sendMessage($chat_id, GPT::GetMessage($text));
    }
}

// Обработка callback запросов от inline кнопок
if (isset($update["callback_query"])) {
    Vars::initFromUpdate($update);
    $callback_query = $update["callback_query"];
    $chat_id = $callback_query["message"]["chat"]["id"];
    $data = $callback_query["data"];
    
    if ($data == 'start_test') {
        sendMessage($chat_id, "Как тебя зовут?");
    }
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