<?php
session_start();
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/Review.php';
require_once __DIR__ . '/../app/models/Favorite.php';

$productId = $_GET['id'] ?? 0;
if (!$productId) {
    header('Location: index.php');
    exit;
}

$db = Database::getInstance()->getConnection();

// Lấy thông tin sản phẩm
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$productId]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    header('Location: index.php');
    exit;
}

// Lấy đánh giá sản phẩm
$review = new Review();
$reviews = $review->getByProduct($productId, 10);
$reviewStats = $review->getStatsByProduct($productId);

// Xử lý gửi đánh giá
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
    
    $userId = $_SESSION['user'];
    $rating = $_POST['rating'];
    $comment = $_POST['comment'] ?? '';
    
    $result = $review->create($userId, $productId, $rating, $comment);
    
    if ($result['success']) {
        $successMessage = $result['message'];
        // Refresh trang để hiển thị đánh giá mới
        header("Location: product.php?id=$productId&success=1");
        exit;
    } else {
        $errorMessage = $result['message'];
    }
}

// XỬ LÝ GỬI REPLY ADMIN (thêm vào đầu file, sau session_start)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_review_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    require_once __DIR__ . '/../app/models/Review.php';
    $reviewId = (int)$_POST['reply_review_id'];
    $reply = trim($_POST['admin_reply'] ?? '');
    $reviewModel = new Review();
    $reviewModel->updateAdminReply($reviewId, $reply);
    header("Location: product.php?id=$productId");
    exit;
}

// Kiểm tra user đã đánh giá sản phẩm này chưa
$userReview = null;
if (isset($_SESSION['user'])) {
    $userReview = $review->getUserReview($_SESSION['user'], $productId);
}

// Kiểm tra sản phẩm có trong yêu thích không
$isFavorite = false;
if (isset($_SESSION['user'])) {
    $isFavorite = Favorite::isFavorite($_SESSION['user'], $productId);
}

// Xử lý thêm vào giỏ hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user'])) {
        header('Location: login.php');
        exit;
    }
    
    $quantity = $_POST['quantity'] ?? 1;
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    
    // Kiểm tra sản phẩm đã có trong giỏ chưa
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $productId) {
            $item['quantity'] += $quantity;
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $productId,
            'quantity' => $quantity
        ];
    }
    
    header('Location: cart.php');
    exit;
}

function format_money($amount) {
    $amount = preg_replace('/[^0-9]/', '', $amount);
    return number_format((int)$amount, 0, '', ',') . 'đ';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($product['name']) ?> - HANADA.VN</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            max-width: 100%;
            overflow-x: hidden;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding-left: 15px;
            padding-right: 15px;
            box-sizing: border-box;
        }
        .product-detail {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
            width: 100%;
            box-sizing: border-box;
        }
        .product-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
        .rating-stars {
            color: #ffc107;
            font-size: 1.2rem;
        }
        .rating-stats {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .review-item {
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background: white;
        }
        .review-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 10px;
        }
        .review-author {
            font-weight: 500;
            color: #333;
        }
        .review-date {
            color: #666;
            font-size: 0.9rem;
        }
        .quantity-input {
            width: 80px;
        }
        .stock-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        .in-stock {
            background: #d4edda;
            color: #155724;
        }
        .out-of-stock {
            background: #f8d7da;
            color: #721c24;
        }
        .star-rating {
            font-size: 24px;
            color: #ffc107;
            cursor: pointer;
        }
        .star-rating .fa-star {
            transition: color 0.2s;
            color: #ccc;
        }
        .star-rating .fa-star:hover,
        .star-rating .fa-star.active,
        .star-rating .fa-star.text-warning {
            color: #ffc107;
        }
        .review-item .rating-stars .fa-star {
            font-size: 1.1rem;
        }
        .review-item .fw-bold {
            color: #e53935;
        }
        .review-item {
            transition: box-shadow 0.2s;
        }
        .review-item:hover {
            box-shadow: 0 2px 12px rgba(0,0,0,0.07);
            background: #fff;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../app/views/partials/header.php'; ?>

<div class="container mt-4 mb-5">
    <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa fa-check-circle me-2"></i>Đánh giá đã được gửi thành công!
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($errorMessage)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa fa-exclamation-circle me-2"></i><?= $errorMessage ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active"><?= htmlspecialchars($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Product Image -->
        <div class="col-md-6">
            <div class="product-detail">
                <img src="<?= htmlspecialchars($product['img']) ?>" 
                     alt="<?= htmlspecialchars($product['name']) ?>" 
                     class="product-image"
                     onerror="this.src='https://via.placeholder.com/400x400/eee/999?text=No+Image'">
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-md-6">
            <div class="product-detail p-4">
                <h2 class="mb-3"><?= htmlspecialchars($product['name']) ?></h2>
                
                <div class="mb-3">
                    <span class="h3 text-danger fw-bold"><?= format_money($product['price']) ?></span>
                </div>

                <div class="mb-3">
                    <span class="stock-status <?= $product['stock'] > 0 ? 'in-stock' : 'out-of-stock' ?>">
                        <i class="fa <?= $product['stock'] > 0 ? 'fa-check' : 'fa-times' ?> me-1"></i>
                        <?= $product['stock'] > 0 ? 'Còn hàng' : 'Hết hàng' ?>
                    </span>
                </div>

                <?php if ($product['description']): ?>
                    <div class="mb-4">
                        <h5>Mô tả sản phẩm:</h5>
                        <p class="text-muted"><?= nl2br(htmlspecialchars($product['description'])) ?></p>
                    </div>
                <?php endif; ?>

                <!-- Rating Summary -->
                <?php if ($reviewStats['total_reviews'] > 0): ?>
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <div class="rating-stars me-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="fa fa-star <?= $i <= round($reviewStats['average_rating']) ? 'text-warning' : 'text-muted' ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="fw-bold"><?= number_format($reviewStats['average_rating'], 1) ?></span>
                            <span class="text-muted ms-2">(<?= $reviewStats['total_reviews'] ?> đánh giá)</span>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Add to Cart Form -->
                <form method="POST" class="mb-4">
                    <div class="row align-items-end">
                        <div class="col-md-4">
                            <label for="quantity" class="form-label">Số lượng:</label>
                            <input type="number" name="quantity" id="quantity" 
                                   class="form-control quantity-input" 
                                   value="1" min="1" max="<?= $product['stock'] ?>"
                                   <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" name="add_to_cart" 
                                    class="btn btn-primary w-100"
                                    <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
                                <i class="fa fa-shopping-cart me-2"></i>
                                <?= $product['stock'] > 0 ? 'Thêm vào giỏ hàng' : 'Hết hàng' ?>
                        </div>
                        <div class="col-md-4">
                            <?php if (isset($_SESSION['user'])): ?>
                                <button type="button" id="favorite-btn" 
                                        class="btn <?= $isFavorite ? 'btn-danger' : 'btn-outline-danger' ?> w-100"
                                        data-product-id="<?= $productId ?>"
                                        data-is-favorite="<?= $isFavorite ? '1' : '0' ?>">
                                    <i class="fa <?= $isFavorite ? 'fa-heart' : 'fa-heart-o' ?> me-2"></i>
                                    <?= $isFavorite ? 'Đã yêu thích' : 'Yêu thích' ?>
                                </button>
                            <?php else: ?>
                                <a href="login.php" class="btn btn-outline-danger w-100">
                                    <i class="fa fa-heart-o me-2"></i>Yêu thích
                                </a>
                            <?php endif; ?>
                        </div>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Reviews Section -->
    <div class="row mt-4">
        <div class="col-12">
            <h3 class="mb-4">
                <i class="fa fa-star me-2 text-warning"></i>
                Đánh giá sản phẩm (<?= $reviewStats['total_reviews'] ?>)
            </h3>
            
            <style>
            .reviews-container {
                max-height: 300px;
                overflow-y: auto;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 15px;
                background: #fafafa;
                max-width: 1000px;
                margin: 0 auto;
            }
            .review-item {
                border-bottom: 1px solid #eee;
                padding: 12px 0;
                margin-bottom: 12px;
            }
            .review-item:last-child {
                border-bottom: none;
                margin-bottom: 0;
            }
            .rating-stats {
                margin-bottom: 20px !important;
                max-width: 1000px;
                margin-left: auto;
                margin-right: auto;
            }
            .rating-stats .row {
                margin-bottom: 15px;
            }
            .star-rating {
                font-size: 18px;
            }
            .admin-reply-form {
                margin-top: 10px;
                padding: 10px;
                background: #f8f9fa;
                border-radius: 5px;
            }
            .admin-reply-form textarea {
                margin-bottom: 8px;
                font-size: 12px;
            }
            .admin-reply-form button {
                padding: 4px 12px;
                font-size: 11px;
            }
            .reviews-section {
                margin-top: 30px !important;
            }
            .progress {
                height: 8px !important;
            }
            .h2.text-warning {
                font-size: 1.8rem;
                margin-bottom: 0.5rem;
            }
            .rating-stars {
                font-size: 16px;
            }
            .card {
                max-width: 1000px;
                margin: 0 auto;
            }
            </style>

            <!-- Thống kê sao và danh sách đánh giá -->
            <?php if ($reviewStats['total_reviews'] > 0): ?>
                <div class="rating-stats mb-4">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="h2 text-warning mb-1"><?= number_format($reviewStats['average_rating'], 1) ?></div>
                                <div class="rating-stars mb-2">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="fa fa-star <?= $i <= round($reviewStats['average_rating']) ? 'text-warning' : 'text-muted' ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <div class="text-muted"><?= $reviewStats['total_reviews'] ?> đánh giá</div>
                            </div>
                        </div>
                        <div class="col-md-9">
                            <div class="row align-items-center">
                                <?php
                                $starKeys = [
                                    5 => 'five',
                                    4 => 'four',
                                    3 => 'three',
                                    2 => 'two',
                                    1 => 'one'
                                ];
                                for ($i = 5; $i >= 1; $i--):
                                    $key = $starKeys[$i] . '_star';
                                    $percentKey = $starKeys[$i] . '_star_percent';
                                ?>
                                    <div class="col-12 mb-2">
                                        <div class="d-flex align-items-center">
                                            <span class="me-2" style="min-width: 40px;"><?= $i ?> sao</span>
                                            <div class="progress flex-grow-1 me-2" style="height: 8px; max-width: 200px;">
                                                <div class="progress-bar bg-warning" style="width: <?= isset($reviewStats[$percentKey]) ? $reviewStats[$percentKey] : 0 ?>%"></div>
                                            </div>
                                            <span class="text-muted" style="min-width: 20px;"><?= isset($reviewStats[$key]) ? $reviewStats[$key] : 0 ?></span>
                                        </div>
                                    </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Danh sách đánh giá -->
            <div class="reviews-container mb-4">
                <?php if (!empty($reviews)): ?>
                    <?php foreach ($reviews as $review): ?>
                        <div class="review-item d-flex align-items-start">
                            <div class="me-3">
                                <img src="https://ui-avatars.com/api/?name=<?=urlencode($review['username'])?>&background=eee&color=555&size=48" class="rounded-circle" width="48" height="48" alt="avatar">
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center mb-1">
                                    <span class="fw-bold me-2"><?= htmlspecialchars($review['username']) ?></span>
                                    <div class="rating-stars me-2">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fa fa-star <?= $i <= $review['rating'] ? 'text-warning' : 'text-muted' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-muted small ms-2"><i class="fa fa-clock me-1"></i><?= date('d/m/Y', strtotime($review['created_at'])) ?></span>
                                </div>
                                <?php if ($review['comment']): ?>
                                    <div class="mb-0 text-secondary" style="white-space:pre-line;"> <?= nl2br(htmlspecialchars($review['comment'])) ?> </div>
                                <?php endif; ?>
                                <?php if (!empty($review['admin_reply'])): ?>
                                    <div class="mt-2 p-2 rounded" style="background:#e3f2fd; color:#1976d2;">
                                        <i class="fa fa-reply me-1"></i>
                                        <b>Phản hồi từ Admin:</b><br>
                                        <?= nl2br(htmlspecialchars($review['admin_reply'])) ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <form method="POST" class="admin-reply-form">
                                        <input type="hidden" name="reply_review_id" value="<?= $review['id'] ?>">
                                        <textarea name="admin_reply" rows="2" class="form-control" placeholder="Nhập phản hồi..."><?= htmlspecialchars($review['admin_reply'] ?? '') ?></textarea>
                                        <button type="submit" class="btn btn-primary btn-sm">Gửi phản hồi</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">Chưa có đánh giá nào cho sản phẩm này.</div>
                <?php endif; ?>
            </div>

            <!-- Form đánh giá cho user đã đăng nhập -->
            <?php if (isset($_SESSION['user']) && !$userReview): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fa fa-edit me-2"></i>Viết đánh giá của bạn</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Đánh giá:</label>
                                <div class="star-rating" id="starRating">
                                    <i class="fa fa-star" data-rating="1"></i>
                                    <i class="fa fa-star" data-rating="2"></i>
                                    <i class="fa fa-star" data-rating="3"></i>
                                    <i class="fa fa-star" data-rating="4"></i>
                                    <i class="fa fa-star" data-rating="5"></i>
                                </div>
                                <small class="text-muted">Nhấp vào ngôi sao để chọn đánh giá</small>
                                <input type="hidden" name="rating" id="ratingInput" required>
                            </div>
                            <div class="mb-3">
                                <label for="comment" class="form-label">Nhận xét (tùy chọn):</label>
                                <textarea class="form-control" id="comment" name="comment" rows="4" 
                                          placeholder="Chia sẻ trải nghiệm của bạn về sản phẩm này..."></textarea>
                            </div>
                            <button type="submit" name="submit_review" class="btn btn-primary" id="submitReviewBtn" disabled>
                                <i class="fa fa-paper-plane me-2"></i>Gửi đánh giá
                            </button>
                        </form>
                    </div>
                </div>
            <?php elseif (!isset($_SESSION['user'])): ?>
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-triangle me-2"></i>
                    <a href="login.php" class="alert-link">Đăng nhập</a> để viết đánh giá cho sản phẩm này.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Footer -->
<?php include __DIR__ . '/../app/views/partials/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Star rating functionality
// Sửa lại logic để cho phép click lại vào 1 sao sẽ reset về 0 sao

document.addEventListener('DOMContentLoaded', function() {
    let selectedRating = 0;
    const stars = document.querySelectorAll('#starRating .fa-star');
    const ratingInput = document.getElementById('ratingInput');
    const submitBtn = document.getElementById('submitReviewBtn');

    function highlightStars(rating) {
        stars.forEach((star, idx) => {
            star.classList.toggle('text-warning', idx < rating);
            star.classList.toggle('text-muted', idx >= rating);
        });
    }

    if (stars.length > 0) {
        stars.forEach((star, idx) => {
            star.addEventListener('click', function() {
                // Nếu đã chọn 1 sao và click lại vào 1 sao thì reset về 0
                if (selectedRating === 1 && idx === 0) {
                    selectedRating = 0;
                    ratingInput.value = '';
                } else {
                    selectedRating = idx + 1;
                    ratingInput.value = selectedRating;
                }
                highlightStars(selectedRating);
                if (submitBtn) submitBtn.disabled = selectedRating === 0;
            });

            star.addEventListener('mouseenter', function() {
                highlightStars(idx + 1);
            });

            star.addEventListener('mouseleave', function() {
                highlightStars(selectedRating);
            });
        });
    }

    // Favorite functionality
    const favoriteBtn = document.getElementById('favorite-btn');
    if (favoriteBtn) {
        favoriteBtn.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const isFavorite = this.dataset.isFavorite === '1';
            const action = isFavorite ? 'remove' : 'add';

            fetch('toggle_favorite.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `product_id=${productId}&action=${action}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Toggle button state
                    this.dataset.isFavorite = isFavorite ? '0' : '1';
                    this.classList.toggle('btn-danger', !isFavorite);
                    this.classList.toggle('btn-outline-danger', isFavorite);
                    
                    const icon = this.querySelector('i');
                    icon.classList.toggle('fa-heart', !isFavorite);
                    icon.classList.toggle('fa-heart-o', isFavorite);
                    
                    this.innerHTML = isFavorite ? 
                        '<i class="fa fa-heart-o me-2"></i>Yêu thích' : 
                        '<i class="fa fa-heart me-2"></i>Đã yêu thích';
                    
                    // Show success message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
                    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                    alert.innerHTML = `
                        <i class="fa fa-check-circle me-2"></i>${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(alert);
                    
                    // Auto remove after 3 seconds
                    setTimeout(() => {
                        alert.remove();
                    }, 3000);
                } else {
                    // Show error message
                    const alert = document.createElement('div');
                    alert.className = 'alert alert-danger alert-dismissible fade show position-fixed';
                    alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                    alert.innerHTML = `
                        <i class="fa fa-exclamation-circle me-2"></i>${data.message}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    `;
                    document.body.appendChild(alert);
                    
                    // Auto remove after 3 seconds
                    setTimeout(() => {
                        alert.remove();
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show position-fixed';
                alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
                alert.innerHTML = `
                    <i class="fa fa-exclamation-circle me-2"></i>Có lỗi xảy ra
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                `;
                document.body.appendChild(alert);
                
                setTimeout(() => {
                    alert.remove();
                }, 3000);
            });
        });
    }
});
</script>
</body>
</html> 