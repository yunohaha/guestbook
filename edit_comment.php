<?php
require_once 'config.php';
header('Content-Type: application/json');

if (!isLoggedIn()) { 
    die(json_encode(['success' => false, 'error' => 'no auth'])); 
}

$id = (int)($_POST['id'] ?? 0);
$content = trim($_POST['content'] ?? '');
$content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); 

if (!$content) { 
    die(json_encode(['success' => false, 'error' => 'empty'])); 
}

$stmt = $pdo->prepare("SELECT user_id FROM comments WHERE id = ?");
$stmt->execute([$id]);
$c = $stmt->fetch();

if (!$c || $c['user_id'] != $_SESSION['user_id']) { 
    die(json_encode(['success' => false, 'error' => 'no rights'])); 
}

$stmt = $pdo->prepare("UPDATE comments SET content = ?, updated_at = NOW() WHERE id = ?");
$stmt->execute([$content, $id]);

echo json_encode(['success' => true]);
?>