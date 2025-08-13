<?
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
require_once 'Core/EnvReader.php';

EnvReader::load();
define("BOT_TOKEN", EnvReader::get('BOT_TOKEN'));

echo "Hello World Test :".BOT_TOKEN;
?>