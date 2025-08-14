<?
require_once 'EnvReader.php';

EnvReader::load();

define("BOT_TOKEN", EnvReader::get('BOT_TOKEN'));
define("AI_TOKEN", EnvReader::get('AI_TOKEN'));
?>