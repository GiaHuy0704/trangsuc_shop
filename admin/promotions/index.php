<?php
require_once '../../includes/db.php';
// Bắt đầu session và kiểm tra quyền admin.
// Lưu ý: session_start() và kiểm tra quyền cũng được thực hiện trong admin_header.php.
// Việc gọi lại ở đây là an toàn (session_start() sẽ không làm gì nếu session đã bắt đầu)
// nhưng nếu bạn muốn tối ưu, bạn có thể dựa hoàn toàn vào admin_header.php.
// Tuy nhiên, để đảm bảo an toàn, giữ lại kiểm tra quyền ở đây cũng không sao.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$sql = "SELECT * FROM promotions ORDER BY id DESC";
$result = $conn->query($sql);

// Nhúng header riêng cho Admin Panel.
// File admin_header.php sẽ chứa các thẻ <head>, <title>, <link>, <style>, <body>, <header>, <main class="container">
// và logic hiển thị flash messages.
require_once '../includes/admin_header.php';
?>

<div class="admin-promotions-page">
    <div class="container">
        <div class="admin-promotions-header">
            <h2><i class="fas fa-gift"></i> Quản Lý Khuyến Mãi</h2>
            <a href="create.php" class="btn-add-promotion">
                <i class="fas fa-plus"></i> Thêm Khuyến Mãi Mới
            </a>
        </div>

        <div class="admin-promotions-table-container">
            <?php if ($result->num_rows > 0): ?>
                <table class="admin-promotions-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID</th>
                            <th><i class="fas fa-tag"></i> Mã Khuyến Mãi</th>
                            <th><i class="fas fa-gift"></i> Loại</th>
                            <th><i class="fas fa-coins"></i> Giá Trị</th>
                            <th><i class="fas fa-shopping-cart"></i> Tối Thiểu</th>
                            <th><i class="fas fa-calendar"></i> Bắt Đầu</th>
                            <th><i class="fas fa-calendar"></i> Kết Thúc</th>
                            <th><i class="fas fa-users"></i> Giới Hạn</th>
                            <th><i class="fas fa-check-circle"></i> Đã Dùng</th>
                            <th><i class="fas fa-toggle-on"></i> Trạng Thái</th>
                            <th><i class="fas fa-cogs"></i> Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td class="admin-promotion-id"><?php echo htmlspecialchars($row['id']); ?></td>
                            <td class="admin-promotion-code"><?php echo htmlspecialchars($row['code']); ?></td>
                            <td class="admin-promotion-type <?php echo htmlspecialchars($row['type']); ?>">
                                <?php 
                                $type_labels = [
                                    'percentage' => 'Phần trăm',
                                    'fixed_amount' => 'Số tiền cố định',
                                    'free_shipping' => 'Miễn phí vận chuyển'
                                ];
                                echo $type_labels[$row['type']] ?? htmlspecialchars($row['type']); 
                                ?>
                            </td>
                            <td class="admin-promotion-value"><?php echo htmlspecialchars($row['value']); ?></td>
                            <td><?php echo number_format($row['min_amount'], 0, ',', '.'); ?> VNĐ</td>
                            <td class="admin-promotion-date"><?php echo date('d/m/Y H:i', strtotime($row['start_date'])); ?></td>
                            <td class="admin-promotion-date"><?php echo date('d/m/Y H:i', strtotime($row['end_date'])); ?></td>
                            <td><?php echo $row['usage_limit'] ?? 'Không giới hạn'; ?></td>
                            <td><?php echo htmlspecialchars($row['used_count']); ?></td>
                            <td class="admin-promotion-status <?php echo $row['is_active'] ? 'active' : 'inactive'; ?>">
                                <?php echo $row['is_active'] ? 'Hoạt động' : 'Không hoạt động'; ?>
                            </td>
                            <td class="admin-promotions-action-buttons">
                                <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn-edit-promotion">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>
                                <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn-delete-promotion" 
                                   onclick="return confirm('Bạn có chắc chắn muốn xóa khuyến mãi này không?');">
                                    <i class="fas fa-trash"></i> Xóa
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="admin-promotions-empty-state">
                    <i class="fas fa-gift"></i>
                    <p>Chưa có khuyến mãi nào.</p>
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