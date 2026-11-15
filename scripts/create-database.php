<?php

// Creates only the explicitly configured application database. Never drops data.
require __DIR__.'/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->safeLoad();
$name = getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? '');
if (! preg_match('/^[a-z][a-z0-9_]{1,63}$/', $name)) {
    throw new RuntimeException('Invalid database name.');
}
$get = fn ($key, $default = '') => getenv($key) ?: ($_ENV[$key] ?? $default);
try {
    $pdo = new PDO('mysql:host='.$get('DB_HOST', '127.0.0.1').';port='.$get('DB_PORT', '3306'), $get('DB_USERNAME'), $get('DB_PASSWORD'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$name}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    echo "Database {$name} is ready.\n";
} catch (Throwable $e) {
    fwrite(STDERR, 'Database setup failed: '.$e->getMessage()."\n");
    exit(1);
}
