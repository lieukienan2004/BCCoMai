<?php
require_once __DIR__ . '/../backend/config/config.php';

if (!User::isLoggedIn() || !User::isAdmin()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$message = '';
$error = '';

// Thông báo từ redirect
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'added') {
        $message = 'Thêm sản phẩm mới thành công!';
    } elseif ($_GET['success'] === 'updated') {
        $message = 'Cập nhật sản phẩm thành công!';
    } elseif ($_GET['success'] === '1') {
        $message = 'Thao tác thành công!';
    }
}

if (isset($_GET['error']) && $_GET['error'] === 'notfound') {
    $error = 'Không tìm thấy sản phẩm!';
}

// Xử lý xóa sản phẩm
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Xóa sản phẩm thành công!';
    } catch (PDOException $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

// Lấy danh sách sản phẩm
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT p.*, c.name as category_name FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND p.name LIKE ?";
    $params[] = "%$search%";
}
if ($category) {
    $sql .= " AND p.category_id = ?";
    $params[] = $category;
}

$sql .= " ORDER BY p.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Lấy danh mục theo cấu trúc parent-child
$categoriesQuery = $conn->query("
    SELECT c.*, p.name as parent_name 
    FROM categories c 
    LEFT JOIN categories p ON c.parent_id = p.id 
    ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NULL DESC, c.name
");
$allCategories = $categoriesQuery->fetchAll();

// Tổ chức danh mục theo nhóm
$parentCategories = [];
$childCategories = [];
foreach ($allCategories as $cat) {
    if ($cat['parent_id'] === null) {
        $parentCategories[$cat['id']] = $cat;
    } else {
        $childCategories[$cat['parent_id']][] = $cat;
    }
}

require_once 'includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1 class="page-title">Quản lý sản phẩm</h1>
        <a href="product-form.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> Thêm sản phẩm
        </a>
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
        <div class="card-header">
            <form class="filter-form" method="GET">
                <input type="text" name="search" placeholder="Tìm kiếm..." value="<?= htmlspecialchars($search) ?>">
                <select name="category">
                    <option value="">Tất cả danh mục</option>
                    <?php foreach ($parentCategories as $parentId => $parent): ?>
                    <optgroup label="<?= htmlspecialchars($parent['name']) ?>">
                        <?php if (isset($childCategories[$parentId])): ?>
                            <?php foreach ($childCategories[$parentId] as $child): ?>
                            <option value="<?= $child['id'] ?>" <?= $category == $child['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($child['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </optgroup>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn"><i class="fas fa-search"></i> Lọc</button>
            </form>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hình ảnh</th>
                        <th>Tên sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Giá</th>
                        <th>Kho</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= $product['id'] ?></td>
                        <td>
                            <img src="../images/<?= $product['image'] ?: 'no-image.png' ?>" 
                                 alt="" class="product-thumb">
                        </td>
                        <td><?= htmlspecialchars($product['name']) ?></td>
                        <td><?= htmlspecialchars($product['category_name'] ?? 'Chưa phân loại') ?></td>
                        <td>
                            <?php if ($product['sale_price']): ?>
                            <span class="price-old"><?= number_format($product['price']) ?>đ</span>
                            <span class="price-sale"><?= number_format($product['sale_price']) ?>đ</span>
                            <?php else: ?>
                            <?= number_format($product['price']) ?>đ
                            <?php endif; ?>
                        </td>
                        <td><?= $product['stock'] ?></td>
                        <td>
                            <?php if ($product['status'] == 'inactive'): ?>
                            <span class="badge badge-out-of-stock">Hết hàng</span>
                            <?php else: ?>
                            <span class="badge badge-active">Còn hàng</span>
                            <?php endif; ?>
                        </td>
                        <td class="actions">
                            <a href="product-form.php?id=<?= $product['id'] ?>" class="btn btn-sm btn-edit" title="Sửa">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="?delete=<?= $product['id'] ?>" class="btn btn-sm btn-delete" 
                               onclick="return confirm('Bạn có chắc muốn xóa?')" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                    <tr><td colspan="8" class="text-center">Không có sản phẩm nào</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
