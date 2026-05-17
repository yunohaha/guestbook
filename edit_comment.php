<?php
require_once 'config.php';
header('Content-Type: application/json');
if (!isLoggedIn()) { die(json_encode(['success'=>false,'error'=>'no auth'])); }

$id = (int)($_POST['id'] ?? 0);
$content = trim($_POST['content'] ?? '');
if (!$content) { die(json_encode(['success'=>false,'error'=>'empty'])); }

$stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();
if (!$c || $c['user_id'] != $_SESSION['user_id']) { die(json_encode(['success'=>false,'error'=>'no rights'])); }

$content = strip_tags($content);
$pdo->prepare("UPDATE comments SET content = ?, updated_at = NOW() WHERE id = ?")->execute([$content, $id]);
echo json_encode(['success'=>true]);