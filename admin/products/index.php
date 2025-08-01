<?php
require_once '../../includes/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Chú ý: Đảm bảo biến session 'user_role' được sử dụng nhất quán với login.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Lấy danh sách sản phẩm cùng với tên danh mục
$sql = "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC";
$result = $conn->query($sql);

// Các thông báo flash message nên được xử lý trong admin_header.php
// hoặc bạn có thể giữ lại ở đây nếu muốn kiểm soát cụ thể hơn
// $success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
// $error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
// unset($_SESSION['success_message']);
// unset($_SESSION['error_message']);

// Nhúng header riêng cho Admin Panel
require_once '../includes/admin_header.php'; // Đã thay đổi đường dẫn và tên file header
?>

<div class="admin-products-page">
    <div class="container">
        <div class="admin-products-header">
            <h2><i class="fas fa-gem"></i> Quản Lý Sản Phẩm</h2>
            <a href="create.php" class="btn-add-product">
                <i class="fas fa-plus"></i> Thêm Sản Phẩm Mới
            </a>
        </div>

        <div class="admin-products-table-container">
            <?php if ($result->num_rows > 0): ?>
                <table class="admin-products-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-image"></i> Ảnh</th>
                            <th><i class="fas fa-tag"></i> Tên Sản Phẩm</th>
                            <th><i class="fas fa-folder"></i> Danh Mục</th>
                            <th><i class="fas fa-coins"></i> Giá</th>
                            <th><i class="fas fa-boxes"></i> Số lượng</th>
                            <th><i class="fas fa-cogs"></i> Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $row['id']; ?></strong></td>
                                <td>
                                    <img src="../../images/<?php echo htmlspecialchars($row['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($row['name']); ?>" 
                                         class="admin-product-image"
                                         onerror="this.src='../../img/image.png'; this.onerror=null;">
                                </td>
                                <td>
                                    <span class="admin-product-name"><?php echo htmlspecialchars($row['name']); ?></span>
                                </td>
                                <td>
                                    <span class="admin-category-badge">
                                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($row['category_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="admin-product-price"><?php echo number_format($row['price'], 0, ',', '.'); ?> VNĐ</span>
                                </td>
                                <td>
                                    <?php 
                                    $stock = $row['stock'];
                                    if ($stock > 10) {
                                        echo '<span class="admin-stock-status in-stock"><i class="fas fa-check-circle"></i> ' . $stock . '</span>';
                                    } elseif ($stock > 0) {
                                        echo '<span class="admin-stock-status low-stock"><i class="fas fa-exclamation-triangle"></i> ' . $stock . '</span>';
                                    } else {
                                        echo '<span class="admin-stock-status out-of-stock"><i class="fas fa-times-circle"></i> Hết hàng</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="admin-action-buttons">
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn-edit-product">
                                            <i class="fas fa-edit"></i> Sửa
                                        </a>
                                        <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                           class="btn-delete-product"
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa sản phẩm này?');">
                                            <i class="fas fa-trash"></i> Xóa
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="admin-empty-state">
                    <i class="fas fa-box-open"></i>
                    <p>Chưa có sản phẩm nào trong hệ thống.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</main>
<footer>
    <div class="container">
        <p>&copy; <?php echo date("Y"); ?> Admin Panel. All rights reserved.</p>
    </div>
</footer>
</body>
</html>
<?php $conn->close(); ?>