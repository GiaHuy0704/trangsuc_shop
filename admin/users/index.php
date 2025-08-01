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

// Lấy danh sách khách hàng
$sql = "SELECT id, username, email, phone_number, address, role, created_at FROM users WHERE role = 'user' ORDER BY created_at DESC";
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

<div class="admin-users-page">
    <div class="container">
        <div class="admin-users-header">
            <h2><i class="fas fa-users"></i> Quản Lý Khách Hàng</h2>
            <div class="admin-users-stats">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $result->num_rows; ?></span>
                    <span class="stat-label">Tổng Khách Hàng</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo date('d/m'); ?></span>
                    <span class="stat-label">Hôm Nay</span>
                </div>
            </div>
        </div>

        <div class="admin-users-table-container">
            <?php if ($result->num_rows > 0): ?>
                <table class="admin-users-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-user"></i> Tên Đăng Nhập</th>
                            <th><i class="fas fa-envelope"></i> Email</th>
                            <th><i class="fas fa-phone"></i> Số Điện Thoại</th>
                            <th><i class="fas fa-map-marker-alt"></i> Địa Chỉ</th>
                            <th><i class="fas fa-user-tag"></i> Vai Trò</th>
                            <th><i class="fas fa-calendar"></i> Ngày Đăng Ký</th>
                            <th><i class="fas fa-cogs"></i> Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><strong>#<?php echo $row['id']; ?></strong></td>
                                <td>
                                    <span class="admin-user-username"><?php echo htmlspecialchars($row['username']); ?></span>
                                </td>
                                <td>
                                    <span class="admin-user-email"><?php echo htmlspecialchars($row['email']); ?></span>
                                </td>
                                <td>
                                    <span class="admin-user-phone"><?php echo htmlspecialchars($row['phone_number'] ?? 'N/A'); ?></span>
                                </td>
                                <td>
                                    <span class="admin-user-address" title="<?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?>">
                                        <?php echo htmlspecialchars($row['address'] ?? 'N/A'); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="admin-user-role"><?php echo htmlspecialchars($row['role']); ?></span>
                                </td>
                                <td>
                                    <span class="admin-user-date"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></span>
                                </td>
                                <td>
                                    <div class="admin-action-buttons">
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn-edit-user">
                                            <i class="fas fa-edit"></i> Sửa
                                        </a>
                                        <a href="delete.php?id=<?php echo $row['id']; ?>" 
                                           class="btn-delete-user"
                                           onclick="return confirm('Bạn có chắc chắn muốn xóa khách hàng này?');">
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
                    <i class="fas fa-users"></i>
                    <p>Không có khách hàng nào trong hệ thống.</p>
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