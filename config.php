<?php
session_start();

$host = 'localhost';
$dbname = 'guestbook_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Ошибка подкл: " . $e->getMessage());
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function h($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

function pagination($currentPage, $totalPages) {
    if ($totalPages <= 1) return '';
    
    $html = '<div class="pagination">';
    
    if ($currentPage > 1) {
        $html .= '<a href="?page=' . ($currentPage - 1) . '">Назад</a>';
    }
    
    for ($i = 1; $i <= $totalPages; $i++) {
        if ($i == $currentPage) {
            $html .= '<span class="current">' . $i . '</span>';
        } else {
            $html .= '<a href="?page=' . $i . '">' . $i . '</a>';
        }
    }
    
    if ($currentPage < $totalPages) {
        $html .= '<a href="?page=' . ($currentPage + 1) . '">Вперёд</a>';
    }
    $html .= '</div>';
    return $html;
}
?>