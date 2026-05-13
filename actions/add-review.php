<?php
// Đảm bảo session đã bật
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once '../includes/config.php';

header('Content-Type: application/json; charset=utf-8');

// 1. Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['ok' => false, 'message' => 'Vui lòng đăng nhập để đánh giá.']);
    exit;
}

$userId    = (int)$_SESSION['user_id'];
$productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$rating    = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment   = trim($_POST['comment'] ?? '');

// 2. Validate dữ liệu đầu vào
if ($productId <= 0) {
    echo json_encode(['ok' => false, 'message' => 'Sản phẩm không hợp lệ.']);
    exit;
}

if ($rating < 1 || $rating > 5) {
    echo json_encode(['ok' => false, 'message' => 'Vui lòng chọn số sao (1-5).']);
    exit;
}

if (mb_strlen($comment, 'UTF-8') < 5) {
    echo json_encode(['ok' => false, 'message' => 'Nội dung đánh giá quá ngắn (tối thiểu 5 ký tự).']);
    exit;
}

try {
    // 3. Kiểm tra sản phẩm có tồn tại không
    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $stmt->bind_param("i", $productId);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        echo json_encode(['ok' => false, 'message' => 'Sản phẩm không tồn tại.']);
        exit;
    }
    $stmt->close();

    // 4. Kiểm tra xem User này đã đánh giá sản phẩm này chưa
    // (Vì DB của bạn không có khóa UNIQUE nên phải check thủ công bằng PHP)
    $checkStmt = $conn->prepare("SELECT id FROM product_reviews WHERE user_id = ? AND product_id = ?");
    $checkStmt->bind_param("ii", $userId, $productId);
    $checkStmt->execute();
    $existingReview = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if ($existingReview) {
        // --- CASE 1: Đã có -> Cập nhật lại ---
        $updateStmt = $conn->prepare("
            UPDATE product_reviews 
            SET rating = ?, comment = ?, created_at = NOW(), status = 1 
            WHERE id = ?
        ");
        $updateStmt->bind_param("isi", $rating, $comment, $existingReview['id']);
        $updateStmt->execute();
        $updateStmt->close();
        $msg = "Đã cập nhật đánh giá của bạn!";
    } else {
        // --- CASE 2: Chưa có -> Thêm mới ---
        $insertStmt = $conn->prepare("
            INSERT INTO product_reviews (product_id, user_id, rating, comment, status) 
            VALUES (?, ?, ?, ?, 1)
        ");
        $insertStmt->bind_param("iiis", $productId, $userId, $rating, $comment);
        $insertStmt->execute();
        $insertStmt->close();
        $msg = "Cảm ơn bạn đã đánh giá!";
    }

    // 5. Tự động tính lại số sao trung bình cho bảng Products
    // Bước này giúp hiển thị số sao chuẩn ngoài trang chủ
    $calcStmt = $conn->prepare("
        SELECT COUNT(*) as total, AVG(rating) as avg_rate 
        FROM product_reviews 
        WHERE product_id = ? AND status = 1
    ");
    $calcStmt->bind_param("i", $productId);
    $calcStmt->execute();
    $stats = $calcStmt->get_result()->fetch_assoc();
    $calcStmt->close();

    $newCount = (int)($stats['total'] ?? 0);
    $newAvg   = round((float)($stats['avg_rate'] ?? 5), 1);

    // Cập nhật vào bảng products
    $prodUpdate = $conn->prepare("UPDATE products SET rating = ?, reviews = ? WHERE id = ?");
    $prodUpdate->bind_param("dii", $newAvg, $newCount, $productId);
    $prodUpdate->execute();
    $prodUpdate->close();

    // Trả về kết quả thành công
    echo json_encode(['ok' => true, 'message' => $msg]);

} catch (Exception $e) {
    // Ghi log lỗi nếu cần thiết
    echo json_encode(['ok' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}
?>