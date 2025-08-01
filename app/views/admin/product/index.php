<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__, 3) . '/models/Database.php';
$db = Database::getInstance()->getConnection();

function handle_upload_img($input_name) {
    if (isset($_FILES[$input_name]) && $_FILES[$input_name]['error'] == 0) {
        $targetDir = '/test/public/uploads/';
        $fileName = uniqid('img_') . '_' . basename($_FILES[$input_name]['name']);
        $targetFile = $targetDir . $fileName;
        $absPath = $_SERVER['DOCUMENT_ROOT'] . $targetFile;
        if (move_uploaded_file($_FILES[$input_name]['tmp_name'], $absPath)) {
            return $targetFile;
        }
    }
    return null;
}

// Xử lý xóa sản phẩm
if (isset($_POST['delete_id'])) {
    $id = (int)$_POST['delete_id'];
    $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$id]);
    header('Location: admin_dashboard.php?page=products');
    exit;
}
// Xử lý cập nhật sản phẩm
if (isset($_POST['update_game'])) {
    $id = (int)$_POST['edit_id'];
    $name = $_POST['edit_name'];
    $type = $_POST['edit_type'];
    $price = $_POST['edit_price'];
    $stock = (int)$_POST['edit_stock'];
    $description = $_POST['edit_description'] ?? '';
    $img = $_POST['edit_img'] ?? '';
    $imgFile = handle_upload_img('edit_img_file');
    if ($imgFile) $img = $imgFile;
    $stmt = $db->prepare("UPDATE products SET name=?, type=?, price=?, stock=?, img=?, description=? WHERE id=?");
    $stmt->execute([$name, $type, $price, $stock, $img, $description, $id]);
    header('Location: admin_dashboard.php?page=products');
    exit;
}
// Xử lý thêm sản phẩm mới
if (isset($_POST['add_game'])) {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $price = $_POST['price'];
    $stock = (int)$_POST['stock'];
    $description = $_POST['description'] ?? '';
    $imgPath = '';
    $imgFile = handle_upload_img('img_file');
    if ($imgFile) $imgPath = $imgFile;
    $stmt = $db->prepare("INSERT INTO products (name, type, price, stock, img, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$name, $type, $price, $stock, $imgPath, $description]);
    header('Location: admin_dashboard.php?page=products');
    exit;
}

// Lấy loại sản phẩm và từ khóa tìm kiếm từ URL
$currentType = isset($_GET['type']) ? $_GET['type'] : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Xây dựng câu truy vấn SQL động
$sql = "SELECT * FROM products WHERE 1";
$params = [];
if ($currentType !== 'all') {
    $sql .= " AND type = ?";
    $params[] = $currentType;
}
if ($search !== '') {
    $sql .= " AND name LIKE ?";
    $params[] = '%' . $search . '%';
}
$sql .= " ORDER BY id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<h2>Quản lý sản phẩm</h2>
<!-- Tabs phân loại sản phẩm -->
<ul class="nav nav-tabs mb-3">
  <li class="nav-item"><a class="nav-link <?= $currentType=='all'?'active':'' ?>" href="admin_dashboard.php?page=products&type=all">Tất cả</a></li>
  <li class="nav-item"><a class="nav-link <?= $currentType=='daychuyen'?'active':'' ?>" href="admin_dashboard.php?page=products&type=daychuyen">Dây chuyền</a></li>
  <li class="nav-item"><a class="nav-link <?= $currentType=='ring'?'active':'' ?>" href="admin_dashboard.php?page=products&type=ring">Nhẫn</a></li>
  <li class="nav-item"><a class="nav-link <?= $currentType=='vongtay'?'active':'' ?>" href="admin_dashboard.php?page=products&type=vongtay">Vòng tay</a></li>
  <li class="nav-item"><a class="nav-link <?= $currentType=='bongtai'?'active':'' ?>" href="admin_dashboard.php?page=products&type=bongtai">Khuyên tai</a></li>
</ul>
<!-- Form tìm kiếm sản phẩm -->
<form method="get" class="mb-3 d-flex" style="max-width:400px;">
  <input type="hidden" name="page" value="products">
  <input type="hidden" name="type" value="<?= htmlspecialchars($currentType) ?>">
  <input type="text" name="search" class="form-control me-2" placeholder="Tìm tên sản phẩm..." value="<?= htmlspecialchars($search) ?>">
  <button class="btn btn-primary" type="submit">Tìm</button>
</form>
<!-- Nút mở form -->
<button class="btn btn-success mb-3" onclick="document.getElementById('addGameForm').style.display='block'">Thêm sản phẩm mới</button>
<!-- Form thêm sản phẩm mới (ẩn/hiện bằng JS) -->
<div id="addGameForm" style="display:none; max-width:500px; background:#fff; padding:24px; border-radius:8px; box-shadow:0 2px 8px #ccc; margin-bottom:24px;">
    <form method="POST" enctype="multipart/form-data">
        <div class="mb-2">
            <label class="form-label">Tên sản phẩm</label>
            <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Nhóm</label>
            <select name="type" class="form-control" required>
                <option value="daychuyen">DÂY CHUYỀN</option>
                <option value="ring">NHẪN</option>
                <option value="vongtay">VÒNG TAY</option>
                <option value="bongtai">KHUYÊN TAI</option>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Giá</label>
            <input type="text" name="price" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Tồn kho</label>
            <input type="number" name="stock" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Mô tả sản phẩm</label>
            <textarea name="description" class="form-control" rows="3"></textarea>
        </div>
        <div class="mb-2">
            <label class="form-label">Hình ảnh</label>
            <input type="file" name="img_file" class="form-control" accept="image/*">
        </div>
        <button type="submit" name="add_game" class="btn btn-primary">Lưu</button>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('addGameForm').style.display='none'">Hủy</button>
    </form>
</div>
<!-- Form sửa sản phẩm (ẩn/hiện bằng JS) -->
<div id="editGameForm" style="display:none; max-width:500px; background:#fff; padding:24px; border-radius:8px; box-shadow:0 2px 8px #ccc; margin-bottom:24px;">
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="edit_id" id="edit_id">
        <div class="mb-2">
            <label class="form-label">Tên sản phẩm</label>
            <input type="text" name="edit_name" id="edit_name" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Nhóm</label>
            <select name="edit_type" id="edit_type" class="form-control" required>
                <option value="daychuyen">DÂY CHUYỀN</option>
                <option value="ring">NHẪN</option>
                <option value="vongtay">VÒNG TAY</option>
                <option value="bongtai">KHUYÊN TAI</option>
            </select>
        </div>
        <div class="mb-2">
            <label class="form-label">Giá</label>
            <input type="text" name="edit_price" id="edit_price" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Tồn kho</label>
            <input type="number" name="edit_stock" id="edit_stock" class="form-control" required>
        </div>
        <div class="mb-2">
            <label class="form-label">Mô tả sản phẩm</label>
            <textarea name="edit_description" id="edit_description" class="form-control" rows="3"></textarea>
        </div>
        <div class="mb-2">
            <label class="form-label">Hình ảnh mới (nếu muốn thay)
                <input type="file" name="edit_img_file" class="form-control" accept="image/*">
            </label>
            <input type="hidden" name="edit_img" id="edit_img">
        </div>
        <button type="submit" name="update_game" class="btn btn-primary">Cập nhật</button>
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('editGameForm').style.display='none'">Hủy</button>
    </form>
</div>
<table class="table table-bordered admin-table">
    <thead>
        <tr>
            <th>ID</th><th>Tên sản phẩm</th><th>Loại</th><th>Giá</th><th>Tồn kho</th><th>Hình ảnh</th><th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($products as $product): ?>
        <tr>
            <td><?= $product['id'] ?></td>
            <td><?= htmlspecialchars($product['name']) ?></td>
            <td><?= htmlspecialchars($product['type']) ?></td>
            <td><?= htmlspecialchars($product['price']) ?></td>
            <td><?= $product['stock'] ?? 0 ?></td>
            <td>
                <?php if ($product['img']): ?>
                    <img src="<?= htmlspecialchars($product['img']) ?>" style="max-width:60px;">
                <?php endif; ?>
            </td>
            <td>
                <button type="button" class="btn btn-primary btn-sm" onclick="editGame(<?= $product['id'] ?>, '<?= htmlspecialchars(addslashes($product['name'])) ?>', '<?= htmlspecialchars(addslashes($product['type'])) ?>', '<?= htmlspecialchars(addslashes($product['price'])) ?>', '<?= $product['stock'] ?>', '<?= htmlspecialchars(addslashes($product['img'])) ?>', '<?= htmlspecialchars(addslashes($product['description'] ?? '')) ?>')">Sửa</button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Bạn có chắc muốn xóa sản phẩm này?');">
                    <input type="hidden" name="delete_id" value="<?= $product['id'] ?>">
                    <button type="submit" class="btn btn-danger btn-sm">Xóa</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<script>
function editGame(id, name, type, price, stock, img, description) {
    document.getElementById('edit_id').value = id;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_type').value = type;
    var select = document.getElementById('edit_type');
    for (var i = 0; i < select.options.length; i++) {
        if (select.options[i].value === type) {
            select.selectedIndex = i;
            break;
        }
    }
    document.getElementById('edit_price').value = price;
    document.getElementById('edit_stock').value = stock;
    document.getElementById('edit_img').value = img;
    document.getElementById('edit_description').value = description || '';
    document.getElementById('editGameForm').style.display = 'block';
    window.scrollTo(0,0);
}
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?> 