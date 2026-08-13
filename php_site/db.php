<?php
// db.php - Подключение к БД через MySQLi

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "127.0.0.1";
$dbname = 'registerUser';
$username = 'root';
$password = '';


// Создаем подключение
$conn = new mysqli($servername, $username, $password, $dbname);

// Проверяем подключение
if ($conn->connect_error) {
    die("⚠️ Ошибка подключения к БД: " . $conn->connect_error);
}

// Устанавливаем кодировку
$conn->set_charset("utf8mb4");

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
?>