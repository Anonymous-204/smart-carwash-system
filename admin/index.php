<?php
include "admin_header.php";

// 1. Thống kê KPI
$totalRevenueResult = $conn->query("SELECT SUM(tong_tien) FROM rua_xe_dat_lich WHERE trang_thai = 'Đã hoàn thành'");
$totalRevenue = ($totalRevenueResult ? $totalRevenueResult->fetch_row()[0] : 0) ?? 0;

$totalBookingsResult = $conn->query("SELECT COUNT(*) FROM rua_xe_dat_lich");
$totalBookings = ($totalBookingsResult ? $totalBookingsResult->fetch_row()[0] : 0) ?? 0;

$pendingBookingsResult = $conn->query("SELECT COUNT(*) FROM rua_xe_dat_lich WHERE trang_thai = 'Chờ duyệt'");
$pendingBookings = ($pendingBookingsResult ? $pendingBookingsResult->fetch_row()[0] : 0) ?? 0;

$totalMembersResult = $conn->query("SELECT COUNT(*) FROM rua_xe_thanh_vien");
$totalMembers = ($totalMembersResult ? $totalMembersResult->fetch_row()[0] : 0) ?? 0;

$totalServicesResult = $conn->query("SELECT COUNT(*) FROM rua_xe_dich_vu WHERE trang_thai = 1");
$totalServices = ($totalServicesResult ? $totalServicesResult->fetch_row()[0] : 0) ?? 0;

// 2. Lấy dữ liệu cho biểu đồ Doanh thu 6 ngày qua
$revenueDays = [];
$revenueLabels = [];
for ($i = 5; $i >= 0; $i--) {
    $dateStr = date('Y-m-d', strtotime("-$i days"));
    $displayLabel = date('d/m', strtotime("-$i days"));
    
    $dailyRevResult = $conn->query("SELECT SUM(tong_tien) FROM rua_xe_dat_lich WHERE ngay_dat = '$dateStr' AND trang_thai = 'Đã hoàn thành'");
    $dailyRev = ($dailyRevResult ? $dailyRevResult->fetch_row()[0] : 0) ?? 0;
    
    $revenueDays[] = (int)$dailyRev;
    $revenueLabels[] = $displayLabel;
}

// 3. Lấy dữ liệu phân bổ Trạng thái đặt lịch
$statusLabels = ['Chờ duyệt', 'Đã duyệt', 'Đã hoàn thành', 'Đã hủy'];
$statusCounts = [];
foreach ($statusLabels as $status) {
    $statusResult = $conn->query("SELECT COUNT(*) FROM rua_xe_dat_lich WHERE trang_thai = '$status'");
    $statusCounts[] = ($statusResult ? $statusResult->fetch_row()[0] : 0) ?? 0;
}

// 4. Lấy danh sách lịch đặt mới nhất
$recentBookings = $conn->query("SELECT b.*, s.ten_goi 
                                FROM rua_xe_dat_lich b 
                                LEFT JOIN rua_xe_dich_vu s ON b.goi_id = s.id 
                                ORDER BY b.ngay_tao DESC LIMIT 5");
?>

<div class="row g-4 mb-4">
    <!-- Card Doanh thu -->
    <div class="col-md-3">
        <div class="card card-stat bg-white h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3 text-success">
                    <i class="fa-solid fa-money-bill-wave fa-2x"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Doanh thu thực tế</h6>
                    <h4 class="mb-0 fw-bold"><?php echo number_format($totalRevenue); ?> đ</h4>
                    <small class="text-success"><i class="fa-solid fa-check-double me-1"></i>Đã hoàn thành</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Lịch đặt -->
    <div class="col-md-3">
        <div class="card card-stat bg-white h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3 text-primary">
                    <i class="fa-solid fa-calendar-check fa-2x"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Tổng lượt đặt lịch</h6>
                    <h4 class="mb-0 fw-bold"><?php echo $totalBookings; ?></h4>
                    <small class="text-warning">
                        <i class="fa-solid fa-clock me-1"></i><?php echo $pendingBookings; ?> chờ duyệt
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Thành viên -->
    <div class="col-md-3">
        <div class="card card-stat bg-white h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3 text-info">
                    <i class="fa-solid fa-users fa-2x"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Khách hàng thành viên</h6>
                    <h4 class="mb-0 fw-bold"><?php echo $totalMembers; ?></h4>
                    <small class="text-muted">Chương trình Loyalty</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Gói dịch vụ -->
    <div class="col-md-3">
        <div class="card card-stat bg-white h-100">
            <div class="card-body d-flex align-items-center">
                <div class="rounded-circle bg-warning bg-opacity-10 p-3 me-3 text-warning">
                    <i class="fa-solid fa-tags fa-2x"></i>
                </div>
                <div>
                    <h6 class="text-muted mb-1">Gói dịch vụ</h6>
                    <h4 class="mb-0 fw-bold"><?php echo $totalServices; ?></h4>
                    <small class="text-muted">Đang cung cấp</small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- BIỂU ĐỒ THỐNG KÊ -->
<div class="row g-4 mb-4">
    <!-- Biểu đồ cột Doanh thu -->
    <div class="col-md-8">
        <div class="card bg-white p-4 h-100">
            <h5 class="card-title fw-bold text-dark mb-4"><i class="fa-solid fa-chart-bar text-success me-2"></i>Thống kê doanh thu (6 ngày qua)</h5>
            <div style="position: relative; height: 320px;">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Biểu đồ tròn Trạng thái -->
    <div class="col-md-4">
        <div class="card bg-white p-4 h-100">
            <h5 class="card-title fw-bold text-dark mb-4"><i class="fa-solid fa-chart-pie text-info me-2"></i>Trạng thái đặt lịch</h5>
            <div style="position: relative; height: 320px;" class="d-flex align-items-center justify-content-center">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- DANH SÁCH LỊCH ĐẶT MỚI NHẤT -->
<div class="card bg-white p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="card-title fw-bold text-dark mb-0"><i class="fa-solid fa-clock-rotate-left text-warning me-2"></i>Lịch đặt xe mới nhất</h5>
        <a href="bookings.php" class="btn btn-sm btn-outline-primary">Xem tất cả lịch đặt <i class="fa-solid fa-arrow-right ms-1"></i></a>
    </div>
    
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>Khách hàng</th>
                    <th>Điện thoại</th>
                    <th>Biển số xe</th>
                    <th>Dịch vụ</th>
                    <th>Ngày & Giờ đặt</th>
                    <th>Tổng tiền</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($recentBookings && $recentBookings->num_rows > 0): ?>
                    <?php while ($row = $recentBookings->fetch_assoc()): ?>
                        <?php 
                            $badgeClass = 'bg-pending';
                            if ($row['trang_thai'] == 'Đã duyệt') $badgeClass = 'bg-approved';
                            if ($row['trang_thai'] == 'Đã hoàn thành') $badgeClass = 'bg-completed';
                            if ($row['trang_thai'] == 'Đã hủy') $badgeClass = 'bg-cancelled';
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['ten_khach_hang']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['so_dien_thoai']); ?></td>
                            <td><span class="badge bg-secondary"><?php echo htmlspecialchars($row['bien_so_xe']); ?></span></td>
                            <td><?php echo htmlspecialchars($row['ten_goi']); ?></td>
                            <td>
                                <div><i class="fa-regular fa-calendar text-muted me-1"></i> <?php echo date('d/m/Y', strtotime($row['ngay_dat'])); ?></div>
                                <div class="small text-muted"><i class="fa-regular fa-clock text-muted me-1"></i> <?php echo date('H:i', strtotime($row['gio_dat'])); ?></div>
                            </td>
                            <td class="fw-bold text-dark"><?php echo number_format($row['tong_tien']); ?> đ</td>
                            <td>
                                <span class="badge-status <?php echo $badgeClass; ?>">
                                    <?php echo htmlspecialchars($row['trang_thai']); ?>
                                </span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">Chưa có lịch đặt nào.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Biểu đồ Doanh thu (Line chart)
    const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
    new Chart(ctxRevenue, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($revenueLabels); ?>,
            datasets: [{
                label: 'Doanh thu (đ)',
                data: <?php echo json_encode($revenueDays); ?>,
                borderColor: '#10b981',
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.3,
                pointBackgroundColor: '#10b981',
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString() + ' đ';
                        }
                    }
                }
            }
        }
    });

    // Biểu đồ Trạng thái (Doughnut chart)
    const ctxStatus = document.getElementById('statusChart').getContext('2d');
    new Chart(ctxStatus, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($statusLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($statusCounts); ?>,
                backgroundColor: ['#fbbf24', '#38bdf8', '#34d399', '#f87171'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, padding: 15 }
                }
            },
            cutout: '70%'
        }
    });
</script>

<?php include "admin_footer.php"; ?>
