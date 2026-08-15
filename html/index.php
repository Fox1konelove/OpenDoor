<?php
require_once __DIR__ . '/db.php';

// Получаем сообщения
$flashes = getFlashes();

// Проверяем, авторизован ли пользователь
$isLoggedIn = isset($_SESSION['user_login']);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .container { display: flex; gap: 20px; flex-wrap: wrap; justify-content: center; padding: 20px; }
        .card { background: white; padding: 30px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 320px; }
        .card.logged { border: 2px solid #4CAF50; background: #f0faf0; }
        h2 { margin-top: 0; color: #333; text-align: center; }
        h2 .badge { font-size: 14px; background: #4CAF50; color: white; padding: 2px 12px; border-radius: 20px; }
        input { width: 100%; padding: 12px; margin: 8px 0; box-sizing: border-box; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
        button { width: 100%; padding: 12px; background: #4CAF50; color: white; border: none; border-radius: 6px; cursor: pointer; font-size: 16px; margin-top: 10px; transition: 0.3s; }
        button:hover { background: #45a049; }
        button.danger { background: #f44336; }
        button.danger:hover { background: #d32f2f; }
        .link-form { display: block; text-align: center; margin-top: 14px; color: #4CAF50; text-decoration: none; font-size: 14px; }
        .link-form:hover { text-decoration: underline; }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.6); display: flex; justify-content: center;
            align-items: center; z-index: 1000; backdrop-filter: blur(3px);
        }
        .modal {
            background: white; padding: 30px; border-radius: 12px;
            max-width: 400px; width: 90%; text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            animation: popIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        .modal.success { border-top: 5px solid #4CAF50; }
        .modal.error { border-top: 5px solid #f44336; }
        .modal-body { font-size: 16px; color: #333; line-height: 1.5; margin-bottom: 20px; }
        .modal-close { padding: 10px 30px; background: #eee; border: none; border-radius: 6px; cursor: pointer; font-weight: bold; }
        .modal-close:hover { background: #ddd; }

        @keyframes popIn { from { transform: scale(0.8); opacity: 0; } to { transform: scale(1); opacity: 1; } }
        .user-info { background: #e8f5e9; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; }
        .logout-btn { background: #f44336; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; margin-top: 10px; }
        .logout-btn:hover { background: #d32f2f; }
    </style>
</head>
<body>

<div class="container">
    <!-- Карточка регистрации -->
    <div class="card">
        <h2>Регистрация</h2>
        <form action="register.php" method="POST">
            <input type="text" name="Login" placeholder="Логин" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="pass" placeholder="Пароль" required>
            <input type="password" name="repeatpass" placeholder="Повторите пароль" required>
            <button type="submit">Зарегистрироваться</button>
        </form>
        <a class="link-form" href="index.html">← На главную форму</a>
    </div>

    <!-- Карточка входа -->
    <div class="card <?= $isLoggedIn ? 'logged' : '' ?>">
        <h2><?= $isLoggedIn ? '✅ Вы авторизованы' : 'Вход' ?></h2>

        <?php if ($isLoggedIn): ?>
            <div class="user-info">
                <p>👤 <strong><?= htmlspecialchars($_SESSION['user_login']) ?></strong></p>
                <p style="font-size: 12px; color: #666;"><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></p>
                <form action="logout.php" method="POST">
                    <button type="submit" class="logout-btn">🚪 Выйти</button>
                </form>
            </div>
        <?php else: ?>
            <form action="login.php" method="POST">
                <input type="text" name="Login" placeholder="Логин" required>
                <input type="password" name="pass" placeholder="Пароль" required>
                <button type="submit">Войти</button>
            </form>
        <?php endif; ?>
    </div>
</div>

<!-- Вывод модальных окон -->
<?php if (!empty($flashes)): ?>
    <?php
    $modalId = 0;
    foreach ($flashes as $type => $messages):
        foreach ($messages as $message):
            $modalId++;
            $currentId = 'modal_' . $modalId;
    ?>
        <div class="modal-overlay" id="<?= $currentId ?>">
            <div class="modal <?= htmlspecialchars($type) ?>">
                <div class="modal-body"><?= $message ?></div>
                <button class="modal-close" onclick="document.getElementById('<?= $currentId ?>').style.display='none'">Закрыть</button>
            </div>
        </div>
    <?php endforeach; endforeach; ?>
<?php endif; ?>

</body>
</html>
