<?php
require_once 'includes/config.php';
$page_title = 'Trang chủ';

// 1. Lấy danh mục
$categories = [];
$catQuery = $conn->query("SELECT slug, name FROM categories");
while ($row = $catQuery->fetch_assoc()) {
    $categories[$row['slug']] = $row['name'];
}

// 2. Lấy sản phẩm nổi bật (Sắp xếp theo rating thực tế + số lượng review)
// Query phức tạp hơn xíu để join bảng reviews
$products = [];
$sql = "
    SELECT 
        p.*, 
        COUNT(pr.id) as calc_reviews, 
        COALESCE(AVG(pr.rating), 5) as calc_rating 
    FROM products p
    LEFT JOIN product_reviews pr ON p.id = pr.product_id AND pr.status = 1
    GROUP BY p.id
    ORDER BY calc_rating DESC, calc_reviews DESC
    LIMIT 8
";

$prodQuery = $conn->query($sql);
while ($row = $prodQuery->fetch_assoc()) {
    // Làm tròn rating
    $row['rating'] = round((float)$row['calc_rating'], 1);
    $row['reviews'] = (int)$row['calc_reviews'];
    $products[] = $row;
}

include 'includes/header.php';
?>

<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <span class="hero-badge">Ưu đãi lên đến 40%</span>
                <h1>Công Nghệ Mới Nhất<br><span class="gradient-text">Nâng Tầm Trải Nghiệm</span></h1>
                <p>Sở hữu ngay những mẫu Laptop đỉnh cao với ưu đãi hấp dẫn. Trả góp 0%, miễn phí vận chuyển toàn quốc.</p>
                <div class="hero-buttons">
                    <a href="products.php" class="btn btn-primary">
                        <i class="fas fa-shopping-bag"></i> Mua Ngay
                    </a>
                    <a href="#featured" class="btn btn-outline">
                        <i class="fas fa-arrow-down"></i> Khám Phá
                    </a>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <strong>5000+</strong>
                        <span>Khách hàng</span>
                    </div>
                    <div class="stat-item">
                        <strong>50+</strong>
                        <span>Cửa hàng</span>
                    </div>
                    <div class="stat-item">
                        <strong>4.8⭐</strong>
                        <span>Đánh giá</span>
                    </div>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-img-wrapper">
                    <img src="https://images.unsplash.com/photo-1603302576837-37561b2e2302?w=600&q=80" alt="Laptop Hero">
                    <div class="float-card card-1">
                        <i class="fas fa-shipping-fast"></i>
                        <span>Free Ship</span>
                    </div>
                    <div class="float-card card-2">
                        <i class="fas fa-shield-alt"></i>
                        <span>Bảo hành 2 năm</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="categories-section">
    <div class="container">
        <div class="categories-grid">
            <?php foreach ($categories as $slug => $name): ?>
                <?php if ($slug != 'all'): ?>
                    <a href="products.php?category=<?php echo $slug; ?>" class="category-card">
                        <div class="category-icon">
                            <?php
                            $icons = [
                                'gaming' => 'fa-gamepad',
                                'office' => 'fa-briefcase',
                                'macbook' => 'fa-apple',
                                'graphic' => 'fa-palette'
                            ];
                            $iconClass = $icons[$slug] ?? 'fa-laptop';
                            ?>
                            <i class="fas <?php echo $iconClass; ?>"></i>
                        </div>
                        <h3><?php echo $name; ?></h3>
                        <p>Khám phá ngay</p>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="products-section" id="featured">
    <div class="container">
        <div class="section-header">
            <h2>Sản Phẩm Nổi Bật</h2>
            <p>Được chọn lọc kỹ lưỡng dựa trên đánh giá của chuyên gia và người dùng</p>
        </div>

        <div class="products-grid">
            <?php foreach ($products as $p): 
                $discount = calculateDiscount($p['old_price'], $p['price']);
                
                // Xử lý ảnh
                $img = $p['image'] ?? '';
                // Nếu ảnh không phải link online thì thêm path local (nếu cần)
                // Tuy nhiên ở đây giả định DB đã lưu đúng path hoặc link
                if (empty($img)) $img = 'assets/img/no-image.png';

                // Rating thực tế
                $ratingVal = isset($p['rating']) ? (float)$p['rating'] : 0;
                $reviewCount = isset($p['reviews']) ? (int)$p['reviews'] : 0;
            ?>
                <div class="product-card">
                    <?php if ($discount > 0): ?>
                        <div class="product-badge">-<?php echo (int)$discount; ?>%</div>
                    <?php endif; ?>
                    
                    <?php if (($p['stock'] ?? 0) < 5): ?>
                        <div class="product-badge badge-warning">Sắp hết</div>
                    <?php endif; ?>
                    
                    <div class="product-img-wrapper">
                        <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" loading="lazy">
                        <div class="card-actions">
                            <button class="icon-btn" onclick="quickView(<?php echo (int)$p['id']; ?>)" title="Xem nhanh">
                                <i class="far fa-eye"></i>
                            </button>
                            <button class="icon-btn" onclick="toggleWishlist(this, <?php echo (int)$p['id']; ?>)" title="Yêu thích">
                                <i class="far fa-heart"></i>
                            </button>
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
                                for ($i = 0; $i < 5; $i++): 
                                ?>
                                    <i class="fas fa-star <?php echo $i < $fullStars ? 'active' : ''; ?>"></i>
                                <?php endfor; ?>
                            </span>
                            <span class="reviews">(<?php echo $reviewCount; ?>)</span>
                        </div>

                        <div class="specs"><?php echo htmlspecialchars($p['specs'] ?? ''); ?></div>
                        
                        <div class="price-row">
                            <div class="price-group">
                                <div class="price"><?php echo formatMoney($p['price']); ?></div>
                                <?php if ($discount > 0): ?>
                                    <div class="old-price"><?php echo formatMoney($p['old_price']); ?></div>
                                <?php endif; ?>
                            </div>
                            <button class="add-cart-btn" onclick="addToCart(<?php echo (int)$p['id']; ?>)">
                                <i class="fas fa-cart-plus"></i>
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center" style="margin-top: 40px;">
            <a href="products.php" class="btn btn-primary">
                Xem Tất Cả Sản Phẩm <i class="fas fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>

<section class="benefits-section">
    <div class="container">
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-truck"></i></div>
                <h3>Miễn phí vận chuyển</h3>
                <p>Cho đơn hàng trên 5.000.000đ</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-shield-alt"></i></div>
                <h3>Bảo hành chính hãng</h3>
                <p>1 đổi 1 trong 30 ngày đầu tiên</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-headset"></i></div>
                <h3>Hỗ trợ 24/7</h3>
                <p>Tư vấn kỹ thuật trọn đời</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon"><i class="fas fa-credit-card"></i></div>
                <h3>Trả góp 0%</h3>
                <p>Duyệt nhanh, nhận hàng ngay</p>
            </div>
        </div>
    </div>
</section>

<section class="newsletter-section">
    <div class="container">
        <div class="newsletter-content">
            <div class="newsletter-text">
                <h2>Đăng ký nhận thông tin ưu đãi</h2>
                <p>Nhận ngay voucher 500.000đ cho đơn hàng đầu tiên</p>
            </div>
            <form class="newsletter-form" onsubmit="handleNewsletter(event)">
                <input type="email" placeholder="Nhập email của bạn" required>
                <button type="submit" class="btn btn-dark">Đăng ký</button>
            </form>
        </div>
    </div>
</section>

<script>
// Pass data to JS if needed
window.PRODUCTS = <?php echo json_encode($products); ?>;

function handleNewsletter(e) {
    e.preventDefault();
    const email = e.target.querySelector('input').value;
    showToast('Đăng ký thành công! Kiểm tra email để nhận voucher.');
    e.target.reset();
}
</script>



<?php include 'includes/footer.php'; ?>