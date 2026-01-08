<?php
require_once __DIR__ . '/../backend/config/config.php';

if (!User::isLoggedIn() || !User::isAdmin()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();
$message = '';

// Cập nhật trạng thái
if (isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    // Lấy thông tin đơn hàng trước khi cập nhật
    $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    $oldStatus = $order['status'] ?? '';
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);
    
    // Cộng điểm thưởng khi đơn hàng hoàn thành
    if ($status === 'completed' && $oldStatus !== 'completed' && $order['user_id']) {
        $loyalty = new Loyalty();
        $earnedPoints = $loyalty->earnPoints($order['user_id'], $orderId, $order['total']);
        if ($earnedPoints > 0) {
            $message = "Cập nhật trạng thái thành công! Đã cộng {$earnedPoints} điểm thưởng cho khách hàng.";
        } else {
            $message = 'Cập nhật trạng thái thành công!';
        }
    } else {
        $message = 'Cập nhật trạng thái thành công!';
    }
}

// Lọc đơn hàng
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT o.*, u.fullname as user_name FROM orders o 
        LEFT JOIN users u ON o.user_id = u.id WHERE 1=1";
$params = [];

if ($status) {
    $sql .= " AND o.status = ?";
    $params[] = $status;
}
if ($search) {
    $sql .= " AND (o.fullname LIKE ? OR o.phone LIKE ? OR o.id = ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = $search;
}

$sql .= " ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$statuses = [
    'pending' => 'Chờ xử lý',
    'confirmed' => 'Đã xác nhận',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];

require_once 'includes/header.php';
?>

<div class="admin-content">
    <h1 class="page-title">Quản lý đơn hàng</h1>
    
    <?php if ($message): ?>
    <div class="alert alert-success"><?= $message ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <form class="filter-form" method="GET">
                <input type="text" name="search" placeholder="Tìm theo tên, SĐT, mã ĐH..." value="<?= htmlspecialchars($search) ?>">
                <select name="status">
                    <option value="">Tất cả trạng thái</option>
                    <?php foreach ($statuses as $key => $val): ?>
                    <option value="<?= $key ?>" <?= $status == $key ? 'selected' : '' ?>><?= $val ?></option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn"><i class="fas fa-search"></i> Lọc</button>
            </form>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Mã ĐH</th>
                        <th>Khách hàng</th>
                        <th>SĐT</th>
                        <th>Tổng tiền</th>
                        <th>Thanh toán</th>
                        <th>Trạng thái</th>
                        <th>Ngày đặt</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td>#<?= $order['id'] ?></td>
                        <td><?= htmlspecialchars($order['fullname']) ?></td>
                        <td><?= htmlspecialchars($order['phone']) ?></td>
                        <td><?= number_format($order['total']) ?>đ</td>
                        <td><?= $order['payment_method'] == 'cod' ? 'COD' : 'Chuyển khoản' ?></td>
                        <td>
                            <form method="POST" class="status-form">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status" onchange="this.form.submit()">
                                    <?php foreach ($statuses as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= $order['status'] == $key ? 'selected' : '' ?>><?= $val ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="hidden" name="update_status" value="1">
                            </form>
                        </td>
                        <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                        <td class="actions">
                            <a href="order-detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-edit" title="Chi tiết">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                    <tr><td colspan="8" class="text-center">Không có đơn hàng nào</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
