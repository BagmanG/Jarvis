<?
require_once 'Core/EnvReader.php';

define("BOT_TOKEN", EnvReader::get('BOT_TOKEN'));

echo "Hello World Test :".BOT_TOKEN;
?>