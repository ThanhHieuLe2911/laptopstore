<?php
require_once "_auth.php";
require_once "../includes/config.php";

$alertSuccess = '';
$alertError   = '';

// --- 1. XỬ LÝ XÓA HÃNG ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Kiểm tra xem hãng này có sản phẩm nào không?
    // (Lưu ý: Nếu bảng products của bạn cột hãng là 'brand' thì giữ nguyên, nếu là tên khác hãy sửa lại)
    $check = $conn->query("SELECT id FROM products WHERE brand=$id LIMIT 1");
    
    if ($check && $check->num_rows > 0) {
        $alertError = "Không thể xóa! Đang có sản phẩm thuộc hãng này.";
    } else {
        if ($conn->query("DELETE FROM brands WHERE id=$id")) {
            header("Location: brands.php?msg=deleted");
            exit();
        } else {
            $alertError = "Lỗi xóa: " . $conn->error;
        }
    }
}

// --- 2. XỬ LÝ THÊM / SỬA ---
if (isset($_POST['save_brand'])) {
    $id   = isset($_POST['brand_id']) ? (int)$_POST['brand_id'] : 0;
    $name = $conn->real_escape_string($_POST['name']);

    if (empty($name)) {
        $alertError = "Tên hãng không được để trống!";
    } else {
        if ($id > 0) {
            // UPDATE (Sửa)
            $sql = "UPDATE brands SET name='$name' WHERE id=$id";
            if ($conn->query($sql)) $alertSuccess = "Cập nhật hãng thành công!";
            else $alertError = "Lỗi: " . $conn->error;
        } else {
            // INSERT (Thêm mới)
            $sql = "INSERT INTO brands (name) VALUES ('$name')";
            if ($conn->query($sql)) $alertSuccess = "Thêm hãng mới thành công!";
            else $alertError = "Lỗi: " . $conn->error;
        }
    }
}

// Thông báo sau khi redirect xóa
if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $alertSuccess = "Đã xóa hãng thành công!";
}

// --- LẤY DANH SÁCH HÃNG ---
$brands = [];
$res = $conn->query("SELECT * FROM brands ORDER BY id DESC");
while ($row = $res->fetch_assoc()) {
    $brands[] = $row;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Hãng sản xuất - LaptopStore</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        .admin-form-box { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        
        /* Modal Style */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 25px; border-radius: 12px; width: 400px; max-width: 90%; position: relative; animation: slideDown 0.3s; }
        @keyframes slideDown { from {top: -50px; opacity: 0;} to {top: 0; opacity: 1;} }
        .close { position: absolute; right: 20px; top: 15px; font-size: 24px; cursor: pointer; color: #666; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 600; font-size: 14px; }
        .form-control { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
        .btn-primary { width: 100%; padding: 10px; background: #4f46e5; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; }
        .btn-primary:hover { background: #4338ca; }
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
            <div class="admin-user-avatar"><?= strtoupper(substr($_SESSION['admin_name']??'A', 0, 1)) ?></div>
            <div>
                <div style="font-size:13px;font-weight:600;"><?= htmlspecialchars($_SESSION['admin_name']??'Admin') ?></div>
                <div style="font-size:11px;color:#9ca3af;">Quản trị viên</div>
            </div>
        </div>
        <ul class="admin-nav">
            <li><a href="index.php"><i class="fas fa-chart-line"></i><span>Tổng quan</span></a></li>
            <li><a href="orders.php"><i class="fas fa-receipt"></i><span>Đơn hàng</span></a></li>
            <li><a href="products.php"><i class="fas fa-box-open"></i><span>Sản phẩm</span></a></li>
            <li><a href="categories.php"><i class="fas fa-folder"></i><span>Danh mục</span></a></li>
            <li><a href="brands.php" class="active"><i class="fas fa-tags"></i><span>Hãng sản xuất</span></a></li>
            <li><a href="users.php"><i class="fas fa-users"></i><span>Người dùng</span></a></li>
            <li><a href="../index.php"><i class="fas fa-store"></i><span>Xem trang khách</span></a></li>
            <li><a href="logout.php"><i class="fas fa-right-from-bracket"></i><span>Đăng xuất</span></a></li>
        </ul>
        <div class="admin-sidebar-footer">© <?= date('Y') ?> LaptopStore.</div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="admin-top-title">Quản lý Hãng</div>
                <div class="admin-top-sub">Danh sách thương hiệu laptop</div>
            </div>
            <button onclick="openModal()" class="btn btn-primary" style="width:auto;">
                <i class="fas fa-plus"></i> Thêm hãng mới
            </button>
        </div>

        <section class="admin-form-box">
            <?php if ($alertSuccess): ?>
                <div style="padding:12px; background:#dcfce7; color:#166534; border-radius:6px; margin-bottom:15px;">
                    <i class="fas fa-check-circle"></i> <?= $alertSuccess ?>
                </div>
            <?php endif; ?>
            <?php if ($alertError): ?>
                <div style="padding:12px; background:#fee2e2; color:#b91c1c; border-radius:6px; margin-bottom:15px;">
                    <i class="fas fa-exclamation-circle"></i> <?= $alertError ?>
                </div>
            <?php endif; ?>

            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th style="width: 60px;">ID</th>
                            <th>Tên hãng</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($brands)): ?>
                            <tr><td colspan="3" style="text-align:center; padding:20px;">Chưa có hãng nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($brands as $b): ?>
                                <tr>
                                    <td>#<?= $b['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($b['name']) ?></strong></td>
                                    <td style="text-align:right;">
                                        <button onclick='editBrand(<?= json_encode($b) ?>)' class="btn btn-outline btn-small" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="brands.php?delete=<?= $b['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa hãng <?= htmlspecialchars($b['name']) ?>?')" class="btn btn-outline btn-small" style="color:#dc2626; border-color:#dc2626;" title="Xóa">
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

<div id="brandModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3 id="modalTitle" style="margin-top:0; margin-bottom:20px;">Thêm hãng mới</h3>
        
        <form method="POST">
            <input type="hidden" name="brand_id" id="brand_id" value="0">

            <div class="form-group">
                <label>Tên hãng <span style="color:red">*</span></label>
                <input type="text" name="name" id="brand_name" class="form-control" required placeholder="Ví dụ: Dell, Macbook, Asus...">
            </div>

            <button type="submit" name="save_brand" class="btn btn-primary">Lưu lại</button>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById("brandModal");
    const modalTitle = document.getElementById("modalTitle");
    const inpId = document.getElementById("brand_id");
    const inpName = document.getElementById("brand_name");

    // Mở form Thêm mới
    function openModal() {
        modalTitle.innerText = "Thêm hãng mới";
        inpId.value = "0";
        inpName.value = "";
        modal.style.display = "block";
    }

    // Mở form Sửa (đổ dữ liệu cũ vào)
    function editBrand(data) {
        modalTitle.innerText = "Cập nhật hãng";
        inpId.value = data.id;
        inpName.value = data.name;
        modal.style.display = "block";
    }

    function closeModal() {
        modal.style.display = "none";
    }

    // Đóng khi click ra ngoài popup
    window.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

</body>
</html>