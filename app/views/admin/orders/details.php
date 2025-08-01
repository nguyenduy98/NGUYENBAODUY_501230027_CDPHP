<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 3) . '/models/Database.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header('Location: /test/public/admin_login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    header('Location: admin_dashboard.php?page=orders');
    exit;
}

// Lấy thông tin đơn hàng với thông tin sản phẩm
$stmt = $db->prepare("
    SELECT o.*, 
           COALESCE(p.name, 'Sản phẩm không tồn tại') as product_name, 
           COALESCE(p.price, o.total) as product_price, 
           p.img,
           COALESCE(c.name, o.city) as city_name,
           COALESCE(d.name, o.district) as district_name,
           COALESCE(w.name, o.ward) as ward_name,
           oi.quantity as item_quantity
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id 
    LEFT JOIN cities c ON o.city = c.id
    LEFT JOIN districts d ON o.district = d.id
    LEFT JOIN wards w ON o.ward = w.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Mapping tên trạng thái sang tiếng Việt
$statusLabels = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý', 
    'shipped' => 'Đang giao',
    'delivered' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Chi tiết đơn hàng #<?= $orderId ?></title>
    <link rel="stylesheet" href="/test/public/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: #fff; 
            color: #222; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-sidebar {
            min-height: 100vh;
            background: #111;
            border-radius: 24px 0 0 0;
            padding-top: 24px;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        .admin-sidebar h4 {
            font-weight: bold;
            text-align: center;
            margin-bottom: 32px;
            letter-spacing: 1px;
            color: #fff;
            font-size: 18px;
        }
        .admin-sidebar a {
            color: #eee;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 8px;
            font-size: 15px;
            transition: background 0.2s, color 0.2s;
        }
        .admin-sidebar a.active, .admin-sidebar a:hover {
            background: #fff;
            color: #111;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .admin-sidebar a.text-danger {
            color: #dc3545 !important;
        }
        .admin-main-content {
            padding: 32px;
            background: #fff;
        }
        .card {
            border: 1px solid #e0e0e0;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }
        .card-header {
            background: #f8f9fa;
            border-bottom: 1px solid #e0e0e0;
            padding: 16px 20px;
            font-weight: 600;
            color: #333;
            border-radius: 12px 12px 0 0;
        }
        .card-body {
            padding: 20px;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
            font-weight: 600;
            color: #333;
        }
        .table td {
            vertical-align: middle;
            border-bottom: 1px solid #f0f0f0;
        }
        .badge {
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 20px;
        }
        .btn {
            border-radius: 8px;
            font-weight: 500;
            padding: 10px 20px;
            margin-right: 10px;
        }
        .btn-danger {
            background: #dc3545;
            border-color: #dc3545;
        }
        .btn-secondary {
            background: #6c757d;
            border-color: #6c757d;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .product-placeholder {
            width: 60px;
            height: 60px;
            background: #f8f9fa;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            font-size: 12px;
            border: 1px solid #e0e0e0;
        }
        .order-info p {
            margin-bottom: 12px;
            font-size: 14px;
        }
        .order-info strong {
            color: #333;
            font-weight: 600;
        }
        .total-row {
            background: #f8f9fa;
            font-weight: 600;
        }
        .total-row td {
            border-top: 2px solid #dee2e6;
        }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 admin-sidebar">
            <h4 class="mt-4 mb-4 text-center">QUẢN TRỊ</h4>
            <a href="admin_dashboard.php" class="<?= !isset($_GET['page']) ? 'active' : '' ?>"><i class="fa fa-chart-bar"></i> Thống kê</a>
            <a href="admin_dashboard.php?page=products" class="<?= ($_GET['page'] ?? '') == 'products' ? 'active' : '' ?>"><i class="fa fa-gem"></i> Quản lý sản phẩm</a>
            <a href="admin_dashboard.php?page=users" class="<?= ($_GET['page'] ?? '') == 'users' ? 'active' : '' ?>"><i class="fa fa-users"></i> Quản lý người dùng</a>
            <a href="admin_dashboard.php?page=orders" class="<?= ($_GET['page'] ?? '') == 'orders' ? 'active' : '' ?>"><i class="fa fa-shopping-cart"></i> Quản lý đơn hàng</a>
            <a href="admin_dashboard.php?page=coupons" class="<?= ($_GET['page'] ?? '') == 'coupons' ? 'active' : '' ?>"><i class="fa fa-tag"></i> Quản lý mã giảm giá</a>
            <a href="logout.php" class="text-danger"><i class="fa fa-sign-out-alt"></i> Đăng xuất</a>
        </nav>
        <main class="col-md-10 admin-main-content">
            <h2 class="mb-4">Chi tiết đơn hàng #<?= $orderId ?></h2>
            <div class="card">
                <div class="card-header">Thông tin đơn hàng</div>
                <div class="card-body">
                    <div class="row order-info">
                        <div class="col-md-6">
                            <p><strong>Khách hàng:</strong> <?= htmlspecialchars($order['fullname']) ?></p>
                            <p><strong>Số điện thoại:</strong> <?= htmlspecialchars($order['phone']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($order['email']) ?></p>
                            <p><strong>Địa chỉ:</strong> <?= htmlspecialchars($order['address'] . ', ' . $order['ward_name'] . ', ' . $order['district_name'] . ', ' . $order['city_name']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Ngày đặt:</strong> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></p>
                            <p><strong>Trạng thái:</strong> <span class="badge bg-info"><?= $statusLabels[$order['status']] ?? ucfirst($order['status']) ?></span></p>
                            <p><strong>Phương thức thanh toán:</strong> <?= htmlspecialchars($order['payment']) ?></p>
                            <p><strong>Ghi chú:</strong> <?= htmlspecialchars($order['note']) ?></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Sản phẩm đã mua</div>
                <div class="card-body">
                    <table class="table">
                        <thead>
                            <tr>
                                <th style="width: 80px;">Hình ảnh</th>
                                <th>Tên sản phẩm</th>
                                <th>Số lượng</th>
                                <th>Đơn giá</th>
                                <th>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <?php if ($order['img']): ?>
                                        <img src="<?= htmlspecialchars($order['img']) ?>" 
                                             alt="<?= htmlspecialchars($order['product_name']) ?>" 
                                             class="product-image"
                                             onerror="this.src='https://via.placeholder.com/60x60/eee/999?text=No+Image'">
                                    <?php else: ?>
                                        <div class="product-placeholder">
                                            <i class="fa fa-image"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($order['product_name']) ?></td>
                                <td><?= $order['quantity'] ?? 1 ?></td>
                                <td><?= number_format($order['product_price']) ?>đ</td>
                                <td><?= number_format($order['total']) ?>đ</td>
                            </tr>
                        </tbody>
                        <tfoot>
                            <tr class="total-row">
                                <td colspan="4" class="text-end"><strong>Tổng cộng:</strong></td>
                                <td><strong><?= number_format($order['total']) ?>đ</strong></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <a href="/test/public/generate_invoice.php?id=<?= $orderId ?>" target="_blank" class="btn btn-danger"><i class="fa fa-print"></i> In Hóa Đơn</a>
            <a href="admin_dashboard.php?page=orders" class="btn btn-secondary">Quay lại danh sách</a>
        </main>
    </div>
</div>
</body>
</html> 