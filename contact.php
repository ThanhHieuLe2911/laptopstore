<?php
require_once 'includes/config.php';
$page_title = 'Liên hệ';
include 'includes/header.php';
?>

<section class="contact-hero-section">
    <div class="container">
        <div class="hero-content">
            <span class="sub-title">Hỗ trợ 24/7</span>
            <h1>Liên Hệ Với Chúng Tôi</h1>
            <p>Chúng tôi luôn lắng nghe và sẵn sàng giải đáp mọi thắc mắc của bạn.</p>
        </div>
    </div>
</section>

<section class="contact-info-section">
    <div class="container">
        <div class="info-grid">
            <div class="info-card">
                <div class="icon-box"><i class="fas fa-map-marker-alt"></i></div>
                <h3>Trụ sở chính</h3>
                <p>123 Nguyễn Văn Cừ, Quận 5,<br>TP. Hồ Chí Minh</p>
            </div>
            <div class="info-card">
                <div class="icon-box"><i class="fas fa-phone-alt"></i></div>
                <h3>Hotline</h3>
                <p><strong>1900 1234</strong><br>(028) 3838 3838</p>
            </div>
            <div class="info-card">
                <div class="icon-box"><i class="fas fa-envelope"></i></div>
                <h3>Email</h3>
                <p>hotro@laptopstore.vn<br>sales@laptopstore.vn</p>
            </div>
            <div class="info-card">
                <div class="icon-box"><i class="fas fa-clock"></i></div>
                <h3>Giờ làm việc</h3>
                <p>Thứ 2 - Chủ Nhật<br>8:00 - 21:00</p>
            </div>
        </div>
    </div>
</section>

<section class="contact-form-section">
    <div class="container">
        <div class="form-layout">
            <div class="form-wrapper">
                <div class="form-header">
                    <h2>Gửi tin nhắn</h2>
                    <p>Điền thông tin bên dưới, chúng tôi sẽ phản hồi trong vòng 24h.</p>
                </div>
                <form class="contact-form" onsubmit="handleContactForm(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Họ và tên <span class="required">*</span></label>
                            <input type="text" name="name" required placeholder="Nguyễn Văn A">
                        </div>
                        <div class="form-group">
                            <label>Email <span class="required">*</span></label>
                            <input type="email" name="email" required placeholder="example@email.com">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="tel" name="phone" placeholder="09xxxxxxxxx">
                        </div>
                        <div class="form-group">
                            <label>Vấn đề cần hỗ trợ</label>
                            <select name="subject">
                                <option value="general">Tư vấn chung</option>
                                <option value="order">Tra cứu đơn hàng</option>
                                <option value="warranty">Bảo hành - Đổi trả</option>
                                <option value="tech">Hỗ trợ kỹ thuật</option>
                                <option value="other">Khác</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Nội dung tin nhắn <span class="required">*</span></label>
                        <textarea name="message" rows="5" required placeholder="Mô tả chi tiết vấn đề của bạn..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-paper-plane"></i> Gửi Tin Nhắn
                    </button>
                </form>
            </div>

            <div class="store-locator">
                <div class="locator-header">
                    <h3><i class="fas fa-store"></i> Tìm cửa hàng gần bạn</h3>
                    <select id="citySelect" onchange="filterStores()">
                        <option value="all">Tất cả khu vực</option>
                        <option value="hcm">TP. Hồ Chí Minh</option>
                        <option value="hanoi">Hà Nội</option>
                        <option value="danang">Đà Nẵng</option>
                    </select>
                </div>

                <div class="store-list" id="storesGrid">
                    <div class="store-item" data-city="hcm">
                        <div class="store-icon"><i class="fas fa-laptop-house"></i></div>
                        <div class="store-info">
                            <h4>Chi nhánh Quận 5</h4>
                            <p>123 Nguyễn Văn Cừ, P.4, Q.5, TP.HCM</p>
                            <a href="https://maps.google.com" target="_blank" class="map-link">Xem bản đồ</a>
                        </div>
                    </div>
                    <div class="store-item" data-city="hcm">
                        <div class="store-icon"><i class="fas fa-laptop-house"></i></div>
                        <div class="store-info">
                            <h4>Chi nhánh Thủ Đức</h4>
                            <p>456 Lê Văn Việt, TP. Thủ Đức, TP.HCM</p>
                            <a href="https://maps.google.com" target="_blank" class="map-link">Xem bản đồ</a>
                        </div>
                    </div>
                    <div class="store-item" data-city="hanoi">
                        <div class="store-icon"><i class="fas fa-laptop-house"></i></div>
                        <div class="store-info">
                            <h4>Chi nhánh Thái Hà</h4>
                            <p>789 Thái Hà, Đống Đa, Hà Nội</p>
                            <a href="https://maps.google.com" target="_blank" class="map-link">Xem bản đồ</a>
                        </div>
                    </div>
                    <div class="store-item" data-city="danang">
                        <div class="store-icon"><i class="fas fa-laptop-house"></i></div>
                        <div class="store-info">
                            <h4>Chi nhánh Hải Châu</h4>
                            <p>321 Lê Duẩn, Hải Châu, Đà Nẵng</p>
                            <a href="https://maps.google.com" target="_blank" class="map-link">Xem bản đồ</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="faq-section">
    <div class="container">
        <div class="section-header text-center">
            <h2>Câu Hỏi Thường Gặp</h2>
            <p>Giải đáp nhanh những thắc mắc phổ biến nhất</p>
        </div>
        
        <div class="faq-grid">
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h3>Làm thế nào để đặt hàng online?</h3>
                    <i class="fas fa-plus"></i>
                </div>
                <div class="faq-answer">
                    <p>Quý khách chọn sản phẩm ưng ý, nhấn "Thêm vào giỏ hàng" và tiến hành thanh toán theo hướng dẫn. Chúng tôi hỗ trợ nhiều hình thức thanh toán: COD, Chuyển khoản, Thẻ tín dụng.</p>
                </div>
            </div>
            
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h3>Chính sách bảo hành như thế nào?</h3>
                    <i class="fas fa-plus"></i>
                </div>
                <div class="faq-answer">
                    <p>Tất cả sản phẩm Laptop đều được bảo hành chính hãng từ 12-24 tháng. Hỗ trợ đổi mới trong 30 ngày đầu nếu có lỗi phần cứng từ nhà sản xuất.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h3>Thời gian giao hàng bao lâu?</h3>
                    <i class="fas fa-plus"></i>
                </div>
                <div class="faq-answer">
                    <p>Nội thành TP.HCM & Hà Nội: Giao trong ngày. Các tỉnh thành khác: 2-4 ngày làm việc. Miễn phí vận chuyển cho đơn hàng trên 5 triệu đồng.</p>
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <h3>Cửa hàng có hỗ trợ trả góp không?</h3>
                    <i class="fas fa-plus"></i>
                </div>
                <div class="faq-answer">
                    <p>Có, chúng tôi hỗ trợ trả góp 0% lãi suất qua thẻ tín dụng của hơn 25 ngân hàng. Hoặc trả góp qua công ty tài chính (HD Saison, FE Credit) với thủ tục đơn giản.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
function handleContactForm(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang gửi...';
    btn.disabled = true;
    
    // Giả lập gửi form
    setTimeout(() => {
        showToast('Gửi tin nhắn thành công! Chúng tôi sẽ liên hệ sớm nhất.');
        e.target.reset();
        btn.innerHTML = originalText;
        btn.disabled = false;
    }, 1500);
}

function filterStores() {
    const city = document.getElementById('citySelect').value;
    const stores = document.querySelectorAll('.store-item');
    
    stores.forEach(store => {
        if (city === 'all' || store.dataset.city === city) {
            store.style.display = 'flex';
        } else {
            store.style.display = 'none';
        }
    });
}

function toggleFaq(element) {
    const parent = element.parentElement;
    const isActive = parent.classList.contains('active');
    
    // Close all others
    document.querySelectorAll('.faq-item').forEach(item => {
        item.classList.remove('active');
        item.querySelector('.faq-question i').className = 'fas fa-plus';
    });
    
    // Toggle current
    if (!isActive) {
        parent.classList.add('active');
        element.querySelector('i').className = 'fas fa-minus';
    }
}
</script>

<style>
/* Hero */
.contact-hero-section {
    background: linear-gradient(135deg, #4f46e5, #818cf8);
    color: white;
    padding: 80px 0;
    text-align: center;
}
.hero-content h1 { font-size: 36px; margin: 10px 0; }
.hero-content .sub-title { 
    text-transform: uppercase; letter-spacing: 2px; font-size: 12px; font-weight: 700; opacity: 0.8; 
}

/* Info Cards */
.contact-info-section { margin-top: -50px; position: relative; z-index: 2; }
.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
.info-card {
    background: white;
    padding: 30px 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    transition: transform 0.3s;
}
.info-card:hover { transform: translateY(-5px); }
.icon-box {
    width: 60px; height: 60px; background: #eff6ff; color: #4f46e5;
    border-radius: 50%; display: flex; align-items: center; justify-content: center;
    font-size: 24px; margin: 0 auto 15px;
}
.info-card h3 { font-size: 18px; margin-bottom: 10px; color: #1f2937; }
.info-card p { font-size: 14px; color: #6b7280; line-height: 1.6; }

/* Form Section */
.contact-form-section { padding: 80px 0; background: #f9fafb; }
.form-layout { display: grid; grid-template-columns: 1.5fr 1fr; gap: 40px; }
.form-wrapper, .store-locator { background: white; padding: 40px; border-radius: 16px; border: 1px solid #e5e7eb; }
.form-header { margin-bottom: 25px; }
.form-header h2 { font-size: 24px; margin-bottom: 5px; }
.required { color: #ef4444; }

/* Store Locator */
.store-locator { height: fit-content; }
.locator-header { margin-bottom: 20px; border-bottom: 1px solid #e5e7eb; padding-bottom: 15px; }
.locator-header select { width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #d1d5db; margin-top: 10px; }
.store-list { display: flex; flex-direction: column; gap: 15px; max-height: 400px; overflow-y: auto; }
.store-item { display: flex; gap: 15px; padding: 15px; border: 1px solid #f3f4f6; border-radius: 8px; transition: 0.2s; }
.store-item:hover { border-color: #4f46e5; background: #fdfeff; }
.store-icon { color: #4f46e5; font-size: 20px; margin-top: 2px; }
.store-info h4 { font-size: 15px; margin: 0 0 5px; }
.store-info p { font-size: 13px; color: #6b7280; margin-bottom: 8px; }
.map-link { font-size: 12px; color: #4f46e5; text-decoration: none; font-weight: 600; }

/* FAQ */
.faq-section { padding: 60px 0; }
.faq-grid { max-width: 800px; margin: 30px auto 0; }
.faq-item { border-bottom: 1px solid #e5e7eb; margin-bottom: 10px; }
.faq-question {
    padding: 20px 0; display: flex; justify-content: space-between; align-items: center;
    cursor: pointer; font-weight: 600; color: #1f2937;
}
.faq-question:hover { color: #4f46e5; }
.faq-answer { max-height: 0; overflow: hidden; transition: all 0.3s ease; color: #4b5563; line-height: 1.6; }
.faq-item.active .faq-answer { max-height: 200px; padding-bottom: 20px; }
.faq-item.active .faq-question { color: #4f46e5; }

/* Mobile Responsive */
@media (max-width: 900px) {
    .form-layout { grid-template-columns: 1fr; }
    .contact-info-section { margin-top: 30px; }
}
</style>

<?php include 'includes/footer.php'; ?>