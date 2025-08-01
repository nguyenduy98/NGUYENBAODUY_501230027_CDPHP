<?php
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/Coupon.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    $total = $_POST['total'] ?? 0;
    
    if (empty($code)) {
        echo json_encode([
            'valid' => false,
            'message' => 'Vui lòng nhập mã giảm giá'
        ]);
        exit;
    }
    
    try {
        // Lấy user_id từ session
        session_start();
        $userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;
        
        $result = Coupon::validate($code, $total, $userId);
        
        if ($result['valid']) {
            $coupon = $result['coupon'];
            $discount = Coupon::calculateDiscount($coupon, $total);
            $finalTotal = $total - $discount;
            
            echo json_encode([
                'valid' => true,
                'discount' => number_format($discount),
                'finalTotal' => number_format($finalTotal),
                'maxDiscount' => $coupon['max_discount_amount'] ? number_format($coupon['max_discount_amount']) : null,
                'message' => 'Mã giảm giá hợp lệ!'
            ]);
        } else {
            echo json_encode([
                'valid' => false,
                'message' => $result['message']
            ]);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'valid' => false,
            'message' => 'Lỗi khi kiểm tra mã giảm giá'
        ]);
    }
} else {
    echo json_encode([
        'valid' => false,
        'message' => 'Phương thức không được hỗ trợ'
    ]);
}
?> 