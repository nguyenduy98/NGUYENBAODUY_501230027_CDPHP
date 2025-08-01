<?php
session_start();
require_once __DIR__ . '/../app/models/Database.php';
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user'];
$error = '';
$success = '';
// Xử lý cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    if ($username !== '') {
        $stmt = $db->prepare("UPDATE users SET username=? WHERE id=?");
        $stmt->execute([$username, $userId]);
        $_SESSION['username'] = $username;
        $success = "Cập nhật thành công!";
    } else {
        $error = "Tên không được để trống!";
    }
}
$stmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thông tin cá nhân</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5" style="max-width: 400px;">
    <h2 class="mb-4 text-center">Thông tin cá nhân</h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= $error ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= $success ?></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3">
            <label class="form-label">Tên đăng nhập</label>
            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
        </div>
        <button type="submit" class="btn btn-primary w-100">Cập nhật</button>
    </form>
</div>
</body>
</html> 