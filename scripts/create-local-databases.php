<?php
// Uses local .env credentials without printing them. Never drops databases.
require __DIR__.'/../vendor/autoload.php';
Dotenv\Dotenv::createImmutable(dirname(__DIR__))->load();
$pdo = new PDO('pgsql:host='.$_ENV['DB_HOST'].';port='.$_ENV['DB_PORT'].';dbname=postgres', $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
foreach (['ticketing_local', 'ticketing_test'] as $database) {
    $query = $pdo->prepare('SELECT 1 FROM pg_database WHERE datname = ?');
    $query->execute([$database]);
    if (!$query->fetchColumn()) { $pdo->exec('CREATE DATABASE '.$database); }
    echo $database." ready\n";
}
