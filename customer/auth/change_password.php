<?php
require_once __DIR__ . '/../../db.php';
require_customer_login();
$customer = current_customer();

$success = '';
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old     = trim($_POST['old_password']     ?? '');
    $new     = trim($_POST['new_password']     ?? '');
    $confirm = trim($_POST['confirm_password'] ?? '');

    if ($old === '') $errors[] = 'Vui lòng nhập mật khẩu hiện tại.';
    if ($new === '') $errors[] = 'Vui lòng nhập mật khẩu mới.';
    elseif (mb_strlen($new) < 6) $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự.';
    if ($new !== $confirm) $errors[] = 'Xác nhận mật khẩu không khớp.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT hashed_password FROM users WHERE id = ? LIMIT 1");
        $stmt->bind_param('i', $customer['id']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$row || !password_verify($old, $row['hashed_password'])) {
            $errors[] = 'Mật khẩu hiện tại không đúng.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("UPDATE users SET hashed_password = ? WHERE id = ?");
            $stmt->bind_param('si', $hash, $customer['id']);
            $stmt->execute();
            $stmt->close();
            $success = 'Đổi mật khẩu thành công!';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<main>
<div class="container py-5" style="max-width:520px">
    <div class="card cardx p-4 p-md-5">
        <h4 class="fw-bold mb-4"><i class="fa-solid fa-lock text-primary me-2"></i>Đổi mật khẩu</h4>

        <?php if ($success): ?>
            <div class="alert alert-success"><i class="fa-solid fa-circle-check me-1"></i><?= e($success) ?></div>
        <?php endif; ?>
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $err): ?>
                    <div><i class="fa-solid fa-circle-exclamation me-1"></i><?= e($err) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <div class="mb-3">
                <label class="form-label fw-semibold">Mật khẩu hiện tại</label>
                <input type="password" name="old_password" class="form-control" placeholder="••••••" required>
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold">Mật khẩu mới</label>
                <input type="password" name="new_password" class="form-control" placeholder="Ít nhất 6 ký tự" required>
            </div>
            <div class="mb-4">
                <label class="form-label fw-semibold">Xác nhận mật khẩu mới</label>
                <input type="password" name="confirm_password" class="form-control" placeholder="Nhập lại" required>
            </div>
            <button class="btn btn-primary w-100">
                <i class="fa-solid fa-floppy-disk me-2"></i>Lưu mật khẩu mới
            </button>
        </form>

        <div class="mt-3 text-center">
            <a href="<?= BASE_URL ?>/customer/index.php" class="text-muted small">
                <i class="fa-solid fa-arrow-left me-1"></i>Quay lại trang chủ
            </a>
        </div>
    </div>
</div>
</main>
<?php include __DIR__ . '/../includes/footer.php'; ?>