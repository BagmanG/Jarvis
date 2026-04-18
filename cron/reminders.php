<?php
require_once dirname(__DIR__) . '/src/Support/helpers.php';
require_once dirname(__DIR__) . '/src/Support/Autoload.php';

date_default_timezone_set(config('app.timezone', 'UTC'));

use MiniApp\Repositories\TaskRepository;
use MiniApp\Support\Logger;

$repo = new TaskRepository();
$items = $repo->getDueReminders();
$sent = 0;
$failed = 0;

foreach ($items as $item) {
    $chatId = (int) ($item['telegram_id'] ?? 0);
    if ($chatId <= 0) {
        $repo->markReminderFailed((int) $item['id']);
        $failed++;
        continue;
    }

    $message = buildReminderMessage($item);
    if (sendTelegramMessage($chatId, $message)) {
        $repo->markReminderSent((int) $item['id']);
        $sent++;
    } else {
        $repo->markReminderFailed((int) $item['id']);
        $failed++;
    }
}

echo sprintf("Processed: %d, sent: %d, failed: %d\n", count($items), $sent, $failed);

function buildReminderMessage(array $task): string
{
    $title = trim((string) $task['title']);
    $date = date('d.m.Y', strtotime((string) $task['task_date']));
    $time = 'Весь день';

    if (!(int) $task['all_day']) {
        $start = !empty($task['time_start']) ? substr((string) $task['time_start'], 0, 5) : null;
        $end = !empty($task['time_end']) ? substr((string) $task['time_end'], 0, 5) : null;
        if ($start && $end) {
            $time = $start . ' - ' . $end;
        } elseif ($start || $end) {
            $time = $start ?: $end;
        } else {
            $time = 'Без времени';
        }
    }

    $lines = [
        '⏰ Напоминание о задаче',
        '',
        'Задача: ' . $title,
        'Дата: ' . $date,
        'Время: ' . $time,
    ];

    if (!empty($task['description'])) {
        $lines[] = 'Описание: ' . trim((string) $task['description']);
    }

    return implode("\n", $lines);
}

function sendTelegramMessage(int $chatId, string $message): bool
{
    $url = 'https://api.telegram.org/bot' . config('telegram.bot_token') . '/sendMessage';
    $payload = [
        'chat_id' => $chatId,
        'text' => $message,
    ];

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
        $response = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        if ($status >= 200 && $status < 300) {
            $decoded = json_decode((string) $response, true);
            return !empty($decoded['ok']);
        }
        Logger::api('error', 'Telegram reminder send failed', ['status' => $status, 'error' => $error]);
        return false;
    }

    $context = stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => "Content-Type: application/json\r\n",
            'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'timeout' => 20,
            'ignore_errors' => true,
        ],
    ]);
    $response = @file_get_contents($url, false, $context);
    if (!is_string($response)) {
        return false;
    }
    $decoded = json_decode($response, true);
    return !empty($decoded['ok']);
}
