<?php
require_once 'includes/config.php';

// Lấy ID sản phẩm
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$product = getProductById($id);

// Nếu không tìm thấy sản phẩm, quay về trang danh sách
if (!$product) {
    header('Location: products.php');
    exit;
}

$page_title = $product['name'];
$discount   = calculateDiscount($product['old_price'], $product['price']);

/* ===========================
   1. XỬ LÝ ẢNH (Gallery & Main Image)
   =========================== */
$UPLOAD_DIR = 'uploads/products/';

function buildImageUrl(string $path): string {
    $path = trim($path);
    if ($path === '') return 'assets/img/no-image.png';
    if (preg_match('#^https?://#i', $path)) return $path; // Link online
    
    // Xóa các ký tự thừa ../ ./ / ở đầu
    while (strpos($path, '../') === 0 || strpos($path, './') === 0 || strpos($path, '/') === 0) {
        $path = ltrim($path, './');
    }
    
    // Nếu đã có uploads/ thì giữ nguyên, không thì thêm vào
    if (strpos($path, 'uploads/') === 0) return $path;
    return 'uploads/products/' . $path;
}

$mainImage = buildImageUrl($product['image'] ?? '');
$galleryImages = [];

if (!empty($product['gallery_images'])) {
    $tmp = is_array($product['gallery_images']) ? $product['gallery_images'] : json_decode($product['gallery_images'], true);
    if (is_array($tmp)) {
        foreach ($tmp as $img) {
            $galleryImages[] = buildImageUrl($img);
        }
    }
}

// Đảm bảo ảnh chính luôn nằm đầu gallery
if ($mainImage !== '' && $mainImage !== 'assets/img/no-image.png') {
    if (!in_array($mainImage, $galleryImages)) {
        array_unshift($galleryImages, $mainImage);
    }
}
if (empty($galleryImages)) $galleryImages[] = $mainImage;


/* ===========================
   2. THÔNG SỐ KỸ THUẬT
   =========================== */
$detailSpecs = [];
if (!empty($product['detail_specs'])) {
    $detailSpecs = is_array($product['detail_specs']) ? $product['detail_specs'] : json_decode($product['detail_specs'], true);
}
$descriptionText = $product['description'] ?? ($product['desc'] ?? '');


/* ===========================
   3. SẢN PHẨM LIÊN QUAN
   =========================== */
$related = [];
if (isset($products) && is_array($products)) {
    $related = array_filter($products, function ($p) use ($product) {
        $cat1 = $p['category_slug'] ?? ($p['category'] ?? '');
        $cat2 = $product['category_slug'] ?? ($product['category'] ?? '');
        return $cat1 == $cat2 && $p['id'] != $product['id'];
    });
    $related = array_slice($related, 0, 4);
}


/* ===========================
   4. LOGIC REVIEWS (LẤY TỪ DATABASE)
   =========================== */

// Filter & Sort từ URL
$starFilter = isset($_GET['star']) ? (int)$_GET['star'] : 0;
$sortReview = $_GET['sort'] ?? 'new';

// Khởi tạo biến thống kê
$reviewStats = [
    'avg'   => 0.0,
    'count' => 0,
    'dist'  => [1=>0, 2=>0, 3=>0, 4=>0, 5=>0] // Phân bố sao
];
$reviewList = [];

if (isset($conn)) {
    // 4.1. Lấy điểm trung bình & tổng số đánh giá
    $stmt = $conn->prepare("
        SELECT 
            COUNT(*) as total, 
            COALESCE(AVG(rating), 0) as avg_rate 
        FROM product_reviews 
        WHERE product_id = ? AND status = 1
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stats = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $reviewStats['count'] = (int)$stats['total'];
    $reviewStats['avg']   = round((float)$stats['avg_rate'], 1);

    // 4.2. Lấy phân bố sao (để vẽ thanh progress bar)
    $stmt = $conn->prepare("
        SELECT rating, COUNT(*) as c 
        FROM product_reviews 
        WHERE product_id = ? AND status = 1 
        GROUP BY rating
    ");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $distRes = $stmt->get_result();
    while ($row = $distRes->fetch_assoc()) {
        $r = (int)$row['rating'];
        if (isset($reviewStats['dist'][$r])) {
            $reviewStats['dist'][$r] = (int)$row['c'];
        }
    }
    $stmt->close();

    // 4.3. Lấy danh sách review chi tiết (JOIN với bảng Users để lấy tên)
    $orderSql = "r.created_at DESC"; // Mặc định mới nhất
    if ($sortReview === 'old') $orderSql = "r.created_at ASC";
    elseif ($sortReview === 'rating_desc') $orderSql = "r.rating DESC, r.created_at DESC";
    elseif ($sortReview === 'rating_asc') $orderSql = "r.rating ASC, r.created_at DESC";

    $sql = "
        SELECT r.*, u.full_name 
        FROM product_reviews r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.product_id = ? AND r.status = 1
    ";
    
    // Thêm điều kiện lọc theo sao
    if ($starFilter >= 1 && $starFilter <= 5) {
        $sql .= " AND r.rating = $starFilter";
    }

    $sql .= " ORDER BY $orderSql LIMIT 20"; // Giới hạn 20 review 1 trang

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $reviewList[] = $row;
    }
    $stmt->close();
}

// Dữ liệu hiển thị (Ưu tiên lấy từ Review Table, nếu chưa có review nào thì hiển thị 0)
$displayRating  = $reviewStats['count'] > 0 ? $reviewStats['avg'] : 0;
$displayReviews = $reviewStats['count'];
$fullStars      = (int)floor($displayRating);

include 'includes/header.php';
?>

<div class="breadcrumb">
    <div class="container">
        <a href="index.php">Trang chủ</a>
        <i class="fas fa-chevron-right"></i>
        <a href="products.php">Sản phẩm</a>
        <i class="fas fa-chevron-right"></i>
        <span><?= htmlspecialchars($product['name']) ?></span>
    </div>
</div>

<section class="product-detail-section">
    <div class="container">
        <div class="product-detail-grid">
            <div class="product-gallery">
                <div class="main-image">
                    <?php if ($discount > 0): ?>
                        <div class="discount-badge">-<?= $discount ?>%</div>
                    <?php endif; ?>
                    <img src="<?= htmlspecialchars($galleryImages[0]) ?>" 
                         alt="<?= htmlspecialchars($product['name']) ?>" 
                         id="mainImage">
                </div>
                <div class="thumbnail-images">
                    <?php foreach ($galleryImages as $idx => $imgUrl): ?>
                        <img src="<?= htmlspecialchars($imgUrl) ?>" 
                             class="thumb <?= $idx === 0 ? 'active' : '' ?>" 
                             onclick="changeImage(this)">
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="product-detail-info">
                <div class="brand-tag"><?= htmlspecialchars($product['brand']) ?></div>
                <h1><?= htmlspecialchars($product['name']) ?></h1>

                <div class="rating-row">
                    <div class="stars-large">
                        <?php for ($i = 0; $i < 5; $i++): ?>
                            <i class="fas fa-star <?= $i < $fullStars ? 'active' : '' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <span class="rating-number"><?= $displayRating ?>/5</span>
                    <span class="review-count">(<?= $displayReviews ?> đánh giá)</span>
                    <span class="stock-status <?= $product['stock'] > 0 ? 'in-stock' : 'out-stock'; ?>">
                        <i class="fas fa-check-circle"></i>
                        <?= $product['stock'] > 0 ? 'Còn hàng' : 'Hết hàng'; ?>
                    </span>
                </div>

                <div class="price-section">
                    <div class="current-price"><?= formatMoney($product['price']); ?></div>
                    <?php if ($discount > 0): ?>
                        <div class="old-price-large"><?= formatMoney($product['old_price']); ?></div>
                        <div class="save-amount">Tiết kiệm <?= formatMoney($product['old_price'] - $product['price']); ?></div>
                    <?php endif; ?>
                </div>

                <div class="product-description">
                    <h3>Mô tả ngắn</h3>
                    <p><?= nl2br(htmlspecialchars(substr($descriptionText, 0, 300))) ?>...</p>
                </div>

                <div class="product-actions">
                    <div class="quantity-selector">
                        <button type="button" onclick="changeQty(-1)">-</button>
                        <input type="number" value="1" min="1" max="<?= (int)$product['stock']; ?>" id="quantity">
                        <button type="button" onclick="changeQty(1)">+</button>
                    </div>
                    <button class="btn btn-primary btn-large" onclick="addToCartWithQty(<?= $id ?>)">
                        <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                    </button>
                    <button class="btn btn-outline btn-large" onclick="buyNow(<?= $id ?>)">Mua ngay</button>
                </div>

                <div class="product-features">
                    <div class="feature-item"><i class="fas fa-shield-alt"></i> <span>Bảo hành 24 tháng</span></div>
                    <div class="feature-item"><i class="fas fa-sync-alt"></i> <span>Đổi trả 30 ngày</span></div>
                    <div class="feature-item"><i class="fas fa-truck"></i> <span>Freeship toàn quốc</span></div>
                    <div class="feature-item"><i class="fas fa-credit-card"></i> <span>Trả góp 0%</span></div>
                </div>
            </div>
        </div>

        <div class="product-tabs">
            <div class="tab-buttons">
                <button class="tab-btn active" onclick="showTab(event, 'specs')">Thông số kỹ thuật</button>
                <button class="tab-btn" onclick="showTab(event, 'reviews')">Đánh giá (<?= $displayReviews ?>)</button>
                <button class="tab-btn" onclick="showTab(event, 'warranty')">Bảo hành</button>
            </div>

            <div class="tab-content active" id="specs">
                <h3>Thông số kỹ thuật chi tiết</h3>
                <table class="specs-table">
                    <?php foreach ($detailSpecs as $key => $value): ?>
                        <tr>
                            <td class="spec-label"><?= htmlspecialchars($key) ?></td>
                            <td class="spec-value"><?= htmlspecialchars($value) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>

            <div class="tab-content" id="reviews">
                <div class="reviews-header">
                    <div class="reviews-overall">
                        <div class="avg-score"><?= $displayRating ?></div>
                        <div class="stars-large">
                            <?php for ($i=0; $i<5; $i++) echo '<i class="fas fa-star '.($i<$fullStars?'active':'').'"></i>'; ?>
                        </div>
                        <div class="count"><?= $displayReviews ?> đánh giá</div>
                    </div>

                    <div class="reviews-bars">
                        <?php for ($s=5; $s>=1; $s--): 
                            $pct = $displayReviews > 0 ? round(($reviewStats['dist'][$s] / $displayReviews) * 100) : 0;
                        ?>
                        <div class="rating-bar">
                            <span><?= $s ?> sao</span>
                            <div class="bar"><div class="fill" style="width: <?= $pct ?>%"></div></div>
                            <span><?= $pct ?>%</span>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <div class="review-form-card">
                    <h3>Viết đánh giá của bạn</h3>
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <div class="review-login-note">
                            Vui lòng <a href="login.php"><strong>đăng nhập</strong></a> để gửi đánh giá.
                        </div>
                    <?php else: ?>
                        <form id="reviewForm" class="review-form">
                            <input type="hidden" name="product_id" value="<?= $id ?>">
                            <input type="hidden" name="rating" id="ratingInput" value="0">
                            
                            <div class="star-picker" id="starPicker">
                                <?php for ($i=1; $i<=5; $i++): ?>
                                    <button type="button" class="star-btn" data-star="<?= $i ?>"><i class="fas fa-star"></i></button>
                                <?php endfor; ?>
                                <span class="star-hint" id="starHint">Chọn số sao</span>
                            </div>
                            
                            <textarea name="comment" id="commentInput" rows="4" placeholder="Mời bạn chia sẻ cảm nhận về sản phẩm..." required></textarea>
                            
                            <div class="review-form-actions">
                                <button type="submit" class="btn btn-primary">Gửi đánh giá</button>
                            </div>
                        </form>
                    <?php endif; ?>
                </div>

                <div class="review-list">
                    <div class="review-list-head">
                        <h3>Khách hàng nhận xét</h3>
                        <div class="review-filters">
                            <select id="filterStar" onchange="applyReviewFilter()">
                                <option value="0" <?= $starFilter==0?'selected':'' ?>>Tất cả sao</option>
                                <option value="5" <?= $starFilter==5?'selected':'' ?>>5 sao</option>
                                <option value="4" <?= $starFilter==4?'selected':'' ?>>4 sao</option>
                                <option value="3" <?= $starFilter==3?'selected':'' ?>>3 sao</option>
                                <option value="2" <?= $starFilter==2?'selected':'' ?>>2 sao</option>
                                <option value="1" <?= $starFilter==1?'selected':'' ?>>1 sao</option>
                            </select>
                            <select id="sortReview" onchange="applyReviewFilter()">
                                <option value="new" <?= $sortReview=='new'?'selected':'' ?>>Mới nhất</option>
                                <option value="old" <?= $sortReview=='old'?'selected':'' ?>>Cũ nhất</option>
                                <option value="rating_desc" <?= $sortReview=='rating_desc'?'selected':'' ?>>Cao đến thấp</option>
                                <option value="rating_asc" <?= $sortReview=='rating_asc'?'selected':'' ?>>Thấp đến cao</option>
                            </select>
                        </div>
                    </div>

                    <?php if (empty($reviewList)): ?>
                        <div class="review-empty">Chưa có đánh giá nào phù hợp.</div>
                    <?php else: ?>
                        <?php foreach ($reviewList as $rv): 
                            $uName = !empty($rv['full_name']) ? $rv['full_name'] : 'Khách hàng ẩn danh';
                            $uAvatar = strtoupper(mb_substr($uName, 0, 1));
                            $rStar = (int)$rv['rating'];
                        ?>
                        <div class="review-item">
                            <div class="reviewer-info">
                                <div class="avatar"><?= $uAvatar ?></div>
                                <div>
                                    <strong><?= htmlspecialchars($uName) ?></strong>
                                    <div class="review-date"><?= date('d/m/Y H:i', strtotime($rv['created_at'])) ?></div>
                                </div>
                            </div>
                            <div class="review-stars">
                                <?php for($k=1; $k<=5; $k++) echo '<i class="fas fa-star '.($k<=$rStar?'active':'').'"></i>'; ?>
                            </div>
                            <p><?= nl2br(htmlspecialchars($rv['comment'])) ?></p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="tab-content" id="warranty">
                <h3>Chính sách bảo hành</h3>
                <ul class="warranty-list" style="list-style: none; padding-left: 0;">
                    <li style="margin-bottom: 8px;"><i class="fas fa-check-circle" style="color: #28a745; margin-right: 5px;"></i> Bảo hành chính hãng 24 tháng</li>
                    <li style="margin-bottom: 8px;"><i class="fas fa-check-circle" style="color: #28a745; margin-right: 5px;"></i> Đổi mới trong 30 ngày đầu nếu lỗi NSX</li>
                    <li style="margin-bottom: 8px;"><i class="fas fa-check-circle" style="color: #28a745; margin-right: 5px;"></i> Hỗ trợ cài đặt phần mềm trọn đời</li>
                </ul>
            </div>
        </div>

        <?php if (!empty($related)): ?>
        <div class="related-products">
            <h2>Sản phẩm tương tự</h2>
            <div class="products-grid">
                <?php foreach ($related as $p): 
                    $d = calculateDiscount($p['old_price'], $p['price']);
                ?>
                <div class="product-card">
                    <?php if ($d > 0): ?><div class="product-badge">-<?= $d ?>%</div><?php endif; ?>
                    <div class="product-img-wrapper">
                        <img src="<?= htmlspecialchars($p['image']) ?>" alt="">
                    </div>
                    <div class="product-info">
                        <div class="brand"><?= htmlspecialchars($p['brand']) ?></div>
                        <a href="product-detail.php?id=<?= $p['id'] ?>" class="product-title"><?= htmlspecialchars($p['name']) ?></a>
                        <div class="price"><?= formatMoney($p['price']) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>

<script>
window.PRODUCTS = <?= json_encode($products ?? []) ?>;

// Change Image
function changeImage(thumb) {
    document.getElementById('mainImage').src = thumb.src;
    document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}

// Qty
function changeQty(delta) {
    const input = document.getElementById('quantity');
    let val = parseInt(input.value) + delta;
    if (val < 1) val = 1;
    if (val > parseInt(input.max)) val = parseInt(input.max);
    input.value = val;
}

// Add Cart
function addToCartWithQty(id) {
    const qty = parseInt(document.getElementById('quantity').value);
    for(let i=0; i<qty; i++) addToCart(id, i===qty-1);
}

function buyNow(id) {
    addToCartWithQty(id);
    setTimeout(() => window.location.href = 'checkout.php', 500);
}

// Tabs
function showTab(e, tabName) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    e.target.classList.add('active');
    document.getElementById(tabName).classList.add('active');
    window.location.hash = tabName;
}

// Filter Reviews
function applyReviewFilter() {
    const star = document.getElementById('filterStar').value;
    const sort = document.getElementById('sortReview').value;
    const url = new URL(window.location.href);
    url.searchParams.set('star', star);
    url.searchParams.set('sort', sort);
    url.hash = 'reviews';
    window.location.href = url.toString();
}

// Review Form Logic
(function(){
    const picker = document.getElementById('starPicker');
    const ratingInput = document.getElementById('ratingInput');
    const hint = document.getElementById('starHint');
    
    if(picker) {
        picker.addEventListener('click', (e) => {
            const btn = e.target.closest('.star-btn');
            if(!btn) return;
            const star = parseInt(btn.dataset.star);
            ratingInput.value = star;
            hint.textContent = `Bạn chọn ${star} sao`;
            picker.querySelectorAll('.star-btn').forEach(b => {
                const s = parseInt(b.dataset.star);
                b.querySelector('i').classList.toggle('active', s <= star);
            });
        });
    }

    const form = document.getElementById('reviewForm');
    if(form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const rating = parseInt(ratingInput.value);
            const comment = document.getElementById('commentInput').value.trim();
            
            if(!rating || rating < 1) { showToast('Vui lòng chọn số sao', 'error'); return; }
            if(comment.length < 5) { showToast('Nội dung quá ngắn', 'error'); return; }

            const fd = new FormData(form);
            try {
                const res = await fetch('actions/add-review.php', { method:'POST', body:fd });
                const data = await res.json();
                if(data.ok) {
                    showToast(data.message);
                    setTimeout(() => {
                        const u = new URL(window.location.href);
                        u.hash = 'reviews';
                        window.location.href = u.toString();
                    }, 800);
                } else {
                    showToast(data.message, 'error');
                }
            } catch(err) {
                showToast('Lỗi kết nối', 'error');
            }
        });
    }
})();

// Auto switch tab by hash
document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash.replace('#','');
    if(hash) {
        const btn = document.querySelector(`.tab-btn[onclick*="${hash}"]`);
        if(btn) btn.click();
    }
});
</script>

<?php include 'includes/footer.php'; ?>

<style>
.reviews-header { display:grid; grid-template-columns: 200px 1fr; gap:30px; margin-bottom:30px; background:#f9fafb; padding:20px; border-radius:12px; }
.reviews-overall { text-align:center; display:flex; flex-direction:column; justify-content:center; }
.avg-score { font-size:48px; font-weight:800; color:#111827; line-height:1; }
.stars-large i { color:#d1d5db; font-size:18px; }
.stars-large i.active { color:#f59e0b; }
.rating-bar { display:flex; align-items:center; gap:10px; font-size:13px; color:#6b7280; margin-bottom:6px; }
.rating-bar .bar { flex:1; height:8px; background:#e5e7eb; border-radius:10px; overflow:hidden; }
.rating-bar .fill { height:100%; background:#f59e0b; border-radius:10px; }

.review-form-card { background:#fff; border:1px solid #e5e7eb; padding:20px; border-radius:12px; margin-bottom:30px; }
.star-picker { display:flex; gap:5px; margin-bottom:15px; align-items:center; }
.star-btn { background:none; border:none; font-size:24px; color:#d1d5db; cursor:pointer; padding:0; transition:0.2s; }
.star-btn i.active { color:#f59e0b; }
.review-form textarea { width:100%; border:1px solid #d1d5db; padding:15px; border-radius:8px; outline:none; }
.review-form textarea:focus { border-color:#4f46e5; }
.review-form-actions { margin-top:15px; text-align:right; }

.review-list-head { display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; padding-bottom:15px; border-bottom:1px solid #e5e7eb; }
.review-filters select { padding:6px 12px; border-radius:6px; border:1px solid #d1d5db; font-size:13px; }

.review-item { padding-bottom:20px; margin-bottom:20px; border-bottom:1px solid #f3f4f6; }
.reviewer-info { display:flex; align-items:center; gap:12px; margin-bottom:8px; }
.reviewer-info .avatar { width:40px; height:40px; background:#e0e7ff; color:#4f46e5; font-weight:700; display:flex; align-items:center; justify-content:center; border-radius:50%; }
.review-date { font-size:12px; color:#9ca3af; }
.review-stars { margin-bottom:10px; }
.review-stars i { font-size:12px; color:#d1d5db; }
.review-stars i.active { color:#f59e0b; }
.review-empty { text-align:center; padding:40px; color:#9ca3af; font-style:italic; }

@media(max-width:768px) { .reviews-header { grid-template-columns:1fr; } }
</style>