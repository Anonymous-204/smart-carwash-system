<?php
/**
 * reset-password.php
 * Đặt tại: /customer/auth/reset-password.php
 *
 * Nhận ?token=xxx từ email, cho phép đặt lại mật khẩu.
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../db.php';

if (current_customer()) {
    header('Location: ' . BASE_URL . '/customer/index.php');
    exit;
}

$token = trim($_POST['token'] ?? $_GET['token'] ?? '');
$error   = '';
$success = false;
$tokenRow = null;

// ---------------------------------------------------------------
// XÁC THỰC TOKEN
// ---------------------------------------------------------------
if ($token) {
    $now  = date('Y-m-d H:i:s');
    $stmt = $conn->prepare(
        "SELECT pr.*, u.id user_id, u.name user_name
         FROM password_resets pr
         JOIN users u ON u.email = pr.email AND u.role = 'customer' AND u.is_active = 1
         WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > ?
         LIMIT 1"
    );
    $stmt->bind_param('ss', $token, $now);
    $stmt->execute();
    $tokenRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ---------------------------------------------------------------
// XỬ LÝ ĐẶT LẠI MẬT KHẨU
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tokenRow) {
    $newPass     = $_POST['new_password']     ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (strlen($newPass) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự.';
    } elseif ($newPass !== $confirmPass) {
        $error = 'Mật khẩu xác nhận không khớp.';
    } else {
        $hashed = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);

        // Cập nhật mật khẩu
        $stmt = $conn->prepare("UPDATE users SET hashed_password = ? WHERE id = ?");
        $stmt->bind_param('si', $hashed, $tokenRow['user_id']);
        $stmt->execute();
        $stmt->close();

        // Đánh dấu token đã dùng
        $stmt = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();

        // Xóa hết session cũ (bảo mật – đăng xuất mọi thiết bị)
        $stmt = $conn->prepare(
            "DELETE FROM sessions WHERE user_id = ?"
        );
        $stmt->bind_param('i', $tokenRow['user_id']);
        $stmt->execute();
        $stmt->close();

        $success = true;
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Đặt lại mật khẩu – Smart Carwash</title>
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

        /* Thanh sức mạnh mật khẩu */
        .strength-bar   { height: 5px; border-radius: 3px; background: #e2e8f0; overflow: hidden; margin-top: 8px; }
        .strength-fill  { height: 100%; border-radius: 3px; width: 0; transition: width .3s, background .3s; }
    </style>
</head>
<body>
<div class="card">
    <div class="card-body p-5">

        <div class="brand mb-3">
            <?php if ($success): ?>
                <i class="fa-solid fa-circle-check"></i>
            <?php elseif (!$tokenRow): ?>
                <i class="fa-solid fa-triangle-exclamation"></i>
            <?php else: ?>
                <i class="fa-solid fa-lock-open"></i>
            <?php endif; ?>
        </div>

        <?php if ($success): ?>
        <!-- ====== THÀNH CÔNG ====== -->
        <h3 class="text-center fw-bold mb-1">Đặt lại thành công!</h3>
        <p class="text-center text-muted mb-4">
            Mật khẩu của bạn đã được cập nhật.<br>Hãy đăng nhập với mật khẩu mới.
        </p>
        <div class="text-center mb-4" style="font-size:3rem;">🎉</div>
        <a href="login.php" class="btn btn-primary w-100">
            <i class="fa-solid fa-right-to-bracket me-2"></i>Đăng nhập ngay
        </a>

        <?php elseif (!$tokenRow): ?>
        <!-- ====== TOKEN KHÔNG HỢP LỆ / HẾT HẠN ====== -->
        <h3 class="text-center fw-bold mb-1">Link không hợp lệ</h3>
        <p class="text-center text-muted mb-4">
            Link đặt lại mật khẩu đã <strong>hết hạn</strong> hoặc đã được sử dụng.<br>
            Vui lòng yêu cầu link mới.
        </p>
        <div class="text-center mb-4" style="font-size:3rem;">⏰</div>
        <a href="forgot-password.php" class="btn btn-primary w-100 mb-3">
            <i class="fa-solid fa-paper-plane me-2"></i>Gửi lại link
        </a>
        <p class="text-center text-muted mb-0 small">
            <a href="login.php" class="text-decoration-none text-primary fw-semibold">
                <i class="fa-solid fa-arrow-left me-1"></i>Quay lại đăng nhập
            </a>
        </p>

        <?php else: ?>
        <!-- ====== FORM ĐẶT LẠI MẬT KHẨU ====== -->
        <h3 class="text-center fw-bold mb-1">Tạo mật khẩu mới</h3>
        <p class="text-center text-muted mb-4">
            Đang đặt lại cho <strong><?= e($tokenRow['email']) ?></strong>
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger py-2">
                <i class="fa-solid fa-circle-exclamation me-1"></i><?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate id="resetForm">
            <input type="hidden" name="token" value="<?= e($token) ?>">

            <!-- Mật khẩu mới -->
            <div class="mb-3">
                <label class="form-label fw-semibold">Mật khẩu mới <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock text-muted"></i></span>
                    <input type="password" name="new_password" id="newPassword"
                           class="form-control" placeholder="Tối thiểu 6 ký tự" required autofocus>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePass('newPassword','eyeNew')"
                            style="border-radius:0 14px 14px 0 !important">
                        <i class="fa-solid fa-eye" id="eyeNew"></i>
                    </button>
                </div>
                <!-- Thanh sức mạnh -->
                <div class="strength-bar mt-2">
                    <div class="strength-fill" id="strengthFill"></div>
                </div>
                <div class="small mt-1" id="strengthLabel" style="min-height:1.2em;"></div>
            </div>

            <!-- Xác nhận mật khẩu -->
            <div class="mb-4">
                <label class="form-label fw-semibold">Xác nhận mật khẩu <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock-open text-muted"></i></span>
                    <input type="password" name="confirm_password" id="confirmPassword"
                           class="form-control" placeholder="Nhập lại mật khẩu mới" required>
                    <button type="button" class="btn btn-outline-secondary" onclick="togglePass('confirmPassword','eyeConfirm')"
                            style="border-radius:0 14px 14px 0 !important">
                        <i class="fa-solid fa-eye" id="eyeConfirm"></i>
                    </button>
                </div>
                <div class="small mt-1 text-danger" id="matchMsg" style="min-height:1.2em;"></div>
            </div>

            <button class="btn btn-primary w-100 mb-3" type="submit" id="submitBtn">
                <i class="fa-solid fa-floppy-disk me-2"></i>Lưu mật khẩu mới
            </button>
        </form>

        <p class="text-center text-muted mb-0 small">
            <a href="login.php" class="text-decoration-none text-primary fw-semibold">
                <i class="fa-solid fa-arrow-left me-1"></i>Quay lại đăng nhập
            </a>
        </p>
        <?php endif; ?>

    </div>
</div>

<script>
function togglePass(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon  = document.getElementById(iconId);
    input.type  = input.type === 'password' ? 'text' : 'password';
    icon.className = input.type === 'text' ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
}

// Đánh giá sức mạnh mật khẩu
const newPass   = document.getElementById('newPassword');
const confirmEl = document.getElementById('confirmPassword');
const fill      = document.getElementById('strengthFill');
const label     = document.getElementById('strengthLabel');
const matchMsg  = document.getElementById('matchMsg');

if (newPass) {
    newPass.addEventListener('input', function () {
        const val   = this.value;
        let score   = 0;
        if (val.length >= 6)  score++;
        if (val.length >= 10) score++;
        if (/[A-Z]/.test(val)) score++;
        if (/[0-9]/.test(val)) score++;
        if (/[^A-Za-z0-9]/.test(val)) score++;

        const levels = [
            { pct: '20%', color: '#ef4444', text: 'Rất yếu' },
            { pct: '40%', color: '#f97316', text: 'Yếu' },
            { pct: '60%', color: '#eab308', text: 'Trung bình' },
            { pct: '80%', color: '#22c55e', text: 'Mạnh' },
            { pct: '100%',color: '#16a34a', text: 'Rất mạnh 💪' },
        ];
        const lv = val.length === 0 ? null : levels[Math.min(score - 1, 4)];
        if (lv) {
            fill.style.width      = lv.pct;
            fill.style.background = lv.color;
            label.style.color     = lv.color;
            label.textContent     = lv.text;
        } else {
            fill.style.width  = '0';
            label.textContent = '';
        }
        checkMatch();
    });
}

if (confirmEl) {
    confirmEl.addEventListener('input', checkMatch);
}

function checkMatch() {
    if (!confirmEl || !confirmEl.value) { matchMsg.textContent = ''; return; }
    if (newPass.value === confirmEl.value) {
        matchMsg.style.color   = '#16a34a';
        matchMsg.textContent   = '✓ Mật khẩu khớp';
    } else {
        matchMsg.style.color   = '#ef4444';
        matchMsg.textContent   = '✗ Mật khẩu chưa khớp';
    }
}
</script>
</body>
</html>