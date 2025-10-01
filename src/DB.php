<?php
namespace App;

use PDO;

class DB {
    private static $pdo;

    public static function get() : PDO {
        if (self::$pdo) return self::$pdo;

        $host = $_ENV['DB_HOST'] ?: '127.0.0.1';
        $port = $_ENV['DB_PORT'] ?: '3306';
        $db   = $_ENV['DB_NAME'] ?: 'goals_db';
        $user = $_ENV['DB_USER'] ?: 'root';
        $pass = $_ENV['DB_PASS'] ?: 'admin';

        $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]);
        self::$pdo = $pdo;
        return $pdo;
    }
}
