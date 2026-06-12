<?php
require_once __DIR__ . '/../../db.php';
customer_logout();
header('Location: ' . BASE_URL . '/customer/auth/login.php');
exit;