-- =============================================
-- COMBO/BUNDLE VÀ ĐIỂM THƯỞNG LOYALTY
-- =============================================

-- Bảng combo sản phẩm
CREATE TABLE IF NOT EXISTS product_combos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    image VARCHAR(255),
    discount_percent DECIMAL(5,2) DEFAULT 10,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sản phẩm trong combo
CREATE TABLE IF NOT EXISTS combo_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    combo_id INT NOT NULL,
    product_id INT NOT NULL,
    FOREIGN KEY (combo_id) REFERENCES product_combos(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Thêm cột điểm thưởng cho users
ALTER TABLE users ADD COLUMN loyalty_points INT DEFAULT 0;

-- Lịch sử điểm thưởng
CREATE TABLE IF NOT EXISTS loyalty_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    points INT NOT NULL,
    type ENUM('earn', 'redeem') NOT NULL,
    description VARCHAR(255),
    order_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Voucher đổi điểm
CREATE TABLE IF NOT EXISTS loyalty_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    points_required INT NOT NULL,
    discount_amount DECIMAL(12,0) NOT NULL,
    min_order DECIMAL(12,0) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active'
) ENGINE=InnoDB;

-- Dữ liệu mẫu combo
INSERT INTO product_combos (name, description, discount_percent) VALUES
('Combo Vợt + Túi Pro', 'Mua vợt kèm túi đựng với giá ưu đãi', 15),
('Combo Starter', 'Bộ khởi đầu cho người mới chơi cầu lông', 20);

-- Dữ liệu mẫu voucher đổi điểm
INSERT INTO loyalty_rewards (name, points_required, discount_amount, min_order) VALUES
('Giảm 50K', 500, 50000, 300000),
('Giảm 100K', 900, 100000, 500000),
('Giảm 200K', 1600, 200000, 1000000),
('Giảm 500K', 3500, 500000, 2000000);
