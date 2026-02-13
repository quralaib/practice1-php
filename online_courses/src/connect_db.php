<?php
function env_load(string $path): array {
    $vars = [];
    if (!is_file($path)) return $vars;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        $pos = strpos($line, '=');
        if ($pos === false) continue;
        $k = trim(substr($line, 0, $pos));
        $v = trim(substr($line, $pos + 1));
        $vars[$k] = $v;
    }
    return $vars;
}

function db(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;

    $env = env_load(__DIR__ . '/../.env');
    $host = $env['DB_HOST'] ?? '127.0.0.1';
    $name = $env['DB_NAME'] ?? '';
    $user = $env['DB_USER'] ?? 'root';
    $pass = $env['DB_PASS'] ?? '';

    $dsn = "mysql:host={$host};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    return $pdo;
}
