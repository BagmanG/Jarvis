<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/Core/Init.php'; // Подключаем ваш Init.php
require __DIR__ . '/config.php';
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

// Запуск бота (теперь без передачи токена в BotCore)
$bot = new Bot\Core\BotCore($config);
$bot->run();
?>