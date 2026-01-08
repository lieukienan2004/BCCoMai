<?php
require_once __DIR__ . '/../backend/config/config.php';

if (!User::isLoggedIn() || !User::isAdmin()) {
    header('Location: login.php');
    exit;
}

$newsletter = new Newsletter();
$message = '';

// Xóa subscriber
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $newsletter->delete($id);
    $message = 'Đã xóa subscriber!';
}

// Lấy danh sách subscribers
$status = $_GET['status'] ?? '';
$subscribers = $newsletter->getAll($status ?: null);
$totalActive = $newsletter->getCount('active');
$totalUnsubscribed = $newsletter->getCount('unsubscribed');

require_once 'includes/header.php';
?>

<div class="admin-content">
    <div class="page-header">
        <h1 class="page-title"><i class="fas fa-envelope"></i> Quản lý Newsletter</h1>
    </div>
    
    <?php if ($message): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
    </div>
    <?php endif; ?>
    
    <div class="stats-grid" style="grid-template-columns: repeat(2, 1fr); margin-bottom: 25px;">
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-user-check"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($totalActive) ?></h3>
                <p>Đang đăng ký</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon bg-danger">
                <i class="fas fa-user-times"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($totalUnsubscribed) ?></h3>
                <p>Đã hủy</p>
            </div>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <form class="filter-form" method="GET">
                <select name="status" onchange="this.form.submit()">
                    <option value="">Tất cả trạng thái</option>
                    <option value="active" <?= $status == 'active' ? 'selected' : '' ?>>Đang đăng ký</option>
                    <option value="unsubscribed" <?= $status == 'unsubscribed' ? 'selected' : '' ?>>Đã hủy</option>
                </select>
            </form>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Email</th>
                        <th>Tên</th>
                        <th>Trạng thái</th>
                        <th>Ngày đăng ký</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($subscribers as $sub): ?>
                    <tr>
                        <td><?= $sub['id'] ?></td>
                        <td><?= htmlspecialchars($sub['email']) ?></td>
                        <td><?= htmlspecialchars($sub['name'] ?? '-') ?></td>
                        <td>
                            <span class="badge <?= $sub['status'] == 'active' ? 'badge-active' : 'badge-inactive' ?>">
                                <?= $sub['status'] == 'active' ? 'Đang đăng ký' : 'Đã hủy' ?>
                            </span>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($sub['subscribed_at'])) ?></td>
                        <td>
                            <a href="?delete=<?= $sub['id'] ?>&status=<?= $status ?>" 
                               class="btn btn-sm btn-delete" 
                               onclick="return confirm('Xóa subscriber này?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($subscribers)): ?>
                    <tr><td colspan="6" class="text-center">Chưa có subscriber nào</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
