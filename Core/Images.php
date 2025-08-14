<?php
namespace Bot\Core;

class Images {
    private static $basePath;
    private static $webBaseUrl; // Базовый URL для веб-доступа
    
    public static function init(string $relativePath = '/assets/images/', string $webBaseUrl = null): void {
        self::$basePath = __DIR__ . '/..' . $relativePath;
        self::$webBaseUrl = $webBaseUrl ?? $relativePath;
    }
    
    public static function getPath(string $imageName): string {
        return self::$basePath . $imageName;
    }
    
    public static function getUrl(string $imageName): string {
        return self::$webBaseUrl . $imageName;
    }
    
    // Предопределенные изображения
    public static function welcomeImage(): string {
        return self::getUrl('welcome.jpg');
    }
    
    public static function helpImage(): string {
        return self::getUrl('help.png');
    }
    
    public static function defaultImage(): string {
        return self::getUrl('default.jpg');
    }
}