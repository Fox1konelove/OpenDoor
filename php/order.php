<?php
// order.php — оформление заказа из корзины (сохранение продаж)
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondJson(['ok' => false, 'errors' => ['❌ Неверный запрос']], 405);
}

// Тело запроса (JSON) либо обычный POST
$raw = file_get_contents('php://input');
$input = json_decode($raw, true);
if (!is_array($input)) {
    $input = $_POST;
}
$items = $input['items'] ?? [];

// Допускаем гостевой заказ, но привязываем к пользователю, если он авторизован
$userId = $_SESSION['user_id'] ?? null;

if (empty($items) || !is_array($items)) {
    respondJson(['ok' => false, 'errors' => ['❌ Корзина пуста']], 400);
}

$conn->begin_transaction();
try {
    $ins = $conn->prepare("INSERT INTO orders (user_id, product_id, title, quantity, price) VALUES (?, ?, ?, ?, ?)");
    $upd = $conn->prepare("UPDATE products SET popularity = popularity + ? WHERE id = ?");

    $orderIds = [];
    foreach ($items as $item) {
        $productId = (int)($item['product_id'] ?? $item['id'] ?? 0);
        $qty = max(1, (int)($item['quantity'] ?? 1));
        $price = (float)($item['price'] ?? 0);
        $title = trim((string)($item['title'] ?? ''));

        if ($productId <= 0) continue;

        $ins->bind_param("iiisd", $userId, $productId, $title, $qty, $price);
        $ins->execute();
        $orderIds[] = $conn->insert_id;

        // Обновляем счётчик популярности в каталоге (если товар есть в БД)
        $upd->bind_param("ii", $qty, $productId);
        $upd->execute();
    }
    $ins->close();
    $upd->close();

    if (empty($orderIds)) {
        $conn->rollback();
        respondJson(['ok' => false, 'errors' => ['❌ Не удалось сохранить заказ']], 400);
    }

    $conn->commit();
    respondJson([
        'ok' => true,
        'message' => '✅ Заказ оформлен! Менеджер свяжется с вами.',
        'orderIds' => $orderIds
    ]);
} catch (Throwable $e) {
    $conn->rollback();
    respondJson(['ok' => false, 'errors' => ['❌ Ошибка сохранения заказа']], 500);
}
