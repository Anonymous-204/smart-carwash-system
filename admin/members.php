<?php include "admin_header.php";
$search = trim($_GET['search'] ?? '');
$where = "WHERE u.role='customer'";
if ($search !== '') {
    $safe = $conn->real_escape_string($search);
    $where .= " AND (u.name LIKE '%$safe%' OR u.phone LIKE '%$safe%' OR u.email LIKE '%$safe%' OR v.license_plate LIKE '%$safe%')";
}
$members = fetch_all($conn->query("
SELECT u.*, r.name rank_name, r.discount,
       GROUP_CONCAT(DISTINCT CONCAT(v.brand,' ',v.vehicle_type,' - ',v.license_plate) SEPARATOR '<br>') vehicles,
       COUNT(DISTINCT o.id) total_orders,
       COALESCE(SUM(CASE WHEN o.status='COMPLETED' THEN o.total ELSE 0 END),0) spent
FROM users u
LEFT JOIN ranks r ON r.id=u.rank_id
LEFT JOIN vehicles v ON v.user_id=u.id
LEFT JOIN orders o ON o.customer_id=u.id
$where
GROUP BY u.id
ORDER BY u.point DESC, u.created_at DESC
"));
?>
<div class="d-flex justify-content-between align-items-center mb-4">
<h4 class="fw-bold">Khách hàng thành viên</h4>
<span class="badge text-bg-primary px-3 py-2"><?= count($members) ?> khách hàng</span>
</div>
<div class="card cardx p-4 mb-4">
<form class="row g-3"><div class="col-md-10"><input name="search" class="form-control" placeholder="Tìm tên, email, SĐT, biển số..." value="<?= e($search) ?>"></div><div class="col-md-2 d-grid"><button class="btn btn-primary">Tìm</button></div></form>
</div>
<div class="card cardx p-4"><table class="table align-middle">
<thead><tr><th>Khách hàng</th><th>Liên hệ</th><th>Xe</th><th>Hạng</th><th>Điểm</th><th>Đơn hàng</th><th>Chi tiêu</th></tr></thead>
<tbody>
<?php foreach($members as $m): ?>
<tr>
<td><strong><?= e($m['name']) ?></strong><br><small><?= e($m['email']) ?></small></td>
<td><?= e($m['phone']) ?><br><small><?= e($m['address']) ?></small></td>
<td><?= $m['vehicles'] ?: '<span class="text-muted">Chưa có xe</span>' ?></td>
<td><span class="badge text-bg-warning"><?= e($m['rank_name'] ?: 'Chưa xếp hạng') ?></span><br><small>Giảm <?= (int)$m['discount'] ?>%</small></td>
<td class="fw-bold text-primary"><?= number_format((int)$m['point']) ?></td>
<td><?= (int)$m['total_orders'] ?></td>
<td class="fw-bold"><?= money($m['spent']) ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php include "admin_footer.php"; ?>
