<?php
require_once 'includes/config.php';
$page_title = 'Giới thiệu';
include 'includes/header.php';
?>

<!-- Breadcrumb -->
<div class="breadcrumb">
    <div class="container">
        <a href="index.php">Trang chủ</a>
        <i class="fas fa-chevron-right"></i>
        <span>Giới thiệu</span>
    </div>
</div>

<!-- About Hero -->
<section class="about-hero">
    <div class="container">
        <div class="about-hero-content">
            <h1>Về <span class="gradient-text">LaptopStore</span></h1>
            <p class="lead">Hệ thống bán lẻ Laptop uy tín hàng đầu Việt Nam</p>
        </div>
    </div>
</section>

<!-- Story Section -->
<section class="about-story">
    <div class="container">
        <div class="story-grid">
            <div class="story-image">
                <img src="https://images.unsplash.com/photo-1497366216548-37526070297c?w=600&q=80" alt="About Us">
            </div>
            <div class="story-content">
                <span class="section-label">Câu chuyện của chúng tôi</span>
                <h2>10 Năm Đồng Hành Cùng Công Nghệ</h2>
                <p>Được thành lập vào năm 2014, LaptopStore bắt đầu từ một cửa hàng nhỏ với niềm đam mê công nghệ và khát vọng mang đến những sản phẩm chất lượng nhất cho khách hàng Việt Nam.</p>
                <p>Sau 10 năm phát triển, chúng tôi tự hào là hệ thống bán lẻ laptop uy tín với hơn 50 cửa hàng trên toàn quốc, phục vụ hàng triệu khách hàng.</p>
                <div class="story-stats">
                    <div class="stat-card">
                        <h3>10+</h3>
                        <p>Năm kinh nghiệm</p>
                    </div>
                    <div class="stat-card">
                        <h3>50+</h3>
                        <p>Cửa hàng</p>
                    </div>
                    <div class="stat-card">
                        <h3>100K+</h3>
                        <p>Khách hàng</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Values Section -->
<section class="values-section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Giá trị cốt lõi</span>
            <h2>Điều Chúng Tôi Tin Tưởng</h2>
        </div>
        <div class="values-grid">
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-gem"></i>
                </div>
                <h3>Chất Lượng</h3>
                <p>100% sản phẩm chính hãng, cam kết nguồn gốc xuất xứ rõ ràng</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h3>Tận Tâm</h3>
                <p>Luôn lắng nghe và thấu hiểu nhu cầu của từng khách hàng</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h3>Đổi Mới</h3>
                <p>Không ngừng cập nhật công nghệ mới nhất cho khách hàng</p>
            </div>
            <div class="value-card">
                <div class="value-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <h3>Uy Tín</h3>
                <p>Xây dựng lòng tin qua chất lượng sản phẩm và dịch vụ</p>
            </div>
        </div>
    </div>
</section>

<!-- Team Section -->
<section class="team-section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Đội ngũ</span>
            <h2>Gặp Gỡ Chuyên Gia Của Chúng Tôi</h2>
        </div>
        <div class="team-grid">
            <div class="team-card">
                <div class="team-avatar">
                    <img src="https://ui-avatars.com/api/?name=Nguyen+Van+A&size=200&background=4f46e5&color=fff" alt="CEO">
                </div>
                <h3>Nguyễn Văn A</h3>
                <p class="team-role">Giám đốc điều hành</p>
                <div class="team-social">
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fas fa-envelope"></i></a>
                </div>
            </div>
            <div class="team-card">
                <div class="team-avatar">
                    <img src="https://ui-avatars.com/api/?name=Tran+Thi+B&size=200&background=ec4899&color=fff" alt="CTO">
                </div>
                <h3>Trần Thị B</h3>
                <p class="team-role">Giám đốc công nghệ</p>
                <div class="team-social">
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fas fa-envelope"></i></a>
                </div>
            </div>
            <div class="team-card">
                <div class="team-avatar">
                    <img src="https://ui-avatars.com/api/?name=Le+Van+C&size=200&background=f59e0b&color=fff" alt="COO">
                </div>
                <h3>Lê Văn C</h3>
                <p class="team-role">Giám đốc vận hành</p>
                <div class="team-social">
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fas fa-envelope"></i></a>
                </div>
            </div>
            <div class="team-card">
                <div class="team-avatar">
                    <img src="https://ui-avatars.com/api/?name=Pham+Thi+D&size=200&background=10b981&color=fff" alt="CMO">
                </div>
                <h3>Phạm Thị D</h3>
                <p class="team-role">Giám đốc marketing</p>
                <div class="team-social">
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fas fa-envelope"></i></a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Certifications -->
<section class="certifications-section">
    <div class="container">
        <div class="section-header">
            <span class="section-label">Chứng nhận</span>
            <h2>Đối Tác & Chứng Nhận</h2>
        </div>
        <div class="partner-logos">
            <div class="partner-logo">
                <img src="https://upload.wikimedia.org/wikipedia/commons/f/fa/Apple_logo_black.svg" alt="Apple" height="40">
            </div>
            <div class="partner-logo">
                <img src="https://upload.wikimedia.org/wikipedia/commons/4/48/Dell_Logo.svg" alt="Dell" height="40">
            </div>
            <div class="partner-logo">
                <img src="https://upload.wikimedia.org/wikipedia/commons/2/2e/ASUS_Logo.svg" alt="Asus" height="40">
            </div>
            <div class="partner-logo">
                <img src="https://upload.wikimedia.org/wikipedia/commons/b/b8/Lenovo_logo_2015.svg" alt="Lenovo" height="40">
            </div>
            <div class="partner-logo">
                <img src="https://upload.wikimedia.org/wikipedia/commons/6/65/MSI_Gaming_logo.svg" alt="MSI" height="40">
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Sẵn Sàng Trải Nghiệm?</h2>
            <p>Ghé thăm cửa hàng gần nhất hoặc mua sắm trực tuyến ngay hôm nay</p>
            <div class="cta-buttons">
                <a href="products.php" class="btn btn-primary btn-large">
                    Mua Sắm Ngay
                </a>
                <a href="contact.php" class="btn btn-outline btn-large">
                    Liên Hệ
                </a>
            </div>
        </div>
    </div>
</section>

<script>
window.PRODUCTS = <?php echo json_encode($products); ?>;
</script>

<?php include 'includes/footer.php'; ?>