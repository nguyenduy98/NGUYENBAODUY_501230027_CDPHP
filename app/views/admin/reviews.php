<?php
require_once __DIR__ . '/../../../app/models/Database.php';
require_once __DIR__ . '/../../../app/models/Review.php';

$db = Database::getInstance()->getConnection();
$reviewModel = new Review();

// Xử lý gửi reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_review_id'])) {
    $reviewId = (int)$_POST['reply_review_id'];
    $reply = trim($_POST['admin_reply'] ?? '');
    $reviewModel->updateAdminReply($reviewId, $reply);
}

// Xử lý xóa đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_review_id'])) {
    $reviewId = (int)$_POST['delete_review_id'];
    if ($reviewModel->deleteReview($reviewId)) {
        $successMessage = 'Đã xóa đánh giá thành công!';
    } else {
        $errorMessage = 'Có lỗi xảy ra khi xóa đánh giá!';
    }
}

// Lấy danh sách đánh giá
$reviews = $db->query("SELECT r.id, r.user_id, r.product_id, r.rating, r.comment, r.admin_reply, r.created_at, u.username, u.email, p.name as product_name, p.img as product_img FROM reviews r JOIN users u ON r.user_id = u.id JOIN products p ON r.product_id = p.id ORDER BY r.created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="d-flex align-items-center mb-4">
    <i class="fa fa-star me-2 text-warning" style="font-size: 2rem;"></i>
    <h2 class="mb-0">Quản lý đánh giá sản phẩm</h2>
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

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Sản phẩm</th>
                        <th>User</th>
                        <th>Rating</th>
                        <th>Bình luận</th>
                        <th>Phản hồi admin</th>
                        <th>Ngày</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($reviews as $review): ?>
                    <tr>
                        <td><?= $review['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= htmlspecialchars($review['product_img']) ?>" alt="sp" width="40" height="40" class="product-img" style="object-fit:cover;">
                                <span><?= htmlspecialchars($review['product_name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="https://ui-avatars.com/api/?name=<?=urlencode($review['username'])?>&background=eee&color=555&size=32" class="rounded-circle" width="32" height="32" alt="avatar">
                                <span class="fw-bold text-primary"><?= htmlspecialchars($review['username']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span style="font-size: 1.3rem;">
                                <?php for ($i=1; $i<=5; $i++): ?>
                                    <i class="fa fa-star<?= $i <= $review['rating'] ? ' text-warning' : ' text-muted' ?>"></i>
                                <?php endfor; ?>
                            </span>
                            <span class="ms-1 fw-bold"><?= $review['rating'] ?>/5</span>
                        </td>
                        <td style="max-width:220px;white-space:pre-line;">
                            <?= nl2br(htmlspecialchars($review['comment'])) ?>
                        </td>
                        <td style="min-width:200px;">
                            <?php if (isset($review['admin_reply']) && $review['admin_reply']): ?>
                                <div class="alert alert-info p-2 mb-2"> <?= nl2br(htmlspecialchars($review['admin_reply'])) ?> </div>
                            <?php endif; ?>
                            <form method="POST" class="d-flex flex-column gap-2">
                                <input type="hidden" name="reply_review_id" value="<?= $review['id'] ?>">
                                <textarea name="admin_reply" rows="2" class="form-control" placeholder="Nhập phản hồi..."><?= htmlspecialchars($review['admin_reply'] ?? '') ?></textarea>
                                <button type="submit" class="btn btn-sm btn-primary align-self-end">Gửi phản hồi</button>
                            </form>
                        </td>
                        <td><span class="text-muted small"><i class="fa fa-clock me-1"></i><?= date('d/m/Y', strtotime($review['created_at'])) ?></span></td>
                        <td>
                            <form method="POST" style="display: inline;" onsubmit="return confirmDelete(<?= $review['id'] ?>)">
                                <input type="hidden" name="delete_review_id" value="<?= $review['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger" title="Xóa đánh giá">
                                    <i class="fa fa-trash me-1"></i>Xóa
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();

// Thêm JavaScript cho thông báo
$scripts = '
<style>
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

<script>
// Tự động ẩn thông báo sau 3 giây
setTimeout(function() {
    var alerts = document.querySelectorAll(".alert");
    alerts.forEach(function(alert) {
        var bsAlert = new bootstrap.Alert(alert);
        bsAlert.close();
    });
}, 3000);

// Xác nhận xóa đánh giá
function confirmDelete(reviewId) {
    return confirm("Bạn có chắc chắn muốn xóa đánh giá này? Hành động này không thể hoàn tác.");
}
</script>';

include __DIR__ . '/layout.php'; 