<?php
require_once 'includes/config.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

$userId = (int)$_SESSION['user_id'];
$page_title = "Đơn hàng của tôi";

// Map trạng thái đơn hàng
$statusMap = [
    'pending'    => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'shipping'   => 'Đang giao hàng',
    'completed'  => 'Hoàn thành',
    'cancelled'  => 'Đã hủy',
];

// Map phương thức thanh toán
$paymentMap = [
    'cod'  => 'Tiền mặt (COD)',
    'bank' => 'Chuyển khoản',
    'momo' => 'Ví MoMo',
    'card' => 'Thẻ quốc tế'
];

// Lấy danh sách đơn hàng
$sql = "
    SELECT 
        o.*,
        COUNT(oi.id) AS item_count,
        COALESCE(SUM(oi.quantity),0) AS total_qty
    FROM orders o
    LEFT JOIN order_items oi ON oi.order_id = o.id
    WHERE o.user_id = ?
    GROUP BY o.id
    ORDER BY o.created_at DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();

$orders = [];
while ($row = $res->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();

/**
 * Render badge trạng thái đơn hàng
 */
function renderStatusBadge($order, $statusMap) {
    $status = strtolower(trim($order['status'] ?? ''));
    $payment = strtolower(trim($order['payment_method'] ?? ''));
    
    // Nếu là Bank mà chưa xử lý -> Hiện "Chờ xác nhận CK"
    if ($status === 'pending' && $payment === 'bank') {
        return '<span class="order-badge order-badge-wait-confirm"><i class="fas fa-clock" style="margin-right:4px;"></i> Chờ xác nhận</span>';
    }

    $text = $statusMap[$status] ?? ucfirst((string)$status);
    $class = 'order-badge ';
    switch ($status) {
        case 'pending':    $class .= 'order-badge-pending'; break;
        case 'processing': $class .= 'order-badge-processing'; break;
        case 'shipping':   $class .= 'order-badge-shipping'; break;
        case 'completed':  $class .= 'order-badge-completed'; break;
        case 'cancelled':  $class .= 'order-badge-cancelled'; break;
        default:           $class .= 'order-badge-secondary';
    }
    return '<span class="'.$class.'">'.$text.'</span>';
}

include 'includes/header.php';
?>

<style>
    /* CSS Cũ giữ nguyên */
    .box { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 24px; margin: 24px 0; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
    .page-header { margin-bottom: 20px; }
    .page-title { font-size: 20px; font-weight: 700; margin: 0 0 4px; color: #111827; }
    .muted { color: #6b7280; font-size: 13px; }
    
    .table-responsive { overflow-x: auto; border-radius: 12px; border: 1px solid #f3f4f6; }
    .orders-table { width: 100%; min-width: 800px; border-collapse: separate; border-spacing: 0; }
    .orders-table th { background: #f9fafb; text-align: left; padding: 14px 16px; color: #4b5563; font-size: 12px; font-weight: 600; text-transform: uppercase; border-bottom: 1px solid #e5e7eb; white-space: nowrap; }
    .orders-table td { padding: 16px; background: #fff; border-bottom: 1px solid #f3f4f6; vertical-align: middle; font-size: 14px; color: #1f2937; }
    .orders-table tr:last-child td { border-bottom: none; }
    .orders-table tbody tr:hover td { background-color: #fcfcfc; }

    .order-badge { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 99px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .order-badge-pending { background: #fff7ed; color: #c2410c; border: 1px solid #ffedd5; }
    .order-badge-processing { background: #eff6ff; color: #1d4ed8; border: 1px solid #dbeafe; }
    .order-badge-shipping { background: #f0f9ff; color: #0369a1; border: 1px solid #e0f2fe; }
    .order-badge-completed { background: #ecfdf5; color: #15803d; border: 1px solid #d1fae5; }
    .order-badge-cancelled { background: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }
    .order-badge-secondary { background: #f3f4f6; color: #4b5563; border: 1px solid #e5e7eb; }
    .order-badge-wait-confirm { background: #fffbeb; color: #b45309; border: 1px solid #fcd34d; animation: pulse 2s infinite; }

    .btn-view { display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 8px; border: 1px solid #e5e7eb; text-decoration: none; color: #374151; background: #fff; font-weight: 500; font-size: 13px; transition: all 0.2s; white-space: nowrap; }
    .btn-view:hover { background: #f9fafb; border-color: #d1d5db; color: #111827; }

    /* --- CSS MỚI CHO TRẠNG THÁI THANH TOÁN --- */
    .pay-status {
        font-size: 11px;
        font-weight: 600;
        padding: 2px 6px;
        border-radius: 4px;
        margin-top: 4px;
        display: inline-block;
    }
    .pay-status.paid { color: #15803d; background: #dcfce7; border: 1px solid #bbf7d0; }
    .pay-status.unpaid { color: #b45309; background: #fff7ed; border: 1px solid #ffedd5; }
    
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.8; } 100% { opacity: 1; } }
</style>

<div class="container">
    <div class="breadcrumb" style="margin: 20px 0 0;">
        <a href="index.php">Trang chủ</a> <i class="fas fa-chevron-right"></i> <span>Đơn hàng của tôi</span>
    </div>

    <div class="box">
        <div class="page-header">
            <h2 class="page-title">Đơn hàng của tôi</h2>
            <div class="muted">Tổng: <strong><?= count($orders) ?></strong> đơn hàng</div>
        </div>

        <div class="table-responsive">
            <table class="orders-table">
                <thead>
                <tr>
                    <th style="width: 140px;">Mã đơn</th>
                    <th>Thời gian</th>
                    <th>Sản phẩm</th>
                    <th>Thanh toán</th> <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                    <th style="width:120px; text-align: right;">Thao tác</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" style="text-align:center; padding: 60px 20px; color: #9ca3af;">
                            <i class="fas fa-box-open" style="font-size: 48px; margin-bottom: 15px; opacity: 0.3;"></i><br>
                            Bạn chưa có đơn hàng nào.<br>
                            <a href="products.php" style="color: #4f46e5; text-decoration: none; font-weight: 600; margin-top: 10px; display: inline-block;">Mua sắm ngay</a>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td>
                                <strong style="color:#111827;"><?= htmlspecialchars($o['order_code']) ?></strong>
                                <br>
                                <span class="muted" style="font-size:11px;">#<?= (int)$o['id'] ?></span>
                            </td>

                            <td>
                                <?= date('d/m/Y', strtotime($o['created_at'])) ?>
                                <br>
                                <span class="muted" style="font-size:11px;"><?= date('H:i', strtotime($o['created_at'])) ?></span>
                            </td>

                            <td>
                                <strong><?= (int)$o['total_qty'] ?></strong> sản phẩm
                                <div class="muted" style="font-size:11px;">(<?= (int)$o['item_count'] ?> loại)</div>
                            </td>

                            <td>
                                <div style="font-weight: 500; font-size: 13px;">
                                    <?= $paymentMap[$o['payment_method']] ?? 'Khác' ?>
                                </div>
                                <?php if ($o['payment_status'] === 'paid'): ?>
                                    <span class="pay-status paid"><i class="fas fa-check"></i> Đã thanh toán</span>
                                <?php else: ?>
                                    <span class="pay-status unpaid"><i class="fas fa-hourglass-half"></i> Chưa thanh toán</span>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong style="color:#ef4444;"><?= formatMoney((int)$o['total']) ?></strong>
                            </td>

                            <td>
                                <?= renderStatusBadge($o, $statusMap) ?>
                            </td>

                            <td style="text-align: right;">
                                <a class="btn-view" href="my-order-detail.php?id=<?= (int)$o['id'] ?>">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>