<?php
session_start();

// **CHÚ Ý ĐƯỜNG DẪN db.php**
// File này nằm trong /frontend/admin/ nên phải lùi 1 cấp
include "db.php";

// Tự động kiểm tra và khởi tạo các bảng cho Hệ thống Rửa xe Thông minh
$conn->query("CREATE TABLE IF NOT EXISTS rua_xe_dich_vu (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_goi VARCHAR(255) NOT NULL,
    gia INT NOT NULL,
    thoi_gian INT NOT NULL,
    mo_ta TEXT,
    trang_thai TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS rua_xe_thanh_vien (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_thanh_vien VARCHAR(255) NOT NULL,
    so_dien_thoai VARCHAR(20) UNIQUE NOT NULL,
    bien_so_xe VARCHAR(20),
    hang_thanh_vien VARCHAR(50) DEFAULT 'Đồng',
    diem_tich_luy INT DEFAULT 0,
    ngay_dang_ky DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$conn->query("CREATE TABLE IF NOT EXISTS rua_xe_dat_lich (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ten_khach_hang VARCHAR(255) NOT NULL,
    so_dien_thoai VARCHAR(20) NOT NULL,
    bien_so_xe VARCHAR(20) NOT NULL,
    loai_xe VARCHAR(50) NOT NULL,
    goi_id INT NOT NULL,
    ngay_dat DATE NOT NULL,
    gio_dat TIME NOT NULL,
    tong_tien INT NOT NULL,
    diem_nhan_duoc INT DEFAULT 0,
    trang_thai VARCHAR(50) DEFAULT 'Chờ duyệt',
    ngay_tao DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Kiểm tra và seed dữ liệu mẫu nếu chưa có dịch vụ nào
$checkServices = $conn->query("SELECT COUNT(*) FROM rua_xe_dich_vu");
$servCount = $checkServices ? $checkServices->fetch_row()[0] : 0;
if ($servCount == 0) {
    $conn->query("INSERT INTO rua_xe_dich_vu (ten_goi, gia, thoi_gian, mo_ta, trang_thai) VALUES 
    ('Rửa tiêu chuẩn (Standard Wash)', 80000, 20, 'Rửa ngoài, xịt gầm, hút bụi cơ bản, lau khô.', 1),
    ('Rửa chuyên sâu (Premium Wash)', 150000, 35, 'Rửa ngoài, xịt gầm chi tiết, rửa khoang máy cơ bản, hút bụi kỹ, dưỡng lốp bóng.', 1),
    ('Vệ sinh nội thất & Khử mùi', 450000, 90, 'Dọn sạch nội thất bằng hơi nước nóng, khử mùi diệt khuẩn chuyên sâu.', 1),
    ('Đánh bóng & Phủ Nano Ceramic', 1200000, 180, 'Đánh bóng hiệu chỉnh bề mặt sơn, phủ lớp bảo vệ Nano Ceramic siêu bóng.', 1)");
}

// Kiểm tra và seed thành viên mẫu nếu chưa có
$checkMembers = $conn->query("SELECT COUNT(*) FROM rua_xe_thanh_vien");
$membCount = $checkMembers ? $checkMembers->fetch_row()[0] : 0;
if ($membCount == 0) {
    $conn->query("INSERT INTO rua_xe_thanh_vien (ten_thanh_vien, so_dien_thoai, bien_so_xe, hang_thanh_vien, diem_tich_luy, ngay_dang_ky) VALUES 
    ('Nguyễn Văn Tuấn', '0912345678', '30A-123.45', 'Vàng', 350, '2026-05-10 10:00:00'),
    ('Trần Thị Hương', '0987654321', '29D-987.65', 'Bạc', 180, '2026-05-12 14:30:00'),
    ('Phạm Minh Đức', '0901234567', '30H-888.88', 'Kim cương', 1200, '2026-04-20 09:15:00'),
    ('Lê Hoàng Nam', '0933334444', '51G-555.55', 'Đồng', 80, '2026-06-01 16:45:00')");
}

// Kiểm tra và seed lịch đặt xe mẫu nếu chưa có
$checkBookings = $conn->query("SELECT COUNT(*) FROM rua_xe_dat_lich");
$bookCount = $checkBookings ? $checkBookings->fetch_row()[0] : 0;
if ($bookCount == 0) {
    // Thêm các lịch đặt xe trong quá khứ và hiện tại để vẽ biểu đồ doanh thu sinh động
    $today = date('Y-m-d');
    $dayMinus1 = date('Y-m-d', strtotime('-1 days'));
    $dayMinus2 = date('Y-m-d', strtotime('-2 days'));
    $dayMinus3 = date('Y-m-d', strtotime('-3 days'));
    $dayMinus4 = date('Y-m-d', strtotime('-4 days'));
    $dayMinus5 = date('Y-m-d', strtotime('-5 days'));

    $conn->query("INSERT INTO rua_xe_dat_lich (ten_khach_hang, so_dien_thoai, bien_so_xe, loai_xe, goi_id, ngay_dat, gio_dat, tong_tien, diem_nhan_duoc, trang_thai, ngay_tao) VALUES 
    ('Nguyễn Văn Tuấn', '0912345678', '30A-123.45', 'Sedan', 1, '$dayMinus5', '09:00:00', 80000, 10, 'Đã hoàn thành', '$dayMinus5 08:30:00'),
    ('Trần Thị Hương', '0987654321', '29D-987.65', 'SUV', 2, '$dayMinus4', '10:30:00', 150000, 20, 'Đã hoàn thành', '$dayMinus4 10:00:00'),
    ('Phạm Minh Đức', '0901234567', '30H-888.88', 'Sedan', 4, '$dayMinus3', '14:00:00', 1200000, 150, 'Đã hoàn thành', '$dayMinus3 13:15:00'),
    ('Lê Hoàng Nam', '0933334444', '51G-555.55', 'Bán tải', 2, '$dayMinus2', '11:00:00', 150000, 20, 'Đã hoàn thành', '$dayMinus2 10:30:00'),
    ('Vũ Anh Tuấn', '0955556666', '30E-222.33', 'Xe máy', 1, '$dayMinus1', '08:00:00', 80000, 10, 'Đã hoàn thành', '$dayMinus1 07:45:00'),
    ('Nguyễn Thị Mai', '0966667777', '29A-444.55', 'Sedan', 3, '$today', '09:30:00', 450000, 50, 'Đã duyệt', '$today 08:15:00'),
    ('Bùi Quang Đạt', '0977778888', '30K-777.89', 'SUV', 2, '$today', '15:00:00', 150000, 20, 'Chờ duyệt', '$today 11:30:00'),
    ('Phạm Quốc Khánh', '0988889999', '30F-999.99', 'Sedan', 1, '$today', '16:30:00', 80000, 10, 'Chờ duyệt', '$today 14:00:00')");
}

// Kiểm tra và tạo tài khoản admin mặc định nếu chưa có admin nào
$checkAdmin = $conn->query("SELECT COUNT(*) FROM admin");
$adminCount = $checkAdmin ? $checkAdmin->fetch_row()[0] : 0;
if ($adminCount == 0) {
    $passMD5 = md5("123456");
    $conn->query("INSERT INTO admin (username, password, role) VALUES ('admin', '$passMD5', 'admin')");
}

// Nếu chưa đăng nhập admin -> đẩy về trang login
if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

// Lấy tên admin để hiển thị
$adminName = is_array($_SESSION['admin'])
    ? ($_SESSION['admin']['username'] ?? 'Admin')
    : $_SESSION['admin'];
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Hệ thống Rửa Xe Thông Minh - Admin</title>
    <!-- Dùng CDN Bootstrap -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <!-- FontAwesome Icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background:#f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .sidebar {
            height: 100vh;
            background: #1e293b;
            color: #cbd5e1;
            padding-top: 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 240px;
            z-index: 1000;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .sidebar a {
            color: #cbd5e1;
            text-decoration: none;
            display: flex;
            align-items: center;
            padding: 12px 20px;
            border-radius: 8px;
            margin: 5px 10px;
            transition: all 0.3s ease;
        }
        .sidebar a i {
            margin-right: 12px;
            font-size: 1.1rem;
            width: 24px;
            text-align: center;
        }
        .sidebar a.active,
        .sidebar a:hover {
            background: #0ea5e9;
            color: #fff;
            transform: translateX(5px);
        }
        .main-content {
            margin-left: 240px;
            min-height: 100vh;
        }
        .admin-topbar {
            background: #fff;
            padding: 15px 30px;
            border-bottom: 1px solid #e2e8f0;
            box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.03);
            transition: transform 0.2s;
        }
        .card-stat:hover {
            transform: translateY(-5px);
        }
        .badge-status {
            font-weight: 600;
            font-size: 0.85rem;
            padding: 6px 12px;
            border-radius: 50px;
        }
        .bg-pending { background-color: #fef3c7; color: #d97706; }
        .bg-approved { background-color: #e0f2fe; color: #0284c7; }
        .bg-completed { background-color: #dcfce7; color: #15803d; }
        .bg-cancelled { background-color: #fee2e2; color: #b91c1c; }
    </style>
</head>
<body>

<div class="container-fluid p-0">
    <!-- SIDEBAR -->
    <nav class="sidebar">
        <div class="px-4 mb-4 text-center border-bottom pb-3 border-secondary">
            <h5 class="mb-0 text-white font-weight-bold"><i class="fa-solid fa-car-wash text-info me-2"></i>SmartWash</h5>
            <small class="text-info">Quản trị Hệ Thống</small>
        </div>

        <a href="index.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='index.php'?'active':''; ?>">
            <i class="fa-solid fa-chart-line"></i> Doanh thu & Thống kê
        </a>
        <a href="bookings.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='bookings.php' || basename($_SERVER['PHP_SELF'])=='orders.php'?'active':''; ?>">
            <i class="fa-solid fa-calendar-check"></i> Duyệt lịch đặt xe
        </a>
        <a href="members.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='members.php' || basename($_SERVER['PHP_SELF'])=='users.php'?'active':''; ?>">
            <i class="fa-solid fa-id-card"></i> Quản lý thành viên
        </a>
        <a href="services.php" class="<?php echo basename($_SERVER['PHP_SELF'])=='services.php' || basename($_SERVER['PHP_SELF'])=='products.php'?'active':''; ?>">
            <i class="fa-solid fa-cubes"></i> Các gói dịch vụ
        </a>
        <div class="border-top my-3 border-secondary"></div>
        <a href="logout_admin.php" class="text-danger">
            <i class="fa-solid fa-right-from-bracket text-danger"></i> Đăng xuất
        </a>
    </nav>

    <!-- PHẦN NỘI DUNG -->
    <main class="main-content">
        <div class="admin-topbar d-flex justify-content-between align-items-center">
            <h5 class="mb-0 text-dark fw-bold"><i class="fa-solid fa-circle-nodes text-primary me-2"></i>Hệ thống Rửa xe tự động Thông minh</h5>
            <div class="d-flex align-items-center">
                <span class="me-3 text-muted"><i class="fa-regular fa-user me-1"></i> Xin chào, <strong><?php echo htmlspecialchars($adminName); ?></strong></span>
                <span class="badge bg-success">Admin Mode</span>
            </div>
        </div>

        <div class="p-4">
            <!-- Các trang con sẽ đặt nội dung vào đây -->