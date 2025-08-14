<?php

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

    public static function OnStart(string $password, string $name) {
        
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