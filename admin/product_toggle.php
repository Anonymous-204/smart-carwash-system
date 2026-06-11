<?php
include "db.php";

$id = $_GET['id'];

mysqli_query($conn, "UPDATE san_pham SET ton_tai = 1 - ton_tai WHERE ma_sp = $id");

header("Location: product_list.php");
exit;
