<?php
/**
 * Class Mailer - Gửi email qua Gmail SMTP
 */
class Mailer {
    private $smtpHost = 'smtp.gmail.com';
    private $smtpPort = 587;
    private $smtpUser;
    private $smtpPass;
    private $fromEmail;
    private $fromName = 'VNB Sports';
    
    public function __construct() {
        // Lấy cấu hình từ config
        $this->smtpUser = defined('SMTP_USER') ? SMTP_USER : '';
        $this->smtpPass = defined('SMTP_PASS') ? SMTP_PASS : '';
        $this->fromEmail = defined('SMTP_FROM') ? SMTP_FROM : $this->smtpUser;
    }
    
    /**
     * Gửi email đặt lại mật khẩu
     */
    public function sendPasswordReset($toEmail, $userName, $resetLink) {
        $subject = 'Đặt lại mật khẩu - VNB Sports';
        
        $body = $this->getPasswordResetTemplate($userName, $resetLink);
        
        return $this->send($toEmail, $subject, $body);
    }
    
    /**
     * Gửi email xác nhận đơn hàng
     */
    public function sendOrderConfirmation($toEmail, $orderData) {
        $subject = 'Xác nhận đơn hàng #' . $orderData['id'] . ' - VNB Sports';
        
        $body = $this->getOrderConfirmationTemplate($orderData);
        
        return $this->send($toEmail, $subject, $body);
    }
    
    /**
     * Gửi email
     */
    public function send($to, $subject, $body, $isHtml = true) {
        // Kiểm tra cấu hình
        if (empty($this->smtpUser) || empty($this->smtpPass)) {
            error_log('SMTP chưa được cấu hình');
            return false;
        }
        
        try {
            // Sử dụng PHPMailer nếu có, hoặc dùng mail() function
            if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
                return $this->sendWithPHPMailer($to, $subject, $body, $isHtml);
            }
            
            // Fallback: Gửi qua SMTP trực tiếp
            return $this->sendViaSMTP($to, $subject, $body, $isHtml);
            
        } catch (Exception $e) {
            error_log('Lỗi gửi email: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gửi email qua SMTP (không cần thư viện)
     */
    private function sendViaSMTP($to, $subject, $body, $isHtml = true) {
        $socket = @fsockopen('ssl://' . $this->smtpHost, 465, $errno, $errstr, 30);
        
        if (!$socket) {
            error_log("Không thể kết nối SMTP: $errstr ($errno)");
            return false;
        }
        
        // Đọc response
        $this->getResponse($socket);
        
        // EHLO
        fwrite($socket, "EHLO localhost\r\n");
        $this->getResponse($socket);
        
        // AUTH LOGIN
        fwrite($socket, "AUTH LOGIN\r\n");
        $this->getResponse($socket);
        
        // Username
        fwrite($socket, base64_encode($this->smtpUser) . "\r\n");
        $this->getResponse($socket);
        
        // Password
        fwrite($socket, base64_encode($this->smtpPass) . "\r\n");
        $response = $this->getResponse($socket);
        
        if (strpos($response, '235') === false) {
            error_log('SMTP Auth failed: ' . $response);
            fclose($socket);
            return false;
        }
        
        // MAIL FROM
        fwrite($socket, "MAIL FROM:<{$this->fromEmail}>\r\n");
        $this->getResponse($socket);
        
        // RCPT TO
        fwrite($socket, "RCPT TO:<{$to}>\r\n");
        $this->getResponse($socket);
        
        // DATA
        fwrite($socket, "DATA\r\n");
        $this->getResponse($socket);
        
        // Headers và Body
        $headers = "From: {$this->fromName} <{$this->fromEmail}>\r\n";
        $headers .= "To: {$to}\r\n";
        $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: " . ($isHtml ? "text/html" : "text/plain") . "; charset=UTF-8\r\n";
        $headers .= "\r\n";
        
        fwrite($socket, $headers . $body . "\r\n.\r\n");
        $this->getResponse($socket);
        
        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);
        
        return true;
    }
    
    private function getResponse($socket) {
        $response = '';
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) == ' ') break;
        }
        return $response;
    }
    
    /**
     * Template email đặt lại mật khẩu
     */
    private function getPasswordResetTemplate($userName, $resetLink) {
        return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f5f5f5;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f5f5; padding: 40px 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #ff6600, #ff8c00); padding: 30px; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px;">VNB Sports</h1>
                        </td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding: 40px 30px;">
                            <h2 style="color: #333; margin: 0 0 20px; font-size: 22px;">Xin chào ' . htmlspecialchars($userName) . ',</h2>
                            
                            <p style="color: #555; font-size: 16px; line-height: 1.6; margin: 0 0 20px;">
                                Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn tại VNB Sports.
                            </p>
                            
                            <p style="color: #555; font-size: 16px; line-height: 1.6; margin: 0 0 30px;">
                                Nhấn vào nút bên dưới để đặt lại mật khẩu:
                            </p>
                            
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center">
                                        <a href="' . $resetLink . '" style="display: inline-block; background: linear-gradient(135deg, #ff6600, #ff8c00); color: #ffffff; text-decoration: none; padding: 15px 40px; border-radius: 8px; font-size: 16px; font-weight: bold;">
                                            Đặt lại mật khẩu
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                            <p style="color: #888; font-size: 14px; line-height: 1.6; margin: 30px 0 0;">
                                Link này sẽ hết hạn sau <strong>1 giờ</strong>.
                            </p>
                            
                            <p style="color: #888; font-size: 14px; line-height: 1.6; margin: 15px 0 0;">
                                Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.
                            </p>
                            
                            <hr style="border: none; border-top: 1px solid #eee; margin: 30px 0;">
                            
                            <p style="color: #999; font-size: 12px; margin: 0;">
                                Nếu nút không hoạt động, sao chép link sau vào trình duyệt:<br>
                                <a href="' . $resetLink . '" style="color: #ff6600; word-break: break-all;">' . $resetLink . '</a>
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f9f9f9; padding: 20px 30px; text-align: center;">
                            <p style="color: #999; font-size: 13px; margin: 0;">
                                © 2024 VNB Sports. All rights reserved.<br>
                                Hotline: 0977508430 | Email: info@vnbsports.com
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }
    
    /**
     * Template email xác nhận đơn hàng
     */
    private function getOrderConfirmationTemplate($order) {
        // Template cho đơn hàng (có thể mở rộng sau)
        return '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body>
    <h2>Cảm ơn bạn đã đặt hàng!</h2>
    <p>Mã đơn hàng: #' . $order['id'] . '</p>
    <p>Tổng tiền: ' . number_format($order['total']) . 'đ</p>
</body>
</html>';
    }
}
