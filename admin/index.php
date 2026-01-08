<?php
require_once __DIR__ . '/../backend/config/config.php';

// Kiểm tra đăng nhập admin
if (!User::isLoggedIn() || !User::isAdmin()) {
    header('Location: login.php');
    exit;
}

// Lấy thống kê
$conn = getConnection();

// Tổng sản phẩm
$stmt = $conn->query("SELECT COUNT(*) as total FROM products");
$totalProducts = $stmt->fetch()['total'];

// Tổng đơn hàng
$stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$totalOrders = $stmt->fetch()['total'];

// Tổng người dùng
$stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'user'");
$totalUsers = $stmt->fetch()['total'];

// Doanh thu (tính từ đơn hàng không bị hủy)
$stmt = $conn->query("SELECT SUM(total) as revenue FROM orders WHERE status != 'cancelled'");
$totalRevenue = $stmt->fetch()['revenue'] ?? 0;

// Đơn hàng mới nhất
$stmt = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
$recentOrders = $stmt->fetchAll();

// Doanh thu theo danh mục (tính từ đơn hàng không bị hủy)
$stmt = $conn->query("SELECT c.name, SUM(oi.price * oi.quantity) as revenue 
                      FROM order_items oi 
                      JOIN products p ON oi.product_id = p.id 
                      JOIN categories c ON p.category_id = c.id 
                      JOIN orders o ON oi.order_id = o.id 
                      WHERE o.status != 'cancelled'
                      GROUP BY c.id, c.name 
                      ORDER BY revenue DESC");
$revenueByCategory = $stmt->fetchAll();

// Thống kê đơn hàng theo trạng thái
$stmt = $conn->query("SELECT status, COUNT(*) as count FROM orders GROUP BY status");
$ordersByStatus = $stmt->fetchAll();

require_once 'includes/header.php';
?>

<div class="admin-content">
    <h1 class="page-title">Dashboard</h1>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon bg-primary">
                <i class="fas fa-box"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($totalProducts) ?></h3>
                <p>Sản phẩm</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-success">
                <i class="fas fa-shopping-cart"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($totalOrders) ?></h3>
                <p>Đơn hàng</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-warning">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($totalUsers) ?></h3>
                <p>Khách hàng</p>
            </div>
        </div>
        
        <div class="stat-card">
            <div class="stat-icon bg-danger">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="stat-info">
                <h3><?= number_format($totalRevenue) ?>đ</h3>
                <p>Doanh thu</p>
            </div>
        </div>
    </div>
    
    <div class="dashboard-row charts-row">
        <div class="card chart-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie"></i> Doanh thu theo danh mục</h3>
            </div>
            <div class="card-body">
                <canvas id="revenueByCategoryChart"></canvas>
            </div>
        </div>
        
        <div class="card chart-card">
            <div class="card-header">
                <h3><i class="fas fa-chart-pie"></i> Đơn hàng theo trạng thái</h3>
            </div>
            <div class="card-body">
                <canvas id="ordersByStatusChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="dashboard-row">
        <div class="card">
            <div class="card-header">
                <h3><i class="fas fa-clock"></i> Đơn hàng gần đây</h3>
                <a href="orders.php" class="btn btn-sm">Xem tất cả</a>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Mã ĐH</th>
                            <th>Khách hàng</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td>#<?= $order['id'] ?></td>
                            <td><?= htmlspecialchars($order['fullname']) ?></td>
                            <td><?= number_format($order['total']) ?>đ</td>
                            <td>
                                <span class="badge badge-<?= $order['status'] ?>">
                                    <?= getOrderStatus($order['status']) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($recentOrders)): ?>
                        <tr><td colspan="5" class="text-center">Chưa có đơn hàng nào</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
function getOrderStatus($status) {
    $statuses = [
        'pending' => 'Chờ xử lý',
        'confirmed' => 'Đã xác nhận',
        'shipping' => 'Đang giao',
        'completed' => 'Hoàn thành',
        'cancelled' => 'Đã hủy'
    ];
    return $statuses[$status] ?? $status;
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Biểu đồ doanh thu theo danh mục
const categoryData = <?= json_encode($revenueByCategory) ?>;
const categoryLabels = categoryData.map(item => item.name);
const categoryValues = categoryData.map(item => parseFloat(item.revenue));

new Chart(document.getElementById('revenueByCategoryChart'), {
    type: 'pie',
    data: {
        labels: categoryLabels.length > 0 ? categoryLabels : ['Chưa có dữ liệu'],
        datasets: [{
            data: categoryValues.length > 0 ? categoryValues : [1],
            backgroundColor: [
                '#e53935', '#43a047', '#fb8c00', '#1976d2', 
                '#8e24aa', '#00acc1', '#ffb300', '#5c6bc0'
            ],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 15, font: { size: 12 } }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        let value = context.raw;
                        return context.label + ': ' + new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                    }
                }
            }
        }
    }
});

// Biểu đồ đơn hàng theo trạng thái
const statusData = <?= json_encode($ordersByStatus) ?>;
const statusLabels = {
    'pending': 'Chờ xử lý',
    'confirmed': 'Đã xác nhận', 
    'shipping': 'Đang giao',
    'completed': 'Hoàn thành',
    'cancelled': 'Đã hủy'
};
const statusColors = {
    'pending': '#fb8c00',
    'confirmed': '#1976d2',
    'shipping': '#00acc1',
    'completed': '#43a047',
    'cancelled': '#e53935'
};

new Chart(document.getElementById('ordersByStatusChart'), {
    type: 'doughnut',
    data: {
        labels: statusData.length > 0 ? statusData.map(item => statusLabels[item.status]) : ['Chưa có dữ liệu'],
        datasets: [{
            data: statusData.length > 0 ? statusData.map(item => parseInt(item.count)) : [1],
            backgroundColor: statusData.length > 0 ? statusData.map(item => statusColors[item.status]) : ['#ccc'],
            borderWidth: 2,
            borderColor: '#fff'
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 15, font: { size: 12 } }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.label + ': ' + context.raw + ' đơn';
                    }
                }
            }
        }
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
