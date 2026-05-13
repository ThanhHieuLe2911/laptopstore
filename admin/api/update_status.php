<?php
// admin/api/update_status.php

// 1. Tắt hiển thị lỗi PHP ra màn hình (tránh làm hỏng JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// 2. Bắt đầu bộ nhớ đệm (Hứng tất cả text/warning rác nếu có)
ob_start();

header('Content-Type: application/json');

try {
    // Kiểm tra đường dẫn file trước khi require
    if (!file_exists("../../includes/config.php")) {
        throw new Exception("Lỗi đường dẫn: Không tìm thấy file ../../includes/config.php");
    }
    if (!file_exists("../_auth.php")) {
        throw new Exception("Lỗi đường dẫn: Không tìm thấy file ../_auth.php");
    }

    require_once "../../includes/config.php"; 
    require_once "../_auth.php";              

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Lấy dữ liệu
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        throw new Exception('Không nhận được dữ liệu JSON');
    }

    $action = $input['action'] ?? '';
    $orderId = isset($input['order_id']) ? (int)$input['order_id'] : 0;

    if ($orderId <= 0) {
        throw new Exception('Invalid Order ID');
    }

    $response = ['success' => false, 'message' => 'Unknown error'];

    // --- LOGIC XỬ LÝ ---
    if ($action === 'update_order_status') {
        $newStatus = $input['new_status'] ?? '';
        $validStatuses = ['pending', 'processing', 'shipping', 'completed', 'cancelled'];
        
        if (!in_array($newStatus, $validStatuses)) {
             throw new Exception('Trạng thái không hợp lệ');
        }

        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $newStatus, $orderId);
        
        if ($stmt->execute()) {
             $response = ['success' => true, 'message' => 'Đã cập nhật trạng thái đơn hàng!'];
        } else {
             throw new Exception('Lỗi SQL: ' . $stmt->error);
        }
        $stmt->close();

    } elseif ($action === 'update_payment_status') {
        $newPayStatus = $input['new_pay_status'] ?? '';
        $validPayStatuses = ['unpaid', 'paid'];

        if (!in_array($newPayStatus, $validPayStatuses)) {
            throw new Exception('Trạng thái thanh toán không hợp lệ');
        }

        $stmt = $conn->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
        $stmt->bind_param("si", $newPayStatus, $orderId);

        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Đã cập nhật thanh toán!'];
        } else {
            throw new Exception('Lỗi SQL: ' . $stmt->error);
        }
        $stmt->close();
    } else {
        throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    // Bắt lỗi và trả về JSON thay vì text chết trang
    $response = ['success' => false, 'message' => $e->getMessage()];
}

// 3. Xóa sạch bộ nhớ đệm (vứt bỏ mọi warning/text rác trước đó)
ob_end_clean(); 

// 4. Trả về JSON sạch
echo json_encode($response);
exit;
?>