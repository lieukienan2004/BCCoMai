<?php
/**
 * Admin - Quản lý mã giảm giá
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

// Xử lý thêm/sửa coupon
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add' || $action === 'edit') {
        $id = intval($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $discount_type = $_POST['discount_type'] ?? 'percent';
        $discount_value = floatval($_POST['discount_value'] ?? 0);
        $min_order_value = floatval($_POST['min_order_value'] ?? 0);
        $max_discount = floatval($_POST['max_discount'] ?? 0) ?: null;
        $usage_limit = intval($_POST['usage_limit'] ?? 0) ?: null;
        $user_limit = intval($_POST['user_limit'] ?? 1);
        $start_date = $_POST['start_date'] ?? null;
        $end_date = $_POST['end_date'] ?? null;
        $status = $_POST['status'] ?? 'active';
        
        if (empty($code) || empty($name) || $discount_value <= 0) {
            $error = 'Vui lòng điền đầy đủ thông tin!';
        } else {
            try {
                if ($action === 'add') {
                    $stmt = $conn->prepare("INSERT INTO coupons (code, name, description, discount_type, discount_value, min_order_value, max_discount, usage_limit, user_limit, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$code, $name, $description, $discount_type, $discount_value, $min_order_value, $max_discount, $usage_limit, $user_limit, $start_date ?: null, $end_date ?: null, $status]);
                    $success = 'Thêm mã giảm giá thành công!';
                } else {
                    $stmt = $conn->prepare("UPDATE coupons SET code = ?, name = ?, description = ?, discount_type = ?, discount_value = ?, min_order_value = ?, max_discount = ?, usage_limit = ?, user_limit = ?, start_date = ?, end_date = ?, status = ? WHERE id = ?");
                    $stmt->execute([$code, $name, $description, $discount_type, $discount_value, $min_order_value, $max_discount, $usage_limit, $user_limit, $start_date ?: null, $end_date ?: null, $status, $id]);
                    $success = 'Cập nhật mã giảm giá thành công!';
                }
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'Mã giảm giá đã tồn tại!';
                } else {
                    $error = 'Có lỗi xảy ra!';
                }
            }
        }
    }
    
    if ($action === 'delete') {
        $id = intval($_POST['id'] ?? 0);
        $stmt = $conn->prepare("DELETE FROM coupons WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Đã xóa mã giảm giá!';
    }
}

// Lấy danh sách coupon
$coupons = $conn->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll();

require_once 'includes/header.php';
?>

<?php
// Tính thống kê
$totalCoupons = count($coupons);
$activeCoupons = count(array_filter($coupons, fn($c) => $c['status'] === 'active'));
$totalUsed = array_sum(array_column($coupons, 'used_count'));
$expiredCoupons = count(array_filter($coupons, fn($c) => $c['end_date'] && strtotime($c['end_date']) < time()));
?>

<div class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-ticket-alt"></i> Quản lý mã giảm giá</h1>
        <button class="btn btn-primary" onclick="showAddModal()">
            <i class="fas fa-plus"></i> Thêm mã mới
        </button>
    </div>
    
    <?php if ($success): ?>
    <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= $success ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
    <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?= $error ?></div>
    <?php endif; ?>
    
    <!-- Stats -->
    <div class="coupon-stats">
        <div class="stat-card">
            <div class="stat-icon orange"><i class="fas fa-ticket-alt"></i></div>
            <div class="stat-info">
                <h4><?= $totalCoupons ?></h4>
                <p>Tổng mã giảm giá</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
            <div class="stat-info">
                <h4><?= $activeCoupons ?></h4>
                <p>Đang hoạt động</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fas fa-shopping-cart"></i></div>
            <div class="stat-info">
                <h4><?= $totalUsed ?></h4>
                <p>Lượt sử dụng</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon purple"><i class="fas fa-clock"></i></div>
            <div class="stat-info">
                <h4><?= $expiredCoupons ?></h4>
                <p>Đã hết hạn</p>
            </div>
        </div>
    </div>
    
    <?php if (empty($coupons)): ?>
    <div class="empty-coupons">
        <i class="fas fa-ticket-alt"></i>
        <h3>Chưa có mã giảm giá nào</h3>
        <p>Tạo mã giảm giá đầu tiên để thu hút khách hàng!</p>
    </div>
    <?php else: ?>
    <div class="coupons-grid">
        <?php foreach ($coupons as $coupon): 
            $isExpired = $coupon['end_date'] && strtotime($coupon['end_date']) < time();
            $usagePercent = $coupon['usage_limit'] ? ($coupon['used_count'] / $coupon['usage_limit']) * 100 : 0;
        ?>
        <div class="coupon-card <?= $coupon['status'] !== 'active' || $isExpired ? 'inactive' : '' ?>">
            <div class="coupon-header">
                <div class="coupon-code">
                    <i class="fas fa-tag"></i>
                    <?= htmlspecialchars($coupon['code']) ?>
                </div>
                <h3 class="coupon-name"><?= htmlspecialchars($coupon['name']) ?></h3>
                <?php if ($coupon['description']): ?>
                <p class="coupon-desc"><?= htmlspecialchars($coupon['description']) ?></p>
                <?php endif; ?>
            </div>
            
            <div class="coupon-body">
                <div class="coupon-discount">
                    <?php if ($coupon['discount_type'] === 'percent'): ?>
                    <div class="discount-value"><?= intval($coupon['discount_value']) ?>%</div>
                    <div class="discount-type">Giảm phần trăm</div>
                    <?php if ($coupon['max_discount']): ?>
                    <div class="discount-max">Tối đa <?= number_format($coupon['max_discount']) ?>đ</div>
                    <?php endif; ?>
                    <?php else: ?>
                    <div class="discount-value"><?= number_format($coupon['discount_value']) ?>đ</div>
                    <div class="discount-type">Giảm trực tiếp</div>
                    <?php endif; ?>
                </div>
                
                <div class="coupon-details">
                    <div class="detail-item">
                        <i class="fas fa-shopping-bag"></i>
                        <span>Đơn tối thiểu: <strong><?= number_format($coupon['min_order_value']) ?>đ</strong></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-user"></i>
                        <span>Giới hạn/người: <strong><?= $coupon['user_limit'] ?> lần</strong></span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-calendar-alt"></i>
                        <span>
                            <?php if ($coupon['start_date'] && $coupon['end_date']): ?>
                            <?= date('d/m/Y', strtotime($coupon['start_date'])) ?> - <?= date('d/m/Y', strtotime($coupon['end_date'])) ?>
                            <?php else: ?>
                            Không giới hạn
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="detail-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>Đã dùng: <strong><?= $coupon['used_count'] ?><?= $coupon['usage_limit'] ? '/' . $coupon['usage_limit'] : '' ?></strong></span>
                    </div>
                </div>
                
                <?php if ($coupon['usage_limit']): ?>
                <div class="usage-progress">
                    <div class="progress-label">
                        <span>Tiến độ sử dụng</span>
                        <span><?= round($usagePercent) ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= min($usagePercent, 100) ?>%"></div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="coupon-footer">
                <div class="coupon-status">
                    <span class="status-dot"></span>
                    <span class="status-text">
                        <?php if ($isExpired): ?>
                        Đã hết hạn
                        <?php elseif ($coupon['status'] === 'active'): ?>
                        Đang hoạt động
                        <?php else: ?>
                        Đã tắt
                        <?php endif; ?>
                    </span>
                </div>
                <div class="coupon-actions">
                    <button class="btn btn-edit" onclick='editCoupon(<?= json_encode($coupon) ?>)' title="Chỉnh sửa">
                        <i class="fas fa-edit"></i>
                    </button>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Xóa mã giảm giá này?')">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= $coupon['id'] ?>">
                        <button type="submit" class="btn btn-delete" title="Xóa">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal thêm/sửa coupon -->
<div id="couponModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2><i class="fas fa-ticket-alt"></i> <span id="modalTitle">Thêm mã giảm giá</span></h2>
            <button class="modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="couponForm">
            <input type="hidden" name="action" id="formAction" value="add">
            <input type="hidden" name="id" id="couponId">
            
            <div class="form-section">
                <div class="form-section-title"><i class="fas fa-info-circle"></i> Thông tin cơ bản</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Mã giảm giá <span class="required">*</span></label>
                        <input type="text" name="code" id="code" required style="text-transform: uppercase;" placeholder="VD: SALE20">
                        <small>Mã sẽ tự động viết hoa</small>
                    </div>
                    <div class="form-group">
                        <label>Tên mã <span class="required">*</span></label>
                        <input type="text" name="name" id="name" required placeholder="VD: Giảm 20% đơn hàng">
                    </div>
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" id="description" rows="2" placeholder="Mô tả chi tiết về mã giảm giá..."></textarea>
                </div>
            </div>
            
            <div class="form-section">
                <div class="form-section-title"><i class="fas fa-percent"></i> Thiết lập giảm giá</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Loại giảm giá</label>
                        <select name="discount_type" id="discount_type">
                            <option value="percent">📊 Phần trăm (%)</option>
                            <option value="fixed">💰 Số tiền cố định (đ)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Giá trị giảm <span class="required">*</span></label>
                        <input type="number" name="discount_value" id="discount_value" required min="0" placeholder="VD: 20">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Đơn hàng tối thiểu</label>
                        <input type="number" name="min_order_value" id="min_order_value" value="0" min="0" placeholder="0">
                        <small>Giá trị đơn hàng tối thiểu để áp dụng</small>
                    </div>
                    <div class="form-group">
                        <label>Giảm tối đa (cho %)</label>
                        <input type="number" name="max_discount" id="max_discount" min="0" placeholder="Để trống = không giới hạn">
                        <small>Chỉ áp dụng cho loại phần trăm</small>
                    </div>
                </div>
            </div>
            
            <div class="form-section">
                <div class="form-section-title"><i class="fas fa-cog"></i> Giới hạn sử dụng</div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Tổng lượt sử dụng</label>
                        <input type="number" name="usage_limit" id="usage_limit" min="0" placeholder="Để trống = không giới hạn">
                    </div>
                    <div class="form-group">
                        <label>Giới hạn/người dùng</label>
                        <input type="number" name="user_limit" id="user_limit" value="1" min="1">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Ngày bắt đầu</label>
                        <input type="datetime-local" name="start_date" id="start_date">
                    </div>
                    <div class="form-group">
                        <label>Ngày kết thúc</label>
                        <input type="datetime-local" name="end_date" id="end_date">
                    </div>
                </div>
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" id="status">
                        <option value="active">✅ Hoạt động</option>
                        <option value="inactive">⏸️ Tạm tắt</option>
                    </select>
                </div>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">
                    <i class="fas fa-times"></i> Hủy
                </button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Lưu mã giảm giá
                </button>
            </div>
        </form>
    </div>
</div>

<style>
/* Coupons Page Styles */
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
    color: #ff6600;
}

/* Coupon Cards Grid */
.coupons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(380px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.coupon-card {
    background: linear-gradient(135deg, #fff 0%, #fafafa 100%);
    border-radius: 16px;
    overflow: hidden;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    position: relative;
    transition: all 0.3s ease;
}

.coupon-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 30px rgba(0,0,0,0.12);
}

.coupon-card::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 6px;
    background: linear-gradient(180deg, #ff6600, #ff8c00);
}

.coupon-card.inactive::before {
    background: #ccc;
}

.coupon-header {
    padding: 20px 20px 15px;
    border-bottom: 2px dashed #eee;
    position: relative;
}

.coupon-header::before,
.coupon-header::after {
    content: '';
    position: absolute;
    bottom: -12px;
    width: 24px;
    height: 24px;
    background: #f5f5f5;
    border-radius: 50%;
}

.coupon-header::before {
    left: -12px;
}

.coupon-header::after {
    right: -12px;
}

.coupon-code {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    background: linear-gradient(135deg, #ff6600, #ff8c00);
    color: white;
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 16px;
    letter-spacing: 1px;
}

.coupon-card.inactive .coupon-code {
    background: linear-gradient(135deg, #999, #bbb);
}

.coupon-code i {
    font-size: 14px;
}

.coupon-name {
    margin: 12px 0 0;
    font-size: 18px;
    color: #333;
    font-weight: 600;
}

.coupon-desc {
    color: #666;
    font-size: 13px;
    margin-top: 5px;
}

.coupon-body {
    padding: 20px;
}

.coupon-discount {
    text-align: center;
    padding: 15px;
    background: linear-gradient(135deg, #fff8f0, #fff5eb);
    border-radius: 12px;
    margin-bottom: 15px;
}

.discount-value {
    font-size: 36px;
    font-weight: 800;
    color: #ff6600;
    line-height: 1;
}

.discount-type {
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}

.discount-max {
    font-size: 12px;
    color: #999;
    margin-top: 3px;
}

.coupon-details {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #555;
}

.detail-item i {
    width: 28px;
    height: 28px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f0f0f0;
    border-radius: 6px;
    color: #666;
    font-size: 12px;
}

.detail-item strong {
    color: #333;
}

.coupon-footer {
    padding: 15px 20px;
    background: #f9f9f9;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.coupon-status {
    display: flex;
    align-items: center;
    gap: 8px;
}

.status-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    background: #27ae60;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.coupon-card.inactive .status-dot {
    background: #ccc;
    animation: none;
}

.status-text {
    font-size: 13px;
    font-weight: 600;
    color: #27ae60;
}

.coupon-card.inactive .status-text {
    color: #999;
}

.coupon-actions {
    display: flex;
    gap: 8px;
}

.coupon-actions .btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.coupon-actions .btn-edit {
    background: #e3f2fd;
    color: #1976d2;
}

.coupon-actions .btn-edit:hover {
    background: #1976d2;
    color: white;
}

.coupon-actions .btn-delete {
    background: #ffebee;
    color: #e74c3c;
}

.coupon-actions .btn-delete:hover {
    background: #e74c3c;
    color: white;
}

/* Usage Progress */
.usage-progress {
    margin-top: 15px;
    padding-top: 15px;
    border-top: 1px solid #eee;
}

.progress-label {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #666;
    margin-bottom: 6px;
}

.progress-bar {
    height: 6px;
    background: #eee;
    border-radius: 3px;
    overflow: hidden;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #ff6600, #ff8c00);
    border-radius: 3px;
    transition: width 0.3s;
}

/* Modal Styles */
.modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

.modal.show {
    display: flex;
}

.modal-content {
    background: white;
    border-radius: 20px;
    width: 650px;
    max-width: 95%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    animation: modalSlide 0.3s ease;
}

@keyframes modalSlide {
    from {
        opacity: 0;
        transform: translateY(-30px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 30px;
    background: linear-gradient(135deg, #ff6600, #ff8c00);
    color: white;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.modal-close {
    background: rgba(255,255,255,0.2);
    border: none;
    font-size: 20px;
    cursor: pointer;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s;
}

.modal-close:hover {
    background: rgba(255,255,255,0.3);
    transform: rotate(90deg);
}

#couponForm {
    padding: 30px;
}

.form-section {
    margin-bottom: 25px;
}

.form-section-title {
    font-size: 14px;
    font-weight: 600;
    color: #ff6600;
    text-transform: uppercase;
    letter-spacing: 1px;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #fff0e6;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
    font-size: 14px;
}

.form-group label .required {
    color: #e74c3c;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    border-color: #ff6600;
    outline: none;
    box-shadow: 0 0 0 4px rgba(255, 102, 0, 0.1);
}

.form-group input::placeholder {
    color: #aaa;
}

.form-group small {
    display: block;
    margin-top: 5px;
    color: #999;
    font-size: 12px;
}

.form-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 30px;
    padding-top: 25px;
    border-top: 2px solid #f0f0f0;
}

.form-actions .btn {
    padding: 12px 30px;
    border-radius: 10px;
    font-weight: 600;
    font-size: 14px;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.form-actions .btn-secondary {
    background: #f5f5f5;
    border: 2px solid #e0e0e0;
    color: #666;
}

.form-actions .btn-secondary:hover {
    background: #eee;
}

.form-actions .btn-primary {
    background: linear-gradient(135deg, #ff6600, #ff8c00);
    border: none;
    color: white;
}

.form-actions .btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 15px rgba(255, 102, 0, 0.4);
}

/* Empty State */
.empty-coupons {
    text-align: center;
    padding: 60px 20px;
    background: #f9f9f9;
    border-radius: 16px;
}

.empty-coupons i {
    font-size: 80px;
    color: #ddd;
    margin-bottom: 20px;
}

.empty-coupons h3 {
    font-size: 20px;
    color: #333;
    margin-bottom: 10px;
}

.empty-coupons p {
    color: #666;
}

/* Stats Cards */
.coupon-stats {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
}

.stat-icon.orange {
    background: #fff0e6;
    color: #ff6600;
}

.stat-icon.green {
    background: #e8f5e9;
    color: #27ae60;
}

.stat-icon.blue {
    background: #e3f2fd;
    color: #1976d2;
}

.stat-icon.purple {
    background: #f3e5f5;
    color: #9c27b0;
}

.stat-info h4 {
    font-size: 24px;
    margin: 0;
    color: #333;
}

.stat-info p {
    font-size: 13px;
    color: #666;
    margin: 0;
}

@media (max-width: 1200px) {
    .coupons-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    .coupon-stats {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 768px) {
    .coupons-grid {
        grid-template-columns: 1fr;
    }
    .coupon-stats {
        grid-template-columns: 1fr;
    }
    .form-row {
        grid-template-columns: 1fr;
    }
}
</style>

<script>
function showAddModal() {
    document.getElementById('modalTitle').textContent = 'Thêm mã giảm giá';
    document.getElementById('formAction').value = 'add';
    document.getElementById('couponForm').reset();
    document.getElementById('couponModal').classList.add('show');
}

function editCoupon(coupon) {
    document.getElementById('modalTitle').textContent = 'Sửa mã giảm giá';
    document.getElementById('formAction').value = 'edit';
    document.getElementById('couponId').value = coupon.id;
    document.getElementById('code').value = coupon.code;
    document.getElementById('name').value = coupon.name;
    document.getElementById('description').value = coupon.description || '';
    document.getElementById('discount_type').value = coupon.discount_type;
    document.getElementById('discount_value').value = coupon.discount_value;
    document.getElementById('min_order_value').value = coupon.min_order_value;
    document.getElementById('max_discount').value = coupon.max_discount || '';
    document.getElementById('usage_limit').value = coupon.usage_limit || '';
    document.getElementById('user_limit').value = coupon.user_limit;
    document.getElementById('start_date').value = coupon.start_date ? coupon.start_date.replace(' ', 'T').slice(0, 16) : '';
    document.getElementById('end_date').value = coupon.end_date ? coupon.end_date.replace(' ', 'T').slice(0, 16) : '';
    document.getElementById('status').value = coupon.status;
    document.getElementById('couponModal').classList.add('show');
}

function closeModal() {
    document.getElementById('couponModal').classList.remove('show');
}

// Close modal when clicking outside
document.getElementById('couponModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>

<?php require_once 'includes/footer.php'; ?>
