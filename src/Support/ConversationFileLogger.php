<?php
namespace MiniApp\Support;

use Throwable;

class ConversationFileLogger
{
    public static function logUserMessage(array $telegramUser, int $chatId, string $text): void
    {
        $identifier = self::resolveUserIdentifier($telegramUser, $chatId);
        self::append('[Пользователь - ' . $identifier . '] ' . self::normalizeText($text));
    }

    public static function logBotMessage(string $text): void
    {
        self::append('[Бот] ' . self::normalizeText($text));
    }

    private static function resolveUserIdentifier(array $telegramUser, int $chatId): string
    {
        $username = trim((string) ($telegramUser['username'] ?? ''));
        if ($username !== '') {
            return '@' . ltrim($username, '@');
        }

        $telegramId = (int) ($telegramUser['id'] ?? 0);
        if ($telegramId > 0) {
            return (string) $telegramId;
        }

        return (string) $chatId;
    }

    private static function normalizeText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        return trim($text);
    }

    private static function append(string $entry): void
    {
        try {
            $path = base_path('logs.txt');
            $payload = $entry . "\n\n";
            @file_put_contents($path, $payload, FILE_APPEND | LOCK_EX);
        } catch (Throwable $e) {
            return;
        }
    }
}
