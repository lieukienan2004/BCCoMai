-- Cập nhật thông số kỹ thuật cho vợt cầu lông
-- Các cột đã có sẵn, chỉ cần chạy UPDATE

-- Vợt Yonex Astrox 99
UPDATE products SET 
    weight = '83g (4U)',
    balance_point = '305mm (Nặng đầu)',
    shaft_hardness = 'Cứng',
    player_level = 'Nâng cao',
    material = 'HM Graphite + Tungsten'
WHERE slug = 'vot-yonex-astrox-99';

-- Vợt Victor Thruster K
UPDATE products SET 
    weight = '85g (4U)',
    balance_point = '295mm (Cân bằng)',
    shaft_hardness = 'Trung bình',
    player_level = 'Trung cấp - Nâng cao',
    material = 'High Resilience Modulus Graphite'
WHERE slug = 'vot-victor-thruster-k';

-- Nếu bạn có thêm vợt khác, thêm UPDATE tương tự ở đây
-- Ví dụ các giá trị phổ biến:

-- TRỌNG LƯỢNG (weight):
-- 2U: 90-94g (Nặng, lực đánh mạnh)
-- 3U: 85-89g (Trung bình)
-- 4U: 80-84g (Nhẹ, linh hoạt)
-- 5U: 75-79g (Siêu nhẹ)
-- F: 73-75g (Cực nhẹ)

-- ĐIỂM CÂN BẰNG (balance_point):
-- < 285mm: Nhẹ đầu (Head Light) - Phòng thủ, phản tay nhanh
-- 285-295mm: Cân bằng (Even Balance) - Đa năng
-- > 295mm: Nặng đầu (Head Heavy) - Tấn công, smash mạnh

-- ĐỘ CỨNG ĐŨA (shaft_hardness):
-- Mềm (Flexible): Dễ đánh, phù hợp người mới
-- Trung bình (Medium): Đa năng
-- Cứng (Stiff): Kiểm soát tốt, cần kỹ thuật
-- Siêu cứng (Extra Stiff): Chuyên nghiệp

-- TRÌNH ĐỘ (player_level):
-- Người mới bắt đầu
-- Trung cấp
-- Nâng cao
-- Chuyên nghiệp
