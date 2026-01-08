<?php
/**
 * Class StockNotification - Thông báo khi có hàng
 */
class StockNotification {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Đăng ký nhận thông báo khi có hàng
     */
    public function subscribe($productId, $email, $userId = null) {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email không hợp lệ'];
        }
        
        // Kiểm tra sản phẩm
        $stmt = $this->conn->prepare("SELECT id, name, status FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
        }
        
        if ($product['status'] == 'active') {
            return ['success' => false, 'message' => 'Sản phẩm hiện đang còn hàng'];
        }
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO stock_notifications (product_id, email, user_id) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                    status = 'pending',
                    user_id = COALESCE(VALUES(user_id), user_id),
                    created_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$productId, $email, $userId]);
            
            return [
                'success' => true, 
                'message' => 'Đăng ký thành công! Chúng tôi sẽ thông báo khi sản phẩm có hàng.'
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
    
    /**
     * Hủy đăng ký
     */
    public function unsubscribe($productId, $email) {
        $stmt = $this->conn->prepare("
            UPDATE stock_notifications 
            SET status = 'cancelled' 
            WHERE product_id = ? AND email = ?
        ");
        $stmt->execute([$productId, $email]);
        
        return ['success' => true, 'message' => 'Đã hủy đăng ký thông báo'];
    }
    
    /**
     * Kiểm tra đã đăng ký chưa
     */
    public function isSubscribed($productId, $email) {
        $stmt = $this->conn->prepare("
            SELECT 1 FROM stock_notifications 
            WHERE product_id = ? AND email = ? AND status = 'pending'
        ");
        $stmt->execute([$productId, $email]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Lấy danh sách người đăng ký cho sản phẩm
     */
    public function getSubscribers($productId) {
        $stmt = $this->conn->prepare("
            SELECT * FROM stock_notifications 
            WHERE product_id = ? AND status = 'pending'
        ");
        $stmt->execute([$productId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Gửi thông báo khi có hàng (gọi khi admin cập nhật stock)
     */
    public function notifySubscribers($productId) {
        $subscribers = $this->getSubscribers($productId);
        
        if (empty($subscribers)) {
            return ['success' => true, 'sent' => 0];
        }
        
        // Lấy thông tin sản phẩm
        $stmt = $this->conn->prepare("SELECT name, image FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        $sent = 0;
        $mailer = new Mailer();
        
        foreach ($subscribers as $sub) {
            $subject = "Sản phẩm {$product['name']} đã có hàng trở lại!";
            $body = $this->getEmailTemplate($product, $productId);
            
            if ($mailer->send($sub['email'], $subject, $body)) {
                // Cập nhật trạng thái
                $updateStmt = $this->conn->prepare("
                    UPDATE stock_notifications 
                    SET status = 'sent', notified_at = NOW() 
                    WHERE id = ?
                ");
                $updateStmt->execute([$sub['id']]);
                $sent++;
            }
        }
        
        return ['success' => true, 'sent' => $sent, 'total' => count($subscribers)];
    }
    
    /**
     * Template email thông báo
     */
    private function getEmailTemplate($product, $productId) {
        $productUrl = BASE_URL . "/product-detail.php?id=" . $productId;
        
        return "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
            <div style='background: linear-gradient(135deg, #ff8c00, #ff6600); padding: 20px; text-align: center;'>
                <h1 style='color: white; margin: 0;'>VNB Sports</h1>
            </div>
            <div style='padding: 30px; background: #f9f9f9;'>
                <h2 style='color: #333;'>Tin vui! Sản phẩm bạn quan tâm đã có hàng</h2>
                <p style='color: #666;'>Sản phẩm <strong>{$product['name']}</strong> mà bạn đăng ký theo dõi đã có hàng trở lại.</p>
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='{$productUrl}' style='background: #ff6600; color: white; padding: 15px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                        Xem sản phẩm ngay
                    </a>
                </div>
                <p style='color: #999; font-size: 12px;'>Nhanh tay đặt hàng trước khi hết!</p>
            </div>
        </div>
        ";
    }
    
    /**
     * Đếm số người đăng ký cho sản phẩm
     */
    public function getSubscriberCount($productId) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM stock_notifications 
            WHERE product_id = ? AND status = 'pending'
        ");
        $stmt->execute([$productId]);
        
        return (int)$stmt->fetchColumn();
    }
}
