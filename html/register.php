<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/db.php';

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    $dbError = '❌ Ошибка подключения к серверу. ' . ($conn && $conn->connect_error ? $conn->connect_error : 'Попробуйте позже');
    if (isAjax()) respondJson(['ok' => false, 'errors' => [$dbError]]);
    if (function_exists('setFlash')) setFlash('error', $dbError);
    header('Location: index.php');
    exit;
}

if (!function_exists('setFlash') || !function_exists('isAjax')) {
    $dbError = '❌ Ошибка сервера. Попробуйте позже';
    if (isAjax()) respondJson(['ok' => false, 'errors' => [$dbError]]);
    header('Location: index.php');
    exit;
}

function respondJson(array $payload): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (isAjax()) respondJson(['ok' => false, 'errors' => ['❌ Неверный запрос']]);
    header('Location: index.php');
    exit;
}

$login = trim($_POST['Login'] ?? '');
$pass = $_POST['pass'] ?? '';
$repeatpass = $_POST['repeatpass'] ?? '';
$email = trim($_POST['email'] ?? '');
$errors = [];

// Валидация
if (empty($login) || strlen($login) < 3 || strlen($login) > 30) {
    $errors[] = '⚠️ Логин должен быть от 3 до 30 символов';
} elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $login)) {
    $errors[] = '⚠️ Логин: только буквы, цифры и _';
}

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = '❌ Введите корректный email';
}

if (empty($pass) || strlen($pass) < 8) {
    $errors[] = '⚠️ Пароль должен содержать минимум 8 символов';
} elseif (!preg_match('/[A-Z]/', $pass) || !preg_match('/[0-9]/', $pass)) {
    $errors[] = '⚠️ Пароль: 1 заглавная буква и 1 цифра';
}

if ($pass !== $repeatpass) {
    $errors[] = '🔒 Пароли не совпадают';
}

// Проверка уникальности
if (!$errors) {
    $stmt = $conn->prepare("SELECT login, email FROM users WHERE login = ? OR email = ?");
    if (!$stmt) {
        $errors[] = '❌ Ошибка сервера. Попробуйте позже';
    } else {
        $stmt->bind_param("ss", $login, $email);
        if (!$stmt->execute()) {
            $errors[] = '❌ Ошибка сервера. Попробуйте позже';
        } else {
            $result = $stmt->get_result();
            $existing = $result->fetch_assoc();

            if ($existing) {
                if ($existing['login'] === $login) {
                    $errors[] = '👤 Такой логин уже занят';
                }
                if ($existing['email'] === $email) {
                    $errors[] = '📧 Этот email уже зарегистрирован';
                }
            }
        }
        $stmt->close();
    }
}

if ($errors) {
    if (isAjax()) respondJson(['ok' => false, 'errors' => $errors]);
    foreach ($errors as $e) setFlash('error', $e);
    header('Location: index.php');
    exit;
}

// Хеширование пароля
$hash = password_hash($pass, PASSWORD_DEFAULT);

// Сохранение в БД
$stmt = $conn->prepare("INSERT INTO users (login, pass, email) VALUES (?, ?, ?)");
if (!$stmt) {
    if (isAjax()) respondJson(['ok' => false, 'errors' => ['❌ Не удалось создать аккаунт. Попробуйте позже']]);
    setFlash('error', '❌ Не удалось создать аккаунт. Попробуйте позже');
    header('Location: index.php');
    exit;
}

$stmt->bind_param("sss", $login, $hash, $email);

if ($stmt->execute()) {
    session_regenerate_id(true);
    $_SESSION['user_login'] = $login;
    $_SESSION['user_email'] = $email;
    $msg = "✅ Регистрация успешна! Добро пожаловать, <strong>" . htmlspecialchars($login) . "</strong>!";
    if (isAjax()) respondJson(['ok' => true, 'message' => $msg, 'user' => $login]);
    setFlash('success', $msg);
    header('Location: index.php');
    exit;
} else {
    if (isAjax()) respondJson(['ok' => false, 'errors' => ['❌ Не удалось создать аккаунт. Попробуйте позже']]);
    setFlash('error', '❌ Не удалось создать аккаунт. Попробуйте позже');
    header('Location: index.php');
    exit;
}