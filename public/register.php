<?php
session_start();
require_once __DIR__ . '/../app/models/Database.php';
$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $email = trim($_POST['email']);

    // Kiểm tra username đã tồn tại chưa
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $error = 'Tên đăng nhập đã tồn tại!';
    } else {
        // Kiểm tra email đã tồn tại chưa
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email đã tồn tại!';
        } else {
            // Hash password
            $hash = password_hash($password, PASSWORD_DEFAULT);
            // Thêm user mới
            $stmt = $db->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, 'user')");
            $stmt->execute([$username, $hash, $email]);
            $success = 'Đăng ký thành công! Đang chuyển đến trang chủ...';
            // Đăng nhập tự động sau khi đăng ký
            $_SESSION['user'] = $db->lastInsertId();
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký</title>
    <link rel="stylesheet" href="/test/public/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../app/views/partials/header.php'; ?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="row bg-white shadow rounded-4 p-4 align-items-stretch">
                <div class="col-md-12">
                    <h3 class="mb-4 fw-bold text-center" style="color:#1976d2;">ĐĂNG KÝ TÀI KHOẢN</h3>
                    <p class="text-center mb-4" style="color:#666;">Nếu bạn chưa có tài khoản, đăng ký tại đây.</p>
                    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                    <form method="POST" class="mx-auto" style="max-width:500px;">
                        <div class="mb-3">
                            <label class="form-label">Tên đăng nhập <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control rounded-3" placeholder="Tên đăng nhập" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control rounded-3" placeholder="Email" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control rounded-3" placeholder="Mật khẩu" required>
                        </div>
                        <button type="submit" class="btn w-100 fw-bold" style="background:#1976d2; color:#fff; border-radius:8px;">Đăng ký</button>
                        <div class="mt-3 text-center">
                            <a href="login.php" style="color:#1976d2; text-decoration:underline;">Đã có tài khoản? Đăng nhập</a>
                        </div>
                        <div class="mt-3 text-center">
                            <span style="color:#888;">Hoặc đăng ký bằng</span><br>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2"><i class="fab fa-facebook me-1"></i> Facebook</button>
                            <button type="button" class="btn btn-outline-danger btn-sm mt-2"><i class="fab fa-google me-1"></i> Google</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../app/views/user/layout_footer.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 