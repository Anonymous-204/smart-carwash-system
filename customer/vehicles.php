<?php
// 1. Khởi động session để nhận diện trạng thái đăng nhập và dùng Session Flash
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Gọi file kết nối CSDL (Lùi 1 tầng từ thư mục customer/ ra thư mục gốc)
require_once __DIR__ . '/../db.php'; 

// 2. Sử dụng hàm hệ thống để kiểm tra thông tin khách hàng đang đăng nhập
$current_user = current_customer(); 

if (!$current_user) {
    // Nếu chưa đăng nhập hoặc bị mất session, chuyển hướng về trang login
    header('Location: ' . BASE_URL . '/customer/auth/login.php');
    exit;
}
include __DIR__ . '/includes/header.php';
$customer_id = $current_user['id']; 

// 3. XỬ LÝ CHỨC NĂNG XÓA XE (GET action=delete)
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $vehicle_id = intval($_GET['id']);
    
    // Kiểm tra xem xe này có đúng là của khách hàng đang đăng nhập hay không
    $check_owner_query = "SELECT id FROM vehicles WHERE id = $vehicle_id AND user_id = $customer_id LIMIT 1";
    $check_owner_result = mysqli_query($conn, $check_owner_query);
    
    if (mysqli_num_rows($check_owner_result) > 0) {
        // Kiểm tra xem xe này đã từng phát sinh đơn hàng (bảng orders) chưa để tránh lỗi khóa ngoại
        $check_order_query = "SELECT id FROM orders WHERE vehicle_id = $vehicle_id LIMIT 1";
        $check_order_result = mysqli_query($conn, $check_order_query);
        
        if (mysqli_num_rows($check_order_result) > 0) {
            $_SESSION['flash_message'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                                            <i class='fa-solid fa-circle-exclamation me-2'></i>Không thể xóa xe này vì thông tin xe đang nằm trong lịch sử đơn đặt lịch!
                                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                                        </div>";
        } else {
            // Tiến hành xóa xe
            $delete_query = "DELETE FROM vehicles WHERE id = $vehicle_id AND user_id = $customer_id";
            if (mysqli_query($conn, $delete_query)) {
                $_SESSION['flash_message'] = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                                                <i class='fa-solid fa-circle-check me-2'></i>Xóa thông tin phương tiện thành công!
                                                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                                            </div>";
            } else {
                $_SESSION['flash_message'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                                                Lỗi CSDL: " . mysqli_error($conn) . "
                                                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                                            </div>";
            }
        }
    }
    // Chuyển hướng về lại trang để xóa các tham số GET thừa trên URL và chống F5 trùng lệnh xóa
    header("Location: vehicles.php");
    exit;
}

// 4. XỬ LÝ CHỨC NĂNG THÊM XE MỚI (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $brand        = mysqli_real_escape_string($conn, trim($_POST['brand'] ?? ''));
    $vehicle_type = mysqli_real_escape_string($conn, trim($_POST['vehicle_type'] ?? ''));
    $license_plate= mysqli_real_escape_string($conn, trim($_POST['license_plate'] ?? ''));
    $color        = mysqli_real_escape_string($conn, trim($_POST['color'] ?? ''));

    if ($brand === '' || $vehicle_type === '' || $license_plate === '') {
        $_SESSION['flash_message'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                                        <i class='fa-solid fa-triangle-exclamation me-2'></i>Vui lòng điền đầy đủ các trường bắt buộc (*).
                                        <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                                    </div>";
    } else {
        // Thêm xe mới vào bảng vehicles liên kết với id của khách hàng
        $insert_query = "INSERT INTO vehicles (user_id, brand, vehicle_type, license_plate, color) 
                         VALUES ('$customer_id', '$brand', '$vehicle_type', '$license_plate', '$color')";
        
        if (mysqli_query($conn, $insert_query)) {
            $_SESSION['flash_message'] = "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                                            <i class='fa-solid fa-circle-check me-2'></i>Thêm phương tiện mới thành công!
                                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                                        </div>";
        } else {
            $_SESSION['flash_message'] = "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                                            Lỗi thêm dữ liệu: " . mysqli_error($conn) . "
                                            <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
                                        </div>";
        }
    }
    // Chuyển hướng sau khi POST hoàn tất (Ngăn lỗi Confirm Form Resubmission hoàn toàn)
    header("Location: vehicles.php");
    exit;
}

// 5. TRUY VẤN TẤT CẢ XE CỦA KHÁCH HÀNG HIỆN TẠI (Dùng cho phương thức GET thuần túy)
$query = "SELECT * FROM vehicles WHERE user_id = '$customer_id' ORDER BY id DESC";
$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phương tiện – Smart Carwash</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <?php 
            if (isset($_SESSION['flash_message'])) {
                echo $_SESSION['flash_message'];
                unset($_SESSION['flash_message']); // Xóa ngay sau khi hiển thị để không lặp lại khi F5
            }
            ?>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header bg-primary text-white py-3 rounded-top-4">
                            <h6 class="mb-0 fw-bold"><i class="fa-solid fa-car-side me-2"></i>Thêm Xe Mới</h6>
                        </div>
                        <div class="card-body p-4">
                            <form method="POST" autocomplete="off">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Hãng xe <span class="text-danger">*</span></label>
                                    <input type="text" name="brand" class="form-control" placeholder="Ví dụ: Toyota, Honda..." required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Loại xe <span class="text-danger">*</span></label>
                                    <input type="text" name="vehicle_type" class="form-control" placeholder="Ví dụ: Sedan, SUV, Bán tải..." required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Biển số xe <span class="text-danger">*</span></label>
                                    <input type="text" name="license_plate" class="form-control" placeholder="Ví dụ: 30A-123.45" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Màu sắc</label>
                                    <input type="text" name="color" class="form-control" placeholder="Ví dụ: Đen, Trắng, Đỏ...">
                                </div>
                                <button type="submit" name="add_vehicle" class="btn btn-primary w-100 fw-bold mt-2">
                                    <i class="fa-solid fa-plus me-2"></i>Thêm vào danh sách
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="card shadow border-0 rounded-4">
                        <div class="card-header bg-dark text-white py-3 rounded-top-4 d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-list me-2"></i>Danh Sách Xe Của Bạn</h5>
                            <span class="badge bg-secondary px-3 py-2 rounded-pill"><?= mysqli_num_rows($result) ?> Xe</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (mysqli_num_rows($result) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle mb-0 text-center">
                                        <thead class="table-light">
                                            <tr>
                                                <th class="py-3">STT</th>
                                                <th>Hãng Xe</th>
                                                <th>Dòng Xe / Loại Xe</th>
                                                <th>Biển Số</th>
                                                <th>Màu Sắc</th>
                                                <th>Hành Động</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $stt = 1;
                                            while($row = mysqli_fetch_assoc($result)): 
                                            ?>
                                                <tr>
                                                    <td class="fw-bold text-muted"><?= $stt++ ?></td>
                                                    <td class="fw-bold text-primary"><?= htmlspecialchars($row['brand']) ?></td>
                                                    <td><?= htmlspecialchars($row['vehicle_type']) ?></td>
                                                    <td>
                                                        <span class="badge bg-light text-dark border border-secondary px-2 py-1 fs-6 font-monospace">
                                                            <?= htmlspecialchars($row['license_plate']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?= $row['color'] ? htmlspecialchars($row['color']) : '<span class="text-muted italic">Chưa xác định</span>' ?>
                                                    </td>
                                                    <td>
                                                        <a href="vehicles.php?action=delete&id=<?= $row['id'] ?>" 
                                                           class="btn btn-sm btn-outline-danger" 
                                                           onclick="return confirm('Bạn có chắc chắn muốn xóa phương tiện này không?');">
                                                            <i class="fa-solid fa-trash"></i> Xóa
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5 px-4">
                                    <i class="fa-solid fa-car-burst text-muted fa-3x mb-3"></i>
                                    <p class="text-secondary mb-0">Bạn chưa đăng ký phương tiện nào hệ thống.</p>
                                    <small class="text-muted">Vui lòng điền thông tin form bên cạnh để thêm xe.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex justify-content-start">
                        <a href="index.php" class="btn btn-outline-secondary px-4">
                            <i class="fa-solid fa-arrow-left me-2"></i>Quay lại trang chính
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>