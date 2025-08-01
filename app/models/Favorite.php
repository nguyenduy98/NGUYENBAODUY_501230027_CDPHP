<?php
require_once __DIR__ . '/Database.php';

class Favorite {
    private static $db;
    
    public static function init() {
        if (!self::$db) {
            self::$db = Database::getInstance()->getConnection();
        }
    }
    
    // Thêm sản phẩm vào yêu thích
    public static function add($userId, $productId) {
        self::init();
        try {
            $stmt = self::$db->prepare("INSERT INTO favorites (user_id, product_id) VALUES (?, ?)");
            return $stmt->execute([$userId, $productId]);
        } catch (PDOException $e) {
            // Nếu đã tồn tại, trả về true
            if ($e->getCode() == 23000) {
                return true;
            }
            return false;
        }
    }
    
    // Xóa sản phẩm khỏi yêu thích
    public static function remove($userId, $productId) {
        self::init();
        $stmt = self::$db->prepare("DELETE FROM favorites WHERE user_id = ? AND product_id = ?");
        return $stmt->execute([$userId, $productId]);
    }
    
    // Kiểm tra sản phẩm có trong yêu thích không
    public static function isFavorite($userId, $productId) {
        self::init();
        $stmt = self::$db->prepare("SELECT id FROM favorites WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        return $stmt->fetch() !== false;
    }
    
    // Lấy danh sách sản phẩm yêu thích của user
    public static function getUserFavorites($userId, $limit = null, $offset = null) {
        self::init();
        $sql = "SELECT p.*, f.created_at as favorited_at 
                FROM favorites f 
                LEFT JOIN products p ON f.product_id = p.id 
                WHERE f.user_id = ? 
                ORDER BY f.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }
        
        $stmt = self::$db->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Đếm số sản phẩm yêu thích của user
    public static function countUserFavorites($userId) {
        self::init();
        $stmt = self::$db->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
} 