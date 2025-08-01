-- Tạo bảng địa chỉ trước khi chạy fetch_address_data.php
-- Chạy script này trước khi chạy fetch_address_data.php

-- Xóa bảng theo thứ tự (từ con đến cha)
DROP TABLE IF EXISTS wards;
DROP TABLE IF EXISTS districts;
DROP TABLE IF EXISTS cities;

-- Tạo bảng cities (tỉnh/thành phố)
CREATE TABLE cities (
    id INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL
);

-- Tạo bảng districts (quận/huyện)
CREATE TABLE districts (
    id INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    city_id INT,
    FOREIGN KEY (city_id) REFERENCES cities(id)
);

-- Tạo bảng wards (phường/xã)
CREATE TABLE wards (
    id INT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    district_id INT,
    FOREIGN KEY (district_id) REFERENCES districts(id)
);

-- Thêm một số dữ liệu mẫu để test
INSERT INTO cities (id, name) VALUES 
(22, 'Tỉnh Khánh Hòa'),
(79, 'TP.HCM'),
(48, 'Tỉnh Quảng Nam');

INSERT INTO districts (id, name, city_id) VALUES 
(194, 'Huyện Vạn Ninh', 22),
(760, 'Quận 1', 79),
(773, 'Quận 4', 79);

INSERT INTO wards (id, name, district_id) VALUES 
(19401, 'Xã Vạn Ninh', 194),
(76001, 'Phường Bến Nghé', 760),
(77301, 'Phường 1', 773); 