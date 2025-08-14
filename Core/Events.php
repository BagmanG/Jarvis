<?php
require_once 'Core/Vars.php';
class Events {
    const HOST = "localhost";
    public static $db_password = "";
    public static $db_name = "";
    private static $connection = null;
    public static function Init(string $password, string $name) {
        self::$db_password = $password;
        self::$db_name = $name;

        self::$connection = new mysqli(
            self::HOST,
            self::$db_name,
            self::$db_password,
            self::$db_name
        );
        if (self::$connection->connect_error) {
            die("Connection failed: " . self::$connection->connect_error);
        }
    }

    public static function OnStart() {
    if (!self::$connection) {
        throw new Exception("Database not initialized. Call Events::Init() first.");
    }

    $userId = Vars::getUserId();
    $username = Vars::getUsername();

    // Получаем текущее время в UTC+3 (Москва)
    $registeredTime = new DateTime('now', new DateTimeZone('Europe/Moscow'));
    $registeredFormatted = $registeredTime->format('Y-m-d H:i:s');

    // Проверяем, есть ли пользователь в базе
    $checkQuery = "SELECT * FROM Users WHERE userId = '$userId'";
    $result = self::$connection->query($checkQuery);

    if ($result === false) {
        throw new Exception("Query failed: " . self::$connection->error);
    }

    // Если пользователя нет — добавляем с датой регистрации
    if ($result->num_rows === 0) {
        $insertQuery = "INSERT INTO Users (userId, username, registered) 
                         VALUES ('$userId', '$username', '$registeredFormatted')";
        $insertResult = self::$connection->query($insertQuery);

        if ($insertResult === false) {
            throw new Exception("Failed to add user: " . self::$connection->error);
        }
    }
}

    public static function Execute(string $sql) {
        if (!self::$connection) {
            throw new Exception("Database not initialized. Call Events::Init() first.");
        }

        $result = self::$connection->query($sql);

        if ($result === false) {
            throw new Exception("Query failed: " . self::$connection->error);
        }

        return $result;
    }

    public static function Close() {
        if (self::$connection) {
            self::$connection->close();
            self::$connection = null;
        }
    }
}