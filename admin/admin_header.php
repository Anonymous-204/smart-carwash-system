<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include __DIR__ . '/../db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    exit;
}

$current = basename($_SERVER['PHP_SELF']);
$adminName = $_SESSION['admin']['name'] ?? 'Administrator';

$menu = [
    ['index.php', 'fa-chart-pie', 'Tổng quan'],
    ['bookings.php', 'fa-calendar-check', 'Đặt lịch'],
    ['orders.php', 'fa-receipt', 'Hóa đơn'],
    ['services.php', 'fa-spray-can-sparkles', 'Dịch vụ'],
    ['members.php', 'fa-users', 'Khách hàng'],
    ['users.php', 'fa-user-shield', 'Nhân sự'],
];
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Carwash Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root{--side:#0f172a;--side2:#111827;--blue:#2563eb;--bg:#f3f6fb}
        body{background:var(--bg);font-family:Inter,Segoe UI,Arial,sans-serif;color:#0f172a}
        .sidebar{position:fixed;left:0;top:0;bottom:0;width:245px;background:linear-gradient(180deg,var(--side),var(--side2));color:#cbd5e1;padding:18px 14px;z-index:99}
        .brand{display:flex;gap:12px;align-items:center;padding:10px 8px 20px;border-bottom:1px solid rgba(255,255,255,.08);margin-bottom:18px}
        .brand-icon{width:42px;height:42px;border-radius:14px;background:#2563eb;color:white;display:flex;align-items:center;justify-content:center}
        .nav-link-admin{display:flex;align-items:center;gap:12px;color:#cbd5e1;text-decoration:none;padding:13px 14px;border-radius:14px;margin:6px 0;font-weight:600}
        .nav-link-admin:hover,.nav-link-admin.active{background:#2563eb;color:white}
        .main{margin-left:245px;min-height:100vh}
        .topbar{height:74px;background:white;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;justify-content:space-between;padding:0 32px;position:sticky;top:0;z-index:50}
        .content{padding:28px 32px}
        .cardx{border:0;border-radius:22px;box-shadow:0 14px 40px rgba(15,23,42,.06)}
        .stat{padding:24px}.stat .num{font-size:32px;font-weight:800;color:#2563eb}
        .iconbox{width:50px;height:50px;border-radius:16px;display:flex;align-items:center;justify-content:center;background:#eff6ff;color:#2563eb}
        .table thead th{font-size:13px;text-transform:uppercase;letter-spacing:.04em;color:#64748b}
        .btn{border-radius:12px}.form-control,.form-select{border-radius:12px}
    </style>
</head>
<body>
<aside class="sidebar">
    <div class="brand">
        <div class="brand-icon"><i class="fa-solid fa-car-side"></i></div>
        <div>
            <div class="fw-bold text-white">Smart Carwash</div>
            <small>Admin Dashboard</small>
        </div>
    </div>

    <?php foreach ($menu as $item): ?>
        <a class="nav-link-admin <?= $current === $item[0] ? 'active' : '' ?>" href="<?= $item[0] ?>">
            <i class="fa-solid <?= $item[1] ?> fa-fw"></i>
            <span><?= $item[2] ?></span>
        </a>
    <?php endforeach; ?>

    <a class="nav-link-admin mt-4" href="logout_admin.php">
        <i class="fa-solid fa-arrow-right-from-bracket fa-fw"></i>
        <span>Đăng xuất</span>
    </a>
</aside>
<main class="main">
    <div class="topbar">
        <div>
            <h4 class="fw-bold mb-0">Hệ thống Rửa Xe Thông Minh</h4>
            <small class="text-muted">Quản trị đặt lịch, khách hàng, dịch vụ và thanh toán</small>
        </div>
        <div class="d-flex align-items-center gap-3">
            <span class="badge text-bg-light border px-3 py-2"><i class="fa-solid fa-user-circle text-primary me-1"></i><?= e($adminName) ?></span>
        </div>
    </div>
    <div class="content">
