-- =====================================================
-- VNB Sports - Thêm các tính năng mới
-- Chạy file này để thêm các bảng cần thiết
-- =====================================================

USE vnb_sports;

-- =====================================================
-- 1. BẢNG WISHLIST (Danh sách yêu thích)
-- =====================================================
CREATE TABLE IF NOT EXISTS wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_wishlist (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================================================
-- 2. BẢNG REVIEWS (Đánh giá sản phẩm)
-- =====================================================
CREATE TABLE IF NOT EXISTS reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    order_id INT DEFAULT NULL,
    rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(255) DEFAULT NULL,
    comment TEXT,
    images TEXT DEFAULT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'approved',
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    INDEX idx_product_rating (product_id, rating),
    INDEX idx_user_product (user_id, product_id)
) ENGINE=InnoDB;

-- =====================================================
-- 3. BẢNG COUPONS (Mã giảm giá)
-- =====================================================
CREATE TABLE IF NOT EXISTS coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    discount_type ENUM('percent', 'fixed') NOT NULL DEFAULT 'percent',
    discount_value DECIMAL(12,0) NOT NULL,
    min_order_value DECIMAL(12,0) DEFAULT 0,
    max_discount DECIMAL(12,0) DEFAULT NULL,
    usage_limit INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    user_limit INT DEFAULT 1,
    start_date DATETIME DEFAULT NULL,
    end_date DATETIME DEFAULT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (code),
    INDEX idx_status_dates (status, start_date, end_date)
) ENGINE=InnoDB;

-- Bảng theo dõi sử dụng coupon của user
CREATE TABLE IF NOT EXISTS coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    discount_amount DECIMAL(12,0) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_coupon_user (coupon_id, user_id)
) ENGINE=InnoDB;

-- =====================================================
-- 4. BẢNG RECENTLY VIEWED (Sản phẩm đã xem)
-- =====================================================
CREATE TABLE IF NOT EXISTS recently_viewed (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    session_id VARCHAR(100) DEFAULT NULL,
    product_id INT NOT NULL,
    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_view (user_id, session_id, product_id),
    INDEX idx_user_viewed (user_id, viewed_at),
    INDEX idx_session_viewed (session_id, viewed_at)
) ENGINE=InnoDB;

-- =====================================================
-- 5. CẬP NHẬT BẢNG ORDERS (Thêm coupon)
-- =====================================================
ALTER TABLE orders 
ADD COLUMN IF NOT EXISTS coupon_id INT DEFAULT NULL,
ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(12,0) DEFAULT 0;

-- =====================================================
-- 6. INSERT MẪU COUPONS
-- =====================================================
INSERT INTO coupons (code, name, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, start_date, end_date) VALUES
('WELCOME10', 'Chào mừng thành viên mới', 'Giảm 10% cho đơn hàng đầu tiên', 'percent', 10, 200000, 100000, NULL, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR)),
('FREESHIP', 'Miễn phí vận chuyển', 'Giảm 30.000đ phí ship', 'fixed', 30000, 300000, NULL, 100, NOW(), DATE_ADD(NOW(), INTERVAL 3 MONTH)),
('SALE20', 'Giảm 20% đơn hàng', 'Giảm 20% tối đa 200.000đ', 'percent', 20, 500000, 200000, 50, NOW(), DATE_ADD(NOW(), INTERVAL 1 MONTH))
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- =====================================================
-- HOÀN TẤT
-- =====================================================
SELECT 'Đã thêm các bảng: wishlist, reviews, coupons, coupon_usage, recently_viewed' AS Result;


-- =====================================================
-- 7. BẢNG PASSWORD RESETS (Đặt lại mật khẩu)
-- =====================================================
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    token VARCHAR(100) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_token (token)
) ENGINE=InnoDB;
