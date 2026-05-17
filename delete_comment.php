<?php
require_once 'config.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { die(json_encode(['success'=>false])); }

$id = (int)($_POST['id'] ?? 0);
$stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if ($c && $c['user_id'] == $_SESSION['user_id']) {
    $pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$id]);
    echo json_encode(['success'=>true]);
} else {
    echo json_encode(['success'=>false]);
}