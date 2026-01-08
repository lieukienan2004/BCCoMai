-- =====================================================
-- Thêm cột user_id vào bảng coupons để lưu voucher cá nhân
-- =====================================================

-- Thêm cột user_id (NULL = coupon công khai, có giá trị = coupon cá nhân)
ALTER TABLE coupons ADD COLUMN user_id INT DEFAULT NULL AFTER id;

-- Thêm index cho user_id
ALTER TABLE coupons ADD INDEX idx_user_id (user_id);

-- Thêm foreign key
ALTER TABLE coupons ADD CONSTRAINT fk_coupon_user 
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;
