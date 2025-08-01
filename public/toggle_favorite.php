<?php
session_start();
require_once '../app/models/Favorite.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Phương thức không hợp lệ']);
    exit;
}

$userId = $_SESSION['user'];
$productId = $_POST['product_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$productId || !$action) {
    echo json_encode(['success' => false, 'message' => 'Thiếu thông tin']);
    exit;
}

try {
    if ($action === 'add') {
        $result = Favorite::add($userId, $productId);
        echo json_encode(['success' => $result, 'message' => 'Đã thêm vào yêu thích']);
    } elseif ($action === 'remove') {
        $result = Favorite::remove($userId, $productId);
        echo json_encode(['success' => $result, 'message' => 'Đã xóa khỏi yêu thích']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
} 