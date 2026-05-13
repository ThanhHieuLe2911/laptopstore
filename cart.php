<?php
require_once 'includes/config.php';
$page_title = 'Giỏ hàng';
include 'includes/header.php';

// --- PHẦN LOGIC PHP ĐỂ LẤY HẠNG THÀNH VIÊN ---
$userRank = 'New';
$discountPercent = 0; // Mặc định 0%

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Tính tổng tiền đã mua (status = completed)
    $stmt = $conn->prepare("SELECT SUM(total) as total_spent FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $totalSpent = (int)($res['total_spent'] ?? 0);
    $stmt->close();

    // Logic xét hạng
    if ($totalSpent >= 100000000) {
        $userRank = 'VIP';
        $discountPercent = 10;
    } elseif ($totalSpent >= 50000000) {
        $userRank = 'Loyal';
        $discountPercent = 6;
    } elseif ($totalSpent >= 15000000) {
        $userRank = 'Member';
        $discountPercent = 3;
    }
}
// --- KẾT THÚC LOGIC PHP ---
?>

<div class="breadcrumb">
    <div class="container">
        <a href="index.php">Trang chủ</a>
        <i class="fas fa-chevron-right"></i>
        <span>Giỏ hàng</span>
    </div>
</div>

<section class="cart-page">
    <div class="container">
        <h1 class="page-title">Giỏ hàng của bạn</h1>

        <div class="cart-layout">
            <div class="cart-main">
                <div id="cartTableContainer">
                    </div>
            </div>

            <div class="cart-summary-card">
                <h3>Tổng đơn hàng</h3>
                
                <div class="summary-row">
                    <span>Tạm tính</span>
                    <span id="subtotal">0đ</span>
                </div>
                
                <div class="summary-row">
                    <span>Phí vận chuyển</span>
                    <span id="shippingFee">0đ</span>
                </div>
                
                <div class="summary-row" style="color: #28a745;">
                    <span>
                        <i class="fas fa-crown"></i> Ưu đãi thành viên 
                        <span class="badge badge-warning" style="font-size: 11px; vertical-align: middle; margin-left: 3px; background: #ffc107; color: #000; padding: 2px 6px; border-radius: 4px;">
                            <?php echo $userRank; ?>
                        </span>
                    </span>
                    <span>-<?php echo $discountPercent; ?>%</span>
                </div>

                <div class="summary-row">
                    <span>Giảm giá</span>
                    <span class="discount" id="discount">0đ</span>
                </div>
                
                <div class="summary-divider"></div>
                
                <div class="summary-row summary-total">
                    <span>Tổng cộng</span>
                    <span class="total-price" id="totalPrice">0đ</span>
                </div>
                
                <input type="hidden" id="rankDiscountPercent" value="<?php echo $discountPercent; ?>">

                <div class="coupon-form">
                    <input type="text" placeholder="Nhập mã giảm giá" id="couponInput">
                    <button class="btn btn-outline" onclick="applyCoupon()">Áp dụng</button>
                </div>
                
                <a href="checkout.php" class="btn btn-primary btn-block">
                    Tiến hành thanh toán <i class="fas fa-arrow-right"></i>
                </a>
                
                <a href="products.php" class="btn btn-outline btn-block">
                    <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                </a>
                
                <div class="payment-icons">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/4/41/Visa_Logo.png" alt="Visa" height="24">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/2/2a/Mastercard-logo.svg" alt="Mastercard" height="24">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/American_Express_logo_%282018%29.svg" alt="AmEx" height="24">
                </div>
            </div>
        </div>

        <div class="recommended-section">
            <h2>Có thể bạn quan tâm</h2>
            <div class="products-grid">
                <?php
                // Lấy 4 sản phẩm ngẫu nhiên
                $randProds = $conn->query("SELECT * FROM products ORDER BY RAND() LIMIT 4");
                if ($randProds) {
                    while ($p = $randProds->fetch_assoc()) {
                        $p_discount = 0;
                        if($p['old_price'] > 0) {
                            $p_discount = round((($p['old_price'] - $p['price']) / $p['old_price']) * 100);
                        }
                        // Xử lý ảnh nếu là đường dẫn local
                        $imgSrc = $p['image'];
                        if (!preg_match("~^(?:f|ht)tps?://~i", $imgSrc)) {
                            // Nếu đường dẫn không bắt đầu bằng http/https thì coi là local
                            // Nếu $p['image'] lưu dạng 'uploads/...' thì ok
                        }
                ?>
                    <div class="product-card">
                        <?php if ($p_discount > 0): ?>
                            <div class="product-badge">-<?php echo $p_discount; ?>%</div>
                        <?php endif; ?>
                        
                        <div class="product-img-wrapper">
                            <img src="<?php echo htmlspecialchars($imgSrc); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>">
                        </div>
                        
                        <div class="product-info">
                            <div class="brand"><?php echo htmlspecialchars($p['brand']); ?></div>
                            <a href="product-detail.php?id=<?php echo $p['id']; ?>" class="product-title">
                                <?php echo htmlspecialchars($p['name']); ?>
                            </a>
                            <div class="price-row">
                                <div class="price"><?php echo number_format($p['price'], 0, ',', '.'); ?>đ</div>
                                <button class="add-cart-btn" onclick="addToCart(<?php echo $p['id']; ?>)">
                                    <i class="fas fa-cart-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                <?php 
                    }
                } 
                ?>
            </div>
        </div>
    </div>
</section>

<script>
// Chuyển biến PHP sang JS để tính toán
const MEMBER_DISCOUNT_PERCENT = <?php echo $discountPercent; ?>;

function renderCartPage() {
    const cart = getCart();
    const container = document.getElementById('cartTableContainer');
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="empty-cart-page">
                <i class="fas fa-shopping-cart"></i>
                <h2>Giỏ hàng trống</h2>
                <p>Bạn chưa có sản phẩm nào trong giỏ hàng</p>
                <a href="products.php" class="btn btn-primary">Tiếp tục mua sắm</a>
            </div>
        `;
        updateCartSummary();
        return;
    }
    
    let html = `
        <table class="cart-table">
            <thead>
                <tr>
                    <th>Sản phẩm</th>
                    <th>Đơn giá</th>
                    <th>Số lượng</th>
                    <th>Tổng</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
    `;
    
    cart.forEach((item, index) => {
        const qty = (typeof item.quantity === 'number' && item.quantity > 0) ? item.quantity : 1;

        html += `
            <tr>
                <td class="cart-product">
                    <img src="${item.image}" alt="${item.name}">
                    <div>
                        <a href="product-detail.php?id=${item.id}">${item.name}</a>
                        <div class="cart-product-brand">${item.brand}</div>
                    </div>
                </td>
                <td class="cart-price">${formatMoney(item.price)}</td>
                <td>
                    <div class="quantity-controls">
                        <button onclick="changeQuantity(${index}, -1)">-</button>
                        <input type="number" value="${qty}" readonly>
                        <button onclick="changeQuantity(${index}, 1)">+</button>
                    </div>
                </td>
                <td class="cart-total">${formatMoney(item.price * qty)}</td>
                <td>
                    <button class="remove-btn" onclick="removeFromCart(${index})" title="Xóa">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    
    html += `
            </tbody>
        </table>
    `;
    
    container.innerHTML = html;
    updateCartSummary();
}

function updateCartSummary() {
    const cart = getCart();
    const subtotal = cart.reduce((sum, item) => sum + (item.price * item.quantity), 0);

    // Phí ship: < 5tr thì 50k, >= 5tr miễn phí. Nếu giỏ rỗng thì 0.
    const shippingFee = (subtotal === 0) ? 0 : (subtotal >= 5000000 ? 0 : 50000);

    // --- TÍNH TOÁN GIẢM GIÁ THÀNH VIÊN ---
    // Giảm trên tổng tiền hàng (subtotal)
    let discountAmount = 0;
    if (MEMBER_DISCOUNT_PERCENT > 0) {
        discountAmount = (subtotal * MEMBER_DISCOUNT_PERCENT) / 100;
    }

    const total = Math.max(0, subtotal + shippingFee - discountAmount);
    
    document.getElementById('subtotal').textContent = formatMoney(subtotal);
    document.getElementById('shippingFee').textContent = shippingFee > 0 ? formatMoney(shippingFee) : (subtotal === 0 ? '0đ' : 'Miễn phí');
    
    // Hiển thị số tiền giảm
    document.getElementById('discount').textContent = discountAmount > 0 ? '-' + formatMoney(discountAmount) : '0đ';
    
    document.getElementById('totalPrice').textContent = formatMoney(total);
}

function applyCoupon() {
    const code = document.getElementById('couponInput').value.trim();
    if (code === '') {
        showToast('Vui lòng nhập mã giảm giá');
        return;
    }
    // Ở đây bạn có thể mở rộng thêm logic ghép coupon + hạng thành viên nếu muốn
    showToast('Hệ thống đang bảo trì tính năng coupon. Đã áp dụng ưu đãi thành viên!');
}

window.addEventListener('DOMContentLoaded', function () {
    renderCartPage();
});
</script>

<?php include 'includes/footer.php'; ?>