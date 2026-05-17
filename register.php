<?php
require_once 'config.php';
if (isLoggedIn()) { header('Location: index.php'); exit; }

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';

    if (!$username || !$password) {
        $error = 'Заполните все поля';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Логин 3–50 символов';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Логин: буквы, цифры, _';
    } elseif (strlen($password) < 4) {
        $error = 'Пароль не менее 4 символов';
    } elseif ($password !== $confirm) {
        $error = 'Пароли не совпадают';
    } else {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = 'Логин занят';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password_hash) VALUES (?, ?)");
            if ($stmt->execute([$username, $hash])) {
                $_SESSION['user_id'] = $pdo->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['role'] = 'user';
                header('Location: index.php');
                exit;
            } else {
                $error = 'Ошибка';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><title>Регистрация</title><link rel="stylesheet" href="/style.css">
</head>
<body>
<div class="form-wrap">
    <h2>Регистрация</h2>
    <?php if ($error): ?><div class="error"><?= h($error) ?></div><?php endif; ?>
    <form method="POST">
        <input type="text" name="username" placeholder="Логин" required>
        <input type="password" name="password" placeholder="Пароль" required>
        <input type="password" name="confirm" placeholder="Повторите пароль" required>
        <button type="submit" class="btn">Зарегистрироваться</button>
        <a href="login.php">Уже есть аккаунт?</a>
    </form>
</div>
</body>
</html>