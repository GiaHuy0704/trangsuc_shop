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

$sql = "SELECT * FROM categories ORDER BY id DESC";
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

<div class="admin-categories-page">
    <div class="container">
        <div class="admin-categories-header">
            <h2><i class="fas fa-folder"></i> Quản Lý Danh Mục</h2>
            <a href="create.php" class="btn-add-category">
                <i class="fas fa-plus"></i> Thêm Danh Mục Mới
            </a>
        </div>

        <div class="admin-categories-table-container">
            <?php if ($result->num_rows > 0): ?>
                <table class="admin-categories-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-folder"></i> Tên Danh Mục</th>
                            <th><i class="fas fa-cogs"></i> Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td class="admin-category-id"><?php echo $row['id']; ?></td>
                                <td class="admin-category-name"><?php echo htmlspecialchars($row['name']); ?></td>
                                <td class="admin-categories-action-buttons">
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn-edit-category">
                                        <i class="fas fa-edit"></i> Sửa
                                    </a>
                                    <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn-delete-category" 
                                       onclick="return confirm('Bạn có chắc chắn muốn xóa danh mục này?');">
                                        <i class="fas fa-trash"></i> Xóa
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="admin-categories-empty-state">
                    <i class="fas fa-folder-open"></i>
                    <p>Chưa có danh mục nào.</p>
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