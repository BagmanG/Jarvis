<?php
// cron.php
require_once 'config.php';
$_GET['action'] = 'cron';
require_once 'handler.php';

// Этот файл будет запускаться cron job каждую минуту
// Добавьте в crontab: * * * * * /usr/bin/php /path/to/cron.php

$handler = new TaskHandler();
$result = $handler->handleCron();
$file = __DIR__ . '/taskmanager-cron.log';
// Логируем выполнение
file_put_contents($file, 
    date('Y-m-d H:i:s') . " - " . $result . "\n", 
    FILE_APPEND
);


echo "Cron job executed at " . date('Y-m-d H:i:s') . "\n";
?>