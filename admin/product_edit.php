<?php
require_once "_auth.php";
require_once "../includes/config.php";

date_default_timezone_set('Asia/Ho_Chi_Minh');

// --------- CẤU HÌNH UPLOAD ----------
$uploadDir       = __DIR__ . '/../uploads/products/';
$uploadUrlPrefix = 'uploads/products/';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0775, true);
}

// Lấy ID sản phẩm
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) die("ID sản phẩm không hợp lệ.");

// Lấy thông tin sản phẩm
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$product) die("Không tìm thấy sản phẩm.");

$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Lấy categories & brands
$categoriesRes = $conn->query("SELECT slug, name FROM categories ORDER BY id");
$categories = [];
while ($row = $categoriesRes->fetch_assoc()) {
    $categories[$row['slug']] = $row['name'];
}

$brandsRes = $conn->query("SELECT name FROM brands ORDER BY name");
$brands = [];
while ($row = $brandsRes->fetch_assoc()) {
    $brands[] = $row['name'];
}

$error = '';
$success = '';

// ====== Chuẩn bị text detail_specs để show lên textarea ======
$detailSpecsText = '';
if (!empty($product['detail_specs'])) {
    $decoded = json_decode($product['detail_specs'], true);
    if (is_array($decoded) && !empty($decoded)) {
        $lines = [];
        foreach ($decoded as $k => $v) $lines[] = $k . ': ' . $v;
        $detailSpecsText = implode("\n", $lines);
    }
}

// ====== Lấy gallery từ DB để hiển thị ======
$galleryArr = [];
if (!empty($product['gallery_images'])) {
    $tmp = json_decode($product['gallery_images'], true);
    if (is_array($tmp)) $galleryArr = $tmp;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name         = trim($_POST['name'] ?? '');
    $brand        = trim($_POST['brand'] ?? '');
    $categorySlug = trim($_POST['category_slug'] ?? '');
    $price        = floatval($_POST['price'] ?? 0);
    $oldPrice     = ($_POST['old_price'] ?? '') === '' ? 0 : floatval($_POST['old_price']);
    $stock        = (int)($_POST['stock'] ?? 0);
    $specs        = trim($_POST['specs'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $detailSpecsTextPost = trim($_POST['detail_specs'] ?? '');

    if ($name === '' || $brand === '' || $categorySlug === '' || $price <= 0) {
        $error = "Vui lòng nhập đầy đủ tên, hãng, danh mục và giá bán hợp lệ.";
    }

    if ($error === '') {

        // =========================
        // 1) ẢNH ĐẠI DIỆN
        // =========================
        $mainImagePath = $product['image']; // giữ nguyên nếu không upload mới

        if (!empty($_FILES['main_image']['name'])) {
            $file = $_FILES['main_image'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['jpg','jpeg','png','webp','gif'];

                if (!in_array($ext, $allowed, true)) {
                    $error = "Định dạng ảnh đại diện không hợp lệ. Chỉ cho phép JPG, PNG, WEBP, GIF.";
                } else {
                    $newName  = 'prod_main_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($file['tmp_name'], $uploadDir . $newName)) {
                        $mainImagePath = $uploadUrlPrefix . $newName;
                    } else {
                        $error = "Không thể lưu file ảnh đại diện.";
                    }
                }
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $error = "Lỗi upload ảnh đại diện.";
            }
        }

        // =========================
        // 2) GALLERY (FIX DỨT ĐIỂM)
        // =========================
        // Luôn lấy gallery hiện tại từ DB làm gốc
        $galleryArray = [];
        if (!empty($product['gallery_images'])) {
            $tmp = json_decode($product['gallery_images'], true);
            if (is_array($tmp)) $galleryArray = $tmp;
        }

        // Nếu có existing_gallery[] => user đã thao tác xoá/giữ ảnh
        // => gallery gốc = đúng danh sách gửi về
        if (isset($_POST['existing_gallery']) && is_array($_POST['existing_gallery'])) {
            $galleryArray = [];
            foreach ($_POST['existing_gallery'] as $path) {
                $path = trim($path);
                if ($path !== '') $galleryArray[] = $path;
            }
        }

        // Upload thêm ảnh mới (có thể chọn 1 ảnh rồi chọn tiếp ảnh khác)
        if ($error === '' && !empty($_FILES['gallery_images']['name'][0])) {
            $allowed = ['jpg','jpeg','png','webp','gif'];

            foreach ($_FILES['gallery_images']['name'] as $i => $fname) {
                if ($_FILES['gallery_images']['error'][$i] === UPLOAD_ERR_NO_FILE) continue;
                if ($_FILES['gallery_images']['error'][$i] !== UPLOAD_ERR_OK) {
                    $error = "Có lỗi khi upload một trong các ảnh thư viện.";
                    break;
                }

                $ext = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed, true)) {
                    $error = "Một trong các ảnh thư viện có định dạng không hợp lệ.";
                    break;
                }

                $newName = 'prod_gallery_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['gallery_images']['tmp_name'][$i], $uploadDir . $newName)) {
                    $galleryArray[] = $uploadUrlPrefix . $newName;
                } else {
                    $error = "Không thể lưu một trong các ảnh thư viện.";
                    break;
                }
            }
        }

        // Chuẩn hoá mảng gallery
        $galleryArray = array_values(array_unique($galleryArray, SORT_STRING));
        $galleryJson = empty($galleryArray) ? null : json_encode($galleryArray, JSON_UNESCAPED_UNICODE);

        // =========================
        // 3) detail_specs JSON
        // =========================
        $detailSpecsArr = [];
        if ($detailSpecsTextPost !== '') {
            $lines = preg_split('/\r\n|\r|\n/', $detailSpecsTextPost);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;

                if (strpos($line, ':') !== false) {
                    [$k, $v] = explode(':', $line, 2);
                    $k = trim($k);
                    $v = trim($v);
                    if ($k !== '' && $v !== '') $detailSpecsArr[$k] = $v;
                }
            }
        }
        $detailSpecsJson = $detailSpecsArr ? json_encode($detailSpecsArr, JSON_UNESCAPED_UNICODE) : '{}';

        // =========================
        // 4) UPDATE DB
        // =========================
        if ($error === '') {
            $stmt = $conn->prepare("
                UPDATE products
                SET name = ?,
                    brand = ?,
                    price = ?,
                    old_price = ?,
                    category_slug = ?,
                    image = ?,
                    specs = ?,
                    description = ?,
                    stock = ?,
                    detail_specs = ?,
                    gallery_images = ?
                WHERE id = ?
            ");

            // s s d d s s s s i s s i  => 12 params
            $stmt->bind_param(
                "ssddssssissi",
                $name,
                $brand,
                $price,
                $oldPrice,
                $categorySlug,
                $mainImagePath,
                $specs,
                $description,
                $stock,
                $detailSpecsJson,
                $galleryJson,
                $id
            );

            if ($stmt->execute()) {
                $success = "Đã cập nhật sản phẩm thành công.";

                // reload product từ DB để đảm bảo form + preview đúng 100%
                $stmt->close();

                $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $product = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                // refresh detailSpecsText + galleryArr
                $detailSpecsText = '';
                if (!empty($product['detail_specs'])) {
                    $decoded = json_decode($product['detail_specs'], true);
                    if (is_array($decoded) && !empty($decoded)) {
                        $lines = [];
                        foreach ($decoded as $k => $v) $lines[] = $k . ': ' . $v;
                        $detailSpecsText = implode("\n", $lines);
                    }
                }

                $galleryArr = [];
                if (!empty($product['gallery_images'])) {
                    $tmp = json_decode($product['gallery_images'], true);
                    if (is_array($tmp)) $galleryArr = $tmp;
                }
            } else {
                $error = "Có lỗi khi cập nhật: " . $stmt->error;
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chỉnh sửa sản phẩm - LaptopStore</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"
          crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        .admin-form-box{background:#fff;border-radius:12px;border:1px solid #e5e7eb;padding:24px;margin-top:16px;}
        .admin-form-grid{display:grid;grid-template-columns:2fr 1.5fr;gap:24px;}
        @media (max-width:900px){.admin-form-grid{grid-template-columns:1fr;}}
        .admin-form-section-title{font-size:16px;font-weight:600;margin-bottom:10px;}
        .admin-gallery-hint{font-size:12px;color:var(--text-gray);margin-top:4px;line-height:1.4;}
        .admin-gallery-preview{display:flex;flex-wrap:wrap;gap:8px;margin-top:10px;}
        .admin-gallery-item{position:relative;width:64px;height:64px;}
        .admin-gallery-item img{width:64px;height:64px;border-radius:8px;object-fit:cover;border:1px solid #e5e7eb;display:block;}
        .admin-gallery-item-lg{width:110px;height:110px;}
        .admin-gallery-item-lg img{width:110px;height:110px;border-radius:12px;}
        .admin-gallery-item.is-new img{border:1px dashed #c7d2fe;}
        .admin-gallery-remove{position:absolute;top:-6px;right:-6px;width:20px;height:20px;border-radius:999px;border:none;background:#ef4444;color:#fff;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:11px;box-shadow:0 2px 6px rgba(0,0,0,0.15);}
        .admin-alert{padding:10px 14px;border-radius:8px;font-size:13px;margin-top:10px;}
        .admin-alert.error{background:#fee2e2;color:#b91c1c;border:1px solid #fecaca;}
        .admin-alert.success{background:#ecfdf5;color:#166534;border:1px solid #bbf7d0;}
        textarea.admin-textarea{min-height:80px;resize:vertical;}
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
            <li><a href="products.php" class="active"><i class="fas fa-box-open"></i><span>Sản phẩm</span></a></li>
            <li><a href="users.php"><i class="fas fa-users"></i><span>Người dùng</span></a></li>
            <li><a href="../index.php"><i class="fas fa-store"></i><span>Xem trang khách</span></a></li>
            <li><a href="logout.php"><i class="fas fa-right-from-bracket"></i><span>Đăng xuất</span></a></li>
        </ul>

        <div class="admin-sidebar-footer">© <?= date('Y') ?> LaptopStore. All rights reserved.</div>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <div>
                <div class="admin-top-title">Chỉnh sửa sản phẩm</div>
                <div class="admin-top-sub">ID #<?= (int)$product['id'] ?> • cập nhật thông tin & hình ảnh</div>
            </div>
            <div class="admin-top-actions">
                <a href="products.php" class="btn btn-outline btn-small"><i class="fas fa-arrow-left"></i> Quay lại danh sách</a>
            </div>
        </div>

        <section class="admin-form-box">
            <?php if ($error): ?>
                <div class="admin-alert error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="admin-alert success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data">
                <div class="admin-form-grid">
                    <div>
                        <div class="admin-form-section-title">Thông tin cơ bản</div>

                        <div class="form-group">
                            <label for="name">Tên sản phẩm</label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="brand">Hãng</label>
                                <select id="brand" name="brand" class="form-control" required>
                                    <?php foreach ($brands as $b): ?>
                                        <option value="<?= htmlspecialchars($b) ?>" <?= ($product['brand'] === $b) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($b) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="category_slug">Danh mục</label>
                                <select id="category_slug" name="category_slug" class="form-control" required>
                                    <?php foreach ($categories as $slug => $cName): ?>
                                        <option value="<?= htmlspecialchars($slug) ?>" <?= ($product['category_slug'] === $slug) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cName) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="description">Mô tả ngắn</label>
                            <textarea id="description" name="description" class="admin-textarea"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
                        </div>

                        <div class="form-group">
                            <label for="specs">Thông số tóm tắt (hiển thị ở card)</label>
                            <input type="text" id="specs" name="specs" value="<?= htmlspecialchars($product['specs'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label for="detail_specs">Thông số kỹ thuật chi tiết</label>
                            <textarea id="detail_specs" name="detail_specs" class="admin-textarea"
                                      placeholder="CPU: ...
RAM: ...
SSD: ..."><?= htmlspecialchars($detailSpecsText) ?></textarea>
                            <div class="admin-gallery-hint">Mỗi dòng 1 cặp <strong>Tên thông số: Giá trị</strong>.</div>
                        </div>
                    </div>

                    <div>
                        <div class="admin-form-section-title">Giá bán & tồn kho</div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="price">Giá hiện tại (VND)</label>
                                <input type="number" step="1000" min="0" id="price" name="price" value="<?= htmlspecialchars($product['price']) ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="old_price">Giá cũ (nếu có)</label>
                                <input type="number" step="1000" min="0" id="old_price" name="old_price" value="<?= htmlspecialchars($product['old_price']) ?>">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="stock">Tồn kho</label>
                            <input type="number" min="0" id="stock" name="stock" value="<?= htmlspecialchars($product['stock']) ?>">
                        </div>

                        <hr style="margin:16px 0; border:none; border-top:1px solid #e5e7eb;">

                        <div class="admin-form-section-title">Hình ảnh</div>

                        <div class="form-group">
                            <label for="main_image">Ảnh đại diện</label>
                            <input type="file" id="main_image" name="main_image" accept="image/*">
                            <div class="admin-gallery-hint">Chọn ảnh để xem preview ngay.</div>

                            <div class="admin-gallery-preview" id="mainImagePreview">
                                <?php if (!empty($product['image'])):
                                    $imgSrc = $product['image'];
                                    if (!preg_match('~^https?://~', $imgSrc)) $imgSrc = '../' . $imgSrc;
                                ?>
                                    <div class="admin-gallery-item admin-gallery-item-lg">
                                        <img src="<?= htmlspecialchars($imgSrc) ?>" alt="Ảnh đại diện hiện tại">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="gallery_images">Thư viện ảnh</label>
                            <input type="file" id="gallery_images" name="gallery_images[]" multiple accept="image/*">
                            <div class="admin-gallery-hint">Chọn 1 ảnh rồi chọn tiếp ảnh khác (ảnh trước vẫn giữ). Ảnh cũ bấm X để xoá.</div>

                            <div class="admin-gallery-preview" id="gallery-existing">
                                <?php if (!empty($galleryArr)): ?>
                                    <?php foreach ($galleryArr as $g):
                                        $gSrc = $g;
                                        if (!preg_match('~^https?://~', $gSrc)) $gSrc = '../' . $gSrc;
                                    ?>
                                        <div class="admin-gallery-item">
                                            <img src="<?= htmlspecialchars($gSrc) ?>" alt="Gallery">
                                            <input type="hidden" name="existing_gallery[]" value="<?= htmlspecialchars($g) ?>">
                                            <button type="button" class="admin-gallery-remove" onclick="removeExistingGalleryImage(this)">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <div class="admin-gallery-preview" id="gallery-new"></div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:20px; display:flex; gap:10px;">
                    <button type="submit" class="btn btn-large"><i class="fas fa-save"></i> Lưu thay đổi</button>
                    <a href="products.php" class="btn btn-outline btn-large">Hủy và quay lại</a>
                </div>
            </form>
        </section>
    </main>
</div>

<script>
const mainInput = document.getElementById('main_image');
const mainPreview = document.getElementById('mainImagePreview');

if (mainInput) {
  mainInput.addEventListener('change', function () {
    if (!this.files || !this.files[0]) return;
    mainPreview.innerHTML = '';
    const url = URL.createObjectURL(this.files[0]);

    const wrap = document.createElement('div');
    wrap.className = 'admin-gallery-item admin-gallery-item-lg is-new';

    const img = document.createElement('img');
    img.src = url;
    img.alt = 'Ảnh đại diện mới';

    wrap.appendChild(img);
    mainPreview.appendChild(wrap);
  });
}

// accumulate gallery
const galleryInput = document.getElementById('gallery_images');
const galleryNewWrap = document.getElementById('gallery-new');
const dt = new DataTransfer();

function renderNewGalleryPreview() {
  galleryNewWrap.innerHTML = '';
  Array.from(dt.files).forEach((file, index) => {
    const url = URL.createObjectURL(file);

    const item = document.createElement('div');
    item.className = 'admin-gallery-item is-new';

    const img = document.createElement('img');
    img.src = url;
    img.alt = file.name;

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'admin-gallery-remove';
    btn.innerHTML = '<i class="fas fa-times"></i>';
    btn.title = 'Bỏ ảnh này';
    btn.addEventListener('click', function () {
      const newDt = new DataTransfer();
      Array.from(dt.files).forEach((f, i) => { if (i !== index) newDt.items.add(f); });
      dt.items.clear();
      Array.from(newDt.files).forEach(f => dt.items.add(f));
      galleryInput.files = dt.files;
      renderNewGalleryPreview();
    });

    item.appendChild(img);
    item.appendChild(btn);
    galleryNewWrap.appendChild(item);
  });
}

if (galleryInput) {
  galleryInput.addEventListener('change', function () {
    if (!this.files || this.files.length === 0) return;

    // Lặp qua các file vừa chọn để thêm vào biến dt (DataTransfer)
    Array.from(this.files).forEach(file => {
      // Kiểm tra trùng lặp cơ bản
      const exists = Array.from(dt.files).some(f => f.name === file.name && f.size === file.size);
      if (!exists) dt.items.add(file);
    });

    // Cập nhật lại input bằng danh sách đã cộng dồn
    this.files = dt.files; 
    
    // Vẽ lại giao diện
    renderNewGalleryPreview();

    // TUYỆT ĐỐI KHÔNG ĐƯỢC RESET this.value = '' Ở ĐÂY
  });
}

function removeExistingGalleryImage(btn) {
  const wrapper = btn.closest('.admin-gallery-item');
  if (!wrapper) return;
  const hidden = wrapper.querySelector('input[type="hidden"][name="existing_gallery[]"]');
  if (hidden) hidden.remove();
  wrapper.remove();
}
window.removeExistingGalleryImage = removeExistingGalleryImage;
</script>
</body>
</html>
