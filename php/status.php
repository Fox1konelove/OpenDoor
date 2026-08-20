<?php
// status.php - Возвращает статус авторизации в виде JSON (для AJAX)

require_once __DIR__ . '/db.php';

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'loggedIn' => false,
        'login'    => null,
        'email'    => null,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$role = $_SESSION['user_role'] ?? 'user';
header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'loggedIn' => isset($_SESSION['user_login']),
    'login'    => $_SESSION['user_login'] ?? null,
    'email'    => $_SESSION['user_email'] ?? null,
    'role'     => $role,
    'isAdmin'  => $role === 'admin',
], JSON_UNESCAPED_UNICODE);