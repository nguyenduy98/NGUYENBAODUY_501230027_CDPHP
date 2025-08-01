-- Tạo bảng theo dõi sử dụng mã giảm giá
CREATE TABLE IF NOT EXISTS `coupon_usage` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `coupon_code` varchar(50) NOT NULL,
  `used_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `order_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_coupon_unique` (`user_id`, `coupon_code`),
  KEY `user_id` (`user_id`),
  KEY `coupon_code` (`coupon_code`),
  CONSTRAINT `coupon_usage_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `coupon_usage_ibfk_2` FOREIGN KEY (`coupon_code`) REFERENCES `coupons` (`code`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Thêm một số dữ liệu mẫu (nếu cần)
-- INSERT INTO `coupon_usage` (`user_id`, `coupon_code`, `order_id`) VALUES 
-- (1, 'AAWW12', 1),
-- (2, 'AAWW12', 2); 