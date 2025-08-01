<?php
require_once __DIR__ . '/Database.php';

class Coupon {
    public static function validate($code, $orderTotal = 0, $userId = null) {
        $db = Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1 AND start_date <= CURDATE() AND end_date >= CURDATE()");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn!'];
        }
        
        // Kiểm tra số lần sử dụng
        if ($coupon['max_uses'] && $coupon['used_count'] >= $coupon['max_uses']) {
            return ['valid' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng!'];
        }
        
        // Kiểm tra user đã sử dụng mã này chưa (áp dụng cho cả user và admin)
        if ($userId) {
            $stmt = $db->prepare("SELECT * FROM coupon_usage WHERE user_id = ? AND coupon_code = ?");
            $stmt->execute([$userId, $code]);
            $usage = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($usage) {
                return ['valid' => false, 'message' => 'Bạn đã sử dụng mã giảm giá này rồi!'];
            }
        }
        
        // Kiểm tra đơn hàng tối thiểu
        if ($orderTotal < $coupon['min_order_amount']) {
            return ['valid' => false, 'message' => 'Đơn hàng tối thiểu ' . number_format($coupon['min_order_amount']) . 'đ để sử dụng mã này!'];
        }
        
        return ['valid' => true, 'coupon' => $coupon];
    }
    
    public static function calculateDiscount($coupon, $orderTotal) {
        if ($coupon['discount_type'] === 'percentage') {
            $discount = ($orderTotal * $coupon['discount_value']) / 100;
            // Áp dụng giảm tối đa nếu có
            if ($coupon['max_discount_amount']) {
                $discount = min($discount, $coupon['max_discount_amount']);
            }
        } else {
            $discount = $coupon['discount_value'];
        }
        
        // Đảm bảo không giảm quá tổng đơn hàng
        return min($discount, $orderTotal);
    }
    
    public static function useCoupon($code, $userId = null, $orderId = null) {
        $db = Database::getInstance()->getConnection();
        
        // Debug: Log thông tin
        error_log("DEBUG: useCoupon called with code: $code, userId: $userId, orderId: $orderId");
        
        // Cập nhật số lần sử dụng
        $stmt = $db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE code = ?");
        $updateResult = $stmt->execute([$code]);
        error_log("DEBUG: Update coupons result: " . ($updateResult ? 'success' : 'failed'));
        
        // Lưu thông tin user đã sử dụng
        if ($userId) {
            try {
                $stmt = $db->prepare("INSERT INTO coupon_usage (user_id, coupon_code, order_id) VALUES (?, ?, ?)");
                $insertResult = $stmt->execute([$userId, $code, $orderId]);
                error_log("DEBUG: Insert coupon_usage result: " . ($insertResult ? 'success' : 'failed'));
            } catch (Exception $e) {
                error_log("DEBUG: Error inserting coupon_usage: " . $e->getMessage());
            }
        } else {
            error_log("DEBUG: userId is null, skipping coupon_usage insert");
        }
    }
    
    public static function getAll() {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->query("SELECT * FROM coupons ORDER BY created_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public static function getById($id) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Kiểm tra user đã sử dụng mã giảm giá nào
    public static function getUserUsedCoupons($userId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT coupon_code FROM coupon_usage WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
} 