<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../db.php';

if (current_customer()) {
    header('Location: ' . BASE_URL . '/customer/index.php');
    exit;
}

$errors = [];
$email  = '';
$phone  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $phone    = trim($_POST['phone']    ?? '');
    $password = trim($_POST['password'] ?? '');

    // Validate
    if ($email === '') {
        $errors[] = 'Vui lòng nhập email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Email không hợp lệ.';
    }

    if ($phone === '') {
        $errors[] = 'Vui lòng nhập số điện thoại.';
    } elseif (!preg_match('/^(0|\+84)[0-9]{8,10}$/', $phone)) {
        $errors[] = 'Số điện thoại không hợp lệ (VD: 0912345678).';
    }

    if ($password === '') {
        $errors[] = 'Vui lòng nhập mật khẩu.';
    } elseif (mb_strlen($password) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    }

    // Kiểm tra email đã tồn tại
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Email này đã được đăng ký. Vui lòng dùng email khác.';
        }
        $stmt->close();
    }

    // Tạo tài khoản
    if (empty($errors)) {
        $defaultRankId   = 1;
        $name            = $phone; // dùng phone làm name tạm theo yêu cầu
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);

        $stmt = $conn->prepare(
            "INSERT INTO users (email, hashed_password, name, phone, role, rank_id, point, is_active)
             VALUES (?, ?, ?, ?, 'customer', ?, 0, 1)"
        );
        $stmt->bind_param('ssssi', $email, $hashed_password, $name, $phone, $defaultRankId);

        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            $stmt->close();

            $user = [
                'id'    => $newId,
                'name'  => $name,
                'email' => $email,
                'phone' => $phone,
                'role'  => 'customer',
            ];
            customer_login_set($user);
            header('Location: ' . BASE_URL . '/customer/index.php?welcome=1');
            exit;
        } else {
            $errors[] = 'Đăng ký thất bại. Vui lòng thử lại.';
            $stmt->close();
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng ký – Smart Carwash</title>
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
        .input-group .form-control { border-radius: 0 14px 14px 0 !important; }
        .input-group-text { border-radius: 14px 0 0 14px !important; background: #f8fafc; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-body p-5">
        <div class="brand mb-3"><i class="fa-solid fa-car-side"></i></div>
        <h3 class="text-center fw-bold mb-1">Tạo tài khoản</h3>
        <p class="text-center text-muted mb-4">Đăng ký để đặt lịch rửa xe dễ dàng</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger py-2 mb-3">
                <?php foreach ($errors as $err): ?>
                    <div><i class="fa-solid fa-circle-exclamation me-1"></i><?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-envelope text-muted"></i></span>
                    <input type="email" name="email" class="form-control"
                           placeholder="example@gmail.com"
                           value="<?= e($email) ?>" required>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-semibold">Số điện thoại <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-phone text-muted"></i></span>
                    <input type="tel" name="phone" class="form-control"
                           placeholder="0912345678"
                           value="<?= e($phone) ?>" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-semibold">Mật khẩu <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="password" id="password" class="form-control"
                           placeholder="Ít nhất 6 ký tự" required>
                    <button type="button" class="btn btn-outline-secondary"
                            onclick="togglePass()"
                            style="border-radius:0 14px 14px 0 !important">
                        <i class="fa-solid fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
            </div>

            <button class="btn btn-primary w-100 mb-3" type="submit">
                <i class="fa-solid fa-user-plus me-2"></i>Đăng ký
            </button>
        </form>

        <p class="text-center text-muted mb-0">
            Đã có tài khoản?
            <a href="<?= BASE_URL ?>/customer/auth/login.php" class="fw-bold text-primary text-decoration-none">Đăng nhập</a>
        </p>
    </div>
</div>
<script>
function togglePass() {
    const input   = document.getElementById('password');
    const icon    = document.getElementById('eyeIcon');
    const isPass  = input.type === 'password';
    input.type    = isPass ? 'text' : 'password';
    icon.className = isPass ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
}
</script>
</body>
</html>