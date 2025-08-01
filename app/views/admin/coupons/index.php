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

// Xử lý thêm mã giảm giá mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_coupon'])) {
    $code = trim($_POST['code']);
    $discount_type = $_POST['discount_type'];
    $discount_value = (float)$_POST['discount_value'];
    $max_discount_amount = !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : null;
    $min_order_amount = (float)$_POST['min_order_amount'];
    $max_uses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    
    // Kiểm tra mã đã tồn tại
    $stmt = $db->prepare("SELECT id FROM coupons WHERE code = ?");
    $stmt->execute([$code]);
    if ($stmt->fetch()) {
        $error = 'Mã giảm giá đã tồn tại!';
    } else {
        $stmt = $db->prepare("INSERT INTO coupons (code, discount_type, discount_value, max_discount_amount, min_order_amount, max_uses, start_date, end_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$code, $discount_type, $discount_value, $max_discount_amount, $min_order_amount, $max_uses, $start_date, $end_date]);
        $success = 'Thêm mã giảm giá thành công!';
    }
}

// Xử lý cập nhật mã giảm giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_coupon'])) {
    $id = (int)$_POST['coupon_id'];
    $code = trim($_POST['code']);
    $discount_type = $_POST['discount_type'];
    $discount_value = (float)$_POST['discount_value'];
    $max_discount_amount = !empty($_POST['max_discount_amount']) ? (float)$_POST['max_discount_amount'] : null;
    $min_order_amount = (float)$_POST['min_order_amount'];
    $max_uses = !empty($_POST['max_uses']) ? (int)$_POST['max_uses'] : null;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Kiểm tra mã đã tồn tại (trừ chính nó)
    $stmt = $db->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
    $stmt->execute([$code, $id]);
    if ($stmt->fetch()) {
        $error = 'Mã giảm giá đã tồn tại!';
    } else {
        $stmt = $db->prepare("UPDATE coupons SET code=?, discount_type=?, discount_value=?, max_discount_amount=?, min_order_amount=?, max_uses=?, start_date=?, end_date=?, is_active=? WHERE id=?");
        $stmt->execute([$code, $discount_type, $discount_value, $max_discount_amount, $min_order_amount, $max_uses, $start_date, $end_date, $is_active, $id]);
        $success = 'Cập nhật mã giảm giá thành công!';
    }
}

// Xử lý xóa mã giảm giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_coupon'])) {
    $id = (int)$_POST['coupon_id'];
    $stmt = $db->prepare("DELETE FROM coupons WHERE id = ?");
    $stmt->execute([$id]);
    $success = 'Xóa mã giảm giá thành công!';
}

// Lấy danh sách mã giảm giá
$coupons = $db->query("SELECT * FROM coupons ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quản lý mã giảm giá</title>
    <link rel="stylesheet" href="/test/public/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .coupon-form {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .status-active { color: #28a745; }
        .status-inactive { color: #dc3545; }
        .expired { color: #6c757d; }
        /* Sidebar trắng đen */
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
            <a href="admin_dashboard.php"><i class="fa fa-chart-bar"></i> Thống kê</a>
            <a href="admin_dashboard.php?page=products"><i class="fa fa-gem"></i> Quản lý sản phẩm</a>
            <a href="admin_dashboard.php?page=users"><i class="fa fa-users"></i> Quản lý người dùng</a>
            <a href="admin_dashboard.php?page=orders"><i class="fa fa-shopping-cart"></i> Quản lý đơn hàng</a>
            <a href="admin_dashboard.php?page=coupons" class="active"><i class="fa fa-tag"></i> Quản lý mã giảm giá</a>
            <a href="logout.php" class="text-danger"><i class="fa fa-sign-out-alt"></i> Đăng xuất</a>
        </nav>
        <main class="col-md-10 admin-main-content">
            <h2 class="mb-4">Quản lý mã giảm giá</h2>

            <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?= $error ?></div>
            <?php endif; ?>

            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>

            <!-- Form thêm mã giảm giá -->
            <div class="coupon-form">
                <h5 class="mb-3">Thêm mã giảm giá mới</h5>
                <form method="POST">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="form-label">Mã giảm giá</label>
                            <input type="text" name="code" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Loại giảm giá</label>
                            <select name="discount_type" class="form-select" required>
                                <option value="percentage">Phần trăm (%)</option>
                                <option value="fixed">Số tiền cố định</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Giá trị giảm</label>
                            <input type="number" name="discount_value" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Giảm tối đa</label>
                            <input type="number" name="max_discount_amount" class="form-control" placeholder="Không giới hạn">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Đơn hàng tối thiểu</label>
                            <input type="number" name="min_order_amount" class="form-control" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Số lần sử dụng tối đa</label>
                            <input type="number" name="max_uses" class="form-control" placeholder="Không giới hạn">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-3">
                            <label class="form-label">Ngày bắt đầu</label>
                            <input type="date" name="start_date" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Ngày kết thúc</label>
                            <input type="date" name="end_date" class="form-control" required>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" name="add_coupon" class="btn btn-primary">Thêm mã giảm giá</button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Bảng mã giảm giá -->
            <table class="table table-bordered table-striped bg-white">
                <thead class="table-dark">
                    <tr>
                        <th>Mã</th>
                        <th>Loại giảm giá</th>
                        <th>Giá trị</th>
                        <th>Giảm tối đa</th>
                        <th>Đơn hàng tối thiểu</th>
                        <th>Sử dụng</th>
                        <th>Ngày hiệu lực</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($coupons as $coupon): ?>
                        <?php 
                        $isExpired = $coupon['end_date'] < date('Y-m-d');
                        $isActive = $coupon['is_active'] && !$isExpired;
                        ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($coupon['code']) ?></strong></td>
                            <td><?= $coupon['discount_type'] === 'percentage' ? 'Phần trăm' : 'Số tiền cố định' ?></td>
                            <td>
                                <?php if ($coupon['discount_type'] === 'percentage'): ?>
                                    <?= $coupon['discount_value'] ?>%
                                <?php else: ?>
                                    <?= number_format($coupon['discount_value']) ?>đ
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($coupon['max_discount_amount']): ?>
                                    <?= number_format($coupon['max_discount_amount']) ?>đ
                                <?php else: ?>
                                    <span class="text-muted">Không giới hạn</span>
                                <?php endif; ?>
                            </td>
                            <td><?= number_format($coupon['min_order_amount']) ?>đ</td>
                            <td>
                                <?= $coupon['used_count'] ?>
                                <?php if ($coupon['max_uses']): ?>
                                    / <?= $coupon['max_uses'] ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= date('d/m/Y', strtotime($coupon['start_date'])) ?> - 
                                <?= date('d/m/Y', strtotime($coupon['end_date'])) ?>
                            </td>
                            <td>
                                <?php if ($isExpired): ?>
                                    <span class="expired">Hết hạn</span>
                                <?php elseif ($isActive): ?>
                                    <span class="status-active">Hoạt động</span>
                                <?php else: ?>
                                    <span class="status-inactive">Tạm ngưng</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button type="button" class="btn btn-primary btn-sm" onclick="editCoupon(<?= $coupon['id'] ?>, '<?= htmlspecialchars($coupon['code']) ?>', '<?= $coupon['discount_type'] ?>', <?= $coupon['discount_value'] ?>, <?= $coupon['max_discount_amount'] ?? 'null' ?>, <?= $coupon['min_order_amount'] ?>, <?= $coupon['max_uses'] ?? 'null' ?>, '<?= $coupon['start_date'] ?>', '<?= $coupon['end_date'] ?>', <?= $coupon['is_active'] ?>)">
                                    Sửa
                                </button>
                                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa mã giảm giá này?');">
                                    <input type="hidden" name="coupon_id" value="<?= $coupon['id'] ?>">
                                    <button type="submit" name="delete_coupon" class="btn btn-danger btn-sm">Xóa</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
</div>

<!-- Modal chỉnh sửa mã giảm giá -->
<div class="modal fade" id="editCouponModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Chỉnh sửa mã giảm giá</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="editCouponForm">
                <div class="modal-body">
                    <input type="hidden" name="coupon_id" id="edit_coupon_id">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Mã giảm giá</label>
                            <input type="text" name="code" id="edit_code" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Loại giảm giá</label>
                            <select name="discount_type" id="edit_discount_type" class="form-select" required>
                                <option value="percentage">Phần trăm (%)</option>
                                <option value="fixed">Số tiền cố định</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Giá trị giảm</label>
                            <input type="number" name="discount_value" id="edit_discount_value" class="form-control" step="0.01" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Giảm tối đa</label>
                            <input type="number" name="max_discount_amount" id="edit_max_discount_amount" class="form-control" placeholder="Không giới hạn">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Đơn hàng tối thiểu</label>
                            <input type="number" name="min_order_amount" id="edit_min_order_amount" class="form-control" value="0">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Số lần sử dụng tối đa</label>
                            <input type="number" name="max_uses" id="edit_max_uses" class="form-control" placeholder="Không giới hạn">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Trạng thái</label>
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="edit_is_active" class="form-check-input" value="1">
                                <label class="form-check-label" for="edit_is_active">Hoạt động</label>
                            </div>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Ngày bắt đầu</label>
                            <input type="date" name="start_date" id="edit_start_date" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Ngày kết thúc</label>
                            <input type="date" name="end_date" id="edit_end_date" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                    <button type="submit" name="update_coupon" class="btn btn-primary">Cập nhật</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function editCoupon(id, code, discountType, discountValue, maxDiscountAmount, minOrderAmount, maxUses, startDate, endDate, isActive) {
    document.getElementById('edit_coupon_id').value = id;
    document.getElementById('edit_code').value = code;
    document.getElementById('edit_discount_type').value = discountType;
    document.getElementById('edit_discount_value').value = discountValue;
    document.getElementById('edit_max_discount_amount').value = maxDiscountAmount === null ? '' : maxDiscountAmount;
    document.getElementById('edit_min_order_amount').value = minOrderAmount;
    document.getElementById('edit_max_uses').value = maxUses === null ? '' : maxUses;
    document.getElementById('edit_start_date').value = startDate;
    document.getElementById('edit_end_date').value = endDate;
    document.getElementById('edit_is_active').checked = isActive;
    
    new bootstrap.Modal(document.getElementById('editCouponModal')).show();
}
</script>
</body>
</html> 