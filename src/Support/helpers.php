<?php

function base_path($path = '')
{
    $base = dirname(__DIR__, 2);
    return $path ? $base . '/' . ltrim($path, '/') : $base;
}

function config($key = null, $default = null)
{
    static $config = null;

    if ($config === null) {
        $config = require base_path('config/config.php');
    }

    if ($key === null) {
        return $config;
    }

    $segments = explode('.', $key);
    $value = $config;

    foreach ($segments as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function now()
{
    return date('Y-m-d H:i:s');
}

function public_url($path)
{
    $baseUrl = rtrim((string) config('app.base_url', ''), '/');
    $path = '/' . ltrim($path, '/');
    return $baseUrl ? $baseUrl . $path : $path;
}
