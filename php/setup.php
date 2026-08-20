<?php
// setup.php — Инициализация БД для магазина «Открытые двери»
// Запуск: перейти в браузере на php/setup.php (один раз).
// Создаёт таблицы orders/products, добавляет роль пользователям,
// создаёт админа (admin / admin123) и наполняет каталог товаров.

require_once __DIR__ . '/db.php';

if (!isset($conn) || !($conn instanceof mysqli) || $conn->connect_error) {
    die('❌ Ошибка подключения к БД: ' . ($conn && $conn->connect_error ? $conn->connect_error : 'неизвестно'));
}

$log = [];

// 1. Добавляем колонку role в users (если ещё нет)
$check = $conn->query("SHOW COLUMNS FROM users LIKE 'role'");
if ($check && $check->num_rows === 0) {
    if ($conn->query("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'")) {
        $log[] = '✅ Добавлена колонка role в users';
    } else {
        $log[] = '❌ Не удалось добавить role: ' . $conn->error;
    }
} else {
    $log[] = 'ℹ️ Колонка role уже существует';
}

// 2. Таблица products
$productsTable = "
CREATE TABLE IF NOT EXISTS products (
  id INT(11) NOT NULL AUTO_INCREMENT,
  title VARCHAR(255) NOT NULL,
  category VARCHAR(100) DEFAULT NULL,
  subcategory VARCHAR(100) DEFAULT NULL,
  material VARCHAR(100) DEFAULT NULL,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  image VARCHAR(255) DEFAULT NULL,
  description TEXT DEFAULT NULL,
  popularity INT(11) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
$log[] = $conn->query($productsTable) ? '✅ Таблица products готова' : '❌ products: ' . $conn->error;

// 3. Таблица orders
$ordersTable = "
CREATE TABLE IF NOT EXISTS orders (
  id INT(11) NOT NULL AUTO_INCREMENT,
  user_id INT(11) DEFAULT NULL,
  product_id INT(11) NOT NULL,
  title VARCHAR(255) DEFAULT NULL,
  quantity INT(11) NOT NULL DEFAULT 1,
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_orders_product (product_id),
  KEY idx_orders_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";
$log[] = $conn->query($ordersTable) ? '✅ Таблица orders готова' : '❌ orders: ' . $conn->error;

// 4. Создаём администратора (admin / admin123)
$stmt = $conn->prepare("SELECT id FROM users WHERE login = 'admin' LIMIT 1");
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $ins = $conn->prepare("INSERT INTO users (login, pass, email, role) VALUES (?, ?, ?, 'admin')");
    $ins->bind_param('sss', $login, $adminHash, $email);
    $login = 'admin';
    $email = 'admin@otkrytye-dveri.ru';
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $log[] = $ins->execute() ? '✅ Администратор admin создан (пароль admin123)' : '❌ admin: ' . $ins->error;
    $ins->close();
} else {
    // убедимся, что роль admin
    $conn->query("UPDATE users SET role = 'admin' WHERE login = 'admin'");
    $log[] = 'ℹ️ Администратор admin уже существует';
}
$stmt->close();

// 5. Наполняем каталог (если пусто)
$countRes = $conn->query("SELECT COUNT(*) AS c FROM products");
$count = $countRes ? (int)$countRes->fetch_assoc()['c'] : 0;

if ($count === 0) {
    $seed = json_decode(file_get_contents(__DIR__ . '/products_seed.json'), true);
    $stmt = $conn->prepare("INSERT INTO products (id, title, category, subcategory, material, price, image, description, popularity) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param('issssdssi', $id, $title, $category, $subcategory, $material, $price, $image, $description, $popularity);
    $inserted = 0;
    foreach ($seed as $p) {
        $id = $p['id'];
        $title = $p['title'];
        $category = $p['category'];
        $subcategory = $p['subcategory'];
        $material = $p['material'];
        $price = $p['price'];
        $image = $p['image'];
        $description = $p['description'];
        $popularity = $p['popularity'] ?? 0;
        if ($stmt->execute()) $inserted++;
    }
    $stmt->close();
    $log[] = "✅ Каталог наполнен: добавлено $inserted товаров";
} else {
    $log[] = "ℹ️ Каталог уже содержит $count товаров (пропущено наполнение)";
}

// 6. Папка для загрузки изображений
$uploadDir = __DIR__ . '/../image/uploads';
if (!is_dir($uploadDir)) {
    @mkdir($uploadDir, 0755, true);
    $log[] = '✅ Создана папка image/uploads';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Установка — Открытые двери</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 40px; color: #222; }
        .box { background: #fff; max-width: 640px; margin: 0 auto; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,.1); }
        h1 { color: #1a4a3f; }
        li { margin: 6px 0; }
        a { color: #1a4a3f; font-weight: 600; }
    </style>
</head>
<body>
<div class="box">
    <h1>✅ Установка завершена</h1>
    <ul>
        <?php foreach ($log as $line): ?>
            <li><?= htmlspecialchars($line) ?></li>
        <?php endforeach; ?>
    </ul>
    <p>Админ: <strong>admin</strong> / <strong>admin123</strong></p>
    <p><a href="status.php">Проверить статус</a> · <a href="../admin.html">Админ-панель</a> · <a href="../index.html">Магазин</a></p>
</div>
</body>
</html>
