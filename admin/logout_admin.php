<?php
session_start();
unset($_SESSION['admin']);
session_regenerate_id(true);
header("Location: login_admin.php");
exit;
?>
