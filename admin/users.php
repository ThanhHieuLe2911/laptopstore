<?php
require_once "_auth.php";
require_once "../includes/config.php";

date_default_timezone_set('Asia/Ho_Chi_Minh');

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$currentAdminId = $_SESSION['user_id'] ?? 0;

$alertSuccess = '';
$alertError   = '';

// --- 1. XỬ LÝ XÓA NGƯỜI DÙNG ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];

    // Kiểm tra quyền trước khi xóa
    $check = $conn->query("SELECT role FROM users WHERE id = $id");
    $targetUser = $check->fetch_assoc();

    if ($id == $currentAdminId) {
        $alertError = "Không thể xóa tài khoản đang đăng nhập!";
    } elseif ($targetUser && strtolower($targetUser['role']) === 'admin') {
        $alertError = "Không thể xóa tài khoản Admin!";
    } else {
        if ($conn->query("DELETE FROM users WHERE id = $id")) {
            header("Location: users.php?msg=deleted");
            exit();
        } else {
            $alertError = "Lỗi khi xóa: " . $conn->error;
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $alertSuccess = "Đã xóa người dùng thành công!";
}

// --- 2. LẤY DANH SÁCH USER (CHỈ LẤY ROLE != ADMIN) ---
$q = trim($_GET['q'] ?? '');
$users = [];

if ($q !== '') {
    $like = "%{$q}%";
    // Thêm điều kiện: role != 'admin'
    $stmt = $conn->prepare("
        SELECT id, full_name, email, phone, address, role, created_at
        FROM users
        WHERE role != 'admin' 
        AND (full_name LIKE ? OR email LIKE ?)
        ORDER BY id DESC
    ");
    $stmt->bind_param("ss", $like, $like);
} else {
    // Thêm điều kiện: role != 'admin'
    $stmt = $conn->prepare("
        SELECT id, full_name, email, phone, address, role, created_at
        FROM users
        WHERE role != 'admin'
        ORDER BY id DESC
    ");
}

$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}
$stmt->close();

function renderRoleBadge(string $role): string {
    return '<span class="role-badge role-user">User</span>';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý người dùng - LaptopStore</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
<style>
    .admin-form-box { background:#ffffff; border-radius:12px; border:1px solid #e5e7eb; padding:24px; margin-top:16px; }
    .users-topbar{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:16px; flex-wrap:wrap; }
    .users-title{font-size:18px;font-weight:700;margin:0;}
    .users-sub{font-size:13px;color:#6b7280;margin-top:4px;}
    .users-search{ display:flex; align-items:center; gap:8px; }   
    .users-search input{ width:380px; max-width:65vw; padding:10px 12px; border-radius:12px; border:1px solid #e5e7eb; outline:none; }
    .users-search button{ width:44px;height:44px; border-radius:12px; border:1px solid #e5e7eb; background:#f9fafb; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; }
    .users-search a{ height:44px; padding:0 12px; border-radius:12px; border:1px solid #e5e7eb; background:#fff; text-decoration:none; color:#374151; display:inline-flex; align-items:center; gap:6px; }
    
    .users-table{ width:100%; table-layout:fixed; border-collapse:separate; border-spacing:0 10px; font-size:13px; }
    .users-table thead th{ padding:10px 14px; font-weight:700; color:#6b7280; font-size:15px; text-transform:uppercase; border-bottom:1px solid #e5e7eb; white-space:nowrap; }
    .users-table tbody tr{ background:#fff; box-shadow:0 4px 10px rgba(15,23,42,0.04); border-radius:12px; font-size:15px; }
    .users-table tbody td{ padding:16px 14px; border-bottom:none; vertical-align:middle; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; font-size:15px; }

    /* width theo cột */
    .users-table th:nth-child(1), .users-table td:nth-child(1){ width:60px; }
    .users-table th:nth-child(2), .users-table td:nth-child(2){ width:180px; } 
    .users-table th:nth-child(3), .users-table td:nth-child(3){ width:220px; color:#111827;} 
    .users-table th:nth-child(4), .users-table td:nth-child(4){ width:120px; } 
    .users-table th:nth-child(6), .users-table td:nth-child(6){ width:100px; text-align:center; } 
    .users-table th:nth-child(7), .users-table td:nth-child(7){ width:140px; text-align:center; } 
    .users-table th:nth-child(8), .users-table td:nth-child(8){ width:80px; text-align:center; } 

    .users-table tbody tr td:first-child{ border-top-left-radius:12px; border-bottom-left-radius:12px; }
    .users-table tbody tr td:last-child{ border-top-right-radius:12px; border-bottom-right-radius:12px; }

    .role-badge{ padding:6px 12px; border-radius:999px; font-size:13px; font-weight:800; display:inline-flex; align-items:center; justify-content:center; min-width:70px; }
    .role-user { background:#e0f2fe; color:#075985; }
    .muted{ color:#6b7280; font-size:13px; }

    .btn-delete { color: #dc2626; background: #fee2e2; width: 32px; height: 32px; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: 0.2s; }
    .btn-delete:hover { background: #dc2626; color: white; }
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
            <div>
                <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($adminName) ?></div>
                <div style="font-size:11px;color:#9ca3af;">Quản trị viên</div>
            </div>
        </div>
        <ul class="admin-nav">
            <li><a href="index.php"><i class="fas fa-chart-line"></i><span>Tổng quan</span></a></li>
            <li><a href="orders.php"><i class="fas fa-receipt"></i><span>Đơn hàng</span></a></li>
            <li><a href="products.php"><i class="fas fa-box-open"></i><span>Sản phẩm</span></a></li>
            <li><a href="categories.php"><i class="fas fa-folder"></i><span>Danh mục</span></a></li>
            <li><a href="brands.php"><i class="fas fa-tags"></i><span>Hãng sản xuất</span></a></li>
            <li><a href="users.php" class="active"><i class="fas fa-users"></i><span>Người dùng</span></a></li>
            <li><a href="../index.php"><i class="fas fa-store"></i><span>Xem trang khách</span></a></li>
            <li><a href="logout.php"><i class="fas fa-right-from-bracket"></i><span>Đăng xuất</span></a></li>
        </ul>
        <div class="admin-sidebar-footer">© <?= date('Y') ?> LaptopStore. All rights reserved.</div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="admin-top-title">Quản lý người dùng</div>
                <div class="admin-top-sub">Danh sách khách hàng (Không bao gồm Admin)</div>
            </div>
        </div>

        <section class="admin-form-box">
            <?php if ($alertSuccess): ?>
                <div style="padding:12px; background:#dcfce7; color:#166534; border-radius:8px; margin-bottom:15px; text-align:center;">
                    <i class="fas fa-check-circle"></i> <?= $alertSuccess ?>
                </div>
            <?php endif; ?>
            <?php if ($alertError): ?>
                <div style="padding:12px; background:#fee2e2; color:#b91c1c; border-radius:8px; margin-bottom:15px; text-align:center;">
                    <i class="fas fa-exclamation-triangle"></i> <?= $alertError ?>
                </div>
            <?php endif; ?>

            <div class="users-topbar">
                <div>
                    <h2 class="users-title">Danh sách khách hàng</h2>
                    <div class="users-sub">
                        Tổng: <strong><?= count($users) ?></strong> người dùng
                        <?php if ($q !== ''): ?>
                            • Từ khóa: <strong><?= htmlspecialchars($q) ?></strong>
                        <?php endif; ?>
                    </div>
                </div>

                <form class="users-search" method="get">
                    <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Tìm tên hoặc email...">
                    <button type="submit" title="Tìm kiếm"><i class="fas fa-search"></i></button>
                    <?php if ($q !== ''): ?>
                        <a href="users.php" title="Xóa lọc"><i class="fas fa-xmark"></i> Clear</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="table-wrapper">
                <table class="users-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Họ tên</th>
                        <th>Email</th>
                        <th>SĐT</th>
                        <th>Địa chỉ</th>
                        <th>Role</th>
                        <th>Ngày tạo</th>
                        <th style="text-align:center;">Xóa</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="8" style="text-align:center; padding:30px; color:#6b7280;">Không có khách hàng nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><strong>#<?= (int)$u['id'] ?></strong></td>
                                <td><?= htmlspecialchars($u['full_name'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($u['email'] ?? '-') ?></td>
                                <td class="muted"><?= htmlspecialchars($u['phone'] ?? '-') ?></td>
                                <td class="muted" style="max-width:250px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                                    <?= htmlspecialchars($u['address'] ?? '-') ?>
                                </td>
                                <td><?= renderRoleBadge($u['role'] ?? 'user') ?></td>
                                <td class="muted">
                                    <?php
                                        $dt = $u['created_at'] ?? '';
                                        echo $dt ? date('d/m/y', strtotime($dt)) : '-';
                                    ?>
                                </td>
                                <td style="text-align:center;">
                                    <a href="users.php?delete=<?= $u['id'] ?>" 
                                       onclick="return confirm('CẢNH BÁO: Xóa người dùng này sẽ xóa toàn bộ đơn hàng của họ!\nBạn chắc chắn muốn xóa?')" 
                                       class="btn-delete" title="Xóa người dùng">
                                        <i class="fas fa-trash"></i>
                                    </a>
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
</body>
</html>