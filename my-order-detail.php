<?php
require_once 'includes/config.php';

// 1. Kiểm tra session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

date_default_timezone_set('Asia/Ho_Chi_Minh');

$userId  = (int)$_SESSION['user_id'];
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($orderId <= 0) {
    header("Location: my-orders.php");
    exit;
}

// 2. Lấy thông tin đơn hàng
$sqlOrder = "SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1";
$stmt = $conn->prepare($sqlOrder);
$stmt->bind_param("ii", $orderId, $userId);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    echo "<script>alert('Không tìm thấy đơn hàng!'); window.location='my-orders.php';</script>";
    exit;
}

// 3. Helper Functions
if (!function_exists('formatMoney')) {
    function formatMoney($number) {
        // Format: 100.000₫
        return number_format($number, 0, ',', '.') . '₫';
    }
}

function getProductImageUrl($imgName) {
    $imgName = trim((string)$imgName);
    if ($imgName === '') return 'assets/img/no-image.png';
    if (preg_match('/^https?:\/\//i', $imgName)) return $imgName;
    
    $paths = ["uploads/products/$imgName", "uploads/$imgName", "assets/img/products/$imgName", "assets/img/$imgName", "$imgName"];
    foreach ($paths as $p) {
        if (file_exists(__DIR__ . '/' . $p)) return $p;
    }
    return 'assets/img/no-image.png';
}

// 4. MAPPING DATA
$statusMap = [
    'pending'    => ['label' => 'Chờ xử lý',   'class' => 'warning', 'icon' => 'clock'],
    'processing' => ['label' => 'Đang xử lý',  'class' => 'primary', 'icon' => 'cogs'],
    'shipping'   => ['label' => 'Đang giao',   'class' => 'info',    'icon' => 'truck'],
    'completed'  => ['label' => 'Hoàn thành',  'class' => 'success', 'icon' => 'check-circle'],
    'cancelled'  => ['label' => 'Đã hủy',      'class' => 'danger',  'icon' => 'times-circle'],
];

$paymentMethodMap = [
    'cod'  => 'Tiền mặt (COD)',
    'bank' => 'Chuyển khoản',
    'card' => 'Thẻ quốc tế',
    'momo' => 'Ví MoMo',
];

// Mapping trạng thái thanh toán
$payStatusMap = [
    'paid'   => ['label' => 'ĐÃ THANH TOÁN',  'color' => '#16a34a', 'bg' => '#dcfce7', 'border' => '#86efac'],
    'unpaid' => ['label' => 'CHƯA THANH TOÁN', 'color' => '#d97706', 'bg' => '#fef3c7', 'border' => '#fcd34d'],
];

// Lấy sản phẩm
$sqlItems = "
    SELECT oi.*, COALESCE(p.name, oi.product_name) AS product_name, p.image AS product_image 
    FROM order_items oi
    LEFT JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id = ? ORDER BY oi.id ASC
";
$stmt = $conn->prepare($sqlItems);
$stmt->bind_param("i", $orderId);
$stmt->execute();
$itemsRes = $stmt->get_result();
$stmt->close();

$page_title = "Đơn hàng #" . ($order['order_code'] ?? $orderId);
include 'includes/header.php';
?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<style>
  :root { --bg: #f3f4f6; --text: #1f2937; --card-bg: #fff; --primary-red: #ef4444; }
  body { background: var(--bg); color: var(--text); font-family: 'Segoe UI', sans-serif; }

  .order-wrapper { max-width: 1000px; margin: 30px auto; }
  
  /* Buttons Toolbar */
  .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
  .btn-action {
    display: inline-flex; align-items: center; gap: 8px; padding: 10px 16px;
    border-radius: 8px; font-weight: 600; text-decoration: none; font-size: 14px; transition: 0.2s; border: none; cursor: pointer;
  }
  .btn-back { background: #fff; color: #4b5563; border: 1px solid #d1d5db; }
  .btn-back:hover { background: #f9fafb; color: #111827; }
  
  .btn-pdf { background: var(--primary-red); color: #fff; box-shadow: 0 2px 5px rgba(239, 68, 68, 0.3); }
  .btn-pdf:hover { background: #dc2626; }

  /* Invoice Card */
  .invoice-card {
    background: var(--card-bg); border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    overflow: hidden; border: 1px solid #e5e7eb;
  }
  
  /* Header Section */
  .inv-header { padding: 30px; border-bottom: 1px solid #e5e7eb; display: flex; justify-content: space-between; align-items: start; background: #fff; }
  .brand-title { font-size: 24px; font-weight: 800; color: #4f46e5; margin-bottom: 5px; }
  .inv-status { text-align: right; }
  .badge-lg { padding: 6px 12px; border-radius: 6px; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }

  /* Info Grid */
  .inv-body { padding: 30px; }
  .info-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 30px; }
  .info-box h4 { font-size: 12px; color: #6b7280; text-transform: uppercase; margin-bottom: 8px; font-weight: 700; }
  .info-box p { font-size: 14px; color: #111827; margin: 0; line-height: 1.5; }

  /* Payment Status Box */
  .payment-box {
    border: 1px solid; border-radius: 8px; padding: 12px; display: inline-block; margin-top: 8px; font-size: 13px; font-weight: 600;
  }

  /* Table */
  .inv-table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
  .inv-table th { text-align: left; padding: 12px; background: #f9fafb; font-size: 12px; text-transform: uppercase; color: #6b7280; border-bottom: 2px solid #e5e7eb; }
  .inv-table td { padding: 16px 12px; border-bottom: 1px solid #f3f4f6; vertical-align: middle; font-size: 14px; }
  .thumb-img { width: 48px; height: 48px; border-radius: 6px; object-fit: cover; border: 1px solid #e5e7eb; margin-right: 12px; vertical-align: middle; }
  
  /* --- CUSTOM PRICE STYLES --- */
  .price-unit { font-weight: 500; color: #374151; }
  .price-sub { font-weight: 700; color: var(--primary-red); } /* Thành tiền con: Đỏ, Đậm */
  .price-total { font-weight: 800; color: var(--primary-red); font-size: 20px; } /* Tổng cuối: Đỏ, To, Đậm */

  /* Summary Section */
  .inv-footer { display: flex; justify-content: flex-end; background: #f9fafb; padding: 20px 30px; border-top: 1px solid #e5e7eb; }
  .totals { width: 300px; }
  .row-total { display: flex; justify-content: space-between; margin-bottom: 8px; font-size: 14px; color: #4b5563; }
  .row-total.final { font-size: 16px; font-weight: 700; color: #111827; border-top: 1px dashed #d1d5db; padding-top: 12px; margin-top: 12px; align-items: center; }

  /* Responsive */
  @media (max-width: 768px) {
    .info-grid { grid-template-columns: 1fr; }
    .inv-header { flex-direction: column; gap: 20px; }
    .inv-status { text-align: left; }
  }
</style>

<div class="order-wrapper">
  
  <div class="toolbar" data-html2canvas-ignore="true">
    <a href="my-orders.php" class="btn-action btn-back">
      <i class="fas fa-arrow-left"></i> Quay lại
    </a>
    <button onclick="exportInvoice()" class="btn-action btn-pdf">
      <i class="fas fa-file-pdf"></i> Xuất hóa đơn PDF
    </button>
  </div>

  <div id="invoice-content" class="invoice-card">
    
    <div class="inv-header">
      <div>
        <div class="brand-title"><i class="fas fa-laptop-code"></i> LaptopStore</div>
        <div style="font-size:13px; color:#6b7280;">Hóa đơn điện tử</div>
      </div>
      <div class="inv-status">
        <div style="font-size:18px; font-weight:700; color:#111827; margin-bottom:5px;">
            MÃ ĐƠN: #<?= htmlspecialchars($order['order_code'] ?? $orderId) ?>
        </div>
        <div style="font-size:13px; color:#6b7280; margin-bottom:10px;">
            Ngày đặt: <?= date('d/m/Y - H:i', strtotime($order['created_at'])) ?>
        </div>
        
        <?php
            $st = strtolower(trim($order['status'] ?? ''));
            $stInfo = $statusMap[$st] ?? $statusMap['pending'];
        ?>
        <span style="color: #6b7280; font-size: 13px;">Trạng thái đơn:</span>
        <span style="font-weight:700; color:#4f46e5; text-transform:uppercase;">
            <?= $stInfo['label'] ?>
        </span>
      </div>
    </div>

    <div class="inv-body">
      <div class="info-grid">
        <div class="info-box">
          <h4>Khách hàng</h4>
          <p><strong><?= htmlspecialchars($order['full_name']) ?></strong></p>
          <p><?= htmlspecialchars($order['phone']) ?></p>
          <p><?= htmlspecialchars($order['email']) ?></p>
        </div>

        <div class="info-box">
          <h4>Địa chỉ nhận hàng</h4>
          <p><?= htmlspecialchars($order['address']) ?></p>
          <p><?= htmlspecialchars($order['district']) ?>, <?= htmlspecialchars($order['city']) ?></p>
          <?php if(!empty($order['note'])): ?>
            <div style="margin-top:8px; font-size:12px; background:#fffbeb; padding:5px; border-radius:4px; color:#b45309;">
                Ghi chú: <?= htmlspecialchars($order['note']) ?>
            </div>
          <?php endif; ?>
        </div>

        <div class="info-box">
          <h4>Thông tin thanh toán</h4>
          <p><?= $paymentMethodMap[$order['payment_method']] ?? $order['payment_method'] ?></p>
          
          <?php
            // Xử lý hiển thị Payment Status
            $payStKey = strtolower($order['payment_status'] ?? 'unpaid');
            $payInfo  = $payStatusMap[$payStKey] ?? $payStatusMap['unpaid'];
          ?>
          <div class="payment-box" style="
              color: <?= $payInfo['color'] ?>; 
              background: <?= $payInfo['bg'] ?>; 
              border-color: <?= $payInfo['border'] ?>;">
              <?= $payInfo['label'] ?>
          </div>
        </div>
      </div>

      <table class="inv-table">
        <thead>
          <tr>
            <th style="width: 50%;">Sản phẩm</th>
            <th style="text-align: right;">Đơn giá</th>
            <th style="text-align: center;">SL</th>
            <th style="text-align: right;">Thành tiền</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($item = $itemsRes->fetch_assoc()): 
              $img = getProductImageUrl($item['product_image'] ?? '');
              $sub = $item['price'] * $item['quantity'];
          ?>
          <tr>
            <td>
                <img src="<?= $img ?>" class="thumb-img" alt="sp">
                <strong><?= htmlspecialchars($item['product_name']) ?></strong>
            </td>
            <td style="text-align: right;">
                <span class="price-unit"><?= formatMoney($item['price']) ?></span>
            </td>
            <td style="text-align: center;">x<?= $item['quantity'] ?></td>
            <td style="text-align: right;">
                <span class="price-sub"><?= formatMoney($sub) ?></span>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

    <div class="inv-footer">
      <div class="totals">
        <div class="row-total">
            <span>Tổng tiền hàng:</span>
            <span><?= formatMoney($order['subtotal']) ?></span>
        </div>
        <div class="row-total">
            <span>Phí vận chuyển:</span>
            <span><?= formatMoney($order['shipping_fee']) ?></span>
        </div>
        <?php if($order['discount'] > 0): ?>
        <div class="row-total" style="color:#16a34a;">
            <span>Giảm giá:</span>
            <span>-<?= formatMoney($order['discount']) ?></span>
        </div>
        <?php endif; ?>
        <div class="row-total final">
            <span>TỔNG THANH TOÁN:</span>
            <span class="price-total"><?= formatMoney($order['total']) ?></span>
        </div>
      </div>
    </div>

  </div> </div>

<script>
  function exportInvoice() {
    const element = document.getElementById('invoice-content');
    const opt = {
      margin:       [10, 10, 10, 10], 
      filename:     'Hoa_don_LaptopStore_#<?= $order['order_code'] ?>.pdf',
      image:        { type: 'jpeg', quality: 0.98 },
      html2canvas:  { scale: 2, useCORS: true }, 
      jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };
    html2pdf().set(opt).from(element).save();
  }
</script>

<?php include 'includes/footer.php'; ?>