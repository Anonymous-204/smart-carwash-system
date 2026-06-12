<?php include "admin_header.php";
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_service'])) {
        $stmt = $conn->prepare("INSERT INTO services(name,duration,description,price,is_active) VALUES(?,?,?,?,1)");
        $stmt->bind_param("sisi", $_POST['name'], $_POST['duration'], $_POST['description'], $_POST['price']);
        $stmt->execute();
        $message = "Đã thêm dịch vụ mới.";
    }
    if (isset($_POST['edit_service'])) {
        $stmt = $conn->prepare("UPDATE services SET name=?, duration=?, description=?, price=?, is_active=? WHERE id=?");
        $stmt->bind_param("sisiii", $_POST['name'], $_POST['duration'], $_POST['description'], $_POST['price'], $_POST['is_active'], $_POST['id']);
        $stmt->execute();
        $message = "Đã cập nhật dịch vụ.";
    }
    if (isset($_POST['delete_service'])) {
        $stmt = $conn->prepare("DELETE FROM services WHERE id=?");
        $stmt->bind_param("i", $_POST['id']);
        $stmt->execute();
        $message = "Đã xóa dịch vụ.";
    }
}
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $conn->query("UPDATE services SET is_active = 1 - is_active WHERE id=$id");
    $message = "Đã đổi trạng thái dịch vụ.";
}
$services = fetch_all($conn->query("SELECT * FROM services ORDER BY is_active DESC, price ASC"));
?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold">Quản lý dịch vụ</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal"><i class="fa-solid fa-plus me-2"></i>Thêm dịch vụ</button>
</div>
<?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>

<div class="card cardx p-4">
<table class="table align-middle">
<thead><tr><th>Tên dịch vụ</th><th>Thời gian</th><th>Giá</th><th>Mô tả</th><th>Trạng thái</th><th class="text-end">Thao tác</th></tr></thead>
<tbody>
<?php foreach ($services as $s): ?>
<tr>
<td class="fw-bold"><?= e($s['name']) ?></td>
<td><?= (int)$s['duration'] ?> phút</td>
<td class="fw-bold text-primary"><?= money($s['price']) ?></td>
<td class="text-muted"><?= e(mb_strimwidth($s['description'],0,80,'...','UTF-8')) ?></td>
<td><?= $s['is_active'] ? '<span class="badge text-bg-success">Đang bán</span>' : '<span class="badge text-bg-secondary">Tạm ẩn</span>' ?></td>
<td class="text-end">
    <a class="btn btn-sm btn-outline-secondary" href="?toggle=<?= $s['id'] ?>"><?= $s['is_active'] ? 'Ẩn' : 'Hiện' ?></a>
    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#edit<?= $s['id'] ?>">Sửa</button>
</td>
</tr>
<div class="modal fade" id="edit<?= $s['id'] ?>" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content">
<div class="modal-header"><h5>Sửa dịch vụ</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input type="hidden" name="id" value="<?= $s['id'] ?>">
<label class="form-label">Tên</label><input name="name" class="form-control mb-2" value="<?= e($s['name']) ?>" required>
<label class="form-label">Thời gian</label><input name="duration" type="number" class="form-control mb-2" value="<?= $s['duration'] ?>" required>
<label class="form-label">Giá</label><input name="price" type="number" class="form-control mb-2" value="<?= $s['price'] ?>" required>
<label class="form-label">Mô tả</label><textarea name="description" class="form-control mb-2"><?= e($s['description']) ?></textarea>
<label class="form-label">Trạng thái</label><select name="is_active" class="form-select"><option value="1" <?= $s['is_active']?'selected':'' ?>>Đang bán</option><option value="0" <?= !$s['is_active']?'selected':'' ?>>Tạm ẩn</option></select>
</div>
<div class="modal-footer"><button name="edit_service" class="btn btn-primary">Lưu</button></div>
</form></div></div>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="modal fade" id="addModal" tabindex="-1"><div class="modal-dialog"><form method="post" class="modal-content">
<div class="modal-header"><h5>Thêm dịch vụ</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body">
<input name="name" class="form-control mb-2" placeholder="Tên dịch vụ" required>
<input name="duration" type="number" class="form-control mb-2" placeholder="Thời gian phút" required>
<input name="price" type="number" class="form-control mb-2" placeholder="Giá" required>
<textarea name="description" class="form-control" placeholder="Mô tả"></textarea>
</div>
<div class="modal-footer"><button name="add_service" class="btn btn-primary">Thêm</button></div>
</form></div></div>
<?php include "admin_footer.php"; ?>
