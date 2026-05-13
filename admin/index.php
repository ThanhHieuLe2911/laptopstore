<?php
require_once "_auth.php";
require_once "../includes/config.php";

date_default_timezone_set('Asia/Ho_Chi_Minh');

$name = $_SESSION['admin_name'] ?? 'Admin';

// ====== 1. CÁC CHỈ SỐ CƠ BẢN ======
$totalProducts = (int)($conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'] ?? 0);
$totalOrders   = (int)($conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'] ?? 0);
$totalUsers    = (int)($conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'] ?? 0);
// Thêm thống kê danh mục và hãng
$totalCategories = (int)($conn->query("SELECT COUNT(*) AS c FROM categories")->fetch_assoc()['c'] ?? 0);
$totalBrands     = (int)($conn->query("SELECT COUNT(*) AS c FROM brands")->fetch_assoc()['c'] ?? 0);


// Doanh thu tổng (chỉ tính đơn Completed)
$revenueRow = $conn->query("SELECT COALESCE(SUM(total),0) AS revenue FROM orders WHERE status = 'completed'")->fetch_assoc();
$totalRevenue = (float)($revenueRow['revenue'] ?? 0);

// ====== 2. ĐẾM TRẠNG THÁI ĐƠN HÀNG ======
$statusCounts = ['pending' => 0, 'processing' => 0, 'shipping' => 0, 'completed' => 0, 'cancelled' => 0];
$resStatus = $conn->query("SELECT status, COUNT(*) AS c FROM orders GROUP BY status");
while ($row = $resStatus->fetch_assoc()) {
    if (isset($statusCounts[$row['status']])) {
        $statusCounts[$row['status']] = (int)$row['c'];
    }
}

// ====== 3. THỐNG KÊ BIỂU ĐỒ (MỚI) ======

// A. Doanh thu 7 ngày gần nhất
$dailyLabels = [];
$dailyData = [];
// Tạo mảng 7 ngày qua (bao gồm hôm nay)
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dailyLabels[] = date('d/m', strtotime($date)); // Nhãn hiển thị (VD: 15/10)
    
    // Query doanh thu ngày đó
    $sql = "SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE DATE(created_at) = '$date' AND status = 'completed'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    $dailyData[] = (float)$row['total'];
}

// B. Doanh thu 12 tháng gần nhất
$monthlyLabels = [];
$monthlyData = [];
// Tạo mảng 12 tháng qua
for ($i = 11; $i >= 0; $i--) {
    $monthStart = date('Y-m-01', strtotime("-$i months"));
    $monthEnd = date('Y-m-t', strtotime("-$i months"));
    $monthLabel = date('m/Y', strtotime($monthStart));
    
    $monthlyLabels[] = $monthLabel;
    
    $sql = "SELECT COALESCE(SUM(total), 0) as total FROM orders WHERE (DATE(created_at) BETWEEN '$monthStart' AND '$monthEnd') AND status = 'completed'";
    $res = $conn->query($sql);
    $row = $res->fetch_assoc();
    $monthlyData[] = (float)$row['total'];
}

// ====== 4. ĐƠN HÀNG MỚI NHẤT ======
$latestOrders = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 8");

// Helpers
function formatVND($n) { return number_format((float)$n, 0, ',', '.') . " ₫"; }
function renderOrderStatusBadge(string $status): string {
    $map = [
        'pending' => ['Chờ xử lý', 'badge-pending'],
        'processing' => ['Đang xử lý', 'badge-processing'],
        'shipping' => ['Đang giao', 'badge-shipping'],
        'completed' => ['Hoàn thành', 'badge-completed'],
        'cancelled' => ['Đã hủy', 'badge-cancelled']
    ];
    $item = $map[$status] ?? [$status, 'badge-secondary'];
    return '<span class="admin-badge-status '.$item[1].'"><i class="fas fa-circle"></i>'.$item[0].'</span>';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin - LaptopStore</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        /* CSS Bổ sung cho Dashboard */
        .admin-badge-status{display:inline-flex;align-items:center;gap:6px;padding:5px 10px;border-radius:999px;font-size:12px;font-weight:600;white-space:nowrap;}
        .admin-badge-status i{font-size:8px;}
        .badge-pending{background:#fef3c7;color:#92400e;}
        .badge-processing{background:#dbeafe;color:#1d4ed8;}
        .badge-shipping{background:#e0f2fe;color:#075985;}
        .badge-completed{background:#dcfce7;color:#166534;}
        .badge-cancelled{background:#fee2e2;color:#b91c1c;}
        .badge-secondary{background:#f3f4f6;color:#374151;}

        .admin-grid-4{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-top:16px;}
        @media (max-width: 1100px){.admin-grid-4{grid-template-columns:repeat(2,1fr);}}
        @media (max-width: 640px){.admin-grid-4{grid-template-columns:1fr;}}

        .admin-mini-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:14px;display:flex;align-items:center;justify-content:space-between;box-shadow: 0 2px 4px rgba(0,0,0,0.02);}
        .admin-mini-card .left small{color:var(--text-gray);display:block;margin-bottom:4px;font-size:13px;}
        .admin-mini-card .left strong{font-size:20px;color:#111827;}
        .admin-mini-card .icon{width:40px;height:40px;border-radius:10px;display:flex;align-items:center;justify-content:center;background:#f3f4f6;color:#4b5563;}
        
        .charts-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 24px; }
        .chart-container { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .chart-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .chart-header h3 { font-size: 16px; font-weight: 600; color: #374151; margin: 0; }
        @media (max-width: 1000px) { .charts-row { grid-template-columns: 1fr; } }
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
            <div class="admin-user-avatar"><?= strtoupper(substr($name, 0, 1)) ?></div>
            <div>
                <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($name) ?></div>
                <div style="font-size:11px;color:#9ca3af;">Quản trị viên</div>
            </div>
        </div>
        <ul class="admin-nav">
            <li><a href="index.php" class="active"><i class="fas fa-chart-line"></i><span>Tổng quan</span></a></li>
            <li><a href="orders.php"><i class="fas fa-receipt"></i><span>Đơn hàng</span><small><?= $totalOrders ?></small></a></li>
            <li><a href="products.php"><i class="fas fa-box-open"></i><span>Sản phẩm</span><small><?= $totalProducts ?></small></a></li>
            <li><a href="categories.php"><i class="fas fa-folder"></i><span>Danh mục</span><small><?= $totalCategories ?></small></a></li>
            <li><a href="brands.php"><i class="fas fa-tags"></i><span>Hãng sản xuất</span><small><?= $totalBrands ?></small></a></li>
            
            <li><a href="users.php"><i class="fas fa-users"></i><span>Người dùng</span><small><?= $totalUsers ?></small></a></li>
            <li><a href="../index.php"><i class="fas fa-store"></i><span>Xem trang khách</span></a></li>
            <li><a href="logout.php"><i class="fas fa-right-from-bracket"></i><span>Đăng xuất</span></a></li>
        </ul>
        <div class="admin-sidebar-footer">© <?= date('Y') ?> LaptopStore.</div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="admin-top-title">Tổng quan</div>
                <div class="admin-top-sub">Hôm nay là <?= date('d/m/Y') ?> • Thống kê kinh doanh</div>
            </div>
            <div class="admin-top-actions">
                <div class="admin-pill"><i class="far fa-clock"></i><span><?= date('H:i') ?></span></div>
                <a class="admin-icon-btn" href="orders.php"><i class="far fa-bell"></i></a>
            </div>
        </div>

        <section class="admin-grid-3">
            <div class="admin-card">
                <small>Tổng doanh thu (Đã hoàn thành)</small>
                <div class="value" style="color:#10b981;"><?= formatVND($totalRevenue) ?></div>
                <div class="admin-card-icon"><i class="fas fa-sack-dollar"></i></div>
            </div>
            <div class="admin-card">
                <small>Tổng đơn hàng</small>
                <div class="value"><?= $totalOrders ?></div>
                <div class="admin-card-icon"><i class="fas fa-receipt"></i></div>
            </div>
            <div class="admin-card">
                <small>Sản phẩm & Danh mục</small>
                <div class="value"><?= $totalProducts ?> sp</div>
                <div style="font-size:11px;color:#9ca3af; margin-top:4px;">
                    <?= $totalCategories ?> danh mục | <?= $totalBrands ?> hãng
                </div>
                <div class="admin-card-icon"><i class="fas fa-boxes"></i></div>
            </div>
        </section>

        <section class="admin-grid-4">
            <div class="admin-mini-card">
                <div class="left"><small>Chờ xử lý</small><strong><?= $statusCounts['pending'] ?></strong></div>
                <div class="icon" style="color:#d97706;background:#fef3c7;"><i class="fas fa-clock"></i></div>
            </div>
            <div class="admin-mini-card">
                <div class="left"><small>Đang xử lý</small><strong><?= $statusCounts['processing'] ?></strong></div>
                <div class="icon" style="color:#2563eb;background:#dbeafe;"><i class="fas fa-spinner"></i></div>
            </div>
            <div class="admin-mini-card">
                <div class="left"><small>Đang giao</small><strong><?= $statusCounts['shipping'] ?></strong></div>
                <div class="icon" style="color:#0891b2;background:#cffafe;"><i class="fas fa-truck"></i></div>
            </div>
            <div class="admin-mini-card">
                <div class="left"><small>Hoàn thành</small><strong><?= $statusCounts['completed'] ?></strong></div>
                <div class="icon" style="color:#059669;background:#d1fae5;"><i class="fas fa-check-circle"></i></div>
            </div>
        </section>

        <section class="charts-row">
            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class="fas fa-calendar-week"></i> Doanh thu 7 ngày qua</h3>
                </div>
                <canvas id="dailyChart"></canvas>
            </div>
            
            <div class="chart-container">
                <div class="chart-header">
                    <h3><i class="fas fa-calendar-alt"></i> Doanh thu 12 tháng qua</h3>
                </div>
                <canvas id="monthlyChart"></canvas>
            </div>
        </section>

        <section class="admin-box" style="margin-top:24px;">
            <div class="admin-box-header">
                <h2>Đơn hàng mới nhất</h2>
                <a href="orders.php" class="btn btn-outline" style="padding:6px 14px;font-size:12px;">Xem tất cả</a>
            </div>
            <div style="overflow-x:auto; margin-top:10px;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>Tổng tiền</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($order = $latestOrders->fetch_assoc()): ?>
                        <tr>
                            <td style="font-family:monospace;font-weight:bold;">#<?= $order['order_code'] ?></td>
                            <td><?= htmlspecialchars($order['full_name']) ?></td>
                            <td><strong><?= formatVND($order['total']) ?></strong></td>
                            <td><?= renderOrderStatusBadge($order['status']) ?></td>
                            <td><?= date('d/m H:i', strtotime($order['created_at'])) ?></td>
                            <td style="text-align:right;">
                                <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn btn-outline btn-small"><i class="far fa-eye"></i></a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</div>

<script>
    // Dữ liệu từ PHP
    const dailyLabels = <?= json_encode($dailyLabels) ?>;
    const dailyData = <?= json_encode($dailyData) ?>;
    
    const monthlyLabels = <?= json_encode($monthlyLabels) ?>;
    const monthlyData = <?= json_encode($monthlyData) ?>;

    // 1. Biểu đồ ngày (Line Chart)
    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: dailyLabels,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: dailyData,
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.3 // Làm mượt đường vẽ
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // 2. Biểu đồ tháng (Bar Chart)
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: monthlyLabels,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: monthlyData,
                backgroundColor: '#10b981',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });
</script>
</body>
</html>