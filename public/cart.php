<?php
session_start();
require_once __DIR__ . '/../app/models/Database.php';
// Lấy giỏ hàng từ session
$cart = $_SESSION['cart'] ?? [];
$db = Database::getInstance()->getConnection();
// Lấy thông tin sản phẩm trong giỏ
$products = [];
$total = 0;
foreach ($cart as $item) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$item['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) {
        $product['price'] = (int)preg_replace('/[^0-9]/', '', $product['price']);
        $product['quantity'] = $item['quantity'];
        $product['subtotal'] = $product['price'] * $item['quantity'];
        $products[] = $product;
        $total += $product['subtotal'];
    }
}
// Xử lý xóa sản phẩm khỏi giỏ
if (isset($_GET['remove'])) {
    $removeId = (int)$_GET['remove'];
    foreach ($_SESSION['cart'] as $k => $item) {
        if ($item['product_id'] == $removeId) {
            unset($_SESSION['cart'][$k]);
            break;
        }
    }
    header('Location: cart.php');
    exit;
}
// Xử lý cập nhật số lượng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cart'])) {
    foreach ($_POST['qty'] as $productId => $qty) {
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $productId) {
                $item['quantity'] = max(1, (int)$qty);
            }
        }
    }
    header('Location: cart.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Giỏ hàng</title>
    <link rel="stylesheet" href="/test/public/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include __DIR__ . '/../app/views/partials/header.php'; ?>
<div class="container mt-4">
    <h2>Giỏ hàng của bạn</h2>
    <?php if (empty($products)): ?>
        <div class="alert alert-info">Giỏ hàng trống.</div>
    <?php else: ?>
    <form method="POST">
    <table class="table table-bordered align-middle bg-white">
        <thead>
            <tr>
                <th>Hình</th>
                <th>Tên sản phẩm</th>
                <th>Giá</th>
                <th>Số lượng</th>
                <th>Thành tiền</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
            <tr>
                <td><img src="<?= htmlspecialchars($product['img']) ?>" style="max-width:60px;"></td>
                <td><?= htmlspecialchars($product['name']) ?></td>
                <td><?= number_format((int)$product['price']) ?>đ</td>
                <td style="width:100px;">
                    <input type="number" name="qty[<?= $product['id'] ?>]" value="<?= $product['quantity'] ?>" min="1" class="form-control">
                </td>
                <td><?= number_format((int)$product['subtotal']) ?>đ</td>
                <td><a href="cart.php?remove=<?= $product['id'] ?>" class="btn btn-danger btn-sm">Xóa</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <button type="submit" name="update_cart" class="btn btn-secondary">Cập nhật giỏ hàng</button>
        </div>
        <div>
            <b>Tổng cộng: <span class="text-danger fs-5"><?= number_format((int)$total) ?>đ</span></b>
            <a href="order.php" class="btn btn-primary ms-3">Thanh toán</a>
        </div>
    </div>
    </form>
    <?php endif; ?>
</div>
</body>
</html> 