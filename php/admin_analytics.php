<?php
// admin_analytics.php — статистика продаж (только для админа)
require_once __DIR__ . '/admin_check.php';
requireAdmin();

// Общие показатели
$totalOrders = 0;
$totalRevenue = 0;
$res = $conn->query("SELECT COUNT(*) AS cnt, COALESCE(SUM(quantity * price), 0) AS rev FROM orders");
if ($res) {
    $row = $res->fetch_assoc();
    $totalOrders = (int)$row['cnt'];
    $totalRevenue = (float)$row['rev'];
}

// Самый продаваемый товар
$topProduct = null;
$res = $conn->query("
    SELECT COALESCE(NULLIF(title, ''), CONCAT('Товар #', product_id)) AS title,
           SUM(quantity) AS total_qty
    FROM orders
    GROUP BY product_id, title
    ORDER BY total_qty DESC
    LIMIT 1
");
if ($res && $res->num_rows) {
    $topProduct = $res->fetch_assoc();
}

// Топ-10 по количеству
$topProducts = [];
$res = $conn->query("
    SELECT COALESCE(NULLIF(title, ''), CONCAT('Товар #', product_id)) AS title,
           SUM(quantity) AS total_qty, SUM(quantity * price) AS total_revenue
    FROM orders
    GROUP BY product_id, title
    ORDER BY total_qty DESC
    LIMIT 10
");
if ($res) {
    $topProducts = $res->fetch_all(MYSQLI_ASSOC);
}

// Продажи по категориям (через таблицу products)
$byCategory = [];
$res = $conn->query("
    SELECT COALESCE(p.category, 'Без категории') AS category,
           SUM(o.quantity) AS total_qty,
           SUM(o.quantity * o.price) AS total_revenue
    FROM orders o
    LEFT JOIN products p ON o.product_id = p.id
    GROUP BY category
    ORDER BY total_revenue DESC
");
if ($res) {
    $byCategory = $res->fetch_all(MYSQLI_ASSOC);
}

// Выручка по датам (последние 14 дней)
$revenueByDate = [];
$res = $conn->query("
    SELECT DATE(created_at) AS date, COALESCE(SUM(quantity * price), 0) AS revenue
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 14 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date ASC
");
if ($res) {
    $revenueByDate = $res->fetch_all(MYSQLI_ASSOC);
}

// Количество товаров в каталоге
$catalogCount = 0;
$res = $conn->query("SELECT COUNT(*) AS c FROM products");
if ($res) {
    $catalogCount = (int)$res->fetch_assoc()['c'];
}

adminRespond([
    'ok' => true,
    'totalOrders' => $totalOrders,
    'totalRevenue' => $totalRevenue,
    'catalogCount' => $catalogCount,
    'topProduct' => $topProduct,
    'topProducts' => $topProducts,
    'byCategory' => $byCategory,
    'revenueByDate' => $revenueByDate
]);
