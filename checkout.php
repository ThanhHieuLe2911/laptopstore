<?php
require_once 'includes/config.php';

// Đảm bảo session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --- LOGIC TÍNH HẠNG THÀNH VIÊN ---
$userRank = 'S-New';
$discountPercent = 0;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT SUM(total) as total_spent FROM orders WHERE user_id = ? AND status = 'completed'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $totalSpent = (int)($res['total_spent'] ?? 0);
    $stmt->close();

    if ($totalSpent >= 100000000) {
        $userRank = 'S-VIP';
        $discountPercent = 10;
    } elseif ($totalSpent >= 50000000) {
        $userRank = 'S-Loyal';
        $discountPercent = 6;
    } elseif ($totalSpent >= 15000000) {
        $userRank = 'S-Member';
        $discountPercent = 3;
    }
}

// --- XỬ LÝ ĐẶT HÀNG (POST) ---
$orderSuccess = false;
$orderCode    = '';
$orderError   = '';
$payment      = 'cod'; 
$total        = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = trim($_POST['fullname'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $address  = trim($_POST['address'] ?? '');
    $city     = trim($_POST['city'] ?? '');
    $district = trim($_POST['district'] ?? '');
    $note     = trim($_POST['note'] ?? '');
    $shipping = $_POST['shipping'] ?? 'standard';
    $payment  = $_POST['payment'] ?? 'cod';
    $cartJson = $_POST['cart_data'] ?? '';

    if ($fullname && $phone && $address && $city && $district && $cartJson) {
        $cart = json_decode($cartJson, true);

        if (is_array($cart) && count($cart) > 0) {
            $userId      = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
            $subtotal    = 0;
            
            // Phí vận chuyển: Express = 100k, Standard = 0đ (Miễn phí)
            $shippingFee = ($shipping === 'express') ? 100000 : 0;
            
            $validItems  = [];

            foreach ($cart as $item) {
                $productId = (int)($item['id'] ?? 0);
                $quantity  = (int)($item['quantity'] ?? 0);

                if ($productId <= 0 || $quantity <= 0) continue;

                $stmt = $conn->prepare("SELECT id, name, price FROM products WHERE id = ?");
                if (!$stmt) continue;

                $stmt->bind_param("i", $productId);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($row = $result->fetch_assoc()) {
                    $price        = (int)$row['price'];
                    $lineSubtotal = $price * $quantity;
                    $subtotal    += $lineSubtotal;

                    $validItems[] = [
                        'product_id'   => $row['id'],
                        'product_name' => $row['name'],
                        'price'        => $price,
                        'quantity'     => $quantity,
                        'subtotal'     => $lineSubtotal,
                    ];
                }
                $stmt->close();
            }

            if (count($validItems) > 0) {
                // Tính giảm giá thành viên
                $discountAmount = 0;
                if ($discountPercent > 0) {
                    $discountAmount = ($subtotal * $discountPercent) / 100;
                }

                $total = $subtotal + $shippingFee - $discountAmount;
                if ($total < 0) $total = 0;

                $stmt = $conn->prepare("
                    INSERT INTO orders (
                        order_code, user_id, full_name, phone, email, address, city, district, note,
                        shipping_method, payment_method, subtotal, shipping_fee, discount, total, status
                    ) VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");

                if ($stmt) {
                    $stmt->bind_param(
                        "isssssssssiiid", 
                        $userId, $fullname, $phone, $email, $address, $city, $district, $note,
                        $shipping, $payment, $subtotal, $shippingFee, $discountAmount, $total
                    );

                    if ($stmt->execute()) {
                        $orderId = $stmt->insert_id;
                        $stmt->close();

                        $orderCode = 'LPS' . str_pad((string)$orderId, 8, '0', STR_PAD_LEFT);
                        $stmt2 = $conn->prepare("UPDATE orders SET order_code = ? WHERE id = ?");
                        if ($stmt2) {
                            $stmt2->bind_param("si", $orderCode, $orderId);
                            $stmt2->execute();
                            $stmt2->close();
                        }

                        $stmtItem = $conn->prepare("
                            INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal)
                            VALUES (?, ?, ?, ?, ?, ?)
                        ");

                        if ($stmtItem) {
                            foreach ($validItems as $it) {
                                $stmtItem->bind_param("iisiii", $orderId, $it['product_id'], $it['product_name'], $it['price'], $it['quantity'], $it['subtotal']);
                                $stmtItem->execute();
                            }
                            $stmtItem->close();
                        }

                        $orderSuccess = true;
                    } else {
                        $orderError = 'Lỗi DB: ' . $stmt->error;
                    }
                } else {
                    $orderError = 'Lỗi hệ thống.';
                }
            } else {
                $orderError = 'Giỏ hàng không hợp lệ.';
            }
        } else {
            $orderError = 'Giỏ hàng trống.';
        }
    } else {
        $orderError = 'Vui lòng điền đủ thông tin.';
    }
}

$page_title = 'Thanh toán';
include 'includes/header.php';
?>

<div class="breadcrumb">
    <div class="container">
        <a href="index.php">Trang chủ</a>
        <i class="fas fa-chevron-right"></i>
        <a href="cart.php">Giỏ hàng</a>
        <i class="fas fa-chevron-right"></i>
        <span>Thanh toán</span>
    </div>
</div>

<section class="checkout-page">
    <div class="container">
        
        <?php if ($orderError && !$orderSuccess): ?>
            <div class="alert alert-danger" style="margin-bottom:20px;">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($orderError); ?>
            </div>
        <?php endif; ?>

        <div class="checkout-layout">
            <div class="checkout-main">
                <form id="checkoutForm" onsubmit="handleCheckout(event)" method="POST">
                    
                    <div class="checkout-section">
                        <h2><i class="fas fa-user"></i> Thông tin khách hàng</h2>
                        
                        <?php if(!isset($_SESSION['user_id'])): ?>
                            <div class="alert alert-info" style="margin-bottom: 15px; font-size: 13px;">
                                <i class="fas fa-info-circle"></i> Bạn chưa đăng nhập. Hãy <a href="login.php" style="font-weight: bold;">đăng nhập</a> để hưởng ưu đãi!
                            </div>
                        <?php else: ?>
                            <div class="alert alert-success" style="margin-bottom: 15px; font-size: 13px; background-color: #d4edda; color: #155724; border-color: #c3e6cb;">
                                <i class="fas fa-crown"></i> Thành viên <strong><?php echo $userRank; ?></strong> (Giảm <?php echo $discountPercent; ?>%).
                            </div>
                        <?php endif; ?>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Họ và tên *</label>
                                <input type="text" name="fullname" required placeholder="Nguyễn Văn A">
                            </div>
                            <div class="form-group">
                                <label>Số điện thoại *</label>
                                <input type="tel" name="phone" required placeholder="09xxxxxxxxx">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" placeholder="example@email.com">
                        </div>
                    </div>

                    <div class="checkout-section">
                        <h2><i class="fas fa-map-marker-alt"></i> Địa chỉ giao hàng</h2>
                        <div class="form-group">
                            <label>Địa chỉ *</label>
                            <input type="text" name="address" required placeholder="Số nhà, tên đường">
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label>Tỉnh/Thành phố *</label>
                                <select name="city" required>
                                    <option value="">Chọn Tỉnh/Thành phố</option>
                                    <option value="Hà Nội">Hà Nội</option>
                                    <option value="TP. Hồ Chí Minh">TP. Hồ Chí Minh</option>
                                    <option value="Đà Nẵng">Đà Nẵng</option>
                                    <option value="Hải Phòng">Hải Phòng</option>
                                    <option value="Cần Thơ">Cần Thơ</option>
                                    <option value="Bình Dương">Bình Dương</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Quận/Huyện *</label>
                                <select name="district" required>
                                    <option value="">Chọn Quận/Huyện</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Ghi chú</label>
                            <textarea name="note" rows="3" placeholder="Ghi chú thêm..."></textarea>
                        </div>
                    </div>

                    <div class="checkout-section">
                        <h2><i class="fas fa-truck"></i> Phương thức vận chuyển</h2>
                        <div class="shipping-options">
                            <label class="shipping-option">
                                <input type="radio" name="shipping" value="standard" checked>
                                <div class="option-content">
                                    <div>
                                        <strong>Giao hàng tiêu chuẩn</strong>
                                        <p>Giao trong 3-5 ngày</p>
                                    </div>
                                    <span class="option-price" style="color: #28a745;">Miễn phí</span>
                                </div>
                            </label>
                            
                            <label class="shipping-option">
                                <input type="radio" name="shipping" value="express">
                                <div class="option-content">
                                    <div>
                                        <strong>Giao hàng nhanh</strong>
                                        <p>Giao trong 1-2 ngày</p>
                                    </div>
                                    <span class="option-price">100.000đ</span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="checkout-section">
                        <h2><i class="fas fa-credit-card"></i> Phương thức thanh toán</h2>
                        <div class="payment-options">
                            <label class="payment-option">
                                <input type="radio" name="payment" value="cod" checked>
                                <div class="option-content">
                                    <i class="fas fa-money-bill-wave"></i>
                                    <div>
                                        <strong>Thanh toán khi nhận hàng (COD)</strong>
                                        <p>Thanh toán bằng tiền mặt khi nhận hàng</p>
                                    </div>
                                </div>
                            </label>
                            <label class="payment-option">
                                <input type="radio" name="payment" value="bank">
                                <div class="option-content">
                                    <i class="fas fa-university"></i>
                                    <div>
                                        <strong>Chuyển khoản ngân hàng</strong>
                                        <p style="font-size:12px; color:#666;">MB: 222429112004 - LE THANH HIEU</p>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <input type="hidden" name="cart_data" id="cartDataInput">

                    <button type="submit" class="btn btn-primary btn-large btn-block" style="margin-top: 20px;">
                        <i class="fas fa-check"></i> Hoàn tất đặt hàng
                    </button>
                </form>
            </div>

            <div class="checkout-summary">
                <h3>Đơn hàng của bạn</h3>
                <div class="summary-items" id="summaryItems"></div>
                
                <div class="summary-row">
                    <span>Tạm tính</span>
                    <span id="summarySubtotal">0đ</span>
                </div>
                
                <div class="summary-row">
                    <span>Phí vận chuyển</span>
                    <span id="summaryShipping">Miễn phí</span>
                </div>

                <div class="summary-row" style="color: #28a745;">
                    <span><i class="fas fa-crown"></i> Ưu đãi <?php echo $userRank; ?> (-<?php echo $discountPercent; ?>%)</span>
                    <span id="summaryDiscount">0đ</span>
                </div>
                
                <div class="summary-divider"></div>
                
                <div class="summary-row summary-total">
                    <span>Tổng cộng</span>
                    <span class="total-price" id="summaryTotal">0đ</span>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal" id="successModal">
    <div class="modal-content success-modal" style="max-width: 550px;">
        <div class="success-icon"><i class="fas fa-check-circle"></i></div>
        <h2>Đặt hàng thành công!</h2>
        <p>Cảm ơn bạn đã mua sắm tại LaptopStore</p>
        <p class="order-code">Mã đơn hàng: <strong id="orderCodeText" style="font-size: 18px; color: #4f46e5;"></strong></p>
        
        <div id="bankTransferInfo" style="display:none; margin-top: 20px; border: 2px dashed #4f46e5; padding: 20px; border-radius: 12px; background: #f0fdf4;">
            <h4 style="margin: 0 0 15px 0; color: #4f46e5; text-transform: uppercase; font-size: 16px;">
                Quét mã để thanh toán ngay
            </h4>
            
            <div style="display: flex; gap: 20px; align-items: center; justify-content: center; flex-wrap: wrap;">
                <img id="vietqrImage" src="" alt="VietQR" style="width: 200px; border-radius: 8px; border: 1px solid #ddd;">
                
                <div style="text-align: left; font-size: 14px; line-height: 1.6;">
                    <p style="margin: 5px 0;">Ngân hàng: <strong>MB</strong></p>
                    <p style="margin: 5px 0;">Số TK: <strong>222429112004</strong></p>
                    <p style="margin: 5px 0;">Chủ TK: <strong>LE THANH HIEU</strong></p>
                    <p style="margin: 5px 0;">Số tiền: <strong id="qrAmount" style="color: #d70018; font-size: 16px;"></strong></p>
                    <p style="margin: 5px 0;">Nội dung: <strong id="qrContent" style="color: #4f46e5; background: #eef2ff; padding: 2px 6px; border-radius: 4px;"></strong></p>
                </div>
            </div>

            <div style="margin-top: 15px; font-size: 13px; color: #666; font-style: italic;">
                * Vui lòng giữ nguyên nội dung chuyển khoản để đơn hàng được xử lý nhanh nhất.
            </div>
        </div>

        <div class="success-actions" style="margin-top: 25px;">
            <a href="my-orders.php" class="btn btn-primary btn-block">
                <i class="fas fa-money-check-alt"></i> Tôi đã chuyển khoản xong
            </a>
            <a href="index.php" class="btn btn-outline btn-block" style="margin-top: 10px;">
                Về trang chủ
            </a>
        </div>
    </div>
</div>

<script>
// Biến JS nhận % giảm giá từ PHP
const MEMBER_DISCOUNT_PERCENT = <?php echo $discountPercent; ?>;

// ================== TỈNH/THÀNH & QUẬN/HUYỆN ==================
const DISTRICTS_BY_CITY = {
    'Hà Nội': ['Quận Ba Đình','Quận Hoàn Kiếm','Quận Đống Đa','Quận Hai Bà Trưng','Quận Cầu Giấy'],
    'TP. Hồ Chí Minh': ['Quận 1','Quận 3','Quận 5','Quận 7','Quận 10','Thành phố Thủ Đức'],
    'Đà Nẵng': ['Quận Hải Châu','Quận Thanh Khê','Quận Sơn Trà'],
    'Bình Dương': ['Thủ Dầu Một','Thuận An','Dĩ An','Bến Cát']
};

function initLocationSelects() {
    const citySelect = document.querySelector('select[name="city"]');
    const districtSelect = document.querySelector('select[name="district"]');
    if (!citySelect || !districtSelect) return;

    citySelect.addEventListener('change', function() {
        districtSelect.innerHTML = '<option value="">Chọn Quận/Huyện</option>';
        if (this.value && DISTRICTS_BY_CITY[this.value]) {
            DISTRICTS_BY_CITY[this.value].forEach(d => {
                const opt = document.createElement('option');
                opt.value = d;
                opt.textContent = d;
                districtSelect.appendChild(opt);
            });
        }
    });
}

function renderCheckoutSummary() {
    const cart = getCart();
    const container = document.getElementById('summaryItems');
    
    if (!cart || cart.length === 0) {
        window.location.href = 'cart.php';
        return;
    }
    
    let html = '';
    let subtotal = 0;

    cart.forEach(item => {
        subtotal += item.price * item.quantity;
        html += `
            <div class="summary-item">
                <img src="${item.image}" alt="${item.name}">
                <div class="item-details">
                    <div class="item-name">${item.name}</div>
                    <div class="item-qty">x ${item.quantity}</div>
                </div>
                <div class="item-price">${formatMoney(item.price * item.quantity)}</div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    // Tính phí ship (Standard = 0, Express = 100k)
    let shippingFee = 0;
    const shippingType = document.querySelector('input[name="shipping"]:checked')?.value;
    if (shippingType === 'express') {
        shippingFee = 100000;
    } else {
        shippingFee = 0;
    }

    // Tính giảm giá thành viên
    let discountAmount = 0;
    if (MEMBER_DISCOUNT_PERCENT > 0) {
        discountAmount = (subtotal * MEMBER_DISCOUNT_PERCENT) / 100;
    }

    const total = Math.max(0, subtotal + shippingFee - discountAmount);
    
    document.getElementById('summarySubtotal').textContent = formatMoney(subtotal);
    document.getElementById('summaryShipping').textContent = shippingFee > 0 ? formatMoney(shippingFee) : 'Miễn phí';
    document.getElementById('summaryDiscount').textContent = discountAmount > 0 ? '-' + formatMoney(discountAmount) : '0đ';
    document.getElementById('summaryTotal').textContent = formatMoney(total);
}

function handleCheckout(e) {
    e.preventDefault();
    const cart = getCart();
    if (!cart || cart.length === 0) return;

    const btn = e.target.querySelector('button[type="submit"]');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    btn.disabled = true;

    document.getElementById('cartDataInput').value = JSON.stringify(cart);
    e.target.submit();
}

document.querySelectorAll('input[name="shipping"]').forEach(radio => {
    radio.addEventListener('change', renderCheckoutSummary);
});

window.addEventListener('DOMContentLoaded', function () {
    initLocationSelects();
    <?php if (!$orderSuccess): ?>
    renderCheckoutSummary();
    <?php endif; ?>
});

// Xử lý khi đặt hàng thành công
<?php if ($orderSuccess && $orderCode): ?>
window.addEventListener('load', function() {
    localStorage.removeItem('laptopStoreCart'); 
    
    document.getElementById('orderCodeText').textContent = '<?php echo htmlspecialchars($orderCode); ?>';
    
    // --- TÍCH HỢP VIETQR ---
    const paymentMethod = '<?php echo $payment; ?>';
    const totalAmount = <?php echo $total; ?>;
    const orderCode = '<?php echo $orderCode; ?>';

    // ✅ CẤU HÌNH TÀI KHOẢN MB
    const MY_BANK_ID = 'MB'; 
    const MY_ACCOUNT_NO = '222429112004'; 
    const MY_ACCOUNT_NAME = 'LE THANH HIEU'; 

    if (paymentMethod === 'bank') {
        const qrContainer = document.getElementById('bankTransferInfo');
        const qrImg = document.getElementById('vietqrImage');
        const qrContent = document.getElementById('qrContent');
        const qrAmount = document.getElementById('qrAmount');

        const content = `${orderCode}`; 
        // Tạo link QR VietQR
        const qrUrl = `https://img.vietqr.io/image/${MY_BANK_ID}-${MY_ACCOUNT_NO}-compact.jpg?amount=${totalAmount}&addInfo=${content}&accountName=${MY_ACCOUNT_NAME}`;

        qrImg.src = qrUrl;
        qrContent.textContent = content;
        qrAmount.textContent = new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(totalAmount);
        
        qrContainer.style.display = 'block';
    }

    document.getElementById('successModal').classList.add('open');
});
<?php endif; ?>
</script>

<?php include 'includes/footer.php'; ?>