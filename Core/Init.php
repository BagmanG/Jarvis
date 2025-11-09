<?php
require_once 'EnvReader.php';

// Абсолютный путь к .env
$envPath = __DIR__ . '/../.env';
EnvReader::load($envPath);

define("BOT_TOKEN", EnvReader::get('BOT_TOKEN'));
define("AI_TOKEN", EnvReader::get('AI_TOKEN'));
define("DB_PASSWORD", EnvReader::get('DB_PASSWORD'));
define("DB_NAME", EnvReader::get('DB_NAME'));
define("SUPPORT_CHAT_ID", EnvReader::get('SUPPORT_CHAT_ID'));
define("IS_DEV", false); // Поставьте false для production
?>