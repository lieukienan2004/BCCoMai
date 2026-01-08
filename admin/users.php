<?php
require_once __DIR__ . '/../backend/config/config.php';

if (!User::isLoggedIn() || !User::isAdmin()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$message = '';
$error = '';

// Cập nhật trạng thái
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $stmt = $conn->prepare("UPDATE users SET status = IF(status='active','inactive','active') WHERE id = ? AND role != 'admin'");
    $stmt->execute([$id]);
    $message = 'Cập nhật trạng thái thành công!';
}

// Xóa người dùng
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->execute([$id]);
        $message = 'Xóa người dùng thành công!';
    } catch (PDOException $e) {
        $error = 'Không thể xóa người dùng này!';
    }
}

// Lọc
$search = $_GET['search'] ?? '';
$role = $_GET['role'] ?? '';

$sql = "SELECT u.*, (SELECT COUNT(*) FROM orders WHERE user_id = u.id) as order_count FROM users u WHERE 1=1";
$params = [];

if ($search) {
    $sql .= " AND (u.fullname LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($role) {
    $sql .= " AND u.role = ?";
    $params[] = $role;
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="admin-content">
    <h1 class="page-title">Quản lý người dùng</h1>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <form class="filter-form" method="GET">
                <input type="text" name="search" placeholder="Tìm theo tên, email, SĐT..." value="<?= htmlspecialchars($search) ?>">
                <select name="role">
                    <option value="">Tất cả vai trò</option>
                    <option value="user" <?= $role == 'user' ? 'selected' : '' ?>>Khách hàng</option>
                    <option value="admin" <?= $role == 'admin' ? 'selected' : '' ?>>Admin</option>
                </select>
                <button type="submit" class="btn"><i class="fas fa-search"></i> Lọc</button>
            </form>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>Điện thoại</th>
                        <th>Vai trò</th>
                        <th>Đơn hàng</th>
                        <th>Trạng thái</th>
                        <th>Ngày tạo</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['fullname']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['phone'] ?? '-') ?></td>
                        <td>
                            <span class="badge badge-<?= $user['role'] ?>">
                                <?= $user['role'] == 'admin' ? 'Admin' : 'Khách hàng' ?>
                            </span>
                        </td>
                        <td><?= $user['order_count'] ?></td>
                        <td>
                            <span class="badge badge-<?= $user['status'] ?>">
                                <?= $user['status'] == 'active' ? 'Hoạt động' : 'Khóa' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y', strtotime($user['created_at'])) ?></td>
                        <td class="actions">
                            <?php if ($user['role'] != 'admin'): ?>
                            <a href="?toggle=<?= $user['id'] ?>" class="btn btn-sm btn-edit" 
                               title="<?= $user['status'] == 'active' ? 'Khóa' : 'Mở khóa' ?>">
                                <i class="fas fa-<?= $user['status'] == 'active' ? 'lock' : 'unlock' ?>"></i>
                            </a>
                            <a href="?delete=<?= $user['id'] ?>" class="btn btn-sm btn-delete" 
                               onclick="return confirm('Bạn có chắc muốn xóa?')" title="Xóa">
                                <i class="fas fa-trash"></i>
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
