<?php
namespace MiniApp\Support;

use MiniApp\Core\Database;
use Throwable;

class Logger
{
    public static function telegramUserMessage(array $message): void
    {
        try {
            $text = trim((string) ($message['text'] ?? ''));
            if ($text === '') {
                return;
            }

            $from = is_array($message['from'] ?? null) ? $message['from'] : [];
            $label = self::buildTelegramUserLabel($from, $message);
            self::appendTelegramMessageLog("[Пользователь - {$label}] {$text}");
        } catch (Throwable $e) {
            error_log('[MiniApp Logger] telegramUserMessage failed: ' . $e->getMessage());
        }
    }

    public static function telegramBotMessage(string $text): void
    {
        try {
            $normalizedText = self::normalizeTelegramMessageText($text);
            if ($normalizedText === '') {
                return;
            }

            self::appendTelegramMessageLog("[Бот] {$normalizedText}");
        } catch (Throwable $e) {
            error_log('[MiniApp Logger] telegramBotMessage failed: ' . $e->getMessage());
        }
    }

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

    private static function appendTelegramMessageLog(string $entry): void
    {
        try {
            $path = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'logs.txt';
            $payload = $entry . PHP_EOL . PHP_EOL;
            file_put_contents($path, $payload, FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) {
            error_log('[MiniApp Logger] appendTelegramMessageLog failed: ' . $e->getMessage());
        }
    }

    private static function buildTelegramUserLabel(array $from, array $message): string
    {
        $username = trim((string) ($from['username'] ?? ''));
        if ($username !== '') {
            return '@' . ltrim($username, '@');
        }

        $telegramId = (string) ($from['id'] ?? '');
        if ($telegramId !== '') {
            return $telegramId;
        }

        $chatId = (string) ($message['chat']['id'] ?? '');
        return $chatId !== '' ? $chatId : 'unknown';
    }

    private static function normalizeTelegramMessageText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        return trim($text);
    }
}
