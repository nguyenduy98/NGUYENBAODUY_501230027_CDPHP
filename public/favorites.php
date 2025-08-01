<?php
session_start();
require_once __DIR__ . '/../app/models/Favorite.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user'];
$favorites = Favorite::getUserFavorites($userId);
$totalFavorites = Favorite::countUserFavorites($userId);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm yêu thích - Bạc Hiếu Minh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        .favorite-item {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .favorite-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .product-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }
        .remove-favorite-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.9);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #dc3545;
            transition: all 0.2s;
        }
        .remove-favorite-btn:hover {
            background: #dc3545;
            color: white;
        }
        .empty-favorites {
            text-align: center;
            padding: 60px 20px;
        }
        .empty-favorites i {
            font-size: 4rem;
            color: #dee2e6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../app/views/partials/header.php'; ?>

<div class="container mt-4 mb-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
            <li class="breadcrumb-item active">Sản phẩm yêu thích</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="fa fa-heart text-danger me-2"></i>Sản phẩm yêu thích</h2>
        <span class="badge bg-primary fs-6"><?= $totalFavorites ?> sản phẩm</span>
    </div>

    <?php if (empty($favorites)): ?>
        <div class="empty-favorites">
            <i class="fa fa-heart-o"></i>
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
                    <div class="card favorite-item h-100 position-relative">
                        <button class="remove-favorite-btn" 
                                data-product-id="<?= $product['id'] ?>"
                                title="Xóa khỏi yêu thích">
                            <i class="fa fa-times"></i>
                        </button>
                        
                        <img src="<?= htmlspecialchars($product['img']) ?>" 
                             alt="<?= htmlspecialchars($product['name']) ?>" 
                             class="product-image"
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
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Xử lý xóa khỏi yêu thích
    const removeButtons = document.querySelectorAll('.remove-favorite-btn');
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const card = this.closest('.col-md-4');
            
            if (confirm('Bạn có chắc muốn xóa sản phẩm này khỏi danh sách yêu thích?')) {
                fetch('toggle_favorite.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `product_id=${productId}&action=remove`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Xóa card khỏi giao diện
                        card.style.transition = 'all 0.3s';
                        card.style.transform = 'scale(0.8)';
                        card.style.opacity = '0';
                        
                        setTimeout(() => {
                            card.remove();
                            
                            // Cập nhật số lượng
                            const badge = document.querySelector('.badge');
                            const currentCount = parseInt(badge.textContent.split(' ')[0]);
                            badge.textContent = (currentCount - 1) + ' sản phẩm';
                            
                            // Kiểm tra nếu không còn sản phẩm nào
                            const remainingCards = document.querySelectorAll('.favorite-item');
                            if (remainingCards.length === 0) {
                                location.reload(); // Reload để hiển thị trang trống
                            }
                        }, 300);
                        
                        // Hiển thị thông báo thành công
                        showAlert('Đã xóa khỏi yêu thích', 'success');
                    } else {
                        showAlert(data.message || 'Có lỗi xảy ra', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Có lỗi xảy ra', 'danger');
                });
            }
        });
    });
    
    function showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
        alert.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alert.innerHTML = `
            <i class="fa fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alert);
        
        setTimeout(() => {
            alert.remove();
        }, 3000);
    }
});
</script>
</body>
</html> 