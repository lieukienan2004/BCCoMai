-- =====================================================
-- Bảng lưu log thanh toán từ SePay
-- =====================================================

-- Thêm cột payment_status vào bảng orders nếu chưa có
ALTER TABLE orders ADD COLUMN IF NOT EXISTS payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending' AFTER status;

-- Tạo bảng payment_logs
CREATE TABLE IF NOT EXISTS payment_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    amount DECIMAL(12,0) NOT NULL,
    transaction_id VARCHAR(100),
    content VARCHAR(500),
    status ENUM('pending', 'success', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_order_id (order_id),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
) ENGINE=InnoDB;
