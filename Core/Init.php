<?
use Bot\Core\Images;
require_once 'EnvReader.php';

EnvReader::load();
Images::init('/assets/images/');

define("BOT_TOKEN", EnvReader::get('BOT_TOKEN'));
?>