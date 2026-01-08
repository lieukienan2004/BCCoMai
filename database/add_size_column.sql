-- Thêm cột size vào bảng order_items để lưu size sản phẩm đã chọn
-- Chạy file này để cập nhật database

ALTER TABLE order_items ADD COLUMN IF NOT EXISTS size VARCHAR(50) DEFAULT NULL AFTER quantity;

-- Cập nhật sản phẩm mẫu với sizes cho giày và quần áo
-- Giày cầu lông
UPDATE products SET sizes = '38,39,40,41,42,43,44' WHERE category_id IN (6, 7) OR slug LIKE '%giay%';

-- Áo cầu lông  
UPDATE products SET sizes = 'S,M,L,XL,XXL' WHERE category_id IN (8) OR slug LIKE '%ao%';

-- Quần cầu lông
UPDATE products SET sizes = 'S,M,L,XL,XXL' WHERE category_id IN (9) OR slug LIKE '%quan%';

-- Giày bóng đá
UPDATE products SET sizes = '38,39,40,41,42,43,44,45' WHERE category_id = 12;

-- Áo bóng đá
UPDATE products SET sizes = 'S,M,L,XL,XXL' WHERE category_id = 13;

-- Quần bóng đá
UPDATE products SET sizes = 'S,M,L,XL,XXL' WHERE category_id = 14;

-- Giày tennis
UPDATE products SET sizes = '38,39,40,41,42,43,44' WHERE category_id = 18;

-- Áo tennis
UPDATE products SET sizes = 'S,M,L,XL,XXL' WHERE category_id = 19;

-- Quần tennis
UPDATE products SET sizes = 'S,M,L,XL,XXL' WHERE category_id = 20;
