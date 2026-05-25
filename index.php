<?php
require_once 'config.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

$total = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$pages = ceil($total / $limit);

$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.role
    FROM comments c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
    LIMIT :offset, :limit
");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Гостевая книга</title>
    <link rel="stylesheet" href="http://guestbook/style.css">
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Гостевая книга</h1>
        <?php if (isLoggedIn()): ?>
            <div class="user-info">Привет, <?= h($_SESSION['username']) ?> (<?= h($_SESSION['role']) ?>)</div>
            <a class="btn btn-outline" href="logout.php">Выйти</a>
        <?php else: ?>
            <a class="btn" href="login.php">Вход</a>
            <a class="btn" href="register.php">Регистрация</a>
        <?php endif; ?>
    </div>

    <div class="content">
        <?php if (isset($_GET['msg'])): ?>
            <div class="msg"><?= h($_GET['msg']) ?></div>
        <?php endif; ?>

        <?php if (isLoggedIn()): ?>
            <div class="form-box">
                <form method="POST" action="add_comment.php">
                    <textarea name="content" placeholder="Ваш комментарий..." required></textarea>
                    <button type="submit" class="btn">Отправить</button>
                </form>
            </div>
        <?php endif; ?>

        <h3>Комментарии (<?= $total ?>)</h3>

        <?php if (!$comments): ?>
            <p class="empty">Нет комментариев. Напишите первый!</p>
        <?php else: ?>
            <?php foreach ($comments as $c): ?>
                <div class="comment" id="c<?= $c['id'] ?>">
                    <div class="comment-header">
                        <strong><?= h($c['username']) ?></strong>
                        <?php if ($c['role'] === 'admin'): ?>
                            <span class="badge">админ</span>
                        <?php endif; ?>
                        <span class="date"><?= date('d.m.Y H:i', strtotime($c['created_at'])) ?></span>
                    </div>
                    <div class="comment-text" id="text<?= $c['id'] ?>">
                        <?= nl2br(h($c['content'])) ?>
                    </div>
                    <div class="comment-actions">
                        <?php if (isLoggedIn() && $_SESSION['user_id'] == $c['user_id']): ?>
                            <button class="edit-btn" data-id="<?= $c['id'] ?>" data-text="<?= h($c['content']) ?>">Ред.</button>
                            <button class="del-btn" data-id="<?= $c['id'] ?>">Удалить</button>
                        <?php endif; ?>
                        <?php if (isAdmin() && $_SESSION['user_id'] != $c['user_id']): ?>
                            <button class="admin-del-btn" data-id="<?= $c['id'] ?>">удалить</button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?= pagination($page, $pages) ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function escapeHtml(text) {
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    document.querySelectorAll('.edit-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            let id = btn.dataset.id;
            let old = btn.dataset.text;
            let newText = prompt('Редактировать комментарий:', old);
            if (newText && newText !== old) {
                let res = await fetch('edit_comment.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'id=' + id + '&content=' + encodeURIComponent(newText)
                });
                let data = await res.json();
                if (data.success) {
                    let escapedText = escapeHtml(newText);
                    document.getElementById('text' + id).innerHTML = escapedText.replace(/\n/g, '<br>');
                    alert('Обновлено');
                } else {
                    alert('Ошибка: ' + data.error);
                }
            }
        });
    });

    document.querySelectorAll('.del-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Удалить свой комментарий?')) return;
            let id = btn.dataset.id;
            let res = await fetch('delete_comment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + id
            });
            let data = await res.json();
            if (data.success) {
                document.getElementById('c' + id).remove();
                alert('Удалено');
            } else {
                alert('Ошибка');
            }
        });
    });

    document.querySelectorAll('.admin-del-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            if (!confirm('Удалить чужой комментарий (админ)?')) return;
            let id = btn.dataset.id;
            let res = await fetch('admin_delete.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + id
            });
            let data = await res.json();
            if (data.success) {
                document.getElementById('c' + id).remove();
                alert('Удалено админом');
            } else {
                alert('Ошибка');
            }
        });
    });
</script>
</body>
</html>