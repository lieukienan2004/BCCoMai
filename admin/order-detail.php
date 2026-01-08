<?php
require_once __DIR__ . '/../backend/config/config.php';

if (!User::isLoggedIn() || !User::isAdmin()) {
    header('Location: login.php');
    exit;
}

$conn = getConnection();

if (!isset($_GET['id'])) {
    header('Location: orders.php');
    exit;
}

$orderId = (int)$_GET['id'];

// Lấy thông tin đơn hàng
$stmt = $conn->prepare("SELECT o.*, u.email as user_email FROM orders o 
                        LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->execute([$orderId]);
$order = $stmt->fetch();

if (!$order) {
    header('Location: orders.php');
    exit;
}

// Lấy chi tiết đơn hàng
$stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll();

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
    <div class="page-header">
        <h1 class="page-title">Chi tiết đơn hàng #<?= $order['id'] ?></h1>
        <a href="orders.php" class="btn"><i class="fas fa-arrow-left"></i> Quay lại</a>
    </div>
    
    <div class="row">
        <div class="col-8">
            <div class="card">
                <div class="card-header">
                    <h3>Sản phẩm đặt mua</h3>
                </div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Giá</th>
                                <th>Số lượng</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td><?= number_format($item['price']) ?>đ</td>
                                <td><?= $item['quantity'] ?></td>
                                <td><?= number_format($item['price'] * $item['quantity']) ?>đ</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-right"><strong>Tổng cộng:</strong></td>
                                <td><strong><?= number_format($order['total']) ?>đ</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            
            <?php if ($order['note']): ?>
            <div class="card">
                <div class="card-header">
                    <h3>Ghi chú</h3>
                </div>
                <div class="card-body">
                    <p><?= nl2br(htmlspecialchars($order['note'])) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="col-4">
            <div class="card">
                <div class="card-header">
                    <h3>Thông tin đơn hàng</h3>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <label>Trạng thái:</label>
                        <span class="badge badge-<?= $order['status'] ?>"><?= $statuses[$order['status']] ?></span>
                    </div>
                    <div class="info-item">
                        <label>Ngày đặt:</label>
                        <span><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Thanh toán:</label>
                        <span><?= $order['payment_method'] == 'cod' ? 'Thanh toán khi nhận hàng' : 'Chuyển khoản ngân hàng' ?></span>
                    </div>
                </div>
            </div>
            
            <div class="card">
                <div class="card-header">
                    <h3>Thông tin khách hàng</h3>
                </div>
                <div class="card-body">
                    <div class="info-item">
                        <label>Họ tên:</label>
                        <span><?= htmlspecialchars($order['fullname']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email:</label>
                        <span><?= htmlspecialchars($order['email']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Điện thoại:</label>
                        <span><?= htmlspecialchars($order['phone']) ?></span>
                    </div>
                    <div class="info-item">
                        <label>Địa chỉ:</label>
                        <span><?= htmlspecialchars($order['address']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
