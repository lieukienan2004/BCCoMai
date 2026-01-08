<?php
/**
 * Admin - Quản lý đánh giá sản phẩm
 */
require_once __DIR__ . '/../backend/config/config.php';

// Kiểm tra đăng nhập admin
if (!User::isLoggedIn() || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$success = '';
$error = '';

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = intval($_POST['id'] ?? 0);
    
    if ($action === 'approve') {
        $stmt = $conn->prepare("UPDATE reviews SET status = 'approved' WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Đã duyệt đánh giá!';
    } elseif ($action === 'reject') {
        $stmt = $conn->prepare("UPDATE reviews SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Đã từ chối đánh giá!';
    } elseif ($action === 'delete') {
        $stmt = $conn->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Đã xóa đánh giá!';
    }
}

// Lọc theo trạng thái
$statusFilter = $_GET['status'] ?? 'all';
$sql = "SELECT r.*, u.fullname, u.email, p.name as product_name, p.image as product_image
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        JOIN products p ON r.product_id = p.id";

if ($statusFilter !== 'all') {
    $sql .= " WHERE r.status = ?";
}
$sql .= " ORDER BY r.created_at DESC";

$stmt = $conn->prepare($sql);
if ($statusFilter !== 'all') {
    $stmt->execute([$statusFilter]);
} else {
    $stmt->execute();
}
$reviews = $stmt->fetchAll();

// Đếm theo trạng thái
$counts = $conn->query("SELECT status, COUNT(*) as count FROM reviews GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);

require_once 'includes/header.php';
?>

<?php
// Tính thống kê
$totalReviews = array_sum($counts);
$avgRating = $conn->query("SELECT ROUND(AVG(rating), 1) FROM reviews WHERE status = 'approved'")->fetchColumn() ?: 0;
?>

<div class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-star"></i> Quản lý đánh giá</h1>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>
    
    <!-- Stats -->
    <div class="reviews-stats">
        <div class="stat-card">
            <div class="stat-icon yellow"><i class="fas fa-star"></i></div>
            <div class="stat-info">
                <h4><?= $avgRating ?>/5</h4>
                <p>Đánh giá trung bình</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <h4><?= $counts['pending'] ?? 0 ?></h4>
                <p>Chờ duyệt</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h4><?= $counts['approved'] ?? 0 ?></h4>
                <p>Đã duyệt</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon red"><i class="fas fa-ban"></i></div>
            <div class="stat-info">
                <h4><?= $counts['rejected'] ?? 0 ?></h4>
                <p>Từ chối</p>
            </div>
        </div>
    </div>
    
    <div class="filter-tabs">
        <a href="?status=all" class="tab <?= $statusFilter === 'all' ? 'active' : '' ?>">
            <i class="fas fa-list"></i> Tất cả <span class="count"><?= $totalReviews ?></span>
        </a>
        <a href="?status=pending" class="tab <?= $statusFilter === 'pending' ? 'active' : '' ?>">
            <i class="fas fa-clock"></i> Chờ duyệt <span class="count"><?= $counts['pending'] ?? 0 ?></span>
        </a>
        <a href="?status=approved" class="tab <?= $statusFilter === 'approved' ? 'active' : '' ?>">
            <i class="fas fa-check"></i> Đã duyệt <span class="count"><?= $counts['approved'] ?? 0 ?></span>
        </a>
        <a href="?status=rejected" class="tab <?= $statusFilter === 'rejected' ? 'active' : '' ?>">
            <i class="fas fa-ban"></i> Từ chối <span class="count"><?= $counts['rejected'] ?? 0 ?></span>
        </a>
    </div>
    
    <div class="reviews-list">
        <?php if (empty($reviews)): ?>
        <div class="empty-state">
            <i class="fas fa-star"></i>
            <h3>Chưa có đánh giá nào</h3>
            <p>Các đánh giá từ khách hàng sẽ xuất hiện ở đây</p>
        </div>
        <?php else: ?>
        <?php foreach ($reviews as $review): 
            $initials = mb_substr($review['fullname'], 0, 1);
        ?>
        <div class="review-card">
            <div class="review-card-inner">
                <div class="review-product">
                    <img src="/shopcaulong/images/<?= $review['product_image'] ?: 'product-placeholder.jpg' ?>" 
                         alt="" onerror="this.src='/shopcaulong/images/product-placeholder.jpg'">
                    <div>
                        <h4><?= htmlspecialchars($review['product_name']) ?></h4>
                        <span class="product-id">ID: <?= $review['product_id'] ?></span>
                    </div>
                </div>
                
                <div class="review-content">
                    <div class="review-header">
                        <div class="reviewer">
                            <div class="reviewer-avatar"><?= $initials ?></div>
                            <div class="reviewer-info">
                                <strong><?= htmlspecialchars($review['fullname']) ?></strong>
                                <span><?= htmlspecialchars($review['email']) ?></span>
                            </div>
                        </div>
                        <div class="review-meta">
                            <div class="stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star <?= $i <= $review['rating'] ? 'active' : '' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="date"><?= date('d/m/Y H:i', strtotime($review['created_at'])) ?></span>
                        </div>
                    </div>
                    
                    <?php if ($review['title']): ?>
                    <h4 class="review-title"><?= htmlspecialchars($review['title']) ?></h4>
                    <?php endif; ?>
                    
                    <p class="review-comment"><?= nl2br(htmlspecialchars($review['comment'])) ?></p>
                    
                    <div class="review-footer">
                        <span class="status-badge <?= $review['status'] ?>">
                            <?php
                            switch ($review['status']) {
                                case 'pending': echo 'Chờ duyệt'; break;
                                case 'approved': echo 'Đã duyệt'; break;
                                case 'rejected': echo 'Từ chối'; break;
                            }
                            ?>
                        </span>
                        <span class="helpful"><i class="fas fa-thumbs-up"></i> <?= $review['helpful_count'] ?> hữu ích</span>
                        
                        <div class="review-actions">
                            <?php if ($review['status'] !== 'approved'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="approve">
                                <input type="hidden" name="id" value="<?= $review['id'] ?>">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check"></i> Duyệt
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <?php if ($review['status'] !== 'rejected'): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="reject">
                                <input type="hidden" name="id" value="<?= $review['id'] ?>">
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-ban"></i> Từ chối
                                </button>
                            </form>
                            <?php endif; ?>
                            
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Xóa đánh giá này?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $review['id'] ?>">
                                <button type="submit" class="btn btn-delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
/* Reviews Admin Styles */
.content-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.content-header h1 {
    font-size: 24px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 12px;
}

.content-header h1 i {
    color: #f1c40f;
}

/* Stats Row */
.reviews-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 25px;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 18px;
    transition: all 0.3s;
}

.stat-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
}

.stat-icon {
    width: 60px;
    height: 60px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.stat-icon.yellow {
    background: linear-gradient(135deg, #fff8e1, #ffecb3);
    color: #f1c40f;
}

.stat-icon.orange {
    background: linear-gradient(135deg, #fff3e0, #ffe0b2);
    color: #ff9800;
}

.stat-icon.green {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    color: #27ae60;
}

.stat-icon.red {
    background: linear-gradient(135deg, #ffebee, #ffcdd2);
    color: #e74c3c;
}

.stat-info h4 {
    font-size: 28px;
    margin: 0;
    color: #333;
    font-weight: 700;
}

.stat-info p {
    font-size: 14px;
    color: #666;
    margin: 5px 0 0;
}

/* Filter Tabs */
.filter-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 25px;
    background: #f5f5f5;
    padding: 6px;
    border-radius: 12px;
    width: fit-content;
}

.filter-tabs .tab {
    padding: 12px 24px;
    text-decoration: none;
    color: #666;
    border-radius: 8px;
    transition: all 0.3s;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-tabs .tab:hover {
    background: #fff;
    color: #333;
}

.filter-tabs .tab.active {
    background: linear-gradient(135deg, #ff6600, #ff8c00);
    color: white;
    box-shadow: 0 4px 15px rgba(255, 102, 0, 0.3);
}

.filter-tabs .tab .count {
    background: rgba(255,255,255,0.2);
    padding: 2px 8px;
    border-radius: 10px;
    font-size: 12px;
}

.filter-tabs .tab.active .count {
    background: rgba(255,255,255,0.3);
}

/* Reviews List */
.reviews-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.review-card {
    background: white;
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.05);
    transition: all 0.3s;
}

.review-card:hover {
    box-shadow: 0 8px 30px rgba(0,0,0,0.1);
}

.review-card-inner {
    display: grid;
    grid-template-columns: 220px 1fr;
    gap: 0;
}

.review-product {
    background: linear-gradient(135deg, #f8f9fa, #f0f0f0);
    padding: 25px;
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    border-right: 1px solid #eee;
}

.review-product img {
    width: 100px;
    height: 100px;
    object-fit: cover;
    border-radius: 12px;
    margin-bottom: 15px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.1);
}

.review-product h4 {
    font-size: 14px;
    margin: 0 0 5px;
    color: #333;
    line-height: 1.4;
}

.review-product .product-id {
    font-size: 12px;
    color: #999;
    background: #e0e0e0;
    padding: 3px 10px;
    border-radius: 10px;
}

.review-content {
    padding: 25px;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.reviewer {
    display: flex;
    align-items: center;
    gap: 12px;
}

.reviewer-avatar {
    width: 45px;
    height: 45px;
    border-radius: 50%;
    background: linear-gradient(135deg, #ff6600, #ff8c00);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 700;
    font-size: 18px;
}

.reviewer-info strong {
    display: block;
    font-size: 15px;
    color: #333;
}

.reviewer-info span {
    font-size: 13px;
    color: #666;
}

.review-meta {
    text-align: right;
}

.stars {
    display: flex;
    gap: 3px;
    justify-content: flex-end;
    margin-bottom: 5px;
}

.stars i {
    color: #e0e0e0;
    font-size: 16px;
}

.stars i.active {
    color: #f1c40f;
}

.date {
    font-size: 13px;
    color: #999;
}

.review-title {
    font-size: 16px;
    margin: 0 0 10px;
    color: #333;
}

.review-comment {
    color: #555;
    line-height: 1.7;
    margin: 0 0 20px;
    font-size: 14px;
    background: #f9f9f9;
    padding: 15px;
    border-radius: 10px;
    border-left: 4px solid #ff6600;
}

.review-footer {
    display: flex;
    align-items: center;
    gap: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.status-badge {
    padding: 6px 14px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.status-badge::before {
    content: '';
    width: 8px;
    height: 8px;
    border-radius: 50%;
}

.status-badge.pending {
    background: linear-gradient(135deg, #fff8e1, #ffecb3);
    color: #f57c00;
}

.status-badge.pending::before {
    background: #f57c00;
}

.status-badge.approved {
    background: linear-gradient(135deg, #e8f5e9, #c8e6c9);
    color: #2e7d32;
}

.status-badge.approved::before {
    background: #2e7d32;
}

.status-badge.rejected {
    background: linear-gradient(135deg, #ffebee, #ffcdd2);
    color: #c62828;
}

.status-badge.rejected::before {
    background: #c62828;
}

.helpful {
    font-size: 13px;
    color: #666;
    display: flex;
    align-items: center;
    gap: 6px;
}

.helpful i {
    color: #27ae60;
}

.review-actions {
    margin-left: auto;
    display: flex;
    gap: 10px;
}

.review-actions .btn {
    padding: 8px 16px;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: none;
}

.btn-success {
    background: linear-gradient(135deg, #27ae60, #2ecc71);
    color: white;
}

.btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(39, 174, 96, 0.4);
}

.btn-warning {
    background: linear-gradient(135deg, #f39c12, #f1c40f);
    color: white;
}

.btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(243, 156, 18, 0.4);
}

.btn-delete {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
}

.btn-delete:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(231, 76, 60, 0.4);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 80px 20px;
    background: linear-gradient(135deg, #f9f9f9, #f0f0f0);
    border-radius: 20px;
}

.empty-state i {
    font-size: 80px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-state h3 {
    font-size: 20px;
    color: #333;
    margin-bottom: 10px;
}

.empty-state p {
    color: #666;
}

@media (max-width: 1200px) {
    .reviews-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .review-card-inner {
        grid-template-columns: 1fr;
    }
    .review-product {
        border-right: none;
        border-bottom: 1px solid #eee;
        flex-direction: row;
        text-align: left;
        gap: 15px;
    }
    .review-product img {
        width: 60px;
        height: 60px;
        margin-bottom: 0;
    }
    .reviews-stats {
        grid-template-columns: 1fr;
    }
    .filter-tabs {
        flex-wrap: wrap;
    }
    .review-actions {
        flex-wrap: wrap;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?>
