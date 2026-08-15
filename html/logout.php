<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function isAjax(): bool {
    return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
        && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
}

function respondJson(array $payload): void {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// Очищаем сессию
$_SESSION = array();

// Удаляем cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

if (isAjax()) {
    respondJson(['ok' => true, 'message' => '👋 Вы вышли из аккаунта']);
}

header('Location: index.html');
exit;