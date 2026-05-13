<?php
require_once "_auth.php";
require_once "../includes/config.php";

date_default_timezone_set('Asia/Ho_Chi_Minh');

// --------- CẤU HÌNH UPLOAD ----------
$uploadDir       = __DIR__ . '/../uploads/products/'; // thư mục lưu trên server
$uploadUrlPrefix = 'uploads/products/';               // đường dẫn lưu trong DB (relative)

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

// Lấy categories & brands để chọn
$categoriesRes = $conn->query("SELECT slug, name FROM categories ORDER BY id");
$categories    = [];
while ($row = $categoriesRes->fetch_assoc()) {
    $categories[$row['slug']] = $row['name'];
}

$brandsRes = $conn->query("SELECT name FROM brands ORDER BY name");
$brands    = [];
while ($row = $brandsRes->fetch_assoc()) {
    $brands[] = $row['name'];
}

$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Biến cho form
$name            = '';
$brand           = $brands[0] ?? '';
$categorySlug    = array_key_first($categories) ?: '';
$price           = 0;
$oldPrice        = 0;
$stock           = 0;
$specs           = '';
$description     = '';
$detailSpecsText = '';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy dữ liệu
    $name            = trim($_POST['name'] ?? '');
    $brand           = trim($_POST['brand'] ?? '');
    $categorySlug    = trim($_POST['category_slug'] ?? '');
    $price           = floatval($_POST['price'] ?? 0);
    $oldPrice        = $_POST['old_price'] === '' ? 0 : floatval($_POST['old_price']);
    $stock           = (int)($_POST['stock'] ?? 0);
    $specs           = trim($_POST['specs'] ?? '');
    $description     = trim($_POST['description'] ?? '');
    $detailSpecsText = trim($_POST['detail_specs'] ?? '');

    if ($name === '' || $brand === '' || $categorySlug === '' || $price <= 0) {
        $error = "Vui lòng nhập đầy đủ tên, hãng, danh mục và giá bán hợp lệ.";
    }

    // Nếu dữ liệu hợp lệ thì xử lý tiếp
    if ($error === '') {
        $mainImagePath = '';     // sẽ set sau khi upload
        $galleryArray  = [];     // danh sách ảnh gallery

        // ===== 1. Upload ảnh đại diện =====
        if (!empty($_FILES['main_image']['name'])) {
            $file = $_FILES['main_image'];

            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

                if (!in_array($ext, $allowed, true)) {
                    $error = "Định dạng ảnh đại diện không hợp lệ. Chỉ cho phép JPG, PNG, WEBP, GIF.";
                } else {
                    $newName  = 'prod_main_' . time() . '_' . uniqid() . '.' . $ext;
                    $destPath = $uploadDir . $newName;

                    if (move_uploaded_file($file['tmp_name'], $destPath)) {
                        $mainImagePath = $uploadUrlPrefix . $newName;
                    } else {
                        $error = "Không thể lưu file ảnh đại diện.";
                    }
                }
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $error = "Lỗi upload ảnh đại diện.";
            }
        } else {
            $error = "Vui lòng chọn ảnh đại diện cho sản phẩm.";
        }

        // ===== 2. Upload thư viện ảnh =====
        if ($error === '' && !empty($_FILES['gallery_images']['name'][0])) {
            $names  = $_FILES['gallery_images']['name'];
            $tmp    = $_FILES['gallery_images']['tmp_name'];
            $errors = $_FILES['gallery_images']['error'];

            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            for ($i = 0; $i < count($names); $i++) {
                if ($errors[$i] === UPLOAD_ERR_NO_FILE) continue;
                if ($errors[$i] !== UPLOAD_ERR_OK) {
                    $error = "Có lỗi khi upload một trong các ảnh thư viện.";
                    break;
                }

                $ext = strtolower(pathinfo($names[$i], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed, true)) {
                    $error = "Một trong các ảnh thư viện có định dạng không hợp lệ.";
                    break;
                }

                $newName  = 'prod_gallery_' . time() . '_' . uniqid() . '.' . $ext;
                $destPath = $uploadDir . $newName;

                if (move_uploaded_file($tmp[$i], $destPath)) {
                    $galleryArray[] = $uploadUrlPrefix . $newName;
                } else {
                    $error = "Không thể lưu một trong các ảnh thư viện.";
                    break;
                }
            }
        }

        // ===== 3. Chuẩn bị detail_specs (JSON) =====
        $detailSpecsArr = [];

        if ($detailSpecsText !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $detailSpecsText);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;

                if (strpos($line, ':') !== false) {
                    list($k, $v) = explode(':', $line, 2);
                    $k = trim($k);
                    $v = trim($v);
                    if ($k !== '' && $v !== '') {
                        $detailSpecsArr[$k] = $v;
                    }
                }
            }
        }

        $detailSpecsJson = $detailSpecsArr
            ? json_encode($detailSpecsArr, JSON_UNESCAPED_UNICODE)
            : '{}';

        // ===== 4. Chuẩn bị gallery JSON =====
        if (!empty($galleryArray)) {
            $galleryArray = array_values(array_unique($galleryArray));
        }
        $galleryJson = $galleryArray ? json_encode($galleryArray, JSON_UNESCAPED_UNICODE) : null;

        // ===== 5. Insert vào DB nếu không có lỗi =====
        if ($error === '') {
            $rating    = 4.8;                       // giá trị mặc định
            $reviews   = 0;                         // chưa có đánh giá
            $createdAt = date('Y-m-d H:i:s');

            $stmt = $conn->prepare("
                INSERT INTO products
                (name, brand, price, old_price, category_slug, image, specs, description, rating, reviews, stock, detail_specs, created_at, gallery_images)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
            ");

            $stmt->bind_param(
                "ssddssssdiisss",
                $name,
                $brand,
                $price,
                $oldPrice,
                $categorySlug,
                $mainImagePath,
                $specs,
                $description,
                $rating,
                $reviews,
                $stock,
                $detailSpecsJson,
                $createdAt,
                $galleryJson
            );

            if ($stmt->execute()) {
                $newId  = $stmt->insert_id;
                $success = "Đã thêm sản phẩm mới (#{$newId}) thành công.";

                // reset form (tuỳ bạn, có thể giữ lại nếu muốn)
                $name            = '';
                $brand           = $brands[0] ?? '';
                $categorySlug    = array_key_first($categories) ?: '';
                $price           = 0;
                $oldPrice        = 0;
                $stock           = 0;
                $specs           = '';
                $description     = '';
                $detailSpecsText = '';
            } else {
                $error = "Có lỗi khi thêm sản phẩm: " . $stmt->error;
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thêm sản phẩm - LaptopStore</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .admin-form-box {
            background:#ffffff;
            border-radius:12px;
            border:1px solid #e5e7eb;
            padding:24px;
            margin-top:16px;
        }
        .admin-form-grid {
            display:grid;
            grid-template-columns:2fr 1.5fr;
            gap:24px;
        }
        @media (max-width: 900px) {
            .admin-form-grid { grid-template-columns:1fr; }
        }
        .admin-form-section-title {
            font-size:16px;
            font-weight:600;
            margin-bottom:10px;
        }
        .admin-gallery-hint {
            font-size:12px;
            color:var(--text-gray);
            margin-top:4px;
        }
        .admin-gallery-preview {
            display:flex;
            flex-wrap:wrap;
            gap:8px;
            margin-top:10px;
        }
        .admin-gallery-preview img {
            width:64px;
            height:64px;
            border-radius:8px;
            object-fit:cover;
            border:1px solid #e5e7eb;
        }
        .admin-alert {
            padding:10px 14px;
            border-radius:8px;
            font-size:13px;
            margin-top:10px;
        }
        .admin-alert.error {
            background:#fee2e2;
            color:#b91c1c;
            border:1px solid #fecaca;
        }
        .admin-alert.success {
            background:#ecfdf5;
            color:#166534;
            border:1px solid #bbf7d0;
        }
        textarea.admin-textarea {
            min-height:80px;
            resize:vertical;
        }
    </style>
</head>
<body class="admin-body">
<div class="admin-shell">
    <!-- SIDEBAR -->
    <aside class="admin-sidebar">
        <div class="admin-brand">
            <div class="admin-brand-icon">
                <i class="fas fa-laptop"></i>
            </div>
            <div>
                <div class="admin-brand-title">LaptopStore</div>
                <small>Admin Panel</small>
            </div>
        </div>

        <div class="admin-user-mini">
            <div class="admin-user-avatar">
                <?= strtoupper(substr($adminName,0,1)) ?>
            </div>
            <div>
                <div style="font-size:13px;font-weight:600;">
                    <?= htmlspecialchars($adminName) ?>
                </div>
                <div style="font-size:11px;color:#9ca3af;">Quản trị viên</div>
            </div>
        </div>

        <ul class="admin-nav">
            <li><a href="index.php">
                <i class="fas fa-chart-line"></i><span>Tổng quan</span>
            </a></li>
            <li><a href="orders.php">
                <i class="fas fa-receipt"></i><span>Đơn hàng</span>
            </a></li>
            <li><a href="products.php" class="active">
                <i class="fas fa-box-open"></i><span>Sản phẩm</span>
            </a></li>
            <li><a href="users.php">
                <i class="fas fa-users"></i><span>Người dùng</span>
            </a></li>
            <li><a href="../index.php">
                <i class="fas fa-store"></i><span>Xem trang khách</span>
            </a></li>
            <li><a href="logout.php">
                <i class="fas fa-right-from-bracket"></i><span>Đăng xuất</span>
            </a></li>
        </ul>

        <div class="admin-sidebar-footer">
            © <?= date('Y') ?> LaptopStore. All rights reserved.
        </div>
    </aside>

    <!-- MAIN -->
    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="admin-top-title">Thêm sản phẩm mới</div>
                <div class="admin-top-sub">
                    Nhập thông tin & upload hình ảnh sản phẩm
                </div>
            </div>
            <div class="admin-top-actions">
                <a href="products.php" class="btn btn-outline btn-small">
                    <i class="fas fa-arrow-left"></i> Quay lại danh sách
                </a>
            </div>
        </div>

        <section class="admin-form-box">
            <?php if ($error): ?>
                <div class="admin-alert error">
                    <i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="admin-alert success">
                    <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="admin-form-grid">
                    <!-- Cột trái: thông tin chung -->
                    <div>
                        <div class="admin-form-section-title">Thông tin cơ bản</div>

                        <div class="form-group">
                            <label for="name">Tên sản phẩm</label>
                            <input type="text" id="name" name="name"
                                   value="<?= htmlspecialchars($name) ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="brand">Hãng</label>
                                <select id="brand" name="brand" class="form-control" required>
                                    <?php foreach ($brands as $b): ?>
                                        <option value="<?= htmlspecialchars($b) ?>"
                                            <?= $brand === $b ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($b) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="category_slug">Danh mục</label>
                                <select id="category_slug" name="category_slug" class="form-control" required>
                                    <?php foreach ($categories as $slug => $cName): ?>
                                        <option value="<?= htmlspecialchars($slug) ?>"
                                            <?= $categorySlug === $slug ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Mô tả ngắn</label>
                            <textarea id="description"
                                      name="description"
                                      class="admin-textarea"><?= htmlspecialchars($description) ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="specs">Thông số tóm tắt (hiển thị ở card)</label>
                            <input type="text" id="specs" name="specs"
                                   value="<?= htmlspecialchars($specs) ?>">
                        </div>

                        <div class="form-group">
                            <label for="detail_specs">Thông số kỹ thuật chi tiết</label>
                            <textarea id="detail_specs"
                                      name="detail_specs"
                                      class="admin-textarea"
                                      placeholder="Mỗi dòng 1 thông số, dạng:
CPU: Apple M2 8-core
RAM: 8GB Unified
SSD: 256GB NVMe"><?= htmlspecialchars($detailSpecsText) ?></textarea>
                            <div class="admin-gallery-hint">
                                Mỗi dòng 1 cặp <strong>Tên thông số: Giá trị</strong>. Ví dụ:<br>
                                CPU: Apple M2 8-core<br>
                                RAM: 8GB Unified<br>
                                SSD: 256GB
                            </div>
                        </div>
                    </div>

                    <!-- Cột phải: giá, tồn kho, hình ảnh -->
                    <div>
                        <div class="admin-form-section-title">Giá bán & tồn kho</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Giá hiện tại (VND)</label>
                                <input type="number" step="1000" min="0" id="price" name="price"
                                       value="<?= htmlspecialchars($price) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="old_price">Giá cũ (nếu có)</label>
                                <input type="number" step="1000" min="0" id="old_price" name="old_price"
                                       value="<?= htmlspecialchars($oldPrice) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="stock">Tồn kho</label>
                            <input type="number" min="0" id="stock" name="stock"
                                   value="<?= htmlspecialchars($stock) ?>">
                        </div>

                        <hr style="margin:16px 0; border:none; border-top:1px solid #e5e7eb;">

                        <div class="admin-form-section-title">Hình ảnh</div>

                        <!-- Ảnh đại diện -->
                        <div class="form-group">
                            <label for="main_image">Ảnh đại diện</label>
                            <input type="file" id="main_image" name="main_image" accept="image/*" required>
                            <div class="admin-gallery-hint">
                                Bắt buộc chọn 1 ảnh đại diện. Nên dùng ảnh kích thước ngang, chất lượng tốt.
                            </div>
                        </div>

                        <!-- Thư viện ảnh -->
                        <div class="form-group">
                            <label for="gallery_images">Thư viện ảnh (có thể chọn nhiều)</label>
                            <input type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*">
                            <div class="admin-gallery-hint">
                                Có thể chọn nhiều ảnh từ máy của bạn. Các ảnh này dùng làm thumbnail / slide chi tiết.
                            </div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:20px; display:flex; gap:10px;">
                    <button type="submit" class="btn btn-large">
                        <i class="fas fa-save"></i> Thêm sản phẩm
                    </button>
                    <a href="products.php" class="btn btn-outline btn-large">
                        Hủy và quay lại
                    </a>
                </div>
            </form>
        </section>
    </main>
</div>
</body>
</html>
