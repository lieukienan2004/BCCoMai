<?php
require_once __DIR__ . '/../backend/config/config.php';
require_once ADMIN_PATH . '/includes/auth.php';

$sepay = new SePay();
$transactions = [];
$error = '';

// Lấy giao dịch từ SePay
$result = $sepay->getTransactions(50);
if ($result['success']) {
    $transactions = $result['transactions'];
} else {
    $error = $result['message'];
}

// Xử lý xác nhận thủ công
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_order'])) {
    $orderId = (int)$_POST['order_id'];
    $conn = getConnection();
    $stmt = $conn->prepare("UPDATE orders SET payment_status = 'paid', status = 'confirmed' WHERE id = ?");
    $stmt->execute([$orderId]);
    header('Location: payments.php?success=1');
    exit;
}

require_once ADMIN_PATH . '/includes/header.php';
?>

<div class="admin-content">
    <div class="content-header">
        <h1><i class="fas fa-credit-card"></i> Quản lý thanh toán</h1>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">Đã xác nhận thanh toán thành công!</div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="payments-grid">
        <!-- Đơn hàng chờ thanh toán -->
        <div class="payment-section">
            <h2><i class="fas fa-clock"></i> Đơn hàng chờ thanh toán</h2>
            <?php
            $conn = getConnection();
            $stmt = $conn->query("
                SELECT * FROM orders 
                WHERE payment_method = 'bank' 
                AND (payment_status = 'pending' OR payment_status IS NULL)
                ORDER BY created_at DESC
                LIMIT 20
            ");
            $pendingOrders = $stmt->fetchAll();
            ?>
            
            <?php if (empty($pendingOrders)): ?>
            <p class="no-data">Không có đơn hàng nào chờ thanh toán</p>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Mã ĐH</th>
                        <th>Khách hàng</th>
                        <th>Số tiền</th>
                        <th>Nội dung CK</th>
                        <th>Ngày đặt</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pendingOrders as $order): ?>
                    <tr>
                        <td><strong>#<?= $order['id'] ?></strong></td>
                        <td><?= htmlspecialchars($order['fullname']) ?></td>
                        <td class="amount"><?= number_format($order['total']) ?>đ</td>
                        <td><code>DH<?= $order['id'] ?></code></td>
                        <td><?= date('d/m H:i', strtotime($order['created_at'])) ?></td>
                        <td>
                            <form method="POST" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <button type="submit" name="confirm_order" class="btn-confirm" 
                                        onclick="return confirm('Xác nhận đã nhận thanh toán?')">
                                    <i class="fas fa-check"></i> Xác nhận
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        
        <!-- Giao dịch từ SePay -->
        <div class="payment-section">
            <h2><i class="fas fa-exchange-alt"></i> Giao dịch gần đây (SePay)</h2>
            
            <?php if (empty($transactions)): ?>
            <p class="no-data">Chưa có giao dịch nào</p>
            <?php else: ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Thời gian</th>
                        <th>Số tiền</th>
                        <th>Nội dung</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($transactions as $trans): ?>
                    <tr>
                        <td><?= date('d/m H:i', strtotime($trans['transaction_date'] ?? 'now')) ?></td>
                        <td class="amount">+<?= number_format($trans['amount_in'] ?? 0) ?>đ</td>
                        <td><small><?= htmlspecialchars($trans['transaction_content'] ?? '') ?></small></td>
                        <td>
                            <?php 
                            $content = strtoupper($trans['transaction_content'] ?? '');
                            if (preg_match('/DH(\d+)/', $content, $m)) {
                                echo '<span class="badge badge-success">Đơn #' . $m[1] . '</span>';
                            } else {
                                echo '<span class="badge badge-secondary">Khác</span>';
                            }
                            ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.payments-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 25px;
}

.payment-section {
    background: white;
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
}

.payment-section h2 {
    margin: 0 0 20px;
    font-size: 18px;
    color: #333;
    display: flex;
    align-items: center;
    gap: 10px;
}

.payment-section h2 i {
    color: #667eea;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th, .data-table td {
    padding: 12px 10px;
    text-align: left;
    border-bottom: 1px solid #eee;
    font-size: 14px;
}

.data-table th {
    background: #f8f9fa;
    font-weight: 600;
}

.data-table .amount {
    color: #27ae60;
    font-weight: 600;
}

.data-table code {
    background: #e3f2fd;
    padding: 3px 8px;
    border-radius: 4px;
    font-size: 13px;
}

.btn-confirm {
    background: #27ae60;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 5px;
    cursor: pointer;
    font-size: 13px;
}

.btn-confirm:hover {
    background: #219a52;
}

.badge {
    padding: 4px 10px;
    border-radius: 12px;
    font-size: 12px;
}

.badge-success {
    background: #d4edda;
    color: #155724;
}

.badge-secondary {
    background: #e9ecef;
    color: #6c757d;
}

.no-data {
    text-align: center;
    color: #999;
    padding: 30px;
}

.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.alert-success {
    background: #d4edda;
    color: #155724;
}

.alert-error {
    background: #f8d7da;
    color: #721c24;
}

@media (max-width: 1200px) {
    .payments-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require_once ADMIN_PATH . '/includes/footer.php'; ?>
