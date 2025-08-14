<?
//ini_set('display_errors', '1');
//ini_set('display_startup_errors', '1');
//error_reporting(E_ALL);

require_once 'Core/Init.php';

// Обработка входящего обновления
$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    // Получено неверное обновление
    exit;
}

// Проверяем, есть ли сообщение в обновлении
if (isset($update["message"])) {
    $message = $update["message"];
    $chat_id = $message["chat"]["id"];
    $text = isset($message["text"]) ? $message["text"] : "";
    
    // Обработка команд
    if (strpos($text, "/start") === 0) {
        // Отправка текста с фотографией
        $photo_url = "https://example.com/path/to/your/image.jpg"; // Замените на реальный URL изображения
        $caption = "Привет! Это стартовое сообщение с фотографией.";
        
        sendPhoto($chat_id, $photo_url, $caption);
    } elseif (strpos($text, "/help") === 0) {
        // Отправка текста помощи
        $help_text = "Это справочное сообщение.\nДоступные команды:\n/start - начать работу\n/help - получить помощь";
        sendMessage($chat_id, $help_text);
    } elseif (stripos($text, "скажи") !== false) {
        // Если в тексте есть слово "скажи" (регистронезависимо)
        sendMessage($chat_id, "не скажу");
    } else {
        // Если ни одно условие не выполнено
        sendMessage($chat_id, "Извините, я вас не понял.");
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