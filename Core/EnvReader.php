<?php

class EnvReader
{
    private static array $variables = [];

    public static function load(string $path = '.env'): void
    {
        if (!file_exists($path)) {
            throw new RuntimeException('.env file not found');
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos(trim($line), '#') === 0) {
                continue; // Пропускаем комментарии
            }

            list($name, $value) = self::parseLine($line);
            self::$variables[$name] = $value;
            putenv("$name=$value"); // Также добавляем в getenv()
        }
    }

    public static function get(string $key, $default = null): ?string
    {
        return self::$variables[$key] ?? $default;
    }

    private static function parseLine(string $line): array
    {
        if (strpos($line, '=') === false) {
            throw new RuntimeException('Invalid .env line: ' . $line);
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        // Удаляем кавычки, если они есть
        if (preg_match('/^"(.*)"$/', $value, $matches)) {
            $value = $matches[1];
        } elseif (preg_match('/^\'(.*)\'$/', $value, $matches)) {
            $value = $matches[1];
        }

        return [$name, $value];
    }
}