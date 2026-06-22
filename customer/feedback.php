<?php
require_once __DIR__ . '/../db.php';
require_customer_login();
$customer = current_customer();
$uid      = (int)$customer['id'];

$orderId = (int)($_GET['order_id'] ?? 0);
$errors  = [];
$message = '';

// ---------------------------------------------------------------
// LẤY THÔNG TIN ĐƠN HÀNG (chỉ đơn thuộc khách & đã COMPLETED)
// ---------------------------------------------------------------
$order = null;
if ($orderId) {
    $stmt = $conn->prepare("
        SELECT o.id, o.total, o.booking_time, o.note,
               b.name branch_name,
               v.license_plate, v.brand, v.vehicle_type,
               GROUP_CONCAT(s.name SEPARATOR ', ') service_names
        FROM orders o
        JOIN branches b  ON b.id = o.branch_id
        JOIN vehicles v  ON v.id = o.vehicle_id
        LEFT JOIN order_details od ON od.order_id = o.id
        LEFT JOIN services s       ON s.id = od.service_id
        WHERE o.id = ? AND o.customer_id = ? AND o.status = 'COMPLETED'
        GROUP BY o.id
        LIMIT 1
    ");
    $stmt->bind_param('ii', $orderId, $uid);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ---------------------------------------------------------------
// KIỂM TRA ĐÃ ĐÁNH GIÁ CHƯA
// ---------------------------------------------------------------
$existingFeedback = null;
if ($order) {
    $stmt = $conn->prepare("SELECT * FROM feedbacks WHERE order_id = ? LIMIT 1");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $existingFeedback = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

// ---------------------------------------------------------------
// GỬI ĐÁNH GIÁ
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $rating  = (int)($_POST['rating']   ?? 0);
    $content = trim($_POST['content']   ?? '');

    if (!$order)               $errors[] = 'Đơn hàng không hợp lệ.';
    if ($existingFeedback)     $errors[] = 'Bạn đã đánh giá đơn này rồi.';
    if ($rating < 1 || $rating > 5) $errors[] = 'Vui lòng chọn số sao (1–5).';

    if (empty($errors)) {
        $stmt = $conn->prepare(
            "INSERT INTO feedbacks (order_id, rating, content) VALUES (?, ?, ?)"
        );
        $stmt->bind_param('iis', $orderId, $rating, $content);
        $stmt->execute();
        $stmt->close();

        header('Location: ' . $_SERVER['PHP_SELF'] . '?order_id=' . $orderId . '&msg=done');
        exit;
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'done') {
    $message = 'Cảm ơn bạn đã đánh giá! Phản hồi của bạn giúp chúng tôi cải thiện dịch vụ.';
    // reload lại existingFeedback sau redirect
    $stmt = $conn->prepare("SELECT * FROM feedbacks WHERE order_id = ? LIMIT 1");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $existingFeedback = $stmt->get_result()->fetch_assoc();
    $stmt->close();
}

include __DIR__ . '/includes/header.php';
?>
<main>
<div class="container py-4" style="max-width:640px;">

    <a href="index.php" class="btn btn-sm btn-outline-secondary mb-4">
        <i class="fa-solid fa-arrow-left me-1"></i>Quay lại
    </a>

    <div class="card cardx p-4">

        <h5 class="fw-bold mb-1">
            <i class="fa-solid fa-comment-dots text-primary me-2"></i>Đánh giá dịch vụ
        </h5>
        <p class="text-muted small mb-4">Chia sẻ trải nghiệm của bạn về lần rửa xe này.</p>

        <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fa-solid fa-circle-check me-1"></i><?= e($message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php foreach ($errors as $err): ?>
                <div><i class="fa-solid fa-circle-exclamation me-1"></i><?= e($err) ?></div>
            <?php endforeach; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php endif; ?>

        <?php if (!$order): ?>
        <div class="text-center text-muted py-5">
            <i class="fa-solid fa-triangle-exclamation fa-2x mb-3 d-block text-warning"></i>
            <p class="mb-0">Không tìm thấy đơn hàng hoặc đơn chưa hoàn thành.<br>
            Chỉ có thể đánh giá sau khi dịch vụ đã hoàn tất.</p>
        </div>

        <?php else: ?>

        <!-- Thông tin đơn hàng -->
        <div class="border rounded-3 p-3 mb-4" style="background:#f8f9fa;">
            <div class="fw-semibold mb-1">
                <i class="fa-solid fa-car text-primary me-1"></i>
                <?= e($order['brand'] . ' ' . $order['vehicle_type']) ?>
                <span class="badge text-bg-dark ms-1"><?= e($order['license_plate']) ?></span>
            </div>
            <div class="small text-muted mb-1">
                <i class="fa-solid fa-wrench me-1"></i><?= e($order['service_names']) ?>
            </div>
            <div class="small text-muted mb-1">
                <i class="fa-solid fa-location-dot me-1"></i><?= e($order['branch_name']) ?>
                &nbsp;|&nbsp;
                <i class="fa-regular fa-calendar me-1"></i><?= date('d/m/Y H:i', strtotime($order['booking_time'])) ?>
            </div>
            <div class="small fw-semibold text-primary mt-1">
                <i class="fa-solid fa-money-bill me-1"></i><?= money($order['total']) ?>
            </div>
        </div>

        <?php if ($existingFeedback): ?>
        <!-- Hiển thị đánh giá đã gửi -->
        <div class="text-center mb-3">
            <div class="mb-2" style="font-size:2rem;">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="fa-solid fa-star <?= $i <= $existingFeedback['rating'] ? 'text-warning' : 'text-secondary' ?>"></i>
                <?php endfor; ?>
            </div>
            <div class="badge text-bg-warning fs-6 mb-3">
                <?= $existingFeedback['rating'] ?>/5 sao
            </div>
        </div>
        <?php if ($existingFeedback['content']): ?>
        <div class="border rounded-3 p-3 fst-italic text-muted small mb-3">
            <i class="fa-solid fa-quote-left me-1 text-primary"></i>
            <?= e($existingFeedback['content']) ?>
        </div>
        <?php endif; ?>
        <p class="text-center text-muted small mb-0">
            <i class="fa-solid fa-circle-check text-success me-1"></i>
            Đã gửi lúc <?= date('d/m/Y H:i', strtotime($existingFeedback['created_at'])) ?>
        </p>

        <?php else: ?>
        <!-- Form đánh giá -->
        <form method="post" novalidate id="feedbackForm">

            <!-- Star rating -->
            <div class="mb-4 text-center">
                <label class="form-label fw-semibold d-block mb-3">
                    Bạn hài lòng thế nào? <span class="text-danger">*</span>
                </label>
                <div class="star-rating d-inline-flex flex-row-reverse gap-1" role="group" aria-label="Chọn số sao">
                    <?php for ($i = 5; $i >= 1; $i--): ?>
                    <input type="radio" name="rating" id="star<?= $i ?>" value="<?= $i ?>"
                           class="visually-hidden star-radio" required>
                    <label for="star<?= $i ?>" class="star-label"
                           title="<?= ['', 'Rất tệ', 'Tệ', 'Bình thường', 'Hài lòng', 'Tuyệt vời'][$i] ?>">
                        <i class="fa-solid fa-star"></i>
                    </label>
                    <?php endfor; ?>
                </div>
                <div id="starLabel" class="small text-muted mt-2" style="min-height:1.4em;"></div>
            </div>

            <!-- Nội dung -->
            <div class="mb-4">
                <label for="content" class="form-label fw-semibold">Nhận xét của bạn</label>
                <textarea name="content" id="content" class="form-control" rows="4"
                          placeholder="Xe sạch không? Nhân viên có thân thiện? Thời gian chờ có lâu?…"
                          maxlength="1000"></textarea>
                <div class="text-end small text-muted mt-1">
                    <span id="charCount">0</span>/1000
                </div>
            </div>

            <button name="submit_feedback" class="btn btn-primary w-100">
                <i class="fa-solid fa-paper-plane me-2"></i>Gửi đánh giá
            </button>
        </form>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</div>
</main>

<style>
.star-label {
    font-size: 2.2rem;
    color: #ccc;
    cursor: pointer;
    transition: color .15s, transform .15s;
    padding: 0 2px;
    line-height: 1;
}
/* highlight on hover – vì flex-row-reverse nên dùng ~ thay vì + */
.star-rating:hover .star-label { color: #ffc107; }
.star-rating .star-label:hover ~ .star-label { color: #ccc; }
/* highlight khi checked */
.star-radio:checked ~ .star-label { color: #ffc107; }
.star-label:hover { transform: scale(1.15); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const labels  = ['', 'Rất tệ 😞', 'Tệ 😕', 'Bình thường 😐', 'Hài lòng 😊', 'Tuyệt vời 🤩'];
    const radios  = document.querySelectorAll('.star-radio');
    const starLbl = document.getElementById('starLabel');

    radios.forEach(radio => {
        radio.addEventListener('change', function () {
            if (starLbl) starLbl.textContent = labels[this.value] ?? '';
        });
    });

    // Character counter
    const textarea  = document.getElementById('content');
    const charCount = document.getElementById('charCount');
    if (textarea && charCount) {
        textarea.addEventListener('input', function () {
            charCount.textContent = this.value.length;
        });
    }

    // Validate star chọn trước khi submit
    const form = document.getElementById('feedbackForm');
    if (form) {
        form.addEventListener('submit', function (e) {
            const checked = document.querySelector('.star-radio:checked');
            if (!checked) {
                e.preventDefault();
                alert('Vui lòng chọn số sao trước khi gửi đánh giá.');
            }
        });
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>