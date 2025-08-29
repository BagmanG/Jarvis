<?php

class ImageGenerator {
    private static $api_key = "";
    private static $api_url = 'https://api.aitunnel.ru/v1/images/generations';
    
    public static function Init(string $key) {
        self::$api_key = $key;
    }
    
    /**
     * Генерирует изображение по текстовому описанию
     */
    public static function generateImage(string $prompt, string $size = '1024x1024', int $n = 1): array {
        if (empty(self::$api_key)) {
            throw new Exception('API key is not set. Please call ImageGenerator::Init() first.');
        }
        
        // Данные для API запроса
        $data = [
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => $n,
            'size' => $size,
            'quality' => 'standard',
            'response_format' => 'url'
        ];
        
        $ch = curl_init(self::$api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . self::$api_key
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        // Проверяем ошибки cURL
        if ($curl_error) {
            throw new Exception('cURL error: ' . $curl_error);
        }
        
        // Декодируем JSON ответ
        $responseData = json_decode($response, true);
        
        // Проверяем на ошибки декодирования
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Failed to decode JSON response: ' . $response);
        }
        
        // Проверяем HTTP код ответа
        if ($http_code !== 200) {
            $error_message = isset($responseData['error']['message']) ? $responseData['error']['message'] : 'Unknown API error';
            throw new Exception('API error (HTTP ' . $http_code . '): ' . $error_message);
        }
        
        // Возвращаем результат
        if (isset($responseData['data']) && !empty($responseData['data'])) {
            return [
                'success' => true,
                'images' => $responseData['data'],
                'revised_prompt' => $responseData['data'][0]['revised_prompt'] ?? $prompt
            ];
        }
        
        throw new Exception('Invalid response structure');
    }
    
    /**
     * Проверяет, является ли промпт запросом на генерацию изображения
     */
    public static function isImageGenerationRequest(string $text): bool {
        $keywords = [
            'нарисуй', 'создай изображение', 'сгенерируй картинку', 'покажи как выглядит',
            'визуализируй', 'изобрази', 'создай картинку', 'сделай рисунок',
            'draw', 'create image', 'generate image', 'show me', 'visualize',
            'illustrate', 'make picture', 'design', 'sketch'
        ];
        
        $text_lower = mb_strtolower($text);
        
        foreach ($keywords as $keyword) {
            if (strpos($text_lower, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Извлекает описание для генерации из текста пользователя
     */
    public static function extractPromptFromText(string $text): string {
        // Удаляем ключевые слова команд и оставляем описание
        $patterns = [
            '/^(нарисуй|создай изображение|сгенерируй картинку|покажи как выглядит|визуализируй|изобрази|создай картинку|сделай рисунок)\s*/ui',
            '/^(draw|create image|generate image|show me|visualize|illustrate|make picture|design|sketch)\s*/i'
        ];
        
        $prompt = trim($text);
        foreach ($patterns as $pattern) {
            $prompt = preg_replace($pattern, '', $prompt);
        }
        
        return trim($prompt);
    }
    
    /**
     * Улучшает промпт для DALL-E
     */
    public static function enhancePrompt(string $prompt): string {
        // Добавляем детали для лучшего качества изображения
        $enhancements = [
            'high quality',
            'detailed',
            'professional'
        ];
        
        // Проверяем, не слишком ли короткий промпт
        if (strlen($prompt) < 20) {
            $prompt .= ', ' . implode(', ', $enhancements);
        }
        
        return $prompt;
    }
    
    /**
     * Получает список доступных размеров для DALL-E
     */
    public static function getAvailableSizes(): array {
        return [
            '1024x1024' => 'Квадрат (1:1)',
            '1792x1024' => 'Горизонтальный (16:9)',
            '1024x1792' => 'Вертикальный (9:16)'
        ];
    }
}
