<?php
require_once __DIR__ . '/../backend/config/config.php';

if (!User::isLoggedIn() || !User::isAdmin()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$message = '';
$error = '';

// Xử lý xóa
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $message = 'Xóa danh mục thành công!';
    } catch (PDOException $e) {
        $error = 'Không thể xóa danh mục này!';
    }
}

// Xử lý thêm/sửa
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $slug = trim($_POST['slug']) ?: createSlug($name);
    $status = $_POST['status'];
    $id = $_POST['id'] ?? null;
    
    // Upload hình ảnh
    $image = $_POST['current_image'] ?? '';
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = '../images/';
        $fileName = time() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $uploadDir . $fileName)) {
            $image = $fileName;
        }
    }
    
    try {
        if ($id) {
            $stmt = $conn->prepare("UPDATE categories SET name=?, slug=?, image=?, status=? WHERE id=?");
            $stmt->execute([$name, $slug, $image, $status, $id]);
            $message = 'Cập nhật danh mục thành công!';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, slug, image, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $slug, $image, $status]);
            $message = 'Thêm danh mục thành công!';
        }
    } catch (PDOException $e) {
        $error = 'Lỗi: ' . $e->getMessage();
    }
}

function createSlug($str) {
    $str = preg_replace('/(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)/', 'a', $str);
    $str = preg_replace('/(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)/', 'e', $str);
    $str = preg_replace('/(ì|í|ị|ỉ|ĩ)/', 'i', $str);
    $str = preg_replace('/(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)/', 'o', $str);
    $str = preg_replace('/(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)/', 'u', $str);
    $str = preg_replace('/(ỳ|ý|ỵ|ỷ|ỹ)/', 'y', $str);
    $str = preg_replace('/(đ)/', 'd', $str);
    $str = strtolower($str);
    $str = preg_replace('/[^a-z0-9\s-]/', '', $str);
    $str = preg_replace('/[\s-]+/', '-', $str);
    return trim($str, '-');
}

// Lấy danh sách danh mục
$categories = $conn->query("SELECT c.*, (SELECT COUNT(*) FROM products WHERE category_id = c.id) as product_count 
                            FROM categories c ORDER BY c.name")->fetchAll();

// Lấy danh mục cần sửa
$editCategory = null;
if (isset($_GET['edit'])) {
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $editCategory = $stmt->fetch();
}

require_once 'includes/header.php';
?>

<div class="admin-content">
    <h1 class="page-title">Quản lý danh mục</h1>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-4">
            <div class="card">
                <div class="card-header">
                    <h3><?= $editCategory ? 'Sửa danh mục' : 'Thêm danh mục' ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" class="form">
                        <?php if ($editCategory): ?>
                        <input type="hidden" name="id" value="<?= $editCategory['id'] ?>">
                        <input type="hidden" name="current_image" value="<?= $editCategory['image'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Tên danh mục *</label>
                            <input type="text" name="name" value="<?= htmlspecialchars($editCategory['name'] ?? '') ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Slug</label>
                            <input type="text" name="slug" value="<?= htmlspecialchars($editCategory['slug'] ?? '') ?>" 
                                   placeholder="Tự động tạo">
                        </div>
                        
                        <div class="form-group">
                            <label>Hình ảnh</label>
                            <input type="file" name="image" accept="image/*">
                            <?php if ($editCategory && $editCategory['image']): ?>
                            <img src="../images/<?= $editCategory['image'] ?>" alt="" style="max-width: 100px; margin-top: 10px;">
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label>Trạng thái</label>
                            <select name="status">
                                <option value="active" <?= ($editCategory['status'] ?? '') == 'active' ? 'selected' : '' ?>>Hiển thị</option>
                                <option value="inactive" <?= ($editCategory['status'] ?? '') == 'inactive' ? 'selected' : '' ?>>Ẩn</option>
                            </select>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $editCategory ? 'Cập nhật' : 'Thêm mới' ?>
                            </button>
                            <?php if ($editCategory): ?>
                            <a href="categories.php" class="btn">Hủy</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-8">
            <div class="card">
                <div class="card-header">
                    <h3>Danh sách danh mục</h3>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Hình</th>
                                <th>Tên danh mục</th>
                                <th>Slug</th>
                                <th>Sản phẩm</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><?= $cat['id'] ?></td>
                                <td>
                                    <img src="../images/<?= $cat['image'] ?: 'no-image.png' ?>" alt="" class="product-thumb">
                                </td>
                                <td><?= htmlspecialchars($cat['name']) ?></td>
                                <td><?= htmlspecialchars($cat['slug']) ?></td>
                                <td><?= $cat['product_count'] ?></td>
                                <td>
                                    <span class="badge badge-<?= $cat['status'] ?>">
                                        <?= $cat['status'] == 'active' ? 'Hiển thị' : 'Ẩn' ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="?edit=<?= $cat['id'] ?>" class="btn btn-sm btn-edit" title="Sửa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?= $cat['id'] ?>" class="btn btn-sm btn-delete" 
                                       onclick="return confirm('Bạn có chắc muốn xóa?')" title="Xóa">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
