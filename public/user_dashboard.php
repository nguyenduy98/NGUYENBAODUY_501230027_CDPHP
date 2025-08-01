<?php
session_start();
require_once __DIR__ . '/../app/models/Database.php';

// Kiểm tra trạng thái user
if (isset($_SESSION['user'])) {
    $currentUser = $_SESSION['user'];
    if (is_array($currentUser)) {
        $currentUserId = $currentUser['id'] ?? null;
    } else {
        $currentUserId = $currentUser;
    }
    
    if ($currentUserId) {
        $db = Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT active FROM users WHERE id = ?");
        $stmt->execute([$currentUserId]);
        $active = $stmt->fetchColumn();
        
        if (($active ?? 1) == 0) {
            // User bị khóa, đăng xuất và redirect
            session_destroy();
            header('Location: login.php?error=account_locked');
            exit;
        }
    }
}

function format_money($amount) {
    $amount = preg_replace('/[^0-9]/', '', $amount);
    return number_format((int)$amount, 0, '', ',');
}
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
$db = Database::getInstance()->getConnection();
$userId = $_SESSION['user'];

// Lấy thông tin user
$stmt = $db->prepare("SELECT * FROM users WHERE id=?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Thống kê
$totalOrder = $db->query("SELECT COUNT(*) FROM orders WHERE user_id = $userId")->fetchColumn();
$totalMoney = $db->query("SELECT SUM(o.total) FROM orders o WHERE o.user_id = $userId")->fetchColumn() ?: 0;
// Đơn hàng gần đây (join với products)
$orders = $db->query("SELECT o.*, p.name, p.price FROM orders o JOIN products p ON o.product_id = p.id WHERE o.user_id = $userId ORDER BY o.created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// Lấy tất cả đơn hàng cho tab orders
$allOrders = $db->query("
    SELECT o.*, 
           COALESCE(p.name, 'Sản phẩm không tồn tại') as product_name, 
           COALESCE(p.price, o.total) as product_price, 
           p.img,
           COALESCE(c.name, o.city) as city,
           COALESCE(d.name, o.district) as district,
           COALESCE(w.name, o.ward) as ward,
           oi.quantity as item_quantity
    FROM orders o 
    LEFT JOIN order_items oi ON o.id = oi.order_id
    LEFT JOIN products p ON oi.product_id = p.id 
    LEFT JOIN cities c ON o.city = c.id
    LEFT JOIN districts d ON o.district = d.id
    LEFT JOIN wards w ON o.ward = w.id
    WHERE o.user_id = $userId 
    ORDER BY o.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Xử lý tab
$tab = $_GET['tab'] ?? 'dashboard';

// Thông báo
$info_error = $info_success = $pass_error = $pass_success = $order_error = $order_success = '';

// Xử lý hủy đơn hàng
if (isset($_POST['cancel_order'])) {
    $orderId = $_POST['order_id'] ?? 0;
    if ($orderId) {
        // Kiểm tra đơn hàng thuộc về user này
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Chỉ cho phép hủy đơn hàng đang chờ hoặc đang xử lý
            if (in_array($order['status'], ['pending', 'processing'])) {
                $stmt = $db->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
                $stmt->execute([$orderId]);
                $order_success = 'Đã hủy đơn hàng thành công!';
                // Refresh lại danh sách đơn hàng
                $allOrders = $db->query("
                    SELECT o.*, 
                           COALESCE(p.name, 'Sản phẩm không tồn tại') as product_name, 
                           COALESCE(p.price, o.total) as product_price, 
                           p.img,
                           COALESCE(c.name, o.city) as city,
                           COALESCE(d.name, o.district) as district,
                           COALESCE(w.name, o.ward) as ward
                    FROM orders o 
                    LEFT JOIN products p ON o.product_id = p.id 
                    LEFT JOIN cities c ON o.city = c.id
                    LEFT JOIN districts d ON o.district = d.id
                    LEFT JOIN wards w ON o.ward = w.id
                    WHERE o.user_id = $userId 
                    ORDER BY o.created_at DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $order_error = 'Không thể hủy đơn hàng này!';
            }
        } else {
            $order_error = 'Đơn hàng không tồn tại!';
        }
    }
}

// Xử lý hủy yêu cầu hủy đơn hàng
if (isset($_POST['cancel_cancel_request'])) {
    $orderId = $_POST['order_id'] ?? 0;
    if ($orderId) {
        // Kiểm tra đơn hàng thuộc về user này
        $stmt = $db->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
        $stmt->execute([$orderId, $userId]);
        $order = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($order) {
            // Chỉ cho phép hủy yêu cầu hủy nếu đơn hàng đã bị hủy
            if ($order['status'] === 'cancelled') {
                $stmt = $db->prepare("UPDATE orders SET status = 'pending' WHERE id = ?");
                $stmt->execute([$orderId]);
                $order_success = 'Đã hủy yêu cầu hủy đơn hàng!';
                // Refresh lại danh sách đơn hàng
                $allOrders = $db->query("
                    SELECT o.*, 
                           COALESCE(p.name, 'Sản phẩm không tồn tại') as product_name, 
                           COALESCE(p.price, o.total) as product_price, 
                           p.img,
                           COALESCE(c.name, o.city) as city,
                           COALESCE(d.name, o.district) as district,
                           COALESCE(w.name, o.ward) as ward
                    FROM orders o 
                    LEFT JOIN products p ON o.product_id = p.id 
                    LEFT JOIN cities c ON o.city = c.id
                    LEFT JOIN districts d ON o.district = d.id
                    LEFT JOIN wards w ON o.ward = w.id
                    WHERE o.user_id = $userId 
                    ORDER BY o.created_at DESC
                ")->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $order_error = 'Không thể thực hiện thao tác này!';
            }
        } else {
            $order_error = 'Đơn hàng không tồn tại!';
        }
    }
}

// Xử lý đổi thông tin cá nhân
if (isset($_POST['update_info'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    if ($username === '' || $email === '') {
        $info_error = 'Vui lòng nhập đầy đủ thông tin!';
    } else {
        // Kiểm tra trùng username/email với user khác
        $stmt = $db->prepare("SELECT id FROM users WHERE (username=? OR email=?) AND id<>?");
        $stmt->execute([$username, $email, $userId]);
        if ($stmt->fetch()) {
            $info_error = 'Tên đăng nhập hoặc email đã tồn tại!';
        } else {
            $stmt = $db->prepare("UPDATE users SET username=?, email=? WHERE id=?");
            $stmt->execute([$username, $email, $userId]);
            $info_success = 'Cập nhật thành công!';
            $_SESSION['username'] = $username;
            // Cập nhật lại biến $user
            $stmt = $db->prepare("SELECT * FROM users WHERE id=?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    }
}
// Xử lý đổi mật khẩu
if (isset($_POST['change_pass'])) {
    $old = $_POST['old_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    if ($old === '' || $new === '' || $confirm === '') {
        $pass_error = 'Vui lòng nhập đầy đủ thông tin!';
    } elseif (!password_verify($old, $user['password'])) {
        $pass_error = 'Mật khẩu cũ không đúng!';
    } elseif ($new !== $confirm) {
        $pass_error = 'Mật khẩu mới và xác nhận không khớp!';
    } elseif (strlen($new) < 6) {
        $pass_error = 'Mật khẩu mới phải từ 6 ký tự!';
    } else {
        $hash = password_hash($new, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password=? WHERE id=?");
        $stmt->execute([$hash, $userId]);
        $pass_success = 'Đổi mật khẩu thành công!';
    }
}

// Hàm hiển thị trạng thái đơn hàng
function getStatusBadge($status) {
    $statusMap = [
        'pending' => ['Chờ xử lý', 'warning'],
        'processing' => ['Đang xử lý', 'info'],
        'shipped' => ['Đang giao', 'primary'],
        'delivered' => ['Hoàn thành', 'success'],
        'cancelled' => ['Đã hủy', 'danger']
    ];
    $status = $status ?? 'pending';
    $info = $statusMap[$status] ?? ['Không xác định', 'secondary'];
    return "<span class='badge bg-{$info[1]}'>{$info[0]}</span>";
}

// Hàm kiểm tra có thể hủy đơn hàng không
function canCancelOrder($status) {
    return in_array($status, ['pending', 'processing']);
}

// Hàm kiểm tra có thể hủy yêu cầu hủy không
function canCancelCancelRequest($status) {
    return $status === 'cancelled';
}

// Thiết lập thông tin trang
$pageTitle = '';
$pageIcon = '';
$pageIconColor = '';
$currentTab = $tab;

// Thiết lập thông báo - không hiển thị thông báo
unset($successMessage);
unset($errorMessage);

switch ($tab) {
    case 'dashboard':
        $pageTitle = 'Dashboard';
        $pageIcon = 'fa fa-tachometer-alt';
        $pageIconColor = '#007bff';
        break;
    case 'orders':
        $pageTitle = 'Đơn hàng của tôi';
        $pageIcon = 'fa fa-shopping-bag';
        $pageIconColor = '#dc3545';
        break;
    case 'favorites':
        $pageTitle = 'Sản phẩm yêu thích';
        $pageIcon = 'fa fa-heart';
        $pageIconColor = '#dc3545';
        break;
    case 'info':
        $pageTitle = 'Thông tin cá nhân';
        $pageIcon = 'fa fa-user-edit';
        $pageIconColor = '#28a745';
        break;
    case 'pass':
        $pageTitle = 'Đổi mật khẩu';
        $pageIcon = 'fa fa-key';
        $pageIconColor = '#ffc107';
        break;
    default:
        $pageTitle = 'Dashboard';
        $pageIcon = 'fa fa-tachometer-alt';
        $pageIconColor = '#007bff';
}

// Tạo nội dung cho từng tab
ob_start();
?>
<?php if ($tab=='dashboard'): ?>
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fa fa-money-bill me-2" style="color: #28a745; font-size: 1.5rem;"></i>
                                <h3 class="mb-0"><?= format_money($totalMoney) ?>đ</h3>
                            </div>
                            <p class="mb-0">Tiền mua hàng</p>
                        </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fa fa-star me-2" style="color: #ffc107; font-size: 1.5rem;"></i>
                                <h3 class="mb-0">0</h3>
                            </div>
                            <p class="mb-0">Điểm của bạn</p>
                        </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fa fa-shopping-bag me-2" style="color: #007bff; font-size: 1.5rem;"></i>
                                <h3 class="mb-0"><?= $totalOrder ?></h3>
                            </div>
                            <p class="mb-0">Đơn hàng 30 ngày gần đây</p>
                        </div>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <i class="fa fa-clock me-2" style="color: #28a745;"></i>
                    <h5 class="mb-0">Đơn hàng gần đây</h5>
            </div>
            <table class="table table-bordered bg-white">
                <thead>
                    <tr>
                        <th>Tên sản phẩm</th>
                        <th>Ngày đặt</th>
                        <th>Giá</th>
                        <th>Trạng thái</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= htmlspecialchars($order['name']) ?></td>
                        <td><?= $order['created_at'] ?></td>
                        <td><?= format_money($order['price']) ?>đ</td>
                            <td><?= getStatusBadge($order['status']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php elseif ($tab=='orders'): ?>

                <?php if (empty($allOrders)): ?>
                    <div class="alert alert-info">Bạn chưa có đơn hàng nào.</div>
                <?php else: ?>
                    <?php foreach ($allOrders as $order): ?>
                    <div class="order-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0">Đơn hàng #<?= $order['id'] ?></h5>
                            <div>
                                <?= getStatusBadge($order['status']) ?>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-8">
                                <div class="order-item">
                                    <div class="d-flex align-items-center">
                                        <?php if ($order['img']): ?>
                                            <img src="<?= htmlspecialchars($order['img']) ?>" 
                                                 alt="<?= htmlspecialchars($order['product_name']) ?>"
                                                 style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px; margin-right: 15px;"
                                                 onerror="this.src='https://via.placeholder.com/80x80/eee/999?text=No+Image'">
                                        <?php else: ?>
                                            <div style="width: 80px; height: 80px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 15px;">
                                                <i class="fa fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                        <div class="order-details flex-grow-1">
                                            <h6 class="mb-1 fw-bold"><?= htmlspecialchars($order['product_name']) ?></h6>
                                            <p class="mb-1 text-muted">
                                                <i class="fa fa-calendar me-1"></i>
                                                Ngày đặt: <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                            </p>
                                            <p class="mb-1 text-muted">
                                                <i class="fa fa-shopping-cart me-1"></i>
                                                Số lượng: <?= $order['quantity'] ?? 1 ?>
                                            </p>
                                            <p class="mb-0 order-price fw-bold text-danger">
                                                <?= format_money($order['total'] ?? $order['product_price']) ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="delivery-info p-3 bg-light rounded">
                                    <h6 class="mb-3">
                                        <i class="fa fa-truck me-2"></i>Thông tin giao hàng
                                    </h6>
                                    <div class="mb-2">
                                        <i class="fa fa-user me-1 text-muted"></i>
                                        <strong><?= htmlspecialchars($order['fullname']) ?></strong>
                                    </div>
                                    <div class="mb-2">
                                        <i class="fa fa-phone me-1 text-muted"></i>
                                        <?= htmlspecialchars($order['phone']) ?>
                                    </div>
                                    <div class="mb-2">
                                        <i class="fa fa-map-marker-alt me-1 text-muted"></i>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($order['address']) ?>
                                            <?php if (!empty($order['ward'])): ?>, <?= htmlspecialchars($order['ward']) ?><?php endif; ?>
                                            <?php if (!empty($order['district'])): ?>, <?= htmlspecialchars($order['district']) ?><?php endif; ?>
                                            <?php if (!empty($order['city'])): ?>, <?= htmlspecialchars($order['city']) ?><?php endif; ?>
                                        </small>
                                    </div>
                                    <?php if ($order['note']): ?>
                                    <div class="mb-2">
                                        <i class="fa fa-sticky-note me-1 text-muted"></i>
                                        <strong>Ghi chú:</strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($order['note']) ?></small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <div class="order-actions">
                            <?php if (canCancelOrder($order['status'])): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" name="cancel_order" class="btn btn-cancel btn-sm">
                                        <i class="fa-solid fa-times"></i> Hủy đơn hàng
                                    </button>
                                </form>
                            <?php elseif (canCancelCancelRequest($order['status'])): ?>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Bạn có chắc muốn hủy yêu cầu hủy đơn hàng này?')">
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" name="cancel_cancel_request" class="btn btn-restore btn-sm">
                                        <i class="fa-solid fa-undo"></i> Hủy yêu cầu hủy
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php elseif ($tab=='info'): ?>
                <div class="form-section">
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Tên đăng nhập</label>
                            <input type="text" name="username" class="form-control" value="<?= htmlspecialchars($user['username']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                        </div>
                        <button type="submit" name="update_info" class="btn btn-primary w-100">Cập nhật</button>
                    </form>
                </div>
            <?php elseif ($tab=='pass'): ?>
                <div class="form-section">
                    <?php if (!empty($pass_error)): ?>
                        <div class="alert alert-danger"><?= $pass_error ?></div>
                    <?php endif; ?>
                    <?php if (!empty($pass_success)): ?>
                        <div class="alert alert-success"><?= $pass_success ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu cũ</label>
                            <input type="password" name="old_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Mật khẩu mới</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Xác nhận mật khẩu mới</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" name="change_pass" class="btn btn-primary w-100">Đổi mật khẩu</button>
                    </form>
                </div>
            <?php elseif ($tab=='favorites'): ?>
                <?php
                require_once __DIR__ . '/../app/models/Favorite.php';
                $favorites = Favorite::getUserFavorites($userId);
                $totalFavorites = Favorite::countUserFavorites($userId);
                ?>
                <div class="d-flex align-items-center mb-4">
                    <span class="badge bg-primary fs-6 me-3"><?= $totalFavorites ?> sản phẩm</span>
                </div>

                <?php if (empty($favorites)): ?>
                    <div class="text-center py-5">
                        <i class="fa fa-heart-o" style="font-size: 4rem; color: #dee2e6; margin-bottom: 20px;"></i>
                        <h4 class="text-muted">Chưa có sản phẩm yêu thích</h4>
                        <p class="text-muted">Bạn chưa thêm sản phẩm nào vào danh sách yêu thích.</p>
                        <a href="index.php" class="btn btn-primary">
                            <i class="fa fa-shopping-bag me-2"></i>Khám phá sản phẩm
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($favorites as $product): ?>
                            <div class="col-md-4 col-lg-3 mb-4">
                                <div class="card h-100 position-relative favorite-item">
                                    <button class="remove-favorite-btn" 
                                            data-product-id="<?= $product['id'] ?>"
                                            title="Xóa khỏi yêu thích">
                                        <i class="fa fa-times"></i>
                                    </button>
                                    
                                    <img src="<?= htmlspecialchars($product['img']) ?>" 
                                         alt="<?= htmlspecialchars($product['name']) ?>" 
                                         class="card-img-top"
                                         style="height: 200px; object-fit: cover;"
                                         onerror="this.src='https://via.placeholder.com/300x200/eee/999?text=No+Image'">
                                    
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title"><?= htmlspecialchars($product['name']) ?></h6>
                                        <p class="card-text text-danger fw-bold fs-5">
                                            <?= number_format($product['price'], 0, ',', '.') ?> ₫
                                        </p>
                                        
                                        <div class="mt-auto">
                                            <a href="product.php?id=<?= $product['id'] ?>" 
                                               class="btn btn-outline-primary btn-sm w-100">
                                                <i class="fa fa-eye me-1"></i>Xem chi tiết
                                            </a>
                                        </div>
                                    </div>
    </div>
</div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
<?php
$content = ob_get_clean();

// JavaScript cho favorites
$scripts = '';
if ($tab == 'favorites' && !empty($favorites)) {
    $scripts = '
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const removeButtons = document.querySelectorAll(".remove-favorite-btn");
        removeButtons.forEach(button => {
            button.addEventListener("click", function() {
                const productId = this.dataset.productId;
                const card = this.closest(".col-md-4");
                
                if (confirm("Bạn có chắc muốn xóa sản phẩm này khỏi danh sách yêu thích?")) {
                    fetch("toggle_favorite.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded",
                        },
                        body: `product_id=${productId}&action=remove`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            card.style.transition = "all 0.3s";
                            card.style.transform = "scale(0.8)";
                            card.style.opacity = "0";
                            
                            setTimeout(() => {
                                card.remove();
                                
                                const badge = document.querySelector(".badge");
                                if (badge) {
                                    const currentCount = parseInt(badge.textContent.split(" ")[0]);
                                    badge.textContent = (currentCount - 1) + " sản phẩm";
                                }
                                
                                const remainingCards = document.querySelectorAll(".favorite-item");
                                if (remainingCards.length === 0) {
                                    location.reload();
                                }
                            }, 300);
                            
                            showAlert("Đã xóa khỏi yêu thích", "success");
                        } else {
                            showAlert(data.message || "Có lỗi xảy ra", "danger");
                        }
                    })
                    .catch(error => {
                        console.error("Error:", error);
                        showAlert("Có lỗi xảy ra", "danger");
                    });
                }
            });
        });
        
        function showAlert(message, type) {
            const alert = document.createElement("div");
            alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alert.style.cssText = "top: 20px; right: 20px; z-index: 9999; min-width: 300px;";
            alert.innerHTML = `
                <i class="fa fa-${type === "success" ? "check-circle" : "exclamation-circle"} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(alert);
            
            setTimeout(() => {
                alert.remove();
            }, 3000);
        }
    });
    </script>';
}

// Include layout
include __DIR__ . '/../app/views/user/layout_user.php';
?> 