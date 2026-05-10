<?php
require_once 'config.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$commentsPerPage = 20;
$offset = ($page - 1) * $commentsPerPage;

$totalComments = $pdo->query("SELECT COUNT(*) FROM comments")->fetchColumn();
$totalPages = ceil($totalComments / $commentsPerPage);

$stmt = $pdo->prepare("
    SELECT c.*, u.username, u.role 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    ORDER BY c.created_at DESC 
    LIMIT :offset, :limit
");
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->bindValue(':limit', $commentsPerPage, PDO::PARAM_INT);
$stmt->execute();
$comments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Гостевая книга</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Гостевая книга</h1>
            
            <?php if (isLoggedIn()): ?>
                <div class="user-info">
                    Привет, <?= h($_SESSION['username']) ?> 
                    (<?= h($_SESSION['role']) ?>)
                </div>
                <div class="buttons">
                    <a href="logout.php" class="btn btn-red">Выйти</a>
                </div>
            <?php else: ?>
                <div class="buttons">
                    <a href="login.php" class="btn btn-blue"> Войти</a>
                    <a href="register.php" class="btn btn-green">Регистрация</a>
                </div>
            <?php endif; ?>
        </div>

        <div class="comments-area">
            <?php if (isset($_GET['error'])): ?>
                <div class="error">
                    <?php
                    $errors = [
                        'empty' => 'Комментарий не может быть пустым',
                        'not_auth' => 'Нужно авторизоваться',
                        'security' => 'Ошибка безопасности'
                    ];
                    echo $errors[$_GET['error']] ?? 'Неизвестная ошибка';
                    ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success'])): ?>
                <div class="success"><?= $_GET['success'] === 'comment_added' ? 'Комментарий добавлен' : 'Комментарий обновлён' ?></div>
            <?php endif; ?>

            <?php if (isLoggedIn()): ?>
                <div class="comment-form">
                    <h3>Написать комментарий</h3>
                    <form method="POST" action="add_comment.php">
                        <textarea name="content" placeholder="Ваш комментарий..." required></textarea>
                        <button type="submit" class="btn btn-green">Отправить</button>
                    </form>
                </div>
            <?php endif; ?>

            <h3>Комментарии (<?= $totalComments ?>)</h3>
            
            <?php if (empty($comments)): ?>
                <p style="text-align:center; color:#999; padding: 30px;">Пока нет комментариев. Будьте первым!</p>
            <?php else: ?>
                <?php foreach ($comments as $comment): ?>
                    <div class="comment" id="comment-<?= $comment['id'] ?>">
                        <div class="comment-header">
                            <div class="comment-author">
                                <?= h($comment['username']) ?>
                                <?php if ($comment['role'] === 'admin'): ?>
                                    <span class="admin-badge">Админ</span>
                                <?php endif; ?>
                            </div>
                            <div class="comment-date">
                                <?= date('d.m.Y H:i', strtotime($comment['created_at'])) ?>
                                <?php if ($comment['created_at'] != $comment['updated_at']): ?>
                                    (изменён)
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="comment-content" id="content-<?= $comment['id'] ?>">
                            <?= nl2br(h($comment['content'])) ?>
                        </div>
                        <div class="comment-actions">
                            <?php if (isLoggedIn() && $_SESSION['user_id'] == $comment['user_id']): ?>
                                <button onclick="editComment(<?= $comment['id'] ?>, '<?= addslashes(h($comment['content'])) ?>')" class="btn btn-orange" style="padding: 5px 12px;">Редактировать</button>
                                <button onclick="deleteComment(<?= $comment['id'] ?>)" class="btn btn-red" style="padding: 5px 12px;">Удалить</button>
                            <?php endif; ?>
                            <?php if (isAdmin() && $_SESSION['user_id'] != $comment['user_id']): ?>
                                <button onclick="adminDelete(<?= $comment['id'] ?>)" class="btn btn-red" style="padding: 5px 12px;">Удалить (админ)</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
                
                <?= pagination($page, $totalPages) ?>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function editComment(id, currentContent) {
        let newContent = prompt('Редактирование комментария:', currentContent);
        if (newContent && newContent !== currentContent) {
            fetch('edit_comment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + id + '&content=' + encodeURIComponent(newContent)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('content-' + id).innerHTML = newContent.replace(/\n/g, '<br>');
                    alert('Комментарий изменён');
                    location.reload();
                } else {
                    alert('Ошибка: ' + data.error);
                }
            });
        }
    }
    
    function deleteComment(id) {
        if (confirm('Удалить свой комментарий?')) {
            fetch('delete_comment.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('comment-' + id).remove();
                    alert('Комментарий удалён');
                } else {
                    alert('Ошибка: ' + data.error);
                }
            });
        }
    }
    
    function adminDelete(id) {
        if (confirm('Администратор: удалить этот комментарий?')) {
            fetch('admin_delete.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'id=' + id
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('comment-' + id).remove();
                    alert('Комментарий удалён администратором');
                } else {
                    alert('Ошибка: ' + data.error);
                }
            });
        }
    }
    </script>
</body>
</html>