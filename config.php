<?php
define('DB_HOST', 'localhost');
define('DB_USER', getEnvData('DB_NAME'));
define('DB_PASSWORD', getEnvData('DB_PASSWORD'));
define('DB_NAME', getEnvData('DB_NAME'));
define('BOT_TOKEN', getEnvData('BOT_TOKEN'));
function getEnvData($key, $default = null) {
    $envFile = __DIR__ . '/.env';
    
    if (!file_exists($envFile)) {
        return $default;
    }
    
    $envData = parse_ini_file($envFile);
    
    return $envData[$key] ?? $default;
}