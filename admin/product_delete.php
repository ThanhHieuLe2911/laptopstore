<?php
require_once "_auth.php";
require_once "../includes/config.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id > 0) {
    // 1. Kiểm tra xem sản phẩm đã có trong đơn hàng nào chưa?
    // Dựa vào constraint `order_items_product_fk` trong database của bạn
    $checkStmt = $conn->prepare("SELECT COUNT(*) as total FROM order_items WHERE product_id = ?");
    $checkStmt->bind_param("i", $id);
    $checkStmt->execute();
    $checkRes = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if ($checkRes['total'] > 0) {
        // Nếu đã có người mua, không được xóa để giữ lịch sử đơn hàng
        $error = "Không thể xóa sản phẩm này vì đã có " . $checkRes['total'] . " đơn hàng mua nó. Hãy ẩn sản phẩm thay vì xóa.";
        header("Location: products.php?error=" . urlencode($error));
        exit;
    }

    // 2. Nếu chưa ai mua, tiến hành lấy thông tin ảnh để xóa file
    $stmt = $conn->prepare("SELECT image, gallery_images FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($product) {
        // Xóa ảnh đại diện trên server
        if (!empty($product['image'])) {
            // Database lưu dạng 'uploads/products/...', cần thêm ../ để trỏ từ thư mục admin ra ngoài
            $mainImgPath = __DIR__ . '/../' . $product['image'];
            if (file_exists($mainImgPath)) unlink($mainImgPath);
        }

        // Xóa ảnh gallery trên server (Database lưu JSON)
        if (!empty($product['gallery_images'])) {
            $gallery = json_decode($product['gallery_images'], true);
            if (is_array($gallery)) {
                foreach ($gallery as $img) {
                    $galleryPath = __DIR__ . '/../' . $img;
                    if (file_exists($galleryPath)) unlink($galleryPath);
                }
            }
        }

        // 3. Xóa review liên quan (nếu có) để sạch data
        // Database có bảng `product_reviews`
        $conn->query("DELETE FROM product_reviews WHERE product_id = $id");

        // 4. Xóa record trong bảng products
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            header("Location: products.php?msg=deleted");
            exit;
        } else {
            // Phòng trường hợp lỗi database khác
            $error = "Lỗi Database: " . $conn->error;
            header("Location: products.php?error=" . urlencode($error));
            exit;
        }
    } else {
        header("Location: products.php?error=Không tìm thấy sản phẩm");
        exit;
    }
} else {
    header("Location: products.php?error=ID không hợp lệ");
    exit;
}