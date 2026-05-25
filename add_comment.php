<?php
require_once 'config.php';

if (!isLoggedIn()) { 
    header('Location: index.php'); 
    exit; 
}

$content = trim($_POST['content'] ?? '');
$content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8'); 

if ($content !== '') {
    $stmt = $pdo->prepare("INSERT INTO comments (user_id, content) VALUES (?, ?)");
    $stmt->execute([$_SESSION['user_id'], $content]);
}

header('Location: index.php');
?>