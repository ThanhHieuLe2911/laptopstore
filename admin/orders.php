<?php
require_once "_auth.php";
require_once "../includes/config.php";

date_default_timezone_set('Asia/Ho_Chi_Minh');
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Map trạng thái đơn hàng (để hiển thị)
$statusMap = [
    'pending'    => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'shipping'   => 'Đang giao',
    'completed'  => 'Hoàn thành',
    'cancelled'  => 'Đã hủy',
];

$paymentMethodMap = [
    'cod'  => 'COD',
    'bank' => 'Chuyển khoản',
    'card' => 'Thẻ QT',
    'momo' => 'MoMo',
];

$statusFilter = $_GET['status'] ?? 'all';

// --- LẤY DỮ LIỆU ---
$orders = [];
$baseSql = "SELECT o.*, COUNT(oi.id) AS item_count, COALESCE(SUM(oi.quantity),0) AS total_qty 
            FROM orders o LEFT JOIN order_items oi ON oi.order_id = o.id";

if ($statusFilter !== 'all' && isset($statusMap[$statusFilter])) {
    $sql = $baseSql . " WHERE o.status = ? GROUP BY o.id ORDER BY o.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $statusFilter);
} else {
    $sql = $baseSql . " GROUP BY o.id ORDER BY o.created_at DESC";
    $stmt = $conn->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý đơn hàng - LaptopStore</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
    <style>
        :root { --primary: #4f46e5; --text-dark: #111827; --text-gray: #6b7280; --border: #e5e7eb; }
        
        .admin-form-box { background: #fff; border-radius: 16px; border: 1px solid var(--border); padding: 24px; margin-top: 20px; }
        
        .order-filter-bar { display: flex; flex-wrap: wrap; justify-content: space-between; margin-bottom: 24px; gap: 10px; }
        .order-filter-tabs { display: flex; flex-wrap: wrap; gap: 8px; background: #f9fafb; padding: 4px; border-radius: 12px; border: 1px solid var(--border); }
        .order-filter-tabs a { padding: 6px 14px; border-radius: 8px; font-size: 13px; font-weight: 500; color: var(--text-gray); text-decoration: none; transition: 0.2s; }
        .order-filter-tabs a:hover, .order-filter-tabs a.active { background: #fff; color: var(--primary); font-weight: 700; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        
        .table-wrapper { overflow-x: auto; }
        .orders-table { width: 100%; min-width: 1000px; border-collapse: collapse; font-size: 14px; }
        .orders-table th { background: #f9fafb; padding: 12px 16px; text-align: left; border-bottom: 1px solid var(--border); color: var(--text-gray); font-size: 12px; text-transform: uppercase; }
        .orders-table td { padding: 16px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: #e0e7ff; color: #4f46e5; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; margin-right: 12px; flex-shrink: 0; }
        .customer-cell { display: flex; align-items: center; }
        
        /* Select đẹp hơn */
        .status-select {
            padding: 6px 10px; border-radius: 6px; border: 1px solid var(--border); 
            font-size: 13px; font-weight: 500; outline: none; cursor: pointer; transition: 0.2s;
            background-color: #fff; width: 100%; max-width: 140px;
        }
        .status-select:hover { border-color: var(--primary); }
        
        /* Màu sắc select theo trạng thái */
        .status-select[data-val="pending"] { color: #b45309; background: #fffbeb; border-color: #fcd34d; }
        .status-select[data-val="completed"] { color: #059669; background: #ecfdf5; border-color: #6ee7b7; }
        .status-select[data-val="cancelled"] { color: #dc2626; background: #fef2f2; border-color: #fca5a5; }
        
        /* Select thanh toán */
        .pay-select { padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: 600; cursor: pointer; border: 1px solid transparent; }
        .pay-select.unpaid { background: #fff7ed; color: #c2410c; border-color: #ffedd5; }
        .pay-select.paid { background: #ecfdf5; color: #15803d; border-color: #a7f3d0; }

        .btn-view { color: var(--text-dark); font-weight: 500; font-size: 13px; text-decoration: none; white-space: nowrap; }
        .btn-view:hover { color: var(--primary); text-decoration: underline; }
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
            <li><a href="categories.php"><i class="fas fa-folder"></i><span>Danh mục</span></a></li>
            <li><a href="brands.php"><i class="fas fa-tags"></i><span>Hãng sản xuất</span></a></li>
            
            <li><a href="users.php"><i class="fas fa-users"></i><span>Người dùng</span></a></li>
            <li><a href="../index.php"><i class="fas fa-store"></i><span>Xem trang khách</span></a></li>
            <li><a href="logout.php"><i class="fas fa-right-from-bracket"></i><span>Đăng xuất</span></a></li>
        </ul>
        <div class="admin-sidebar-footer">© <?= date('Y') ?> LaptopStore. All rights reserved.</div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="admin-top-title">Quản lý đơn hàng</div>
                <div class="admin-top-sub">Cập nhật trạng thái nhanh chóng (AJAX).</div>
            </div>
        </div>

        <section class="admin-form-box">
            <div class="order-filter-bar">
                <div class="order-filter-tabs">
                    <?php
                    $tabs = ['all'=>'Tất cả', 'pending'=>'Chờ xử lý', 'processing'=>'Đang xử lý', 'shipping'=>'Đang giao', 'completed'=>'Hoàn thành', 'cancelled'=>'Đã hủy'];
                    foreach ($tabs as $key => $label):
                        $active = ($statusFilter === $key) ? 'active' : '';
                    ?>
                        <a href="?status=<?= $key ?>" class="<?= $active ?>"><?= $label ?></a>
                    <?php endforeach; ?>
                </div>
                <div style="font-size:13px;color:#6b7280;align-self: center;">Tổng: <strong><?= count($orders) ?></strong> đơn</div>
            </div>

            <div class="table-wrapper">
                <table class="orders-table">
                    <thead>
                        <tr>
                            <th style="width: 140px;">Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Chi tiết</th>
                            <th>Tổng tiền</th>
                            <th>Thanh toán</th>
                            <th>Trạng thái Đơn</th>
                            <th style="text-align:right;">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($orders)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:30px; color: #9ca3af;">Không có đơn hàng nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td>
                                <strong>#<?= $order['id'] ?></strong><br>
                                <span style="font-size:11px;color:#9ca3af;"><?= $order['order_code'] ?></span>
                                <div style="font-size:11px;color:#6b7280;margin-top:2px;"><?= date('d/m/Y', strtotime($order['created_at'])) ?></div>
                            </td>
                            <td>
                                <div class="customer-cell">
                                    <div class="user-avatar"><?= strtoupper(substr($order['full_name'] ?? 'G', 0, 1)) ?></div>
                                    <div>
                                        <div style="font-weight:600;"><?= htmlspecialchars($order['full_name']) ?></div>
                                        <div style="font-size:12px;color:#6b7280;"><?= htmlspecialchars($order['phone']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <strong><?= (int)$order['total_qty'] ?></strong> sp<br>
                                <span style="font-size:12px;color:#6b7280;"><?= $paymentMethodMap[$order['payment_method']] ?? $order['payment_method'] ?></span>
                            </td>
                            <td>
                                <div style="font-weight:700;color:#ef4444;"><?= number_format($order['total'], 0, ',', '.') ?>₫</div>
                            </td>
                            
                            <td>
                                <select class="pay-select <?= $order['payment_status']=='paid'?'paid':'unpaid' ?>" 
                                        onchange="updatePaymentStatus(<?= $order['id'] ?>, this)">
                                    <option value="unpaid" <?= $order['payment_status']=='unpaid'?'selected':'' ?>>⏳ Chưa TT</option>
                                    <option value="paid" <?= $order['payment_status']=='paid'?'selected':'' ?>>✅ Đã TT</option>
                                </select>
                            </td>

                            <td>
                                <select class="status-select" data-val="<?= $order['status'] ?>"
                                        onchange="updateOrderStatus(<?= $order['id'] ?>, this.value)">
                                    <?php foreach ($statusMap as $key => $label): ?>
                                        <option value="<?= $key ?>" <?= $order['status']===$key?'selected':'' ?>><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>

                            <td style="text-align:right;">
                                <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn-view">Xem <i class="fas fa-arrow-right"></i></a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>

<script>
// Hàm hiển thị thông báo
function showToast(msg, type = 'success') {
    Toastify({
        text: msg,
        duration: 3000,
        gravity: "top", 
        position: "right", 
        style: {
            background: type === 'success' ? "#10b981" : "#ef4444",
            borderRadius: "8px",
            fontSize: "14px",
            boxShadow: "0 4px 12px rgba(0,0,0,0.1)"
        }
    }).showToast();
}

// 1. Cập nhật trạng thái Đơn hàng
async function updateOrderStatus(orderId, newStatus) {
    try {
        // ĐƯỜNG DẪN API ĐÃ SỬA: 'api/update_status.php'
        const res = await fetch('api/update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_order_status',
                order_id: orderId,
                new_status: newStatus
            })
        });

        // Debug lỗi: Đọc text trước khi parse JSON để biết nếu PHP trả về lỗi
        const text = await res.text();
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showToast("Cập nhật trạng thái đơn hàng thành công!");
                // Cập nhật màu sắc select ngay lập tức
                const selectEl = document.querySelector(`select[onchange="updateOrderStatus(${orderId}, this.value)"]`);
                if(selectEl) selectEl.setAttribute('data-val', newStatus);
            } else {
                showToast(data.message || "Lỗi cập nhật", 'error');
            }
        } catch (e) {
            console.error("Lỗi phản hồi từ Server (không phải JSON):", text);
            showToast("Lỗi Code PHP (Xem Console F12)", 'error');
        }

    } catch (err) {
        console.error(err);
        showToast("Lỗi kết nối server", 'error');
    }
}

// 2. Cập nhật trạng thái Thanh toán
async function updatePaymentStatus(orderId, selectElement) {
    const newPayStatus = selectElement.value;
    try {
        const res = await fetch('api/update_status.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                action: 'update_payment_status',
                order_id: orderId,
                new_pay_status: newPayStatus
            })
        });

        const text = await res.text();
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showToast("Cập nhật thanh toán thành công!");
                // Đổi màu badge ngay lập tức
                selectElement.className = `pay-select ${newPayStatus}`;
            } else {
                showToast(data.message || "Lỗi cập nhật", 'error');
            }
        } catch (e) {
            console.error("Lỗi phản hồi từ Server (không phải JSON):", text);
            showToast("Lỗi Code PHP (Xem Console F12)", 'error');
        }

    } catch (err) {
        showToast("Lỗi kết nối server", 'error');
    }
}
</script>
</body>
</html>