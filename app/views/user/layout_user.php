<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? 'Tài khoản của tôi' ?> - Bạc Hiếu Minh</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        .user-sidebar .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 12px;
        }
        .user-sidebar .card-header {
            border-radius: 12px 12px 0 0 !important;
            border: none;
        }
        .user-sidebar .list-group-item {
            border: none;
            border-radius: 0;
            padding: 12px 20px;
            transition: all 0.2s;
        }
        .user-sidebar .list-group-item:hover {
            background-color: #f8f9fa;
            transform: translateX(5px);
        }
        .user-sidebar .list-group-item.active {
            background-color: #007bff;
            color: white;
            border: none;
        }
        .user-sidebar .list-group-item.active:hover {
            background-color: #0056b3;
        }
        .main-content .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 12px;
        }
        .main-content .card-header {
            border-radius: 12px 12px 0 0 !important;
            border: none;
            background-color: #f8f9fa;
        }
        .product-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .order-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .order-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .favorite-item {
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .favorite-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
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
        .stat-card {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-2px);
        }
        .page-title {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-title i {
            font-size: 2rem;
            margin-right: 15px;
        }
        .page-title h1 {
            margin: 0;
            font-weight: 600;
        }
        .form-section {
            max-width: 500px;
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .form-section h5 {
            margin-bottom: 25px;
            color: #333;
        }
        .table {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .table th {
            background-color: #f8f9fa;
            border: none;
            font-weight: 600;
        }
        .table td {
            border: none;
            border-bottom: 1px solid #e9ecef;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6c757d;
        }
        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            color: #dee2e6;
        }
        .empty-state h4 {
            margin-bottom: 10px;
        }
        .empty-state p {
            margin-bottom: 20px;
        }
        .stat-card h3 {
            margin: 0;
            font-size: 2.2rem;
            color: #007bff;
        }
        .stat-card p {
            margin: 0;
            color: #6c757d;
        }
        .btn-cancel {
            background: #dc3545;
            color: white;
            border: none;
        }
        .btn-cancel:hover {
            background: #c82333;
            color: white;
        }
        .btn-restore {
            background: #28a745;
            color: white;
            border: none;
        }
        .btn-restore:hover {
            background: #218838;
            color: white;
        }
        .order-item {
            display: flex;
            align-items: center;
            gap: 16px;
            margin-bottom: 12px;
        }
        .order-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .order-details {
            flex: 1;
        }
        .order-price {
            font-weight: bold;
            color: #dc3545;
        }
        .order-actions {
            margin-top: 16px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        .delivery-info {
            border: 1px solid #e9ecef;
            background: #f8f9fa;
        }
        .user-review-item {
            border-left: 4px solid #007bff;
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .product-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .star-rating {
            font-size: 24px;
            color: #ffc107;
            cursor: pointer;
        }
        .star-rating .fa-star {
            transition: color 0.2s;
        }
        .star-rating .fa-star:hover,
        .star-rating .fa-star.active {
            color: #ffc107;
        }
        .review-form {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-top: 15px;
        }
        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }
        .modal-header {
            border-radius: 12px 12px 0 0;
            border-bottom: 1px solid #e9ecef;
        }
        .modal-footer {
            border-radius: 0 0 12px 12px;
            border-top: 1px solid #e9ecef;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../partials/header.php'; ?>

<div class="container mt-4 mb-5">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <div class="card user-sidebar">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fa fa-user me-2"></i>Tài khoản của tôi</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="user_dashboard.php?tab=dashboard" 
                           class="list-group-item list-group-item-action <?= ($currentTab ?? '') == 'dashboard' ? 'active' : '' ?>">
                            <i class="fa fa-tachometer-alt me-2"></i>Dashboard
                        </a>
                        <a href="user_dashboard.php?tab=orders" 
                           class="list-group-item list-group-item-action <?= ($currentTab ?? '') == 'orders' ? 'active' : '' ?>">
                            <i class="fa fa-shopping-bag me-2"></i>Đơn hàng của tôi
                        </a>
                        <a href="user_dashboard.php?tab=favorites" 
                           class="list-group-item list-group-item-action <?= ($currentTab ?? '') == 'favorites' ? 'active' : '' ?>">
                            <i class="fa fa-heart me-2"></i>Sản phẩm yêu thích
                        </a>
                        <a href="reviews.php" 
                           class="list-group-item list-group-item-action <?= ($currentTab ?? '') == 'reviews' ? 'active' : '' ?>">
                            <i class="fa fa-star me-2"></i>Đánh giá sản phẩm
                        </a>
                        <a href="user_dashboard.php?tab=info" 
                           class="list-group-item list-group-item-action <?= ($currentTab ?? '') == 'info' ? 'active' : '' ?>">
                            <i class="fa fa-user-edit me-2"></i>Thông tin cá nhân
                        </a>
                        <a href="user_dashboard.php?tab=pass" 
                           class="list-group-item list-group-item-action <?= ($currentTab ?? '') == 'pass' ? 'active' : '' ?>">
                            <i class="fa fa-key me-2"></i>Đổi mật khẩu
                        </a>
                        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fa fa-sign-out-alt me-2"></i>Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <div class="page-title">
                <i class="<?= $pageIcon ?? 'fa fa-user' ?>" style="color: <?= $pageIconColor ?? '#007bff' ?>;"></i>
                <h1 class="mb-0"><?= $pageTitle ?? 'Tài khoản của tôi' ?></h1>
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

            <?= $content ?? '' ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<?= $scripts ?? '' ?>
</body>
</html> 