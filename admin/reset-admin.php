<?php
require_once __DIR__ . '/../backend/config/config.php';

$conn = getConnection();

// Tạo password hash mới cho "123123"
$newPassword = password_hash('123123', PASSWORD_DEFAULT);

// Cập nhật password và email
$stmt = $conn->prepare("UPDATE users SET email = 'admin@admin.com', password = ? WHERE email = 'admin@vnbsports.com' OR email = 'admin' OR email = 'admin@admin.com'");
$stmt->execute([$newPassword]);

if ($stmt->rowCount() > 0) {
    echo "Đã reset password thành công!<br>";
    echo "Tài khoản: admin@admin.com<br>";
    echo "Mật khẩu: 123123<br><br>";
    echo "<a href='login.php'>Đăng nhập ngay</a>";
} else {
    // Nếu chưa có admin, tạo mới
    $stmt = $conn->prepare("INSERT INTO users (fullname, email, phone, password, role) VALUES (?, ?, ?, ?, 'admin')");
    $stmt->execute(['Admin VNB', 'admin@admin.com', '0977508430', $newPassword]);
    echo "Đã tạo tài khoản admin!<br>";
    echo "Email: admin<br>";
    echo "Password: 123123<br><br>";
    echo "<a href='login.php'>Đăng nhập ngay</a>";
}
?>
