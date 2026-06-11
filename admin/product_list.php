<?php
session_start();
include "db.php";

$sql = "
    SELECT sp.ma_sp, sp.ten_sp, sp.gia, sp.ton_tai,
           (SELECT ten_anh FROM hinhanh_sp WHERE ma_sp = sp.ma_sp LIMIT 1) AS hinh
    FROM san_pham sp
    ORDER BY sp.ma_sp DESC
";
$ds = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quản lý sản phẩm</title>
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<h3>📦 Danh sách sản phẩm</h3>

<a href="product_add.php" class="btn btn-success mb-3">➕ Thêm sản phẩm</a>

<table class="table table-bordered">
    <tr>
        <th>Hình</th>
        <th>Tên sản phẩm</th>
        <th>Giá</th>
        <th>Trạng thái</th>
        <th>Hành động</th>
    </tr>

    <?php while ($row = mysqli_fetch_assoc($ds)): ?>
        <tr>
            <td width="120">
                <img src="../frontend/images/<?= $row['hinh'] ?>" 
                     style="width:100px;">
            </td>

            <td><?= $row['ten_sp'] ?></td>
            <td><?= number_format($row['gia']) ?>₫</td>

            <td>
                <?= $row['ton_tai'] ? "✔ Hiển thị" : "✖ Đã ẩn" ?>
            </td>

            <td>
                <a href="product_toggle.php?id=<?= $row['ma_sp'] ?>" 
                   class="btn btn-warning btn-sm">
                    <?= $row['ton_tai'] ? "Ẩn" : "Hiện" ?>
                </a>

                <a href="product_delete.php?id=<?= $row['ma_sp'] ?>" 
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Xóa sản phẩm này?')">
                    Xóa
                </a>
            </td>
        </tr>
    <?php endwhile; ?>

</table>

</body>
</html>
