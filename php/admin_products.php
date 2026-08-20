<?php
// admin_products.php — CRUD товаров (только для админа)
require_once __DIR__ . '/admin_check.php';
requireAdmin();

function getUploadDir(): string {
    return __DIR__ . '/../image/uploads';
}

function handleUpload(): ?string {
    if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $name = basename($_FILES['image']['name']);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        return null;
    }
    if (!is_dir(getUploadDir())) {
        @mkdir(getUploadDir(), 0755, true);
    }
    $safeName = uniqid('prod_', true) . '.' . $ext;
    $dest = getUploadDir() . '/' . $safeName;
    if (move_uploaded_file($_FILES['image']['tmp_name'], $dest)) {
        return 'image/uploads/' . $safeName;
    }
    return null;
}

$method = $_SERVER['REQUEST_METHOD'];
$override = strtoupper($_POST['_method'] ?? '');
if ($method === 'POST' && in_array($override, ['PUT', 'DELETE'], true)) {
    $method = $override;
}

// ===== GET: список или один товар =====
if ($method === 'GET') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    if ($id > 0) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($product) {
            adminRespond(['ok' => true, 'product' => $product]);
        }
        adminRespond(['ok' => false, 'error' => 'Товар не найден'], 404);
    }
    $res = $conn->query("SELECT * FROM products ORDER BY id ASC");
    $list = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
    adminRespond(['ok' => true, 'products' => $list, 'count' => count($list)]);
}

// ===== POST: создание =====
if ($method === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $subcategory = trim($_POST['subcategory'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $popularity = (int)($_POST['popularity'] ?? 0);

    if ($title === '' || $category === '') {
        adminRespond(['ok' => false, 'error' => 'Название и категория обязательны'], 400);
    }

    $image = handleUpload();

    $stmt = $conn->prepare("INSERT INTO products (title, category, subcategory, material, price, image, description, popularity) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdssi", $title, $category, $subcategory, $material, $price, $image, $description, $popularity);
    if ($stmt->execute()) {
        $newId = $conn->insert_id;
        $stmt->close();
        adminRespond(['ok' => true, 'message' => 'Товар добавлен', 'id' => $newId], 201);
    }
    $err = $stmt->error;
    $stmt->close();
    adminRespond(['ok' => false, 'error' => 'Ошибка добавления: ' . $err], 500);
}

// ===== PUT: обновление =====
if ($method === 'PUT') {
    $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
    if ($id <= 0) {
        adminRespond(['ok' => false, 'error' => 'Не указан id товара'], 400);
    }
    $existing = $conn->query("SELECT * FROM products WHERE id = $id");
    if (!$existing || $existing->num_rows === 0) {
        adminRespond(['ok' => false, 'error' => 'Товар не найден'], 404);
    }

    $title = trim($_POST['title'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $subcategory = trim($_POST['subcategory'] ?? '');
    $material = trim($_POST['material'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $popularity = (int)($_POST['popularity'] ?? 0);

    if ($title === '' || $category === '') {
        adminRespond(['ok' => false, 'error' => 'Название и категория обязательны'], 400);
    }

    $uploaded = handleUpload();
    $imageSql = $uploaded ? ", image = '" . $conn->real_escape_string($uploaded) . "'" : "";

    $stmt = $conn->prepare("UPDATE products SET title = ?, category = ?, subcategory = ?, material = ?, price = ?, description = ?, popularity = ? $imageSql WHERE id = ?");
    $stmt->bind_param("ssssdssi", $title, $category, $subcategory, $material, $price, $description, $popularity, $id);
    if ($stmt->execute()) {
        adminRespond(['ok' => true, 'message' => 'Товар обновлён']);
    }
    $err = $stmt->error;
    $stmt->close();
    adminRespond(['ok' => false, 'error' => 'Ошибка обновления: ' . $err], 500);
}

// ===== DELETE: удаление =====
if ($method === 'DELETE') {
    $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) {
        adminRespond(['ok' => false, 'error' => 'Не указан id товара'], 400);
    }
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        adminRespond(['ok' => true, 'message' => 'Товар удалён']);
    }
    adminRespond(['ok' => false, 'error' => 'Ошибка удаления'], 500);
}

adminRespond(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
