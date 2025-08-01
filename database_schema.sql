-- Database schema for HANADA.VN

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    img VARCHAR(255),
    type VARCHAR(50),
    stock INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    total DECIMAL(10,2) NOT NULL,
    fullname VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    email VARCHAR(100),
    city VARCHAR(100) NOT NULL,
    district VARCHAR(100) NOT NULL,
    ward VARCHAR(100) NOT NULL,
    address TEXT NOT NULL,
    note TEXT,
    payment_method ENUM('cod', 'momo', 'bank') DEFAULT 'cod',
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Reviews table for product ratings
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id INT NULL, -- Allow NULL for reviews without purchase
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    images TEXT, -- JSON array of image URLs
    is_verified BOOLEAN DEFAULT FALSE, -- Verify if user actually bought the product
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (user_id, product_id) -- Prevent duplicate reviews per user per product
);

-- Coupons table
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    max_discount_amount DECIMAL(10,2) DEFAULT NULL,
    min_order_amount DECIMAL(10,2) DEFAULT 0,
    max_uses INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert sample data
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@hanada.vn', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('user1', 'user1@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('user2', 'user2@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

INSERT INTO categories (name, description) VALUES
('Rings', 'Nhẫn cao cấp'),
('Necklaces', 'Dây chuyền độc đáo'),
('Bracelets', 'Vòng tay sang trọng'),
('Earrings', 'Khuyên tai tinh tế');

INSERT INTO products (name, description, price, img, type, stock) VALUES
('Chrome Hearts Ring', 'Nhẫn Chrome Hearts chính hãng', 15000000, 'products/ring1.jpg', 'ring', 5),
('Chrome Hearts Pendant', 'Dây chuyền Chrome Hearts độc đáo', 12000000, 'products/pendant1.jpg', 'daychuyen', 3),
('Chrome Hearts Bracelet', 'Vòng tay Chrome Hearts sang trọng', 8000000, 'products/bracelet1.jpg', 'vongtay', 4),
('Chrome Hearts Earrings', 'Khuyên tai Chrome Hearts tinh tế', 6000000, 'products/earrings1.jpg', 'bongtai', 6);

INSERT INTO coupons (code, discount_type, discount_value, max_discount_amount, min_order_amount, max_uses, start_date, end_date) VALUES
('WELCOME10', 'percentage', 10, 1000000, 500000, 100, '2024-01-01', '2024-12-31'),
('SAVE20', 'percentage', 20, 2000000, 1000000, 50, '2024-01-01', '2024-12-31'),
('FIXED500K', 'fixed', 500000, 500000, 2000000, 30, '2024-01-01', '2024-12-31'); 

ALTER TABLE reviews ADD COLUMN admin_reply TEXT NULL AFTER comment; 