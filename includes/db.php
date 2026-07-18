<?php
// Parse Railway public MySQL URL if available
if (getenv('MYSQL_PUBLIC_URL')) {
    $url = parse_url(getenv('MYSQL_PUBLIC_URL'));
    $host = $url['host'];
    $port = $url['port'];
    $username = $url['user'];
    $password = $url['pass'];
    $dbname = ltrim($url['path'], '/');
} else {
    // Local WAMP defaults
    $host = 'localhost';
    $port = '3306';
    $dbname = 'attendease_db';
    $username = 'root';
    $password = '';
}

try {
    $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>

