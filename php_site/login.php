<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

// Если уже авторизован
if (isset($_SESSION['user_login'])) {
    header('Location: index.php');
    exit;
}

// Проверка метода
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$login = trim($_POST['Login'] ?? '');
$pass = $_POST['pass'] ?? '';

if (empty($login) || empty($pass)) {
    setFlash('error', '❌ Заполните все поля для входа');
    header('Location: index.php');
    exit;
}

// Поиск пользователя через MySQLi
$stmt = $conn->prepare("SELECT id, login, pass, email FROM users WHERE login = ? LIMIT 1");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($pass, $user['pass'])) {
    setFlash('error', '❌ Неверный логин или пароль');
    header('Location: index.php');
    exit;
}

// Успешный вход
session_regenerate_id(true);
$_SESSION['user_login'] = $user['login'];
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];

setFlash('success', "🎉 Добро пожаловать, <strong>" . htmlspecialchars($user['login']) . "</strong>!");
header('Location: index.php');
exit;
?>