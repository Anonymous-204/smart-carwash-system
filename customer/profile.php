<?php
// 1. Khởi động session để nhận diện trạng thái đăng nhập
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gọi file kết nối CSDL (Lùi 1 tầng từ thư mục customer/ ra thư mục gốc ruaxe/)
require_once __DIR__ . '/../db.php'; 

// 2. Sử dụng hàm hệ thống để kiểm tra thông tin khách hàng đang đăng nhập
$current_user = current_customer(); 

if (!$current_user) {
    // Nếu chưa đăng nhập hoặc bị mất session, chuyển hướng về trang login
    header('Location: ' . BASE_URL . '/customer/auth/login.php');
    exit;
}

$customer_id = $current_user['id']; 
$message = "";

// 3. XỬ LÝ KHI KHÁCH HÀNG NHẤN NÚT CẬP NHẬT (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = mysqli_real_escape_string($conn, trim($_POST['name'] ?? ''));
    $email   = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
    $phone   = mysqli_real_escape_string($conn, trim($_POST['phone'] ?? ''));
    $address = mysqli_real_escape_string($conn, trim($_POST['address'] ?? ''));

    if ($name === '' || $email === '') {
        $message = "<div class='alert alert-danger'>Vui lòng nhập đầy đủ Họ tên và Email.</div>";
    } else {
        // Cập nhật thông tin vào bảng users
        $update_query = "UPDATE users SET 
                            name = '$name', 
                            email = '$email', 
                            phone = '$phone', 
                            address = '$address' 
                         WHERE id = '$customer_id' AND role = 'customer'";

        if (mysqli_query($conn, $update_query)) {
            $message = "<div class='alert alert-success'>Cập nhật thông tin thành công!</div>";
            
            // Đồng bộ dữ liệu mới cập nhật vào lại Session hệ thống
            $current_user['name'] = $name;
            $current_user['email'] = $email;
            $current_user['phone'] = $phone;
            $current_user['address'] = $address;
            customer_login_set($current_user); 
        } else {
            $message = "<div class='alert alert-danger'>Lỗi cập nhật CSDL: " . mysqli_error($conn) . "</div>";
        }
    }
}

// 4. TRUY VẤN DỮ LIỆU ĐỂ HIỂN THỊ LÊN FORM (Liên kết bảng ranks để lấy tên hạng thành viên)
$query = "SELECT u.*, r.name AS rank_name 
          FROM users u 
          LEFT JOIN ranks r ON u.rank_id = r.id 
          WHERE u.id = '$customer_id' AND u.role = 'customer' LIMIT 1";

$result = mysqli_query($conn, $query);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    echo "Không tìm thấy thông tin tài khoản người dùng hợp lệ.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân – Smart Carwash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0 rounded-4">
                <div class="card-header bg-primary text-white py-3 rounded-top-4">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-user-gear me-2"></i>Thông Tin Tài Khoản Khách Hàng</h5>
                </div>
                <div class="card-body p-4">
                    
                    <?= $message ?>

                    <form method="POST" novalidate>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Họ và tên</label>
                            <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Số điện thoại</label>
                            <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Địa chỉ</label>
                            <textarea name="address" class="form-control" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                        </div>

                        <hr class="my-4">

                        <div class="row bg-light p-3 rounded-3 mb-4 g-3">
                            <div class="col-md-4 text-center border-end">
                                <p class="mb-1 text-muted small fw-medium text-uppercase">Hạng thành viên</p>
                                <span class="badge bg-warning text-dark fs-6 px-3 py-2 mt-1 rounded-pill fw-bold">
                                    <i class="fa-solid fa-crown me-1"></i><?= htmlspecialchars($user['rank_name'] ?? 'Chưa có hạng') ?>
                                </span>
                            </div>
                            <div class="col-md-4 text-center border-end">
                                <p class="mb-1 text-muted small fw-medium text-uppercase">Ngày tạo tài khoản</p>
                                <span class="fw-bold d-block mt-2 text-secondary">
                                    <?= isset($user['created_at']) ? date('d/m/Y', strtotime($user['created_at'])) : '---' ?>
                                </span>
                            </div>
                            <div class="col-md-4 text-center">
                                <p class="mb-1 text-muted small fw-medium text-uppercase">Ngày sửa đổi gần nhất</p>
                                <span class="fw-bold d-block mt-2 text-secondary">
                                    <?= isset($user['updated_at']) ? date('d/m/Y H:i', strtotime($user['updated_at'])) : '---' ?>
                                </span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="index.php" class="btn btn-outline-secondary px-4"><i class="fa-solid fa-arrow-left me-2"></i>Quay lại</a>
                            <button type="submit" class="btn btn-success px-4"><i class="fa-solid fa-floppy-disk me-2"></i>Cập nhật thông tin</button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>