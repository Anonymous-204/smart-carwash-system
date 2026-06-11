<?php
session_start();
include "db.php";

$success = "";
$error = "";

// Lấy danh sách loại sản phẩm
$sqlLoai = "SELECT ma_loaisp, ten_loaisp FROM loai_sp ORDER BY ten_loaisp ASC";
$dsLoai = mysqli_query($conn, $sqlLoai);

// Khi nhấn submit
if (isset($_POST["btnAdd"])) {
    $ten_sp = trim($_POST["ten_sp"]);
    $gia = trim($_POST["gia"]);
    $loai = $_POST["ma_loaisp"];

    if ($ten_sp == "" || $gia == "" || $loai == "") {
        $error = "Vui lòng nhập đầy đủ thông tin!";
    } else {
        // Thêm vào bảng sản phẩm
        $sql = "INSERT INTO san_pham (ten_sp, gia, ma_loaisp, ton_tai) VALUES (?, ?, ?, 1)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sii", $ten_sp, $gia, $loai);
        mysqli_stmt_execute($stmt);

        $ma_sp = mysqli_insert_id($conn); // Lấy mã sản phẩm vừa thêm

        // UPLOAD HÌNH ẢNH
        if (!empty($_FILES['hinhanh']['name'][0])) {
            $files = $_FILES['hinhanh'];

            for ($i = 0; $i < count($files['name']); $i++) {
                $name = time() . "_" . basename($files['name'][$i]);
                $path = "../frontend/images/" . $name;

                if (move_uploaded_file($files['tmp_name'][$i], $path)) {
                    $sqlImg = "INSERT INTO hinhanh_sp (ma_sp, ten_anh) VALUES (?, ?)";
                    $stmtImg = mysqli_prepare($conn, $sqlImg);
                    mysqli_stmt_bind_param($stmtImg, "is", $ma_sp, $name);
                    mysqli_stmt_execute($stmtImg);
                }
            }
        }

        $success = "Thêm sản phẩm thành công!";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Thêm sản phẩm</title>
    <link rel="stylesheet"
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="p-4">

<h3>➕ Thêm sản phẩm mới</h3>

<?php if ($error != "") echo "<div class='alert alert-danger'>$error</div>"; ?>
<?php if ($success != "") echo "<div class='alert alert-success'>$success</div>"; ?>

<form method="POST" enctype="multipart/form-data">

    <div class="mb-3">
        <label class="form-label">Tên sản phẩm</label>
        <input type="text" name="ten_sp" class="form-control">
    </div>

    <div class="mb-3">
        <label class="form-label">Giá</label>
        <input type="number" name="gia" class="form-control">
    </div>

    <div class="mb-3">
        <label class="form-label">Loại sản phẩm</label>
        <select name="ma_loaisp" class="form-control">
            <option value="">-- Chọn loại --</option>
            <?php while ($row = mysqli_fetch_assoc($dsLoai)): ?>
                <option value="<?= $row['ma_loaisp'] ?>">
                    <?= $row['ten_loaisp'] ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Hình ảnh (có thể chọn nhiều ảnh)</label>
        <input type="file" name="hinhanh[]" multiple class="form-control">
    </div>

    <button type="submit" name="btnAdd" class="btn btn-primary">Thêm sản phẩm</button>
    <a href="product_list.php" class="btn btn-secondary">Quay lại</a>
</form>

</body>
</html>
