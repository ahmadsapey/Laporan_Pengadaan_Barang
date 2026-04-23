<?php
// MySQL connection configuration
$databaseUrl = getenv('DATABASE_URL') ?: getenv('MYSQL_URL') ?: getenv('CLEARDB_DATABASE_URL');

if ($databaseUrl) {
    $url = parse_url($databaseUrl);
    $host = $url['host'] ?? '127.0.0.1';
    $port = $url['port'] ?? '3306';
    $user = $url['user'] ?? 'root';
    $pass = $url['pass'] ?? '';
    $db   = ltrim($url['path'] ?? '', '/');
} else {
    $host = getenv('DB_HOST') ?: getenv('MYSQL_HOST') ?: '127.0.0.1';
    $port = getenv('DB_PORT') ?: getenv('MYSQL_PORT') ?: '3306';
    $db   = getenv('DB_NAME') ?: getenv('MYSQL_DATABASE') ?: 'dashboard';
    $user = getenv('DB_USER') ?: getenv('MYSQL_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: getenv('MYSQL_PASSWORD') ?: '';
}

$charset = 'utf8mb4';
$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    http_response_code(500);
    die('Database connection failed: ' . $e->getMessage());
}
