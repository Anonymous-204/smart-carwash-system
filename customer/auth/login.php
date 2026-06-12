<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../db.php';

if (current_customer()) {
    header('Location: ' . BASE_URL . '/customer/index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ email và mật khẩu.';
    } else {
        $stmt = $conn->prepare(
            "SELECT id, email, hashed_password, name, phone, role, is_active
             FROM users WHERE email = ? AND role = 'customer' LIMIT 1"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['hashed_password'])) {
            customer_login_set($user);
            $redirect = $_GET['redirect'] ?? BASE_URL . '/customer/index.php';
            header('Location: ' . $redirect);
            exit;
        }
        $error = 'Email hoặc mật khẩu không đúng, hoặc tài khoản đã bị khóa.';
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập – Smart Carwash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #0f172a 0%, #1d4ed8 100%);
            display: flex; align-items: center; justify-content: center;
            font-family: Inter, Segoe UI, Arial, sans-serif;
        }
        .card     { width: 440px; border: 0; border-radius: 28px; box-shadow: 0 24px 80px rgba(0,0,0,.28); }
        .brand    { width: 62px; height: 62px; border-radius: 20px; background: #2563eb; color: #fff;
                    display: flex; align-items: center; justify-content: center; font-size: 28px; margin: auto; }
        .form-control { height: 50px; border-radius: 14px; }
        .btn      { height: 50px; border-radius: 14px; font-weight: 700; }
        .input-group .form-control  { border-radius: 0 14px 14px 0 !important; }
        .input-group-text           { border-radius: 14px 0 0 14px !important; background: #f8fafc; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-body p-5">
        <div class="brand mb-3"><i class="fa-solid fa-car-side"></i></div>
        <h3 class="text-center fw-bold mb-1">Chào mừng trở lại</h3>
        <p class="text-center text-muted mb-4">Đăng nhập để quản lý lịch rửa xe của bạn</p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2">
                <i class="fa-solid fa-circle-exclamation me-1"></i><?= e($error) ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['registered'])): ?>
            <div class="alert alert-success py-2">
                <i class="fa-solid fa-circle-check me-1"></i>Đăng ký thành công! Vui lòng đăng nhập.
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control"
                           placeholder="example@gmail.com"
                           value="<?= e($email) ?>" required>
                </div>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Mật khẩu</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="password" id="password"
                           class="form-control" placeholder="Nhập mật khẩu" required>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePass()"
                            style="border-radius:0 14px 14px 0 !important">
                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button class="btn btn-primary w-100 mb-3" type="submit">
                <i class="fa-solid fa-right-to-bracket me-2"></i>Đăng nhập
            </button>
        </form>

        <p class="text-center text-muted mb-0">
            Chưa có tài khoản?
            <a href="<?= BASE_URL ?>/customer/auth/register.php" class="fw-bold text-primary text-decoration-none">Đăng ký ngay</a>
        </p>
    </div>
</div>
<script>
function togglePass() {
    const input  = document.getElementById('password');
    const icon   = document.getElementById('eyeIcon');
    input.type   = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'text' ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
}
</script>
</body>
</html>