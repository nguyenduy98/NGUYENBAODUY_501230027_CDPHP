<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../vendor/autoload.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(403);
    echo "Bạn không có quyền truy cập trang này.";
    exit;
}

$db = Database::getInstance()->getConnection();
$orderId = $_GET['id'] ?? null;

if (!$orderId) {
    http_response_code(400);
    echo "Mã đơn hàng không hợp lệ.";
    exit;
}

// Lấy thông tin đơn hàng với địa chỉ đầy đủ
$stmt = $db->prepare("
    SELECT o.*,
           COALESCE(c.name, o.city) as city_name,
           COALESCE(d.name, o.district) as district_name,
           COALESCE(w.name, o.ward) as ward_name
    FROM orders o 
    LEFT JOIN cities c ON o.city = c.id
    LEFT JOIN districts d ON o.district = d.id
    LEFT JOIN wards w ON o.ward = w.id
    WHERE o.id = ?
");
$stmt->execute([$orderId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

// Lấy thông tin sản phẩm trong đơn hàng
$stmt = $db->prepare("SELECT p.name, p.img, oi.quantity, oi.price FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
$stmt->execute([$orderId]);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Mapping tên trạng thái sang tiếng Việt
$statusLabels = [
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý', 
    'shipped' => 'Đang giao',
    'delivered' => 'Hoàn thành',
    'cancelled' => 'Đã hủy'
];

if (!$order) {
    http_response_code(404);
    echo "Không tìm thấy đơn hàng.";
    exit;
}

// Tạo PDF hóa đơn
$mpdf = new \Mpdf\Mpdf([
    'default_font' => 'dejavusans' // Hỗ trợ font Unicode
]);

$html = '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><title>Hóa Đơn</title>';
$html .= '<style>
    body { font-family: "dejavusans", sans-serif; }
    .invoice-box { max-width: 800px; margin: auto; padding: 20px; border: 1px solid #eee; box-shadow: 0 0 10px rgba(0,0,0,.15); font-size: 16px; line-height: 24px; }
    h1 { text-align: center; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #ddd; padding: 8px; }
    th { background-color: #f2f2f2; }
    .total { text-align: right; font-weight: bold; font-size: 1.2em; }
</style></head><body>';
$html .= '<div class="invoice-box">';
$html .= '<h1>HÓA ĐƠN ĐƠN HÀNG #' . $orderId . '</h1>';
$html .= '<h2>Thông tin khách hàng</h2>';
$html .= '<p><strong>Khách hàng:</strong> ' . htmlspecialchars($order['fullname']) . '</p>';
$html .= '<p><strong>Số điện thoại:</strong> ' . htmlspecialchars($order['phone']) . '</p>';
$html .= '<p><strong>Địa chỉ:</strong> ' . htmlspecialchars($order['address'] . ', ' . $order['ward_name'] . ', ' . $order['district_name'] . ', ' . $order['city_name']) . '</p>';
$html .= '<h2>Chi tiết sản phẩm</h2>';
$html .= '<table><thead><tr><th style="width: 80px;">Hình ảnh</th><th>Tên sản phẩm</th><th>Số lượng</th><th>Đơn giá</th><th>Thành tiền</th></tr></thead><tbody>';

foreach ($items as $item) {
    $html .= '<tr>';
    if ($item['img']) {
        $html .= '<td><img src="' . htmlspecialchars($item['img']) . '" style="width: 50px; height: 50px; object-fit: cover;"></td>';
    } else {
        $html .= '<td style="text-align: center; color: #999;">-</td>';
    }
    $html .= '<td>' . htmlspecialchars($item['name']) . '</td>';
    $html .= '<td>' . $item['quantity'] . '</td>';
    $html .= '<td>' . number_format($item['price']) . 'đ</td>';
    $html .= '<td>' . number_format($item['price'] * $item['quantity']) . 'đ</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table>';
$html .= '<div class="total">Tổng cộng: ' . number_format($order['total']) . 'đ</div>';
$html .= '</div></body></html>';

$mpdf->WriteHTML($html);
$mpdf->Output('hoa_don_' . $orderId . '.pdf', \Mpdf\Output\Destination::INLINE);
exit; 