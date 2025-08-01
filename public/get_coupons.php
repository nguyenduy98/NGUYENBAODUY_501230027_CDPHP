<?php
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/Coupon.php';

header('Content-Type: application/json');

try {
    $db = Database::getInstance()->getConnection();
    
    // Lấy user_id từ session (nếu user đã đăng nhập)
    session_start();
    $userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : (is_array($_SESSION['user']) ? $_SESSION['user']['id'] : $_SESSION['user']);
    
    // Debug: Log user ID
    error_log("DEBUG: get_coupons.php - User ID: " . ($userId ? $userId : 'null'));
    
    // Lấy danh sách mã giảm giá mà user đã sử dụng
    $usedCoupons = [];
    if ($userId) {
        $usedCoupons = Coupon::getUserUsedCoupons($userId);
        error_log("DEBUG: get_coupons.php - Used coupons: " . implode(', ', $usedCoupons));
    }
    
    // Lấy tất cả mã giảm giá còn hiệu lực (chỉ lấy các cột có sẵn)
    $stmt = $db->prepare("
        SELECT code, discount_type, discount_value, min_order_amount, max_discount_amount, 
               start_date, end_date, used_count
        FROM coupons 
        WHERE (end_date IS NULL OR end_date >= CURDATE())
        ORDER BY discount_value DESC
    ");
    $stmt->execute();
    $allCoupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lọc bỏ các mã giảm giá mà user đã sử dụng
    $availableCoupons = [];
    foreach ($allCoupons as $coupon) {
        if (!in_array($coupon['code'], $usedCoupons)) {
            $availableCoupons[] = $coupon;
        }
    }
    
    // Debug: Log số lượng mã giảm giá
    error_log("Found " . count($availableCoupons) . " available coupons for user " . $userId);
    
    echo json_encode([
        'success' => true,
        'coupons' => $availableCoupons,
        'count' => count($availableCoupons),
        'used_coupons' => $usedCoupons
    ]);
    
} catch (Exception $e) {
    error_log("Error in get_coupons.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi khi lấy danh sách mã giảm giá: ' . $e->getMessage()
    ]);
}
?> 