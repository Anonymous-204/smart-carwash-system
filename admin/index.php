<?php include "admin_header.php";

$totalRevenue = scalar($conn, "SELECT COALESCE(SUM(total),0) FROM orders WHERE status='COMPLETED'");
$totalPaid = scalar($conn, "SELECT COALESCE(SUM(amount),0) FROM payments WHERE status='PAID'");
$totalOrders = scalar($conn, "SELECT COUNT(*) FROM orders");
$pendingOrders = scalar($conn, "SELECT COUNT(*) FROM orders WHERE status='PENDING'");
$totalCustomers = scalar($conn, "SELECT COUNT(*) FROM users WHERE role='customer'");
$totalServices = scalar($conn, "SELECT COUNT(*) FROM services WHERE is_active=1");

$statusRows = fetch_all($conn->query("SELECT status, COUNT(*) total FROM orders GROUP BY status"));
$statusMap = [];
foreach ($statusRows as $r) $statusMap[$r['status']] = (int)$r['total'];
$statusLabels = ['PENDING','CONFIRMED','PROCESSING','COMPLETED','CANCELLED'];
$statusData = array_map(fn($s) => $statusMap[$s] ?? 0, $statusLabels);

$recent = fetch_all($conn->query("
    SELECT o.*, c.name customer_name, c.phone, v.license_plate, v.vehicle_type, b.name branch_name,
           GROUP_CONCAT(s.name SEPARATOR ', ') service_names,
           p.status payment_status
    FROM orders o
    JOIN users c ON c.id=o.customer_id
    JOIN vehicles v ON v.id=o.vehicle_id
    JOIN branches b ON b.id=o.branch_id
    LEFT JOIN order_details od ON od.order_id=o.id
    LEFT JOIN services s ON s.id=od.service_id
    LEFT JOIN payments p ON p.order_id=o.id
    GROUP BY o.id
    ORDER BY o.booking_time DESC
    LIMIT 6
"));
?>
<div class="row g-4 mb-4">
    <?php
    $stats = [
        ['Doanh thu hoàn thành', money($totalRevenue), 'fa-money-bill-wave', 'success'],
        ['Đã thanh toán', money($totalPaid), 'fa-credit-card', 'primary'],
        ['Tổng lịch đặt', $totalOrders . ' lịch', 'fa-calendar-check', 'warning'],
        ['Khách hàng', $totalCustomers . ' người', 'fa-users', 'info'],
    ];
    foreach ($stats as $s): ?>
    <div class="col-md-3">
        <div class="card cardx stat h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div class="text-muted fw-semibold"><?= e($s[0]) ?></div>
                    <div class="num mt-1"><?= e($s[1]) ?></div>
                    <?php if ($s[0] === 'Tổng lịch đặt'): ?><small class="text-warning"><?= $pendingOrders ?> lịch chờ duyệt</small><?php endif; ?>
                </div>
                <div class="iconbox"><i class="fa-solid <?= e($s[2]) ?>"></i></div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card cardx p-4 h-100">
            <h5 class="fw-bold mb-3">Lịch đặt mới nhất</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Khách hàng</th><th>Xe</th><th>Dịch vụ</th><th>Thời gian</th><th>Tổng tiền</th><th>Trạng thái</th></tr></thead>
                    <tbody>
                    <?php foreach ($recent as $row): ?>
                        <tr>
                            <td><strong><?= e($row['customer_name']) ?></strong><br><small class="text-muted"><?= e($row['phone']) ?></small></td>
                            <td><span class="badge text-bg-dark"><?= e($row['license_plate']) ?></span><br><small><?= e($row['vehicle_type']) ?></small></td>
                            <td><?= e($row['service_names']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($row['booking_time'])) ?></td>
                            <td class="fw-bold"><?= money($row['total']) ?></td>
                            <td><?= status_badge($row['status']) ?><br><?= status_badge($row['payment_status'] ?? 'UNPAID') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card cardx p-4 h-100">
            <h5 class="fw-bold mb-3">Phân bổ trạng thái</h5>
            <canvas id="statusChart" height="240"></canvas>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-md-4"><div class="card cardx p-4"><div class="text-muted">Dịch vụ đang bán</div><div class="num"><?= $totalServices ?></div></div></div>
    <div class="col-md-4"><div class="card cardx p-4"><div class="text-muted">Chi nhánh</div><div class="num"><?= scalar($conn, "SELECT COUNT(*) FROM branches") ?></div></div></div>
    <div class="col-md-4"><div class="card cardx p-4"><div class="text-muted">Đánh giá trung bình</div><div class="num"><?= number_format((float)scalar($conn, "SELECT COALESCE(AVG(rating),0) FROM feedbacks"), 1) ?>/5</div></div></div>
</div>

<script>
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: ['Chờ duyệt','Đã xác nhận','Đang xử lý','Hoàn thành','Đã hủy'],
        datasets: [{data: <?= json_encode($statusData) ?>}]
    },
    options: {plugins:{legend:{position:'bottom'}}}
});
</script>
<?php include "admin_footer.php"; ?>
