<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/User.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VNB Sports - Cửa hàng cầu lông chính hãng</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Top Header -->
    <div class="top-header">
        <div class="container">
            <div class="top-header-content">
                <div class="logo">
                    <a href="index.php">
                        <img src="assets/images/logo.png" alt="VNB Sports">
                    </a>
                </div>
                <div class="contact-info">
                    <i class="fas fa-phone"></i>
                    <span>HOTLINE: <strong>0977508430</strong> | <strong>0338000308</strong></span>
                </div>
                <div class="store-system">
                    <i class="fas fa-map-marker-alt"></i>
                    <span>HỆ THỐNG CỬA HÀNG</span>
                </div>
                <div class="search-box">
                    <input type="text" placeholder="Tìm sản phẩm...">
                    <button><i class="fas fa-search"></i></button>
                </div>
                <div class="user-actions">
                    <a href="#">
                        <i class="fas fa-search"></i>
                        <span>TRA CỨU</span>
                    </a>
                    <div class="account-dropdown">
                        <a href="#" class="account-btn">
                            <i class="fas fa-user"></i>
                            <span>TÀI KHOẢN</span>
                        </a>
                        <div class="account-menu">
                            <?php if (User::isLoggedIn()): ?>
                            <div class="user-info-header">
                                <i class="fas fa-user-circle"></i>
                                <span><?= htmlspecialchars($_SESSION['user_name']) ?></span>
                            </div>
                            <a href="profile.php"><i class="fas fa-user-cog"></i> Tài khoản của tôi</a>
                            <a href="orders.php"><i class="fas fa-shopping-bag"></i> Đơn hàng</a>
                            <?php if (User::isAdmin()): ?>
                            <a href="admin/"><i class="fas fa-cog"></i> Quản trị</a>
                            <?php endif; ?>
                            <a href="logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                            <?php else: ?>
                            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập</a>
                            <a href="register.php"><i class="fas fa-user-plus"></i> Đăng ký</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <a href="cart.php" class="cart-icon">
                        <i class="fas fa-shopping-cart"></i>
                        <span>GIỎ HÀNG</span>
                        <span class="cart-count">0</span>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="main-nav">
        <div class="container">
            <ul class="nav-menu">
                <li><a href="index.php">Trang Chủ</a></li>
                <li class="has-dropdown">
                    <a href="#">Sản Phẩm <i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Vợt Cầu Lông</a></li>
                        <li><a href="#">Giày Cầu Lông</a></li>
                        <li><a href="#">Áo Cầu Lông</a></li>
                        <li><a href="#">Quần Cầu Lông</a></li>
                        <li><a href="#">Phụ Kiện</a></li>
                    </ul>
                </li>
                <li><a href="#">Sale Off</a></li>
                <li><a href="#">Tin Tức</a></li>
                <li><a href="#">Chính Sách Nhượng Quyền</a></li>
                <li class="has-dropdown">
                    <a href="#">Hướng Dẫn <i class="fas fa-chevron-down"></i></a>
                    <ul class="dropdown-menu">
                        <li><a href="#">Hướng dẫn mua hàng</a></li>
                        <li><a href="#">Hướng dẫn thanh toán</a></li>
                        <li><a href="#">Chính sách đổi trả</a></li>
                    </ul>
                </li>
                <li><a href="#">Giới Thiệu</a></li>
                <li><a href="#">Liên Hệ</a></li>
            </ul>
        </div>
    </nav>
