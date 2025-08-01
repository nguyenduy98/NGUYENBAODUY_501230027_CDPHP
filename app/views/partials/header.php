<?php
// Tính số lượng item trong giỏ hàng
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}
$userInfo = null;
$userRole = null;
if (isset($_SESSION['user'])) {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT username, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user']]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $userRole = $userInfo['role'] ?? ($_SESSION['role'] ?? 'user');
} else if (isset($_SESSION['role'])) {
    $userRole = $_SESSION['role'];
}

// Xác định trang hiện tại để highlight navigation
$currentPage = $_SERVER['REQUEST_URI'];
$isHomePage = strpos($currentPage, 'index.php') !== false || $currentPage === '/test/public/' || $currentPage === '/test/public';
$isCollectionPage = strpos($currentPage, 'collection.php') !== false;
$currentCategory = $_GET['category'] ?? '';
?>
<header class="main-header shadow-sm" style="background:#fff; border-bottom:1px solid #ddd;">
    <div class="header-top d-flex align-items-center justify-content-between flex-wrap" style="padding:0 32px; min-height:64px;">
        <div class="d-flex align-items-center gap-3" style="font-size:1.1rem; color:#222;">
            <i class="fa fa-phone"></i> <span>028 7309 6968</span>
            <span style="margin:0 8px;">|</span>
            <i class="fa fa-map-marker-alt"></i> <span>Cửa hàng</span>
        </div>
        <div class="text-center flex-grow-1" style="font-family: 'Montserrat', Arial, sans-serif;">
            <img src="/test/public/uploads/logo.png" alt="Logo" style="height:54px;">
        </div>
        <div class="d-flex align-items-center gap-3" style="min-width:360px; justify-content:flex-end;">
            <div class="search-container" style="position: relative; max-width:580px;">
                <input type="text" id="searchInput" class="form-control border-1 shadow-none" placeholder="Tìm kiếm sản phẩm..." style="height:32px; font-size:1rem;">
                <div id="searchDropdown" class="search-dropdown" style="display: none; position: absolute; top: 100%; left: 0; right: 0; background: white; border: 1px solid #ddd; border-top: none; border-radius: 0 0 4px 4px; max-height: 300px; overflow-y: auto; z-index: 1000; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                </div>
            </div>
            <a href="cart.php" class="position-relative" style="color:#222; text-decoration:none; font-size:1.6rem; transition: all 0.3s ease;">
                <i class="fa fa-shopping-cart"></i>
                <?php if ($cartCount > 0): ?>
                <span style="position:absolute; top:-8px; right:-10px; background:#dc3545; color:#fff; border-radius:50%; font-size:0.8rem; min-width:18px; height:18px; display:inline-flex; align-items:center; justify-content:center; font-weight:bold; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
                    <?= $cartCount ?>
                </span>
                <?php endif; ?>
            </a>
            <div class="d-flex align-items-center gap-2 ms-3">
            <?php if ($userInfo): ?>
                    <?php $userLink = ($userRole === 'admin') ? 'admin_dashboard.php' : 'user_dashboard.php'; ?>
                    <a href="<?= $userLink ?>" class="fw-bold" style="color:#222; text-decoration:none;"><i class="fa fa-user me-1"></i> <?= htmlspecialchars($userInfo['username']) ?></a>
                    <?php if ($userRole === 'user'): ?>
                        <a href="reviews.php" class="fw-bold ms-2" style="color:#222; text-decoration:none;"><i class="fa fa-star me-1"></i> Đánh giá</a>
                    <?php endif; ?>
                    <a href="logout.php" class="text-danger fw-bold ms-2" style="text-decoration:none;"><i class="fa fa-sign-out-alt me-1"></i> Đăng xuất</a>
            <?php else: ?>
                    <a href="register.php" class="fw-bold me-2" style="color:#222; text-decoration:none;"><i class="fa fa-user-plus me-1"></i> Đăng ký</a>
                    <a href="login.php" class="fw-bold" style="color:#222; text-decoration:none;"><i class="fa fa-sign-in-alt me-1"></i> Đăng nhập</a>
            <?php endif; ?>
            </div>
        </div>
    </div>
    <nav class="header-menu" style="border-top:1px solid #eee; background:#fff;">
        <ul class="nav justify-content-center py-2" style="gap:24px;">
            <li class="nav-item">
                <a class="nav-link text-dark fw-bold <?= $isHomePage ? 'active' : '' ?>" 
                   href="/test/public/index.php" style="transition: color 0.3s ease;">TRANG CHỦ</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark fw-bold <?= ($isCollectionPage && $currentCategory === 'ring') ? 'active' : '' ?>" 
                   href="/test/public/collection.php?category=ring" style="transition: color 0.3s ease;">NHẪN</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark fw-bold <?= ($isCollectionPage && $currentCategory === 'daychuyen') ? 'active' : '' ?>" 
                   href="/test/public/collection.php?category=daychuyen" style="transition: color 0.3s ease;">DÂY CHUYỀN</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark fw-bold <?= ($isCollectionPage && $currentCategory === 'vongtay') ? 'active' : '' ?>" 
                   href="/test/public/collection.php?category=vongtay" style="transition: color 0.3s ease;">VÒNG TAY</a>
            </li>
            <li class="nav-item">
                <a class="nav-link text-dark fw-bold <?= ($isCollectionPage && $currentCategory === 'bongtai') ? 'active' : '' ?>" 
                   href="/test/public/collection.php?category=bongtai" style="transition: color 0.3s ease;">KHUYÊN TAI</a>
            </li>

        </ul>
        <style>
            .header-menu .nav-link:hover {
                color: #222 !important;
                transform: translateY(-1px);
            }
            .header-menu .nav-link.active {
                color: #222 !important;
                border-bottom: 2px solid #222;
            }
            
            /* Search dropdown styles */
            .search-item {
                padding: 10px 15px;
                border-bottom: 1px solid #eee;
                cursor: pointer;
                transition: background-color 0.2s;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .search-item:hover {
                background-color: #f8f9fa;
            }
            .search-item:last-child {
                border-bottom: none;
            }
            .search-item img {
                width: 40px;
                height: 40px;
                object-fit: cover;
                border-radius: 4px;
            }
            .search-item-info {
                flex: 1;
            }
            .search-item-name {
                font-weight: 500;
                margin-bottom: 2px;
                color: #333;
            }
            .search-item-price {
                font-size: 0.9rem;
                color: #dc3545;
                font-weight: 500;
            }
            .search-no-results {
                padding: 15px;
                text-align: center;
                color: #666;
                font-style: italic;
            }
            .search-loading {
                padding: 15px;
                text-align: center;
                color: #007bff;
            }
        </style>
    </nav>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('searchInput');
            const searchDropdown = document.getElementById('searchDropdown');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                
                // Clear previous timeout
                clearTimeout(searchTimeout);
                
                if (query.length < 2) {
                    searchDropdown.style.display = 'none';
                    return;
                }
                
                // Debounce search
                searchTimeout = setTimeout(() => {
                    searchProducts(query);
                }, 300);
            });
            
            // Hide dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !searchDropdown.contains(e.target)) {
                    searchDropdown.style.display = 'none';
                }
            });
            
            // Handle keyboard navigation
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    searchDropdown.style.display = 'none';
                    searchInput.blur();
                }
            });
            
            function searchProducts(query) {
                // Show loading state
                searchDropdown.innerHTML = '<div class="search-loading"><i class="fa fa-spinner fa-spin me-2"></i>Đang tìm kiếm...</div>';
                searchDropdown.style.display = 'block';
                
                fetch(`/test/public/search_products.php?q=${encodeURIComponent(query)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        displaySearchResults(data);
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        searchDropdown.innerHTML = '<div class="search-no-results">Có lỗi xảy ra</div>';
                        searchDropdown.style.display = 'block';
                    });
            }
            
            function displaySearchResults(products) {
                if (products.length === 0) {
                    searchDropdown.innerHTML = '<div class="search-no-results"><i class="fa fa-search me-2"></i>Không tìm thấy sản phẩm</div>';
                } else {
                    searchDropdown.innerHTML = products.map(product => `
                        <div class="search-item" onclick="window.location.href='/test/public/product.php?id=${product.id}'">
                            <img src="${product.img}" alt="${product.name}" onerror="this.src='https://via.placeholder.com/40x40/eee/999?text=No+Image'">
                            <div class="search-item-info">
                                <div class="search-item-name">${product.name}</div>
                                <div class="search-item-price">${formatPrice(product.price)}</div>
                            </div>
                        </div>
                    `).join('');
                }
                searchDropdown.style.display = 'block';
            }
            
            function formatPrice(price) {
                return new Intl.NumberFormat('vi-VN').format(price) + 'đ';
            }
        });
    </script>
</header> 