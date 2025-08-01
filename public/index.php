<?php
session_start();
require_once __DIR__ . '/../app/models/Database.php';

// Kiểm tra trạng thái user
if (isset($_SESSION['user'])) {
    $currentUser = $_SESSION['user'];
    if (is_array($currentUser)) {
        $currentUserId = $currentUser['id'] ?? null;
    } else {
        $currentUserId = $currentUser;
    }
    
    if ($currentUserId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT active FROM users WHERE id = ?");
        $stmt->execute([$currentUserId]);
        $active = $stmt->fetchColumn();
        
        if (($active ?? 1) == 0) {
            // User bị khóa, đăng xuất và redirect
            session_destroy();
            header('Location: login.php?error=account_locked');
            exit;
        }
    }
}

// Trang chủ cho thuê đĩa game
require_once __DIR__ . '/../app/views/user/game/index.php'; 