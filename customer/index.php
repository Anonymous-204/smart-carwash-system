<?php
require_once __DIR__ . '/../db.php';
$customer = current_customer();

// Dịch vụ nổi bật
$featuredServices = fetch_all($conn->query(
    "SELECT * FROM services WHERE is_active = 1 ORDER BY price ASC LIMIT 3"
));

// Feedback thật từ DB
$feedbacks = fetch_all($conn->query("
    SELECT f.rating, f.content, u.name customer_name, s.name service_name
    FROM feedbacks f
    JOIN orders o ON o.id = f.order_id
    JOIN users u ON u.id = o.customer_id
    LEFT JOIN order_details od ON od.order_id = o.id
    LEFT JOIN services s ON s.id = od.service_id
    ORDER BY f.created_at DESC LIMIT 3
"));

include __DIR__ . '/includes/header.php';
?>

<!-- ========== HERO ========== -->
<section style="background:linear-gradient(135deg,#0f172a 0%,#1e3a8a 60%,#2563eb 100%);
                color:#fff;padding:90px 0 80px;position:relative;overflow:hidden">
    <div style="position:absolute;width:400px;height:400px;border-radius:50%;
                background:rgba(255,255,255,.04);top:-100px;right:-80px"></div>
    <div style="position:absolute;width:250px;height:250px;border-radius:50%;
                background:rgba(255,255,255,.04);bottom:-60px;left:-60px"></div>
    <div class="container position-relative">
        <div class="row align-items-center g-5">
            <div class="col-lg-7">
                <span class="badge text-bg-primary px-3 py-2 mb-3 rounded-pill">
                    <i class="fa-solid fa-star me-1"></i>Hệ thống rửa xe thông minh #1
                </span>
                <h1 style="font-size:3rem;font-weight:800;line-height:1.15">
                    Rửa xe sạch bóng —<br>
                    <span style="color:#60a5fa">Tận tay tận nhà</span>
                </h1>
                <p class="text-white-50 mt-3 mb-4" style="font-size:1.1rem">
                    Đặt lịch online dễ dàng, nhân viên đón &amp; trả xe tận nơi.
                    Xe sạch chuẩn — đúng giờ — uy tín.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <?php if ($customer): ?>
                        <a href="<?= BASE_URL ?>/customer/booking.php"
                           class="btn btn-primary btn-lg px-5 fw-bold">
                            <i class="fa-solid fa-calendar-plus me-2"></i>Đặt lịch ngay
                        </a>
                    <?php else: ?>
                        <a href="<?= BASE_URL ?>/customer/auth/register.php"
                           class="btn btn-primary btn-lg px-5 fw-bold">
                            <i class="fa-solid fa-calendar-plus me-2"></i>Đặt lịch ngay
                        </a>
                        <a href="<?= BASE_URL ?>/customer/auth/login.php"
                           class="btn btn-outline-light btn-lg px-4">
                            Đăng nhập
                        </a>
                    <?php endif; ?>
                </div>
                <!-- Stats -->
                <div class="d-flex gap-4 mt-5 flex-wrap">
                    <div>
                        <div style="font-size:1.8rem;font-weight:800;color:#60a5fa">500+</div>
                        <div class="text-white-50 small">Khách hàng</div>
                    </div>
                    <div style="border-left:1px solid rgba(255,255,255,.15);padding-left:1.5rem">
                        <div style="font-size:1.8rem;font-weight:800;color:#60a5fa">3</div>
                        <div class="text-white-50 small">Chi nhánh</div>
                    </div>
                    <div style="border-left:1px solid rgba(255,255,255,.15);padding-left:1.5rem">
                        <div style="font-size:1.8rem;font-weight:800;color:#60a5fa">4.9 ★</div>
                        <div class="text-white-50 small">Đánh giá</div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 text-center d-none d-lg-flex align-items-center justify-content-center">
                <div style="font-size:11rem;opacity:.12;line-height:1">
                    <i class="fa-solid fa-car-side"></i>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========== DỊCH VỤ NỔI BẬT ========== -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge text-bg-primary rounded-pill px-3 py-2 mb-2">Dịch vụ</span>
            <h2 class="fw-bold">Gói dịch vụ phổ biến</h2>
            <p class="text-muted">Đa dạng gói chăm sóc xe chuyên nghiệp, phù hợp mọi nhu cầu</p>
        </div>
        <div class="row g-4">
            <?php foreach ($featuredServices as $s): ?>
            <div class="col-md-4">
                <div class="card cardx h-100 service-card overflow-hidden">
                    <div style="height:6px;background:linear-gradient(90deg,#2563eb,#60a5fa)"></div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="rounded-3 p-2"
                                 style="background:#eff6ff;width:48px;height:48px;
                                        display:flex;align-items:center;justify-content:center">
                                <i class="fa-solid fa-spray-can-sparkles text-primary"></i>
                            </div>
                            <span class="badge text-bg-light border">
                                <i class="fa-solid fa-clock me-1 text-muted"></i><?= (int)$s['duration'] ?> phút
                            </span>
                        </div>
                        <h5 class="fw-bold mb-2"><?= e($s['name']) ?></h5>
                        <p class="text-muted small mb-4"><?= e($s['description']) ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold fs-5 text-primary"><?= money($s['price']) ?></span>
                            <?php if ($customer): ?>
                                <a href="<?= BASE_URL ?>/customer/booking.php?service=<?= $s['id'] ?>"
                                   class="btn btn-primary btn-sm px-3">Đặt ngay</a>
                            <?php else: ?>
                                <a href="<?= BASE_URL ?>/customer/auth/login.php"
                                   class="btn btn-outline-primary btn-sm px-3">Đặt ngay</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="text-center mt-4">
            <a href="<?= BASE_URL ?>/customer/services.php" class="btn btn-outline-primary px-5">
                <i class="fa-solid fa-grid me-2"></i>Xem tất cả dịch vụ
            </a>
        </div>
    </div>
</section>

<!-- ========== TẠI SAO CHỌN CHÚNG TÔI ========== -->
<section class="py-5" style="background:#f3f6fb">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge text-bg-primary rounded-pill px-3 py-2 mb-2">Lý do</span>
            <h2 class="fw-bold">Tại sao chọn Smart Carwash?</h2>
        </div>
        <div class="row g-4 text-center">
            <?php
            $features = [
                ['fa-truck-pickup',  '#2563eb', 'Đón & trả tận nơi',    'Nhân viên đến tận nhà đón xe, rửa xong trả về — bạn không cần đến cửa hàng.'],
                ['fa-shield-halved', '#16a34a', 'Cam kết chất lượng',   'Hóa chất cao cấp, máy móc hiện đại, bảo đảm sạch chuẩn mỗi lần.'],
                ['fa-clock',         '#d97706', 'Đúng giờ – Nhanh gọn', 'Hệ thống đặt lịch thông minh, không chờ đợi, đúng giờ cam kết.'],
                ['fa-award',         '#7c3aed', 'Tích điểm ưu đãi',     'Mỗi lần rửa xe tích điểm đổi quà, thăng hạng nhận ưu đãi hấp dẫn.'],
            ];
            foreach ($features as $f): ?>
            <div class="col-md-3 col-6">
                <div class="card cardx p-4 h-100">
                    <div class="mx-auto mb-3 rounded-3 d-flex align-items-center justify-content-center"
                         style="width:60px;height:60px;background:<?= $f[1] ?>22">
                        <i class="fa-solid <?= $f[0] ?> fa-lg" style="color:<?= $f[1] ?>"></i>
                    </div>
                    <h6 class="fw-bold mb-2"><?= $f[2] ?></h6>
                    <p class="text-muted small mb-0"><?= $f[3] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========== QUY TRÌNH ========== -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge text-bg-primary rounded-pill px-3 py-2 mb-2">Quy trình</span>
            <h2 class="fw-bold">Đặt lịch chỉ 4 bước</h2>
            <p class="text-muted">Nhanh chóng — Đơn giản — Tiện lợi</p>
        </div>
        <div class="row g-4 text-center">
            <?php
            $steps = [
                ['1','fa-mobile-screen','#2563eb','Chọn dịch vụ',    'Chọn gói rửa xe phù hợp với nhu cầu và ngân sách'],
                ['2','fa-calendar-days','#16a34a','Chọn thời gian',  'Đặt lịch theo khung giờ bạn muốn, linh hoạt 7 ngày/tuần'],
                ['3','fa-car-on',       '#d97706','Chúng tôi xử lý', 'Nhân viên đón xe hoặc bạn mang đến chi nhánh'],
                ['4','fa-star',         '#7c3aed','Nhận xe & đánh giá','Xe sạch bóng trả tận tay, đánh giá nhận điểm thưởng'],
            ];
            foreach ($steps as $i => $s): ?>
            <div class="col-md-3 col-6">
                <div class="card cardx p-4 h-100">
                    <div class="mx-auto mb-3 rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                         style="width:52px;height:52px;background:<?= $s[2] ?>;font-size:1.2rem">
                        <?= $s[0] ?>
                    </div>
                    <i class="fa-solid <?= $s[1] ?> fa-lg mb-2" style="color:<?= $s[2] ?>"></i>
                    <h6 class="fw-bold mb-2"><?= $s[3] ?></h6>
                    <p class="text-muted small mb-0"><?= $s[4] ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ========== ĐÁNH GIÁ ========== -->
<?php if (!empty($feedbacks)): ?>
<section class="py-5" style="background:#f3f6fb">
    <div class="container">
        <div class="text-center mb-5">
            <span class="badge text-bg-primary rounded-pill px-3 py-2 mb-2">Đánh giá</span>
            <h2 class="fw-bold">Khách hàng nói gì?</h2>
        </div>
        <div class="row g-4">
            <?php foreach ($feedbacks as $fb): ?>
            <div class="col-md-4">
                <div class="card cardx p-4 h-100">
                    <div class="mb-2">
                        <?php for ($i=1;$i<=5;$i++): ?>
                            <i class="fa-solid fa-star <?= $i<=$fb['rating']?'text-warning':'text-muted' ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <p class="text-muted mb-3 fst-italic">"<?= e($fb['content']) ?>"</p>
                    <div class="mt-auto d-flex align-items-center gap-2">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white"
                             style="width:38px;height:38px;background:#2563eb;font-size:.9rem;flex-shrink:0">
                            <?= mb_strtoupper(mb_substr($fb['customer_name'],0,1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold small"><?= e($fb['customer_name']) ?></div>
                            <div class="text-muted" style="font-size:.75rem"><?= e($fb['service_name']) ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ========== CTA CUỐI TRANG ========== -->
<section style="background:linear-gradient(135deg,#0f172a,#1d4ed8);color:#fff;padding:70px 0">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">Xe bạn đang chờ được rửa sạch?</h2>
        <p class="text-white-50 mb-4">Đặt lịch ngay hôm nay — nhận ưu đãi 10% cho lần đầu tiên!</p>
        <?php if ($customer): ?>
            <a href="<?= BASE_URL ?>/customer/booking.php"
               class="btn btn-light btn-lg px-5 fw-bold text-primary">
                <i class="fa-solid fa-calendar-plus me-2"></i>Đặt lịch ngay
            </a>
        <?php else: ?>
            <a href="<?= BASE_URL ?>/customer/auth/register.php"
               class="btn btn-light btn-lg px-5 fw-bold text-primary me-3">
                <i class="fa-solid fa-user-plus me-2"></i>Đăng ký miễn phí
            </a>
            <a href="<?= BASE_URL ?>/customer/auth/login.php"
               class="btn btn-outline-light btn-lg px-4">Đăng nhập</a>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>