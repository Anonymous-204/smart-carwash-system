<?php
require_once __DIR__ . '/../db.php';
require_customer_login();
$customer = current_customer();
$uid      = (int)$customer['id'];

$message = '';
$errors  = [];
// ---------------------------------------------------------------
// HELPER: Calculate price with discount based on customer rank
// ---------------------------------------------------------------
function calculate_price_with_discount($conn, $service_price, $customer_id) {
    // Get customer's discount from rank
    $discount_data = $conn->query(
        "SELECT r.discount FROM users u 
         LEFT JOIN ranks r ON r.id = u.rank_id 
         WHERE u.id = $customer_id"
    )->fetch_assoc();
    
    $discount_percent = (int)($discount_data['discount'] ?? 0);
    $discount_amount = (int)($service_price * $discount_percent / 100);
    $final_price = $service_price - $discount_amount;
    
    return [
        'original' => $service_price,
        'discount_percent' => $discount_percent,
        'discount_amount' => $discount_amount,
        'final' => $final_price
    ];
}
// ---------------------------------------------------------------
// THÊM XE
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $brand        = trim($_POST['brand']         ?? '');
    $vehicleType  = trim($_POST['vehicle_type']  ?? '');
    $licensePlate = trim($_POST['license_plate'] ?? '');
    $color        = trim($_POST['color']         ?? '');

    if ($brand && $vehicleType && $licensePlate) {
        $stmt = $conn->prepare(
            "INSERT INTO vehicles (user_id, brand, vehicle_type, license_plate, color)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issss', $uid, $brand, $vehicleType, $licensePlate, $color);
        $stmt->execute();
        $stmt->close();
        $message = 'Đã thêm xe thành công.';
    } else {
        $errors[] = 'Vui lòng nhập đầy đủ thông tin xe.';
    }
}

// ---------------------------------------------------------------
// HỦY LỊCH
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $oid = (int)$_POST['order_id'];
    $stmt = $conn->prepare(
        "SELECT id, status FROM orders WHERE id = ? AND customer_id = ? LIMIT 1"
    );
    $stmt->bind_param('ii', $oid, $uid);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order && in_array($order['status'], ['PENDING', 'CONFIRMED'])) {
        $stmt = $conn->prepare("UPDATE orders SET status='CANCELLED' WHERE id=?");
        $stmt->bind_param('i', $oid);
        $stmt->execute();
        $stmt->close();

        $newStatus = 'CANCELLED';
        $oldStatus = $order['status'];
        $note      = 'Khách hàng tự hủy';
        $stmt = $conn->prepare(
            "INSERT INTO order_status_logs (order_id, old_status, new_status, changed_by, note)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issss', $oid, $oldStatus, $newStatus, $uid, $note);
        $stmt->execute();
        $stmt->close();
        $message = 'Đã hủy lịch thành công.';
    } else {
        $errors[] = 'Không thể hủy lịch ở trạng thái này.';
    }
}

// ---------------------------------------------------------------
// ĐẶT LỊCH
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_now'])) {
    $serviceId   = (int)$_POST['service_id'];
    $vehicleId   = (int)$_POST['vehicle_id'];
    $branchId    = (int)$_POST['branch_id'];
    $bookingTime = trim($_POST['booking_time'] ?? '');
    $pickup      = isset($_POST['pickup_by_staff'])  ? 1 : 0;
    $returnStaff = isset($_POST['return_by_staff'])  ? 1 : 0;
    $note        = trim($_POST['note'] ?? '');

    if (!$serviceId)   $errors[] = 'Vui lòng chọn dịch vụ.';
    if (!$vehicleId)   $errors[] = 'Vui lòng chọn xe.';
    if (!$branchId)    $errors[] = 'Vui lòng chọn chi nhánh.';
    if (!$bookingTime) $errors[] = 'Vui lòng chọn thời gian.';
    elseif (strtotime($bookingTime) < time()) $errors[] = 'Thời gian đặt lịch phải ở tương lai.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT price FROM services WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param('i', $serviceId);
        $stmt->execute();
        $svc = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$svc) {
            $errors[] = 'Dịch vụ không hợp lệ.';
        } else {
            $total  = (int)$svc['price'];
            $status = 'PENDING';

            $stmt = $conn->prepare(
                "INSERT INTO orders
                 (customer_id, vehicle_id, branch_id, booking_time, total, status,
                  pickup_by_staff, return_by_staff, note)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iiisisiss',
                $uid, $vehicleId, $branchId, $bookingTime,
                $total, $status, $pickup, $returnStaff, $note
            );
            $stmt->execute();
            $newOrderId = $stmt->insert_id;
            $stmt->close();

            // Chi tiết đơn
            $stmt = $conn->prepare(
                "INSERT INTO order_details (order_id, service_id, price) VALUES (?, ?, ?)"
            );
            $stmt->bind_param('iii', $newOrderId, $serviceId, $total);
            $stmt->execute();
            $stmt->close();

            // Payment record
            $stmt = $conn->prepare(
                "INSERT INTO payments (order_id, method, amount, status) VALUES (?, 'CASH', ?, 'UNPAID')"
            );
            $stmt->bind_param('ii', $newOrderId, $total);
            $stmt->execute();
            $stmt->close();

            // Log
            $logStatus = 'PENDING';
            $logNote   = 'Khách tự đặt lịch';
            $stmt = $conn->prepare(
                "INSERT INTO order_status_logs (order_id, old_status, new_status, changed_by, note)
                 VALUES (?, NULL, ?, ?, ?)"
            );
            $stmt->bind_param('isis', $newOrderId, $logStatus, $uid, $logNote);
            $stmt->execute();
            $stmt->close();

            $message = 'Đặt lịch thành công! Chúng tôi sẽ xác nhận sớm.';
        }
    }
}

// ---------------------------------------------------------------
// LẤY DỮ LIỆU
// ---------------------------------------------------------------
$services = fetch_all($conn->query(
    "SELECT * FROM services WHERE is_active = 1 ORDER BY price ASC"
));
$vehicles = fetch_all($conn->query(
    "SELECT * FROM vehicles WHERE user_id = $uid ORDER BY created_at DESC"
));
$branches = fetch_all($conn->query("SELECT * FROM branches ORDER BY id ASC"));

$myOrders = fetch_all($conn->query("
    SELECT o.*, b.name branch_name, v.license_plate, v.vehicle_type,
           GROUP_CONCAT(s.name SEPARATOR ', ') service_names,
           p.status payment_status, p.method
    FROM orders o
    JOIN branches b ON b.id = o.branch_id
    JOIN vehicles v ON v.id = o.vehicle_id
    LEFT JOIN order_details od ON od.order_id = o.id
    LEFT JOIN services s ON s.id = od.service_id
    LEFT JOIN payments p ON p.order_id = o.id
    WHERE o.customer_id = $uid
    GROUP BY o.id
    ORDER BY o.booking_time DESC
    LIMIT 20
"));

$preSelectService = (int)($_GET['book'] ?? 0);

include __DIR__ . '/includes/header.php';
?>
<main>
<div class="container py-4">

    <?php if (isset($_GET['welcome'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4">
        <i class="fa-solid fa-party-horn me-2"></i>
        Chào mừng <strong><?= e($customer['name']) ?></strong> đến với Smart Carwash!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4">
        <i class="fa-solid fa-circle-check me-1"></i><?= e($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4">
        <?php foreach ($errors as $err): ?>
            <div><i class="fa-solid fa-circle-exclamation me-1"></i><?= e($err) ?></div>
        <?php endforeach; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <div class="col-lg-5">

            <div class="card cardx p-4">
                <h5 class="fw-bold mb-4">
                    <i class="fa-solid fa-calendar-plus text-primary me-2"></i>Đặt lịch rửa xe
                </h5>

                <?php if (empty($vehicles)): ?>
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        Bạn chưa có xe nào. Vui lòng thêm xe bên dưới trước khi đặt lịch.
                    </div>
                <?php else: ?>
                <form method="post" novalidate>
               //line 219                       <div class="mb-3">
                        <label class="form-label fw-semibold">Dịch vụ <span class="text-danger">*</span></label>
                        <select name="service_id" class="form-select" id="serviceSelect" required>
                            <option value="">-- Chọn dịch vụ --</option>
                            <?php foreach ($services as $s): 
                                $pricing = calculate_price_with_discount($conn, (int)$s['price'], $uid);
                            ?>
                                <option value="<?= $s['id'] ?>"
                                    data-original-price="<?= $pricing['original'] ?>"
                                    data-final-price="<?= $pricing['final'] ?>"
                                    data-discount-amount="<?= $pricing['discount_amount'] ?>"
                                    data-discount-percent="<?= $pricing['discount_percent'] ?>"
                                    <?= $preSelectService===(int)$s['id']?'selected':'' ?>>
                                    <?= e($s['name']) ?> – 
                                    <?php if ($pricing['discount_percent'] > 0): ?>
                                        <span style="text-decoration: line-through;"><?= money($pricing['original']) ?></span>
                                        <span style="color: red;">-<?= money($pricing['discount_amount']) ?></span>
                                        <strong style="color: green;"><?= money($pricing['final']) ?></strong>
                                    <?php else: ?>
                                        <?= money($pricing['original']) ?>
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <!-- Display price breakdown below select -->
                        <div id="priceBreakdown" class="mt-2 p-2" style="background: #f8f9fa; border-radius: 4px; display: none;">
                            <div class="small mb-1"><strong>Giá gốc:</strong> <span id="originalPrice"></span></div>
                            <div class="small mb-1" id="discountRow" style="display: none;">
                                <strong>Giảm <span id="discountPercent"></span>%:</strong> 
                                <span style="color: red;">-<span id="discountAmount"></span></span>
                            </div>
                            <div class="small" style="border-top: 1px solid #ddd; padding-top: 8px; margin-top: 8px;">
                                <strong style="color: green;">Thành tiền:</strong> <span id="finalPrice"></span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Xe <span class="text-danger">*</span></label>
                        <select name="vehicle_id" class="form-select" required>
                            <option value="">-- Chọn xe --</option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?= $v['id'] ?>">
                                    <?= e($v['brand'].' '.$v['vehicle_type']) ?> – <?= e($v['license_plate']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chi nhánh <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">-- Chọn chi nhánh --</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Thời gian <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="booking_time" class="form-control"
                               min="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>

                    <div class="mb-3">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="pickup_by_staff" id="pickup">
                            <label class="form-check-label" for="pickup">
                                <i class="fa-solid fa-truck-pickup text-primary me-1"></i>Nhân viên đến đón xe
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="return_by_staff" id="returnCar">
                            <label class="form-check-label" for="returnCar">
                                <i class="fa-solid fa-rotate-left text-primary me-1"></i>Nhân viên trả xe tận nơi
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <textarea name="note" class="form-control" rows="2"
                                  placeholder="Yêu cầu đặc biệt, địa chỉ..."></textarea>
                    </div>

                    <button name="book_now" class="btn btn-primary w-100">
                        <i class="fa-solid fa-calendar-check me-2"></i>Xác nhận đặt lịch
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <div class="card cardx p-4 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fa-solid fa-car text-primary me-2"></i>Xe của tôi
                    </h5>
                    <button class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="collapse" data-bs-target="#addVehicleForm">
                        <i class="fa-solid fa-plus me-1"></i>Thêm xe
                    </button>
                </div>

                <?php if (empty($vehicles)): ?>
                    <p class="text-muted small mb-0">Chưa có xe nào.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($vehicles as $v): ?>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge text-bg-dark me-2"><?= e($v['license_plate']) ?></span>
                            <?= e($v['brand'].' '.$v['vehicle_type']) ?>
                            <?php if ($v['color']): ?>
                                <small class="text-muted">– <?= e($v['color']) ?></small>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <div class="collapse mt-3" id="addVehicleForm">
                    <hr>
                    <form method="post" novalidate>
                        <div class="row g-2">
                            <div class="col-6">
                                <input name="brand" class="form-control form-control-sm"
                                       placeholder="Hãng xe (Toyota...)" required>
                            </div>
                            <div class="col-6">
                                <input name="vehicle_type" class="form-control form-control-sm"
                                       placeholder="Loại xe (Sedan...)" required>
                            </div>
                            <div class="col-6">
                                <input name="license_plate" class="form-control form-control-sm"
                                       placeholder="Biển số" required>
                            </div>
                            <div class="col-6">
                                <input name="color" class="form-control form-control-sm"
                                       placeholder="Màu sắc">
                            </div>
                            <div class="col-12">
                                <button name="add_vehicle" class="btn btn-sm btn-success w-100">
                                    <i class="fa-solid fa-floppy-disk me-1"></i>Lưu xe
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card cardx p-4">
                <h5 class="fw-bold mb-4">
                    <i class="fa-solid fa-receipt text-primary me-2"></i>Lịch đặt của tôi
                </h5>

                <?php if (empty($myOrders)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fa-solid fa-calendar-xmark fa-2x mb-2 d-block"></i>
                        Bạn chưa có lịch đặt nào.
                    </div>
                <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($myOrders as $o): ?>
                    <div class="border rounded-3 p-3" data-order-block="<?= $o['id'] ?>">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                            <div>
                                <span class="fw-bold"><?= e($o['service_names']) ?></span><br>
                                <small class="text-muted">
                                    <i class="fa-solid fa-location-dot me-1"></i><?= e($o['branch_name']) ?>
                                    &nbsp;|&nbsp;
                                    <i class="fa-solid fa-car me-1"></i><?= e($o['license_plate']) ?>
                                </small>
                                <!-- Show points earned if order is completed -->
                                <?php if ($o['status'] === 'COMPLETED'): 
                                    $points_earned = (int)($o['total'] / 100000) * 10;
                                    if ($points_earned > 0): ?>
                                    <div class="mt-2">
                                        <span class="badge text-bg-success">
                                            <i class="fa-solid fa-star me-1"></i>Nhận <?= $points_earned ?> điểm
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <span class="order-status-badge"><?= status_badge($o['status']) ?></span>
                                <br>
                                <small class="text-muted">
                                    <?= date('d/m/Y H:i', strtotime($o['booking_time'])) ?>
                                </small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <strong class="text-primary"><?= money($o['total']) ?></strong>
                                &nbsp;
                                <span class="order-payment-badge"><?= status_badge($o['payment_status'] ?? 'UNPAID') ?></span>
                            </span>
                            
                            <div class="cancel-action-container">
                                <?php if (in_array($o['status'], ['PENDING','CONFIRMED'])): ?>
                                    <form method="post"
                                          onsubmit="return confirm('Bạn chắc muốn hủy lịch này?')">
                                        <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                        <button name="cancel_order" class="btn btn-sm btn-outline-danger">
                                            <i class="fa-solid fa-xmark me-1"></i>Hủy
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($o['note']): ?>
                        <div class="mt-2 small text-muted">
                            <i class="fa-solid fa-note-sticky me-1"></i><?= e($o['note']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div></div></main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    // Gom toàn bộ ID của các đơn hàng đang có trên giao diện
    const orderBlocks = document.querySelectorAll('[data-order-block]');
    const orderIds = Array.from(orderBlocks).map(block => block.getAttribute('data-order-block'));

    // Nếu không có đơn hàng nào thì dừng, không gửi request ngầm làm gì
    if (orderIds.length === 0) return;

    // Cứ mỗi 3 giây (3000ms), gửi request ngầm kiểm tra xem Admin có đổi trạng thái không
    setInterval(function () {
        fetch('api_get_statuses.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_ids: orderIds })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Duyệt qua danh sách trạng thái mới từ server trả về
                data.orders.forEach(order => {
                    const block = document.querySelector(`[data-order-block="${order.id}"]`);
                    if (block) {
                        // 1. Cập nhật Badge trạng thái đơn hàng (nếu dữ liệu khác biệt)
                        const statusBadge = block.querySelector('.order-status-badge');
                        if (statusBadge && statusBadge.innerHTML.trim() !== order.status_html.trim()) {
                            statusBadge.innerHTML = order.status_html;
                        }

                        // 2. Cập nhật Badge trạng thái thanh toán (nếu dữ liệu khác biệt)
                        const paymentBadge = block.querySelector('.order-payment-badge');
                        if (paymentBadge && paymentBadge.innerHTML.trim() !== order.payment_html.trim()) {
                            paymentBadge.innerHTML = order.payment_html;
                        }

                        // 3. Tự hủy/ẩn nút "Hủy" nếu trạng thái đã chuyển sang Đang rửa, Đã xong,...
                        const cancelContainer = block.querySelector('.cancel-action-container');
                        if (cancelContainer) {
                            if (order.status !== 'PENDING' && order.status !== 'CONFIRMED') {
                                cancelContainer.innerHTML = ''; // Xóa sạch form nút Hủy
                            }
                        }
                    }
                });
            }
        })
        .catch(error => console.error('Lỗi kiểm tra Realtime trạng thái:', error));
    }, 3000); // Bạn có thể tăng/giảm thời gian delay tại đây (ví dụ 3000 = 3 giây)
});
</script>
<script>
document.getElementById('serviceSelect').addEventListener('change', function() {
    const option = this.options[this.selectedIndex];
    const originalPrice = option.dataset.originalPrice;
    const finalPrice = option.dataset.finalPrice;
    const discountAmount = option.dataset.discountAmount;
    const discountPercent = option.dataset.discountPercent;
    
    if (originalPrice) {
        document.getElementById('priceBreakdown').style.display = 'block';
        document.getElementById('originalPrice').textContent = new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(originalPrice).replace('₫', '').trim() + ' đ';
        
        if (discountPercent > 0) {
            document.getElementById('discountRow').style.display = 'block';
            document.getElementById('discountPercent').textContent = discountPercent;
            document.getElementById('discountAmount').textContent = new Intl.NumberFormat('vi-VN', {
                style: 'currency',
                currency: 'VND'
            }).format(discountAmount).replace('₫', '').trim() + ' đ';
        } else {
            document.getElementById('discountRow').style.display = 'none';
        }
        
        document.getElementById('finalPrice').textContent = new Intl.NumberFormat('vi-VN', {
            style: 'currency',
            currency: 'VND'
        }).format(finalPrice).replace('₫', '').trim() + ' đ';
    } else {
        document.getElementById('priceBreakdown').style.display = 'none';
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php
require_once __DIR__ . '/../db.php';
require_customer_login();
$customer = current_customer();
$uid      = (int)$customer['id'];

$message = '';
$errors  = [];

// ---------------------------------------------------------------
// THÊM XE
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $brand        = trim($_POST['brand']         ?? '');
    $vehicleType  = trim($_POST['vehicle_type']  ?? '');
    $licensePlate = trim($_POST['license_plate'] ?? '');
    $color        = trim($_POST['color']         ?? '');

    if ($brand && $vehicleType && $licensePlate) {
        $stmt = $conn->prepare(
            "INSERT INTO vehicles (user_id, brand, vehicle_type, license_plate, color)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issss', $uid, $brand, $vehicleType, $licensePlate, $color);
        $stmt->execute();
        $stmt->close();
        $message = 'Đã thêm xe thành công.';
    } else {
        $errors[] = 'Vui lòng nhập đầy đủ thông tin xe.';
    }
}

// ---------------------------------------------------------------
// HỦY LỊCH
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_order'])) {
    $oid = (int)$_POST['order_id'];
    $stmt = $conn->prepare(
        "SELECT id, status FROM orders WHERE id = ? AND customer_id = ? LIMIT 1"
    );
    $stmt->bind_param('ii', $oid, $uid);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($order && in_array($order['status'], ['PENDING', 'CONFIRMED'])) {
        $stmt = $conn->prepare("UPDATE orders SET status='CANCELLED' WHERE id=?");
        $stmt->bind_param('i', $oid);
        $stmt->execute();
        $stmt->close();

        $newStatus = 'CANCELLED';
        $oldStatus = $order['status'];
        $note      = 'Khách hàng tự hủy';
        $stmt = $conn->prepare(
            "INSERT INTO order_status_logs (order_id, old_status, new_status, changed_by, note)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param('issss', $oid, $oldStatus, $newStatus, $uid, $note);
        $stmt->execute();
        $stmt->close();
        $message = 'Đã hủy lịch thành công.';
    } else {
        $errors[] = 'Không thể hủy lịch ở trạng thái này.';
    }
}

// ---------------------------------------------------------------
// ĐẶT LỊCH
// ---------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_now'])) {
    $serviceId   = (int)$_POST['service_id'];
    $vehicleId   = (int)$_POST['vehicle_id'];
    $branchId    = (int)$_POST['branch_id'];
    $bookingTime = trim($_POST['booking_time'] ?? '');
    $pickup      = isset($_POST['pickup_by_staff'])  ? 1 : 0;
    $returnStaff = isset($_POST['return_by_staff'])  ? 1 : 0;
    $note        = trim($_POST['note'] ?? '');

    if (!$serviceId)   $errors[] = 'Vui lòng chọn dịch vụ.';
    if (!$vehicleId)   $errors[] = 'Vui lòng chọn xe.';
    if (!$branchId)    $errors[] = 'Vui lòng chọn chi nhánh.';
    if (!$bookingTime) $errors[] = 'Vui lòng chọn thời gian.';
    elseif (strtotime($bookingTime) < time()) $errors[] = 'Thời gian đặt lịch phải ở tương lai.';

    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT price FROM services WHERE id = ? AND is_active = 1 LIMIT 1");
        $stmt->bind_param('i', $serviceId);
        $stmt->execute();
        $svc = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if (!$svc) {
            $errors[] = 'Dịch vụ không hợp lệ.';
        } else {
            $total  = (int)$svc['price'];
            $status = 'PENDING';

            $stmt = $conn->prepare(
                "INSERT INTO orders
                 (customer_id, vehicle_id, branch_id, booking_time, total, status,
                  pickup_by_staff, return_by_staff, note)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iiisisiss',
                $uid, $vehicleId, $branchId, $bookingTime,
                $total, $status, $pickup, $returnStaff, $note
            );
            $stmt->execute();
            $newOrderId = $stmt->insert_id;
            $stmt->close();

            // Chi tiết đơn
            $stmt = $conn->prepare(
                "INSERT INTO order_details (order_id, service_id, price) VALUES (?, ?, ?)"
            );
            $stmt->bind_param('iii', $newOrderId, $serviceId, $total);
            $stmt->execute();
            $stmt->close();

            // Payment record
            $stmt = $conn->prepare(
                "INSERT INTO payments (order_id, method, amount, status) VALUES (?, 'CASH', ?, 'UNPAID')"
            );
            $stmt->bind_param('ii', $newOrderId, $total);
            $stmt->execute();
            $stmt->close();

            // Log
            $logStatus = 'PENDING';
            $logNote   = 'Khách tự đặt lịch';
            $stmt = $conn->prepare(
                "INSERT INTO order_status_logs (order_id, old_status, new_status, changed_by, note)
                 VALUES (?, NULL, ?, ?, ?)"
            );
            $stmt->bind_param('isis', $newOrderId, $logStatus, $uid, $logNote);
            $stmt->execute();
            $stmt->close();

            $message = 'Đặt lịch thành công! Chúng tôi sẽ xác nhận sớm.';
        }
    }
}

// ---------------------------------------------------------------
// LẤY DỮ LIỆU
// ---------------------------------------------------------------
$services = fetch_all($conn->query(
    "SELECT * FROM services WHERE is_active = 1 ORDER BY price ASC"
));
$vehicles = fetch_all($conn->query(
    "SELECT * FROM vehicles WHERE user_id = $uid ORDER BY created_at DESC"
));
$branches = fetch_all($conn->query("SELECT * FROM branches ORDER BY id ASC"));

$myOrders = fetch_all($conn->query("
    SELECT o.*, b.name branch_name, v.license_plate, v.vehicle_type,
           GROUP_CONCAT(s.name SEPARATOR ', ') service_names,
           p.status payment_status, p.method
    FROM orders o
    JOIN branches b ON b.id = o.branch_id
    JOIN vehicles v ON v.id = o.vehicle_id
    LEFT JOIN order_details od ON od.order_id = o.id
    LEFT JOIN services s ON s.id = od.service_id
    LEFT JOIN payments p ON p.order_id = o.id
    WHERE o.customer_id = $uid
    GROUP BY o.id
    ORDER BY o.booking_time DESC
    LIMIT 20
"));

$preSelectService = (int)($_GET['book'] ?? 0);

include __DIR__ . '/includes/header.php';
?>
<main>
<div class="container py-4">

    <?php if (isset($_GET['welcome'])): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4">
        <i class="fa-solid fa-party-horn me-2"></i>
        Chào mừng <strong><?= e($customer['name']) ?></strong> đến với Smart Carwash!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($message): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4">
        <i class="fa-solid fa-circle-check me-1"></i><?= e($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4">
        <?php foreach ($errors as $err): ?>
            <div><i class="fa-solid fa-circle-exclamation me-1"></i><?= e($err) ?></div>
        <?php endforeach; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ===== CỘT TRÁI: ĐẶT LỊCH + THÊM XE ===== -->
        <div class="col-lg-5">

            <!-- Form đặt lịch -->
            <div class="card cardx p-4">
                <h5 class="fw-bold mb-4">
                    <i class="fa-solid fa-calendar-plus text-primary me-2"></i>Đặt lịch rửa xe
                </h5>

                <?php if (empty($vehicles)): ?>
                    <div class="alert alert-warning">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>
                        Bạn chưa có xe nào. Vui lòng thêm xe bên dưới trước khi đặt lịch.
                    </div>
                <?php else: ?>
                <form method="post" novalidate>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Dịch vụ <span class="text-danger">*</span></label>
                        <select name="service_id" class="form-select" required>
                            <option value="">-- Chọn dịch vụ --</option>
                            <?php foreach ($services as $s): ?>
                                <option value="<?= $s['id'] ?>"
                                    <?= $preSelectService===(int)$s['id']?'selected':'' ?>>
                                    <?= e($s['name']) ?> – <?= money($s['price']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Xe <span class="text-danger">*</span></label>
                        <select name="vehicle_id" class="form-select" required>
                            <option value="">-- Chọn xe --</option>
                            <?php foreach ($vehicles as $v): ?>
                                <option value="<?= $v['id'] ?>">
                                    <?= e($v['brand'].' '.$v['vehicle_type']) ?> – <?= e($v['license_plate']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Chi nhánh <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select" required>
                            <option value="">-- Chọn chi nhánh --</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= e($b['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Thời gian <span class="text-danger">*</span></label>
                        <input type="datetime-local" name="booking_time" class="form-control"
                               min="<?= date('Y-m-d\TH:i') ?>" required>
                    </div>

                    <div class="mb-3">
                        <div class="form-check mb-1">
                            <input class="form-check-input" type="checkbox" name="pickup_by_staff" id="pickup">
                            <label class="form-check-label" for="pickup">
                                <i class="fa-solid fa-truck-pickup text-primary me-1"></i>Nhân viên đến đón xe
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="return_by_staff" id="returnCar">
                            <label class="form-check-label" for="returnCar">
                                <i class="fa-solid fa-rotate-left text-primary me-1"></i>Nhân viên trả xe tận nơi
                            </label>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Ghi chú</label>
                        <textarea name="note" class="form-control" rows="2"
                                  placeholder="Yêu cầu đặc biệt, địa chỉ..."></textarea>
                    </div>

                    <button name="book_now" class="btn btn-primary w-100">
                        <i class="fa-solid fa-calendar-check me-2"></i>Xác nhận đặt lịch
                    </button>
                </form>
                <?php endif; ?>
            </div>

            <!-- Xe của tôi -->
            <div class="card cardx p-4 mt-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">
                        <i class="fa-solid fa-car text-primary me-2"></i>Xe của tôi
                    </h5>
                    <button class="btn btn-sm btn-outline-primary"
                            data-bs-toggle="collapse" data-bs-target="#addVehicleForm">
                        <i class="fa-solid fa-plus me-1"></i>Thêm xe
                    </button>
                </div>

                <?php if (empty($vehicles)): ?>
                    <p class="text-muted small mb-0">Chưa có xe nào.</p>
                <?php else: ?>
                <ul class="list-group list-group-flush">
                    <?php foreach ($vehicles as $v): ?>
                    <li class="list-group-item px-0 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge text-bg-dark me-2"><?= e($v['license_plate']) ?></span>
                            <?= e($v['brand'].' '.$v['vehicle_type']) ?>
                            <?php if ($v['color']): ?>
                                <small class="text-muted">– <?= e($v['color']) ?></small>
                            <?php endif; ?>
                        </div>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <!-- Form thêm xe -->
                <div class="collapse mt-3" id="addVehicleForm">
                    <hr>
                    <form method="post" novalidate>
                        <div class="row g-2">
                            <div class="col-6">
                                <input name="brand" class="form-control form-control-sm"
                                       placeholder="Hãng xe (Toyota...)" required>
                            </div>
                            <div class="col-6">
                                <input name="vehicle_type" class="form-control form-control-sm"
                                       placeholder="Loại xe (Sedan...)" required>
                            </div>
                            <div class="col-6">
                                <input name="license_plate" class="form-control form-control-sm"
                                       placeholder="Biển số" required>
                            </div>
                            <div class="col-6">
                                <input name="color" class="form-control form-control-sm"
                                       placeholder="Màu sắc">
                            </div>
                            <div class="col-12">
                                <button name="add_vehicle" class="btn btn-sm btn-success w-100">
                                    <i class="fa-solid fa-floppy-disk me-1"></i>Lưu xe
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ===== CỘT PHẢI: LỊCH ĐÃ ĐẶT ===== -->
        <div class="col-lg-7">
            <div class="card cardx p-4">
                <h5 class="fw-bold mb-4">
                    <i class="fa-solid fa-receipt text-primary me-2"></i>Lịch đặt của tôi
                </h5>

                <?php if (empty($myOrders)): ?>
                    <div class="text-center text-muted py-5">
                        <i class="fa-solid fa-calendar-xmark fa-2x mb-2 d-block"></i>
                        Bạn chưa có lịch đặt nào.
                    </div>
                <?php else: ?>
                <div class="d-flex flex-column gap-3">
                    <?php foreach ($myOrders as $o): ?>
                    <div class="border rounded-3 p-3">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                            <div>
                                <span class="fw-bold"><?= e($o['service_names']) ?></span><br>
                                <small class="text-muted">
                                    <i class="fa-solid fa-location-dot me-1"></i><?= e($o['branch_name']) ?>
                                    &nbsp;|&nbsp;
                                    <i class="fa-solid fa-car me-1"></i><?= e($o['license_plate']) ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <?= status_badge($o['status']) ?>
                                <br>
                                <small class="text-muted">
                                    <?= date('d/m/Y H:i', strtotime($o['booking_time'])) ?>
                                </small>
                            </div>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span>
                                <strong class="text-primary"><?= money($o['total']) ?></strong>
                                &nbsp;<?= status_badge($o['payment_status'] ?? 'UNPAID') ?>
                            </span>
                            <?php if (in_array($o['status'], ['PENDING','CONFIRMED'])): ?>
                                <form method="post"
                                      onsubmit="return confirm('Bạn chắc muốn hủy lịch này?')">
                                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                                    <button name="cancel_order" class="btn btn-sm btn-outline-danger">
                                        <i class="fa-solid fa-xmark me-1"></i>Hủy
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        <?php if ($o['note']): ?>
                        <div class="mt-2 small text-muted">
                            <i class="fa-solid fa-note-sticky me-1"></i><?= e($o['note']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

    </div><!-- /.row -->
</div><!-- /.container -->
</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
