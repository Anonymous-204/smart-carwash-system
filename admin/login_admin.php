<?php
session_start();
include "db.php";

$error = "";

if (isset($_POST["btnAdminLogin"])) {
    $user = trim($_POST["username"] ?? "");
    $pass = trim($_POST["password"] ?? "");

    if ($user == "" || $pass == "") {
        $error = "Vui lòng nhập đầy đủ tên đăng nhập và mật khẩu!";
    } else {
        $pass_md5 = md5($pass);

      $sql = "SELECT * FROM admin WHERE username = ? AND password = ? LIMIT 1";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $user, $pass_md5);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            // Lưu thông tin admin vào session riêng
            $_SESSION["admin"] = [
                "id"       => $row["id"],
                "username" => $row["username"],
                "role"     => $row["role"]
            ];

          

            header("Location: index.php");
            exit;
        } else {
            $error = "Sai tài khoản / mật khẩu hoặc không phải admin!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập quản trị</title>
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow" style="min-width:350px;">
        <div class="card-header text-center">
            <h4>Đăng nhập Admin</h4>
        </div>
        <div class="card-body">
            <?php if ($error != ""): ?>
                <div class="alert alert-danger py-2"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Tên đăng nhập</label>
                    <input type="text" name="username" class="form-control"
                           value="<?php echo htmlspecialchars($user ?? ""); ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Mật khẩu</label>
                    <input type="password" name="password" class="form-control">
                </div>
                <button type="submit" name="btnAdminLogin"
                        class="btn btn-primary w-100">
                    Đăng nhập
                </button>
            </form>
        </div>
    </div>
</div>

</body>
</html>
