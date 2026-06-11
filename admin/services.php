<?php
include "admin_header.php";

$message = "";
$msgType = "success";

// Xử lý Thêm gói dịch vụ mới
if (isset($_POST['add_service'])) {
    $name = mysqli_real_escape_string($conn, $_POST['ten_goi']);
    $price = (int)$_POST['gia'];
    $duration = (int)$_POST['thoi_gian'];
    $desc = mysqli_real_escape_string($conn, $_POST['mo_ta']);
    
    $insertQuery = $conn->query("INSERT INTO rua_xe_dich_vu (ten_goi, gia, thoi_gian, mo_ta, trang_thai) 
                                 VALUES ('$name', $price, $duration, '$desc', 1)");
    if ($insertQuery) {
        $message = "Đã thêm gói dịch vụ mới: <strong>$name</strong>";
    } else {
        $message = "Lỗi khi thêm gói dịch vụ.";
        $msgType = "danger";
    }
}

// Xử lý Cập nhật gói dịch vụ
if (isset($_POST['edit_service'])) {
    $id = (int)$_POST['service_id'];
    $name = mysqli_real_escape_string($conn, $_POST['ten_goi']);
    $price = (int)$_POST['gia'];
    $duration = (int)$_POST['thoi_gian'];
    $desc = mysqli_real_escape_string($conn, $_POST['mo_ta']);
    $status = (int)$_POST['trang_thai'];

    $updateQuery = $conn->query("UPDATE rua_xe_dich_vu 
                                 SET ten_goi = '$name', gia = $price, thoi_gian = $duration, mo_ta = '$desc', trang_thai = $status 
                                 WHERE id = $id");
    if ($updateQuery) {
        $message = "Đã cập nhật gói dịch vụ <strong>$name</strong> thành công.";
    } else {
        $message = "Lỗi khi cập nhật gói dịch vụ.";
        $msgType = "danger";
    }
}

// Xử lý Thay đổi trạng thái nhanh (Kích hoạt / Tạm dừng)
if (isset($_GET['toggle_id'])) {
    $id = (int)$_GET['toggle_id'];
    $current_status = (int)$_GET['status'];
    $new_status = $current_status == 1 ? 0 : 1;
    
    $toggleQuery = $conn->query("UPDATE rua_xe_dich_vu SET trang_thai = $new_status WHERE id = $id");
    if ($toggleQuery) {
        $message = "Đã cập nhật trạng thái hoạt động của gói dịch vụ.";
    } else {
        $message = "Lỗi khi cập nhật trạng thái.";
        $msgType = "danger";
    }
}

// Xử lý Xóa gói dịch vụ vĩnh viễn
if (isset($_POST['delete_service'])) {
    $id = (int)$_POST['service_id'];
    $deleteQuery = $conn->query("DELETE FROM rua_xe_dich_vu WHERE id = $id");
    if ($deleteQuery) {
        $message = "Đã xóa gói dịch vụ vĩnh viễn khỏi hệ thống.";
    } else {
        $message = "Lỗi khi xóa gói dịch vụ.";
        $msgType = "danger";
    }
}

// Lấy danh sách các gói dịch vụ
$query = "SELECT * FROM rua_xe_dich_vu ORDER BY gia ASC";
$services = $conn->query($query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-dark"><i class="fa-solid fa-cubes text-warning me-2"></i>Quản lý Các Gói Dịch Vụ</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addServiceModal">
        <i class="fa-solid fa-plus-circle me-2"></i>Thêm gói dịch vụ mới
    </button>
</div>

<?php if ($message != ""): ?>
    <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- BẢNG DANH SÁCH GÓI DỊCH VỤ -->
<div class="card bg-white p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Tên gói dịch vụ</th>
                    <th>Giá dịch vụ</th>
                    <th>Thời gian thực hiện</th>
                    <th>Mô tả chi tiết</th>
                    <th>Trạng thái</th>
                    <th class="text-end" style="width: 180px;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($services && $services->num_rows > 0): ?>
                    <?php while ($row = $services->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['ten_goi']); ?></div>
                            </td>
                            <td>
                                <strong class="text-primary fs-5"><?php echo number_format($row['gia']); ?> đ</strong>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border"><i class="fa-regular fa-clock me-1 text-muted"></i><?php echo $row['thoi_gian']; ?> phút</span>
                            </td>
                            <td>
                                <p class="text-muted small mb-0" style="max-width: 350px; text-overflow: ellipsis; overflow: hidden; white-space: nowrap;" title="<?php echo htmlspecialchars($row['mo_ta']); ?>">
                                    <?php echo htmlspecialchars($row['mo_ta']); ?>
                                </p>
                            </td>
                            <td>
                                <?php if ($row['trang_thai'] == 1): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2.5 py-1.5 rounded-pill">
                                        <i class="fa-solid fa-circle-check me-1"></i>Đang hoạt động
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2.5 py-1.5 rounded-pill">
                                        <i class="fa-solid fa-circle-xmark me-1"></i>Tạm ngưng
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="services.php?toggle_id=<?php echo $row['id']; ?>&status=<?php echo $row['trang_thai']; ?>" 
                                   class="btn btn-sm <?php echo $row['trang_thai'] == 1 ? 'btn-outline-secondary' : 'btn-outline-success'; ?> me-1"
                                   title="<?php echo $row['trang_thai'] == 1 ? 'Tạm ngưng hoạt động' : 'Kích hoạt hoạt động'; ?>">
                                    <i class="fa-solid <?php echo $row['trang_thai'] == 1 ? 'fa-eye-slash' : 'fa-eye'; ?>"></i>
                                </a>
                                <button class="btn btn-sm btn-outline-warning me-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editServiceModal"
                                        data-id="<?php echo $row['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['ten_goi']); ?>"
                                        data-price="<?php echo $row['gia']; ?>"
                                        data-duration="<?php echo $row['thoi_gian']; ?>"
                                        data-desc="<?php echo htmlspecialchars($row['mo_ta']); ?>"
                                        data-status="<?php echo $row['trang_thai']; ?>">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteServiceModal"
                                        data-id="<?php echo $row['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['ten_goi']); ?>">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-cubes fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">Hệ thống chưa cấu hình gói dịch vụ rửa xe nào.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL THÊM GÓI DỊCH VỤ -->
<div class="modal fade" id="addServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-plus-circle text-primary me-2"></i>Thêm gói dịch vụ rửa xe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên gói dịch vụ</label>
                    <input type="text" name="ten_goi" class="form-control" placeholder="Ví dụ: Rửa xe siêu cấp Nano..." required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giá tiền (đ)</label>
                        <input type="number" name="gia" class="form-control" placeholder="80000" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Thời gian (phút)</label>
                        <input type="number" name="thoi_gian" class="form-control" placeholder="30" min="5" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả chi tiết</label>
                    <textarea name="mo_ta" class="form-control" rows="4" placeholder="Nhập các bước thực hiện, ưu điểm gói dịch vụ..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" name="add_service" class="btn btn-primary">Thêm dịch vụ</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CẬP NHẬT GÓI DỊCH VỤ -->
<div class="modal fade" id="editServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-pen-to-square text-warning me-2"></i>Cập nhật gói dịch vụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="service_id" id="edit_service_id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên gói dịch vụ</label>
                    <input type="text" name="ten_goi" id="edit_ten_goi" class="form-control" required>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Giá tiền (đ)</label>
                        <input type="number" name="gia" id="edit_gia" class="form-control" min="0" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Thời gian (phút)</label>
                        <input type="number" name="thoi_gian" id="edit_thoi_gian" class="form-control" min="5" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Mô tả chi tiết</label>
                    <textarea name="mo_ta" id="edit_mo_ta" class="form-control" rows="4" required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Trạng thái hoạt động</label>
                    <select name="trang_thai" id="edit_trang_thai" class="form-select">
                        <option value="1">Kích hoạt hoạt động</option>
                        <option value="0">Tạm ngưng hoạt động</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" name="edit_service" class="btn btn-warning">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL XÓA GÓI DỊCH VỤ -->
<div class="modal fade" id="deleteServiceModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Xác nhận xóa gói dịch vụ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="service_id" id="delete_service_id">
                <p>Bạn có chắc chắn muốn xóa gói dịch vụ <strong id="delete_service_name" class="text-danger"></strong> khỏi hệ thống?</p>
                <p class="text-muted small mb-0"><i class="fa-solid fa-circle-info me-1"></i>Hành động này sẽ xóa vĩnh viễn và có thể ảnh hưởng đến lịch sử đặt xe đang tham chiếu đến gói dịch vụ này.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" name="delete_service" class="btn btn-danger">Xóa vĩnh viễn</button>
            </div>
        </form>
    </div>
</div>

<!-- Javascript để truyền thông tin vào Modal -->
<script>
    const editServiceModal = document.getElementById('editServiceModal');
    if (editServiceModal) {
        editServiceModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const price = button.getAttribute('data-price');
            const duration = button.getAttribute('data-duration');
            const desc = button.getAttribute('data-desc');
            const status = button.getAttribute('data-status');

            document.getElementById('edit_service_id').value = id;
            document.getElementById('edit_ten_goi').value = name;
            document.getElementById('edit_gia').value = price;
            document.getElementById('edit_thoi_gian').value = duration;
            document.getElementById('edit_mo_ta').value = desc;
            document.getElementById('edit_trang_thai').value = status;
        });
    }

    const deleteServiceModal = document.getElementById('deleteServiceModal');
    if (deleteServiceModal) {
        deleteServiceModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');

            document.getElementById('delete_service_id').value = id;
            document.getElementById('delete_service_name').textContent = name;
        });
    }
</script>

<?php include "admin_footer.php"; ?>
