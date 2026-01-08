<?php
/**
 * Class Coupon - Quản lý mã giảm giá
 */
class Coupon {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Kiểm tra và lấy thông tin coupon
     */
    public function validate($code, $userId = null, $orderTotal = 0) {
        $code = strtoupper(trim($code));
        
        // Lấy thông tin coupon - sử dụng NOW() của MySQL để đảm bảo timezone nhất quán
        $stmt = $this->conn->prepare("
            SELECT *, 
                   NOW() as db_now,
                   (start_date IS NULL OR start_date <= NOW()) as is_started,
                   (end_date IS NULL OR end_date >= NOW()) as is_not_expired
            FROM coupons 
            WHERE code = ? AND status = 'active'
        ");
        $stmt->execute([$code]);
        $coupon = $stmt->fetch();
        
        if (!$coupon) {
            return ['valid' => false, 'message' => 'Mã giảm giá không tồn tại hoặc đã hết hạn'];
        }
        
        // Kiểm tra thời gian - sử dụng kết quả từ MySQL
        if (!$coupon['is_started']) {
            return ['valid' => false, 'message' => 'Mã giảm giá chưa có hiệu lực'];
        }
        if (!$coupon['is_not_expired']) {
            return ['valid' => false, 'message' => 'Mã giảm giá đã hết hạn'];
        }
        
        // Kiểm tra giới hạn sử dụng tổng
        if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
            return ['valid' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng'];
        }
        
        // Kiểm tra giá trị đơn hàng tối thiểu
        if ($orderTotal > 0 && $orderTotal < $coupon['min_order_value']) {
            return [
                'valid' => false, 
                'message' => 'Đơn hàng tối thiểu ' . number_format($coupon['min_order_value']) . 'đ để sử dụng mã này'
            ];
        }
        
        // Kiểm tra giới hạn sử dụng của user
        if ($userId && $coupon['user_limit']) {
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM coupon_usage WHERE coupon_id = ? AND user_id = ?");
            $stmt->execute([$coupon['id'], $userId]);
            $userUsed = $stmt->fetchColumn();
            
            if ($userUsed >= $coupon['user_limit']) {
                return ['valid' => false, 'message' => 'Bạn đã sử dụng mã này rồi'];
            }
        }
        
        // Tính số tiền giảm
        $discount = $this->calculateDiscount($coupon, $orderTotal);
        
        return [
            'valid' => true,
            'coupon' => $coupon,
            'discount' => $discount,
            'message' => 'Áp dụng mã giảm giá thành công!'
        ];
    }
    
    /**
     * Tính số tiền giảm
     */
    public function calculateDiscount($coupon, $orderTotal) {
        if ($coupon['discount_type'] === 'percent') {
            $discount = $orderTotal * ($coupon['discount_value'] / 100);
            // Áp dụng giới hạn giảm tối đa
            if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
                $discount = $coupon['max_discount'];
            }
        } else {
            $discount = $coupon['discount_value'];
        }
        
        // Không giảm quá giá trị đơn hàng
        return min($discount, $orderTotal);
    }
    
    /**
     * Sử dụng coupon (ghi nhận sau khi đặt hàng thành công)
     */
    public function use($couponId, $userId, $orderId, $discountAmount) {
        try {
            $this->conn->beginTransaction();
            
            // Ghi nhận sử dụng
            $stmt = $this->conn->prepare("
                INSERT INTO coupon_usage (coupon_id, user_id, order_id, discount_amount)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$couponId, $userId, $orderId, $discountAmount]);
            
            // Tăng số lần sử dụng
            $stmt = $this->conn->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
            $stmt->execute([$couponId]);
            
            $this->conn->commit();
            return true;
        } catch (PDOException $e) {
            $this->conn->rollBack();
            return false;
        }
    }
    
    /**
     * Lấy coupon theo code
     */
    public function getByCode($code) {
        $stmt = $this->conn->prepare("SELECT * FROM coupons WHERE code = ?");
        $stmt->execute([strtoupper(trim($code))]);
        return $stmt->fetch();
    }
    
    /**
     * Lấy danh sách coupon đang hoạt động
     */
    public function getActiveCoupons($limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT * FROM coupons 
            WHERE status = 'active' 
            AND (start_date IS NULL OR start_date <= NOW())
            AND (end_date IS NULL OR end_date >= NOW())
            AND (usage_limit IS NULL OR used_count < usage_limit)
            ORDER BY created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Lấy coupon có thể dùng cho user
     */
    public function getAvailableForUser($userId, $orderTotal = 0) {
        $coupons = $this->getActiveCoupons(20);
        $available = [];
        
        foreach ($coupons as $coupon) {
            $result = $this->validate($coupon['code'], $userId, $orderTotal);
            if ($result['valid']) {
                $coupon['discount_preview'] = $result['discount'];
                $available[] = $coupon;
            }
        }
        
        return $available;
    }
    
    /**
     * Lấy voucher cá nhân của user (đổi từ điểm thưởng)
     */
    public function getUserCoupons($userId) {
        $stmt = $this->conn->prepare("
            SELECT c.*, 
                   (c.end_date IS NOT NULL AND c.end_date < NOW()) as is_expired,
                   (c.used_count >= c.usage_limit) as is_used
            FROM coupons c
            WHERE c.user_id = ?
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Lấy voucher cá nhân còn hiệu lực của user
     */
    public function getActiveUserCoupons($userId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM coupons 
            WHERE user_id = ?
            AND status = 'active'
            AND (end_date IS NULL OR end_date >= NOW())
            AND (usage_limit IS NULL OR used_count < usage_limit)
            ORDER BY end_date ASC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
}
