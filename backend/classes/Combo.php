<?php
class Combo {
    private $conn;
    
    public function __construct() {
        $this->conn = getConnection();
    }
    
    // Lấy tất cả combo đang active
    public function getActive() {
        $stmt = $this->conn->query("
            SELECT c.*, 
                GROUP_CONCAT(p.id) as product_ids,
                GROUP_CONCAT(p.name SEPARATOR '|||') as product_names,
                GROUP_CONCAT(p.image SEPARATOR '|||') as product_images,
                SUM(COALESCE(p.sale_price, p.price)) as total_price
            FROM product_combos c
            JOIN combo_items ci ON c.id = ci.combo_id
            JOIN products p ON ci.product_id = p.id
            WHERE c.status = 'active' AND p.status = 'active'
            GROUP BY c.id
            HAVING COUNT(ci.id) > 0
        ");
        return $stmt->fetchAll();
    }
    
    // Lấy combo theo ID
    public function getById($id) {
        $stmt = $this->conn->prepare("
            SELECT c.*, 
                GROUP_CONCAT(p.id) as product_ids,
                SUM(COALESCE(p.sale_price, p.price)) as total_price
            FROM product_combos c
            JOIN combo_items ci ON c.id = ci.combo_id
            JOIN products p ON ci.product_id = p.id
            WHERE c.id = ? AND c.status = 'active'
            GROUP BY c.id
        ");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // Lấy sản phẩm trong combo
    public function getProducts($comboId) {
        $stmt = $this->conn->prepare("
            SELECT p.* FROM products p
            JOIN combo_items ci ON p.id = ci.product_id
            WHERE ci.combo_id = ? AND p.status = 'active'
        ");
        $stmt->execute([$comboId]);
        return $stmt->fetchAll();
    }
    
    // Tính giá combo sau giảm
    public function getComboPrice($comboId) {
        $combo = $this->getById($comboId);
        if (!$combo) return 0;
        
        $discount = $combo['total_price'] * ($combo['discount_percent'] / 100);
        return $combo['total_price'] - $discount;
    }
    
    // Gợi ý combo dựa trên sản phẩm trong giỏ
    public function suggestForCart($productIds) {
        if (empty($productIds)) return [];
        
        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->conn->prepare("
            SELECT DISTINCT c.*, 
                SUM(COALESCE(p.sale_price, p.price)) as total_price
            FROM product_combos c
            JOIN combo_items ci ON c.id = ci.combo_id
            JOIN products p ON ci.product_id = p.id
            WHERE c.status = 'active' 
            AND ci.product_id IN ($placeholders)
            GROUP BY c.id
            LIMIT 3
        ");
        $stmt->execute($productIds);
        return $stmt->fetchAll();
    }
    
    // Admin: Tạo combo mới
    public function create($data, $productIds) {
        $stmt = $this->conn->prepare("
            INSERT INTO product_combos (name, description, image, discount_percent, status) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['image'] ?? null,
            $data['discount_percent'] ?? 10,
            $data['status'] ?? 'active'
        ]);
        
        $comboId = $this->conn->lastInsertId();
        
        foreach ($productIds as $productId) {
            $stmt = $this->conn->prepare("INSERT INTO combo_items (combo_id, product_id) VALUES (?, ?)");
            $stmt->execute([$comboId, $productId]);
        }
        
        return $comboId;
    }
    
    // Admin: Cập nhật combo
    public function update($id, $data, $productIds = null) {
        $stmt = $this->conn->prepare("
            UPDATE product_combos SET name = ?, description = ?, discount_percent = ?, status = ? WHERE id = ?
        ");
        $stmt->execute([
            $data['name'],
            $data['description'] ?? '',
            $data['discount_percent'] ?? 10,
            $data['status'] ?? 'active',
            $id
        ]);
        
        if ($productIds !== null) {
            $stmt = $this->conn->prepare("DELETE FROM combo_items WHERE combo_id = ?");
            $stmt->execute([$id]);
            
            foreach ($productIds as $productId) {
                $stmt = $this->conn->prepare("INSERT INTO combo_items (combo_id, product_id) VALUES (?, ?)");
                $stmt->execute([$id, $productId]);
            }
        }
        
        return true;
    }
    
    // Admin: Xóa combo
    public function delete($id) {
        $stmt = $this->conn->prepare("DELETE FROM product_combos WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // Admin: Lấy tất cả combo
    public function getAll() {
        $stmt = $this->conn->query("
            SELECT c.*, COUNT(ci.id) as product_count
            FROM product_combos c
            LEFT JOIN combo_items ci ON c.id = ci.combo_id
            GROUP BY c.id
            ORDER BY c.created_at DESC
        ");
        return $stmt->fetchAll();
    }
}
