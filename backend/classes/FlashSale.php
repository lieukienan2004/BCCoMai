<?php
/**
 * Class FlashSale - Quản lý Flash Sale
 */
class FlashSale {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    /**
     * Lấy Flash Sale đang active (có sản phẩm)
     */
    public function getActive() {
        $stmt = $this->conn->query("
            SELECT fs.* FROM flash_sales fs
            INNER JOIN flash_sale_products fsp ON fs.id = fsp.flash_sale_id
            WHERE fs.status = 'active' 
            AND fs.start_time <= NOW() 
            AND fs.end_time >= NOW()
            AND fsp.flash_stock > fsp.sold_count
            GROUP BY fs.id
            ORDER BY fs.created_at DESC
            LIMIT 1
        ");
        
        return $stmt->fetch();
    }
    
    /**
     * Lấy Flash Sale sắp diễn ra
     */
    public function getUpcoming() {
        $stmt = $this->conn->query("
            SELECT * FROM flash_sales 
            WHERE start_time > NOW()
            ORDER BY start_time ASC
            LIMIT 1
        ");
        
        return $stmt->fetch();
    }
    
    /**
     * Lấy sản phẩm trong Flash Sale
     */
    public function getProducts($flashSaleId, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT p.*, fsp.flash_price, fsp.flash_stock, fsp.sold_count,
                   c.name as category_name,
                   ROUND(((p.price - fsp.flash_price) / p.price) * 100) as discount_percent
            FROM flash_sale_products fsp
            JOIN products p ON fsp.product_id = p.id
            LEFT JOIN categories c ON p.category_id = c.id
            WHERE fsp.flash_sale_id = ?
            AND fsp.flash_stock > fsp.sold_count
            ORDER BY fsp.sold_count DESC
            LIMIT ?
        ");
        $stmt->execute([$flashSaleId, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Kiểm tra sản phẩm có trong Flash Sale không
     */
    public function getFlashPrice($productId) {
        $activeSale = $this->getActive();
        if (!$activeSale) {
            return null;
        }
        
        $stmt = $this->conn->prepare("
            SELECT flash_price, flash_stock, sold_count 
            FROM flash_sale_products 
            WHERE flash_sale_id = ? AND product_id = ?
            AND flash_stock > sold_count
        ");
        $stmt->execute([$activeSale['id'], $productId]);
        $result = $stmt->fetch();
        
        return $result ? $result['flash_price'] : null;
    }
    
    /**
     * Cập nhật số lượng đã bán
     */
    public function incrementSold($flashSaleId, $productId, $quantity = 1) {
        $stmt = $this->conn->prepare("
            UPDATE flash_sale_products 
            SET sold_count = sold_count + ?
            WHERE flash_sale_id = ? AND product_id = ?
            AND flash_stock >= sold_count + ?
        ");
        
        return $stmt->execute([$quantity, $flashSaleId, $productId, $quantity]);
    }
    
    /**
     * Lấy thời gian còn lại (seconds)
     */
    public function getTimeRemaining($flashSale) {
        if (!$flashSale) return 0;
        
        $endTime = strtotime($flashSale['end_time']);
        $now = time();
        
        return max(0, $endTime - $now);
    }
    
    /**
     * Cập nhật trạng thái Flash Sale (chạy bằng cron)
     */
    public function updateStatuses() {
        // Kích hoạt các flash sale đến giờ
        $this->conn->query("
            UPDATE flash_sales 
            SET status = 'active' 
            WHERE status = 'upcoming' 
            AND start_time <= NOW() 
            AND end_time >= NOW()
        ");
        
        // Kết thúc các flash sale hết giờ
        $this->conn->query("
            UPDATE flash_sales 
            SET status = 'ended' 
            WHERE status IN ('upcoming', 'active') 
            AND end_time < NOW()
        ");
    }
    
    /**
     * Tạo Flash Sale mới (Admin)
     */
    public function create($data) {
        $stmt = $this->conn->prepare("
            INSERT INTO flash_sales (name, description, start_time, end_time, status, banner_image)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $status = strtotime($data['start_time']) <= time() ? 'active' : 'upcoming';
        
        $stmt->execute([
            $data['name'],
            $data['description'] ?? null,
            $data['start_time'],
            $data['end_time'],
            $status,
            $data['banner_image'] ?? null
        ]);
        
        return $this->conn->lastInsertId();
    }
    
    /**
     * Thêm sản phẩm vào Flash Sale (Admin)
     */
    public function addProduct($flashSaleId, $productId, $flashPrice, $flashStock) {
        $stmt = $this->conn->prepare("
            INSERT INTO flash_sale_products (flash_sale_id, product_id, flash_price, flash_stock)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE 
                flash_price = VALUES(flash_price),
                flash_stock = VALUES(flash_stock)
        ");
        
        return $stmt->execute([$flashSaleId, $productId, $flashPrice, $flashStock]);
    }
    
    /**
     * Lấy tất cả Flash Sales (Admin)
     */
    public function getAll() {
        $stmt = $this->conn->query("
            SELECT fs.*, 
                   COUNT(fsp.id) as product_count,
                   SUM(fsp.sold_count) as total_sold
            FROM flash_sales fs
            LEFT JOIN flash_sale_products fsp ON fs.id = fsp.flash_sale_id
            GROUP BY fs.id
            ORDER BY fs.created_at DESC
        ");
        
        return $stmt->fetchAll();
    }
}
