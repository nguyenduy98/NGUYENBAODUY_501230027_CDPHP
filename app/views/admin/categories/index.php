<?php
require_once dirname(__DIR__, 3) . '/models/Database.php';
$db = Database::getInstance()->getConnection();
$categories = $db->query("SELECT * FROM categories")->fetchAll(PDO::FETCH_ASSOC);
ob_start();
?>
<h2>Quản lý thể loại</h2>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th><th>Tên thể loại</th><th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($categories as $cat): ?>
        <tr>
            <td><?= $cat['id'] ?></td>
            <td><?= htmlspecialchars($cat['name']) ?></td>
            <td>
                <a href="#" class="btn btn-primary btn-sm">Sửa</a>
                <a href="#" class="btn btn-danger btn-sm">Xóa</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?> 