<?php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../../db.php';

$customer    = current_customer();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Smart Carwash – Rửa xe thông minh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary:#2563eb; --dark:#0f172a; --bg:#f3f6fb; }
        body { background:var(--bg); font-family:Inter,Segoe UI,Arial,sans-serif; color:var(--dark); }
        .navbar-brand { font-weight:800; letter-spacing:-.5px; }
        .brand-icon { width:36px;height:36px;border-radius:10px;background:var(--primary);color:#fff;
                      display:inline-flex;align-items:center;justify-content:center; }
        .nav-link { font-weight:600; }
        .nav-link.active { color:var(--primary) !important; }
        .cardx { border:0;border-radius:20px;box-shadow:0 10px 36px rgba(15,23,42,.07); }
        .btn,.form-control,.form-select { border-radius:12px; }
        .form-control,.form-select { height:48px; }
        textarea.form-control { height:auto; }
        .service-card { transition:transform .2s,box-shadow .2s; }
        .service-card:hover { transform:translateY(-4px);box-shadow:0 16px 48px rgba(15,23,42,.12); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2"
           href="<?= BASE_URL ?>/customer/index.php">
            <span class="brand-icon"><i class="fa-solid fa-car-side fa-sm"></i></span>
            Smart Carwash
        </a>
        <button class="navbar-toggler" type="button"
                data-bs-toggle="collapse" data-bs-target="#navMain">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navMain">
            <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-1">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='index.php'?'active':'' ?>"
                       href="<?= BASE_URL ?>/customer/index.php">
                        <i class="fa-solid fa-house me-1"></i>Trang chủ
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='services.php'?'active':'' ?>"
                       href="<?= BASE_URL ?>/customer/services.php">
                        <i class="fa-solid fa-spray-can-sparkles me-1"></i>Dịch vụ
                    </a>
                </li>

                <?php if ($customer): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage==='booking.php'?'active':'' ?>"
                       href="<?= BASE_URL ?>/customer/booking.php">
                        <i class="fa-solid fa-calendar-check me-1"></i>Đặt lịch
                    </a>
                </li>
                <li class="nav-item dropdown ms-lg-2">
                    <a class="nav-link dropdown-toggle fw-bold" href="#" data-bs-toggle="dropdown">
                        <i class="fa-solid fa-circle-user me-1 text-primary"></i>
                        <?= e($customer['name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                        <li>
                            <a class="dropdown-item"
                               href="<?= BASE_URL ?>/customer/booking.php">
                                <i class="fa-solid fa-calendar-check me-2 text-primary"></i>Lịch đặt của tôi
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item"
                               href="<?= BASE_URL ?>/customer/auth/change_password.php">
                                <i class="fa-solid fa-lock me-2 text-warning"></i>Đổi mật khẩu
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger"
                               href="<?= BASE_URL ?>/customer/auth/logout.php">
                                <i class="fa-solid fa-arrow-right-from-bracket me-2"></i>Đăng xuất
                            </a>
                        </li>
                    </ul>
                </li>
                <?php else: ?>
                <li class="nav-item ms-lg-2">
                    <a class="btn btn-outline-primary px-4"
                       href="<?= BASE_URL ?>/customer/auth/login.php">Đăng nhập</a>
                </li>
                <li class="nav-item ms-lg-1">
                    <a class="btn btn-primary px-4"
                       href="<?= BASE_URL ?>/customer/auth/register.php">Đăng ký</a>
                </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>