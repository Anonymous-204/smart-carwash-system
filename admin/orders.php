<?php include "admin_header.php";
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_paid'])) {
    $id = (int)$_POST['payment_id'];
    $method = $_POST['method'];
    $stmt = $conn->prepare("UPDATE payments SET status='PAID', method=?, paid_at=NOW(), transaction_code=CONCAT('PAY-', id) WHERE id=?");
    $stmt->bind_param("si", $method, $id);
    $stmt->execute();
    $message = "Đã ghi nhận thanh toán.";
}
$rows = fetch_all($conn->query("
SELECT o.id order_id, o.booking_time, o.total, o.status order_status, c.name customer_name, c.phone,
       p.id payment_id, p.method, p.amount, p.status payment_status, p.paid_at
FROM orders o
JOIN users c ON c.id=o.customer_id
LEFT JOIN payments p ON p.order_id=o.id
ORDER BY o.created_at DESC
"));
?>
<h4 class="fw-bold mb-4">Hóa đơn & thanh toán</h4>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<div class="card cardx p-4">
<table class="table align-middle">
<thead><tr><th>Mã đơn</th><th>Khách hàng</th><th>Thời gian</th><th>Tổng tiền</th><th>Trạng thái đơn</th><th>Thanh toán</th><th>Thao tác</th></tr></thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
<td>#<?= $r['order_id'] ?></td>
<td><strong><?= e($r['customer_name']) ?></strong><br><small><?= e($r['phone']) ?></small></td>
<td><?= date('d/m/Y H:i', strtotime($r['booking_time'])) ?></td>
<td class="fw-bold"><?= money($r['total']) ?></td>
<td><?= status_badge($r['order_status']) ?></td>
<td><?= status_badge($r['payment_status'] ?? 'UNPAID') ?><br><small><?= e($r['method']) ?> <?= $r['paid_at'] ? date('d/m/Y H:i', strtotime($r['paid_at'])) : '' ?></small></td>
<td>
<?php if (($r['payment_status'] ?? 'UNPAID') !== 'PAID' && $r['payment_id']): ?>
<form method="post" class="d-flex gap-2">
<input type="hidden" name="payment_id" value="<?= $r['payment_id'] ?>">
<select name="method" class="form-select form-select-sm"><option value="CASH">Tiền mặt</option><option value="BANK_TRANSFER">Chuyển khoản</option><option value="MOMO">MoMo</option><option value="CARD">Thẻ</option></select>
<button name="mark_paid" class="btn btn-sm btn-success">Đã thanh toán</button>
</form>
<?php else: ?><span class="text-muted">Hoàn tất</span><?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody></table>
</div>
<?php include "admin_footer.php"; ?>
