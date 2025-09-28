<?php
if (!function_exists('getEnvData')) {
    function getEnvData($key, $default = null) {
        $envFile = __DIR__ . '/.env';
        if (!file_exists($envFile)) {
            return $default;
        }
        $envData = parse_ini_file($envFile);
        return $envData[$key] ?? $default;
    }
}
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_USER')) define('DB_USER', getEnvData('DB_NAME'));
if (!defined('DB_PASSWORD')) define('DB_PASSWORD', getEnvData('DB_PASSWORD'));
if (!defined('DB_NAME')) define('DB_NAME', getEnvData('DB_NAME'));
if (!defined('BOT_TOKEN')) define('BOT_TOKEN', getEnvData('BOT_TOKEN'));