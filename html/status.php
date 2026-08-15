<?php
// status.php - Возвращает статус авторизации в виде JSON (для AJAX)

require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
echo json_encode([
    'loggedIn' => isset($_SESSION['user_login']),
    'login'    => $_SESSION['user_login'] ?? null,
    'email'    => $_SESSION['user_email'] ?? null,
], JSON_UNESCAPED_UNICODE);