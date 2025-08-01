<?php
session_start();
require_once __DIR__ . '/../app/models/Product.php';
require_once __DIR__ . '/../app/models/Database.php';

function format_money($amount) {
    $amount = preg_replace('/[^0-9]/', '', $amount);
    return number_format((int)$amount, 0, '', ',') . 'đ';
}

$db = Database::getInstance()->getConnection();

// Lấy category từ URL
$category = $_GET['category'] ?? 'daychuyen';

// Map category names
$categoryNames = [
    'ring' => 'CHROME HEARTS RINGS',
    'bongtai' => 'CHROME HEARTS EARRINGS', 
    'daychuyen' => 'CHROME HEARTS PENDANTS',
    'vongtay' => 'CHROME HEARTS BRACELETS'
];

// Map category names for breadcrumb
$categoryBreadcrumbNames = [
    'ring' => 'NHẪN',
    'bongtai' => 'KHUYÊN TAI', 
    'daychuyen' => 'DÂY CHUYỀN',
    'vongtay' => 'VÒNG TAY'
];

$categoryDescriptions = [
    'ring' => 'Bộ sưu tập nhẫn độc đáo với thiết kế gothic đặc trưng',
    'bongtai' => 'Bộ sưu tập khuyên tai tinh tế và sang trọng',
    'daychuyen' => 'Bộ sưu tập dây chuyền độc đáo với thiết kế gothic đặc trưng',
    'vongtay' => 'Bộ sưu tập vòng tay với chất liệu cao cấp'
];

// Lấy tất cả sản phẩm theo category
$products = Product::getByType($category, 100);

// Xử lý filter và sort
$sort = $_GET['sort'] ?? 'newest';
$filter = $_GET['filter'] ?? 'all';

// Sắp xếp sản phẩm
if ($sort === 'price_low') {
    usort($products, function($a, $b) {
        return (int)$a['price'] - (int)$b['price'];
    });
} elseif ($sort === 'price_high') {
    usort($products, function($a, $b) {
        return (int)$b['price'] - (int)$a['price'];
    });
} elseif ($sort === 'name') {
    usort($products, function($a, $b) {
        return strcmp($a['name'], $b['name']);
    });
}

// Filter theo giá
if ($filter === 'under_5m') {
    $products = array_filter($products, function($product) {
        return (int)$product['price'] < 5000000;
    });
} elseif ($filter === '5m_to_10m') {
    $products = array_filter($products, function($product) {
        $price = (int)$product['price'];
        return $price >= 5000000 && $price < 10000000;
    });
} elseif ($filter === 'over_10m') {
    $products = array_filter($products, function($product) {
        return (int)$product['price'] >= 10000000;
    });
}

$products = array_values($products); // Reset array keys

// Lấy tên category hiện tại
$currentCategoryName = $categoryNames[$category] ?? 'CHROME HEARTS COLLECTION';
$currentCategoryBreadcrumbName = $categoryBreadcrumbNames[$category] ?? 'COLLECTION';
$currentCategoryDescription = $categoryDescriptions[$category] ?? 'Bộ sưu tập độc đáo với thiết kế gothic đặc trưng';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title><?= $currentCategoryName ?> Collection</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .collection-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #333 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        .collection-title {
            font-size: 3rem;
            font-weight: bold;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }
        .collection-subtitle {
            font-size: 1.2rem;
            opacity: 0.8;
            margin-bottom: 30px;
        }
        .filter-section {
            background: white;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
        }
        .filter-controls {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .filter-group {
            display: flex;
            gap: 15px;
            align-items: center;
        }
        .filter-btn {
            padding: 8px 16px;
            border: 1px solid #ddd;
            background: white;
            border-radius: 20px;
            text-decoration: none;
            color: #333;
            font-size: 14px;
            transition: all 0.3s;
        }
        .filter-btn:hover, .filter-btn.active {
            background: #333;
            color: white;
            border-color: #333;
        }
        .sort-select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            background: white;
        }
        .products-grid {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        .product-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.15);
        }
        .product-img {
            position: relative;
            width: 100%;
            padding-top: 100%;
            overflow: hidden;
        }
        .product-img img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }
        .product-card:hover .product-img img {
            transform: scale(1.05);
        }
        .product-info {
            padding: 20px;
        }
        .product-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 8px;
            color: #333;
            min-height: 48px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #e53935;
            margin-bottom: 12px;
        }
        .product-stock {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        .add-to-cart-btn {
            width: 100%;
            padding: 10px;
            background: #333;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            transition: background 0.3s;
        }
        .add-to-cart-btn:hover {
            background: #555;
        }
        .add-to-cart-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .no-products {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        .breadcrumb-custom {
            background: white;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .breadcrumb-custom a {
            color: #1976d2;
            text-decoration: none;
        }
        .breadcrumb-custom a:hover {
            text-decoration: underline;
        }
        @media (max-width: 768px) {
            .collection-title {
                font-size: 2rem;
            }
            .filter-controls {
                flex-direction: column;
                gap: 15px;
            }
            .filter-group {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../app/views/partials/header.php'; ?>

<!-- Breadcrumb -->
<div class="breadcrumb-custom">
    <div class="container">
        <a href="index.php">Trang chủ</a> &gt; 
        <span><?= $currentCategoryBreadcrumbName ?></span>
    </div>
</div>

<!-- Collection Header -->
<div class="collection-header">
    <div class="container">
        <h1 class="collection-title"><?= $currentCategoryName ?></h1>
        <p class="collection-subtitle"><?= $currentCategoryDescription ?></p>
        <p class="mb-0"><?= count($products) ?> sản phẩm</p>
    </div>
</div>

<!-- Filter Section -->
<div class="filter-section">
    <div class="filter-controls">
        <div class="filter-group">
            <span style="font-weight: bold; margin-right: 10px;">Lọc theo giá:</span>
            <a href="?category=<?= $category ?>&filter=all" class="filter-btn <?= $filter === 'all' ? 'active' : '' ?>">Tất cả</a>
            <a href="?category=<?= $category ?>&filter=under_5m" class="filter-btn <?= $filter === 'under_5m' ? 'active' : '' ?>">Dưới 5M</a>
            <a href="?category=<?= $category ?>&filter=5m_to_10m" class="filter-btn <?= $filter === '5m_to_10m' ? 'active' : '' ?>">5M - 10M</a>
            <a href="?category=<?= $category ?>&filter=over_10m" class="filter-btn <?= $filter === 'over_10m' ? 'active' : '' ?>">Trên 10M</a>
        </div>
        <div class="filter-group">
            <span style="font-weight: bold; margin-right: 10px;">Sắp xếp:</span>
            <select class="sort-select" onchange="window.location.href='?category=<?= $category ?>&sort=' + this.value + '&filter=<?= $filter ?>'">
                <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                <option value="price_low" <?= $sort === 'price_low' ? 'selected' : '' ?>>Giá tăng dần</option>
                <option value="price_high" <?= $sort === 'price_high' ? 'selected' : '' ?>>Giá giảm dần</option>
                <option value="name" <?= $sort === 'name' ? 'selected' : '' ?>>Tên A-Z</option>
            </select>
        </div>
    </div>
</div>

<!-- Products Grid -->
<div class="products-grid">
    <?php if (empty($products)): ?>
        <div class="no-products">
            <i class="fas fa-search" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
            <h3>Không tìm thấy sản phẩm</h3>
            <p>Vui lòng thử lại với bộ lọc khác</p>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($products as $product): ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="product-card">
                        <a href="product.php?id=<?= $product['id'] ?>" style="text-decoration: none; color: inherit;">
                            <div class="product-img">
                                <img src="<?= htmlspecialchars($product['img']) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>"
                                     onerror="this.src='https://via.placeholder.com/300x300/eee/999?text=No+Image'">
                            </div>
                        </a>
                        <div class="product-info">
                            <a href="product.php?id=<?= $product['id'] ?>" style="text-decoration: none; color: inherit;">
                                <h5 class="product-name"><?= htmlspecialchars($product['name']) ?></h5>
                            </a>
                            <div class="product-price"><?= format_money($product['price']) ?></div>
                            <div class="product-stock">
                                <?php if (($product['stock'] ?? 0) > 0): ?>
                                    <span style="color: #4caf50;">✓ Còn hàng</span>
                                <?php else: ?>
                                    <span style="color: #f44336;">✗ Hết hàng</span>
                                <?php endif; ?>
                            </div>
                            <form method="POST" action="product.php?id=<?= $product['id'] ?>">
                                <input type="hidden" name="quantity" value="1">
                                <button type="submit" name="add_to_cart" class="add-to-cart-btn" 
                                        <?= ($product['stock'] ?? 0) <= 0 ? 'disabled' : '' ?>>
                                    <?= ($product['stock'] ?? 0) > 0 ? 'Thêm vào giỏ' : 'Hết hàng' ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 