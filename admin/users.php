<?php include "admin_header.php";
$message = '';
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    if ($id !== (int)$_SESSION['admin']['id']) {
        $conn->query("UPDATE users SET is_active = 1 - is_active WHERE id=$id AND role IN ('admin','staff')");
        $message = "Đã cập nhật trạng thái tài khoản.";
    }
}
$users = fetch_all($conn->query("
SELECT u.*, b.name branch_name
FROM users u
LEFT JOIN branches b ON b.id=u.branch_id
WHERE u.role IN ('admin','staff')
ORDER BY FIELD(u.role,'admin','staff'), u.name
"));
?>
<h4 class="fw-bold mb-4">Tài khoản quản trị & nhân viên</h4>
<?php if($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
<div class="card cardx p-4">
<table class="table align-middle">
<thead><tr><th>Họ tên</th><th>Email</th><th>Vai trò</th><th>Chi nhánh</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
<tbody>
<?php foreach($users as $u): ?>
<tr>
<td class="fw-bold"><?= e($u['name']) ?></td>
<td><?= e($u['email']) ?></td>
<td><span class="badge <?= $u['role']==='admin'?'text-bg-primary':'text-bg-info' ?>"><?= e($u['role']) ?></span></td>
<td><?= e($u['branch_name'] ?: '-') ?></td>
<td><?= $u['is_active'] ? '<span class="badge text-bg-success">Hoạt động</span>' : '<span class="badge text-bg-danger">Đã khóa</span>' ?></td>
<td class="text-end">
<?php if ((int)$u['id'] !== (int)$_SESSION['admin']['id']): ?>
<a class="btn btn-sm btn-outline-secondary" href="?toggle=<?= $u['id'] ?>"><?= $u['is_active'] ? 'Khóa' : 'Mở khóa' ?></a>
<?php endif; ?>
</td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php include "admin_footer.php"; ?>
