<?php
/**
 * Class RecentlyViewed - Quản lý sản phẩm đã xem
 */
class RecentlyViewed {
    private $conn;
    private $maxItems = 20; // Số sản phẩm tối đa lưu trữ
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Ghi nhận sản phẩm đã xem
     */
    public function track($productId, $userId = null) {
        $sessionId = session_id();
        
        try {
            // Xóa record cũ nếu có (để cập nhật thời gian)
            if ($userId) {
                $stmt = $this->conn->prepare("DELETE FROM recently_viewed WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$userId, $productId]);
            } else {
                $stmt = $this->conn->prepare("DELETE FROM recently_viewed WHERE session_id = ? AND product_id = ? AND user_id IS NULL");
                $stmt->execute([$sessionId, $productId]);
            }
            
            // Thêm record mới
            $stmt = $this->conn->prepare("
                INSERT INTO recently_viewed (user_id, session_id, product_id)
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$userId, $sessionId, $productId]);
            
            // Giới hạn số lượng
            $this->cleanup($userId, $sessionId);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Xóa các record cũ vượt quá giới hạn
     */
    private function cleanup($userId, $sessionId) {
        if ($userId) {
            $stmt = $this->conn->prepare("
                DELETE FROM recently_viewed 
                WHERE user_id = ? AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM recently_viewed WHERE user_id = ? ORDER BY viewed_at DESC LIMIT ?
                    ) as tmp
                )
            ");
            $stmt->execute([$userId, $userId, $this->maxItems]);
        } else {
            $stmt = $this->conn->prepare("
                DELETE FROM recently_viewed 
                WHERE session_id = ? AND user_id IS NULL AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM recently_viewed WHERE session_id = ? AND user_id IS NULL ORDER BY viewed_at DESC LIMIT ?
                    ) as tmp
                )
            ");
            $stmt->execute([$sessionId, $sessionId, $this->maxItems]);
        }
    }
    
    /**
     * Lấy danh sách sản phẩm đã xem
     */
    public function get($userId = null, $limit = 10, $excludeProductId = null) {
        $sessionId = session_id();
        
        $sql = "
            SELECT DISTINCT p.*, c.name as category_name, rv.viewed_at
            FROM recently_viewed rv
            JOIN products p ON rv.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE p.status = 'active'
        ";
        
        $params = [];
        
        if ($userId) {
            $sql .= " AND (rv.user_id = ? OR (rv.session_id = ? AND rv.user_id IS NULL))";
            $params[] = $userId;
            $params[] = $sessionId;
        } else {
            $sql .= " AND rv.session_id = ? AND rv.user_id IS NULL";
            $params[] = $sessionId;
        }
        
        if ($excludeProductId) {
            $sql .= " AND p.id != ?";
            $params[] = $excludeProductId;
        }
        
        $sql .= " ORDER BY rv.viewed_at DESC LIMIT ?";
        $params[] = $limit;
        
        $stmt = $this->conn->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    /**
     * Merge session data vào user khi đăng nhập
     */
    public function mergeSessionToUser($userId) {
        $sessionId = session_id();
        
        try {
            // Lấy các sản phẩm đã xem trong session
            $stmt = $this->conn->prepare("
                SELECT product_id FROM recently_viewed 
                WHERE session_id = ? AND user_id IS NULL
            ");
            $stmt->execute([$sessionId]);
            $products = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            foreach ($products as $productId) {
                // Xóa record cũ của user nếu có
                $stmt = $this->conn->prepare("DELETE FROM recently_viewed WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$userId, $productId]);
            }
            
            // Cập nhật session records thành user records
            $stmt = $this->conn->prepare("
                UPDATE recently_viewed SET user_id = ? WHERE session_id = ? AND user_id IS NULL
            ");
            $stmt->execute([$userId, $sessionId]);
            
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Xóa lịch sử xem của user
     */
    public function clearHistory($userId = null) {
        if ($userId) {
            $stmt = $this->conn->prepare("DELETE FROM recently_viewed WHERE user_id = ?");
            $stmt->execute([$userId]);
        } else {
            $stmt = $this->conn->prepare("DELETE FROM recently_viewed WHERE session_id = ? AND user_id IS NULL");
            $stmt->execute([session_id()]);
        }
        return true;
    }
}
