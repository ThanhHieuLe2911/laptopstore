# LaptopStore - Hệ thống Thương mại Điện tử

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql" alt="MySQL">
  <img src="https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=flat-square&logo=javascript" alt="JavaScript">
  <img src="https://img.shields.io/badge/CSS3-Custom-1572B6?style=flat-square&logo=css3" alt="CSS3">
  <img src="https://img.shields.io/badge/Status-Completed-28a745?style=flat-square" alt="Status">
</p>

## Giới thiệu

**LaptopStore** là website thương mại điện tử (E-Commerce) chuyên bán laptop, được xây dựng hoàn chỉnh với đầy đủ tính năng cho cả **người dùng mua hàng** lẫn **quản trị viên**.

Dự án thể hiện khả năng:
- Xây dựng hệ thống web động với PHP & MySQL
- Thiết kế giao diện Responsive (Desktop, Tablet, Mobile)
- Triển khai hệ thống Authentication với bảo mật
- Tích hợp API bên thứ ba (Email SMTP, VietQR)

---

## Công nghệ sử dụng

| Layer | Công nghệ |
|-------|-----------|
| **Backend** | PHP 7.x / 8.x (Vanilla PHP) |
| **Database** | MySQL (XAMPP) |
| **Frontend** | HTML5, CSS3 (Custom), JavaScript ES6+ |
| **Email Service** | PHPMailer Library (SMTP Gmail) |
| **UI Icons** | Font Awesome 6.x |
| **Storage** | localStorage (giỏ hàng), MySQL (đơn hàng) |

---

## Tính năng chính

### Người dùng (Customer)
- **Duyệt sản phẩm**: Trang chủ, danh sách, chi tiết sản phẩm
- **Tìm kiếm & Lọc**: Theo danh mục, hãng, mức giá
- **Giỏ hàng**: Thêm/sửa/xóa sản phẩm (localStorage)
- **Đặt hàng**: Thanh toán COD, chuyển khoản (VietQR)
- **Tài khoản**: Đăng ký/Đăng nhập/Quên mật khẩu (OTP Email)
- **Lịch sử đơn hàng**: Theo dõi trạng thái đơn hàng
- **Đánh giá sản phẩm**: Rating & Comment

### Quản trị (Admin Panel)
- **Dashboard**: Thống kê doanh thu, đơn hàng (Chart.js)
- **Quản lý sản phẩm**: CRUD đầy đủ với upload ảnh (Gallery)
- **Quản lý danh mục & hãng**: Tạo/đọc/cập nhật/xóa
- **Quản lý đơn hàng**: Cập nhật trạng thái (AJAX real-time)
- **Quản lý người dùng**: Xem danh sách, xóa tài khoản

---

## Kiến trúc hệ thống

```
laptopstore/
├── index.php                    # Trang chủ
├── login.php                    # Xác thực người dùng
├── cart.php                     # Giỏ hàng
├── checkout.php                 # Thanh toán
├── products.php                 # Danh sách sản phẩm
├── product-detail.php           # Chi tiết sản phẩm
│
├── admin/                       # Panel quản trị
│   ├── index.php               # Dashboard
│   ├── products.php            # Quản lý sản phẩm
│   ├── product_create.php      # Thêm sản phẩm
│   ├── orders.php              # Quản lý đơn hàng
│   └── api/update_status.php   # API AJAX
│
├── includes/
│   ├── config.php              # Cấu hình DB & Helpers
│   ├── header.php              # Template header
│   ├── footer.php              # Template footer
│   └── send_mail.php          # Gửi email (PHPMailer)
│
└── assets/
    ├── css/style.css          # Stylesheet
    └── js/main.js             # JavaScript logic
```

---

## Bảo mật

| Yếu tố | Phương thức |
|--------|-------------|
| Password | `password_hash()` / `password_verify()` (bcrypt) |
| SQL Injection | Prepared Statements (`bind_param`) |
| XSS | `htmlspecialchars()` output encoding |
| Session | Kiểm tra role-based access control |
| File Upload | Validate extension & unique naming |

---

## Cách cài đặt

### 1. Yêu cầu
- PHP 7.4+ hoặc 8.x
- MySQL 5.7+
- XAMPP / WAMP / LAMP

### 2. Các bước

```bash
# 1. Clone repository
git clone https://github.com/ThanhHieuLe2911/laptopstore.git
cd laptopstore

# 2. Tạo database
# - Mở phpMyAdmin
# - Tạo database tên: laptop_store
# - Import file SQL (nếu có)

# 3. Cấu hình môi trường
cp .env.example .env
# Chỉnh sửa .env với thông tin database

# 4. Khởi chạy
# - Bật Apache & MySQL trong XAMPP
# - Truy cập: http://localhost/laptopstore/
```

### 3. Truy cập

| Vai trò | Đường dẫn |
|---------|-----------|
| Khách hàng | `http://localhost/laptopstore/` |
| Quản trị | `http://localhost/laptopstore/admin/login.php` |

---

## Các kỹ năng thể hiện qua dự án

- **Backend Development**: PHP procedural, MySQL queries, REST API
- **Frontend Development**: Responsive CSS, Vanilla JavaScript (ES6+)
- **Database Design**: Schema thiết kế chuẩn, quan hệ bảng
- **Authentication System**: OTP, Password hashing, Session management
- **Third-party Integration**: PHPMailer, VietQR Payment
- **Version Control**: Git, GitHub
- **Environment Management**: .env configuration

---

## Tác giả

**Họ tên**: Lê Thanh Hiếu

**GitHub**: [ThanhHieuLe2911](https://github.com/ThanhHieuLe2911)

---

> Dự án được phát triển với mục tiêu học tập và thực hành. Mọi thông tin sản phẩm chỉ mang tính demo.
