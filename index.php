<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/Core/Init.php';
$config = require __DIR__ . '/config.php'; // Теперь $config точно будет массивом
require __DIR__ . '/Core/BotCore.php';
require __DIR__ . '/Core/Command.php';
require __DIR__ . '/Core/Router.php';
require __DIR__ . '/Core/Messages.php';

// Автозагрузка команд
spl_autoload_register(function ($class) {
    $file = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

// Запуск бота
$bot = new Bot\Core\BotCore($config);
$bot->run();
?>