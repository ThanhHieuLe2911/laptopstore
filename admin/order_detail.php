<?php
require_once "_auth.php";
require_once "../includes/config.php";

date_default_timezone_set('Asia/Ho_Chi_Minh');

$adminName = $_SESSION['admin_name'] ?? 'Admin';

$statusMap = [
    'pending'    => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'shipping'   => 'Đang giao hàng',
    'completed'  => 'Hoàn thành',
    'cancelled'  => 'Đã hủy',
];

$paymentMap = [
    'cod'  => 'Tiền mặt (COD)',
    'bank' => 'Chuyển khoản',
    'card' => 'Thẻ quốc tế',
    'momo' => 'Ví MoMo',
];

$shippingMap = [
    'standard' => 'Tiêu chuẩn',
    'express'  => 'Nhanh',
];

$paymentStatusMap = [
    'unpaid' => 'Chưa thanh toán',
    'paid'   => 'Đã thanh toán',
];

// Lấy ID đơn
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("ID đơn hàng không hợp lệ.");

$alertError   = '';
$alertSuccess = '';

// --- XỬ LÝ: Cập nhật trạng thái đơn hàng ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // 1. Cập nhật trạng thái đơn hàng (Order Status)
    if ($_POST['action'] === 'update_status' && isset($_POST['new_status'])) {
        $newStatus = $_POST['new_status'];
        if (isset($statusMap[$newStatus])) {
            $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $stmt->bind_param("si", $newStatus, $id);
            if ($stmt->execute()) {
                $alertSuccess = "Đã cập nhật trạng thái đơn hàng thành: " . $statusMap[$newStatus];
            } else {
                $alertError = "Lỗi: " . $stmt->error;
            }
            $stmt->close();
        }
    }
    
    // 2. Cập nhật trạng thái thanh toán (Payment Status) - MỚI
    if ($_POST['action'] === 'update_payment' && isset($_POST['new_pay_status'])) {
        $newPayStatus = $_POST['new_pay_status'];
        if (isset($paymentStatusMap[$newPayStatus])) {
            $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
            $stmt->bind_param("si", $newPayStatus, $id);
            if ($stmt->execute()) {
                $alertSuccess = "Đã cập nhật trạng thái thanh toán thành: " . $paymentStatusMap[$newPayStatus];
            } else {
                $alertError = "Lỗi: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}

// Lấy thông tin đơn
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) die("Không tìm thấy đơn hàng.");

// Lấy danh sách sản phẩm
$items = [];
$sql = "
    SELECT oi.*, p.image AS product_image
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ?
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $items[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn <?= htmlspecialchars($order['order_code']) ?> - LaptopStore</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .admin-form-box { background:#fff; border-radius:12px; border:1px solid #e5e7eb; padding:24px; margin-top:16px; }
        .order-detail-grid { display:grid; grid-template-columns:2fr 1.2fr; gap:24px; }
        @media (max-width: 900px) { .order-detail-grid { grid-template-columns:1fr; } }
        
        /* Table Styles */
        .order-info-table, .order-items-table { width:100%; border-collapse:collapse; font-size:13px; }
        .order-info-table td { padding:8px 0; vertical-align:top; border-bottom: 1px dashed #f3f4f6; }
        .order-info-table tr:last-child td { border-bottom: none; }
        
        .order-items-table thead { background:#f9fafb; }
        .order-items-table th, .order-items-table td { padding:10px; border-bottom:1px solid #e5e7eb; text-align:left; }
        .order-items-table th { font-size:12px; font-weight:600; color:#6b7280; text-transform: uppercase; }
        
        .order-product { display:flex; align-items:center; gap:12px; }
        .order-product img { width:48px; height:48px; border-radius:6px; object-fit:cover; border:1px solid #e5e7eb; }
        
        /* Badges */
        .status-badge { padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; display:inline-flex; align-items:center; gap:4px; }
        .badge-pending { background:#fff7ed; color:#c2410c; }
        .badge-processing { background:#eff6ff; color:#1d4ed8; }
        .badge-shipping { background:#f5f3ff; color:#7c3aed; }
        .badge-completed { background:#ecfdf5; color:#15803d; }
        .badge-cancelled { background:#fef2f2; color:#b91c1c; }

        .pay-badge { padding:2px 8px; border-radius:4px; font-size:11px; font-weight:700; text-transform:uppercase; }
        .pay-unpaid { background:#fff1f2; color:#be123c; border:1px solid #fda4af; }
        .pay-paid { background:#f0fdf4; color:#15803d; border:1px solid #86efac; }

        .admin-alert { padding:12px 16px; border-radius:8px; font-size:14px; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .admin-alert.error { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
        .admin-alert.success { background:#ecfdf5; color:#15803d; border:1px solid #bbf7d0; }

        /* Action Form */
        .action-box { background:#f9fafb; border:1px solid #e5e7eb; border-radius:10px; padding:16px; margin-top:20px; }
        .action-box h4 { margin:0 0 10px; font-size:14px; color:#374151; }
        .form-inline { display:flex; gap:8px; }
        .form-select { flex:1; padding:8px; border:1px solid #d1d5db; border-radius:6px; font-size:13px; }
        .btn-update { padding:8px 16px; background:#4f46e5; color:#fff; border:none; border-radius:6px; cursor:pointer; font-size:13px; font-weight:500; transition:0.2s; }
        .btn-update:hover { background:#4338ca; }
    </style>
</head>
<body class="admin-body">
<div class="admin-shell">
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <div class="admin-brand-icon"><i class="fas fa-laptop"></i></div>
            <div><div class="admin-brand-title">LaptopStore</div><small>Admin Panel</small></div>
        </div>
        <div class="admin-user-mini">
            <div class="admin-user-avatar"><?= strtoupper(substr($adminName,0,1)) ?></div>
            <div><div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($adminName) ?></div><div style="font-size:11px;color:#9ca3af;">Quản trị viên</div></div>
        </div>
        <ul class="admin-nav">
            <li><a href="index.php"><i class="fas fa-chart-line"></i><span>Tổng quan</span></a></li>
            <li><a href="orders.php" class="active"><i class="fas fa-receipt"></i><span>Đơn hàng</span></a></li>
            <li><a href="products.php"><i class="fas fa-box-open"></i><span>Sản phẩm</span></a></li>
            <li><a href="users.php"><i class="fas fa-users"></i><span>Người dùng</span></a></li>
            <li><a href="../index.php"><i class="fas fa-store"></i><span>Xem trang khách</span></a></li>
            <li><a href="logout.php"><i class="fas fa-right-from-bracket"></i><span>Đăng xuất</span></a></li>
        </ul>
        <div class="admin-sidebar-footer">© <?= date('Y') ?> LaptopStore. All rights reserved.</div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="admin-top-title">Chi tiết đơn hàng #<?= htmlspecialchars($order['order_code']) ?></div>
                <div class="admin-top-sub">Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
            </div>
            <div class="admin-top-actions">
                <a href="orders.php" class="btn btn-outline btn-small"><i class="fas fa-arrow-left"></i> Quay lại</a>
                <a href="#" onclick="window.print()" class="btn btn-outline btn-small"><i class="fas fa-print"></i> In đơn</a>
            </div>
        </div>

        <section class="admin-form-box">
            <?php if ($alertError): ?>
                <div class="admin-alert error"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($alertError) ?></div>
            <?php endif; ?>
            <?php if ($alertSuccess): ?>
                <div class="admin-alert success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($alertSuccess) ?></div>
            <?php endif; ?>

            <div class="order-detail-grid">
                <div>
                    <h3 style="font-size:16px; margin-bottom:15px; font-weight:700;">Danh sách sản phẩm</h3>
                    <table class="order-items-table">
                        <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>Đơn giá</th>
                            <th style="text-align:center;">SL</th>
                            <th style="text-align:right;">Thành tiền</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $it): 
                            $img = $it['product_image'] ?? '';
                            if ($img && !preg_match('~^https?://~', $img)) $img = '../'.$img;
                        ?>
                            <tr>
                                <td>
                                    <div class="order-product">
                                        <?php if ($img): ?>
                                            <img src="<?= htmlspecialchars($img) ?>" alt="">
                                        <?php endif; ?>
                                        <div style="font-weight:500;"><?= htmlspecialchars($it['product_name']) ?></div>
                                    </div>
                                </td>
                                <td><?= number_format($it['price'], 0, ',', '.') ?>₫</td>
                                <td style="text-align:center;"><?= (int)$it['quantity'] ?></td>
                                <td style="text-align:right; font-weight:600;"><?= number_format($it['subtotal'], 0, ',', '.') ?>₫</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align:right; padding-top:20px;">Tạm tính:</td>
                                <td style="text-align:right; padding-top:20px;"><?= number_format($order['subtotal'], 0, ',', '.') ?>₫</td>
                            </tr>
                            <tr>
                                <td colspan="3" style="text-align:right;">Phí vận chuyển:</td>
                                <td style="text-align:right;"><?= number_format($order['shipping_fee'], 0, ',', '.') ?>₫</td>
                            </tr>
                            <?php if($order['discount'] > 0): ?>
                            <tr>
                                <td colspan="3" style="text-align:right; color:#10b981;">Giảm giá thành viên:</td>
                                <td style="text-align:right; color:#10b981;">-<?= number_format($order['discount'], 0, ',', '.') ?>₫</td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <td colspan="3" style="text-align:right; font-weight:700; font-size:16px;">TỔNG CỘNG:</td>
                                <td style="text-align:right; font-weight:700; font-size:16px; color:#ef4444;">
                                    <?= number_format($order['total'], 0, ',', '.') ?>₫
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div>
                    <h3 style="font-size:16px; margin-bottom:15px; font-weight:700;">Thông tin đơn hàng</h3>
                    <table class="order-info-table">
                        <tr>
                            <td style="color:#6b7280; width:130px;">Khách hàng:</td>
                            <td>
                                <strong><?= htmlspecialchars($order['full_name']) ?></strong><br>
                                <?= htmlspecialchars($order['phone']) ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="color:#6b7280;">Địa chỉ:</td>
                            <td>
                                <?= htmlspecialchars($order['address']) ?><br>
                                <?= htmlspecialchars($order['district']) ?>, <?= htmlspecialchars($order['city']) ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="color:#6b7280;">Ghi chú:</td>
                            <td style="font-style:italic; color:#4b5563;"><?= nl2br(htmlspecialchars($order['note'] ?? 'Không có')) ?></td>
                        </tr>
                        <tr>
                            <td style="color:#6b7280;">Phương thức TT:</td>
                            <td><?= $paymentMap[$order['payment_method']] ?? $order['payment_method'] ?></td>
                        </tr>
                        <tr>
                            <td style="color:#6b7280;">Thanh toán:</td>
                            <td>
                                <?php if ($order['payment_status'] === 'paid'): ?>
                                    <span class="pay-badge pay-paid"><i class="fas fa-check"></i> Đã thanh toán</span>
                                <?php else: ?>
                                    <span class="pay-badge pay-unpaid"><i class="fas fa-times"></i> Chưa thanh toán</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td style="color:#6b7280;">Trạng thái đơn:</td>
                            <td>
                                <?php
                                $st = $order['status'];
                                $badgeClass = 'status-badge';
                                if     ($st === 'pending')    $badgeClass .= ' badge-pending';
                                elseif ($st === 'processing') $badgeClass .= ' badge-processing';
                                elseif ($st === 'completed')  $badgeClass .= ' badge-completed';
                                elseif ($st === 'cancelled')  $badgeClass .= ' badge-cancelled';
                                ?>
                                <span class="<?= $badgeClass ?>">
                                    <?= $statusMap[$st] ?? $st ?>
                                </span>
                            </td>
                        </tr>
                    </table>

                    <div class="action-box">
                        <h4><i class="fas fa-cog"></i> Xử lý đơn hàng</h4>
                        
                        <form method="post" style="margin-bottom:12px;">
                            <input type="hidden" name="action" value="update_status">
                            <label style="display:block; font-size:12px; margin-bottom:4px; color:#6b7280;">Trạng thái đơn hàng:</label>
                            <div class="form-inline">
                                <select name="new_status" class="form-select">
                                    <?php foreach ($statusMap as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= ($order['status'] === $key) ? 'selected' : '' ?>>
                                            <?= $label ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit" class="btn-update">Cập nhật</button>
                            </div>
                        </form>

                        <form method="post">
                            <input type="hidden" name="action" value="update_payment">
                            <label style="display:block; font-size:12px; margin-bottom:4px; color:#6b7280;">Trạng thái thanh toán:</label>
                            <div class="form-inline">
                                <select name="new_pay_status" class="form-select">
                                    <option value="unpaid" <?= ($order['payment_status'] === 'unpaid') ? 'selected' : '' ?>>Chưa thanh toán</option>
                                    <option value="paid"   <?= ($order['payment_status'] === 'paid')   ? 'selected' : '' ?>>Đã thanh toán</option>
                                </select>
                                <button type="submit" class="btn-update" style="background:#10b981;">Lưu</button>
                            </div>
                        </form>
                    </div>

                </div>
            </div>
        </section>
    </main>
</div>
</body>
</html>