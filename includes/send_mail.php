<?php
require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name = trim($name);
            $value = trim($value);
            if (!array_key_exists($name, $_ENV)) {
                putenv("$name=$value");
                $_ENV[$name] = $value;
            }
        }
    }
}

// Thêm tham số $type mặc định là 'register'
function sendVerificationEmail($recipientEmail, $code, $type = 'register') {
    $mail = new PHPMailer(true);

    try {
        // Cấu hình Server
        $mail->isSMTP();
        $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        
        // --- TÀI KHOẢN GỬI MAIL ---
        $mail->Username   = getenv('SMTP_USERNAME') ?: '';
        $mail->Password   = getenv('SMTP_PASSWORD') ?: ''; 
        
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // Người gửi
        $mail->setFrom('2224801030200@student.tdmu.edu.vn', 'LaptopStore');
        
        // Người nhận
        $mail->addAddress($recipientEmail);

        // Nội dung Email
        $mail->isHTML(true);

        if ($type === 'forgot_password') {
            // --- NỘI DUNG EMAIL QUÊN MẬT KHẨU ---
            $mail->Subject = 'Mã xác nhận khôi phục mật khẩu';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <h3 style='color: #d9534f;'>Yêu cầu đặt lại mật khẩu</h3>
                    <p>Hệ thống nhận được yêu cầu khôi phục mật khẩu cho tài khoản liên kết với email này.</p>
                    <p>Mã xác thực của bạn là:</p>
                    <p style='font-size: 24px; font-weight: bold; color: #4f46e5; letter-spacing: 5px; text-align: center; margin: 20px 0;'>
                        $code
                    </p>
                    <p>Nếu bạn không thực hiện yêu cầu này, vui lòng bỏ qua email và bảo mật tài khoản của mình.</p>
                    <hr style='border: none; border-top: 1px solid #eee;' />
                    <small style='color: #888;'>LaptopStore Support Team</small>
                </div>
            ";
        } else {
            // --- NỘI DUNG EMAIL ĐĂNG KÝ (Mặc định) ---
            $mail->Subject = 'Mã xác nhận đăng ký tài khoản';
            $mail->Body    = "
                <div style='font-family: Arial, sans-serif; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                    <h3 style='color: #4f46e5;'>Chào mừng bạn đến với LaptopStore!</h3>
                    <p>Cảm ơn bạn đã đăng ký tài khoản. Đây là mã xác thực của bạn:</p>
                    <p style='font-size: 24px; font-weight: bold; color: #d9534f; letter-spacing: 5px; text-align: center; margin: 20px 0;'>
                        $code
                    </p>
                    <p>Mã này có hiệu lực để kích hoạt tài khoản của bạn ngay lập tức.</p>
                    <hr style='border: none; border-top: 1px solid #eee;' />
                    <small style='color: #888;'>Nếu bạn không yêu cầu mã này, vui lòng bỏ qua email này.</small>
                </div>
            ";
        }

        $mail->send();
        return true;
    } catch (Exception $e) {
        return false; 
    }
}
?>