<?php
session_start();
include __DIR__ . '/../db.php';

if (isset($_SESSION['admin'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $error = 'Vui lòng nhập email và mật khẩu.';
    } else {
        $stmt = $conn->prepare("SELECT id, email, hashed_password, name, role, is_active FROM users WHERE email = ? AND role IN ('admin','staff') LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user && (int)$user['is_active'] === 1 && password_verify($password, $user['hashed_password'])) {
            $_SESSION['admin'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'name' => $user['name'],
                'role' => $user['role']
            ];
            header('Location: index.php');
            exit;
        }
        $error = 'Email hoặc mật khẩu không đúng, hoặc tài khoản không có quyền quản trị.';
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đăng nhập quản trị</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        body{min-height:100vh;background:linear-gradient(135deg,#0f172a,#1d4ed8);display:flex;align-items:center;justify-content:center;font-family:Inter,Segoe UI,Arial,sans-serif}
        .login-card{width:430px;border:0;border-radius:28px;box-shadow:0 24px 80px rgba(0,0,0,.25);overflow:hidden}
        .brand{width:60px;height:60px;border-radius:20px;background:#2563eb;color:#fff;display:flex;align-items:center;justify-content:center;font-size:26px;margin:auto}
        .form-control{height:50px;border-radius:15px}.btn{height:50px;border-radius:15px;font-weight:700}
    </style>
</head>
<body>
<div class="card login-card">
    <div class="card-body p-5">
        <div class="brand mb-3"><i class="fa-solid fa-car-side"></i></div>
        <h3 class="text-center fw-bold mb-1">Smart Carwash</h3>
        <p class="text-center text-muted mb-4">Đăng nhập hệ thống quản trị</p>
        <?php if ($error): ?><div class="alert alert-danger py-2"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
            <div class="mb-3">
                <label class="form-label fw-semibold">Email</label>
                <input type="email" name="email" class="form-control" value="<?= e($email) ?>" placeholder="admin@gmail.com" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Mật khẩu</label>
                <input type="password" name="password" class="form-control" placeholder="Nhập mật khẩu" required>
            </div>
            <button class="btn btn-primary w-100" type="submit"><i class="fa-solid fa-right-to-bracket me-2"></i>Đăng nhập</button>
        </form>
    </div>
</div>
</body>
</html>
