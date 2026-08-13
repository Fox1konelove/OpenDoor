<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit;
}

$login = trim($_POST['Login'] ?? '');
$pass = $_POST['pass'] ?? '';
$repeatpass = $_POST['repeatpass'] ?? '';
$email = trim($_POST['email'] ?? '');
$hasErrors = false;

// Валидация
if (empty($login) || strlen($login) < 3 || strlen($login) > 30) {
    setFlash('error', '⚠️ Логин должен быть от 3 до 30 символов');
    $hasErrors = true;
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $login)) {
    setFlash('error', '⚠️ Логин: только буквы, цифры и _');
    $hasErrors = true;
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setFlash('error', '❌ Введите корректный email');
    $hasErrors = true;
}

if (empty($pass) || strlen($pass) < 8) {
    setFlash('error', '⚠️ Пароль должен содержать минимум 8 символов');
    $hasErrors = true;
} elseif (!preg_match('/[A-Z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
    setFlash('error', '⚠️ Пароль: 1 заглавная буква и 1 цифра');
    $hasErrors = true;
}

if ($pass !== $repeatpass) {
    setFlash('error', '🔒 Пароли не совпадают');
    $hasErrors = true;
}

// Проверка уникальности
if (!$hasErrors) {
    $stmt = $conn->prepare("SELECT login, email FROM users WHERE login = ? OR email = ?");
    $stmt->bind_param("ss", $login, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing = $result->fetch_assoc();
    
    if ($existing) {
        if ($existing['login'] === $login) {
            setFlash('error', '👤 Такой логин уже занят');
            $hasErrors = true;
        }
        if ($existing['email'] === $email) {
            setFlash('error', '📧 Этот email уже зарегистрирован');
            $hasErrors = true;
        }
    }
}

if ($hasErrors) {
    header('Location: index.php');
    exit;
}

// Хеширование пароля
$hash = password_hash($pass, PASSWORD_DEFAULT);

// Сохранение в БД
$stmt = $conn->prepare("INSERT INTO users (login, pass, email) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $login, $hash, $email);

if ($stmt->execute()) {
    session_regenerate_id(true);
    $_SESSION['user_login'] = $login;
    setFlash('success', "✅ Регистрация успешна! Добро пожаловать, <strong>" . htmlspecialchars($login) . "</strong>!");
    header('Location: index.php');
    exit;
} else {
    setFlash('error', '❌ Не удалось создать аккаунт. Попробуйте позже');
    header('Location: index.php');
    exit;
}
?>