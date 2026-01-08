-- VNB Sports Database
CREATE DATABASE IF NOT EXISTS vnb_sports CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE vnb_sports;

-- Users Table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(15),
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT NULL,
    address TEXT,
    role ENUM('user', 'admin') DEFAULT 'user',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Categories Table
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

-- Products Table
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
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Orders Table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    fullname VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    address TEXT NOT NULL,
    note TEXT,
    total DECIMAL(12,0) NOT NULL,
    status ENUM('pending', 'confirmed', 'shipping', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cod', 'bank', 'momo') DEFAULT 'cod',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Order Items Table
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

-- Insert sample categories
INSERT INTO categories (name, slug, image) VALUES
('Vợt Cầu Lông', 'vot-cau-long', 'cat-vot.png'),
('Giày Cầu Lông', 'giay-cau-long', 'cat-giay.png'),
('Áo Cầu Lông', 'ao-cau-long', 'cat-ao.png'),
('Quần Cầu Lông', 'quan-cau-long', 'cat-quan.png'),
('Váy Cầu Lông', 'vay-cau-long', 'cat-vay.png'),
('Túi Vợt', 'tui-vot', 'cat-tui.png'),
('Balo Cầu Lông', 'balo-cau-long', 'cat-balo.png'),
('Phụ Kiện', 'phu-kien', 'cat-phukien.png');

-- Insert sample products
INSERT INTO products (name, slug, price, sale_price, image, category_id, stock, featured) VALUES
('Vợt Cầu Lông VNB V200 Xanh Chính Hãng', 'vot-vnb-v200-xanh', 529000, NULL, 'vot-v200-xanh.png', 1, 50, 1),
('Vợt Cầu Lông VNB Carbon Training 150g', 'vot-vnb-carbon-training', 850000, 698000, 'vot-carbon.png', 1, 30, 1),
('Vợt Cầu Lông VNB V200i Hồng Chính Hãng', 'vot-vnb-v200i-hong', 529000, NULL, 'vot-v200i-hong.png', 1, 40, 1),
('Vợt Cầu Lông VNB V88 Xanh Chính Hãng', 'vot-vnb-v88-xanh', 638000, NULL, 'vot-v88-xanh.png', 1, 35, 1),
('Vợt Cầu Lông VNB V200 Đỏ Chính Hãng', 'vot-vnb-v200-do', 529000, NULL, 'vot-v200-do.png', 1, 45, 1);

-- Insert admin user (password: admin123)
INSERT INTO users (fullname, email, phone, password, role) VALUES
('Admin', 'admin@vnbsports.com', '0977508430', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
