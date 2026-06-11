<?php
include "admin_header.php";

// Xử lý cập nhật trạng thái đơn đặt lịch
$message = "";
$msgType = "success";

if (isset($_POST['update_status'])) {
    $booking_id = (int)$_POST['booking_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);
    
    // Lấy thông tin đơn đặt lịch trước khi cập nhật
    $bookingQuery = $conn->query("SELECT * FROM rua_xe_dat_lich WHERE id = $booking_id");
    if ($bookingQuery && $bookingQuery->num_rows > 0) {
        $booking = $bookingQuery->fetch_assoc();
        $old_status = $booking['trang_thai'];
        
        // Tiến hành cập nhật trạng thái
        $updateQuery = $conn->query("UPDATE rua_xe_dat_lich SET trang_thai = '$new_status' WHERE id = $booking_id");
        
        if ($updateQuery) {
            $message = "Đã cập nhật trạng thái lịch đặt sang: <strong>$new_status</strong>";
            
            // Nếu chuyển thành "Đã hoàn thành" và trước đó chưa hoàn thành -> Cộng điểm tích lũy cho thành viên
            if ($new_status == 'Đã hoàn thành' && $old_status != 'Đã hoàn thành') {
                $phone = $booking['so_dien_thoai'];
                $points = (int)$booking['diem_nhan_duoc'];
                
                // Kiểm tra xem số điện thoại này có đăng ký thành viên chưa
                $memberQuery = $conn->query("SELECT * FROM rua_xe_thanh_vien WHERE so_dien_thoai = '$phone'");
                if ($memberQuery && $memberQuery->num_rows > 0) {
                    $member = $memberQuery->fetch_assoc();
                    $new_points = $member['diem_tich_luy'] + $points;
                    
                    // Xác định hạng thành viên mới dựa trên điểm tích lũy
                    $new_tier = 'Đồng';
                    if ($new_points >= 1000) {
                        $new_tier = 'Kim cương';
                    } elseif ($new_points >= 500) {
                        $new_tier = 'Vàng';
                    } elseif ($new_points >= 150) {
                        $new_tier = 'Bạc';
                    }
                    
                    $conn->query("UPDATE rua_xe_thanh_vien 
                                  SET diem_tich_luy = $new_points, hang_thanh_vien = '$new_tier' 
                                  WHERE so_dien_thoai = '$phone'");
                    
                    $message .= "<br><i class='fa-solid fa-gem text-warning me-1'></i> Khách hàng thành viên được cộng <strong>+$points</strong> điểm. Hạng mới: <strong>$new_tier</strong>.";
                }
            }
        } else {
            $message = "Lỗi khi cập nhật trạng thái đơn đặt lịch.";
            $msgType = "danger";
        }
    }
}

// Xử lý tìm kiếm và lọc trạng thái
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$status_filter = isset($_GET['status_filter']) ? mysqli_real_escape_string($conn, $_GET['status_filter']) : '';

// Xây dựng câu truy vấn
$where_clauses = [];
if ($search != '') {
    $where_clauses[] = "(b.ten_khach_hang LIKE '%$search%' OR b.so_dien_thoai LIKE '%$search%' OR b.bien_so_xe LIKE '%$search%')";
}
if ($status_filter != '') {
    $where_clauses[] = "b.trang_thai = '$status_filter'";
}

$where_sql = "";
if (count($where_clauses) > 0) {
    $where_sql = "WHERE " . implode(' AND ', $where_clauses);
}

$query = "SELECT b.*, s.ten_goi 
          FROM rua_xe_dat_lich b 
          LEFT JOIN rua_xe_dich_vu s ON b.goi_id = s.id 
          $where_sql 
          ORDER BY b.ngay_dat DESC, b.gio_dat DESC";
$bookings = $conn->query($query);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="fw-bold text-dark"><i class="fa-solid fa-calendar-check text-primary me-2"></i>Duyệt lịch đặt xe</h4>
    <span class="badge bg-primary px-3 py-2 fs-6">Tổng số: <?php echo $bookings ? $bookings->num_rows : 0; ?> lịch đặt</span>
</div>

<?php if ($message != ""): ?>
    <div class="alert alert-<?php echo $msgType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- BỘ LỌC VÀ TÌM KIẾM (Data Tables Filter) -->
<div class="card bg-white p-4 mb-4">
    <form method="GET" class="row g-3 align-items-end">
        <div class="col-md-5">
            <label class="form-label fw-semibold text-muted">Tìm kiếm khách hàng</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="search" class="form-control bg-light border-start-0" placeholder="Nhập tên, số điện thoại, biển số xe..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
        </div>
        
        <div class="col-md-4">
            <label class="form-label fw-semibold text-muted">Lọc theo trạng thái</label>
            <select name="status_filter" class="form-select bg-light">
                <option value="">-- Tất cả trạng thái --</option>
                <option value="Chờ duyệt" <?php echo $status_filter == 'Chờ duyệt' ? 'selected' : ''; ?>>Chờ duyệt</option>
                <option value="Đã duyệt" <?php echo $status_filter == 'Đã duyệt' ? 'selected' : ''; ?>>Đã duyệt</option>
                <option value="Đã hoàn thành" <?php echo $status_filter == 'Đã hoàn thành' ? 'selected' : ''; ?>>Đã hoàn thành</option>
                <option value="Đã hủy" <?php echo $status_filter == 'Đã hủy' ? 'selected' : ''; ?>>Đã hủy</option>
            </select>
        </div>
        
        <div class="col-md-3 d-grid">
            <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter me-2"></i>Lọc & Tìm kiếm</button>
        </div>
    </form>
</div>

<!-- DANH SÁCH LỊCH ĐẶT -->
<div class="card bg-white p-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Khách hàng</th>
                    <th>Thông tin xe</th>
                    <th>Gói dịch vụ</th>
                    <th>Thời gian đặt</th>
                    <th>Chi phí & Điểm</th>
                    <th>Trạng thái</th>
                    <th class="text-center" style="width: 220px;">Duyệt nhanh</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($bookings && $bookings->num_rows > 0): ?>
                    <?php while ($row = $bookings->fetch_assoc()): ?>
                        <?php 
                            $badgeClass = 'bg-pending';
                            if ($row['trang_thai'] == 'Đã duyệt') $badgeClass = 'bg-approved';
                            if ($row['trang_thai'] == 'Đã hoàn thành') $badgeClass = 'bg-completed';
                            if ($row['trang_thai'] == 'Đã hủy') $badgeClass = 'bg-cancelled';
                        ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-dark"><?php echo htmlspecialchars($row['ten_khach_hang']); ?></div>
                                <div class="small text-muted"><i class="fa-solid fa-phone me-1"></i><?php echo htmlspecialchars($row['so_dien_thoai']); ?></div>
                            </td>
                            <td>
                                <div><span class="badge bg-dark px-2 py-1"><?php echo htmlspecialchars($row['bien_so_xe']); ?></span></div>
                                <div class="small text-muted mt-1">Phân loại: <?php echo htmlspecialchars($row['loai_xe']); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold text-secondary"><?php echo htmlspecialchars($row['ten_goi']); ?></div>
                            </td>
                            <td>
                                <div class="fw-semibold"><i class="fa-regular fa-calendar-days text-muted me-1"></i> <?php echo date('d/m/Y', strtotime($row['ngay_dat'])); ?></div>
                                <div class="small text-muted"><i class="fa-regular fa-clock text-muted me-1"></i> <?php echo date('H:i', strtotime($row['gio_dat'])); ?></div>
                            </td>
                            <td>
                                <div class="fw-bold text-primary"><?php echo number_format($row['tong_tien']); ?> đ</div>
                                <div class="small text-success fw-semibold"><i class="fa-solid fa-plus me-1"></i><?php echo $row['diem_nhan_duoc']; ?> điểm</div>
                            </td>
                            <td>
                                <span class="badge-status <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($row['trang_thai']); ?>
                                </span>
                            </td>
                            <td>
                                <form method="POST" class="d-flex justify-content-center gap-1">
                                    <input type="hidden" name="booking_id" value="<?php echo $row['id']; ?>">
                                    
                                    <?php if ($row['trang_thai'] == 'Chờ duyệt'): ?>
                                        <button type="submit" name="update_status" class="btn btn-sm btn-outline-info" onclick="this.form.status.value='Đã duyệt';">
                                            <i class="fa-solid fa-check"></i> Duyệt
                                        </button>
                                        <button type="submit" name="update_status" class="btn btn-sm btn-outline-danger" onclick="this.form.status.value='Đã hủy';">
                                            <i class="fa-solid fa-ban"></i> Hủy
                                        </button>
                                    <?php elseif ($row['trang_thai'] == 'Đã duyệt'): ?>
                                        <button type="submit" name="update_status" class="btn btn-sm btn-outline-success" onclick="this.form.status.value='Đã hoàn thành';">
                                            <i class="fa-solid fa-circle-check"></i> Xong
                                        </button>
                                        <button type="submit" name="update_status" class="btn btn-sm btn-outline-danger" onclick="this.form.status.value='Đã hủy';">
                                            <i class="fa-solid fa-ban"></i> Hủy
                                        </button>
                                    <?php else: ?>
                                        <span class="text-muted small italic">Không khả dụng</span>
                                    <?php endif; ?>
                                    
                                    <input type="hidden" name="status" value="">
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="fa-solid fa-triangle-exclamation fa-2x mb-3 text-warning"></i>
                            <p class="mb-0">Không tìm thấy lịch đặt xe nào phù hợp.</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include "admin_footer.php"; ?>
