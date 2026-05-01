<?php
namespace MiniApp\Services;

use MiniApp\Support\ConversationFileLogger;
use MiniApp\Support\Logger;

class TelegramBotApi
{
    private string $token;
    private string $baseUrl;

    public function __construct()
    {
        $this->token = (string) config('telegram.bot_token', '');
        $this->baseUrl = 'https://api.telegram.org/bot' . $this->token . '/';
    }

    public function sendMessage(int $chatId, string $text, array $options = []): ?array
    {
        $response = $this->request('sendMessage', array_merge([
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $options));

        if ($this->isSuccessfulResponse($response)) {
            ConversationFileLogger::logBotMessage($text);
        }

        return $response;
    }

    public function answerCallbackQuery(string $callbackQueryId, string $text = '', bool $showAlert = false): ?array
    {
        return $this->request('answerCallbackQuery', [
            'callback_query_id' => $callbackQueryId,
            'text' => $text,
            'show_alert' => $showAlert,
        ]);
    }

    public function editMessageText(int $chatId, int $messageId, string $text, array $options = []): ?array
    {
        return $this->request('editMessageText', array_merge([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ], $options));
    }

    public function editMessageReplyMarkup(int $chatId, int $messageId, array $replyMarkup): ?array
    {
        return $this->request('editMessageReplyMarkup', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'reply_markup' => $replyMarkup,
        ]);
    }

    public function getFile(string $fileId): ?array
    {
        $response = $this->request('getFile', ['file_id' => $fileId]);
        return $response['result'] ?? null;
    }

    public function downloadFile(string $filePath): ?string
    {
        $url = 'https://api.telegram.org/file/bot' . $this->token . '/' . ltrim($filePath, '/');
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 60,
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($status >= 200 && $status < 300 && is_string($body)) {
                return $body;
            }
            Logger::api('error', 'Telegram file download failed', ['status' => $status, 'error' => $error]);
            return null;
        }

        $body = @file_get_contents($url);
        return is_string($body) ? $body : null;
    }

    private function request(string $method, array $payload): ?array
    {
        if (!$this->token) {
            Logger::api('error', 'Telegram token missing');
            return null;
        }

        if (isset($payload['reply_markup']) && is_array($payload['reply_markup'])) {
            $payload['reply_markup'] = json_encode($payload['reply_markup'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $url = $this->baseUrl . $method;
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 15,
                CURLOPT_TIMEOUT => 60,
                CURLOPT_POSTFIELDS => $payload,
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            if ($status >= 200 && $status < 300 && is_string($body)) {
                $decoded = json_decode($body, true);
                if (is_array($decoded) && !empty($decoded['ok'])) {
                    return $decoded;
                }
                Logger::api('error', 'Telegram API error payload', ['method' => $method, 'body' => $body]);
                return is_array($decoded) ? $decoded : null;
            }
            Logger::api('error', 'Telegram API request failed', ['method' => $method, 'status' => $status, 'error' => $error]);
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($payload),
                'timeout' => 60,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        if (!is_string($body)) {
            return null;
        }
        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : null;
    }

    private function isSuccessfulResponse(?array $response): bool
    {
        return is_array($response) && !empty($response['ok']);
    }
}
