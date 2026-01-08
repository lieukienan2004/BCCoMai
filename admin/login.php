<?php
require_once __DIR__ . '/../backend/config/config.php';

// Nếu đã đăng nhập admin, chuyển về dashboard
if (User::isLoggedIn() && User::isAdmin()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        $user = new User();
        $result = $user->login($email, $password);
        
        if ($result['success']) {
            if ($result['user']['role'] === 'admin') {
                header('Location: index.php');
                exit;
            } else {
                $user->logout();
                $error = 'Bạn không có quyền truy cập trang quản trị!';
            }
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin - VNB Sports</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <div class="login-logo">
                <img src="../images/logo.jpg" alt="VNB Sports">
                <h1>VNB Admin</h1>
                <p style="color: #64748b; font-size: 14px; margin-top: 8px;">Đăng nhập để quản lý hệ thống</p>
            </div>
            
            <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>
            
            <form method="POST" class="login-form">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Tài khoản</label>
                    <input type="text" name="email" placeholder="Nhập email admin" 
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                
                <div class="form-group">
                    <label><i class="fas fa-lock"></i> Mật khẩu</label>
                    <input type="password" name="password" placeholder="Nhập mật khẩu" required>
                </div>
                
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Đăng nhập
                </button>
            </form>
            
            <div class="login-footer">
                <a href="../frontend/index.php"><i class="fas fa-arrow-left"></i> Về trang chủ</a>
            </div>
        </div>
    </div>
</body>
</html>
