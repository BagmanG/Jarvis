<?php
namespace MiniApp\Services;

class TelegramAvatarService
{
    public function sync(int $userId, int $telegramId): ?array
    {
        $botToken = (string) config('telegram.bot_token', '');
        if ($botToken === '' || $telegramId <= 0) {
            return null;
        }

        $photos = $this->apiRequest('getUserProfilePhotos', [
            'user_id' => $telegramId,
            'limit' => 1,
        ]);

        if (empty($photos['photos'][0])) {
            return null;
        }

        $sizes = $photos['photos'][0];
        $best = end($sizes);
        if (empty($best['file_id'])) {
            return null;
        }

        $file = $this->apiRequest('getFile', ['file_id' => $best['file_id']]);
        if (empty($file['file_path'])) {
            return null;
        }

        $remoteUrl = sprintf('https://api.telegram.org/file/bot%s/%s', rawurlencode($botToken), ltrim($file['file_path'], '/'));
        $binary = $this->download($remoteUrl);
        if ($binary === null) {
            return null;
        }

        $extension = strtolower(pathinfo($file['file_path'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $extension = 'jpg';
        }

        $storedName = 'telegram_' . $userId . '.' . $extension;
        $originalRelative = 'uploads/avatars/original/' . $storedName;
        $thumbRelative = 'uploads/avatars/thumb/' . $storedName;
        $originalPath = base_path($originalRelative);
        $thumbPath = base_path($thumbRelative);

        $this->ensureDirectory(dirname($originalPath));
        $this->ensureDirectory(dirname($thumbPath));

        file_put_contents($originalPath, $binary);
        $mime = $this->detectMime($originalPath, $extension);
        $thumbnailCreated = (new AvatarService())->makeThumbnailFromPath($originalPath, $thumbPath, $mime, 240, 240);

        return [
            'user_id' => $userId,
            'type' => 'telegram_avatar',
            'original_name' => basename($file['file_path']),
            'stored_name' => $storedName,
            'mime_type' => $mime,
            'extension' => $extension,
            'size_bytes' => strlen($binary),
            'path' => '/' . $originalRelative,
            'thumbnail_path' => $thumbnailCreated ? '/' . $thumbRelative : '/' . $originalRelative,
        ];
    }

    private function apiRequest(string $method, array $params): ?array
    {
        $url = 'https://api.telegram.org/bot' . config('telegram.bot_token') . '/' . $method . '?' . http_build_query($params);
        $response = $this->download($url);
        if ($response === null) {
            return null;
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded) || empty($decoded['ok']) || empty($decoded['result'])) {
            return null;
        }

        return $decoded['result'];
    }

    private function download(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 20,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $body = curl_exec($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($status >= 200 && $status < 300 && is_string($body)) {
                return $body;
            }
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'timeout' => 20,
                'ignore_errors' => true,
            ],
        ]);
        $body = @file_get_contents($url, false, $context);
        return is_string($body) ? $body : null;
    }

    private function detectMime(string $path, string $extension): string
    {
        if (class_exists('finfo')) {
            $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($path);
            if (is_string($mime) && $mime !== '') {
                return $mime;
            }
        }

        if ($extension === 'png') return 'image/png';
        if ($extension === 'webp') return 'image/webp';
        return 'image/jpeg';
    }

    private function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0775, true);
        }
    }
}
