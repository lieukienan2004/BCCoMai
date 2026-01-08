<?php
class Loyalty {
    private $conn;
    
    // 1000đ = 1 điểm
    const POINTS_PER_VND = 1000;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Lấy điểm của user
    public function getPoints($userId) {
        $stmt = $this->conn->prepare("SELECT loyalty_points FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        return $user ? (int)$user['loyalty_points'] : 0;
    }
    
    // Tính điểm từ đơn hàng
    public function calculatePoints($orderTotal) {
        return floor($orderTotal / self::POINTS_PER_VND);
    }
    
    // Cộng điểm khi hoàn thành đơn hàng
    public function earnPoints($userId, $orderId, $orderTotal) {
        $points = $this->calculatePoints($orderTotal);
        if ($points <= 0) return 0;
        
        // Cập nhật điểm user
        $stmt = $this->conn->prepare("UPDATE users SET loyalty_points = loyalty_points + ? WHERE id = ?");
        $stmt->execute([$points, $userId]);
        
        // Ghi lịch sử
        $stmt = $this->conn->prepare("INSERT INTO loyalty_history (user_id, points, type, description, order_id) VALUES (?, ?, 'earn', ?, ?)");
        $stmt->execute([$userId, $points, "Tích điểm từ đơn hàng #$orderId", $orderId]);
        
        return $points;
    }
    
    // Đổi điểm lấy voucher
    public function redeemReward($userId, $rewardId) {
        $reward = $this->getRewardById($rewardId);
        if (!$reward) {
            return ['success' => false, 'message' => 'Phần thưởng không tồn tại (ID: ' . $rewardId . ')'];
        }
        
        $currentPoints = $this->getPoints($userId);
        if ($currentPoints < $reward['points_required']) {
            return ['success' => false, 'message' => 'Không đủ điểm (Có: ' . $currentPoints . ', Cần: ' . $reward['points_required'] . ')'];
        }
        
        // Trừ điểm
        $stmt = $this->conn->prepare("UPDATE users SET loyalty_points = loyalty_points - ? WHERE id = ?");
        $stmt->execute([$reward['points_required'], $userId]);
        
        // Tạo coupon cho user (lưu user_id để biết voucher của ai)
        $code = 'LOYALTY' . strtoupper(substr(md5(uniqid()), 0, 8));
        $expiry = date('Y-m-d', strtotime('+30 days'));
        
        $stmt = $this->conn->prepare("
            INSERT INTO coupons (user_id, code, name, discount_type, discount_value, min_order_value, usage_limit, used_count, end_date, status) 
            VALUES (?, ?, ?, 'fixed', ?, ?, 1, 0, ?, 'active')
        ");
        $stmt->execute([$userId, $code, $reward['name'], $reward['discount_amount'], $reward['min_order'], $expiry]);
        
        // Ghi lịch sử
        $stmt = $this->conn->prepare("INSERT INTO loyalty_history (user_id, points, type, description) VALUES (?, ?, 'redeem', ?)");
        $stmt->execute([$userId, -$reward['points_required'], "Đổi: {$reward['name']} - Mã: $code"]);
        
        return [
            'success' => true, 
            'message' => 'Đổi điểm thành công!',
            'coupon_code' => $code,
            'discount' => $reward['discount_amount'],
            'expiry' => $expiry
        ];
    }
    
    // Lấy lịch sử điểm
    public function getHistory($userId, $limit = 20) {
        $stmt = $this->conn->prepare("SELECT * FROM loyalty_history WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    // Lấy danh sách phần thưởng
    public function getRewards() {
        $stmt = $this->conn->query("SELECT * FROM loyalty_rewards WHERE status = 'active' ORDER BY points_required ASC");
        return $stmt->fetchAll();
    }
    
    // Lấy phần thưởng theo ID
    public function getRewardById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM loyalty_rewards WHERE id = ? AND status = 'active'");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Lấy thứ hạng thành viên dựa trên tổng điểm đã tích
    public function getMemberTier($userId) {
        $stmt = $this->conn->prepare("SELECT SUM(points) as total FROM loyalty_history WHERE user_id = ? AND type = 'earn'");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        $totalEarned = $result ? (int)$result['total'] : 0;
        
        if ($totalEarned >= 10000) return ['name' => 'Kim Cương', 'icon' => '💎', 'bonus' => 1.5];
        if ($totalEarned >= 5000) return ['name' => 'Vàng', 'icon' => '🥇', 'bonus' => 1.3];
        if ($totalEarned >= 2000) return ['name' => 'Bạc', 'icon' => '🥈', 'bonus' => 1.2];
        if ($totalEarned >= 500) return ['name' => 'Đồng', 'icon' => '🥉', 'bonus' => 1.1];
        return ['name' => 'Thành viên', 'icon' => '⭐', 'bonus' => 1.0];
    }
}
