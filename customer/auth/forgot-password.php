<?php
/**
 * forgot-password.php
 * Đặt tại: /customer/auth/forgot-password.php
 *
 * Yêu cầu: PHPMailer (cài qua Composer: composer require phpmailer/phpmailer)
 * Cấu hình SMTP Gmail ở mục SMTP CONFIG bên dưới.
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../db.php';

// Chuyển hướng nếu đã đăng nhập
if (current_customer()) {
    header('Location: ' . BASE_URL . '/customer/index.php');
    exit;
}

// ---------------------------------------------------------------
// SMTP CONFIG – thay bằng thông tin Gmail thực của bạn
// Lưu ý: dùng App Password (https://myaccount.google.com/apppasswords)
// không dùng mật khẩu Gmail thường vì Google chặn SMTP thường.
// ---------------------------------------------------------------
define('SMTP_HOST',     'smtp.gmail.com');
define('SMTP_PORT',     587);
define('SMTP_USER',     'testerduong04@gmail.com');   // <-- đổi
define('SMTP_PASS',     'pokivncpkeetiryj');       // <-- đổi (App Password 16 ký tự)
define('SMTP_FROM',     'testerduong04@gmail.com');   // <-- đổi
define('SMTP_FROM_NAME','Smart Carwash');
define('RESET_EXPIRE_MINUTES', 15);

// ---------------------------------------------------------------
// Autoload PHPMailer (Composer)
// ---------------------------------------------------------------
$mailerAvailable = file_exists(__DIR__ . '/../../vendor/autoload.php');
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as MailException;

require_once __DIR__ . '/../../PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../PHPMailer/src/SMTP.php';

$mailerAvailable = true;

$success = false;
$error   = '';
$email   = '';

// ---------------------------------------------------------------
// XỬ LÝ FORM
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = 'Vui lòng nhập địa chỉ email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Địa chỉ email không hợp lệ.';
    } else {
        // Kiểm tra email tồn tại (role = customer, is_active = 1)
        $stmt = $conn->prepare(
            "SELECT id, name FROM users WHERE email = ? AND role = 'customer' AND is_active = 1 LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        // Luôn hiện thông báo thành công dù email có tồn tại hay không
        // (bảo vệ: không để lộ email nào đã đăng ký)
        if ($user) {
            // Xóa token cũ của email này (chưa dùng)
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->close();

            // Tạo token ngẫu nhiên 32 bytes → 64 hex chars
            $token     = bin2hex(random_bytes(32));
            $expiresAt = date('Y-m-d H:i:s', time() + RESET_EXPIRE_MINUTES * 60);

            $stmt = $conn->prepare(
                "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)"
            );
            $stmt->bind_param('sss', $email, $token, $expiresAt);
            $stmt->execute();
            $stmt->close();

            // Tạo link reset
            $resetLink = rtrim(BASE_URL, '/')
                       . '/customer/auth/reset-password.php?token=' . urlencode($token);

            // Gửi email
            $mailSent = false;
            if ($mailerAvailable) {
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host       = SMTP_HOST;
                    $mail->SMTPAuth   = true;
                    $mail->Username   = SMTP_USER;
                    $mail->Password   = SMTP_PASS;
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port       = SMTP_PORT;
                    $mail->CharSet    = 'UTF-8';

                    $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
                    $mail->addAddress($email, $user['name']);
                    $mail->isHTML(true);
                    $mail->Subject = '[Smart Carwash] Đặt lại mật khẩu của bạn';
                    $mail->Body    = build_reset_email($user['name'], $resetLink);
                    $mail->AltBody = "Xin chào {$user['name']},\n\nNhấn vào link sau để đặt lại mật khẩu (hết hạn sau " . RESET_EXPIRE_MINUTES . " phút):\n{$resetLink}\n\nNếu bạn không yêu cầu, hãy bỏ qua email này.";

                    $mail->send();
                    $mailSent = true;
                } catch (MailException $e) {
                    // Ghi log lỗi nội bộ, không hiện cho user
                    error_log('[SmartCarwash] Mailer error: ' . $e->getMessage());
                }
            } else {
                // PHPMailer chưa cài – dùng mail() fallback (chỉ cho dev/localhost)
                $headers  = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
                $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $body     = "Xin chào {$user['name']},\n\nĐặt lại mật khẩu:\n{$resetLink}\n\nLink hết hạn sau " . RESET_EXPIRE_MINUTES . " phút.";
                $mailSent = mail($email, '[Smart Carwash] Đặt lại mật khẩu', $body, $headers);
            }

            // Dù gửi mail thành công hay không, vẫn báo thành công (bảo mật)
            $success = true;
        } else {
            // Email không tồn tại – vẫn báo thành công (không lộ thông tin)
            $success = true;
        }
    }
}

// ---------------------------------------------------------------
// HTML EMAIL TEMPLATE
// ---------------------------------------------------------------
function build_reset_email(string $name, string $link): string {
    $expire = RESET_EXPIRE_MINUTES;
    return <<<HTML
    <div style="font-family:Inter,Arial,sans-serif;max-width:520px;margin:auto;background:#fff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.08);">
        <div style="background:#2563eb;padding:32px 40px;text-align:center;">
            <div style="width:56px;height:56px;background:#fff3;border-radius:16px;display:inline-flex;align-items:center;justify-content:center;margin-bottom:12px;">
                <span style="font-size:28px;">🚗</span>
            </div>
            <h2 style="color:#fff;margin:0;font-size:22px;">Smart Carwash</h2>
        </div>
        <div style="padding:36px 40px;">
            <h3 style="margin:0 0 8px;color:#0f172a;">Xin chào, {$name}!</h3>
            <p style="color:#475569;margin:0 0 24px;line-height:1.6;">
                Chúng tôi nhận được yêu cầu đặt lại mật khẩu cho tài khoản của bạn.<br>
                Nhấn nút bên dưới để tạo mật khẩu mới. Link này sẽ hết hạn sau <strong>{$expire} phút</strong>.
            </p>
            <div style="text-align:center;margin-bottom:28px;">
                <a href="{$link}"
                   style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;
                          padding:14px 36px;border-radius:12px;font-weight:700;font-size:16px;">
                    Đặt lại mật khẩu
                </a>
            </div>
            <p style="color:#94a3b8;font-size:13px;margin:0;line-height:1.5;">
                Nếu bạn không yêu cầu đặt lại mật khẩu, hãy bỏ qua email này — tài khoản của bạn vẫn an toàn.<br><br>
                Hoặc copy link: <a href="{$link}" style="color:#2563eb;word-break:break-all;">{$link}</a>
            </p>
        </div>
        <div style="background:#f8fafc;padding:16px 40px;text-align:center;">
            <p style="color:#94a3b8;font-size:12px;margin:0;">© Smart Carwash – Hệ thống rửa xe thông minh</p>
        </div>
    </div>
HTML;
}

?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quên mật khẩu – Smart Carwash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
            display: flex; align-items: center; justify-content: center;
            font-family: Inter, Segoe UI, Arial, sans-serif;
        }
        .card           { width: 440px; border: 0; border-radius: 28px; box-shadow: 0 24px 80px rgba(0,0,0,.28); }
        .brand          { width: 62px; height: 62px; border-radius: 20px; background: #2563eb; color: #fff;
                          display: flex; align-items: center; justify-content: center; font-size: 28px; margin: auto; }
        .form-control   { height: 50px; border-radius: 14px; }
        .btn            { height: 50px; border-radius: 14px; font-weight: 700; }
        .input-group .form-control  { border-radius: 0 14px 14px 0 !important; }
        .input-group-text           { border-radius: 14px 0 0 14px !important; background: #f8fafc; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-body p-5">

        <div class="brand mb-3"><i class="fa-solid fa-key"></i></div>

        <?php if ($success): ?>
            <!-- Trạng thái đã gửi email -->
            <h3 class="text-center fw-bold mb-1">Kiểm tra hộp thư!</h3>
            <p class="text-center text-muted mb-4">
                Nếu email <strong><?= e($email) ?></strong> tồn tại trong hệ thống,<br>
                chúng tôi đã gửi link đặt lại mật khẩu.<br>
                <span class="small">Link hết hạn sau <strong><?= RESET_EXPIRE_MINUTES ?> phút</strong>.</span>
            </p>
            <div class="text-center mb-4">
                <div style="font-size:3rem;">📬</div>
            </div>
            <div class="alert alert-info py-2 small text-center mb-4">
                <i class="fa-solid fa-circle-info me-1"></i>
                Không thấy email? Kiểm tra thư mục <strong>Spam / Thư rác</strong>.
            </div>
            <a href="login.php" class="btn btn-primary w-100 mb-3">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Quay lại đăng nhập
            </a>

        <?php else: ?>
            <!-- Form nhập email -->
            <h3 class="text-center fw-bold mb-1">Quên mật khẩu?</h3>
            <p class="text-center text-muted mb-4">
                Nhập email đăng ký của bạn.<br>Chúng tôi sẽ gửi link đặt lại mật khẩu qua Gmail.
            </p>

            <?php if ($error): ?>
                <div class="alert alert-danger py-2">
                    <i class="fa-solid fa-circle-exclamation me-1"></i><?= e($error) ?>
                </div>
            <?php endif; ?>

            <form method="post" novalidate>
                <div class="mb-4">
                    <label class="form-label fw-semibold">Email đăng ký</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fa-solid fa-envelope text-muted"></i></span>
                        <input type="email" name="email" class="form-control"
                               placeholder="example@gmail.com"
                               value="<?= e($email) ?>" required autofocus>
                    </div>
                </div>

                <button class="btn btn-primary w-100 mb-3" type="submit">
                    <i class="fa-solid fa-paper-plane me-2"></i>Gửi link đặt lại mật khẩu
                </button>
            </form>

            <p class="text-center text-muted mb-0">
                <a href="login.php" class="text-decoration-none text-primary fw-semibold">
                    <i class="fa-solid fa-arrow-left me-1"></i>Quay lại đăng nhập
                </a>
            </p>
        <?php endif; ?>

    </div>
</div>
</body>
</html>