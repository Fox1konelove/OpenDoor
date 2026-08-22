<?php
// admin_check.php — общие помощники для админ-эндпоинтов
require_once __DIR__ . '/db.php';

function adminRespond(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireAdmin(): void {
    if (empty($_SESSION['user_login']) || ($_SESSION['user_role'] ?? '') !== 'admin') {
        adminRespond(['ok' => false, 'error' => 'Доступ запрещён. Требуется роль администратора.'], 403);
    }
}
