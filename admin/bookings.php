<?php include "admin_header.php";
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = (int)$_POST['order_id'];
    $new = $_POST['status'];
    $old = scalar($conn, "SELECT status FROM orders WHERE id=$id", '');
    $stmt = $conn->prepare("UPDATE orders SET status=? WHERE id=?");
    $stmt->bind_param("si", $new, $id);
    $stmt->execute();

    $adminId = (int)$_SESSION['admin']['id'];
    $note = "Cập nhật từ trang quản trị";
    $stmt = $conn->prepare("INSERT INTO order_status_logs(order_id,old_status,new_status,changed_by,note) VALUES(?,?,?,?,?)");
    $stmt->bind_param("issis", $id, $old, $new, $adminId, $note);
    $stmt->execute();
    $message = "Đã cập nhật trạng thái lịch đặt.";
    // Chỉ cộng điểm khi trạng thái MỚI là COMPLETED và trạng thái CŨ chưa phải là COMPLETED (tránh cộng dồn nhiều lần)
    if ($new === 'COMPLETED' && $old !== 'COMPLETED') {
        // Lấy thông tin khách hàng và tổng tiền của đơn
        $res_order = $conn->query("SELECT customer_id, total FROM orders WHERE id = $id");
        if ($res_order && $row_order = $res_order->fetch_assoc()) {
            $customer_id = (int)$row_order['customer_id'];
            $total_price = (int)$row_order['total'];

            // Tính điểm: Cứ 100.000đ được 10 điểm
            $points_earned = floor($total_price / 100000) * 10;

            if ($points_earned > 0 && $customer_id > 0) {
                // 1. Cập nhật cộng điểm vào bảng users (Dùng COALESCE phòng trường hợp điểm đang là NULL)
                $conn->query("UPDATE users SET points = COALESCE(points, 0) + $points_earned WHERE id = $customer_id");

                // 2. Lấy số điểm mới nhất để xét hạng
                $res_pts = $conn->query("SELECT points FROM users WHERE id = $customer_id");
                if ($res_pts && $row_pts = $res_pts->fetch_assoc()) {
                    $current_points = (int)$row_pts['points'];
                    
                    // 3. Logic xét hạng (Nhóm bạn có thể tự đổi mốc điểm)
                    $new_rank = 'Bình thường';
                    if ($current_points >= 1000) {
                        $new_rank = 'Kim Cương';
                    } elseif ($current_points >= 500) {
                        $new_rank = 'Vàng';
                    } elseif ($current_points >= 200) {
                        $new_rank = 'Bạc';
                    }

                    // 4. Cập nhật hạng vào bảng users
                    $stmt_rank = $conn->prepare("UPDATE users SET rank = ? WHERE id = ?");
                    $stmt_rank->bind_param("si", $new_rank, $customer_id);
                    $stmt_rank->execute();
                    
                    // (Nếu có bảng khác cần cập nhật, bạn copy 3 dòng prepare, bind_param, execute ở trên và sửa câu SQL)
                }
            }
        }
    }
}
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
$where = "WHERE 1=1";
if ($search !== '') {
    $safe = $conn->real_escape_string($search);
    $where .= " AND (c.name LIKE '%$safe%' OR c.phone LIKE '%$safe%' OR v.license_plate LIKE '%$safe%')";
}
if ($status !== '') {
    $safe = $conn->real_escape_string($status);
    $where .= " AND o.status='$safe'";
}
$orders = fetch_all($conn->query("
SELECT o.*, c.name customer_name, c.phone, st.name staff_name, v.license_plate, v.vehicle_type, b.name branch_name,
       GROUP_CONCAT(s.name SEPARATOR ', ') service_names, p.status payment_status, p.method
FROM orders o
JOIN users c ON c.id=o.customer_id
LEFT JOIN users st ON st.id=o.staff_id
JOIN vehicles v ON v.id=o.vehicle_id
JOIN branches b ON b.id=o.branch_id
LEFT JOIN order_details od ON od.order_id=o.id
LEFT JOIN services s ON s.id=od.service_id
LEFT JOIN payments p ON p.order_id=o.id
$where
GROUP BY o.id
ORDER BY o.booking_time DESC
"));
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold">Duyệt lịch đặt xe</h4>
    <span class="badge text-bg-primary px-3 py-2"><?= count($orders) ?> lịch</span>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<div class="card cardx p-4 mb-4">
<form class="row g-3">
    <div class="col-md-6"><input name="search" class="form-control" placeholder="Tìm tên, SĐT, biển số..." value="<?= e($search) ?>"></div>
    <div class="col-md-4">
        <select name="status" class="form-select">
            <option value="">Tất cả trạng thái</option>
            <?php foreach(['PENDING','CONFIRMED','PROCESSING','COMPLETED','CANCELLED'] as $st): ?>
                <option value="<?= $st ?>" <?= $status===$st?'selected':'' ?>><?= status_text($st) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2 d-grid"><button class="btn btn-primary">Lọc</button></div>
</form>
</div>
<div class="card cardx p-4">
<div class="table-responsive">
<table class="table align-middle">
<thead><tr><th>Khách hàng</th><th>Xe</th><th>Dịch vụ</th><th>Chi nhánh</th><th>Thời gian</th><th>Thanh toán</th><th>Trạng thái</th><th>Cập nhật</th></tr></thead>
<tbody>
<?php foreach($orders as $o): ?>
<tr>
<td><strong><?= e($o['customer_name']) ?></strong><br><small><?= e($o['phone']) ?></small></td>
<td><span class="badge text-bg-dark"><?= e($o['license_plate']) ?></span><br><small><?= e($o['vehicle_type']) ?></small></td>
<td><?= e($o['service_names']) ?><br><strong><?= money($o['total']) ?></strong></td>
<td><?= e($o['branch_name']) ?><br><small>NV: <?= e($o['staff_name'] ?: 'Chưa phân công') ?></small></td>
<td><?= date('d/m/Y H:i', strtotime($o['booking_time'])) ?></td>
<td><?= status_badge($o['payment_status'] ?? 'UNPAID') ?><br><small><?= e($o['method'] ?? '') ?></small></td>
<td><?= status_badge($o['status']) ?></td>
<td>
<form method="post" class="d-flex gap-2">
<input type="hidden" name="order_id" value="<?= $o['id'] ?>">
<select name="status" class="form-select form-select-sm">
<?php foreach(['PENDING','CONFIRMED','PROCESSING','COMPLETED','CANCELLED'] as $st): ?>
<option value="<?= $st ?>" <?= $o['status']===$st?'selected':'' ?>><?= status_text($st) ?></option>
<?php endforeach; ?>
</select>
<button name="update_status" class="btn btn-sm btn-primary">Lưu</button>
</form>
</td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div></div>
<?php include "admin_footer.php"; ?>
