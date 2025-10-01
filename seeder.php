<?php
require __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'] ?? '127.0.0.1';
$port = $_ENV['DB_PORT'] ?? '3306';
$db   = $_ENV['DB_NAME'] ?? 'goals_db';
$user = $_ENV['DB_USER'] ?? 'root';
$pass = $_ENV['DB_PASS'] ?? 'admin';

$dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
$pdo = new PDO($dsn, $user, $pass, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$email = 'demo@example.com';
$password = 'password123'; // change this for production!
$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
$stmt->execute([':email' => $email]);
if ($stmt->fetch()) {
    echo "User already exists: $email\n";
    exit;
}

$ins = $pdo->prepare("INSERT INTO users (email, password_hash) VALUES (:email, :hash)");
$ins->execute([':email' => $email, ':hash' => $hash]);

echo "Seeded user: $email with password: $password\n";
