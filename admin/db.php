<?php
// Kết nối CSDL cho Hệ thống Rửa xe Thông minh
$DB_HOST = "127.0.0.1";
$DB_USER = "root";
$DB_PASS = "";
$DB_NAME = "rua_xe";

// Kết nối tới MySQL server (chưa chọn database)
$conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS);

if ($conn->connect_error) {
    die("Kết nối MySQL thất bại: " . $conn->connect_error);
}

// Tự tạo database nếu chưa tồn tại
$conn->query("CREATE DATABASE IF NOT EXISTS `$DB_NAME` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$conn->select_db($DB_NAME);
$conn->set_charset("utf8mb4");

// Tự tạo bảng admin nếu chưa tồn tại
$conn->query("CREATE TABLE IF NOT EXISTS admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(50) DEFAULT 'admin'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
?>