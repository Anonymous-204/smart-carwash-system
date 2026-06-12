<?php
require_once __DIR__ . '/../db.php';
$services = fetch_all($conn->query(
    "SELECT * FROM services WHERE is_active = 1 ORDER BY price ASC"
));
include __DIR__ . '/includes/header.php';
?>
<main>
<section style="background:linear-gradient(135deg,#0f172a,#1d4ed8);color:#fff;padding:48px 0 40px">
    <div class="container text-center">
        <h2 class="fw-bold mb-2"><i class="fa-solid fa-spray-can-sparkles me-2"></i>Gói dịch vụ rửa xe</h2>
        <p class="text-white-50 mb-0">Chọn gói phù hợp, đặt lịch ngay – nhận xe sạch tận tay</p>
    </div>
</section>

<div class="container py-5">
    <div class="row g-4">
        <?php foreach ($services as $s): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card cardx h-100 service-card p-0 overflow-hidden">
                <div style="height:6px;background:linear-gradient(90deg,#2563eb,#60a5fa)"></div>
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h5 class="fw-bold mb-0"><?= e($s['name']) ?></h5>
                        <span class="badge text-bg-primary rounded-pill px-3">
                            <i class="fa-solid fa-clock me-1"></i><?= (int)$s['duration'] ?> phút
                        </span>
                    </div>
                    <p class="text-muted small mb-4"><?= e($s['description']) ?></p>
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-5 text-primary"><?= money($s['price']) ?></span>
                        <?php $cust = current_customer(); ?>
                        <?php if ($cust): ?>
                            <a href="<?= BASE_URL ?>/customer/index.php?book=<?= $s['id'] ?>"
                               class="btn btn-primary btn-sm px-4">
                                <i class="fa-solid fa-calendar-plus me-1"></i>Đặt lịch
                            </a>
                        <?php else: ?>
                            <a href="<?= BASE_URL ?>/customer/auth/login.php"
                               class="btn btn-outline-primary btn-sm px-4">
                                Đăng nhập để đặt
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if (empty($services)): ?>
        <div class="col-12 text-center text-muted py-5">
            <i class="fa-solid fa-circle-info fa-2x mb-2"></i>
            <p>Hiện chưa có dịch vụ nào.</p>
        </div>
        <?php endif; ?>
    </div>
</div>
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>