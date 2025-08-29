<?php

class ImageProcessor {
    
    /**
     * Проверяет, является ли файл изображением
     */
    public static function isValidImage(string $mimeType): bool {
        $validTypes = [
            'image/jpeg',
            'image/jpg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/bmp'
        ];
        
        return in_array(strtolower($mimeType), $validTypes);
    }
    
    /**
     * Получает URL изображения из Telegram файла
     */
    public static function getImageUrlFromTelegram(string $fileId, string $botToken): ?string {
        $file_info_url = "https://api.telegram.org/bot" . $botToken . "/getFile?file_id=" . $fileId;
        $file_info = json_decode(file_get_contents($file_info_url), true);
        
        if ($file_info && isset($file_info["result"]["file_path"])) {
            $file_path = $file_info["result"]["file_path"];
            return "https://api.telegram.org/file/bot" . $botToken . "/" . $file_path;
        }
        
        return null;
    }
    
    /**
     * Проверяет размер изображения (в байтах)
     */
    public static function getImageSize(string $imageUrl): ?int {
        $headers = get_headers($imageUrl, 1);
        if ($headers && isset($headers['Content-Length'])) {
            return (int)$headers['Content-Length'];
        }
        return null;
    }
    
    /**
     * Проверяет, не превышает ли изображение максимальный размер (20MB для GPT-4o)
     */
    public static function isImageSizeValid(string $imageUrl, int $maxSize = 20971520): bool {
        $size = self::getImageSize($imageUrl);
        return $size === null || $size <= $maxSize;
    }
    
    /**
     * Получает информацию об изображении для отправки в GPT
     */
    public static function prepareImageForGPT(string $imageUrl, string $detail = 'high'): array {
        return [
            'type' => 'image_url',
            'image_url' => [
                'url' => $imageUrl,
                'detail' => $detail
            ]
        ];
    }
    
    /**
     * Формирует массив изображений для GPT API
     */
    public static function prepareImagesForGPT(array $imageUrls, string $detail = 'high'): array {
        $images = [];
        foreach ($imageUrls as $imageUrl) {
            $images[] = self::prepareImageForGPT($imageUrl, $detail);
        }
        return $images;
    }
    
    /**
     * Валидирует изображение перед отправкой в GPT
     */
    public static function validateImage(string $imageUrl, string $botToken): array {
        $result = [
            'valid' => false,
            'url' => null,
            'error' => null
        ];
        
        // Проверяем размер
        if (!self::isImageSizeValid($imageUrl)) {
            $result['error'] = 'Изображение слишком большое. Максимальный размер: 20MB';
            return $result;
        }
        
        // Проверяем доступность
        $headers = get_headers($imageUrl);
        if (!$headers || strpos($headers[0], '200') === false) {
            $result['error'] = 'Изображение недоступно';
            return $result;
        }
        
        $result['valid'] = true;
        $result['url'] = $imageUrl;
        return $result;
    }
}
