<?php
include "admin_header.php";

$message = "";
$msgType = "success";

// Xử lý Thêm thành viên mới
if (isset($_POST['add_member'])) {
    $name = mysqli_real_escape_string($conn, $_POST['ten_thanh_vien']);
    $phone = mysqli_real_escape_string($conn, $_POST['so_dien_thoai']);
    $plate = mysqli_real_escape_string($conn, $_POST['bien_so_xe']);
    $points = (int)($_POST['diem_tich_luy'] ?? 0);
    
    // Xác định hạng thành viên dựa trên điểm ban đầu
    $tier = 'Đồng';
    if ($points >= 1000) {
        $tier = 'Kim cương';
    } elseif ($points >= 500) {
        $tier = 'Vàng';
    } elseif ($points >= 150) {
        $tier = 'Bạc';
    }

    // Kiểm tra xem số điện thoại đã tồn tại chưa
    $checkQuery = $conn->query("SELECT * FROM rua_xe_thanh_vien WHERE so_dien_thoai = '$phone'");
    if ($checkQuery && $checkQuery->num_rows > 0) {
        $message = "Lỗi: Số điện thoại <strong>$phone</strong> đã đăng ký thành viên trước đó!";
        $msgType = "danger";
    } else {
        $insertQuery = $conn->query("INSERT INTO rua_xe_thanh_vien (ten_thanh_vien, so_dien_thoai, bien_so_xe, hang_thanh_vien, diem_tich_luy) 
                                     VALUES ('$name', '$phone', '$plate', '$tier', $points)");
        if ($insertQuery) {
            $message = "Đã thêm thành viên mới thành công: <strong>$name</strong>";
        } else {
            $message = "Lỗi khi thêm thành viên.";
            $msgType = "danger";
        }
    }
}

// Xử lý Cập nhật điểm/thông tin thành viên
if (isset($_POST['edit_member'])) {
    $id = (int)$_POST['member_id'];
    $name = mysqli_real_escape_string($conn, $_POST['ten_thanh_vien']);
    $phone = mysqli_real_escape_string($conn, $_POST['so_dien_thoai']);
    $plate = mysqli_real_escape_string($conn, $_POST['bien_so_xe']);
    $points = (int)$_POST['diem_tich_luy'];
    
    // Xác định hạng thành viên
    $tier = 'Đồng';
    if ($points >= 1000) {
        $tier = 'Kim cương';
    } elseif ($points >= 500) {
        $tier = 'Vàng';
    } elseif ($points >= 150) {
        $tier = 'Bạc';
    }

    $updateQuery = $conn->query("UPDATE rua_xe_thanh_vien 
                                 SET ten_thanh_vien = '$name', so_dien_thoai = '$phone', bien_so_xe = '$plate', diem_tich_luy = $points, hang_thanh_vien = '$tier' 
                                 WHERE id = $id");
    if ($updateQuery) {
        $message = "Đã cập nhật thông tin thành viên thành công.";
    } else {
        $message = "Lỗi khi cập nhật thông tin thành viên.";
        $msgType = "danger";
    }
}

// Xử lý Xóa thành viên
if (isset($_POST['delete_member'])) {
    $id = (int)$_POST['member_id'];
    $deleteQuery = $conn->query("DELETE FROM rua_xe_thanh_vien WHERE id = $id");
    if ($deleteQuery) {
        $message = "Đã xóa thành viên thành công.";
    } else {
        $message = "Lỗi khi xóa thành viên.";
        $msgType = "danger";
    }
}

// Xử lý Tìm kiếm thành viên
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$where_sql = "";
if ($search != '') {
    $where_sql = "WHERE ten_thanh_vien LIKE '%$search%' OR so_dien_thoai LIKE '%$search%' OR bien_so_xe LIKE '%$search%'";
}

$query = "SELECT * FROM rua_xe_thanh_vien $where_sql ORDER BY diem_tich_luy DESC, ngay_dang_ky DESC";
$members = $conn->query($query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-dark"><i class="fa-solid fa-id-card text-info me-2"></i>Chương trình Khách hàng Thân thiết (Loyalty)</h4>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
        <i class="fa-solid fa-user-plus me-2"></i>Thêm thành viên mới
    </button>
</div>

<?php if ($message != ""): ?>
    <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- THANH TÌM KIẾM -->
<div class="card bg-white p-4 mb-4">
    <form method="GET" class="row g-3">
        <div class="col-md-9">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Tìm theo tên thành viên, số điện thoại, biển số xe..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-outline-primary"><i class="fa-solid fa-search me-2"></i>Tìm kiếm</button>
        </div>
    </form>
</div>

<!-- BẢNG DANH SÁCH THÀNH VIÊN -->
<div class="card bg-white p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Họ và Tên</th>
                    <th>Số điện thoại</th>
                    <th>Biển số xe</th>
                    <th>Hạng thành viên</th>
                    <th>Điểm tích lũy</th>
                    <th>Ngày đăng ký</th>
                    <th class="text-end" style="width: 150px;">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($members && $members->num_rows > 0): ?>
                    <?php while ($row = $members->fetch_assoc()): ?>
                        <?php 
                            $badgeClass = "bg-secondary";
                            if ($row['hang_thanh_vien'] == 'Bạc') $badgeClass = "bg-info text-white";
                            if ($row['hang_thanh_vien'] == 'Vàng') $badgeClass = "bg-warning text-dark";
                            if ($row['hang_thanh_vien'] == 'Kim cương') $badgeClass = "bg-danger text-white";
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['ten_thanh_vien']); ?></div>
                            </td>
                            <td><strong><?php echo htmlspecialchars($row['so_dien_thoai']); ?></strong></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['bien_so_xe'] ?: 'Chưa cập nhật'); ?></span></td>
                            <td>
                                <span class="badge <?php echo $badgeClass; ?> px-3 py-2 fw-bold fs-7">
                                    <i class="fa-solid fa-crown me-1"></i><?php echo htmlspecialchars($row['hang_thanh_vien']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="text-primary fw-bold fs-5"><?php echo number_format($row['diem_tich_luy']); ?></span> <small class="text-muted">điểm</small>
                            </td>
                            <td>
                                <span class="text-muted small"><i class="fa-regular fa-calendar me-1"></i><?php echo date('d/m/Y', strtotime($row['ngay_dang_ky'])); ?></span>
                            </td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-warning me-1" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#editMemberModal"
                                        data-id="<?php echo $row['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['ten_thanh_vien']); ?>"
                                        data-phone="<?php echo htmlspecialchars($row['so_dien_thoai']); ?>"
                                        data-plate="<?php echo htmlspecialchars($row['bien_so_xe']); ?>"
                                        data-points="<?php echo $row['diem_tich_luy']; ?>">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-danger" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#deleteMemberModal"
                                        data-id="<?php echo $row['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($row['ten_thanh_vien']); ?>">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-user-slash fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">Không tìm thấy thành viên nào phù hợp.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL THÊM THÀNH VIÊN MỚI -->
<div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-plus text-primary me-2"></i>Thêm thành viên Loyalty</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Họ và Tên</label>
                    <input type="text" name="ten_thanh_vien" class="form-control" placeholder="Nhập tên khách hàng..." required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Số điện thoại</label>
                    <input type="text" name="so_dien_thoai" class="form-control" placeholder="Nhập số điện thoại..." required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Biển số xe</label>
                    <input type="text" name="bien_so_xe" class="form-control" placeholder="Nhập biển số xe (Ví dụ: 30A-123.45)...">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Điểm tích lũy ban đầu</label>
                    <input type="number" name="diem_tich_luy" class="form-control" value="0" min="0">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" name="add_member" class="btn btn-primary">Lưu thành viên</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CẬP NHẬT THÀNH VIÊN -->
<div class="modal fade" id="editMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-user-pen text-warning me-2"></i>Chỉnh sửa thông tin thành viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="member_id" id="edit_member_id">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Họ và Tên</label>
                    <input type="text" name="ten_thanh_vien" id="edit_ten_thanh_vien" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Số điện thoại</label>
                    <input type="text" name="so_dien_thoai" id="edit_so_dien_thoai" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Biển số xe</label>
                    <input type="text" name="bien_so_xe" id="edit_bien_so_xe" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">Điểm tích lũy</label>
                    <input type="number" name="diem_tich_luy" id="edit_diem_tich_luy" class="form-control" min="0" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" name="edit_member" class="btn btn-warning">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL XÓA THÀNH VIÊN -->
<div class="modal fade" id="deleteMemberModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Xác nhận xóa thành viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="member_id" id="delete_member_id">
                <p>Bạn có chắc chắn muốn xóa thành viên <strong id="delete_member_name" class="text-danger"></strong> khỏi hệ thống?</p>
                <p class="text-muted small mb-0"><i class="fa-solid fa-circle-info me-1"></i>Hành động này không thể hoàn tác và mọi điểm tích lũy của thành viên sẽ bị mất hoàn toàn.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" name="delete_member" class="btn btn-danger">Xóa vĩnh viễn</button>
            </div>
        </form>
    </div>
</div>

<!-- Javascript để truyền thông tin vào Modal -->
<script>
    const editModal = document.getElementById('editMemberModal');
    if (editModal) {
        editModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const phone = button.getAttribute('data-phone');
            const plate = button.getAttribute('data-plate');
            const points = button.getAttribute('data-points');

            document.getElementById('edit_member_id').value = id;
            document.getElementById('edit_ten_thanh_vien').value = name;
            document.getElementById('edit_so_dien_thoai').value = phone;
            document.getElementById('edit_bien_so_xe').value = plate;
            document.getElementById('edit_diem_tich_luy').value = points;
        });
    }

    const deleteModal = document.getElementById('deleteMemberModal');
    if (deleteModal) {
        deleteModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');

            document.getElementById('delete_member_id').value = id;
            document.getElementById('delete_member_name').textContent = name;
        });
    }
</script>

<?php include "admin_footer.php"; ?>
