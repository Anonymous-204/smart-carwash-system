<?php
include "db.php";

$id = $_GET['id'];

// Xóa ảnh
mysqli_query($conn, "DELETE FROM hinhanh_sp WHERE ma_sp = $id");

// Xóa sản phẩm
mysqli_query($conn, "DELETE FROM san_pham WHERE ma_sp = $id");

header("Location: product_list.php");
exit;
