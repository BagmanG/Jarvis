<?php
// cron.php
require_once 'config.php';
$_GET['action'] = 'cron';
require_once 'handler.php';

// Этот файл будет запускаться cron job каждую минуту
// Добавьте в crontab: * * * * * /usr/bin/php /path/to/cron.php

$handler = new TaskHandler();
$result = $handler->handleCron();

// Логируем выполнение
error_log(date('Y-m-d H:i:s') . ' - ' . $result . PHP_EOL, 3, 'error.log');

echo "Cron job executed at " . date('Y-m-d H:i:s') . "\n";
?>