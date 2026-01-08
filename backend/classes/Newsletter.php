<?php
/**
 * Class Newsletter - Quản lý đăng ký nhận tin
 */
class Newsletter {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Đăng ký nhận tin
     */
    public function subscribe($email, $name = null) {
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Email không hợp lệ'];
        }
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO newsletter_subscribers (email, name) 
                VALUES (?, ?)
                ON DUPLICATE KEY UPDATE 
                    status = 'active',
                    name = COALESCE(VALUES(name), name),
                    unsubscribed_at = NULL
            ");
            $stmt->execute([$email, $name]);
            
            return [
                'success' => true, 
                'message' => 'Đăng ký thành công! Cảm ơn bạn đã quan tâm.'
            ];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
    
    /**
     * Hủy đăng ký
     */
    public function unsubscribe($email) {
        $stmt = $this->conn->prepare("
            UPDATE newsletter_subscribers 
            SET status = 'unsubscribed', unsubscribed_at = NOW() 
            WHERE email = ?
        ");
        $stmt->execute([$email]);
        
        return ['success' => true, 'message' => 'Đã hủy đăng ký nhận tin'];
    }
    
    /**
     * Kiểm tra đã đăng ký chưa
     */
    public function isSubscribed($email) {
        $stmt = $this->conn->prepare("
            SELECT 1 FROM newsletter_subscribers 
            WHERE email = ? AND status = 'active'
        ");
        $stmt->execute([$email]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Lấy tất cả subscribers (Admin)
     */
    public function getAll($status = null) {
        $sql = "SELECT * FROM newsletter_subscribers";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY subscribed_at DESC";
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Đếm số subscribers
     */
    public function getCount($status = 'active') {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM newsletter_subscribers WHERE status = ?
        ");
        $stmt->execute([$status]);
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Xóa subscriber (Admin)
     */
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
