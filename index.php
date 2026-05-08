<?php

echo "<h1>Jarvis works!</h1>";
echo "<p>PHP version: " . phpversion() . "</p>";

$mysqli = new mysqli("localhost", "jarvis_user", "СЮДА_СЛОЖНЫЙ_ПАРОЛЬ", "jarvis_db");

if ($mysqli->connect_error) {
    die("<p>MySQL error: " . $mysqli->connect_error . "</p>");
}

echo "<p>MySQL connected successfully.</p>";
