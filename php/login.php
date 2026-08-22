<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

function respondJson(array $payload): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    if (isAjax()) respondJson(['ok' => false, 'errors' => ['❌ Ошибка подключения к серверу. ' . ($conn && $conn->connect_error ? $conn->connect_error : 'Попробуйте позже')]]);
    setFlash('error', '❌ Ошибка подключения к серверу. Попробуйте позже');
    header('Location: index.php');
    exit;
}

// Если уже авторизован
if (isset($_SESSION['user_login'])) {
    if (isAjax()) {
        respondJson(['ok' => true, 'message' => 'Вы уже авторизованы', 'user' => $_SESSION['user_login'], 'email' => $_SESSION['user_email'] ?? null]);
    }
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isAjax()) respondJson(['ok' => false, 'errors' => ['❌ Неверный запрос']]);
    header('Location: index.php');
    exit;
}

$login = trim($_POST['Login'] ?? '');
$pass = $_POST['pass'] ?? '';

if (empty($login) || empty($pass)) {
    if (isAjax()) respondJson(['ok' => false, 'errors' => ['❌ Заполните все поля для входа']]);
    setFlash('error', '❌ Заполните все поля для входа');
    header('Location: index.php');
    exit;
}

// Поиск пользователя через MySQLi
$stmt = $conn->prepare("SELECT id, login, pass, email, role FROM users WHERE login = ? LIMIT 1");
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user || !password_verify($pass, $user['pass'])) {
    if (isAjax()) respondJson(['ok' => false, 'errors' => ['❌ Неверный логин или пароль']]);
    setFlash('error', '❌ Неверный логин или пароль');
    header('Location: index.php');
    exit;
}

// Успешный вход
session_regenerate_id(true);
$_SESSION['user_login'] = $user['login'];
$_SESSION['user_id'] = $user['id'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role'] = $user['role'] ?? 'user';

$msg = "🎉 Добро пожаловать, <strong>" . htmlspecialchars($user['login']) . "</strong>!";
if (isAjax()) {
    respondJson([
        'ok' => true,
        'message' => $msg,
        'user' => $user['login'],
        'email' => $user['email'],
        'role' => $user['role'] ?? 'user',
        'isAdmin' => ($user['role'] ?? 'user') === 'admin'
    ]);
}

setFlash('success', $msg);
header('Location: index.php');
exit;