<?php
/**
 * Class ProductCompare - Quản lý so sánh sản phẩm
 */
class ProductCompare {
    private $conn;
    private $maxProducts = 4; // Tối đa 4 sản phẩm so sánh
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Lấy session ID
     */
    private function getSessionId() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return session_id();
    }
    
    /**
     * Thêm sản phẩm vào danh sách so sánh
     */
    public function add($productId, $userId = null) {
        $sessionId = $this->getSessionId();
        
        // Kiểm tra số lượng hiện tại
        $count = $this->getCount($userId);
        if ($count >= $this->maxProducts) {
            return ['success' => false, 'message' => "Chỉ được so sánh tối đa {$this->maxProducts} sản phẩm"];
        }
        
        // Kiểm tra sản phẩm tồn tại
        $stmt = $this->conn->prepare("SELECT id, category_id FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            return ['success' => false, 'message' => 'Sản phẩm không tồn tại'];
        }
        
        // Kiểm tra cùng danh mục (chỉ so sánh sản phẩm cùng loại)
        $existing = $this->getProducts($userId);
        if (!empty($existing)) {
            $firstCategoryId = $existing[0]['category_id'];
            if ($product['category_id'] != $firstCategoryId) {
                return ['success' => false, 'message' => 'Chỉ có thể so sánh sản phẩm cùng danh mục'];
            }
        }
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO product_compare (session_id, user_id, product_id) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$sessionId, $userId, $productId]);
            
            return ['success' => true, 'message' => 'Đã thêm vào danh sách so sánh', 'count' => $this->getCount($userId)];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
        }
    }
    
    /**
     * Xóa sản phẩm khỏi danh sách so sánh
     */
    public function remove($productId, $userId = null) {
        $sessionId = $this->getSessionId();
        
        $stmt = $this->conn->prepare("
            DELETE FROM product_compare 
            WHERE session_id = ? AND product_id = ?
        ");
        $stmt->execute([$sessionId, $productId]);
        
        return ['success' => true, 'count' => $this->getCount($userId)];
    }
    
    /**
     * Xóa tất cả
     */
    public function clear($userId = null) {
        $sessionId = $this->getSessionId();
        
        $stmt = $this->conn->prepare("DELETE FROM product_compare WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        
        return ['success' => true];
    }
    
    /**
     * Lấy số lượng sản phẩm đang so sánh
     */
    public function getCount($userId = null) {
        $sessionId = $this->getSessionId();
        
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM product_compare WHERE session_id = ?
        ");
        $stmt->execute([$sessionId]);
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Lấy danh sách sản phẩm đang so sánh
     */
    public function getProducts($userId = null) {
        $sessionId = $this->getSessionId();
        
        $stmt = $this->conn->prepare("
            SELECT p.*, c.name as category_name,
                   pc.created_at as added_at
            FROM product_compare pc
            JOIN products p ON pc.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE pc.session_id = ?
            ORDER BY pc.created_at ASC
        ");
        $stmt->execute([$sessionId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Kiểm tra sản phẩm có trong danh sách so sánh không
     */
    public function isInCompare($productId, $userId = null) {
        $sessionId = $this->getSessionId();
        
        $stmt = $this->conn->prepare("
            SELECT 1 FROM product_compare 
            WHERE session_id = ? AND product_id = ?
        ");
        $stmt->execute([$sessionId, $productId]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Lấy IDs sản phẩm đang so sánh
     */
    public function getProductIds($userId = null) {
        $sessionId = $this->getSessionId();
        
        $stmt = $this->conn->prepare("
            SELECT product_id FROM product_compare WHERE session_id = ?
        ");
        $stmt->execute([$sessionId]);
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
