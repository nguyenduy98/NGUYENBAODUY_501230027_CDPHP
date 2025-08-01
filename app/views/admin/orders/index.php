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

// Xử lý cập nhật trạng thái
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $orderId = $_POST['order_id'];
    $status = $_POST['status'];

    // Lấy trạng thái cũ của đơn hàng
    $stmt = $db->prepare("SELECT status FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $oldStatus = $stmt->fetchColumn();

    // Lấy danh sách sản phẩm trong đơn
    $orderItems = $db->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
    $orderItems->execute([$orderId]);
    $items = $orderItems->fetchAll(PDO::FETCH_ASSOC);

    // Nếu chuyển sang hoàn thành (delivered) và trước đó chưa phải delivered => trừ kho
    if ($status === 'delivered' && $oldStatus !== 'delivered') {
        foreach ($items as $item) {
            $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")
                ->execute([$item['quantity'], $item['product_id']]);
        }
    }
    // Nếu chuyển sang hủy (cancelled) => luôn cộng lại kho (tránh cộng lặp)
    if ($status === 'cancelled') {
        foreach ($items as $item) {
            $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")
                ->execute([$item['quantity'], $item['product_id']]);
        }
    }
    // Cập nhật trạng thái đơn hàng
    $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $orderId]);
    // Giữ lại trạng thái hiện tại khi redirect
    $currentStatus = $_GET['status'] ?? 'all';
    $currentStatusParam = $currentStatus !== 'all' ? "&status=$currentStatus" : '';
    header('Location: admin_dashboard.php?page=orders&success=1' . $currentStatusParam);
    exit;
}

// Xử lý xóa nhiều đơn hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_multiple'])) {
    $orderIds = $_POST['order_ids'] ?? [];
    if (!empty($orderIds)) {
        // Xóa order_items trước
        $placeholders = str_repeat('?,', count($orderIds) - 1) . '?';
        $stmt = $db->prepare("DELETE FROM order_items WHERE order_id IN ($placeholders)");
        $stmt->execute($orderIds);
        
        // Sau đó xóa orders
        $stmt = $db->prepare("DELETE FROM orders WHERE id IN ($placeholders)");
        $stmt->execute($orderIds);
        
        // Giữ lại trạng thái hiện tại khi redirect
        $currentStatusParam = $currentStatus !== 'all' ? "&status=$currentStatus" : '';
        header('Location: admin_dashboard.php?page=orders&deleted=' . count($orderIds) . $currentStatusParam);
        exit;
    }
}

// Lấy trạng thái hiện tại từ URL
$currentStatus = $_GET['status'] ?? 'all';

// Lấy danh sách đơn hàng với thông tin sản phẩm
$whereClause = "";
if ($currentStatus !== 'all') {
    $whereClause = "WHERE o.status = '$currentStatus'";
}

$orders = $db->query("
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
    $whereClause
    ORDER BY o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Đếm số lượng đơn hàng theo từng trạng thái
$statusCounts = [];
$allStatuses = ['all', 'pending', 'processing', 'shipped', 'delivered', 'cancelled'];
foreach ($allStatuses as $status) {
    if ($status === 'all') {
        $count = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    } else {
        $count = $db->query("SELECT COUNT(*) FROM orders WHERE status = '$status'")->fetchColumn();
    }
    $statusCounts[$status] = $count;
}
$statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

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
    <title>Quản lý đơn hàng</title>
    <link rel="stylesheet" href="/test/public/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .select-all-container {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .delete-selected-btn {
            margin-bottom: 15px;
        }
        .checkbox-cell {
            width: 50px;
            text-align: center;
        }
        .admin-sidebar {
            min-height: 100vh;
            background: #111;
            border-radius: 24px 0 0 0;
            padding-top: 24px;
        }
        .admin-sidebar h4 {
            font-weight: bold;
            text-align: center;
            margin-bottom: 32px;
            letter-spacing: 1px;
            color: #fff;
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
            font-size: 17px;
            transition: background 0.2s, color 0.2s;
        }
        .admin-sidebar a.active, .admin-sidebar a:hover {
            background: #fff;
            color: #111;
        }
        .admin-sidebar a.text-danger {
            color: #e53935 !important;
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
            <h2 class="mb-4">Quản lý đơn hàng</h2>

            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success">Cập nhật trạng thái thành công!</div>
            <?php endif; ?>

            <?php if (isset($_GET['deleted'])): ?>
                <div class="alert alert-success">Đã xóa <?= $_GET['deleted'] ?> đơn hàng thành công!</div>
            <?php endif; ?>

            <!-- Tabs cho từng trạng thái -->
            <ul class="nav nav-tabs mb-4" id="orderTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $currentStatus === 'all' ? 'active' : '' ?>" 
                       href="admin_dashboard.php?page=orders&status=all">
                        Tất cả <span class="badge bg-secondary"><?= $statusCounts['all'] ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $currentStatus === 'pending' ? 'active' : '' ?>" 
                       href="admin_dashboard.php?page=orders&status=pending">
                        Chờ xử lý <span class="badge bg-warning"><?= $statusCounts['pending'] ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $currentStatus === 'processing' ? 'active' : '' ?>" 
                       href="admin_dashboard.php?page=orders&status=processing">
                        Đang xử lý <span class="badge bg-info"><?= $statusCounts['processing'] ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $currentStatus === 'shipped' ? 'active' : '' ?>" 
                       href="admin_dashboard.php?page=orders&status=shipped">
                        Đang giao <span class="badge bg-primary"><?= $statusCounts['shipped'] ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $currentStatus === 'delivered' ? 'active' : '' ?>" 
                       href="admin_dashboard.php?page=orders&status=delivered">
                        Hoàn thành <span class="badge bg-success"><?= $statusCounts['delivered'] ?></span>
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link <?= $currentStatus === 'cancelled' ? 'active' : '' ?>" 
                       href="admin_dashboard.php?page=orders&status=cancelled">
                        Đã hủy <span class="badge bg-danger"><?= $statusCounts['cancelled'] ?></span>
                    </a>
                </li>
            </ul>

            <button type="button" class="btn btn-danger delete-selected-btn" id="deleteBtn" style="display: none;" onclick="deleteSelectedOrders()">
                <i class="fa fa-trash"></i> Xóa đã chọn (<span id="selectedCount">0</span>)
            </button>
            
            <!-- Form ẩn để xử lý xóa nhiều đơn hàng -->
            <form method="POST" id="deleteForm" style="display: none;">
                <input type="hidden" name="delete_multiple" value="1">
            </form>
            
            <table class="table table-bordered table-striped bg-white">
                <thead class="table-dark">
                    <tr>
                        <th class="checkbox-cell">
                            <div class="select-all-container">
                                <input type="checkbox" id="selectAll" class="form-check-input">
                                <label for="selectAll" class="form-check-label text-white">Tất cả</label>
                            </div>
                        </th>
                        <th>Mã Đơn</th>
                        <th>Sản Phẩm</th>
                        <th>Khách Hàng</th>
                        <th>Địa Chỉ</th>
                        <th>Ngày Đặt</th>
                        <th>Tổng Tiền</th>
                        <th>Trạng Thái</th>
                        <th>Hành Động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="checkbox-cell">
                                <input type="checkbox" name="order_ids[]" value="<?= $order['id'] ?>" class="form-check-input order-checkbox">
                            </td>
                            <td>#<?= $order['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <?php if ($order['img'] && $order['product_name'] !== 'Sản phẩm đã bị xóa'): ?>
                                        <img src="<?= htmlspecialchars($order['img']) ?>" 
                                             alt="<?= htmlspecialchars($order['product_name']) ?>"
                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 4px; margin-right: 10px;"
                                             onerror="this.src='https://via.placeholder.com/40x40/eee/999?text=No+Image'">
                                    <?php else: ?>
                                        <div style="width: 40px; height: 40px; background: #f8d7da; border-radius: 4px; display: flex; align-items: center; justify-content: center; margin-right: 10px;">
                                            <i class="fa fa-trash text-danger" style="font-size: 12px;"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="fw-bold <?= $order['product_name'] === 'Sản phẩm đã bị xóa' ? 'text-danger' : '' ?>">
                                            <?= htmlspecialchars($order['product_name']) ?>
                                        </div>
                                        <small class="text-muted">ID: <?= $order['product_id'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?= htmlspecialchars($order['fullname']) ?></td>
                            <td>
                                <small>
                                    <?= htmlspecialchars($order['address']) ?><br>
                                    <?= htmlspecialchars($order['ward_name'] . ', ' . $order['district_name'] . ', ' . $order['city_name']) ?>
                                </small>
                            </td>
                            <td><?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></td>
                            <td><?= number_format($order['total']) ?>đ</td>
                            <td>
                                <form method="POST" class="d-flex status-form">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <select name="status" class="form-select form-select-sm">
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= $status ?>" <?= $order['status'] == $status ? 'selected' : '' ?>>
                                                <?= $statusLabels[$status] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" name="update_status" class="btn btn-primary btn-sm ms-2">Lưu</button>
                                </form>
                            </td>
                            <td>
                                <a href="admin_dashboard.php?page=order_details&id=<?= $order['id'] ?>" class="btn btn-info btn-sm">Xem</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectAllCheckbox = document.getElementById('selectAll');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    const deleteBtn = document.getElementById('deleteBtn');
    const selectedCountSpan = document.getElementById('selectedCount');
    const deleteForm = document.getElementById('deleteForm');

    // Xử lý checkbox "Tất cả"
    selectAllCheckbox.addEventListener('change', function() {
        orderCheckboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateDeleteButton();
    });

    // Xử lý từng checkbox đơn hàng
    orderCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            updateSelectAllCheckbox();
            updateDeleteButton();
        });
    });

    // Cập nhật trạng thái checkbox "Tất cả"
    function updateSelectAllCheckbox() {
        const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
        const totalCount = orderCheckboxes.length;
        
        if (checkedCount === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedCount === totalCount) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        }
    }

    // Cập nhật nút xóa
    function updateDeleteButton() {
        const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
        selectedCountSpan.textContent = checkedCount;
        
        if (checkedCount > 0) {
            deleteBtn.style.display = 'inline-block';
        } else {
            deleteBtn.style.display = 'none';
        }
    }

    // Xử lý xóa nhiều đơn hàng
    window.deleteSelectedOrders = function() {
        const checkedBoxes = document.querySelectorAll('.order-checkbox:checked');
        const checkedCount = checkedBoxes.length;
        
        if (checkedCount === 0) {
            alert('Vui lòng chọn ít nhất một đơn hàng để xóa!');
            return;
        }
        
        if (confirm(`Bạn có chắc muốn xóa ${checkedCount} đơn hàng đã chọn?`)) {
            const deleteForm = document.getElementById('deleteForm');
            
            // Thêm các checkbox đã chọn vào form
            checkedBoxes.forEach(checkbox => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'order_ids[]';
                input.value = checkbox.value;
                deleteForm.appendChild(input);
            });
            
            // Submit form
            deleteForm.submit();
        }
    }
});
</script>
</body>
</html> 