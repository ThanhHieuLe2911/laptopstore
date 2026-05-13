<?php
require_once "_auth.php";
require_once "../includes/config.php";

date_default_timezone_set('Asia/Ho_Chi_Minh');

$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Thống kê
$totalProducts = $conn->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'];
$totalOrders   = $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$totalUsers    = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];

// Lấy danh mục & hãng
$categories = [];
$catRes = $conn->query("SELECT slug, name FROM categories ORDER BY id");
while ($r = $catRes->fetch_assoc()) $categories[$r['slug']] = $r['name'];

$brands = [];
$brandRes = $conn->query("SELECT name FROM brands ORDER BY name");
while ($r = $brandRes->fetch_assoc()) $brands[] = $r['name'];

// --- LỌC & TÌM KIẾM ---
$search      = trim($_GET['search'] ?? '');
$category    = $_GET['category'] ?? '';
$brand       = $_GET['brand'] ?? '';
$stockStatus = $_GET['stock_status'] ?? '';
$sort        = $_GET['sort'] ?? 'newest';

$where = "WHERE 1=1";

if ($search !== '') {
    $s = $conn->real_escape_string($search);
    $where .= " AND (p.name LIKE '%$s%' OR p.brand LIKE '%$s%')";
}
if ($category !== '' && isset($categories[$category])) {
    $s = $conn->real_escape_string($category);
    $where .= " AND p.category_slug = '$s'";
}
if ($brand !== '' && in_array($brand, $brands)) {
    $s = $conn->real_escape_string($brand);
    $where .= " AND p.brand = '$s'";
}
if ($stockStatus === 'out') $where .= " AND p.stock <= 0";
elseif ($stockStatus === 'low') $where .= " AND p.stock BETWEEN 1 AND 5";
elseif ($stockStatus === 'in')  $where .= " AND p.stock > 0";

// Sắp xếp
$orderBy = "ORDER BY p.created_at DESC";
if ($sort === 'price_asc')  $orderBy = "ORDER BY p.price ASC";
if ($sort === 'price_desc') $orderBy = "ORDER BY p.price DESC";
if ($sort === 'name_asc')   $orderBy = "ORDER BY p.name ASC";
if ($sort === 'name_desc')  $orderBy = "ORDER BY p.name DESC";

// --- QUERY CHÍNH (Đã sửa để tính Rating từ bảng Reviews) ---
$sql = "
    SELECT 
        p.*, 
        c.name AS category_name,
        COUNT(pr.id) as calc_reviews, 
        COALESCE(AVG(pr.rating), 5) as calc_rating
    FROM products p
    LEFT JOIN categories c ON p.category_slug = c.slug
    LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 1
    $where
    GROUP BY p.id
    $orderBy
";

$res = $conn->query($sql);
$products = [];
if ($res) {
    while ($row = $res->fetch_assoc()) $products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý sản phẩm - LaptopStore</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
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
            <li><a href="orders.php"><i class="fas fa-receipt"></i><span>Đơn hàng</span></a></li>
            <li><a href="products.php" class="active"><i class="fas fa-box-open"></i><span>Sản phẩm</span></a></li>
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
                <div class="admin-top-title">Quản lý sản phẩm</div>
                <div class="admin-top-sub">Danh sách tất cả sản phẩm đang kinh doanh</div>
            </div>
            <div class="admin-top-actions">
                <a href="product_create.php" class="btn btn-small" style="padding:8px 16px; font-size:13px; background:var(--primary); color:#fff; border-radius:8px; text-decoration:none; display:flex; align-items:center; gap:6px;">
                    <i class="fas fa-plus"></i> Thêm mới
                </a>
            </div>
        </div>

        <section class="admin-form-box">
            <form method="get" class="filter-bar">
                <input type="text" name="search" class="filter-input" placeholder="Tên sp, hãng..." value="<?= htmlspecialchars($search) ?>" style="flex:1;">
                
                <select name="category" class="filter-select">
                    <option value="">-- Danh mục --</option>
                    <?php foreach ($categories as $slug => $name): ?>
                        <option value="<?= $slug ?>" <?= $category===$slug?'selected':'' ?>><?= htmlspecialchars($name) ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="brand" class="filter-select">
                    <option value="">-- Hãng --</option>
                    <?php foreach ($brands as $b): ?>
                        <option value="<?= $b ?>" <?= $brand===$b?'selected':'' ?>><?= htmlspecialchars($b) ?></option>
                    <?php endforeach; ?>
                </select>

                <select name="stock_status" class="filter-select">
                    <option value="">-- Tồn kho --</option>
                    <option value="in" <?= $stockStatus==='in'?'selected':'' ?>>Còn hàng</option>
                    <option value="low" <?= $stockStatus==='low'?'selected':'' ?>>Sắp hết</option>
                    <option value="out" <?= $stockStatus==='out'?'selected':'' ?>>Hết hàng</option>
                </select>

                <select name="sort" class="filter-select">
                    <option value="newest" <?= $sort==='newest'?'selected':'' ?>>Mới nhất</option>
                    <option value="price_asc" <?= $sort==='price_asc'?'selected':'' ?>>Giá tăng dần</option>
                    <option value="price_desc" <?= $sort==='price_desc'?'selected':'' ?>>Giá giảm dần</option>
                </select>

                <button type="submit" class="btn-filter"><i class="fas fa-filter"></i> Lọc</button>
                <a href="products.php" class="btn-reset">Xóa lọc</a>
            </form>

            <div class="table-wrapper">
                <table class="admin-table">
                    <thead>
                    <tr>
                        <th style="width:60px;">ID</th>
                        <th style="width:300px;">Sản phẩm</th>
                        <th>Danh mục</th>
                        <th>Giá bán</th>
                        <th>Tồn kho</th>
                        <th>Đánh giá (Real-time)</th>
                        <th style="text-align:right;">Hành động</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="7" style="text-align:center; padding:40px; color:#9ca3af;">Không tìm thấy sản phẩm nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): 
                            $img = $p['image'] ?? '';
                            if ($img && !preg_match('~^https?://~', $img)) $img = '../' . $img;
                            if (!$img) $img = '../assets/img/no-image.png';
                            
                            $stockClass = 'stock-in';
                            if ($p['stock'] <= 0) $stockClass = 'stock-out';
                            elseif ($p['stock'] <= 5) $stockClass = 'stock-low';

                            // Dùng dữ liệu tính toán từ bảng Reviews
                            $showRating = number_format($p['calc_rating'], 1);
                            $showCount  = (int)$p['calc_reviews'];
                        ?>
                        <tr>
                            <td>#<?= (int)$p['id'] ?></td>
                            <td>
                                <div class="product-cell">
                                    <img src="<?= htmlspecialchars($img) ?>" class="product-thumb">
                                    <div class="product-info">
                                        <h4><?= htmlspecialchars($p['name']) ?></h4>
                                        <div class="product-meta">Hãng: <?= htmlspecialchars($p['brand']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($p['category_name'] ?? '-') ?></td>
                            <td>
                                <strong style="color:var(--text-dark);"><?= number_format($p['price'], 0, ',', '.') ?>₫</strong>
                                <?php if($p['old_price'] > 0): ?>
                                    <div style="font-size:11px; text-decoration:line-through; color:#9ca3af;">
                                        <?= number_format($p['old_price'], 0, ',', '.') ?>₫
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="stock-badge <?= $stockClass ?>">
                                    <?= (int)$p['stock'] ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-size:12px;">
                                    <i class="fas fa-star" style="color:#f59e0b;"></i> <?= $showRating ?>
                                    <span style="color:#9ca3af;">(<?= $showCount ?>)</span>
                                </div>
                            </td>
                            <td>
                                <div class="action-btns">
                                    <a href="product_edit.php?id=<?= (int)$p['id'] ?>" class="btn-icon" title="Sửa">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <a href="product_delete.php?id=<?= (int)$p['id'] ?>" class="btn-icon delete" title="Xóa" onclick="return confirm('Xóa sản phẩm này?');">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
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