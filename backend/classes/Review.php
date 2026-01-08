<?php
/**
 * Class Review - Quản lý đánh giá sản phẩm
 */
class Review {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Thêm đánh giá mới
     */
    public function add($userId, $productId, $rating, $title = '', $comment = '', $orderId = null) {
        // Kiểm tra đã đánh giá chưa
        if ($this->hasReviewed($userId, $productId)) {
            return ['success' => false, 'message' => 'Bạn đã đánh giá sản phẩm này rồi'];
        }
        
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO reviews (user_id, product_id, order_id, rating, title, comment)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $productId, $orderId, $rating, $title, $comment]);
            return ['success' => true, 'message' => 'Cảm ơn bạn đã đánh giá!', 'id' => $this->conn->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Có lỗi xảy ra'];
        }
    }
    
    /**
     * Cập nhật đánh giá
     */
    public function update($reviewId, $userId, $rating, $title, $comment) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE reviews SET rating = ?, title = ?, comment = ?, updated_at = NOW()
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$rating, $title, $comment, $reviewId, $userId]);
            return ['success' => true, 'message' => 'Đã cập nhật đánh giá'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Có lỗi xảy ra'];
        }
    }
    
    /**
     * Xóa đánh giá
     */
    public function delete($reviewId, $userId) {
        try {
            $stmt = $this->conn->prepare("DELETE FROM reviews WHERE id = ? AND user_id = ?");
            $stmt->execute([$reviewId, $userId]);
            return ['success' => true, 'message' => 'Đã xóa đánh giá'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Có lỗi xảy ra'];
        }
    }
    
    /**
     * Kiểm tra user đã đánh giá sản phẩm chưa
     */
    public function hasReviewed($userId, $productId) {
        $stmt = $this->conn->prepare("SELECT id FROM reviews WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Kiểm tra user đã mua sản phẩm chưa (để cho phép đánh giá)
     */
    public function hasPurchased($userId, $productId) {
        $stmt = $this->conn->prepare("
            SELECT o.id FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'
            LIMIT 1
        ");
        $stmt->execute([$userId, $productId]);
        return $stmt->fetch() !== false;
    }
    
    /**
     * Lấy đánh giá của sản phẩm
     */
    public function getByProduct($productId, $limit = 10, $offset = 0, $status = 'approved') {
        $stmt = $this->conn->prepare("
            SELECT r.*, u.fullname, u.avatar
            FROM reviews r
            JOIN users u ON r.user_id = u.id
            WHERE r.product_id = ? AND r.status = ?
            ORDER BY r.created_at DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$productId, $status, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    /**
     * Lấy thống kê đánh giá của sản phẩm
     */
    public function getProductStats($productId) {
        $stmt = $this->conn->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                ROUND(AVG(rating), 1) as avg_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as star_5,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as star_4,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as star_3,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as star_2,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as star_1
            FROM reviews
            WHERE product_id = ? AND status = 'approved'
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch();
    }
    
    /**
     * Đếm số đánh giá của sản phẩm
     */
    public function countByProduct($productId, $status = 'approved') {
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM reviews WHERE product_id = ? AND status = ?");
        $stmt->execute([$productId, $status]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Lấy đánh giá của user
     */
    public function getByUser($userId, $limit = 20) {
        $stmt = $this->conn->prepare("
            SELECT r.*, p.name as product_name, p.image as product_image, p.slug as product_slug
            FROM reviews r
            JOIN products p ON r.product_id = p.id
            WHERE r.user_id = ?
            ORDER BY r.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Tăng helpful count
     */
    public function markHelpful($reviewId) {
        $stmt = $this->conn->prepare("UPDATE reviews SET helpful_count = helpful_count + 1 WHERE id = ?");
        return $stmt->execute([$reviewId]);
    }
}
