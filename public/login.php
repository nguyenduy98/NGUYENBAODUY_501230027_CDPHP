<?php
session_start();
require_once __DIR__ . '/../app/models/Database.php';
$error = '';
$success = '';

// Thêm PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $login_id = trim($_POST['login_id'] ?? '');
    $password = $_POST['password'] ?? '';
    if ($login_id === '' || $password === '') {
        $error = 'Vui lòng nhập đầy đủ thông tin.';
    } else {
        $db = Database::getInstance()->getConnection();
        // Kiểm tra là email hay username
        if (filter_var($login_id, FILTER_VALIDATE_EMAIL)) {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        } else {
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        }
        $stmt->execute([$login_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user && password_verify($password, $user['password'])) {
            // Kiểm tra trạng thái active
            if (($user['active'] ?? 1) == 0) {
                $error = 'Tài khoản của bạn đã bị khóa! Vui lòng liên hệ admin để được hỗ trợ.';
            } else {
                $_SESSION['user'] = $user['id'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                if (($user['role'] ?? '') === 'admin') {
                    header('Location: admin_dashboard.php');
                    exit;
                } else {
                    header('Location: index.php');
                    exit;
                }
            }
        } else {
            $error = 'Tên đăng nhập/email hoặc mật khẩu không đúng!';
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot'])) {
    $email = trim($_POST['forgot_email'] ?? '');
    if ($email === '') {
        $error = 'Vui lòng nhập email để lấy lại mật khẩu!';
    } else {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($user) {
            // Tạo mật khẩu mới ngẫu nhiên
            $newPass = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 8);
            $hash = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password=? WHERE id=?");
            $stmt->execute([$hash, $user['id']]);
            // Gửi email (dùng PHPMailer)
            require_once __DIR__ . '/../vendor/autoload.php';
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nguyenduy220598@gmail.com'; // Thay bằng email gửi
                $mail->Password = 'xpdnrqpmwqlvzdlw'; // Thay bằng app password
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->setFrom('your_email@gmail.com', 'Tên website');
                $mail->addAddress($email, $user['username']);
                $mail->isHTML(true);
                $mail->Subject = 'Lấy lại mật khẩu';
                $mail->Body = 'Mật khẩu mới của bạn là: <b>' . $newPass . '</b><br>Vui lòng đăng nhập và đổi lại mật khẩu.';
                $mail->send();
                $success = 'Mật khẩu mới đã được gửi tới email của bạn!';
            } catch (Exception $e) {
                $error = 'Không gửi được email. Vui lòng thử lại sau!';
            }
        } else {
            $error = 'Email không tồn tại!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập</title>
    <link rel="stylesheet" href="/test/public/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../app/views/partials/header.php'; ?>
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="row bg-white shadow rounded-4 p-4 align-items-stretch">
                <div class="col-md-6 border-end">
                    <h3 class="mb-4 fw-bold" style="color:#1976d2;">ĐĂNG NHẬP TÀI KHOẢN</h3>
                    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
                    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email hoặc tên đăng nhập <span class="text-danger">*</span></label>
                            <input type="text" name="login_id" class="form-control rounded-3" placeholder="Email hoặc tên đăng nhập" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control rounded-3" placeholder="Mật khẩu" required>
                        </div>
                        <button type="submit" name="login" class="btn w-100 fw-bold" style="background:#1976d2; color:#fff; border-radius:8px;">Đăng nhập</button>
                        <div class="mt-3 text-center">
                            <a href="register.php" style="color:#1976d2; text-decoration:underline;">Đăng ký</a>
                        </div>
                    </form>
                </div>
                <div class="col-md-6">
                    <h5 class="mb-4 fw-bold" style="color:#1976d2;">Quên mật khẩu?</h5>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" name="forgot_email" class="form-control rounded-3" placeholder="Email" required>
                        </div>
                        <button type="submit" name="forgot" class="btn w-100 fw-bold" style="background:#1976d2; color:#fff; border-radius:8px;">Lấy lại mật khẩu</button>
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