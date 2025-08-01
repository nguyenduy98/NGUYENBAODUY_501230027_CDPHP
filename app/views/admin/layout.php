<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản trị Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background: #fff; color: #222; }
        .sidebar-admin {
            min-height: 100vh;
            background: #111;
            border-radius: 24px 0 0 0;
            padding-top: 24px;
        }
        .sidebar-admin h4 {
            font-weight: bold;
            text-align: center;
            margin-bottom: 32px;
            letter-spacing: 1px;
            color: #fff;
        }
        .sidebar-admin a {
            color: #eee;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 18px;
            text-decoration: none;
            border-radius: 10px;
            margin-bottom: 8px;
            font-size: 17px;
            transition: background 0.2s, color 0.2s;
        }
        .sidebar-admin a.active, .sidebar-admin a:hover {
            background: #fff;
            color: #111;
        }
        .sidebar-admin a.text-danger {
            color: #e53935 !important;
        }
        .main-content { padding: 32px; background: #fff; color: #222; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar-admin">
            <h4>QUẢN TRỊ</h4>
            <a href="admin_dashboard.php" class="<?= (!isset($_GET['page']) || $_GET['page']=='') ? 'active' : '' ?>">
                <i class="fa-solid fa-list"></i> Thống kê
            </a>
            <a href="admin_dashboard.php?page=products" class="<?= ($_GET['page'] ?? '') == 'products' ? 'active' : '' ?>">
                <i class="fa-solid fa-box-open"></i> Quản lý sản phẩm
            </a>
            <a href="admin_dashboard.php?page=users" class="<?= ($_GET['page'] ?? '') == 'users' ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> Quản lý người dùng
            </a>
            <a href="admin_dashboard.php?page=orders" class="<?= ($_GET['page'] ?? '') == 'orders' ? 'active' : '' ?>">
                <i class="fa-solid fa-cart-shopping"></i> Quản lý đơn hàng
            </a>
            <a href="admin_dashboard.php?page=coupons" class="<?= ($_GET['page'] ?? '') == 'coupons' ? 'active' : '' ?>">
                <i class="fa-solid fa-tag"></i> Quản lý mã giảm giá
            </a>
            <a href="admin_dashboard.php?page=reviews" class="<?= ($_GET['page'] ?? '') == 'reviews' ? 'active' : '' ?>">
                <i class="fa-solid fa-star"></i> Quản lý đánh giá
            </a>
            <a href="fix_orders.php">
                <i class="fa-solid fa-tools"></i> Sửa lỗi đơn hàng
            </a>
            <a href="logout.php" class="text-danger">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
            </a>
        </nav>
        <main class="col-md-10 main-content">
            <?php if (isset($content)) echo $content; ?>
        </main>
    </div>
</div>

<!-- Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<?php if (isset($scripts)) echo $scripts; ?>

</body>
</html> 