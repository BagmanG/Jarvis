<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

require_once __DIR__ . '/core/Init.php'; // Подключаем ваш Init.php
require __DIR__ . '/config.php';
require __DIR__ . '/core/BotCore.php';
require __DIR__ . '/core/Command.php';
require __DIR__ . '/core/Router.php';
require __DIR__ . '/core/Messages.php';

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