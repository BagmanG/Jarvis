<?php
define('DB_HOST', 'localhost');
define('DB_USER', getenv('DB_NAME'));
define('DB_PASS', getenv('DB_PASSWORD'));
define('DB_NAME', getenv('DB_NAME'));
define('BOT_TOKEN', getenv('BOT_TOKEN'));
function getEnvData($key, $default = null) {
    $envFile = __DIR__ . '/.env';
    
    if (!file_exists($envFile)) {
        return $default;
    }
    
    $envData = parse_ini_file($envFile);
    
    return $envData[$key] ?? $default;
}