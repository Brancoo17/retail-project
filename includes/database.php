<?php

$dbHost = $_ENV['DB_HOST'] ?? '127.0.0.1';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? 'root';
$dbName = $_ENV['DB_NAME'] ?? 'retail_mvc';
$dbPort = (int)($_ENV['DB_PORT'] ?? 3306);

try {
    $db = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
} catch (\Throwable $e) {
    $db = false;
}

if (!$db && $dbHost !== '127.0.0.1') {
    try {
        $db = @mysqli_connect('127.0.0.1', $dbUser, $dbPass, $dbName, $dbPort);
    } catch (\Throwable $e2) {
        $db = false;
    }
}

// En entorno local, si la base de datos no existe todavía, crearla automáticamente
if (!$db) {
    if (($_ENV['APP_ENV'] ?? '') === 'local') {
        try {
            $rootConn = @mysqli_connect($dbHost, $dbUser, $dbPass, '', $dbPort);
            if ($rootConn) {
                $rootConn->query("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $rootConn->close();
                $db = @mysqli_connect($dbHost, $dbUser, $dbPass, $dbName, $dbPort);
            }
        } catch (\Throwable $e3) {
            $db = false;
        }
    }
}

if (!$db) {
    if (php_sapi_name() !== 'cli') {
        echo "Error: No se pudo conectar a MySQL.<br>";
        echo "errno de depuración: " . mysqli_connect_errno() . "<br>";
        echo "error de depuración: " . mysqli_connect_error() . "<br>";
    }
    return;
}

$db->set_charset('utf8mb4');
$db->query("SET time_zone = '-03:00'");
