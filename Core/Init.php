<?
require_once 'EnvReader.php';

EnvReader::load();

define("BOT_TOKEN", EnvReader::get('BOT_TOKEN'));
define("AI_TOKEN", EnvReader::get('AI_TOKEN'));
define("DB_PASSWORD", EnvReader::get('DB_PASSWORD'));
define("DB_NAME", EnvReader::get('DB_NAME'));
define("SUPPORT_CHAT_ID", EnvReader::get('SUPPORT_CHAT_ID'));
?>