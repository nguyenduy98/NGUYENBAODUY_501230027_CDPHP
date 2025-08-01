<?php
require_once __DIR__ . '/Database.php';

class Review {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Tạo đánh giá mới
     */
    public function create($userId, $productId, $rating, $comment = '', $images = []) {
        try {
            // Kiểm tra xem user đã đánh giá sản phẩm này chưa
            $stmt = $this->db->prepare("
                SELECT id FROM reviews 
                WHERE user_id = ? AND product_id = ?
            ");
            $stmt->execute([$userId, $productId]);
            
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này rồi'];
            }
            
            // Tạo đánh giá (không cần mua hàng)
            $stmt = $this->db->prepare("
                INSERT INTO reviews (user_id, product_id, order_id, rating, comment, images, is_verified, status)
                VALUES (?, ?, NULL, ?, ?, ?, FALSE, 'approved')
            ");
            
            $imagesJson = json_encode($images);
            $stmt->execute([$userId, $productId, $rating, $comment, $imagesJson]);
            
            return ['success' => true, 'message' => 'Đánh giá đã được gửi thành công'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
        }
    }
    
    /**
     * Lấy tất cả đánh giá của một sản phẩm
     */
    public function getByProduct($productId, $limit = 10, $offset = 0) {
        try {
            $sql = "
                SELECT r.*, u.username, u.email
                FROM reviews r
                LEFT JOIN users u ON r.user_id = u.id
                WHERE r.product_id = ? AND r.status = 'approved'
                ORDER BY r.created_at DESC
                LIMIT $limit OFFSET $offset
            ";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$productId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            // Ghi log lỗi ra file để debug
            file_put_contents(__DIR__.'/../../review_error.log', $e->getMessage()."\n", FILE_APPEND);
            return [];
        }
    }
    
    /**
     * Lấy thống kê đánh giá của sản phẩm
     */
    public function getStatsByProduct($productId) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_reviews,
                    AVG(rating) as average_rating,
                    COUNT(CASE WHEN rating = 5 THEN 1 END) as five_star,
                    COUNT(CASE WHEN rating = 4 THEN 1 END) as four_star,
                    COUNT(CASE WHEN rating = 3 THEN 1 END) as three_star,
                    COUNT(CASE WHEN rating = 2 THEN 1 END) as two_star,
                    COUNT(CASE WHEN rating = 1 THEN 1 END) as one_star
                FROM reviews 
                WHERE product_id = ? AND status = 'approved'
            ");
            $stmt->execute([$productId]);
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);

            // Đảm bảo luôn có đủ key
            $stats = array_merge([
                'total_reviews' => 0,
                'average_rating' => 0,
                'five_star' => 0, 'four_star' => 0, 'three_star' => 0, 'two_star' => 0, 'one_star' => 0,
            ], $stats ?: []);

            // Tính phần trăm
            $total = $stats['total_reviews'];
            $stats['five_star_percent'] = $total > 0 ? round(($stats['five_star'] / $total) * 100) : 0;
            $stats['four_star_percent'] = $total > 0 ? round(($stats['four_star'] / $total) * 100) : 0;
            $stats['three_star_percent'] = $total > 0 ? round(($stats['three_star'] / $total) * 100) : 0;
            $stats['two_star_percent'] = $total > 0 ? round(($stats['two_star'] / $total) * 100) : 0;
            $stats['one_star_percent'] = $total > 0 ? round(($stats['one_star'] / $total) * 100) : 0;

            return $stats;
        } catch (Exception $e) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0,
                'five_star' => 0, 'four_star' => 0, 'three_star' => 0, 'two_star' => 0, 'one_star' => 0,
                'five_star_percent' => 0, 'four_star_percent' => 0, 'three_star_percent' => 0, 'two_star_percent' => 0, 'one_star_percent' => 0
            ];
        }
    }
    
    /**
     * Lấy đánh giá của user cho một sản phẩm
     */
    public function getUserReview($userId, $productId) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM reviews 
                WHERE user_id = ? AND product_id = ?
            ");
            
            $stmt->execute([$userId, $productId]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Lấy danh sách đơn hàng đã hoàn thành của user để đánh giá
     */
    public function getCompletedOrdersForReview($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT o.*, p.name as product_name, p.img as product_img, p.price,
                       r.id as review_id, r.rating, r.comment, r.status as review_status
                FROM orders o
                JOIN products p ON o.product_id = p.id
                LEFT JOIN reviews r ON o.id = r.order_id AND o.product_id = r.product_id
                WHERE o.user_id = ? AND o.status = 'delivered'
                ORDER BY o.created_at DESC
            ");
            
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Cập nhật đánh giá
     */
    public function update($reviewId, $userId, $rating, $comment = '', $images = []) {
        try {
            // Kiểm tra quyền sở hữu
            $stmt = $this->db->prepare("
                SELECT id FROM reviews 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$reviewId, $userId]);
            
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'Không có quyền cập nhật đánh giá này'];
            }
            
            // Cập nhật
            $stmt = $this->db->prepare("
                UPDATE reviews 
                SET rating = ?, comment = ?, images = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            
            $imagesJson = json_encode($images);
            $stmt->execute([$rating, $comment, $imagesJson, $reviewId]);
            
            return ['success' => true, 'message' => 'Đánh giá đã được cập nhật'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
        }
    }
    
    /**
     * Xóa đánh giá
     */
    public function delete($reviewId, $userId) {
        try {
            // Kiểm tra quyền sở hữu
            $stmt = $this->db->prepare("
                SELECT id FROM reviews 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$reviewId, $userId]);
            
            if (!$stmt->fetch()) {
                return ['success' => false, 'message' => 'Không có quyền xóa đánh giá này'];
            }
            
            // Xóa
            $stmt = $this->db->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$reviewId]);
            
            return ['success' => true, 'message' => 'Đánh giá đã được xóa'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Có lỗi xảy ra: ' . $e->getMessage()];
        }
    }

    /**
     * Admin reply to a review
     */
    public function updateAdminReply($reviewId, $reply) {
        try {
            $stmt = $this->db->prepare("UPDATE reviews SET admin_reply = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$reply, $reviewId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Admin delete a review (không cần kiểm tra quyền sở hữu)
     */
    public function deleteReview($reviewId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM reviews WHERE id = ?");
            $stmt->execute([$reviewId]);
            return $stmt->rowCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAdminReply($reviewId) {
        try {
            $stmt = $this->db->prepare("SELECT admin_reply FROM reviews WHERE id = ?");
            $stmt->execute([$reviewId]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ? $row['admin_reply'] : null;
        } catch (Exception $e) {
            return null;
        }
    }
}
?> 