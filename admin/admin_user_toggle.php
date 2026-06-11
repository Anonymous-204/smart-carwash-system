<?php
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include __DIR__ . '/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: login_admin.php");
    ob_end_flush();
    exit;
}

if (!isset($_GET['id'], $_GET['action'])) {
    header("Location: users.php");
    ob_end_flush();
    exit;
}

$id     = (int)$_GET['id'];
$action = $_GET['action'] === 'lock' ? 'lock' : 'unlock';
$newStatus = ($action === 'lock') ? 0 : 1;

$sql  = "UPDATE khach_hang SET trang_thai = ? WHERE ma_kh = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $newStatus, $id);
mysqli_stmt_execute($stmt);

header("Location: users.php");
ob_end_flush();
exit;