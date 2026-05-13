<?php
require_once "_auth.php";
require_once "../includes/config.php";

$alertSuccess = '';
$alertError   = '';

// --- HÀM TẠO SLUG (Tên không dấu) ---
function create_slug($string) {
    $search = array(
        '#(à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ)#',
        '#(è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ)#',
        '#(ì|í|ị|ỉ|ĩ)#',
        '#(ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ)#',
        '#(ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ)#',
        '#(ỳ|ý|ỵ|ỷ|ỹ)#',
        '#(đ)#',
        '#(À|Á|Ạ|Ả|Ã|Â|Ầ|Ấ|Ậ|Ẩ|Ẫ|Ă|Ằ|Ắ|Ặ|Ẳ|Ẵ)#',
        '#(È|É|Ẹ|Ẻ|Ẽ|Ê|Ề|Ế|Ệ|Ể|Ễ)#',
        '#(Ì|Í|Ị|Ỉ|Ĩ)#',
        '#(Ò|Ó|Ọ|Ỏ|Õ|Ô|Ồ|Ố|Ộ|Ổ|Ỗ|Ơ|Ờ|Ớ|Ợ|Ở|Ỡ)#',
        '#(Ù|Ú|Ụ|Ủ|Ũ|Ư|Ừ|Ứ|Ự|Ử|Ữ)#',
        '#(Ỳ|Ý|Ỵ|Ỷ|Ỹ)#',
        '#(Đ)#',
        "/[^a-zA-Z0-9\-\_]/",
    );
    $replace = array(
        'a', 'e', 'i', 'o', 'u', 'y', 'd',
        'A', 'E', 'I', 'O', 'U', 'Y', 'D',
        '-',
    );
    $string = preg_replace($search, $replace, $string);
    $string = preg_replace('/(-)+/', '-', $string);
    return strtolower($string);
}

// --- 1. XỬ LÝ XÓA ---
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // Kiểm tra xem danh mục có sản phẩm không (nếu bảng products có cột category_id)
    // Nếu bảng products chưa có cột category_id thì bỏ qua check này hoặc thêm cột vào DB
    $check = $conn->query("SELECT id FROM products WHERE category_slug=$id LIMIT 1");
    
    if ($check && $check->num_rows > 0) {
        $alertError = "Không thể xóa! Đang có sản phẩm thuộc danh mục này.";
    } else {
        if ($conn->query("DELETE FROM categories WHERE id=$id")) {
            header("Location: categories.php?msg=deleted");
            exit();
        } else {
            $alertError = "Lỗi xóa: " . $conn->error;
        }
    }
}

// --- 2. XỬ LÝ THÊM / SỬA ---
if (isset($_POST['save_cat'])) {
    $id   = isset($_POST['cat_id']) ? (int)$_POST['cat_id'] : 0;
    $name = $conn->real_escape_string($_POST['name']);
    $slug = $conn->real_escape_string($_POST['slug']);

    // Nếu người dùng không nhập slug, tự tạo từ tên
    if (empty($slug)) {
        $slug = create_slug($name);
    }

    if (empty($name)) {
        $alertError = "Tên danh mục không được để trống!";
    } else {
        if ($id > 0) {
            // UPDATE
            $sql = "UPDATE categories SET name='$name', slug='$slug' WHERE id=$id";
            if ($conn->query($sql)) $alertSuccess = "Cập nhật thành công!";
            else $alertError = "Lỗi: " . $conn->error;
        } else {
            // INSERT
            $sql = "INSERT INTO categories (name, slug) VALUES ('$name', '$slug')";
            if ($conn->query($sql)) $alertSuccess = "Thêm mới thành công!";
            else $alertError = "Lỗi: " . $conn->error;
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] == 'deleted') {
    $alertSuccess = "Đã xóa danh mục thành công!";
}

// --- LẤY DANH SÁCH ---
$categories = [];
$res = $conn->query("SELECT * FROM categories ORDER BY id ASC");
while ($row = $res->fetch_assoc()) {
    $categories[] = $row;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý Danh mục - LaptopStore</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <style>
        .admin-form-box { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; padding: 20px; }
        
        /* Modal */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
        .modal-content { background-color: #fff; margin: 10% auto; padding: 25px; border-radius: 12px; width: 450px; max-width: 90%; position: relative; animation: slideDown 0.3s; }
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
            <li><a href="categories.php" class="active"><i class="fas fa-folder"></i><span>Danh mục</span></a></li>
            <li><a href="brands.php"><i class="fas fa-tags"></i><span>Hãng sản xuất</span></a></li>
            <li><a href="users.php"><i class="fas fa-users"></i><span>Người dùng</span></a></li>
            <li><a href="../index.php"><i class="fas fa-store"></i><span>Xem trang khách</span></a></li>
            <li><a href="logout.php"><i class="fas fa-right-from-bracket"></i><span>Đăng xuất</span></a></li>
        </ul>
        <div class="admin-sidebar-footer">© <?= date('Y') ?> LaptopStore.</div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="admin-top-title">Quản lý Danh mục</div>
                <div class="admin-top-sub">Phân loại sản phẩm (Gaming, Office, Macbook...)</div>
            </div>
            <button onclick="openModal()" class="btn btn-primary" style="width:auto;">
                <i class="fas fa-plus"></i> Thêm danh mục
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
                            <th>Tên danh mục</th>
                            <th>Slug (Đường dẫn)</th>
                            <th style="text-align:right;">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($categories)): ?>
                            <tr><td colspan="4" style="text-align:center; padding:20px;">Chưa có danh mục nào.</td></tr>
                        <?php else: ?>
                            <?php foreach ($categories as $c): ?>
                                <tr>
                                    <td>#<?= $c['id'] ?></td>
                                    <td><strong><?= htmlspecialchars($c['name']) ?></strong></td>
                                    <td style="color:#6b7280; font-family:monospace;"><?= htmlspecialchars($c['slug']) ?></td>
                                    <td style="text-align:right;">
                                        <button onclick='editCat(<?= json_encode($c) ?>)' class="btn btn-outline btn-small" title="Sửa">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <a href="categories.php?delete=<?= $c['id'] ?>" onclick="return confirm('Bạn có chắc muốn xóa danh mục này?')" class="btn btn-outline btn-small" style="color:#dc2626; border-color:#dc2626;" title="Xóa">
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

<div id="catModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h3 id="modalTitle" style="margin-top:0; margin-bottom:20px;">Thêm danh mục mới</h3>
        
        <form method="POST">
            <input type="hidden" name="cat_id" id="cat_id" value="0">

            <div class="form-group">
                <label>Tên danh mục <span style="color:red">*</span></label>
                <input type="text" name="name" id="cat_name" class="form-control" required placeholder="Ví dụ: Laptop Gaming" onkeyup="generateSlug()">
            </div>

            <div class="form-group">
                <label>Slug (Đường dẫn - Tự động tạo)</label>
                <input type="text" name="slug" id="cat_slug" class="form-control" placeholder="vi-du-laptop-gaming">
                <small style="color:#888;">Để trống sẽ tự tạo từ tên.</small>
            </div>

            <button type="submit" name="save_cat" class="btn btn-primary">Lưu lại</button>
        </form>
    </div>
</div>

<script>
    const modal = document.getElementById("catModal");
    const modalTitle = document.getElementById("modalTitle");
    const inpId = document.getElementById("cat_id");
    const inpName = document.getElementById("cat_name");
    const inpSlug = document.getElementById("cat_slug");

    function openModal() {
        modalTitle.innerText = "Thêm danh mục mới";
        inpId.value = "0";
        inpName.value = "";
        inpSlug.value = "";
        modal.style.display = "block";
    }

    function editCat(data) {
        modalTitle.innerText = "Cập nhật danh mục";
        inpId.value = data.id;
        inpName.value = data.name;
        inpSlug.value = data.slug;
        modal.style.display = "block";
    }

    function closeModal() {
        modal.style.display = "none";
    }

    window.onclick = function(event) {
        if (event.target == modal) closeModal();
    }

    // Hàm tạo slug tự động bằng JS khi nhập tên
    function generateSlug() {
        let str = inpName.value;
        str = str.toLowerCase();
        str = str.replace(/à|á|ạ|ả|ã|â|ầ|ấ|ậ|ẩ|ẫ|ă|ằ|ắ|ặ|ẳ|ẵ/g,"a"); 
        str = str.replace(/è|é|ẹ|ẻ|ẽ|ê|ề|ế|ệ|ể|ễ/g,"e"); 
        str = str.replace(/ì|í|ị|ỉ|ĩ/g,"i"); 
        str = str.replace(/ò|ó|ọ|ỏ|õ|ô|ồ|ố|ộ|ổ|ỗ|ơ|ờ|ớ|ợ|ở|ỡ/g,"o"); 
        str = str.replace(/ù|ú|ụ|ủ|ũ|ư|ừ|ứ|ự|ử|ữ/g,"u"); 
        str = str.replace(/ỳ|ý|ỵ|ỷ|ỹ/g,"y"); 
        str = str.replace(/đ/g,"d");
        str = str.replace(/!|@|%|\^|\*|\(|\)|\+|\=|\<|\>|\?|\/|,|\.|\:|\;|\'|\"|\&|\#|\[|\]|~|\$|_|`|-|{|}|\||\\/g," ");
        str = str.replace(/ + /g," ");
        str = str.trim(); 
        str = str.replace(/\s+/g, '-');
        inpSlug.value = str;
    }
</script>

</body>
</html>