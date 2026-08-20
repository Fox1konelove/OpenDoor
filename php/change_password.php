<?php
// change_password.php — смена пароля для авторизованного пользователя
require_once __DIR__ . '/db.php';

function respondJson(array $payload, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    respondJson(['ok' => false, 'errors' => ['❌ Ошибка подключения к серверу']], 500);
}

if (!isset($_SESSION['user_login'])) {
    respondJson(['ok' => false, 'errors' => ['🔒 Вы не авторизованы']], 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(['ok' => false, 'errors' => ['❌ Неверный запрос']], 405);
}

$current = $_POST['current_pass'] ?? '';
$new = $_POST['new_pass'] ?? '';
$repeat = $_POST['repeat_pass'] ?? '';
$errors = [];

if (empty($current)) {
    $errors[] = '🔒 Введите текущий пароль';
}

if (empty($new) || strlen($new) < 8) {
    $errors[] = '⚠️ Новый пароль: минимум 8 символов';
} elseif (!preg_match('/[A-Z]/', $new) || !preg_match('/[0-9]/', $new)) {
    $errors[] = '⚠️ Новый пароль: 1 заглавная буква и 1 цифра';
}

if ($new !== $repeat) {
    $errors[] = '🔒 Новые пароли не совпадают';
}

if ($errors) {
    respondJson(['ok' => false, 'errors' => $errors], 400);
}

// Проверяем текущий пароль
$stmt = $conn->prepare("SELECT id, pass FROM users WHERE login = ? LIMIT 1");
$stmt->bind_param("s", $_SESSION['user_login']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user || !password_verify($current, $user['pass'])) {
    respondJson(['ok' => false, 'errors' => ['🔒 Текущий пароль указан неверно']], 400);
}

$hash = password_hash($new, PASSWORD_DEFAULT);
$stmt = $conn->prepare("UPDATE users SET pass = ? WHERE id = ?");
$stmt->bind_param("si", $hash, $user['id']);

if ($stmt->execute()) {
    respondJson(['ok' => true, 'message' => '✅ Пароль успешно изменён']);
} else {
    respondJson(['ok' => false, 'errors' => ['❌ Не удалось изменить пароль']], 500);
}
