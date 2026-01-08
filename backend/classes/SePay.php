<?php
/**
 * Class SePay - Tích hợp API SePay để xác nhận thanh toán tự động
 */
class SePay {
    private $apiToken;
    private $apiUrl = 'https://my.sepay.vn/userapi/transactions/list';
    
    public function __construct() {
        $this->apiToken = 'XIMITJNW79COS0SXNYR2DE3GPSIL8JOBW3FYUZP5DEWFFQCQLX44KKJOVVL1P9HN';
    }
    
    /**
     * Lấy danh sách giao dịch từ SePay
     */
    public function getTransactions($limit = 20) {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->apiUrl . '?limit=' . $limit,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            return ['success' => false, 'message' => 'API Error: ' . $httpCode];
        }
        
        $data = json_decode($response, true);
        return ['success' => true, 'transactions' => $data['transactions'] ?? []];
    }
    
    /**
     * Kiểm tra giao dịch theo nội dung chuyển khoản
     * @param string $content Nội dung cần tìm (VD: DH123)
     * @param int $amount Số tiền cần kiểm tra
     */
    public function checkPayment($content, $amount) {
        $result = $this->getTransactions(50);
        
        if (!$result['success']) {
            return $result;
        }
        
        foreach ($result['transactions'] as $trans) {
            // Kiểm tra nội dung chứa mã đơn hàng và số tiền khớp
            $transContent = strtoupper($trans['transaction_content'] ?? '');
            $transAmount = (int)($trans['amount_in'] ?? 0);
            
            if (strpos($transContent, strtoupper($content)) !== false && $transAmount >= $amount) {
                return [
                    'success' => true,
                    'paid' => true,
                    'transaction' => $trans,
                    'message' => 'Đã xác nhận thanh toán'
                ];
            }
        }
        
        return [
            'success' => true,
            'paid' => false,
            'message' => 'Chưa tìm thấy giao dịch phù hợp'
        ];
    }
    
    /**
     * Xử lý webhook từ SePay
     */
    public function handleWebhook($data) {
        if (empty($data['content']) || empty($data['transferAmount'])) {
            return ['success' => false, 'message' => 'Invalid webhook data'];
        }
        
        $content = strtoupper($data['content']);
        $amount = (int)$data['transferAmount'];
        
        // Tìm mã đơn hàng trong nội dung (format: DH123 hoặc SHOPCAULONG123)
        if (preg_match('/(?:DH|SHOPCAULONG)(\d+)/i', $content, $matches)) {
            $orderId = (int)$matches[1];
            
            $conn = getConnection();
            
            // Lấy thông tin đơn hàng
            $stmt = $conn->prepare("SELECT * FROM orders WHERE id = ? AND payment_method = 'bank'");
            $stmt->execute([$orderId]);
            $order = $stmt->fetch();
            
            if ($order && $order['payment_status'] !== 'paid') {
                // Kiểm tra số tiền
                if ($amount >= $order['total']) {
                    // Cập nhật trạng thái thanh toán
                    $stmt = $conn->prepare("
                        UPDATE orders 
                        SET payment_status = 'paid', 
                            status = 'confirmed',
                            updated_at = NOW()
                        WHERE id = ?
                    ");
                    $stmt->execute([$orderId]);
                    
                    // Lưu log giao dịch
                    $stmt = $conn->prepare("
                        INSERT INTO payment_logs (order_id, amount, transaction_id, content, status, created_at)
                        VALUES (?, ?, ?, ?, 'success', NOW())
                    ");
                    $stmt->execute([
                        $orderId, 
                        $amount, 
                        $data['id'] ?? '', 
                        $content
                    ]);
                    
                    return [
                        'success' => true,
                        'message' => 'Đã xác nhận thanh toán đơn hàng #' . $orderId
                    ];
                }
            }
        }
        
        return ['success' => false, 'message' => 'Không tìm thấy đơn hàng phù hợp'];
    }
}
