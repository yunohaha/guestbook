<?php
require_once 'config.php';
header('Content-Type: application/json');
if (!isAdmin()) { die(json_encode(['success'=>false])); }

$id = (int)($_POST['id'] ?? 0);
$pdo->prepare("DELETE FROM comments WHERE id = ?")->execute([$id]);
echo json_encode(['success'=>true]);