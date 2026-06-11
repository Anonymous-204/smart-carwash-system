<?php
$DB_HOST = "127.0.0.1";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "rua_xe";

$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($conn->connect_error) {
    die("Kết nối MySQL thất bại: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

function money($value) {
    return number_format((int)$value, 0, ',', '.') . " đ";
}

function status_text($status) {
    $map = [
        'PENDING' => 'Chờ duyệt',
        'CONFIRMED' => 'Đã xác nhận',
        'PROCESSING' => 'Đang xử lý',
        'COMPLETED' => 'Hoàn thành',
        'CANCELLED' => 'Đã hủy',
        'PAID' => 'Đã thanh toán',
        'UNPAID' => 'Chưa thanh toán',
        'FAILED' => 'Thất bại',
        'REFUNDED' => 'Hoàn tiền'
    ];
    return $map[$status] ?? $status;
}

function status_badge($status) {
    $class = [
        'PENDING' => 'warning',
        'CONFIRMED' => 'info',
        'PROCESSING' => 'primary',
        'COMPLETED' => 'success',
        'CANCELLED' => 'danger',
        'PAID' => 'success',
        'UNPAID' => 'secondary',
        'FAILED' => 'danger',
        'REFUNDED' => 'dark'
    ][$status] ?? 'secondary';
    return '<span class="badge rounded-pill text-bg-' . $class . '">' . e(status_text($status)) . '</span>';
}

function fetch_all($result) {
    if (!$result) return [];
    return $result->fetch_all(MYSQLI_ASSOC);
}

function scalar($conn, $sql, $default = 0) {
    $rs = $conn->query($sql);
    if (!$rs) return $default;
    $row = $rs->fetch_row();
    return $row[0] ?? $default;
}
?>
