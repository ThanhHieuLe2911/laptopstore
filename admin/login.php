<?php
session_start();
require_once "../includes/config.php"; // $conn

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = "Vui lòng nhập đầy đủ email và mật khẩu.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user   = $result->fetch_assoc();

        if ($user && password_verify($password, $user['password'])) {
            if ($user['role'] !== 'admin') {
                $error = "Tài khoản này không có quyền quản trị.";
            } else {
                $_SESSION['admin_id']   = $user['id'];
                $_SESSION['admin_name'] = $user['full_name'];
                header("Location: index.php");
                exit;
            }
        } else {
            $error = "Sai email hoặc mật khẩu.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập quản trị - LaptopStore</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="admin-body">
<div class="admin-auth-wrapper">
    <div class="admin-auth-card">
        <!-- Header trên cùng -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
            <div>
                <div class="admin-auth-logo">LaptopStore</div>
                <p class="admin-auth-sub">Khu vực quản trị hệ thống bán hàng</p>
            </div>
            <a href="../index.php"
               style="font-size:11px;color:var(--text-gray);text-decoration:none;">
                <i class="fas fa-arrow-left"></i> Trang khách
            </a>
        </div>

        <h1 class="admin-auth-title">Đăng nhập quản trị</h1>
        <p style="font-size:12px;color:var(--text-gray);margin-bottom:14px;">
            Chỉ dành cho tài khoản được phân quyền <strong>admin</strong>.
        </p>

        <?php if ($error): ?>
            <div class="admin-auth-error">
                <i class="fas fa-circle-exclamation"></i>
                <span><?= htmlspecialchars($error) ?></span>
            </div>
        <?php endif; ?>

        <form method="post" autocomplete="off">
            <div class="form-group">
                <label for="email">Email đăng nhập</label>
                <input type="email"
                       id="email"
                       name="email"
                       placeholder="email.côngviệc@laptopstore.vn"
                       required>
            </div>

            <div class="form-group">
                <label for="password">Mật khẩu</label>
                <input type="password"
                       id="password"
                       name="password"
                       placeholder="Nhập mật khẩu"
                       required>
            </div>

            <button type="submit"
                    class="btn btn-block btn-large admin-login-btn"
                    style="margin-top:6px;">
                <i class="fas fa-lock"></i>&nbsp; Đăng nhập
            </button>
        </form>

        <div class="admin-auth-footer">
            LaptopStore Admin • Khu vực nội bộ cho bộ phận quản trị hệ thống.
        </div>
    </div>
</div>
</body>
</html>
