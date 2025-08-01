<?php
session_start();
require_once __DIR__ . '/../app/models/Database.php';

// Kiểm tra đăng nhập và quyền admin
if (!isset($_SESSION['user'])) {
    header('Location: admin_login.php');
    exit;
}

// Kiểm tra quyền admin từ database
$currentUser = $_SESSION['user'];
if (is_array($currentUser)) {
    $currentUserId = $currentUser['id'] ?? null;
} else {
    $currentUserId = $currentUser;
}

if (!$currentUserId) {
    header('Location: admin_login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
$stmt->execute([$currentUserId]);
$userRole = $stmt->fetchColumn();

if ($userRole !== 'admin') {
    header('Location: admin_login.php');
    exit;
}

$page = $_GET['page'] ?? '';
switch ($page) {
    case 'products':
        require_once '../app/views/admin/product/index.php';
        break;
    case 'users':
        require_once __DIR__ . '/../app/views/admin/users/index.php';
        break;
    case 'categories':
        require_once __DIR__ . '/../app/views/admin/categories/index.php';
        break;
    case 'orders':
        require_once __DIR__ . '/../app/views/admin/orders/index.php';
        break;
    case 'order_details':
        require_once __DIR__ . '/../app/views/admin/orders/details.php';
        break;
    case 'reviews':
        require_once __DIR__ . '/../app/views/admin/reviews.php';
        break;
    case 'coupons':
        require_once __DIR__ . '/../app/views/admin/coupons/index.php';
        break;
    default:
        require_once __DIR__ . '/../app/views/admin/dashboard/index.php';
} 