<?php
require_once __DIR__ . '/../backend/config/config.php';

if (!User::isLoggedIn() || !User::isAdmin()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$flashSale = new FlashSale();
$message = '';
$error = '';

// Xử lý tạo Flash Sale mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';
        
        if ($name && $startTime && $endTime) {
            $flashSaleId = $flashSale->create([
                'name' => $name,
                'description' => $description,
                'start_time' => $startTime,
                'end_time' => $endTime
            ]);
            $message = 'Tạo Flash Sale thành công!';
        } else {
            $error = 'Vui lòng điền đầy đủ thông tin!';
        }
    }
    
    if ($_POST['action'] === 'add_product') {
        $flashSaleId = (int)$_POST['flash_sale_id'];
        $productId = (int)$_POST['product_id'];
        $flashPrice = (int)$_POST['flash_price'];
        $flashStock = (int)$_POST['flash_stock'];
        
        if ($flashSaleId && $productId && $flashPrice && $flashStock) {
            $flashSale->addProduct($flashSaleId, $productId, $flashPrice, $flashStock);
            $message = 'Thêm sản phẩm vào Flash Sale thành công!';
        }
    }
}

// Xóa Flash Sale
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM flash_sales WHERE id = ?");
    $stmt->execute([$id]);
    $message = 'Xóa Flash Sale thành công!';
}

// Lấy danh sách Flash Sales
$flashSales = $flashSale->getAll();

// Lấy danh sách sản phẩm để thêm vào Flash Sale
$productsStmt = $conn->query("SELECT id, name, price FROM products WHERE status = 'active' ORDER BY name");
$products = $productsStmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-bolt"></i> Quản lý Flash Sale</h1>
        <button type="button" class="btn btn-primary" onclick="openModal('createModal')">
            <i class="fas fa-plus"></i> Tạo Flash Sale
        </button>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên</th>
                        <th>Thời gian</th>
                        <th>Trạng thái</th>
                        <th>Sản phẩm</th>
                        <th>Đã bán</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($flashSales as $fs): ?>
                    <tr>
                        <td><?= $fs['id'] ?></td>
                        <td><?= htmlspecialchars($fs['name']) ?></td>
                        <td>
                            <small>
                                <?= date('d/m/Y H:i', strtotime($fs['start_time'])) ?><br>
                                đến <?= date('d/m/Y H:i', strtotime($fs['end_time'])) ?>
                            </small>
                        </td>
                        <td>
                            <?php 
                            $statusClass = [
                                'upcoming' => 'badge-pending',
                                'active' => 'badge-active',
                                'ended' => 'badge-inactive'
                            ];
                            $statusText = [
                                'upcoming' => 'Sắp diễn ra',
                                'active' => 'Đang diễn ra',
                                'ended' => 'Đã kết thúc'
                            ];
                            ?>
                            <span class="badge <?= $statusClass[$fs['status']] ?>">
                                <?= $statusText[$fs['status']] ?>
                            </span>
                        </td>
                        <td><?= $fs['product_count'] ?? 0 ?></td>
                        <td><?= $fs['total_sold'] ?? 0 ?></td>
                        <td class="actions">
                            <button class="btn btn-sm btn-edit" onclick="openAddProduct(<?= $fs['id'] ?>)" title="Thêm sản phẩm">
                                <i class="fas fa-plus"></i>
                            </button>
                            <a href="?delete=<?= $fs['id'] ?>" class="btn btn-sm btn-delete" 
                               onclick="return confirm('Xóa Flash Sale này?')" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($flashSales)): ?>
                    <tr><td colspan="7" class="text-center">Chưa có Flash Sale nào</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Tạo Flash Sale -->
<div id="createModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Tạo Flash Sale mới</h3>
            <button type="button" class="modal-close" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="create">
            <div class="modal-body">
                <div class="form-group">
                    <label>Tên Flash Sale</label>
                    <input type="text" name="name" required>
                </div>
                <div class="form-group">
                    <label>Mô tả</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label>Bắt đầu</label>
                        <input type="datetime-local" name="start_time" required>
                    </div>
                    <div class="form-group col-6">
                        <label>Kết thúc</label>
                        <input type="datetime-local" name="end_time" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('createModal')">Hủy</button>
                <button type="submit" class="btn btn-primary">Tạo</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Thêm sản phẩm -->
<div id="addProductModal" class="modal-overlay">
    <div class="modal">
        <div class="modal-header">
            <h3>Thêm sản phẩm vào Flash Sale</h3>
            <button type="button" class="modal-close" onclick="closeModal('addProductModal')">&times;</button>
        </div>
        <form method="POST">
            <input type="hidden" name="action" value="add_product">
            <input type="hidden" name="flash_sale_id" id="flashSaleIdInput">
            <div class="modal-body">
                <div class="form-group">
                    <label>Sản phẩm</label>
                    <select name="product_id" id="productSelect" required>
                        <option value="">-- Chọn sản phẩm --</option>
                        <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>">
                            <?= htmlspecialchars($p['name']) ?> - <?= number_format($p['price']) ?>đ
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-row">
                    <div class="form-group col-6">
                        <label>Giá Flash Sale</label>
                        <input type="number" name="flash_price" id="flashPriceInput" required>
                    </div>
                    <div class="form-group col-6">
                        <label>Số lượng</label>
                        <input type="number" name="flash_stock" value="10" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" onclick="closeModal('addProductModal')">Hủy</button>
                <button type="submit" class="btn btn-primary">Thêm</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(modalId) {
    document.getElementById(modalId).style.display = 'flex';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

function openAddProduct(flashSaleId) {
    document.getElementById('flashSaleIdInput').value = flashSaleId;
    openModal('addProductModal');
}

// Auto fill flash price when select product
document.getElementById('productSelect')?.addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const price = option.dataset.price;
    if (price) {
        document.getElementById('flashPriceInput').value = Math.round(price * 0.7);
    }
});

// Close modal when clicking outside
document.querySelectorAll('.modal-overlay').forEach(modal => {
    modal.addEventListener('click', function(e) {
        if (e.target === this) {
            this.style.display = 'none';
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
