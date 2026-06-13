<?php

define('BASE_URL', '/smart-carwash-system');

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

function update_customer_rank($conn, $customer_id) {
    // Get customer's current points
    $stmt = $conn->prepare("SELECT point FROM users WHERE id = ?");
    $stmt->bind_param('i', $customer_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $current_points = (int)($result['point'] ?? 0);
    $stmt->close();

    // Find the highest rank the customer qualifies for
    $stmt = $conn->prepare(
        "SELECT id FROM ranks WHERE min_point <= ? ORDER BY min_point DESC LIMIT 1"
    );
    $stmt->bind_param('i', $current_points);
    $stmt->execute();
    $rank_result = $stmt->get_result()->fetch_assoc();
    $new_rank_id = $rank_result['id'] ?? 1; // Default to rank 1 (Đồng)
    $stmt->close();

    // Update user's rank
    $stmt = $conn->prepare("UPDATE users SET rank_id = ? WHERE id = ?");
    $stmt->bind_param('ii', $new_rank_id, $customer_id);
    $stmt->execute();
    $stmt->close();
}
// -------------------------------------------------------
// Auth helpers – PHP Session + cookie (Hướng B)
// -------------------------------------------------------
 
function customer_login_set($user) {
    if (session_status() === PHP_SESSION_NONE) session_start();
    $_SESSION['customer'] = [
        'id'    => $user['id'],
        'name'  => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'role'  => $user['role'],
    ];
 
    // Access Token: session ID, hết hạn khi đóng trình duyệt
    setcookie('access_token', session_id(), [
        'expires'  => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
 
    // Refresh Token: tồn tại 30 ngày
    $refresh = base64_encode($user['id'] . ':' . hash_hmac('sha256', $user['id'], 'carwash_secret_key'));
    setcookie('refresh_token', $refresh, [
        'expires'  => time() + 30 * 24 * 3600,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
}
 
function customer_logout() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    unset($_SESSION['customer']);
    session_regenerate_id(true);
 
    foreach (['access_token', 'refresh_token'] as $c) {
        setcookie($c, '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true]);
    }
}
 
function current_customer() {
    if (session_status() === PHP_SESSION_NONE) session_start();
    return $_SESSION['customer'] ?? null;
}
 
function require_customer_login() {
    if (!current_customer()) {
        header('Location: ' . BASE_URL . '/customer/auth/login.php');
        exit;
    }
}
?>
