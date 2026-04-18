<?php
require_once __DIR__ . '/src/Support/helpers.php';
require_once __DIR__ . '/src/Support/Autoload.php';

date_default_timezone_set(config('app.timezone', 'UTC'));

use MiniApp\Services\BotAssistantService;
use MiniApp\Support\Logger;

header('Content-Type: application/json; charset=utf-8');

try {
    $raw = file_get_contents('php://input');
    $update = json_decode($raw ?: '{}', true);
    if (!is_array($update)) {
        throw new RuntimeException('Invalid Telegram update payload');
    }

    (new BotAssistantService())->handleUpdate($update);
    echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    Logger::api('error', 'Bot webhook failed', [
        'message' => $e->getMessage(),
        'trace' => config('app.debug') ? $e->getTraceAsString() : null,
    ]);
    http_response_code(500);
    echo json_encode(['ok' => false], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
