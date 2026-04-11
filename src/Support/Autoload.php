<?php
spl_autoload_register(function ($class) {
    $prefix = 'MiniApp\\';
    $baseDir = __DIR__ . '/../';

    if (strpos($class, $prefix) !== 0) {
        return;
    }

    $relative = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';

    if (is_file($file)) {
        require_once $file;
    }
});
