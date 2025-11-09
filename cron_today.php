<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once __DIR__ . '/Core/Init.php';
require_once __DIR__ . '/Core/TaskHandler.php';

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
            if (!empty($task['due_time'])) {
                // Обрезаем секунды, если есть
                $time = $task['due_time'];
                if (strlen($time) > 5) {
                    $time = substr($time, 0, 5);
                }
                $msg .= " (" . $time . ")";
            }
            if (!empty($task['description'])) $msg .= "\n   — " . $task['description'];
            $msg .= "\n";
        }
    } else {
        $randomMessages = [
            "У вас нет задач на сегодня. Хотите создать новую? Просто напишите мне!",
            "Сегодня ваш список задач пуст. Может, добавим что-то интересное?",
            "Отличный день! Задач нет, но вы всегда можете создать новую.",
            "Ваш план на сегодня свободен. Хотите запланировать что-то?",
            "Ни одной задачи на сегодня! Идеальное время для чего-то нового.",
            "Список задач пуст. Отличная возможность добавить что-то важное!",
            "Сегодня у вас нет запланированных дел. Создадим первую задачу?",
            "Свободный день! Но если хотите что-то сделать - просто скажите.",
            "Задач не найдено. Это шанс начать что-то новое!",
            "Ваш день чист как белый лист. Напишем на нём первую задачу?"
        ];

        $msg = $randomMessages[random_int(0, count($randomMessages) - 1)];
    }
    // Отправляем сообщение
    sendMessage($chatId, $msg);
}

$mysqli->close();

file_put_contents(__DIR__ . '/cron_today.log', date('Y-m-d H:i:s') . " - Рассылка задач на сегодня завершена\n", FILE_APPEND);

echo "Рассылка задач на сегодня завершена\n";

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
