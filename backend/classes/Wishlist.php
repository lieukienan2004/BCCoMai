<?php
/**
 * Class Wishlist - Quản lý danh sách yêu thích
 */
class Wishlist {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Thêm sản phẩm vào wishlist
     */
    public function add($userId, $productId) {
        try {
            $stmt = $this->conn->prepare("INSERT IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$userId, $productId]);
            return ['success' => true, 'message' => 'Đã thêm vào danh sách yêu thích'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Có lỗi xảy ra'];
        }
    }
    
    /**
     * Xóa sản phẩm khỏi wishlist
     */
    public function remove($userId, $productId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            return ['success' => true, 'message' => 'Đã xóa khỏi danh sách yêu thích'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Có lỗi xảy ra'];
        }
    }
    
    /**
     * Toggle wishlist (thêm nếu chưa có, xóa nếu đã có)
     */
    public function toggle($userId, $productId) {
        if ($this->isInWishlist($userId, $productId)) {
            return $this->remove($userId, $productId);
        }
        return $this->add($userId, $productId);
    }
    
    /**
     * Kiểm tra sản phẩm có trong wishlist không
     */
    public function isInWishlist($userId, $productId) {
        $stmt = $this->conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Lấy danh sách wishlist của user
     */
    public function getByUser($userId, $limit = 20, $offset = 0) {
        $stmt = $this->conn->prepare("
            SELECT w.*, p.name, p.slug, p.price, p.sale_price, p.image, p.stock,
                   c.name as category_name
            FROM wishlist w
            JOIN products p ON w.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE w.user_id = ? AND p.status = 'active'
            ORDER BY w.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$userId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Đếm số sản phẩm trong wishlist
     */
    public function countByUser($userId) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Lấy danh sách product_id trong wishlist (để check nhanh)
     */
    public function getProductIds($userId) {
        $stmt = $this->conn->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
