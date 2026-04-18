<?php
namespace MiniApp\Services;

use MiniApp\Support\Logger;

class AiTunnelService
{
    private string $apiKey;
    private string $apiUrl;
    private string $chatModel;
    private string $transcriptionModel;

    public function __construct()
    {
        $this->apiKey = (string) config('ai.api_key', '');
        $this->apiUrl = (string) config('ai.api_url', 'https://api.aitunnel.ru/v1');
        $this->chatModel = (string) config('ai.chat_model', 'gpt-4o-mini');
        $this->transcriptionModel = (string) config('ai.transcription_model', 'whisper-1');
    }

    public function plan(array $messages, int $maxTokens = 1200): ?array
    {
        $payload = [
            'model' => $this->chatModel,
            'max_tokens' => $maxTokens,
            'temperature' => 0.2,
            'response_format' => ['type' => 'json_object'],
            'messages' => $messages,
        ];

        $response = $this->jsonRequest('/chat/completions', $payload);
        if (!$response) {
            return null;
        }

        $content = $response['choices'][0]['message']['content'] ?? null;
        if (!is_string($content) || $content === '') {
            Logger::api('error', 'AI empty content', ['response' => $response]);
            return null;
        }

        $decoded = json_decode($content, true);
        if (!is_array($decoded)) {
            Logger::api('error', 'AI invalid JSON response', ['content' => $content]);
            return null;
        }

        return $decoded;
    }

    public function transcribe(string $filename, string $binary, string $mimeType = 'audio/ogg'): ?string
    {
        $url = rtrim($this->apiUrl, '/') . '/audio/transcriptions';
        if (!$this->apiKey || !function_exists('curl_init')) {
            Logger::api('error', 'AI transcription unavailable', ['curl' => function_exists('curl_init')]);
            return null;
        }

        $tmpDir = base_path('storage/tmp');
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0775, true);
        }
        $tmpPath = $tmpDir . '/' . uniqid('voice_', true) . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
        file_put_contents($tmpPath, $binary);

        $postFields = [
            'model' => $this->transcriptionModel,
            'file' => new \CURLFile($tmpPath, $mimeType, basename($tmpPath)),
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 120,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        @unlink($tmpPath);

        if ($status < 200 || $status >= 300 || !is_string($body)) {
            Logger::api('error', 'AI transcription failed', ['status' => $status, 'error' => $error, 'body' => $body]);
            return null;
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            return null;
        }

        return isset($decoded['text']) ? trim((string) $decoded['text']) : null;
    }

    private function jsonRequest(string $path, array $payload): ?array
    {
        if (!$this->apiKey) {
            Logger::api('error', 'AI API key missing');
            return null;
        }

        $url = rtrim($this->apiUrl, '/') . $path;
        if (!function_exists('curl_init')) {
            return null;
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_TIMEOUT => 120,
        ]);
        $body = curl_exec($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($status < 200 || $status >= 300 || !is_string($body)) {
            Logger::api('error', 'AI request failed', ['path' => $path, 'status' => $status, 'error' => $error, 'body' => $body]);
            return null;
        }

        $decoded = json_decode($body, true);
        return is_array($decoded) ? $decoded : null;
    }
}
