# LaptopStore - Tài liệu Dự án Website Bán Laptop

## 1. Tổng quan Dự án

**LaptopStore** là một website thương mại điện tử (e-commerce) chuyên bán laptop, được xây dựng bằng **PHP thuần (Vanilla PHP)** kết hợp với **MySQL** (thông qua XAMPP). Dự án có đầy đủ tính năng cho cả người dùng mua hàng lẫn quản trị viên.

---

## 2. Thông tin kỹ thuật

| Thành phần | Công nghệ |
|------------|------------|
| Backend | PHP 7.x/8.x (Vanilla PHP) |
| Database | MySQL (XAMPP) |
| Frontend | HTML5, CSS3, JavaScript (ES6+) |
| Email | PHPMailer Library (SMTP Gmail) |
| UI Framework | Font Awesome 6.x, Custom CSS |
| Local Storage | localStorage (giỏ hàng, wishlist) |

### Cấu hình Database
```php
DB_HOST: localhost
DB_USER: root
DB_PASS: (trống)
DB_NAME: laptop_store
Site URL: http://localhost/laptopstore
```

---

## 3. Cấu trúc thư mục

```
laptopstore/
├── index.php                    # Trang chủ
├── login.php                    # Đăng nhập / Đăng ký
├── logout.php                   # Đăng xuất
├── profile.php                  # Trang cá nhân người dùng
├── cart.php                     # Trang giỏ hàng
├── checkout.php                 # Trang thanh toán
├── products.php                # Danh sách sản phẩm (có lọc, phân trang)
├── product-detail.php          # Chi tiết sản phẩm
├── about.php                    # Trang giới thiệu
├── contact.php                  # Trang liên hệ
├── my-orders.php               # Lịch sử đơn hàng
├── my-order-detail.php         # Chi tiết đơn hàng
│
├── actions/
│   └── add-review.php          # Xử lý thêm đánh giá sản phẩm
│
├── admin/                      # PANEL QUẢN TRỊ
│   ├── index.php               # Dashboard tổng quan
│   ├── login.php               # Đăng nhập Admin
│   ├── logout.php              # Đăng xuất Admin
│   ├── _auth.php              # Middleware xác thực Admin
│   ├── users.php              # Quản lý người dùng
│   ├── products.php           # Quản lý sản phẩm
│   ├── product_create.php     # Thêm sản phẩm mới
│   ├── product_edit.php       # Chỉnh sửa sản phẩm
│   ├── product_delete.php     # Xóa sản phẩm
│   ├── categories.php         # Quản lý danh mục
│   ├── brands.php             # Quản lý hãng sản xuất
│   ├── orders.php             # Quản lý đơn hàng
│   ├── order_detail.php       # Chi tiết đơn hàng
│   └── api/
│       └── update_status.php  # API cập nhật trạng thái (AJAX)
│
├── includes/
│   ├── config.php             # Cấu hình DB & Helper functions
│   ├── header.php             # Header dùng chung
│   ├── footer.php             # Footer dùng chung
│   ├── send_mail.php          # Hàm gửi email (PHPMailer)
│   └── PHPMailer/             # Thư viện PHPMailer
│
├── assets/
│   ├── css/
│   │   └── style.css         # Stylesheet chính
│   └── js/
│       └── main.js            # JavaScript chính
│
└── uploads/                    # Thư mục lưu ảnh sản phẩm
    └── products/
```

---

## 4. Các bảng Database

### 4.1. `users` - Người dùng
| Trường | Kiểu | Mô tả |
|--------|-------|--------|
| id | INT (PK) | ID người dùng |
| full_name | VARCHAR | Họ tên |
| email | VARCHAR | Email (unique) |
| phone | VARCHAR | Số điện thoại |
| address | TEXT | Địa chỉ |
| password | VARCHAR | Mật khẩu (đã hash) |
| role | ENUM | 'admin' hoặc 'user' |
| created_at | DATETIME | Ngày tạo |

### 4.2. `products` - Sản phẩm
| Trường | Kiểu | Mô tả |
|--------|-------|--------|
| id | INT (PK) | ID sản phẩm |
| name | VARCHAR | Tên sản phẩm |
| brand | VARCHAR | Hãng (Dell, ASUS, Macbook...) |
| category_slug | VARCHAR | Slug danh mục |
| price | INT | Giá bán |
| old_price | INT | Giá cũ (để tính giảm giá) |
| image | VARCHAR | Đường dẫn ảnh |
| gallery_images | JSON | Danh sách ảnh gallery |
| specs | TEXT | Thông số tóm tắt |
| description | TEXT | Mô tả sản phẩm |
| detail_specs | JSON | Thông số kỹ thuật chi tiết |
| rating | FLOAT | Điểm đánh giá |
| reviews | INT | Số lượng đánh giá |
| stock | INT | Số lượng tồn kho |
| created_at | DATETIME | Ngày tạo |

### 4.3. `categories` - Danh mục
| Trường | Kiểu | Mô tả |
|--------|-------|--------|
| id | INT (PK) | ID danh mục |
| name | VARCHAR | Tên danh mục |
| slug | VARCHAR | Slug URL (gaming, office, macbook...) |

### 4.4. `brands` - Hãng sản xuất
| Trường | Kiểu | Mô tả |
|--------|-------|--------|
| id | INT (PK) | ID hãng |
| name | VARCHAR | Tên hãng (Dell, HP, Apple...) |

### 4.5. `orders` - Đơn hàng
| Trường | Kiểu | Mô tả |
|--------|-------|--------|
| id | INT (PK) | ID đơn hàng |
| order_code | VARCHAR | Mã đơn (LPS00000001) |
| user_id | INT (FK) | ID người dùng |
| full_name | VARCHAR | Họ tên khách |
| phone | VARCHAR | SĐT |
| email | VARCHAR | Email |
| address | TEXT | Địa chỉ giao hàng |
| city | VARCHAR | Tỉnh/Thành phố |
| district | VARCHAR | Quận/Huyện |
| note | TEXT | Ghi chú |
| shipping_method | ENUM | 'standard' hoặc 'express' |
| payment_method | ENUM | 'cod', 'bank', 'card', 'momo' |
| payment_status | ENUM | 'paid', 'unpaid' |
| subtotal | INT | Tổng tiền hàng |
| shipping_fee | INT | Phí vận chuyển |
| discount | INT | Giảm giá |
| total | INT | Tổng cộng |
| status | ENUM | Trạng thái đơn hàng |
| created_at | DATETIME | Ngày đặt |

### 4.6. `order_items` - Chi tiết đơn hàng
| Trường | Kiểu | Mô tả |
|--------|-------|--------|
| id | INT (PK) | ID |
| order_id | INT (FK) | ID đơn hàng |
| product_id | INT (FK) | ID sản phẩm |
| product_name | VARCHAR | Tên sản phẩm |
| price | INT | Giá tại thời điểm mua |
| quantity | INT | Số lượng |
| subtotal | INT | Thành tiền |

### 4.7. `product_reviews` - Đánh giá sản phẩm
| Trường | Kiểu | Mô tả |
|--------|-------|--------|
| id | INT (PK) | ID |
| product_id | INT (FK) | ID sản phẩm |
| user_id | INT (FK) | ID người dùng |
| rating | INT | Điểm (1-5) |
| comment | TEXT | Bình luận |
| status | INT | Trạng thái duyệt (0/1) |
| created_at | DATETIME | Ngày tạo |

---

## 5. Tính năng cho Người dùng (Frontend)

### 5.1. Xem sản phẩm
- **Trang chủ**: Hero banner, danh mục sản phẩm, sản phẩm nổi bật
- **Danh sách sản phẩm**: Lọc theo danh mục, hãng, mức giá
- **Chi tiết sản phẩm**: Hình ảnh, thông số, mô tả, đánh giá
- **Tìm kiếm**: Tìm theo tên, hãng, thông số

### 5.2. Giỏ hàng
- Lưu trữ bằng **localStorage** (không cần đăng nhập)
- Tăng/giảm số lượng
- Xóa sản phẩm
- Tính phí vận chuyển (miễn phí cho đơn > 5 triệu)
- Áp dụng mã giảm giá (coupon)

### 5.3. Đặt hàng & Thanh toán
- **Hình thức thanh toán**:
  - COD (Thanh toán khi nhận hàng)
  - Chuyển khoản ngân hàng (MB Bank)
  - Tích hợp **VietQR** để quét mã thanh toán
- **Phương thức vận chuyển**:
  - Tiêu chuẩn (3-5 ngày) - Miễn phí
  - Nhanh (1-2 ngày) - 100.000đ
- **Ưu đãi thành viên**:
  - New: 0% (mới)
  - Member: 3% (tổng mua >= 15 triệu)
  - Loyal: 6% (tổng mua >= 50 triệu)
  - VIP: 10% (tổng mua >= 100 triệu)

### 5.4. Tài khoản người dùng
- **Đăng ký**: Xác thực bằng **OTP qua email**
- **Đăng nhập**: Email + mật khẩu (hash bằng `password_hash`)
- **Quên mật khẩu**: Khôi phục qua OTP
- **Hồ sơ**: Cập nhật thông tin cá nhân
- **Lịch sử đơn hàng**: Xem các đơn đã đặt

### 5.5. Đánh giá sản phẩm
- Gửi đánh giá (sao + bình luận)
- Xem đánh giá từ người dùng khác

---

## 6. Tính năng Quản trị (Admin Panel)

### 6.1. Dashboard
- **Thống kê tổng quan**: Doanh thu, đơn hàng, sản phẩm
- **Biểu đồ Chart.js**:
  - Doanh thu 7 ngày gần nhất (Line Chart)
  - Doanh thu 12 tháng gần nhất (Bar Chart)
- **Trạng thái đơn hàng**: Chờ xử lý, đang xử lý, đang giao, hoàn thành, đã hủy
- **Đơn hàng mới nhất**: 8 đơn gần đây

### 6.2. Quản lý Sản phẩm
- **Danh sách**: Bảng với hình ảnh, giá, tồn kho, đánh giá real-time
- **Lọc**: Theo danh mục, hãng, tồn kho
- **Sắp xếp**: Theo giá, tên, mới nhất
- **Tìm kiếm**: Theo tên, hãng
- **CRUD**:
  - Thêm sản phẩm (upload ảnh đại diện + gallery)
  - Sửa sản phẩm
  - Xóa sản phẩm

### 6.3. Quản lý Danh mục & Hãng
- **Danh mục**: Tạo slug tự động từ tên
- **Hãng**: CRUD cơ bản

### 6.4. Quản lý Đơn hàng
- **Lọc theo trạng thái**: Tab giao diện
- **Cập nhật trạng thái AJAX**: Không cần reload trang
- **Cập nhật thanh toán**: Đã thanh toán / Chưa thanh toán
- **Chi tiết đơn hàng**: Thông tin khách, sản phẩm, tổng tiền

### 6.5. Quản lý Người dùng
- **Danh sách khách hàng** (không hiển thị admin)
- **Tìm kiếm**: Theo tên, email
- **Xóa người dùng** (có xác nhận)

---

## 7. Các API Endpoints

### 7.1. API Admin (`admin/api/update_status.php`)
```json
// Cập nhật trạng thái đơn hàng
POST /admin/api/update_status.php
Body: {
  "action": "update_order_status",
  "order_id": 123,
  "new_status": "completed"
}

// Cập nhật trạng thái thanh toán
POST /admin/api/update_status.php
Body: {
  "action": "update_payment_status",
  "order_id": 123,
  "new_pay_status": "paid"
}
```

### 7.2. Actions (`actions/add-review.php`)
- Xử lý thêm đánh giá sản phẩm từ người dùng

---

## 8. Helper Functions (trong `config.php`)

```php
// Format tiền VND
formatMoney($amount) // VD: 25.000.000đ

// Tính % giảm giá
calculateDiscount($oldPrice, $newPrice) // VD: 15%

// Lấy sản phẩm theo ID
getProductById($id)
```

---

## 9. JavaScript Functions (trong `main.js`)

### Giỏ hàng
```javascript
addToCart(productId)       // Thêm vào giỏ
changeQuantity(index, d)   // +/- số lượng
removeFromCart(index)      // Xóa sản phẩm
getCart()                  // Lấy giỏ hàng từ localStorage
saveCart(cart)             // Lưu giỏ hàng
toggleCart()               // Mở/đóng sidebar giỏ hàng
```

### Yêu thích (Wishlist)
```javascript
toggleWishlist(btn, productId) // Thêm/xóa wishlist
getWishlist()                   // Lấy danh sách yêu thích
```

### UI
```javascript
showToast(message, type)   // Hiện thông báo
quickView(productId)       // Popup xem nhanh
handleHeaderSearch()       // Tìm kiếm từ header
toggleMobileMenu()         // Menu mobile
```

---

## 10. Email System (PHPMailer)

- **Host**: smtp.gmail.com
- **Port**: 587 (STARTTLS)
- **Sử dụng**: Gửi mã OTP khi đăng ký, quên mật khẩu

### Email Templates
1. **Xác thực đăng ký**: Mã OTP kích hoạt tài khoản
2. **Khôi phục mật khẩu**: Mã OTP đặt lại mật khẩu

---

## 11. Bảo mật

- **Password**: Hash bằng `password_hash()` (bcrypt)
- **SQL Injection**: Sử dụng `Prepared Statements`
- **XSS**: Sử dụng `htmlspecialchars()`
- **Session**: Kiểm tra `$_SESSION['admin_id']` cho Admin
- **File Upload**: Kiểm tra extension, đặt tên file unique

---

## 12. Responsive Design

- Desktop: Bố cục 2-3 cột
- Tablet: Sidebar thu gọn
- Mobile: Menu hamburger, grid 1 cột

---

## 13. Các file chính cần lưu ý

| File | Mục đích |
|------|----------|
| `includes/config.php` | Cấu hình database, helper functions |
| `includes/header.php` | Navigation, cart sidebar, toast |
| `includes/footer.php` | Footer, scripts |
| `admin/_auth.php` | Middleware bảo vệ trang admin |
| `assets/js/main.js` | Toàn bộ logic JavaScript phía client |
| `assets/css/style.css` | Stylesheet cho cả frontend và admin |

---

## 14. Cách chạy dự án

1. **Cài đặt XAMPP** và bật Apache + MySQL
2. **Import database**: Tạo database `laptop_store` và import file SQL
3. **Cấu hình** `includes/config.php` với thông tin database
4. **Truy cập**:
   - Frontend: `http://localhost/laptopstore/`
   - Admin: `http://localhost/laptopstore/admin/login.php`

### Tài khoản mặc định
- **Admin**: Tạo thủ công trong database với `role = 'admin'`
- **User**: Đăng ký qua trang đăng ký (sẽ có OTP gửi email)

---

## 15. Đường dẫn file thực tế trong hệ thống

```
C:\xampp\htdocs\laptopstore\laptopstore\
```

> **Lưu ý**: Dự án nằm trong thư mục `laptopstore` lồng nhau.

---

## 16. Tác giả / Phiên bản

- **Phiên bản**: 1.0
- **Ngày cập nhật gần nhất**: Tháng 12/2025 - Tháng 5/2026
- **Email gửi mail**: 2224801030200@student.tdmu.edu.vn
