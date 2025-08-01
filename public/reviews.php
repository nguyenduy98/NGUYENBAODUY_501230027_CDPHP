<?php
session_start();
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/Review.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user']) || $_SESSION['role'] !== 'user') {
    header('Location: login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$reviewModel = new Review();

// Lấy user_id từ session
$userId = $_SESSION['user'] ?? null;
if (!$userId) {
    header('Location: login.php');
    exit;
}

// Lấy đánh giá đã viết của user
$stmt = $db->prepare("
    SELECT r.*, p.name as product_name, p.img as product_img 
    FROM reviews r 
    JOIN products p ON r.product_id = p.id 
    WHERE r.user_id = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$userId]);
$userReviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Thiết lập thông tin trang
$pageTitle = 'Đánh giá sản phẩm';
$pageIcon = 'fa fa-star';
$pageIconColor = '#ffc107';
$currentTab = 'reviews';

// Tạo nội dung
ob_start();
?>
            <!-- Đánh giá đã viết -->
            <?php if (!empty($userReviews)): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa fa-list me-2"></i>Đánh giá đã viết (<?= count($userReviews) ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($userReviews as $review): ?>
                            <div class="user-review-item">
                                <div class="d-flex align-items-start">
                                    <?php if ($review['product_img']): ?>
                                        <img src="<?= htmlspecialchars($review['product_img']) ?>" 
                                             alt="<?= htmlspecialchars($review['product_name']) ?>"
                                             class="product-image me-3"
                                             onerror="this.src='https://via.placeholder.com/60x60/eee/999?text=No+Image'">
                                    <?php else: ?>
                                        <div class="product-image me-3 bg-light d-flex align-items-center justify-content-center">
                                            <i class="fa fa-image text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1"><?= htmlspecialchars($review['product_name']) ?></h6>
                                        <div class="mb-2">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="fa fa-star <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                            <?php endfor; ?>
                                            <span class="ms-2 text-muted"><?= $review['rating'] ?>/5</span>
                                        </div>
                                        <?php if ($review['comment']): ?>
                                            <p class="mb-1"><?= htmlspecialchars($review['comment']) ?></p>
                                        <?php endif; ?>
                                        <small class="text-muted">
                                            <i class="fa fa-calendar me-1"></i>
                                            <?= date('d/m/Y H:i', strtotime($review['created_at'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fa fa-star text-muted" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <h5 class="text-muted">Bạn chưa có đánh giá nào</h5>
                        <p class="text-muted">Hãy mua sản phẩm và đánh giá để chia sẻ trải nghiệm của bạn!</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Không cần JavaScript nữa vì đã xóa modal
$scripts = '';

// Include layout
include __DIR__ . '/../app/views/user/layout_user.php';
?> 