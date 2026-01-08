-- Tạo database VNB Sports
CREATE DATABASE IF NOT EXISTS vnb_sports CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vnb_sports;

-- Bảng users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    google_id VARCHAR(100) DEFAULT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    address TEXT,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_google_id (google_id)
) ENGINE=InnoDB;

-- Bảng categories
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) NOT NULL UNIQUE,
    image VARCHAR(255),
    parent_id INT DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Bảng products
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(12,0) NOT NULL,
    sale_price DECIMAL(12,0) DEFAULT NULL,
    image VARCHAR(255),
    images TEXT,
    category_id INT,
    stock INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    featured TINYINT(1) DEFAULT 0,
    -- Thuộc tính chung
    brand VARCHAR(100) DEFAULT NULL,
    color VARCHAR(100) DEFAULT NULL,
    -- Thuộc tính vợt cầu lông
    weight VARCHAR(50) DEFAULT NULL,
    balance_point VARCHAR(50) DEFAULT NULL,
    shaft_hardness VARCHAR(50) DEFAULT NULL,
    player_level VARCHAR(50) DEFAULT NULL,
    -- Thuộc tính giày/áo/quần
    sizes VARCHAR(255) DEFAULT NULL,
    material VARCHAR(100) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Bảng orders
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    address TEXT NOT NULL,
    note TEXT,
    total DECIMAL(12,0) NOT NULL,
    shipping_fee DECIMAL(12,0) DEFAULT 0,
    status ENUM('pending', 'confirmed', 'shipping', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cod', 'bank') DEFAULT 'cod',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Bảng order_items
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT,
    product_name VARCHAR(255) NOT NULL,
    price DECIMAL(12,0) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Insert sample categories (Danh mục cha)
INSERT INTO categories (id, name, slug, image, parent_id) VALUES
(1, 'Cầu Lông', 'cau-long', 'cat-caulong.png', NULL),
(2, 'Bóng Đá', 'bong-da', 'cat-bongda.png', NULL),
(3, 'Tennis', 'tennis', 'cat-tennis.png', NULL),
(4, 'Phụ Kiện', 'phu-kien', 'cat-phukien.png', NULL);

-- Danh mục con: CẦU LÔNG (ID 6-11)
INSERT INTO categories (id, name, slug, image, parent_id) VALUES
(6, 'Vợt Cầu Lông', 'vot-cau-long', 'cat-vot.png', 1),
(7, 'Giày Cầu Lông', 'giay-cau-long', 'cat-giay.png', 1),
(8, 'Áo Cầu Lông', 'ao-cau-long', 'cat-ao.png', 1),
(9, 'Quần Cầu Lông', 'quan-cau-long', 'cat-quan.png', 1),
(10, 'Túi - Balo Cầu Lông', 'tui-balo-cau-long', 'cat-tui.png', 1),
(11, 'Phụ Kiện Cầu Lông', 'phu-kien-cau-long', 'cat-phukien-cl.png', 1);

-- Danh mục con: BÓNG ĐÁ (ID 12-16)
INSERT INTO categories (id, name, slug, image, parent_id) VALUES
(12, 'Giày Bóng Đá', 'giay-bong-da', 'cat-giay-bd.png', 2),
(13, 'Áo Bóng Đá', 'ao-bong-da', 'cat-ao-bd.png', 2),
(14, 'Quần Bóng Đá', 'quan-bong-da', 'cat-quan-bd.png', 2),
(15, 'Bóng Đá', 'bong-da-qua', 'cat-bong.png', 2),
(16, 'Phụ Kiện Bóng Đá', 'phu-kien-bong-da', 'cat-phukien-bd.png', 2);

-- Danh mục con: TENNIS (ID 17-22)
INSERT INTO categories (id, name, slug, image, parent_id) VALUES
(17, 'Vợt Tennis', 'vot-tennis', 'cat-vot-tennis.png', 3),
(18, 'Giày Tennis', 'giay-tennis', 'cat-giay-tennis.png', 3),
(19, 'Áo Tennis', 'ao-tennis', 'cat-ao-tennis.png', 3),
(20, 'Quần Tennis', 'quan-tennis', 'cat-quan-tennis.png', 3),
(21, 'Bóng Tennis', 'bong-tennis', 'cat-bong-tennis.png', 3),
(22, 'Phụ Kiện Tennis', 'phu-kien-tennis', 'cat-phukien-tennis.png', 3);

-- Danh mục con: PHỤ KIỆN (ID 23-26)
INSERT INTO categories (id, name, slug, image, parent_id) VALUES
(23, 'Túi & Balo', 'tui-balo', 'cat-tui-balo.png', 4),
(24, 'Vớ & Căng Cước', 'vo-cang-cuoc', 'cat-vo-cuoc.png', 4),
(25, 'Băng Bảo Vệ', 'bang-bao-ve', 'cat-bang-baove.png', 4),
(26, 'Phụ Kiện Khác', 'phu-kien-khac', 'cat-pk-khac.png', 4);

-- Insert sample products
INSERT INTO products (name, slug, price, sale_price, image, category_id, stock, featured, brand) VALUES
('Vợt Cầu Lông Yonex Astrox 99', 'vot-yonex-astrox-99', 3500000, 2990000, 'vot-astrox99.png', 6, 50, 1, 'Yonex'),
('Vợt Cầu Lông Victor Thruster K', 'vot-victor-thruster-k', 2800000, NULL, 'vot-victor.png', 6, 30, 1, 'Victor'),
('Giày Cầu Lông Yonex 65Z', 'giay-yonex-65z', 2500000, 2190000, 'giay-65z.png', 7, 40, 1, 'Yonex'),
('Áo Cầu Lông Yonex 2024', 'ao-yonex-2024', 450000, NULL, 'ao-yonex.png', 8, 100, 0, 'Yonex'),
('Giày Bóng Đá Nike Mercurial', 'giay-nike-mercurial', 3200000, 2800000, 'giay-mercurial.png', 12, 25, 1, 'Nike'),
('Vợt Tennis Wilson Pro Staff', 'vot-wilson-pro-staff', 4500000, NULL, 'vot-wilson.png', 17, 15, 1, 'Wilson');

-- Insert admin user (password: admin123)
INSERT INTO users (fullname, email, phone, password, role) VALUES
('Admin VNB', 'admin@vnbsports.com', '0977508430', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
