<?php
// Use Railway environment variables in production, fallback to local WAMP settings
$host = getenv('MYSQL_HOST') ?: 'localhost';
$dbname = getenv('MYSQL_DATABASE') ?: 'attendease_db';
$username = getenv('MYSQL_USER') ?: 'root';
$password = getenv('MYSQL_PASSWORD') ?: '';
$port = getenv('MYSQL_PORT') ?: '3306';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>