<?
require_once 'EnvReader.php';
require_once 'Images.php';

EnvReader::load();
\Bot\Core\Images::init('/assets/images/');

define("BOT_TOKEN", EnvReader::get('BOT_TOKEN'));
?>