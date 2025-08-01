<?php
session_start();
require_once __DIR__ . '/../app/models/Database.php';

header('Content-Type: application/json');

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $db = Database::getInstance()->getConnection();
    
    // Tìm kiếm sản phẩm theo tên (không phân biệt hoa thường)
    $stmt = $db->prepare("
        SELECT id, name, price, img, stock 
        FROM products 
        WHERE LOWER(name) LIKE LOWER(?) 
        AND stock > 0
        ORDER BY name ASC 
        LIMIT 10
    ");
    
    $searchTerm = '%' . $query . '%';
    $stmt->execute([$searchTerm]);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Format giá tiền
    foreach ($products as &$product) {
        $product['price'] = (int)preg_replace('/[^0-9]/', '', $product['price']);
    }
    
    echo json_encode($products);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 