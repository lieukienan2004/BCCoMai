-- =====================================================
-- VNB Sports - Tính năng mới
-- =====================================================

USE vnb_sports;

-- =====================================================
-- 1. BẢNG SO SÁNH SẢN PHẨM (Product Compare)
-- =====================================================
CREATE TABLE IF NOT EXISTS product_compare (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(100) NOT NULL,
    user_id INT DEFAULT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_compare (session_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_session (session_id)
) ENGINE=InnoDB;

-- =====================================================
-- 2. BẢNG THÔNG BÁO KHI CÓ HÀNG (Stock Notifications)
-- =====================================================
CREATE TABLE IF NOT EXISTS stock_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    email VARCHAR(100) NOT NULL,
    user_id INT DEFAULT NULL,
    status ENUM('pending', 'sent', 'cancelled') DEFAULT 'pending',
    notified_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_notification (product_id, email),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_product_status (product_id, status)
) ENGINE=InnoDB;

-- =====================================================
-- 3. BẢNG FLASH SALE
-- =====================================================
CREATE TABLE IF NOT EXISTS flash_sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    start_time DATETIME NOT NULL,
    end_time DATETIME NOT NULL,
    status ENUM('upcoming', 'active', 'ended') DEFAULT 'upcoming',
    banner_image VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_time (status, start_time, end_time)
) ENGINE=InnoDB;

-- Bảng sản phẩm trong Flash Sale
CREATE TABLE IF NOT EXISTS flash_sale_products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    flash_sale_id INT NOT NULL,
    product_id INT NOT NULL,
    flash_price DECIMAL(12,0) NOT NULL,
    flash_stock INT NOT NULL DEFAULT 0,
    sold_count INT DEFAULT 0,
    UNIQUE KEY unique_flash_product (flash_sale_id, product_id),
    FOREIGN KEY (flash_sale_id) REFERENCES flash_sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 4. BẢNG NEWSLETTER (Đăng ký nhận tin)
-- =====================================================
CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(100) DEFAULT NULL,
    status ENUM('active', 'unsubscribed') DEFAULT 'active',
    subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    unsubscribed_at DATETIME DEFAULT NULL,
    INDEX idx_status (status)
) ENGINE=InnoDB;

-- =====================================================
-- 5. THÊM CỘT CHO PRODUCTS (Lọc nâng cao)
-- =====================================================
-- Bỏ qua nếu đã tồn tại
-- ALTER TABLE products
-- ADD COLUMN IF NOT EXISTS min_price DECIMAL(12,0) GENERATED ALWAYS AS (COALESCE(sale_price, price)) STORED,
-- ADD INDEX idx_min_price (min_price),
-- ADD INDEX idx_brand (brand),
-- ADD INDEX idx_featured (featured);

-- =====================================================
-- 6. INSERT MẪU FLASH SALE
-- =====================================================
INSERT INTO flash_sales (name, description, start_time, end_time, status) VALUES
('Flash Sale Cuối Tuần', 'Giảm giá sốc cuối tuần - Số lượng có hạn!', 
 DATE_ADD(CURDATE(), INTERVAL 0 DAY), 
 DATE_ADD(CURDATE(), INTERVAL 3 DAY), 
 'active');

-- Thêm sản phẩm vào flash sale (lấy 3 sản phẩm đầu)
INSERT INTO flash_sale_products (flash_sale_id, product_id, flash_price, flash_stock)
SELECT 1, id, ROUND(price * 0.7), 10 FROM products WHERE status = 'active' LIMIT 3
ON DUPLICATE KEY UPDATE flash_price = VALUES(flash_price);

SELECT 'Đã tạo các bảng mới cho tính năng: So sánh, Thông báo hàng, Flash Sale, Newsletter' AS Result;
