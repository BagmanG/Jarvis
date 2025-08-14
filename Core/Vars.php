<?php

class Vars {
    private static $userId = null;
    private static $username = null;
    private static $chatId = null;
    private static $text = null;
    private static $message = null;
    private static $callbackData = null;
    
    public static function initFromUpdate(array $update) {
        if (isset($update["message"])) {
            self::$message = $update["message"];
            self::$chatId = self::$message["chat"]["id"] ?? null;
            self::$userId = self::$message["from"]["id"] ?? null;
            self::$username = self::$message["from"]["username"] ?? null;
            self::$text = self::$message["text"] ?? null;
        }
        
        if (isset($update["callback_query"])) {
            self::$callbackData = $update["callback_query"]["data"] ?? null;
            // Для callback_query также можно обновить другие поля, если нужно
            if (isset($update["callback_query"]["message"])) {
                self::$chatId = $update["callback_query"]["message"]["chat"]["id"] ?? null;
                self::$userId = $update["callback_query"]["from"]["id"] ?? null;
                self::$username = $update["callback_query"]["from"]["username"] ?? null;
            }
        }
    }
    
    public static function getUserId() {
        return self::$userId;
    }
    
    public static function getUsername() {
        return self::$username;
    }
    
    public static function getChatId() {
        return self::$chatId;
    }
    
    public static function getText() {
        return self::$text;
    }
    
    public static function getMessage() {
        return self::$message;
    }
    
    public static function getCallbackData() {
        return self::$callbackData;
    }
    
    // Очистка данных (например, при обработке нового обновления)
    public static function clear() {
        self::$userId = null;
        self::$username = null;
        self::$chatId = null;
        self::$text = null;
        self::$message = null;
        self::$callbackData = null;
    }
}