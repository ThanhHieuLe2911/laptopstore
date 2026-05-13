<?php
require_once 'includes/config.php';
$page_title = 'Sản phẩm';

// ========================
// 1. LẤY DỮ LIỆU TỪ DATABASE
// ========================

// Lấy danh mục
$categories = [];
$catQuery = $conn->query("SELECT slug, name FROM categories");
while ($row = $catQuery->fetch_assoc()) {
    $categories[$row['slug']] = $row['name'];
}

// Lấy thương hiệu
$brands = [];
$brandQuery = $conn->query("SELECT name FROM brands ORDER BY name");
while ($row = $brandQuery->fetch_assoc()) {
    $brands[] = $row['name'];
}

// Lấy sản phẩm + Tính toán đánh giá từ bảng Reviews
// Sử dụng LEFT JOIN để kết nối bảng products và product_reviews
$products = [];
$sql = "
    SELECT 
        p.*, 
        COUNT(pr.id) as calc_reviews, 
        COALESCE(AVG(pr.rating), 5) as calc_rating 
    FROM products p
    LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 1
    GROUP BY p.id
";
$prodQuery = $conn->query($sql);
while ($row = $prodQuery->fetch_assoc()) {
    // Làm tròn rating 1 chữ số thập phân (ví dụ 4.5)
    $row['rating'] = round((float)$row['calc_rating'], 1);
    $row['reviews'] = (int)$row['calc_reviews'];
    $products[] = $row;
}

$priceRanges = [
    '0-20'  => 'Dưới 20 triệu',
    '20-30' => 'Từ 20 - 30 triệu',
    '30-40' => 'Từ 30 - 40 triệu',
    '40-50' => 'Từ 40 - 50 triệu',
    '50+'   => 'Trên 50 triệu'
];

// ========================
// 2. NHẬN THAM SỐ GET
// ========================
$category   = isset($_GET['category']) ? trim($_GET['category']) : 'all';
$brand      = isset($_GET['brand']) ? trim($_GET['brand']) : 'all';
$priceRange = isset($_GET['price']) ? trim($_GET['price']) : 'all';
$search     = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort       = isset($_GET['sort']) ? trim($_GET['sort']) : 'default';

// Pagination setup
$perPage = 9;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// ========================
// 3. LỌC SẢN PHẨM (FILTER)
// ========================
$filtered = $products;

if ($category !== 'all') {
    $filtered = array_filter($filtered, function ($p) use ($category) {
        return isset($p['category_slug']) && $p['category_slug'] === $category;
    });
}

if ($brand !== 'all') {
    $filtered = array_filter($filtered, function ($p) use ($brand) {
        return isset($p['brand']) && mb_strtolower($p['brand']) === mb_strtolower($brand);
    });
}

if ($priceRange !== 'all') {
    $filtered = array_filter($filtered, function ($p) use ($priceRange) {
        $rawPrice = isset($p['price']) ? (float)$p['price'] : 0;
        $priceM = $rawPrice / 1000000;
        if ($priceRange === '0-20') return $priceM < 20;
        if ($priceRange === '20-30') return $priceM >= 20 && $priceM < 30;
        if ($priceRange === '30-40') return $priceM >= 30 && $priceM < 40;
        if ($priceRange === '40-50') return $priceM >= 40 && $priceM < 50;
        if ($priceRange === '50+')  return $priceM >= 50;
        return true;
    });
}

if ($search !== '') {
    $filtered = array_filter($filtered, function ($p) use ($search) {
        $q = mb_strtolower($search);
        $name  = mb_strtolower($p['name']  ?? '');
        $brandP = mb_strtolower($p['brand'] ?? '');
        $specs = mb_strtolower($p['specs'] ?? '');
        return (strpos($name, $q) !== false) || (strpos($brandP, $q) !== false) || (strpos($specs, $q) !== false);
    });
}

$filtered = array_values($filtered);

// ========================
// 4. SẮP XẾP (SORTING)
// ========================
if ($sort !== 'default') {
    usort($filtered, function($a, $b) use ($sort, $categories) {
        $priceA = (float)($a['price'] ?? 0);
        $priceB = (float)($b['price'] ?? 0);
        $nameA = mb_strtolower($a['name'] ?? '');
        $nameB = mb_strtolower($b['name'] ?? '');
        $ratingA = (float)($a['rating'] ?? 0);
        $ratingB = (float)($b['rating'] ?? 0);
        $catSlugA = $a['category_slug'] ?? '';
        $catSlugB = $b['category_slug'] ?? '';
        $catNameA = mb_strtolower($categories[$catSlugA] ?? '');
        $catNameB = mb_strtolower($categories[$catSlugB] ?? '');

        if ($sort === 'price-asc')  return $priceA <=> $priceB;
        if ($sort === 'price-desc') return $priceB <=> $priceA;
        if ($sort === 'name')       return $nameA <=> $nameB;
        if ($sort === 'rating')     return $ratingB <=> $ratingA;
        if ($sort === 'category')   return $catNameA <=> $catNameB;
        return 0;
    });
}

// ========================
// 5. PHÂN TRANG (PAGINATION)
// ========================
$totalItems = count($filtered);
$totalPages = (int)ceil($totalItems / $perPage);
if ($totalPages > 0 && $page > $totalPages) $page = $totalPages;

$offset = ($page - 1) * $perPage;
$pagedProducts = array_slice($filtered, $offset, $perPage);

function buildPageUrl($pageNum) {
    $params = $_GET;
    $params['page'] = $pageNum;
    return 'products.php?' . http_build_query($params);
}

include 'includes/header.php';
?>



<div class="breadcrumb">
    <div class="container">
        <a href="index.php">Trang chủ</a>
        <i class="fas fa-chevron-right"></i>
        <span>Sản phẩm</span>
    </div>
</div>

<section class="products-page">
    <div class="container">
        <div class="page-layout">
            <aside class="filters-sidebar">
                <div class="filter-header">
                    <h3><i class="fas fa-filter"></i> Bộ lọc</h3>
                    <?php if ($category !== 'all' || $brand !== 'all' || $priceRange !== 'all' || $search !== '' || $sort !== 'default'): ?>
                        <a href="products.php" class="clear-filter">Xóa bộ lọc</a>
                    <?php endif; ?>
                </div>

                <div class="filter-group">
                    <h4>Danh mục</h4>
                    <div class="filter-options">
                        <?php foreach ($categories as $slug => $name): ?>
                            <label class="filter-option">
                                <input type="radio" name="category" value="<?php echo htmlspecialchars($slug); ?>"
                                    <?php echo ($category === $slug) ? 'checked' : ''; ?>
                                    onchange="updateFilter('category', '<?php echo htmlspecialchars($slug); ?>')">
                                <span><?php echo htmlspecialchars($name); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-group">
                    <h4>Thương hiệu</h4>
                    <div class="filter-options">
                        <label class="filter-option">
                            <input type="radio" name="brand" value="all"
                                <?php echo ($brand === 'all') ? 'checked' : ''; ?>
                                onchange="updateFilter('brand', 'all')">
                            <span>Tất cả</span>
                        </label>
                        <?php foreach ($brands as $b): ?>
                            <label class="filter-option">
                                <input type="radio" name="brand" value="<?php echo htmlspecialchars($b); ?>"
                                    <?php echo (mb_strtolower($brand) === mb_strtolower($b)) ? 'checked' : ''; ?>
                                    onchange="updateFilter('brand', '<?php echo htmlspecialchars($b); ?>')">
                                <span><?php echo htmlspecialchars($b); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="filter-group">
                    <h4>Mức giá</h4>
                    <div class="filter-options">
                        <label class="filter-option">
                            <input type="radio" name="price" value="all"
                                <?php echo ($priceRange === 'all') ? 'checked' : ''; ?>
                                onchange="updateFilter('price', 'all')">
                            <span>Tất cả</span>
                        </label>
                        <?php foreach ($priceRanges as $key => $label): ?>
                            <label class="filter-option">
                                <input type="radio" name="price" value="<?php echo htmlspecialchars($key); ?>"
                                    <?php echo ($priceRange === $key) ? 'checked' : ''; ?>
                                    onchange="updateFilter('price', '<?php echo htmlspecialchars($key); ?>')">
                                <span><?php echo htmlspecialchars($label); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </aside>

            <div class="products-main">
                <div class="products-header">
                    <div class="result-count">
                        <strong><?php echo $totalItems; ?></strong> sản phẩm
                        <?php if ($search !== ''): ?> cho "<strong><?php echo htmlspecialchars($search); ?></strong>"<?php endif; ?>
                    </div>

                    <div class="products-controls">
                        <select class="sort-select" onchange="sortProducts(this.value)">
                            <option value="default"    <?php echo ($sort==='default') ? 'selected' : ''; ?>>Sắp xếp: Mặc định</option>
                            <option value="price-asc"  <?php echo ($sort==='price-asc') ? 'selected' : ''; ?>>Giá: Thấp đến Cao</option>
                            <option value="price-desc" <?php echo ($sort==='price-desc') ? 'selected' : ''; ?>>Giá: Cao đến Thấp</option>
                            <option value="name"       <?php echo ($sort==='name') ? 'selected' : ''; ?>>Tên: A-Z</option>
                            <option value="rating"     <?php echo ($sort==='rating') ? 'selected' : ''; ?>>Đánh giá cao nhất</option>
                            <option value="category"   <?php echo ($sort==='category') ? 'selected' : ''; ?>>Danh mục: A-Z</option>
                        </select>

                        <div class="view-toggle">
                            <button class="view-btn active" type="button" onclick="setView('grid', this)"><i class="fas fa-th"></i></button>
                            <button class="view-btn" type="button" onclick="setView('list', this)"><i class="fas fa-list"></i></button>
                        </div>
                    </div>
                </div>

                <?php if ($totalItems == 0): ?>
                    <div class="no-results">
                        <i class="fas fa-search"></i>
                        <h3>Không tìm thấy sản phẩm</h3>
                        <p>Vui lòng thử lại với bộ lọc khác</p>
                        <a href="products.php" class="btn btn-primary">Xem tất cả sản phẩm</a>
                    </div>
                <?php else: ?>
                    <div class="products-grid" id="productsGrid">
                        <?php foreach ($pagedProducts as $p): 
                            $discount = calculateDiscount($p['old_price'] ?? 0, $p['price'] ?? 0);
                            $img = !empty($p['image']) ? $p['image'] : 'assets/img/no-image.png';
                            
                            // Sử dụng dữ liệu rating thực tế
                            $ratingVal = isset($p['rating']) ? (float)$p['rating'] : 0;
                            $reviewCount = isset($p['reviews']) ? (int)$p['reviews'] : 0;
                        ?>
                            <div class="product-card">
                                <?php if ($discount > 0): ?><div class="product-badge">-<?php echo (int)$discount; ?>%</div><?php endif; ?>
                                <?php if (($p['stock'] ?? 0) < 5): ?><div class="product-badge badge-warning">Sắp hết</div><?php endif; ?>

                                <div class="product-img-wrapper">
                                    <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy">
                                    <div class="card-actions">
                                        <button class="icon-btn" onclick="quickView(<?php echo (int)$p['id']; ?>)"><i class="far fa-eye"></i></button>
                                        <button class="icon-btn" onclick="toggleWishlist(this, <?php echo (int)$p['id']; ?>)"><i class="far fa-heart"></i></button>
                                    </div>
                                </div>

                                <div class="product-info">
                                    <div class="brand"><?php echo htmlspecialchars($p['brand']); ?></div>
                                    <a href="product-detail.php?id=<?php echo (int)$p['id']; ?>" class="product-title" title="<?php echo htmlspecialchars($p['name']); ?>">
                                        <?php echo htmlspecialchars($p['name']); ?>
                                    </a>
                                    
                                    <div class="product-rating">
                                        <span class="stars">
                                            <?php 
                                            $fullStars = (int)floor($ratingVal);
                                            for ($i=0; $i<5; $i++) echo '<i class="fas fa-star '.($i<$fullStars?'active':'').'"></i>';
                                            ?>
                                        </span>
                                        <span class="reviews">(<?php echo $reviewCount; ?>)</span>
                                    </div>

                                    <div class="specs"><?php echo htmlspecialchars($p['specs'] ?? ''); ?></div>
                                    <div class="price-row">
                                        <div class="price-group">
                                            <div class="price"><?php echo formatMoney($p['price']); ?></div>
                                            <?php if ($discount > 0): ?><div class="old-price"><?php echo formatMoney($p['old_price']); ?></div><?php endif; ?>
                                        </div>
                                        <button class="add-cart-btn" onclick="addToCart(<?php echo (int)$p['id']; ?>)"><i class="fas fa-cart-plus"></i></button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php if ($totalPages > 1): ?>
                    <div class="pagination-wrap">
                        <ul class="pagination">
                            <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page > 1) ? buildPageUrl($page - 1) : '#'; ?>">&laquo;</a>
                            </li>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                    <a class="page-link" href="<?php echo buildPageUrl($i); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                                <a class="page-link" href="<?php echo ($page < $totalPages) ? buildPageUrl($page + 1) : '#'; ?>">&raquo;</a>
                            </li>
                        </ul>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
window.PRODUCTS = <?php echo json_encode($products ?? [], JSON_UNESCAPED_UNICODE); ?>;

function updateFilter(type, value) {
    const url = new URL(window.location.href);
    url.searchParams.set(type, value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function sortProducts(type) {
    const url = new URL(window.location.href);
    url.searchParams.set('sort', type);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function setView(viewType, btnEl) {
    const grid = document.getElementById('productsGrid');
    if (!grid) return;
    document.querySelectorAll('.view-btn').forEach(b => b.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');
    if (viewType === 'list') grid.classList.add('list-view');
    else grid.classList.remove('list-view');
}
</script>

<?php include 'includes/footer.php'; ?>