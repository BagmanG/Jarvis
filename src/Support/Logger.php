<?php
namespace MiniApp\Support;

use MiniApp\Core\Database;
use Throwable;

class Logger
{
    public static function api(string $level, string $message, array $context = []): void
    {
        try {
            $pdo = Database::connection();
            $stmt = $pdo->prepare('INSERT INTO api_logs (level, message, context_json, created_at) VALUES (:level, :message, :context_json, :created_at)');
            $stmt->execute([
                'level' => substr($level, 0, 20),
                'message' => substr($message, 0, 255),
                'context_json' => json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            error_log('[MiniApp Logger] ' . $message . ' ' . json_encode($context));
        }
    }
}
