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

// Thêm CSS cho dropdown
echo '<style>
    .dropdown-menu {
        z-index: 1050;
    }
    .btn-group .dropdown {
        margin-right: 5px;
    }
    .dropdown-item:hover {
        background-color: #f8f9fa;
    }
    .dropdown-item:active {
        background-color: #e9ecef;
    }
</style>';

// Xử lý thay đổi quyền
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $userId = $_POST['user_id'];
    $newRole = $_POST['new_role'];
    
    // Debug: Kiểm tra session
    error_log("DEBUG: Session user = " . print_r($_SESSION['user'], true));
    error_log("DEBUG: POST user_id = $userId, new_role = $newRole");
    
    // Kiểm tra xem user hiện tại có phải là admin không
    $currentUser = $_SESSION['user'];
    
    // Xử lý trường hợp $_SESSION['user'] có thể là array hoặc integer
    if (is_array($currentUser)) {
        $currentUserId = $currentUser['id'] ?? null;
    } else {
        $currentUserId = $currentUser;
    }
    
    error_log("DEBUG: Current user ID = $currentUserId");
    
    if (!$currentUserId) {
        $errorMessage = 'Không tìm thấy thông tin user!';
    } else {
        // Kiểm tra quyền admin từ database
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$currentUserId]);
        $currentUserRole = $stmt->fetchColumn();
        
        error_log("DEBUG: Current user role = $currentUserRole");
        
        if ($currentUserRole !== 'admin') {
            $errorMessage = 'Chỉ admin mới có quyền thay đổi quyền truy cập!';
        } else {
            // Không cho phép admin tự hạ quyền chính mình
            if ($userId == $currentUserId) {
                $errorMessage = 'Không thể thay đổi quyền của chính mình!';
            } else {
                $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
                if ($stmt->execute([$newRole, $userId])) {
                    $successMessage = 'Đã cập nhật quyền thành công!';
                    error_log("DEBUG: Role updated successfully");
                } else {
                    $errorMessage = 'Có lỗi xảy ra khi cập nhật quyền!';
                    error_log("DEBUG: Error updating role");
                }
            }
        }
    }
}

// Xử lý khóa/mở khóa user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_status'])) {
    $userId = $_POST['user_id'];
    
    // Kiểm tra xem user hiện tại có phải là admin không
    $currentUser = $_SESSION['user'];
    
    // Xử lý trường hợp $_SESSION['user'] có thể là array hoặc integer
    if (is_array($currentUser)) {
        $currentUserId = $currentUser['id'] ?? null;
    } else {
        $currentUserId = $currentUser;
    }
    
    if (!$currentUserId) {
        $errorMessage = 'Không tìm thấy thông tin user!';
    } else {
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$currentUserId]);
        $currentUserRole = $stmt->fetchColumn();
        
        if ($currentUserRole !== 'admin') {
            $errorMessage = 'Chỉ admin mới có quyền khóa/mở khóa tài khoản!';
        } else {
            // Không cho phép admin tự khóa chính mình
            if ($userId == $currentUserId) {
                $errorMessage = 'Không thể khóa tài khoản của chính mình!';
            } else {
                $stmt = $db->prepare("UPDATE users SET active = NOT active WHERE id = ?");
                if ($stmt->execute([$userId])) {
                    $successMessage = 'Đã cập nhật trạng thái thành công!';
                } else {
                    $errorMessage = 'Có lỗi xảy ra khi cập nhật trạng thái!';
                }
            }
        }
    }
}

// Lấy danh sách users
$users = $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa fa-users me-2"></i>Quản lý người dùng</h2>
        <div class="d-flex gap-2">
            <span class="badge bg-primary">Tổng: <?= count($users) ?> users</span>
            <span class="badge bg-success">Admin: <?= count(array_filter($users, fn($u) => $u['role'] === 'admin')) ?></span>
            <span class="badge bg-info">User: <?= count(array_filter($users, fn($u) => $u['role'] === 'user')) ?></span>
        </div>
    </div>

    <?php if (isset($successMessage)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i><?= $successMessage ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i><?= $errorMessage ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php 
    // Kiểm tra quyền của user hiện tại
    $currentUser = $_SESSION['user'];
    
    // Xử lý trường hợp $_SESSION['user'] có thể là array hoặc integer
    if (is_array($currentUser)) {
        $currentUserId = $currentUser['id'] ?? null;
    } else {
        $currentUserId = $currentUser;
    }
    
    if ($currentUserId) {
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->execute([$currentUserId]);
        $currentUserRole = $stmt->fetchColumn();
    } else {
        $currentUserRole = 'user'; // Default fallback
    }
    
    if ($currentUserRole !== 'admin'): 
    ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-triangle me-2"></i>
            <strong>Lưu ý:</strong> Bạn đang đăng nhập với quyền <strong><?= $currentUserRole ?></strong>. 
            Chỉ tài khoản <strong>admin</strong> mới có quyền thay đổi quyền truy cập và khóa/mở khóa tài khoản.
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Thông tin</th>
                            <th>Quyền</th>
                            <th>Trạng thái</th>
                            <th>Ngày tạo</th>
                            <th>Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td>
                                <span class="badge bg-secondary">#<?= $user['id'] ?></span>
                            </td>
                            <td>
                                <div>
                                    <strong><?= htmlspecialchars($user['username']) ?></strong>
                                    <br>
                                    <small class="text-muted"><?= htmlspecialchars($user['email']) ?></small>
                                </div>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'admin'): ?>
                                    <span class="badge bg-danger">
                                        <i class="fa fa-shield me-1"></i>Admin
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-info">
                                        <i class="fa fa-user me-1"></i>User
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($user['active'] ?? 1): ?>
                                    <span class="badge bg-success">
                                        <i class="fa fa-check-circle me-1"></i>Hoạt động
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-warning">
                                        <i class="fa fa-ban me-1"></i>Đã khóa
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= date('d/m/Y H:i', strtotime($user['created_at'])) ?>
                                </small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <?php 
                                    // Sử dụng $currentUserId đã được xử lý ở trên
                                    if ($currentUserRole === 'admin'): 
                                    ?>
                                        <!-- Thay đổi quyền -->
                                        <div class="dropdown">
                                            <button class="btn btn-outline-primary btn-sm dropdown-toggle" 
                                                    type="button" 
                                                    data-bs-toggle="dropdown">
                                                <i class="fa fa-user-shield me-1"></i>Quyền
                                            </button>
                                            <ul class="dropdown-menu">
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <input type="hidden" name="new_role" value="user">
                                                        <button type="submit" name="change_role" class="dropdown-item">
                                                            <i class="fa fa-user me-2"></i>Đặt làm User
                                                        </button>
                                                    </form>
                                                </li>
                                                <li>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                        <input type="hidden" name="new_role" value="admin">
                                                        <button type="submit" name="change_role" class="dropdown-item">
                                                            <i class="fa fa-shield me-2"></i>Đặt làm Admin
                                                        </button>
                                                    </form>
                                                </li>
                                            </ul>
                                        </div>

                                        <!-- Khóa/Mở khóa -->
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                            <button type="submit" name="toggle_status" class="btn btn-outline-warning btn-sm">
                                                <?php if ($user['active'] ?? 1): ?>
                                                    <i class="fa fa-ban me-1"></i>Khóa
                                                <?php else: ?>
                                                    <i class="fa fa-unlock me-1"></i>Mở khóa
                                                <?php endif; ?>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">
                                            <i class="fa fa-lock me-1"></i>Chỉ admin mới có quyền
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Thêm JavaScript cho dropdown
$scripts = '
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Đảm bảo dropdown hoạt động
    var dropdownElementList = [].slice.call(document.querySelectorAll(".dropdown-toggle"));
    var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
        return new bootstrap.Dropdown(dropdownToggleEl);
    });
    
    // Debug: Log để kiểm tra
    console.log("Dropdown elements found:", dropdownElementList.length);
});
</script>';

include __DIR__ . '/../layout.php';
?> 