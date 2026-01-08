# 🏸 VNB Sports Shop

Website bán đồ thể thao cầu lông - Badminton Equipment E-commerce

## 📋 Giới thiệu

VNB Sports Shop là website thương mại điện tử chuyên cung cấp các sản phẩm thể thao cầu lông như vợt, giày, quần áo, phụ kiện...

## ✨ Tính năng

### 👤 Người dùng
- Đăng ký / Đăng nhập (Email & Google OAuth)
- Quản lý thông tin cá nhân
- Giỏ hàng & Thanh toán
- Theo dõi đơn hàng
- Danh sách yêu thích (Wishlist)
- Đánh giá sản phẩm
- Tích điểm thưởng (Loyalty Points)

### 🛒 Sản phẩm
- Danh mục sản phẩm
- Tìm kiếm & Lọc sản phẩm
- So sánh sản phẩm
- Sản phẩm đã xem gần đây
- Flash Sale
- Combo khuyến mãi

### 💳 Thanh toán
- Thanh toán COD
- Thanh toán chuyển khoản ngân hàng (SePay API)
- Mã giảm giá (Coupon)

### 👨‍💼 Admin
- Quản lý sản phẩm
- Quản lý đơn hàng
- Quản lý người dùng
- Quản lý khuyến mãi
- Thống kê doanh thu

## 🛠️ Công nghệ sử dụng

- **Backend:** PHP
- **Database:** MySQL
- **Frontend:** HTML, CSS, JavaScript
- **Payment:** SePay API
- **Authentication:** Google OAuth 2.0
- **Email:** Gmail SMTP

## 📁 Cấu trúc thư mục

```
├── admin/              # Trang quản trị
├── assets/             # CSS, JS, Images
├── backend/
│   ├── classes/        # PHP Classes
│   ├── config/         # Cấu hình
│   └── database/       # SQL files
├── config/             # Database config
├── database/           # SQL migration files
├── frontend/           # Trang người dùng
├── images/             # Hình ảnh sản phẩm
└── includes/           # Header, Footer
```

## ⚙️ Cài đặt

1. Clone repository
```bash
git clone https://github.com/lieukienan2004/BCCoMai.git
```

2. Import database
```bash
# Import file database/vnb_sports.sql vào MySQL
```

3. Cấu hình
```bash
# Copy .env.example thành .env và điền thông tin
cp .env.example .env
```

4. Chạy với XAMPP
```
- Đặt project vào thư mục htdocs
- Truy cập: http://localhost/shopcaulong
```

## 👥 Tác giả

- **Liễu Kiện An** - [GitHub](https://github.com/lieukienan2004)

## 📄 License

MIT License
