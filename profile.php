<?php
require_once 'includes/config.php';
// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$page_title = 'Thông tin tài khoản';
include 'includes/header.php';

$user_id = $_SESSION['user_id'];
$msg = ''; // Biến lưu thông báo

// --- 1. XỬ LÝ ĐỔI MẬT KHẨU ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass     = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (password_verify($current_pass, $res['password'])) {
        if ($new_pass === $confirm_pass) {
            if (strlen($new_pass) >= 6) {
                $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);
                if ($stmt->execute()) {
                    $msg = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Đổi mật khẩu thành công!</div>';
                } else {
                    $msg = '<div class="alert alert-danger">Lỗi hệ thống!</div>';
                }
                $stmt->close();
            } else {
                $msg = '<div class="alert alert-danger">Mật khẩu mới phải từ 6 ký tự!</div>';
            }
        } else {
            $msg = '<div class="alert alert-danger">Mật khẩu xác nhận không khớp!</div>';
        }
    } else {
        $msg = '<div class="alert alert-danger">Mật khẩu hiện tại không đúng!</div>';
    }
}

// --- 2. XỬ LÝ CẬP NHẬT THÔNG TIN (SĐT, ĐỊA CHỈ) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_info'])) {
    $phone   = trim($_POST['phone']);
    $address = trim($_POST['address']);

    // Validate cơ bản
    if (empty($phone) || empty($address)) {
        $msg = '<div class="alert alert-danger">Vui lòng nhập đầy đủ thông tin!</div>';
    } else {
        $stmt = $conn->prepare("UPDATE users SET phone = ?, address = ? WHERE id = ?");
        $stmt->bind_param("ssi", $phone, $address, $user_id);
        if ($stmt->execute()) {
            $msg = '<div class="alert alert-success"><i class="fas fa-check-circle"></i> Cập nhật thông tin thành công!</div>';
        } else {
            $msg = '<div class="alert alert-danger">Lỗi cập nhật: ' . $conn->error . '</div>';
        }
        $stmt->close();
    }
}

// --- 3. LẤY DỮ LIỆU USER (MỚI NHẤT) ---
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// --- 4. TÍNH TOÁN HẠNG THÀNH VIÊN ---
$stmt = $conn->prepare("SELECT SUM(total) as total_spent FROM orders WHERE user_id = ? AND status = 'completed'");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$spentRes = $stmt->get_result()->fetch_assoc();
$totalSpent = (int)($spentRes['total_spent'] ?? 0);
$stmt->close();

$rankName = 'New Member';
$rankColor = 'linear-gradient(135deg, #90aaedff 0%, #3f2b96 100%)'; 
$discountRate = 0;
$nextRankTarget = 15000000;
$nextRankName = 'Member';

if ($totalSpent >= 100000000) {
    $rankName = 'VIP';
    $rankColor = 'linear-gradient(135deg, #efc74fff 0%, #414345 100%)'; 
    $discountRate = 10;
    $nextRankTarget = $totalSpent; 
    $nextRankName = 'Max Level';
} elseif ($totalSpent >= 50000000) {
    $rankName = 'Loyal';
    $rankColor = 'linear-gradient(135deg, #e7e7e7ff 0%, #eb2c52ff 100%)'; 
    $discountRate = 6;
    $nextRankTarget = 100000000;
    $nextRankName = 'S-VIP';
} elseif ($totalSpent >= 15000000) {
    $rankName = 'Member';
    $rankColor = 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'; 
    $discountRate = 3;
    $nextRankTarget = 50000000;
    $nextRankName = 'Loyal';
}

if ($rankName === 'S-VIP' || $rankName === 'VIP') {
    $progressPercent = 100;
    $moneyNeed = 0;
} else {
    $progressPercent = ($totalSpent / $nextRankTarget) * 100;
    $moneyNeed = $nextRankTarget - $totalSpent;
}

// --- 5. LẤY LỊCH SỬ ĐƠN HÀNG ---
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$ordersRes = $stmt->get_result();
$orders = [];
while ($row = $ordersRes->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();
?>

<style>
    .breadcrumb-section {
        background: #fff;
        padding: 15px 0;
        border-bottom: 1px solid #e5e7eb;
        margin-bottom: 0;
    }
    .breadcrumb-content {
        display: flex; align-items: center; gap: 8px; font-size: 14px; color: #6b7280;
    }
    .breadcrumb-content a { text-decoration: none; color: #374151; transition: 0.2s; }
    .breadcrumb-content a:hover { color: #4f46e5; }
    .breadcrumb-content i { font-size: 10px; color: #9ca3af; }
    .breadcrumb-content span { color: #9ca3af; }
</style>

<div class="breadcrumb-section">
    <div class="container">
        <div class="breadcrumb-content">
            <a href="index.php"><i class="fas fa-home"></i> Trang chủ</a>
            <i class="fas fa-chevron-right"></i>
            <span>Hồ sơ của tôi</span>
        </div>
    </div>
</div>

<section class="profile-page" style="padding: 40px 0; background: #f9fafb; min-height: 80vh;">
    <div class="container">
        <?php echo $msg; ?>

        <div class="profile-layout">
            <div class="profile-sidebar">
                <div class="user-avt">
                    <div class="avt-circle">
                        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                    </div>
                    <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <p class="rank-badge-text"><?php echo $rankName; ?></p>
                </div>
                
                <hr style="border: 0; border-top: 1px solid #f3f4f6; margin: 20px 0;">
                
                <div class="info-group">
                    <label><i class="far fa-envelope"></i> Email</label>
                    <div><?php echo htmlspecialchars($user['email']); ?></div>
                </div>
                <div class="info-group">
                    <label><i class="fas fa-phone-alt"></i> Số điện thoại</label>
                    <div><?php echo htmlspecialchars($user['phone'] ?? 'Chưa cập nhật'); ?></div>
                </div>
                <div class="info-group">
                    <label><i class="fas fa-map-marker-alt"></i> Địa chỉ</label>
                    <div><?php echo htmlspecialchars($user['address'] ?? 'Chưa cập nhật'); ?></div>
                </div>

                <button onclick="openModal('infoModal')" class="btn-action btn-update-info">
                    <i class="fas fa-edit"></i> Cập nhật thông tin
                </button>

                <button onclick="openModal('passwordModal')" class="btn-action btn-change-pass">
                    <i class="fas fa-key"></i> Đổi mật khẩu
                </button>
            </div>

            <div class="profile-content">
                
                <div class="membership-card" style="background: <?php echo $rankColor; ?>;">
                    <div class="mem-header">
                        <div class="mem-rank-title"><?php echo $rankName; ?></div>
                        <div class="mem-discount-badge"><i class="fas fa-tag"></i> Giảm <?php echo $discountRate; ?>% đơn hàng</div>
                    </div>
                    
                    <div class="mem-info">
                        <div class="mem-spent">
                            Đã tích lũy: <strong><?php echo number_format($totalSpent, 0, ',', '.'); ?>đ</strong>
                        </div>
                        <div class="mem-target">
                            Mục tiêu: <?php echo number_format($nextRankTarget, 0, ',', '.'); ?>đ
                        </div>
                    </div>

                    <div class="mem-progress-bg">
                        <div class="mem-progress-bar" style="width: <?php echo $progressPercent; ?>%;"></div>
                    </div>

                    <div class="mem-footer">
                        <?php if ($rankName !== 'S-VIP' && $rankName !== 'VIP'): ?>
                            <span>Cần mua thêm <strong><?php echo number_format($moneyNeed, 0, ',', '.'); ?>đ</strong> để lên hạng <strong><?php echo $nextRankName; ?></strong></span>
                        <?php else: ?>
                            <span><i class="fas fa-crown"></i> Bạn đã đạt cấp độ cao nhất!</span>
                        <?php endif; ?>
                    </div>
                </div>

                <h2 class="section-title">
                    <i class="fas fa-history"></i> Đơn hàng gần đây
                </h2>
                
                <?php if (count($orders) > 0): ?>
                    <div class="order-list">
                        <?php foreach ($orders as $order): ?>
                            <?php 
                                $statusClass = ''; $statusLabel = '';
                                switch($order['status']) {
                                    case 'pending': $statusClass = 'badge-warning'; $statusLabel = 'Chờ xử lý'; break;
                                    case 'processing': $statusClass = 'badge-info'; $statusLabel = 'Đang xử lý'; break;
                                    case 'shipping': $statusClass = 'badge-primary'; $statusLabel = 'Đang giao'; break;
                                    case 'completed': $statusClass = 'badge-success'; $statusLabel = 'Hoàn thành'; break;
                                    case 'cancelled': $statusClass = 'badge-danger'; $statusLabel = 'Đã hủy'; break;
                                }
                            ?>
                            <div class="order-card">
                                <div class="order-info">
                                    <div class="order-code">
                                        Đơn hàng #<?php echo $order['order_code']; ?>
                                    </div>
                                    <div class="order-date">
                                        <i class="far fa-calendar-alt"></i> <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                                    </div>
                                    <div class="order-total">
                                        Tổng tiền: <span><?php echo number_format($order['total'], 0, ',', '.'); ?>₫</span>
                                    </div>
                                </div>
                                <div class="order-actions">
                                    <span class="badge <?php echo $statusClass; ?>"><?php echo $statusLabel; ?></span>
                                    <a href="my-order-detail.php?id=<?php echo $order['id']; ?>" class="view-detail-link">
                                        Xem chi tiết <i class="fas fa-arrow-right"></i>
                                    </a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div style="text-align: center; margin-top: 15px;">
                            <a href="my-orders.php" class="btn-view-all">Xem tất cả đơn hàng</a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <img src="assets/img/empty-order.png" alt="Empty" style="width: 80px; opacity: 0.5;">
                        <p>Bạn chưa có đơn hàng nào.</p>
                        <a href="products.php" class="btn-buy-now">Mua sắm ngay</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div id="passwordModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('passwordModal')">&times;</span>
        <h3><i class="fas fa-lock"></i> Đổi mật khẩu</h3>
        <form method="POST" action="">
            <div class="form-group-modal">
                <label>Mật khẩu hiện tại</label>
                <input type="password" name="current_password" required class="input-modal">
            </div>
            <div class="form-group-modal">
                <label>Mật khẩu mới</label>
                <input type="password" name="new_password" required class="input-modal" placeholder="Tối thiểu 6 ký tự">
            </div>
            <div class="form-group-modal">
                <label>Xác nhận mật khẩu mới</label>
                <input type="password" name="confirm_password" required class="input-modal">
            </div>
            <button type="submit" name="change_password" class="btn-modal-submit">Cập nhật mật khẩu</button>
        </form>
    </div>
</div>

<div id="infoModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" onclick="closeModal('infoModal')">&times;</span>
        <h3><i class="fas fa-user-edit"></i> Cập nhật thông tin</h3>
        <form method="POST" action="">
            <div class="form-group-modal">
                <label>Số điện thoại</label>
                <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required class="input-modal">
            </div>
            <div class="form-group-modal">
                <label>Địa chỉ giao hàng</label>
                <textarea name="address" rows="3" required class="input-modal"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
            </div>
            <button type="submit" name="update_info" class="btn-modal-submit">Lưu thông tin</button>
        </form>
    </div>
</div>

<style>
    /* Layout Grid */
    .profile-layout { display: grid; grid-template-columns: 300px 1fr; gap: 30px; }
    
    /* Sidebar */
    .profile-sidebar {
        background: #fff; padding: 30px; border-radius: 12px; 
        box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e5e7eb; height: fit-content;
    }
    .avt-circle {
        width: 90px; height: 90px; background: #e0e7ff; color: #4f46e5; 
        border-radius: 50%; display: flex; align-items: center; justify-content: center; 
        font-size: 36px; margin: 0 auto 15px; font-weight: 700;
    }
    .user-avt h3 { margin: 10px 0 5px; font-size: 18px; font-weight: 700; color: #111827; text-align: center; }
    .rank-badge-text { 
        color: #fff; background: #4f46e5; display: inline-block; 
        padding: 4px 12px; border-radius: 99px; font-size: 12px; font-weight: 600; 
        margin-top: 5px; width: 100%; text-align: center;
    }
    
    .info-group { margin-bottom: 18px; }
    .info-group label { display: block; font-size: 13px; color: #6b7280; margin-bottom: 4px; font-weight: 500; }
    .info-group label i { margin-right: 5px; width: 16px; text-align: center; }
    .info-group div { font-weight: 600; color: #374151; font-size: 14px; word-break: break-word; }

    /* Buttons in Sidebar */
    .btn-action {
        width: 100%; padding: 10px; border-radius: 8px; cursor: pointer; font-weight: 600;
        margin-top: 10px; transition: 0.2s; font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px;
    }
    .btn-update-info { background: #4f46e5; color: #fff; border: 1px solid #4f46e5; }
    .btn-update-info:hover { background: #4338ca; }
    
    .btn-change-pass { background: #fff; color: #374151; border: 1px solid #d1d5db; margin-top: 10px; }
    .btn-change-pass:hover { background: #f3f4f6; color: #111827; border-color: #9ca3af; }

    /* Content Area */
    .membership-card {
        border-radius: 16px; padding: 30px; color: #fff;
        box-shadow: 0 10px 25px rgba(79, 70, 229, 0.2); position: relative; overflow: hidden;
    }
    .mem-rank-title { font-size: 24px; font-weight: 800; letter-spacing: 0.5px; }
    .mem-discount-badge {
        background: rgba(255,255,255,0.25); padding: 5px 15px; 
        border-radius: 20px; font-size: 13px; font-weight: 600; backdrop-filter: blur(4px);
    }
    .mem-header, .mem-info { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    .mem-spent, .mem-target { font-size: 14px; opacity: 0.95; }
    .mem-progress-bg { background: rgba(255,255,255,0.3); height: 8px; border-radius: 4px; overflow: hidden; margin-bottom: 15px; }
    .mem-progress-bar { background: #fff; height: 100%; border-radius: 4px; transition: width 0.5s ease; }
    .mem-footer { font-size: 13px; opacity: 0.9; font-weight: 500; }

    /* Order List */
    .section-title { font-size: 18px; font-weight: 700; color: #111827; margin: 30px 0 20px; display: flex; align-items: center; gap: 10px; }
    .order-list { display: flex; flex-direction: column; gap: 15px; }
    .order-card {
        background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb;
        display: flex; justify-content: space-between; align-items: center; transition: 0.2s;
    }
    .order-card:hover { border-color: #4f46e5; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
    .order-code { font-weight: 700; color: #111827; margin-bottom: 4px; }
    .order-date { font-size: 13px; color: #6b7280; margin-bottom: 4px; }
    .order-total span { color: #dc2626; font-weight: 700; }
    
    .order-actions { text-align: right; display: flex; flex-direction: column; align-items: flex-end; gap: 8px; }
    .view-detail-link { font-size: 13px; color: #4f46e5; text-decoration: none; font-weight: 600; }
    .view-detail-link:hover { text-decoration: underline; }

    .btn-view-all { 
        display: inline-block; padding: 8px 20px; border: 1px solid #d1d5db; 
        border-radius: 8px; color: #4b5563; text-decoration: none; font-size: 14px; font-weight: 500; 
    }
    .btn-view-all:hover { background: #f3f4f6; color: #111827; }

    /* Empty State */
    .empty-state { text-align: center; padding: 40px; background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; }
    .btn-buy-now { 
        display: inline-block; margin-top: 15px; padding: 10px 24px; 
        background: #4f46e5; color: #fff; border-radius: 8px; text-decoration: none; font-weight: 600; 
    }

    /* Modals */
    .modal { display: none; position: fixed; z-index: 9999; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(2px); }
    .modal-content {
        background-color: #fff; margin: 10vh auto; padding: 30px; border-radius: 16px;
        width: 400px; max-width: 90%; position: relative; animation: slideDown 0.3s;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
    }
    .modal-content h3 { margin-top: 0; font-size: 18px; color: #111827; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
    .close-modal { position: absolute; right: 20px; top: 20px; font-size: 24px; cursor: pointer; color: #9ca3af; }
    .close-modal:hover { color: #4b5563; }
    
    .input-modal { width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px; outline: none; transition: 0.2s; font-size: 14px; }
    .input-modal:focus { border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    .btn-modal-submit { width: 100%; padding: 12px; background: #4f46e5; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; margin-top: 10px; }
    .btn-modal-submit:hover { background: #4338ca; }

    /* Alerts & Badges */
    .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    .alert-success { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
    .alert-danger { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    
    .badge { padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; display: inline-block; }
    .badge-warning { background: #fffbeb; color: #b45309; }
    .badge-info { background: #eff6ff; color: #1d4ed8; }
    .badge-primary { background: #e0f2fe; color: #0369a1; }
    .badge-success { background: #dcfce7; color: #15803d; }
    .badge-danger { background: #fef2f2; color: #b91c1c; }

    @media (max-width: 768px) {
        .profile-layout { grid-template-columns: 1fr; }
        .order-card { flex-direction: column; align-items: flex-start; gap: 15px; }
        .order-actions { width: 100%; flex-direction: row; justify-content: space-between; align-items: center; }
    }
</style>

<script>
    // Hàm mở Modal theo ID
    function openModal(modalId) {
        document.getElementById(modalId).style.display = "block";
    }

    // Hàm đóng Modal theo ID
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = "none";
    }

    // Đóng modal khi click ra ngoài vùng content
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = "none";
        }
    }
</script>

<?php include 'includes/footer.php'; ?>