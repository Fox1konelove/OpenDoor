<?php
// db.php - Подключение к БД через MySQLi

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$config = require __DIR__ . '/config.php';

$conn = new mysqli($config['servername'], $config['username'], $config['password'], $config['dbname']);

$conn && $conn->set_charset($config['charset']);

function setFlash(string $type, string $message): void {
    if (!isset($_SESSION['flash'][$type])) {
        $_SESSION['flash'][$type] = [];
    }
    $_SESSION['flash'][$type][] = $message;
}

function getFlashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

function isAjax(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}
