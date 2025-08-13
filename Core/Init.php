<?
require_once 'EnvReader.php';

EnvReader::load();

define("BOT_TOKEN", EnvReader::get('BOT_TOKEN'));
?>