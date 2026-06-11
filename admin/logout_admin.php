<?php
session_start();

// Chỉ xóa session admin, không đụng tới session user ở frontend
unset($_SESSION['admin']);

session_regenerate_id(true); // đổi id tránh fixation

header("Location: login_admin.php");
exit;
