<?php
// cron.php
require_once 'config.php';
require_once 'handler.php';

// Этот файл будет запускаться cron job каждую минуту
// Добавьте в crontab: * * * * * /usr/bin/php /path/to/cron.php

$handler = new TaskHandler();
$result = $handler->handleCron();

// Логируем выполнение
file_put_contents('/tmp/taskmanager-cron.log', 
    date('Y-m-d H:i:s') . " - " . $result . "\n", 
    FILE_APPEND
);

echo "Cron job executed at " . date('Y-m-d H:i:s') . "\n";
?>