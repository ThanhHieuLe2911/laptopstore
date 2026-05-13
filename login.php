<?php
require_once 'includes/config.php';
require_once 'includes/send_mail.php'; 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';
$viewMode = 'login'; 

// --- 1. XỬ LÝ ĐĂNG KÝ (TẠO OTP & GỬI MAIL) ---
if (isset($_POST['register'])) {
    // ... (Code lấy dữ liệu input giữ nguyên) ...
    $name = $conn->real_escape_string($_POST['name']);
    $email = $conn->real_escape_string($_POST['email']);
    $phone = $conn->real_escape_string($_POST['phone']);
    $address = $conn->real_escape_string($_POST['address']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    
    if ($check->num_rows > 0) {
        $error = "Email này đã được sử dụng!";
        $viewMode = 'register';
    } elseif ($password != $confirm_password) {
        $error = "Mật khẩu xác nhận không khớp!";
        $viewMode = 'register';
    } else {
        $otp = rand(100000, 999999);
        
        // GỌI HÀM VỚI TYPE = 'register'
        if (sendVerificationEmail($email, $otp, 'register')) {
            $_SESSION['temp_register_data'] = [
                'name' => $name, 'email' => $email, 'phone' => $phone,
                'address' => $address, 'password' => password_hash($password, PASSWORD_DEFAULT),
                'otp' => $otp
            ];
            $success = "Mã xác nhận đã gửi đến <b>$email</b>.";
            $viewMode = 'verify_register';
        } else {
            $error = "Không thể gửi email. Thử lại sau.";
            $viewMode = 'register';
        }
    }
}

// ... (Phần 2 Verify Register và Phần 3 Login giữ nguyên) ...
// --- 2. XỬ LÝ XÁC THỰC ĐĂNG KÝ ---
if (isset($_POST['verify_register_otp'])) {
    $input_otp = $_POST['otp_code'];
    if (isset($_SESSION['temp_register_data'])) {
        $data = $_SESSION['temp_register_data'];
        if ($input_otp == $data['otp']) {
            $sql = "INSERT INTO users (full_name, email, phone, address, password, role) 
                    VALUES ('{$data['name']}', '{$data['email']}', '{$data['phone']}', '{$data['address']}', '{$data['password']}', 'user')";
            if ($conn->query($sql)) {
                $success = "Đăng ký thành công! Hãy đăng nhập.";
                unset($_SESSION['temp_register_data']);
                $viewMode = 'login';
            } else {
                $error = "Lỗi hệ thống: " . $conn->error;
                $viewMode = 'verify_register';
            }
        } else {
            $error = "Mã OTP không đúng!";
            $viewMode = 'verify_register';
        }
    } else {
        $error = "Hết hạn phiên. Đăng ký lại.";
        $viewMode = 'register';
    }
}

// --- 3. XỬ LÝ ĐĂNG NHẬP ---
if (isset($_POST['login'])) {
    $email = $conn->real_escape_string($_POST['email']);
    $password = $_POST['password'];

    $result = $conn->query("SELECT * FROM users WHERE email = '$email'");
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: index.php");
            exit();
        } else {
            $error = "Sai mật khẩu!";
        }
    } else {
        $error = "Email không tồn tại!";
    }
}

// --- 4. XỬ LÝ QUÊN MẬT KHẨU: GỬI OTP ---
if (isset($_POST['send_reset_otp'])) {
    $email = $conn->real_escape_string($_POST['reset_email']);
    $check = $conn->query("SELECT id FROM users WHERE email = '$email'");
    
    if ($check->num_rows > 0) {
        $otp = rand(100000, 999999);
        
        // GỌI HÀM VỚI TYPE = 'forgot_password'
        if (sendVerificationEmail($email, $otp, 'forgot_password')) {
            $_SESSION['reset_data'] = ['email' => $email, 'otp' => $otp];
            $success = "Mã OTP khôi phục đã gửi đến <b>$email</b>.";
            $viewMode = 'forgot_otp';
        } else {
            $error = "Lỗi gửi mail. Vui lòng thử lại.";
            $viewMode = 'forgot_email';
        }
    } else {
        $error = "Email không tồn tại trong hệ thống!";
        $viewMode = 'forgot_email';
    }
}

// ... (Các phần còn lại giữ nguyên HTML và Script bên dưới) ...
// --- 5. XỬ LÝ QUÊN MẬT KHẨU: CHECK OTP ---
if (isset($_POST['verify_reset_otp'])) {
    $input_otp = $_POST['otp_code'];
    if (isset($_SESSION['reset_data']) && $input_otp == $_SESSION['reset_data']['otp']) {
        $viewMode = 'reset_pass'; // OTP đúng, chuyển sang nhập pass mới
        $success = "Xác thực thành công. Nhập mật khẩu mới.";
    } else {
        $error = "Mã OTP không đúng!";
        $viewMode = 'forgot_otp';
    }
}

// --- 6. XỬ LÝ QUÊN MẬT KHẨU: ĐỔI PASS MỚI ---
if (isset($_POST['save_new_pass'])) {
    $new_pass = $_POST['new_pass'];
    $confirm_pass = $_POST['confirm_new_pass'];
    
    if ($new_pass === $confirm_pass) {
        if (isset($_SESSION['reset_data'])) {
            $email = $_SESSION['reset_data']['email'];
            $hashed_password = password_hash($new_pass, PASSWORD_DEFAULT);
            
            $conn->query("UPDATE users SET password = '$hashed_password' WHERE email = '$email'");
            
            unset($_SESSION['reset_data']); // Xóa session tạm
            $success = "Đổi mật khẩu thành công! Vui lòng đăng nhập.";
            $viewMode = 'login';
        } else {
            $error = "Phiên làm việc hết hạn.";
            $viewMode = 'forgot_email';
        }
    } else {
        $error = "Mật khẩu xác nhận không khớp!";
        $viewMode = 'reset_pass';
    }
}

$page_title = 'Đăng nhập / Đăng ký';
include 'includes/header.php';
?>

<style>
    .auth-section { padding: 60px 0; background: #f8f9fa; min-height: 80vh; display: flex; align-items: center; justify-content: center; }
    .auth-card { background: white; width: 100%; max-width: 500px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); overflow: hidden; }
    .auth-header { padding: 25px; text-align: center; background: linear-gradient(135deg, #0d6efd, #0a58ca); color: white; }
    .auth-header h2 { margin: 0; font-size: 22px; }
    .auth-body { padding: 30px; }
    .form-group { margin-bottom: 15px; }
    .form-group label { font-weight: 500; font-size: 14px; margin-bottom: 6px; display: block; }
    .form-control { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; }
    .form-control:focus { border-color: #0d6efd; outline: none; box-shadow: 0 0 0 2px rgba(13,110,253,0.1); }
    .btn-auth { width: 100%; padding: 12px; background: #0d6efd; color: white; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: 0.2s; margin-top: 10px; }
    .btn-auth:hover { background: #0b5ed7; }
    .auth-switch { text-align: center; margin-top: 20px; font-size: 14px; }
    .auth-switch a { color: #0d6efd; text-decoration: none; cursor: pointer; font-weight: 600; }
    .auth-link { float: right; font-size: 13px; color: #666; text-decoration: none; margin-bottom: 10px; cursor: pointer; }
    .auth-link:hover { color: #0d6efd; }
    .alert { padding: 12px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; text-align: center; }
    .alert-danger { background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .alert-success { background: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
    .hidden { display: none; }
</style>

<section class="auth-section">
    <div class="auth-card">
        <div class="auth-header">
            <h2 id="formTitle">
                <?php 
                    if($viewMode == 'login') echo 'Đăng Nhập';
                    elseif($viewMode == 'register') echo 'Đăng Ký';
                    elseif($viewMode == 'verify_register') echo 'Xác Thực Đăng Ký';
                    elseif($viewMode == 'forgot_email') echo 'Quên Mật Khẩu';
                    elseif($viewMode == 'forgot_otp') echo 'Nhập Mã OTP';
                    elseif($viewMode == 'reset_pass') echo 'Đặt Lại Mật Khẩu';
                ?>
            </h2>
        </div>
        
        <div class="auth-body">
            <?php if($error): ?><div class="alert alert-danger"><?php echo $error; ?></div><?php endif; ?>
            <?php if($success): ?><div class="alert alert-success"><?php echo $success; ?></div><?php endif; ?>

            <form id="loginForm" method="POST" class="<?php echo ($viewMode == 'login') ? '' : 'hidden'; ?>">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="example@email.com">
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu">
                </div>
                <div style="overflow: hidden; margin-bottom: 10px;">
                    <a onclick="switchForm('forgot_email')" class="auth-link">Quên mật khẩu?</a>
                </div>
                <button type="submit" name="login" class="btn btn-auth">Đăng Nhập</button>
                <div class="auth-switch">
                    Chưa có tài khoản? <a onclick="switchForm('register')">Đăng ký ngay</a>
                </div>
            </form>

            <form id="registerForm" method="POST" class="<?php echo ($viewMode == 'register') ? '' : 'hidden'; ?>">
                <div class="form-group">
                    <label>Họ và tên</label>
                    <input type="text" name="name" class="form-control" placeholder="Nguyễn Văn A">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" placeholder="example@email.com">
                </div>
                <div class="form-group">
                    <label>Số điện thoại</label>
                    <input type="tel" name="phone" class="form-control" placeholder="09xxxxxxxxx">
                </div>
                <div class="form-group">
                    <label>Địa chỉ</label>
                    <input type="text" name="address" class="form-control" placeholder="Số nhà, đường...">
                </div>
                <div class="form-group">
                    <label>Mật khẩu</label>
                    <input type="password" name="password" class="form-control" placeholder="Tối thiểu 6 ký tự">
                </div>
                <div class="form-group">
                    <label>Nhập lại mật khẩu</label>
                    <input type="password" name="confirm_password" class="form-control" placeholder="Xác nhận mật khẩu">
                </div>
                <button type="submit" name="register" class="btn btn-auth">Tiếp Tục</button>
                <div class="auth-switch">
                    Đã có tài khoản? <a onclick="switchForm('login')">Đăng nhập</a>
                </div>
            </form>

            <form id="verifyRegisterForm" method="POST" class="<?php echo ($viewMode == 'verify_register') ? '' : 'hidden'; ?>">
                <div class="form-group">
                    <label>Mã xác thực (OTP)</label>
                    <input type="text" name="otp_code" class="form-control" required style="letter-spacing: 5px; text-align: center; font-size: 18px; font-weight: bold;">
                </div>
                <button type="submit" name="verify_register_otp" class="btn btn-auth">Xác Nhận & Hoàn Tất</button>
                <div class="auth-switch"><a onclick="switchForm('register')">Quay lại đăng ký</a></div>
            </form>

            <form id="forgotEmailForm" method="POST" class="<?php echo ($viewMode == 'forgot_email') ? '' : 'hidden'; ?>">
                <p style="text-align: center; color: #666; font-size: 14px; margin-bottom: 20px;">
                    Nhập email đã đăng ký để nhận mã khôi phục mật khẩu.
                </p>
                <div class="form-group">
                    <label>Email đăng ký</label>
                    <input type="email" name="reset_email" class="form-control" required placeholder="example@email.com">
                </div>
                <button type="submit" name="send_reset_otp" class="btn btn-auth">Gửi Mã Xác Nhận</button>
                <div class="auth-switch"><a onclick="switchForm('login')">Quay lại đăng nhập</a></div>
            </form>

            <form id="forgotOtpForm" method="POST" class="<?php echo ($viewMode == 'forgot_otp') ? '' : 'hidden'; ?>">
                <div class="form-group">
                    <label>Mã OTP khôi phục</label>
                    <input type="text" name="otp_code" class="form-control" required style="letter-spacing: 5px; text-align: center; font-size: 18px; font-weight: bold;">
                </div>
                <button type="submit" name="verify_reset_otp" class="btn btn-auth">Xác Minh</button>
                <div class="auth-switch"><a onclick="switchForm('forgot_email')">Gửi lại mã?</a></div>
            </form>

            <form id="resetPassForm" method="POST" class="<?php echo ($viewMode == 'reset_pass') ? '' : 'hidden'; ?>">
                <div class="form-group">
                    <label>Mật khẩu mới</label>
                    <input type="password" name="new_pass" class="form-control" required placeholder="Nhập mật khẩu mới">
                </div>
                <div class="form-group">
                    <label>Xác nhận mật khẩu</label>
                    <input type="password" name="confirm_new_pass" class="form-control" required placeholder="Nhập lại mật khẩu">
                </div>
                <button type="submit" name="save_new_pass" class="btn btn-auth">Lưu Mật Khẩu Mới</button>
            </form>

        </div>
    </div>
</section>

<script>
    function switchForm(type) {
        document.querySelectorAll('form').forEach(f => f.classList.add('hidden'));
        document.querySelector('.alert')?.remove(); 

        const title = document.getElementById('formTitle');
        
        if (type === 'register') {
            document.getElementById('registerForm').classList.remove('hidden');
            title.innerText = 'Đăng Ký Tài Khoản';
        } else if (type === 'login') {
            document.getElementById('loginForm').classList.remove('hidden');
            title.innerText = 'Đăng Nhập';
        } else if (type === 'forgot_email') {
            document.getElementById('forgotEmailForm').classList.remove('hidden');
            title.innerText = 'Quên Mật Khẩu';
        }
    }
</script>

<?php include 'includes/footer.php'; ?>