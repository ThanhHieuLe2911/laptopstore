<?php
// Đảm bảo session đã được bật
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!defined('SITE_NAME')) {
    require_once 'config.php';
}
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?><?php echo SITE_NAME; ?> - Công Nghệ Đỉnh Cao</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* --- CSS DROP DOWN USER ĐÃ CHỈNH SỬA --- */
        .user-menu { position: relative; cursor: pointer; height: 100%; display: flex; align-items: center; }
        
        .user-dropdown {
            display: none;
            position: absolute;
            top: 100%; /* Hiện ngay dưới user name */
            right: 0;
            background: white;
            box-shadow: 0 5px 20px rgba(0,0,0,0.15); /* Bóng đổ đậm hơn chút cho nổi */
            border-radius: 8px;
            width: 220px; /* Tăng chiều rộng để chữ không bị xuống dòng */
            z-index: 1000;
            overflow: hidden;
            border: 1px solid #f0f0f0; /* Viền nhẹ */
            animation: fadeIn 0.2s ease-in-out;
        }

        /* Hiệu ứng hiện menu */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .user-menu:hover .user-dropdown { display: block; }

        .user-dropdown a {
            display: flex; /* Dùng flex để icon và chữ thẳng hàng */
            align-items: center;
            gap: 10px; /* Khoảng cách giữa icon và chữ */
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            font-size: 14px;
            transition: 0.2s;
            border-bottom: 1px solid #f9f9f9;
        }

        .user-dropdown a:last-child { border-bottom: none; }

        .user-dropdown a:hover { 
            background: #f8f9fa; 
            color: #0d6efd; 
            padding-left: 20px; /* Hiệu ứng đẩy nhẹ sang phải khi hover */
        }
        
        .user-dropdown a i {
            width: 20px; /* Cố định chiều rộng icon để chữ thẳng hàng */
            text-align: center;
            color: #6c757d;
        }
        
        .user-dropdown a:hover i { color: #0d6efd; }

        .nav-item.active-user i { color: #0d6efd; }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="container">
            <div class="top-left">
                <span><i class="fas fa-phone-alt"></i> 1900 1234 (8:00 - 22:00)</span>
                <span><i class="fas fa-map-marker-alt"></i> Hệ thống 50 cửa hàng toàn quốc</span>
            </div>
            <div class="top-right">
                <a href="#"><i class="fab fa-facebook-f"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
            </div>
        </div>
    </div>

    <nav>
        <div class="container">
            <div class="menu-toggle" onclick="toggleMobileMenu()">
                <i class="fas fa-bars"></i>
            </div>
            
            <a href="index.php" class="logo"><?php echo SITE_NAME; ?></a>
            
            <div class="search-bar">
                <input type="text" id="searchInput" placeholder="Tìm kiếm tên máy, dòng chip, hãng...">
                <button class="search-btn" onclick="handleHeaderSearch()">
                    <i class="fas fa-search"></i>
                </button>
            </div>

            <div class="nav-menu">
                <a href="index.php" class="nav-link <?php echo $current_page == 'index' ? 'active' : ''; ?>">Trang chủ</a>
                <a href="products.php" class="nav-link <?php echo $current_page == 'products' ? 'active' : ''; ?>">Sản phẩm</a>
                <a href="about.php" class="nav-link <?php echo $current_page == 'about' ? 'active' : ''; ?>">Giới thiệu</a>
                <a href="contact.php" class="nav-link <?php echo $current_page == 'contact' ? 'active' : ''; ?>">Liên hệ</a>
            </div>

            <div class="nav-icons">
                <div class="nav-item" title="Yêu thích">
                    <i class="far fa-heart"></i>
                    <span class="badge" id="wishlistCount">0</span>
                </div>
                <div class="nav-item" onclick="toggleCart()" title="Giỏ hàng">
                    <i class="fas fa-shopping-bag"></i>
                    <span class="badge" id="cartCount">0</span>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="nav-item user-menu active-user">
                        <i class="fas fa-user-check"></i>
                        <span style="font-size: 13px; font-weight: 600; margin-left: 5px;">
                            <?php echo explode(' ', $_SESSION['user_name'])[0]; ?>
                        </span>
                        
                        <div class="user-dropdown">
                            <a href="profile.php">
                                <i class="fas fa-user-circle"></i> Thông tin tài khoản
                            </a>
                            <a href="my-orders.php">
                                <i class="fas fa-history"></i> Đơn hàng
                            </a>
                            <a href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Đăng xuất
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="nav-item" title="Đăng nhập / Đăng ký">
                        <i class="far fa-user"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <div class="mobile-menu-container" id="mobileMenu">
        <a href="index.php" class="mobile-link">Trang chủ</a>
        <a href="products.php" class="mobile-link">Sản phẩm</a>
        <a href="about.php" class="mobile-link">Giới thiệu</a>
        <a href="contact.php" class="mobile-link">Liên hệ</a>
        <div class="mobile-divider"></div>
        <a href="#" class="mobile-link"><i class="far fa-heart"></i> Yêu thích</a>
        <a href="cart.php" class="mobile-link"><i class="fas fa-shopping-bag"></i> Giỏ hàng</a>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="profile.php" class="mobile-link text-primary"><i class="fas fa-user-circle"></i> Tài khoản của tôi</a>
            <a href="my-orders.php" class="mobile-link"><i class="fas fa-history"></i> Lịch sử đơn hàng</a>
            <a href="logout.php" class="mobile-link"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
        <?php else: ?>
            <a href="login.php" class="mobile-link"><i class="far fa-user"></i> Đăng nhập / Đăng ký</a>
        <?php endif; ?>
    </div>

    <div class="cart-wrapper" id="cartWrapper">
        <div class="cart-overlay" onclick="toggleCart()"></div>
        <div class="cart-sidebar">
            <div class="cart-header">
                <h3>Giỏ hàng (<span id="cartItemCountHeader">0</span>)</h3>
                <div class="close-cart" onclick="toggleCart()">&times;</div>
            </div>
            <div class="cart-items" id="cartItemsContainer">
                <div class="empty-cart">
                    <i class="fas fa-shopping-basket"></i>
                    <p>Giỏ hàng đang trống</p>
                </div>
            </div>
            <div class="cart-footer">
                <div class="total-row">
                    <span>Tổng tiền:</span>
                    <span class="total-price" id="cartTotal">0đ</span>
                </div>
                <a href="cart.php" class="btn btn-block" onclick="toggleCart()">XEM GIỎ HÀNG</a>
                <a href="checkout.php" class="btn btn-block btn-dark">THANH TOÁN</a>
            </div>
        </div>
    </div>

    <div class="toast" id="toast">
        <i class="fas fa-check-circle"></i>
        <span id="toastMsg">Thông báo</span>
    </div>