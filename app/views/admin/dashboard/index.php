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
// Thống kê
$totalProducts = $db->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUsers = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$totalRevenue = $db->query("SELECT SUM(total) FROM orders WHERE status = 'delivered'")->fetchColumn() ?: 0;
// Doanh thu theo ngày (7 ngày gần nhất) - chỉ đơn hàng hoàn thành
$revenueByDay = $db->query("SELECT DATE(created_at) as ngay, SUM(total) as doanhthu FROM orders WHERE status = 'delivered' GROUP BY ngay ORDER BY ngay DESC LIMIT 7")->fetchAll(PDO::FETCH_ASSOC);
// Top sản phẩm bán chạy - chỉ đơn hàng hoàn thành
$topProducts = $db->query("SELECT p.name, SUM(oi.quantity) as total_sold FROM products p JOIN order_items oi ON p.id = oi.product_id JOIN orders o ON oi.order_id = o.id WHERE o.status = 'delivered' GROUP BY p.id, p.name ORDER BY total_sold DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
$tpLabels = [];
$tpData = [];
$tpTotal = 0;
foreach ($topProducts as $row) {
    $tpLabels[] = $row['name'];
    $tpData[] = (int)$row['total_sold'];
    $tpTotal += (int)$row['total_sold'];
}
// Doanh thu theo tháng trong năm hiện tại - chỉ đơn hàng hoàn thành
$monthlyRevenue = $db->query("SELECT MONTH(created_at) AS thang, SUM(total) AS doanhthu FROM orders WHERE YEAR(created_at) = YEAR(CURDATE()) AND status = 'delivered' GROUP BY thang ORDER BY thang")->fetchAll(PDO::FETCH_ASSOC);
$labels = [];
$data = [];
for ($i = 1; $i <= 12; $i++) {
    $labels[] = date('M', mktime(0,0,0,$i,1));
    $found = false;
    foreach ($monthlyRevenue as $row) {
        if ((int)$row['thang'] === $i) {
            $data[] = (int)$row['doanhthu'];
            $found = true;
            break;
        }
    }
    if (!$found) $data[] = 0;
}
// Doanh thu theo loại sản phẩm - chỉ đơn hàng hoàn thành
$typeRevenue = $db->query("SELECT p.type, SUM(oi.quantity * oi.price) AS total FROM order_items oi JOIN products p ON oi.product_id = p.id JOIN orders o ON oi.order_id = o.id WHERE o.status = 'delivered' GROUP BY p.type ORDER BY total DESC")->fetchAll(PDO::FETCH_ASSOC);
$doughnutLabels = [];
$doughnutData = [];
foreach ($typeRevenue as $row) {
    $doughnutLabels[] = ucfirst($row['type']);
    $doughnutData[] = (int)$row['total'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Dashboard Admin</title>
    <link rel="stylesheet" href="/test/public/assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; color: #222; }
        .sidebar-admin {
            min-height: 100vh;
            background: #111;
            border-radius: 24px 0 0 0;
            padding-top: 24px;
        }
        .sidebar-admin h4 {
            font-weight: bold;
            text-align: center;
            margin-bottom: 32px;
            letter-spacing: 1px;
            color: #fff;
        }
        .sidebar-admin a {
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
        .sidebar-admin a.active, .sidebar-admin a:hover {
            background: #fff;
            color: #111;
        }
        .sidebar-admin a.text-danger {
            color: #e53935 !important;
        }
        .main-content, .stat-card { background: #fff; color: #222; }
        .stat-card { border-radius: 8px; padding: 18px; text-align: center; margin-bottom: 18px; border: 1px solid #eee; }
        .stat-card h3 { margin: 0; font-size: 2.2rem; color: #111; }
        .stat-card p { margin: 0; color: #888; }
        .table-topgame th, .table-topgame td { text-align: center; }
    </style>
</head>
<body>
<?php include dirname(__DIR__, 2) . '/partials/header.php'; ?>
<div class="container-fluid">
    <div class="row">
        <nav class="col-md-2 sidebar-admin">
            <h4>QUẢN TRỊ</h4>
            <a href="admin_dashboard.php" class="<?= (!isset($_GET['page']) || $_GET['page']=='') ? 'active' : '' ?>">
                <i class="fa-solid fa-list"></i> Thống kê
            </a>
            <a href="admin_dashboard.php?page=products" class="<?= ($_GET['page'] ?? '') == 'products' ? 'active' : '' ?>">
                <i class="fa-solid fa-box-open"></i> Quản lý sản phẩm
            </a>
            <a href="admin_dashboard.php?page=users" class="<?= ($_GET['page'] ?? '') == 'users' ? 'active' : '' ?>">
                <i class="fa-solid fa-users"></i> Quản lý người dùng
            </a>
            <a href="admin_dashboard.php?page=orders" class="<?= ($_GET['page'] ?? '') == 'orders' ? 'active' : '' ?>">
                <i class="fa-solid fa-cart-shopping"></i> Quản lý đơn hàng
            </a>
            <a href="admin_dashboard.php?page=coupons" class="<?= ($_GET['page'] ?? '') == 'coupons' ? 'active' : '' ?>">
                <i class="fa-solid fa-tag"></i> Quản lý mã giảm giá
            </a>
            <a href="admin_dashboard.php?page=reviews" class="<?= ($_GET['page'] ?? '') == 'reviews' ? 'active' : '' ?>">
                <i class="fa-solid fa-star"></i> Quản lý đánh giá
            </a>
            <a href="logout.php" class="text-danger">
                <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
            </a>
        </nav>
        <main class="col-md-10 admin-main-content">
            <div class="d-flex align-items-center mb-4">
                <i class="fa fa-tachometer-alt me-3" style="font-size: 2rem; color: #007bff;"></i>
                <h2 class="mb-0">Dashboard</h2>
            </div>
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <i class="fa fa-gem me-2" style="color: #28a745; font-size: 1.5rem;"></i>
                                <div class="text-muted small">SẢN PHẨM</div>
                            </div>
                            <div class="fs-4 fw-bold"><?= $totalProducts ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <i class="fa fa-users me-2" style="color: #007bff; font-size: 1.5rem;"></i>
                                <div class="text-muted small">NGƯỜI DÙNG</div>
                            </div>
                            <div class="fs-4 fw-bold"><?= $totalUsers ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <i class="fa fa-shopping-cart me-2" style="color: #ffc107; font-size: 1.5rem;"></i>
                                <div class="text-muted small">ĐƠN HÀNG</div>
                            </div>
                            <div class="fs-4 fw-bold"><?= $totalOrders ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body text-center">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <i class="fa fa-money-bill me-2" style="color: #dc3545; font-size: 1.5rem;"></i>
                                <div class="text-muted small">DOANH THU</div>
                            </div>
                            <div class="fs-4 fw-bold"><?= number_format($totalRevenue) ?>đ</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="fw-bold mb-2">Doanh Thu Theo Tháng</div>
                            <canvas id="earningsLineChart" height="120"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-body">
                            <div class="fw-bold mb-2">Doanh Thu Theo Loại Sản Phẩm</div>
                            <canvas id="revenueDoughnutChart" height="180"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
            <script>
            const earningsLabels = <?= json_encode($labels) ?>;
            const earningsData = <?= json_encode($data) ?>;
            const lineCtx = document.getElementById('earningsLineChart').getContext('2d');
            new Chart(lineCtx, {
                type: 'line',
                data: {
                    labels: earningsLabels,
                    datasets: [{
                        label: 'Doanh Thu',
                        data: earningsData,
                        borderColor: '#36a2eb',
                        backgroundColor: 'rgba(54,162,235,0.1)',
                        tension: 0.4,
                        pointBackgroundColor: '#36a2eb',
                        pointRadius: 4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: { beginAtZero: true }
                    }
                }
            });
            const doughnutLabels = <?= json_encode($tpLabels) ?>;
            const doughnutData = <?= json_encode($tpData) ?>;
            const doughnutTotal = <?= $tpTotal ?>;
            const doughnutCtx = document.getElementById('revenueDoughnutChart').getContext('2d');
            new Chart(doughnutCtx, {
                type: 'doughnut',
                data: {
                    labels: doughnutLabels,
                    datasets: [{
                        data: doughnutData,
                        backgroundColor: ['#36c2eb', '#4bc0c0', '#8e44ad', '#ff6384', '#ffce56']
                    }]
                },
                options: {
                    responsive: true,
                    cutout: '70%',
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                boxWidth: 16,
                                font: { size: 13 },
                                generateLabels: function(chart) {
                                    const data = chart.data;
                                    if (data.labels.length && data.datasets.length) {
                                        return data.labels.map(function(label, i) {
                                            const value = data.datasets[0].data[i];
                                            const percent = doughnutTotal ? Math.round(value / doughnutTotal * 100) : 0;
                                            return {
                                                text: `${label} ${percent}%`,
                                                fillStyle: data.datasets[0].backgroundColor[i],
                                                strokeStyle: '#fff',
                                                lineWidth: 2,
                                                hidden: isNaN(data.datasets[0].data[i]) || chart.getDataVisibility(i) === false,
                                                index: i
                                            };
                                        });
                                    }
                                    return [];
                                }
                            }
                        }
                    }
                }
            });
            </script>
        </main>
    </div>
</div>
</body>
</html> 