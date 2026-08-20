<?php
// admin_orders.php — список заказов и аналитика продаж (только для админа)
require_once __DIR__ . '/admin_check.php';
requireAdmin();

$limit = isset($_GET['limit']) ? max(1, min(500, (int)$_GET['limit'])) : 100;

// Список заказов
$orders = [];
$res = $conn->query("SELECT o.*, u.login AS user_login FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT $limit");
if ($res) {
    $orders = $res->fetch_all(MYSQLI_ASSOC);
}

// Сводка
$summary = ['count' => 0, 'revenue' => 0];
$res = $conn->query("SELECT COUNT(*) AS cnt, COALESCE(SUM(quantity * price), 0) AS rev FROM orders");
if ($res) {
    $row = $res->fetch_assoc();
    $summary = ['count' => (int)$row['cnt'], 'revenue' => (float)$row['rev']];
}

// Топ продаваемых (по количеству)
$topSelling = [];
$res = $conn->query("
    SELECT product_id, COALESCE(NULLIF(title, ''), CONCAT('Товар #', product_id)) AS title,
           SUM(quantity) AS total_qty, SUM(quantity * price) AS total_revenue
    FROM orders
    GROUP BY product_id, title
    ORDER BY total_qty DESC
    LIMIT 10
");
if ($res) {
    $topSelling = $res->fetch_all(MYSQLI_ASSOC);
}

adminRespond([
    'ok' => true,
    'orders' => $orders,
    'summary' => $summary,
    'topSelling' => $topSelling
]);
