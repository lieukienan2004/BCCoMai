-- Tạo bảng và thêm dữ liệu (bỏ qua nếu đã tồn tại)
-- Chạy file này nếu đã có sẵn bảng products

USE vnb_sports;

-- Tạo bảng categories nếu chưa có
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

-- Danh mục cha (ID 1-4)
INSERT IGNORE INTO categories (id, name, slug, image, parent_id) VALUES
(1, 'Cầu Lông', 'cau-long', 'cat-caulong.png', NULL),
(2, 'Bóng Đá', 'bong-da', 'cat-bongda.png', NULL),
(3, 'Tennis', 'tennis', 'cat-tennis.png', NULL),
(4, 'Phụ Kiện', 'phu-kien', 'cat-phukien.png', NULL);

-- Danh mục con: CẦU LÔNG (ID 6-11)
INSERT IGNORE INTO categories (id, name, slug, image, parent_id) VALUES
(6, 'Vợt Cầu Lông', 'vot-cau-long', 'cat-vot.png', 1),
(7, 'Giày Cầu Lông', 'giay-cau-long', 'cat-giay.png', 1),
(8, 'Áo Cầu Lông', 'ao-cau-long', 'cat-ao.png', 1),
(9, 'Quần Cầu Lông', 'quan-cau-long', 'cat-quan.png', 1),
(10, 'Túi - Balo Cầu Lông', 'tui-balo-cau-long', 'cat-tui.png', 1),
(11, 'Phụ Kiện Cầu Lông', 'phu-kien-cau-long', 'cat-phukien-cl.png', 1);

-- Danh mục con: BÓNG ĐÁ (ID 12-16)
INSERT IGNORE INTO categories (id, name, slug, image, parent_id) VALUES
(12, 'Giày Bóng Đá', 'giay-bong-da', 'cat-giay-bd.png', 2),
(13, 'Áo Bóng Đá', 'ao-bong-da', 'cat-ao-bd.png', 2),
(14, 'Quần Bóng Đá', 'quan-bong-da', 'cat-quan-bd.png', 2),
(15, 'Bóng Đá', 'bong-da-qua', 'cat-bong.png', 2),
(16, 'Phụ Kiện Bóng Đá', 'phu-kien-bong-da', 'cat-phukien-bd.png', 2);

-- Danh mục con: TENNIS (ID 17-22)
INSERT IGNORE INTO categories (id, name, slug, image, parent_id) VALUES
(17, 'Vợt Tennis', 'vot-tennis', 'cat-vot-tennis.png', 3),
(18, 'Giày Tennis', 'giay-tennis', 'cat-giay-tennis.png', 3),
(19, 'Áo Tennis', 'ao-tennis', 'cat-ao-tennis.png', 3),
(20, 'Quần Tennis', 'quan-tennis', 'cat-quan-tennis.png', 3),
(21, 'Bóng Tennis', 'bong-tennis', 'cat-bong-tennis.png', 3),
(22, 'Phụ Kiện Tennis', 'phu-kien-tennis', 'cat-phukien-tennis.png', 3);



-- Danh mục con: PHỤ KIỆN (ID 23-26)
INSERT IGNORE INTO categories (id, name, slug, image, parent_id) VALUES
(23, 'Túi & Balo', 'tui-balo', 'cat-tui-balo.png', 4),
(24, 'Vớ & Căng Cước', 'vo-cang-cuoc', 'cat-vo-cuoc.png', 4),
(25, 'Băng Bảo Vệ', 'bang-bao-ve', 'cat-bang-baove.png', 4),
(26, 'Phụ Kiện Khác', 'phu-kien-khac', 'cat-pk-khac.png', 4);

-- Bảng users (đăng ký, đăng nhập)
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

-- Bảng chat_ai (lưu lịch sử chat với AI)
CREATE TABLE IF NOT EXISTS chat_ai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT NOT NULL,
    response TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Insert admin user (password: admin123)
INSERT IGNORE INTO users (fullname, email, phone, password, role) VALUES
('Admin VNB', 'admin@vnbsports.com', '0977508430', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
