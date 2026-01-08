<?php
require_once __DIR__ . '/../backend/config/config.php';

if (!User::isAdmin()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$combo = new Combo();
$message = '';
$error = '';

// Lấy danh sách sản phẩm
$stmt = $conn->query("SELECT * FROM products WHERE status = 'active' ORDER BY name");
$allProducts = $stmt->fetchAll();

// Xử lý form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $productIds = $_POST['product_ids'] ?? [];
        if (count($productIds) < 2) {
            $error = 'Combo cần ít nhất 2 sản phẩm';
        } else {
            $combo->create($_POST, $productIds);
            $message = 'Tạo combo thành công!';
        }
    } elseif ($action === 'update') {
        $productIds = $_POST['product_ids'] ?? [];
        $combo->update($_POST['id'], $_POST, $productIds);
        $message = 'Cập nhật combo thành công!';
    } elseif ($action === 'delete') {
        $combo->delete($_POST['id']);
        $message = 'Xóa combo thành công!';
    }
}

$combos = $combo->getAll();

require_once ADMIN_PATH . '/includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-box-open"></i> Quản lý Combo</h1>
        <button class="btn btn-primary" onclick="openModal()">
            <i class="fas fa-plus"></i> Tạo Combo
        </button>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= $message ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?= $error ?>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th width="50">ID</th>
                        <th>Tên Combo</th>
                        <th width="100">Số SP</th>
                        <th width="100">Giảm giá</th>
                        <th width="120">Trạng thái</th>
                        <th width="120">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($combos as $c): ?>
                    <tr>
                        <td><?= $c['id'] ?></td>
                        <td>
                            <strong><?= htmlspecialchars($c['name']) ?></strong>
                            <?php if ($c['description']): ?>
                            <br><small style="color: #888;"><?= htmlspecialchars($c['description']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?= $c['product_count'] ?> sản phẩm</td>
                        <td><span class="badge badge-active">-<?= $c['discount_percent'] ?>%</span></td>
                        <td>
                            <?php if ($c['status'] == 'active'): ?>
                            <span class="badge badge-active">Hoạt động</span>
                            <?php else: ?>
                            <span class="badge badge-out-of-stock">Tắt</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <button class="btn btn-sm btn-edit" onclick='editCombo(<?= json_encode($c) ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display:inline" onsubmit="return confirm('Xóa combo này?')">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-delete">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($combos)): ?>
                    <tr><td colspan="6" class="text-center">Chưa có combo nào</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal -->
<div id="comboModal" class="admin-modal">
    <div class="admin-modal-content">
        <div class="admin-modal-header">
            <h2 id="modalTitle"><i class="fas fa-box-open"></i> Tạo Combo</h2>
            <button class="admin-modal-close" onclick="closeModal()">&times;</button>
        </div>
        <form method="POST" id="comboForm">
            <div class="admin-modal-body">
                <input type="hidden" name="action" id="formAction" value="create">
                <input type="hidden" name="id" id="comboId">
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Tên Combo *</label>
                        <input type="text" name="name" id="comboName" class="form-control" required placeholder="VD: Combo Vợt + Túi Pro">
                    </div>
                    <div class="form-group">
                        <label>Giảm giá (%)</label>
                        <input type="number" name="discount_percent" id="comboDiscount" class="form-control" value="10" min="1" max="50">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" id="comboDesc" class="form-control" rows="2" placeholder="Mô tả ngắn về combo"></textarea>
                </div>
                
                <div class="form-group">
                    <label>Trạng thái</label>
                    <select name="status" id="comboStatus" class="form-control">
                        <option value="active">Hoạt động</option>
                        <option value="inactive">Tắt</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Chọn sản phẩm trong combo * <small>(tối thiểu 2 sản phẩm)</small></label>
                    <div class="product-select-grid">
                        <?php foreach ($allProducts as $p): ?>
                        <label class="product-checkbox">
                            <input type="checkbox" name="product_ids[]" value="<?= $p['id'] ?>">
                            <div class="product-checkbox-content">
                                <img src="../images/<?= $p['image'] ?: 'product-placeholder.jpg' ?>" alt="">
                                <div class="product-checkbox-info">
                                    <span class="product-checkbox-name"><?= htmlspecialchars($p['name']) ?></span>
                                    <span class="product-checkbox-price"><?= number_format($p['sale_price'] ?: $p['price'], 0, ',', '.') ?>đ</span>
                                </div>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="admin-modal-footer">
                <button type="button" class="btn" onclick="closeModal()">Hủy</button>
                <button type="submit" class="btn btn-primary">Lưu Combo</button>
            </div>
        </form>
    </div>
</div>

<style>
.admin-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.6);
    z-index: 9999;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.admin-modal.active {
    display: flex;
}

.admin-modal-content {
    background: white;
    border-radius: 16px;
    width: 100%;
    max-width: 700px;
    max-height: 90vh;
    overflow: hidden;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}

.admin-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.admin-modal-header h2 {
    margin: 0;
    font-size: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.admin-modal-close {
    background: rgba(255,255,255,0.2);
    border: none;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    font-size: 24px;
    cursor: pointer;
    transition: all 0.3s;
}

.admin-modal-close:hover {
    background: rgba(255,255,255,0.3);
    transform: rotate(90deg);
}

.admin-modal-body {
    padding: 25px;
    max-height: 60vh;
    overflow-y: auto;
}

.admin-modal-footer {
    padding: 15px 25px;
    background: #f8f9fa;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    border-top: 1px solid #eee;
}

.form-row {
    display: grid;
    grid-template-columns: 2fr 1fr;
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
}

.form-group label small {
    font-weight: normal;
    color: #888;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e0e0e0;
    border-radius: 8px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-control:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.product-select-grid {
    max-height: 300px;
    overflow-y: auto;
    border: 2px solid #e0e0e0;
    border-radius: 10px;
    padding: 10px;
}

.product-checkbox {
    display: block;
    padding: 10px;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: 8px;
    border: 2px solid transparent;
}

.product-checkbox:hover {
    background: #f5f5f5;
}

.product-checkbox:has(input:checked) {
    background: #e8f0fe;
    border-color: #667eea;
}

.product-checkbox input {
    display: none;
}

.product-checkbox-content {
    display: flex;
    align-items: center;
    gap: 12px;
}

.product-checkbox-content img {
    width: 50px;
    height: 50px;
    object-fit: contain;
    border-radius: 8px;
    background: white;
    padding: 3px;
    border: 1px solid #eee;
}

.product-checkbox-info {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 3px;
}

.product-checkbox-name {
    font-size: 14px;
    color: #333;
    font-weight: 500;
}

.product-checkbox-price {
    font-size: 13px;
    color: #e74c3c;
    font-weight: 600;
}

.product-checkbox:has(input:checked) .product-checkbox-content::before {
    content: '✓';
    width: 24px;
    height: 24px;
    background: #667eea;
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
}
</style>

<script>
function openModal() {
    document.getElementById('comboModal').classList.add('active');
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-box-open"></i> Tạo Combo';
    document.getElementById('formAction').value = 'create';
    document.getElementById('comboForm').reset();
    document.querySelectorAll('input[name="product_ids[]"]').forEach(cb => cb.checked = false);
}

function closeModal() {
    document.getElementById('comboModal').classList.remove('active');
}

function editCombo(combo) {
    document.getElementById('comboModal').classList.add('active');
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-edit"></i> Sửa Combo';
    document.getElementById('formAction').value = 'update';
    document.getElementById('comboId').value = combo.id;
    document.getElementById('comboName').value = combo.name;
    document.getElementById('comboDesc').value = combo.description || '';
    document.getElementById('comboDiscount').value = combo.discount_percent;
    document.getElementById('comboStatus').value = combo.status;
    
    // Uncheck all first
    document.querySelectorAll('input[name="product_ids[]"]').forEach(cb => cb.checked = false);
    
    // Fetch and check products in combo
    fetch('<?= BASE_URL ?>/api/combo.php?action=detail&id=' + combo.id)
        .then(res => res.json())
        .then(data => {
            if (data.success && data.combo && data.combo.products) {
                data.combo.products.forEach(p => {
                    const cb = document.querySelector(`input[name="product_ids[]"][value="${p.id}"]`);
                    if (cb) cb.checked = true;
                });
            }
        });
}

// Close modal on outside click
document.getElementById('comboModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

// Close on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeModal();
});
</script>

<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
