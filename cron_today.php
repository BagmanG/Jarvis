<?php
require_once __DIR__ . '/Core/Init.php';
require_once __DIR__ . '/Core/TaskHandler.php';

// --- sendMessage ---
if (!function_exists('sendMessage')) {
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
}

// Получаем токен бота
$botToken = BOT_TOKEN;

// Подключение к базе
$mysqli = new mysqli('localhost', DB_NAME, DB_PASSWORD, DB_NAME);
if ($mysqli->connect_error) {
    file_put_contents('app_error.log', date('Y-m-d H:i:s') . " - DB Error: " . $mysqli->connect_error . "\n", FILE_APPEND);
    exit("DB Error");
}

// Получаем всех пользователей
$users = [];
if (defined('IS_DEV') && IS_DEV) {
    // Только DEV-пользователь
    $result = $mysqli->query("SELECT userId, chat_id FROM Users WHERE chat_id = '1012037332'");
} else {
    $result = $mysqli->query("SELECT userId, chat_id FROM Users WHERE chat_id IS NOT NULL");
}
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

foreach ($users as $user) {
    $userId = $user['userId'];
    $chatId = $user['chat_id'];
    // Получаем задачи на сегодня
    $tasksResult = TaskHandler::listTasks(['filter'=>'today'], $userId);
    $tasks = $tasksResult['tasks'] ?? [];
    if (!empty($tasks)) {
        $msg = "📝 Ваши задачи на сегодня:\n";
        foreach ($tasks as $i => $task) {
            $msg .= ($i+1) . ". " . $task['title'];
            if (!empty($task['due_time'])) $msg .= " (" . $task['due_time'] . ")";
            if (!empty($task['description'])) $msg .= "\n   — " . $task['description'];
            $msg .= "\n";
        }
    } else {
        $msg = "У вас нет задач на сегодня. Хотите создать новую? Просто напишите мне!";
    }
    // Отправляем сообщение
    sendMessage($chatId, $msg);
}

$mysqli->close();

file_put_contents(__DIR__ . '/cron_today.log', date('Y-m-d H:i:s') . " - Рассылка задач на сегодня завершена\n", FILE_APPEND);

echo "Рассылка задач на сегодня завершена\n";
