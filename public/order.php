<?php
session_start();
require_once __DIR__ . '/../app/models/Database.php';
require_once __DIR__ . '/../app/models/Coupon.php';
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$cart = $_SESSION['cart'] ?? [];
$db = Database::getInstance()->getConnection();
$products = [];
$total = 0;
foreach ($cart as $item) {
    $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$item['product_id']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) {
        $product['quantity'] = $item['quantity'];
        $product['subtotal'] = (int)$product['price'] * $item['quantity'];
        $products[] = $product;
        $total += $product['subtotal'];
    }
}
$error = '';
$couponDiscount = 0;
$couponCode = '';
$finalTotal = $total;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order'])) {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $city = trim($_POST['city']);
    $district = trim($_POST['district']);
    $ward = trim($_POST['ward']);
    $address = trim($_POST['address']);
    $note = trim($_POST['note'] ?? '') . ' ' . trim($_POST['note2'] ?? '');
    $payment = $_POST['payment'];
    $couponCode = trim($_POST['coupon']);
    $agree = isset($_POST['agree']);
    
    // Xử lý mã giảm giá
    if (!empty($couponCode)) {
        $userId = is_array($_SESSION['user']) ? $_SESSION['user']['id'] : $_SESSION['user'];
        $couponResult = Coupon::validate($couponCode, $total, $userId);
        if ($couponResult['valid']) {
            $couponDiscount = Coupon::calculateDiscount($couponResult['coupon'], $total);
            $finalTotal = $total - $couponDiscount;
        } else {
            $error = $couponResult['message'];
        }
    }
    
    if ($fullname && $phone && $city && $district && $ward && $address && $agree && !empty($products) && empty($error)) {
        // Debug: Log validation
        error_log("DEBUG: Validation - fullname: '$fullname', phone: '$phone', city: '$city', district: '$district', ward: '$ward', address: '$address', agree: " . ($agree ? 'true' : 'false') . ", products: " . count($products) . ", error: '$error'");
        error_log("DEBUG: Session user: " . print_r($_SESSION['user'], true));
        error_log("DEBUG: Coupon code: '$couponCode'");
        
        // Kiểm tra user đăng nhập
        if (!isset($_SESSION['user']) || (is_array($_SESSION['user']) && !isset($_SESSION['user']['id']))) {
            $error = 'Vui lòng đăng nhập để đặt hàng!';
        } else {
            // Lấy product_id từ sản phẩm đầu tiên (vì đơn hàng chỉ có 1 sản phẩm)
            $firstProduct = $products[0];
            $userId = is_array($_SESSION['user']) ? $_SESSION['user']['id'] : $_SESSION['user'];
            $stmt = $db->prepare("INSERT INTO orders (user_id, product_id, fullname, phone, email, city, district, ward, address, note, payment, coupon, total, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$userId, $firstProduct['id'], $fullname, $phone, $email, $city, $district, $ward, $address, $note, $payment, $couponCode, $finalTotal]);
            
            // Lấy orderId ngay sau khi insert
            $orderId = $db->lastInsertId();
            
            // Tăng số lần sử dụng mã giảm giá
            if (!empty($couponCode)) {
                // Debug: Log thông tin chi tiết
                error_log("DEBUG: Coupon code: $couponCode, User ID: $userId, Order ID: $orderId");
                error_log("DEBUG: Session user: " . print_r($_SESSION['user'], true));
                error_log("DEBUG: Session keys: " . implode(', ', array_keys($_SESSION)));
                
                try {
                    Coupon::useCoupon($couponCode, $userId, $orderId);
                    error_log("DEBUG: Coupon::useCoupon() executed successfully");
                    
                    // Kiểm tra sau khi lưu
                    $db = Database::getInstance()->getConnection();
                    $stmt = $db->prepare("SELECT * FROM coupon_usage WHERE user_id = ? AND coupon_code = ? AND order_id = ?");
                    $stmt->execute([$userId, $couponCode, $orderId]);
                    $record = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($record) {
                        error_log("DEBUG: Record saved successfully in coupon_usage");
                    } else {
                        error_log("DEBUG: ERROR - No record found in coupon_usage after save");
                    }
                    
                } catch (Exception $e) {
                    error_log("DEBUG: Error in Coupon::useCoupon(): " . $e->getMessage());
                }
            }
            
            foreach ($products as $product) {
                $stmt = $db->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $stmt->execute([$orderId, $product['id'], $product['quantity'], $product['price']]);
                $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$product['quantity'], $product['id']]);
            }
            // Tạo PDF hóa đơn NGAY SAU khi đặt hàng thành công
            $mpdf = new \Mpdf\Mpdf();
            $html = '<h2 style="text-align:center;">HÓA ĐƠN ĐƠN HÀNG #' . $orderId . '</h2>';
            $html .= '<p><b>Khách hàng:</b> ' . htmlspecialchars($fullname) . '</p>';
            $html .= '<p><b>Điện thoại:</b> ' . htmlspecialchars($phone) . '</p>';
            $html .= '<p><b>Email:</b> ' . htmlspecialchars($email) . '</p>';
            $cityName = $_POST['city_name'] ?? '';
            $districtName = $_POST['district_name'] ?? '';
            $wardName = $_POST['ward_name'] ?? '';
            $address = $_POST['address'] ?? '';
            $html .= '<p><b>Địa chỉ:</b> ' . htmlspecialchars($address) . ', ' . htmlspecialchars($wardName) . ', ' . htmlspecialchars($districtName) . ', ' . htmlspecialchars($cityName) . '</p>';
            $html .= '<p><b>Phương thức thanh toán:</b> ' . htmlspecialchars($payment) . '</p>';
            $html .= '<p><b>Mã giảm giá:</b> ' . htmlspecialchars($coupon) . '</p>';
            $html .= '<p><b>Ghi chú:</b> ' . htmlspecialchars($note) . '</p>';
            $html .= '<p><b>Thời gian đặt hàng:</b> ' . date('d/m/Y H:i') . '</p>';
            $html .= '<br><table border="1" cellpadding="6" cellspacing="0" width="100%">';
            $html .= '<tr><th>Sản phẩm</th><th>Số lượng</th><th>Đơn giá</th><th>Thành tiền</th></tr>';
            foreach ($products as $product) {
                $html .= '<tr>
                    <td>' . htmlspecialchars($product['name']) . '</td>
                    <td>' . $product['quantity'] . '</td>
                    <td>' . number_format($product['price']) . 'đ</td>
                    <td>' . number_format($product['subtotal']) . 'đ</td>
                </tr>';
            }
            $html .= '</table>';
            $html .= '<h3 style="text-align:right;">Tổng cộng: ' . number_format($total) . 'đ</h3>';
            $mpdf->WriteHTML($html);
            $pdfFile = __DIR__ . '/../storage/invoice_order_' . $orderId . '.pdf';
            $mpdf->Output($pdfFile, \Mpdf\Output\Destination::FILE);

            // Gửi mail xác nhận NGAY SAU khi đặt hàng thành công
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'nguyenduy220598@gmail.com';
                $mail->Password = 'xpdnrqpmwqlvzdlw';
                $mail->SMTPSecure = 'tls';
                $mail->Port = 587;
                $mail->setFrom('your_email@gmail.com', 'Tên shop');
                $mail->addAddress($email, $fullname);
                $mail->Subject = 'Xác nhận đơn hàng #' . $orderId;
                $mail->Body = 'Cảm ơn bạn đã đặt hàng tại shop. Hóa đơn đính kèm file PDF.';
                $mail->addAttachment($pdfFile);
                $mail->send();
            } catch (Exception $e) {
                // Có thể log lỗi gửi mail nếu cần
            }
            // Sau khi gửi mail và tạo PDF, chuyển hướng
            unset($_SESSION['cart']);
            header('Location: order.php?success=1');
            exit;
        }
    } else {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Thanh toán</title>
    <link rel="stylesheet" href="/test/public/assets/style.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        .checkout-step { font-size: 1.2rem; font-weight: bold; border-bottom: 2px solid #111; margin-bottom: 18px; }
        .checkout-label { font-weight: 500; margin-bottom: 4px; }
        .checkout-box { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px #eee; padding: 24px; margin-bottom: 24px; }
        .order-summary { background: #fff; border-radius: 12px; box-shadow: 0 2px 12px #eee; padding: 24px; }
        .order-summary th, .order-summary td { vertical-align: middle; }
        .order-btn { width: 100%; font-size: 1.1rem; font-weight: bold; background: #111; color: #fff; border-radius: 8px; padding: 12px 0; }
        .order-btn:hover { background: #333; }
        /* Đảm bảo checkout-summary chỉ hiển thị trong phần checkout */
        .checkout-summary {
            position: relative;
            z-index: 1;
        }
        /* Tránh conflict với header */
        .main-header .fw-bold.text-danger {
            pointer-events: none;
        }
        /* Đảm bảo JavaScript chỉ target đúng phần checkout */
        .order-summary .checkout-summary {
            isolation: isolate;
        }
    </style>
</head>
<body>
<?php include __DIR__ . '/../app/views/partials/header.php'; ?>
<div class="container mt-4 mb-5" style="max-width:1200px;">
<?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success text-center">Đặt hàng thành công! Cảm ơn bạn đã mua hàng.<br><a href="index.php" class="btn btn-primary mt-3">Về trang chủ</a></div>
<?php else: ?>
    <?php if ($error): ?><div class="alert alert-danger text-center"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="POST">
        <input type="hidden" name="city_name" id="city_name">
        <input type="hidden" name="district_name" id="district_name">
        <input type="hidden" name="ward_name" id="ward_name">
        <div class="row g-4">
            <!-- Thông tin khách hàng -->
            <div class="col-md-7">
                <div class="checkout-box">
                    <div class="checkout-step">
                        <i class="fa fa-user me-2"></i>1 &nbsp; THÔNG TIN KHÁCH HÀNG
                    </div>
                    <div class="mb-2">
                        <label class="checkout-label">Họ và tên *</label>
                        <input type="text" name="fullname" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="checkout-label">Điện thoại *</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="checkout-label">Email</label>
                        <input type="email" name="email" class="form-control">
                    </div>
                    <div class="mb-2">
                        <label class="checkout-label">Thành phố *</label>
                        <select name="city" id="city" class="form-control" required></select>
                    </div>
                    <div class="mb-2">
                        <label class="checkout-label">Quận/Huyện *</label>
                        <select name="district" id="district" class="form-control" required></select>
                    </div>
                    <div class="mb-2">
                        <label class="checkout-label">Phường/Xã *</label>
                        <select name="ward" id="ward" class="form-control" required></select>
                    </div>
                    <div class="mb-2">
                        <label class="checkout-label">Địa chỉ *</label>
                        <input type="text" name="address" class="form-control" required>
                    </div>
                    <div class="mb-2">
                        <label class="checkout-label">Ghi chú</label>
                        <textarea name="note" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <!-- Phương thức thanh toán -->
                <div class="checkout-box">
                    <div class="checkout-step">
                        <i class="fa fa-credit-card me-2"></i>2 &nbsp; PHƯƠNG THỨC THANH TOÁN
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment" value="cod" id="pay1" checked>
                        <label class="form-check-label" for="pay1">
                            <i class="fa fa-money-bill me-2"></i>Thanh toán khi nhận hàng (COD)
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="payment" value="bank" id="pay3">
                        <label class="form-check-label" for="pay3">
                            <i class="fa fa-university me-2"></i>Chuyển khoản ngân hàng
                        </label>
                    </div>
                    <!-- Thông tin chuyển khoản (ẩn/hiện theo JavaScript) -->
                    <div id="bank-info" style="display: none; margin-top: 15px; padding: 15px; background: #f8f9fa; border-radius: 8px;">
                        <h6 class="mb-3"><i class="fa fa-info-circle me-2"></i>Thông tin chuyển khoản:</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Ngân hàng:</strong> Vietcombank</p>
                                <p><strong>Số tài khoản:</strong> 1234567890</p>
                                <p><strong>Chủ tài khoản:</strong> NGUYEN VAN A</p>
                                <p><strong>Số tiền:</strong> <span id="transfer-amount" class="text-danger fw-bold"><?= number_format($finalTotal) ?>đ</span></p>
                                <p><strong>Nội dung:</strong> <span id="transfer-content">Thanh toan don hang</span></p>
                            </div>
                            <div class="col-md-6 text-center">
                                <div id="qr-container">
                                    <img src="/test/public/assets/qr-code.png" alt="QR Code" style="max-width: 150px; border: 1px solid #ddd; border-radius: 8px;">
                                    <p class="mt-2 small text-muted">Quét mã QR để thanh toán</p>
                                </div>
                                <div id="qr-generator" style="display: none;">
                                    <canvas id="qr-canvas" style="max-width: 150px; border: 1px solid #ddd; border-radius: 8px;"></canvas>
                                    <p class="mt-2 small text-muted"></p>
                                </div>
                                <div id="qr-api" style="display: none;">
                                    <img id="qr-api-img" style="max-width: 150px; border: 1px solid #ddd; border-radius: 8px;">
                                    <p class="mt-2 small text-muted"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Xác nhận đơn hàng -->
            <div class="col-md-5">
                <div class="order-summary">
                    <div class="checkout-step">
                        <i class="fa fa-shopping-cart me-2"></i>3 &nbsp; XÁC NHẬN ĐƠN HÀNG
                    </div>
                    <table class="table">
                        <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td style="width:60px;"><img src="<?= htmlspecialchars($product['img']) ?>" style="max-width:50px;"></td>
                                <td><?= htmlspecialchars($product['name']) ?><br><span class="text-muted">SL: <?= $product['quantity'] ?></span></td>
                                <td class="text-end text-danger"><?= number_format($product['subtotal']) ?>đ</td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                    <hr>
                    <div class="checkout-summary">
                        <div class="d-flex justify-content-between">
                            <span>Tổng đơn hàng</span>
                            <span class="fw-bold"><?= number_format($total) ?>đ</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Phí vận chuyển</span>
                            <span>-</span>
                        </div>
                        <?php if ($couponDiscount > 0): ?>
                        <div class="d-flex justify-content-between text-success">
                            <span>Giảm giá (<?= $couponCode ?>)</span>
                            <span>-<?= number_format($couponDiscount) ?>đ</span>
                        </div>
                        <?php 
                        // Hiển thị thông tin giảm tối đa nếu có
                        if (!empty($couponCode)) {
                            $couponResult = Coupon::validate($couponCode, $total);
                            if ($couponResult['valid'] && $couponResult['coupon']['max_discount_amount']) {
                                echo '<div class="d-flex justify-content-between text-muted small">';
                                echo '<span>Giảm tối đa</span>';
                                echo '<span>' . number_format($couponResult['coupon']['max_discount_amount']) . 'đ</span>';
                                echo '</div>';
                            }
                        }
                        ?>
                        <?php endif; ?>
                        <div class="d-flex justify-content-between fs-5 mt-2">
                            <span>Tổng cộng</span>
                            <span class="fw-bold text-danger"><?= number_format($finalTotal) ?>đ</span>
                        </div>
                    </div>
                    <div class="mb-2 mt-2">
                        <label class="checkout-label">Mã giảm giá</label>
                        <button type="button" class="btn btn-outline-primary" id="select-voucher-btn">
                            <i class="fa fa-ticket me-2"></i>Chọn Voucher
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm ms-2" id="remove-voucher-btn" style="display: none;">
                            <i class="fa fa-times me-1"></i>Bỏ voucher
                        </button>
                        <input type="hidden" name="coupon" id="selected-coupon" value="<?= htmlspecialchars($couponCode) ?>">
                        <div id="selected-voucher-info" class="mt-2" style="display: none;">
                            <small class="text-success" id="voucher-message"></small>
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="checkout-label">Ghi chú</label>
                        <textarea name="note2" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="agree" id="agree" required>
                        <label class="form-check-label" for="agree">Tôi đồng ý với <a href="#">chính sách của website</a></label>
                    </div>
                    <button type="submit" name="order" class="order-btn">
                        <i class="fa fa-check me-2"></i>XÁC NHẬN & ĐẶT HÀNG
                    </button>
                </div>
            </div>
        </div>
    </form>
<?php endif; ?>

<!-- Modal Chọn Voucher -->
<div class="modal fade" id="voucherModal" tabindex="-1" aria-labelledby="voucherModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="voucherModalLabel">
                    <i class="fa fa-ticket me-2"></i>Chọn Voucher
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Nhập mã voucher thủ công -->
                <div class="mb-3">
                    <label class="form-label">Nhập mã voucher</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="manual-voucher-code" placeholder="Nhập mã voucher">
                        <button class="btn btn-primary" type="button" id="apply-manual-voucher">
                            <i class="fa fa-check me-1"></i>Áp dụng
                        </button>
                    </div>
                </div>
                
                <hr>
                
                <!-- Danh sách voucher có sẵn -->
                <div class="mb-3">
                    <h6 class="text-primary">Voucher có sẵn</h6>
                    <small class="text-muted">Có thể chọn 1 voucher</small>
                </div>
                
                <div id="voucher-list">
                    <!-- Voucher sẽ được load bằng JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Trở lại</button>
                <button type="button" class="btn btn-primary" id="apply-selected-voucher">OK</button>
            </div>
        </div>
    </div>
</div>

</div>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(function() {
    var selectedVoucher = null;
    var currentTotal = <?= $total ?>;
    
    // Mở modal khi click nút Chọn Voucher
    $('#select-voucher-btn').on('click', function() {
        loadVouchers();
        $('#voucherModal').modal('show');
    });
    
    // Load danh sách voucher
    function loadVouchers() {
        $.get('get_coupons.php', function(response) {
            console.log('Voucher response:', response);
            
            if (response.success && response.coupons && response.coupons.length > 0) {
                var voucherHtml = '';
                
                response.coupons.forEach(function(voucher) {
                    var discountText = '';
                    var maxDiscountText = '';
                    var minOrderText = '';
                    var expiryText = '';
                    
                    if (voucher.discount_type === 'percentage') {
                        discountText = 'Giảm ' + voucher.discount_value + '%';
                    } else {
                        discountText = 'Giảm ' + parseInt(voucher.discount_value).toLocaleString() + 'đ';
                    }
                    
                    if (voucher.max_discount_amount) {
                        maxDiscountText = 'Giảm tối đa ' + parseInt(voucher.max_discount_amount).toLocaleString() + 'đ';
                    }
                    
                    if (voucher.min_order_amount) {
                        minOrderText = 'Đơn tối thiểu ' + parseInt(voucher.min_order_amount).toLocaleString() + 'đ';
                    }
                    
                    if (voucher.end_date) {
                        var endDate = new Date(voucher.end_date);
                        var today = new Date();
                        var diffTime = endDate - today;
                        var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                        expiryText = 'Còn ' + diffDays + ' ngày';
                    }
                    
                    voucherHtml += `
                        <div class="voucher-item border rounded p-3 mb-3" data-voucher='${JSON.stringify(voucher)}'>
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px;">
                                            <i class="fa fa-ticket"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">${voucher.code}</h6>
                                            <small class="text-muted">${discountText}</small>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <small class="text-muted">${maxDiscountText}</small>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-muted">${minOrderText}</small>
                                        </div>
                                        <div class="col-md-4">
                                            <small class="text-warning">${expiryText}</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4 text-end">
                                    <div class="form-check">
                                        <input class="form-check-input voucher-checkbox" type="checkbox" name="selectedVoucher" value="${voucher.code}" id="voucher_${voucher.code}">
                                        <label class="form-check-label" for="voucher_${voucher.code}">
                                            <small class="text-muted">Chọn</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                $('#voucher-list').html(voucherHtml);
            } else {
                $('#voucher-list').html('<p class="text-muted">Không có voucher nào khả dụng</p>');
            }
        }).fail(function(xhr, status, error) {
            console.error('Error loading vouchers:', error);
            $('#voucher-list').html('<p class="text-danger">Lỗi khi tải voucher</p>');
        });
    }
    
    // Xử lý khi chọn voucher
    $(document).on('change', '.voucher-checkbox', function() {
        var voucherCode = $(this).val();
        var voucherItem = $(this).closest('.voucher-item');
        var voucherData = JSON.parse(voucherItem.attr('data-voucher'));
        
        if ($(this).is(':checked')) {
            // Bỏ chọn tất cả voucher khác
            $('.voucher-checkbox').not(this).prop('checked', false);
            
            // Chọn voucher này
            selectedVoucher = voucherData;
            console.log('Selected voucher:', selectedVoucher);
        } else {
            // Bỏ chọn voucher này
            selectedVoucher = null;
            console.log('Deselected voucher:', voucherCode);
        }
    });
    
    // Xử lý bỏ voucher đã áp dụng
    $('#remove-voucher-btn').on('click', function() {
        // Xóa voucher đã chọn
        $('#selected-coupon').val('');
        $('#selected-voucher-info').hide();
        $('#remove-voucher-btn').hide();
        
        // Khôi phục giá gốc
        removeDiscountInfo();
        updateTotalPrice(originalTotal);
        
        // Cập nhật QR code nếu đang chọn thanh toán ngân hàng
        if ($('input[name="payment"]:checked').val() === 'bank') {
            updateTransferInfo();
            generateQRCode();
        }
        
        console.log('Removed applied voucher');
    });
    
    // Áp dụng voucher được chọn
    $('#apply-selected-voucher').on('click', function() {
        if (selectedVoucher) {
            applyVoucher(selectedVoucher);
            $('#voucherModal').modal('hide');
        } else {
            alert('Vui lòng chọn một voucher');
        }
    });
    
    // Áp dụng voucher nhập tay
    $('#apply-manual-voucher').on('click', function() {
        var manualCode = $('#manual-voucher-code').val().trim();
        if (manualCode) {
            $.post('check_coupon.php', {
                code: manualCode,
                total: currentTotal
            }, function(response) {
                if (response.valid) {
                    var voucherData = {
                        code: manualCode,
                        discount_type: 'fixed',
                        discount_value: parseInt(response.discount.replace(/[^\d]/g, '')),
                        max_discount_amount: response.maxDiscount ? parseInt(response.maxDiscount.replace(/[^\d]/g, '')) : null
                    };
                    applyVoucher(voucherData);
                    $('#voucherModal').modal('hide');
                } else {
                    alert(response.message);
                }
            }, 'json');
        } else {
            alert('Vui lòng nhập mã voucher');
        }
    });
    
    // Hàm áp dụng voucher
    function applyVoucher(voucher) {
        var discount = 0;
        var finalTotal = currentTotal;
        
        if (voucher.discount_type === 'percentage') {
            discount = currentTotal * voucher.discount_value / 100;
            if (voucher.max_discount_amount && discount > voucher.max_discount_amount) {
                discount = voucher.max_discount_amount;
            }
        } else {
            discount = voucher.discount_value;
        }
        
        finalTotal = currentTotal - discount;
        if (finalTotal < 0) finalTotal = 0;
        
        // Cập nhật UI
        $('#selected-coupon').val(voucher.code);
        $('#voucher-message').html(`Voucher <strong>${voucher.code}</strong> đã được áp dụng! Giảm ${parseInt(discount).toLocaleString()}đ`);
        $('#selected-voucher-info').show();
        $('#remove-voucher-btn').show();
        
        // Cập nhật giá tổng cộng
        updateTotalPrice(finalTotal);
        
        // Thêm thông tin giảm giá vào phần tổng
        addDiscountInfo(parseInt(discount), voucher.max_discount_amount);
        
        // Cập nhật QR code nếu đang chọn thanh toán ngân hàng
        if ($('input[name="payment"]:checked').val() === 'bank') {
            updateTransferInfo();
            generateQRCode();
        }
    }
    
    // Lấy danh sách mã giảm giá
    console.log('Starting to load coupons...');
    console.log('Dropdown element exists:', $('#coupon-select').length > 0);
    
    $.get('get_coupons.php', function(response) {
        console.log('Coupon response:', response);
        console.log('Response type:', typeof response);
        
        // Nếu response là string, parse thành JSON
        if (typeof response === 'string') {
            try {
                response = JSON.parse(response);
            } catch (e) {
                console.error('Error parsing JSON:', e);
                return;
            }
        }
        
        if (response.success && response.coupons && response.coupons.length > 0) {
            console.log('Found ' + response.coupons.length + ' coupons');
            console.log('Coupons data:', response.coupons);
            
            response.coupons.forEach(function(coupon, index) {
                console.log('Processing coupon ' + index + ':', coupon);
                
                var discountText = '';
                if (coupon.discount_type === 'percentage') {
                    discountText = 'Giảm ' + coupon.discount_value + '%';
                } else {
                    discountText = 'Giảm ' + parseInt(coupon.discount_value).toLocaleString() + 'đ';
                }
                
                if (coupon.min_order_amount) {
                    discountText += ' (Đơn tối thiểu: ' + parseInt(coupon.min_order_amount).toLocaleString() + 'đ)';
                }
                
                var optionHtml = '<option value="' + coupon.code + '" data-discount="' + coupon.discount_value + '" data-type="' + coupon.discount_type + '" data-min="' + (coupon.min_order_amount || '') + '" data-max="' + (coupon.max_discount_amount || '') + '">' + coupon.code + ' - ' + discountText + '</option>';
                console.log('Adding option:', optionHtml);
                $('#coupon-select').append(optionHtml);
            });
            
            console.log('Total options in dropdown:', $('#coupon-select option').length);
        } else {
            console.log('No coupons found or error:', response);
            console.log('response.success:', response.success);
            console.log('response.coupons:', response.coupons);
            console.log('response.coupons.length:', response.coupons ? response.coupons.length : 'undefined');
            console.log('All response keys:', Object.keys(response));
        }
    }).fail(function(xhr, status, error) {
        console.error('Error loading coupons:', error);
        console.log('Status:', status);
        console.log('Response:', xhr.responseText);
        console.log('Response headers:', xhr.getAllResponseHeaders());
    });
    
    // Xử lý khi chọn mã giảm giá từ dropdown
    $('#coupon-select').on('change', function() {
        var selectedCode = $(this).val();
        if (selectedCode) {
            $('input[name="coupon"]').val(selectedCode);
            
            // Lấy thông tin mã giảm giá từ option được chọn
            var selectedOption = $(this).find('option:selected');
            var discountValue = selectedOption.data('discount');
            var discountType = selectedOption.data('type');
            var minOrder = selectedOption.data('min');
            var maxDiscount = selectedOption.data('max');
            
            // Kiểm tra điều kiện áp dụng
            var currentTotal = <?= $total ?>;
            var isValid = true;
            var message = '';
            
            if (minOrder && currentTotal < minOrder) {
                isValid = false;
                message = 'Đơn hàng tối thiểu: ' + parseInt(minOrder).toLocaleString() + 'đ';
            } else {
                // Tính toán giảm giá
                var discount = 0;
                if (discountType === 'percentage') {
                    discount = currentTotal * discountValue / 100;
                    if (maxDiscount && discount > maxDiscount) {
                        discount = maxDiscount;
                    }
                } else {
                    discount = discountValue;
                }
                
                var finalTotal = currentTotal - discount;
                if (finalTotal < 0) finalTotal = 0;
                
                // Hiển thị thông báo
                $('input[name="coupon"]').next('small').remove();
                message = 'Mã giảm giá hợp lệ! Giảm ' + parseInt(discount).toLocaleString() + 'đ';
                if (maxDiscount) {
                    message += ' (Tối đa: ' + parseInt(maxDiscount).toLocaleString() + 'đ)';
                }
                
                // Cập nhật giá tổng cộng
                updateTotalPrice(finalTotal);
                
                // Thêm thông tin giảm giá vào phần tổng
                addDiscountInfo(parseInt(discount), maxDiscount);
                
                // Cập nhật QR code nếu đang chọn thanh toán ngân hàng
                if ($('input[name="payment"]:checked').val() === 'bank') {
                    updateTransferInfo();
                    generateQRCode();
                }
            }
            
            if (!isValid) {
                $('input[name="coupon"]').next('small').remove();
                $('input[name="coupon"]').after('<small class="text-danger">' + message + '</small>');
                removeDiscountInfo();
                updateTotalPrice(originalTotal);
            } else {
                $('input[name="coupon"]').after('<small class="text-success">' + message + '</small>');
            }
        } else {
            // Xóa mã giảm giá
            $('input[name="coupon"]').val('');
            $('input[name="coupon"]').next('small').remove();
            removeDiscountInfo();
            updateTotalPrice(originalTotal);
        }
    });
    
    // Lấy tỉnh/thành
    $.get('https://provinces.open-api.vn/api/p/', function(data) {
        $('#city').append('<option value="">Chọn tỉnh/thành</option>');
        data.forEach(function(item) {
            $('#city').append('<option value="'+item.code+'">'+item.name+'</option>');
        });
    });
    // Khi chọn tỉnh/thành, load quận/huyện
    $('#city').on('change', function() {
        var code = $(this).val();
        $('#district').html('<option value=\"\">Chọn quận/huyện</option>');
        $('#ward').html('<option value=\"\">Chọn phường/xã</option>');
        if (code) {
            $.get('https://provinces.open-api.vn/api/p/'+code+'?depth=2', function(data) {
                data.districts.forEach(function(item) {
                    $('#district').append('<option value="'+item.code+'">'+item.name+'</option>');
                });
            });
        }
    });
    // Khi chọn quận/huyện, load phường/xã
    $('#district').on('change', function() {
        var code = $(this).val();
        $('#ward').html('<option value=\"\">Chọn phường/xã</option>');
        if (code) {
            $.get('https://provinces.open-api.vn/api/d/'+code+'?depth=2', function(data) {
                data.wards.forEach(function(item) {
                    $('#ward').append('<option value="'+item.name+'">'+item.name+'</option>');
                });
            });
        }
    });
    $('#ward').on('change', function() {
        var selected = $(this).find('option:selected').text();
        $('#ward_name').val(selected);
    });
    
    // Xử lý kiểm tra mã giảm giá
    $('input[name="coupon"]').on('blur', function() {
        var couponCode = $(this).val().trim();
        if (couponCode) {
            $.post('check_coupon.php', {
                code: couponCode,
                total: <?= $total ?>
            }, function(response) {
                if (response.valid) {
                    $('input[name="coupon"]').next('small').remove();
                    var message = 'Mã giảm giá hợp lệ! Giảm ' + response.discount + 'đ';
                    if (response.maxDiscount) {
                        message += ' (Tối đa: ' + response.maxDiscount + 'đ)';
                    }
                    $('input[name="coupon"]').after('<small class="text-success">' + message + '</small>');
                    
                    // Cập nhật giá tổng cộng
                    updateTotalPrice(response.finalTotal);
                    
                    // Thêm thông tin giảm giá vào phần tổng
                    addDiscountInfo(response.discount, response.maxDiscount);
                    
                    // Cập nhật QR code nếu đang chọn thanh toán ngân hàng
                    if ($('input[name="payment"]:checked').val() === 'bank') {
                        updateTransferInfo();
                        generateQRCode();
                    }
                } else {
                    $('input[name="coupon"]').next('small').remove();
                    $('input[name="coupon"]').after('<small class="text-danger">' + response.message + '</small>');
                    
                    // Xóa thông tin giảm giá nếu có
                    removeDiscountInfo();
                    // Khôi phục giá gốc
                    updateTotalPrice(originalTotal);
                    
                    // Cập nhật QR code nếu đang chọn thanh toán ngân hàng
                    if ($('input[name="payment"]:checked').val() === 'bank') {
                        updateTransferInfo();
                        generateQRCode();
                    }
                }
            }, 'json');
        } else {
            // Xóa thông tin giảm giá nếu có
            removeDiscountInfo();
            // Khôi phục giá gốc
            updateTotalPrice(originalTotal);
        }
    });
    
    // Hàm cập nhật giá tổng cộng
    function updateTotalPrice(newTotal) {
        // Sử dụng selector cụ thể hơn để tránh conflict với header
        $('.order-summary .checkout-summary .d-flex.justify-content-between.fs-5.mt-2 .fw-bold.text-danger').fadeOut(200, function() {
            $(this).text(newTotal + 'đ').fadeIn(200);
        });
    }
    
    // Hàm thêm thông tin giảm giá
    function addDiscountInfo(discount, maxDiscount) {
        // Xóa thông tin giảm giá cũ nếu có
        removeDiscountInfo();
        
        var discountHtml = '<div class="d-flex justify-content-between text-success">' +
            '<span>Giảm giá</span>' +
            '<span>-' + discount + 'đ</span>' +
            '</div>';
        
        // Thêm thông tin giảm tối đa nếu có
        if (maxDiscount) {
            discountHtml += '<div class="d-flex justify-content-between text-muted small">' +
                '<span>Giảm tối đa</span>' +
                '<span>' + maxDiscount + 'đ</span>' +
                '</div>';
        }
        
        // Thêm vào trước phần tổng cộng trong checkout summary
        $('.order-summary .checkout-summary .d-flex.justify-content-between.fs-5.mt-2').before(discountHtml);
    }
    
    // Hàm xóa thông tin giảm giá
    function removeDiscountInfo() {
        $('.order-summary .checkout-summary .d-flex.justify-content-between.text-success').remove();
        $('.order-summary .checkout-summary .d-flex.justify-content-between.text-muted.small').remove();
    }
    
    // Khởi tạo giá gốc
    var originalTotal = '<?= number_format($total) ?>';
    
    // Xử lý hiển thị thông tin chuyển khoản
    $('input[name="payment"]').on('change', function() {
        var selectedPayment = $(this).val();
        if (selectedPayment === 'bank') {
            $('#bank-info').slideDown(300);
            updateTransferInfo();
            generateQRCode();
        } else {
            $('#bank-info').slideUp(300);
        }
    });
    
    // Cập nhật nội dung chuyển khoản khi thay đổi thông tin
    $('input[name="fullname"], input[name="phone"]').on('input', function() {
        if ($('input[name="payment"]:checked').val() === 'bank') {
            updateTransferInfo();
            generateQRCode();
        }
    });
    
    // Hàm cập nhật thông tin chuyển khoản
    function updateTransferInfo() {
        var fullname = $('input[name="fullname"]').val() || 'Khach hang';
        var phone = $('input[name="phone"]').val() || '';
        var orderId = 'DH' + Date.now();
        var content = fullname + ' - ' + phone + ' - ' + orderId;
        $('#transfer-content').text(content);
        
        // Cập nhật số tiền
        var finalTotal = $('.order-summary .checkout-summary .d-flex.justify-content-between.fs-5.mt-2 .fw-bold.text-danger').text();
        $('#transfer-amount').text(finalTotal);
    }
    
    // Hàm tạo QR code động
    function generateQRCode() {
        try {
            var fullname = $('input[name="fullname"]').val() || 'Khach hang';
            var phone = $('input[name="phone"]').val() || '';
            var orderId = 'DH' + Date.now();
            var finalTotal = $('.order-summary .checkout-summary .d-flex.justify-content-between.fs-5.mt-2 .fw-bold.text-danger').text();
            
            // Lấy số tiền từ text (loại bỏ ký tự đặc biệt)
            var amount = finalTotal.replace(/[^\d]/g, '');
            
            // Tạo dữ liệu QR code
            var qrData = {
                bank: 'Vietcombank',
                account: '1234567890',
                amount: amount,
                content: fullname + ' - ' + phone + ' - ' + orderId
            };
            
            // Ẩn tất cả QR containers
            $('#qr-container').hide();
            $('#qr-generator').hide();
            $('#qr-api').hide();
            
            // Ưu tiên sử dụng API VietQR của bạn
            generateQRWithAPI(qrData);
            
        } catch (error) {
            console.error('Lỗi trong generateQRCode:', error);
            // Fallback về QR code tĩnh
            $('#qr-generator').hide();
            $('#qr-api').hide();
            $('#qr-container').show();
        }
    }
    
    // Hàm tạo QR code bằng API
    function generateQRWithAPI(qrData) {
        try {
            $('#qr-generator').hide();
            $('#qr-api').show();
            
            // Sử dụng API VietQR của bạn
            var amount = qrData.amount || '0';
            var qrUrl = 'https://api.vietqr.io/image/970415-113366668888-0U0fDKF.jpg?amount=' + amount;
            
            $('#qr-api-img').attr('src', qrUrl);
        } catch (error) {
            console.error('Lỗi tạo QR API:', error);
            // Fallback cuối cùng về QR code tĩnh
            $('#qr-api').hide();
            $('#qr-container').show();
        }
    }
});
</script>
<?php
// Ở phía dưới, khi chỉ có ?success=1 thì chỉ hiển thị thông báo thành công, KHÔNG tạo PDF, KHÔNG gửi mail, KHÔNG truy cập $_POST nữa!
?>
</body>
</html> 