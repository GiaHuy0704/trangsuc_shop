<?php
require_once '../../includes/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

// Nhúng header riêng cho Admin Panel
require_once '../includes/admin_header.php';

// Lấy thống kê đơn hàng
$stats_sql = "SELECT 
    COUNT(*) as total_orders,
    SUM(CASE WHEN status = 'Chờ xử lý' THEN 1 ELSE 0 END) as pending_orders,
    SUM(CASE WHEN status = 'Đang giao' THEN 1 ELSE 0 END) as shipping_orders,
    SUM(CASE WHEN status = 'Đã giao' THEN 1 ELSE 0 END) as completed_orders,
    SUM(final_total_amount) as total_revenue
FROM orders";
$stats_result = $conn->query($stats_sql);
$stats = $stats_result->fetch_assoc();

// Lấy danh sách đơn hàng
$sql = "SELECT id, user_id, total_amount, final_total_amount, shipping_address, delivery_phone_number, payment_method, status, order_date FROM orders ORDER BY order_date DESC";
$result = $conn->query($sql);
?>

<div class="admin-orders-page">
    <div class="container">
        <div class="admin-orders-header">
            <h2><i class="fas fa-shopping-cart"></i> Quản Lý Đơn Hàng</h2>
            <div class="admin-orders-stats">
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['total_orders']; ?></span>
                    <span class="stat-label">Tổng Đơn Hàng</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['pending_orders']; ?></span>
                    <span class="stat-label">Chờ Xử Lý</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo $stats['shipping_orders']; ?></span>
                    <span class="stat-label">Đang Giao</span>
                </div>
                <div class="stat-card">
                    <span class="stat-number"><?php echo number_format($stats['total_revenue'], 0, ',', '.'); ?> VNĐ</span>
                    <span class="stat-label">Tổng Doanh Thu</span>
                </div>
            </div>
        </div>

        <div class="admin-orders-table-container">
            <?php if ($result->num_rows > 0): ?>
                <table class="admin-orders-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-hashtag"></i> ID Đơn Hàng</th>
                            <th><i class="fas fa-user"></i> Người Đặt</th>
                            <th><i class="fas fa-coins"></i> Tổng Tiền</th>
                            <th><i class="fas fa-money-bill-wave"></i> Thành Tiền</th>
                            <th><i class="fas fa-map-marker-alt"></i> Địa Chỉ Giao</th>
                            <th><i class="fas fa-phone"></i> SĐT Giao Hàng</th>
                            <th><i class="fas fa-credit-card"></i> Phương Thức TT</th>
                            <th><i class="fas fa-info-circle"></i> Trạng Thái</th>
                            <th><i class="fas fa-calendar"></i> Ngày Đặt</th>
                            <th><i class="fas fa-cogs"></i> Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <?php
                            // Lấy tên người dùng
                            $user_name = "N/A";
                            $stmt_user = $conn->prepare("SELECT username FROM users WHERE id = ?");
                            $stmt_user->bind_param("i", $row['user_id']);
                            $stmt_user->execute();
                            $user_result = $stmt_user->get_result();
                            if ($user_result->num_rows > 0) {
                                $user_name = $user_result->fetch_assoc()['username'];
                            }
                            $stmt_user->close();
                            ?>
                            <tr>
                                <td><strong>#<?php echo $row['id']; ?></strong></td>
                                <td>
                                    <span class="admin-order-customer"><?php echo htmlspecialchars($user_name); ?></span>
                                </td>
                                <td>
                                    <span class="admin-order-amount"><?php echo number_format($row['total_amount'], 0, ',', '.'); ?> VNĐ</span>
                                </td>
                                <td>
                                    <span class="admin-order-final-amount"><?php echo number_format($row['final_total_amount'], 0, ',', '.'); ?> VNĐ</span>
                                </td>
                                <td>
                                    <span class="admin-order-address" title="<?php echo htmlspecialchars($row['shipping_address']); ?>">
                                        <?php echo htmlspecialchars($row['shipping_address']); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="admin-order-phone"><?php echo htmlspecialchars($row['delivery_phone_number']); ?></span>
                                </td>
                                <td>
                                    <span class="admin-order-payment"><?php echo htmlspecialchars($row['payment_method']); ?></span>
                                </td>
                                <td>
                                    <?php
                                    $status = $row['status'];
                                    $status_class = '';
                                    switch($status) {
                                        case 'Chờ xử lý':
                                            $status_class = 'pending';
                                            break;
                                        case 'Đang xử lý':
                                            $status_class = 'processing';
                                            break;
                                        case 'Đang giao':
                                            $status_class = 'shipping';
                                            break;
                                        case 'Đã giao':
                                            $status_class = 'completed';
                                            break;
                                        case 'Đã hủy':
                                            $status_class = 'cancelled';
                                            break;
                                    }
                                    ?>
                                    <span class="admin-order-status <?php echo $status_class; ?>">
                                        <i class="fas fa-circle"></i> <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="admin-order-date"><?php echo date('d/m/Y H:i', strtotime($row['order_date'])); ?></span>
                                </td>
                                <td>
                                    <div class="admin-action-buttons">
                                        <button class="btn-update-status" onclick="showUpdateForm(<?php echo $row['id']; ?>, '<?php echo $row['status']; ?>')">
                                            <i class="fas fa-edit"></i> Cập Nhật
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="admin-empty-state">
                    <i class="fas fa-shopping-cart"></i>
                    <p>Không có đơn hàng nào trong hệ thống.</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Modal for updating order status -->
        <div id="updateStatusModal" class="admin-modal">
            <div class="admin-modal-content">
                <div class="admin-modal-header">
                    <h3><i class="fas fa-edit"></i> Cập Nhật Trạng Thái Đơn Hàng</h3>
                    <span class="admin-modal-close" onclick="hideUpdateForm()">&times;</span>
                </div>
                <form action="update_status.php" method="post" class="admin-modal-form">
                    <input type="hidden" id="orderIdInput" name="order_id">
                    <div class="admin-form-group">
                        <label for="newStatus">Trạng thái mới:</label>
                        <select id="newStatus" name="new_status" class="admin-form-select" required>
                            <option value="Chờ xử lý">Chờ xử lý</option>
                            <option value="Đang xử lý">Đang xử lý</option>
                            <option value="Đang giao">Đang giao</option>
                            <option value="Đã giao">Đã giao</option>
                            <option value="Đã hủy">Đã hủy</option>
                        </select>
                    </div>
                    <div class="admin-modal-buttons">
                        <button type="submit" class="btn-update-order">
                            <i class="fas fa-save"></i> Cập Nhật
                        </button>
                        <button type="button" class="btn-cancel-order" onclick="hideUpdateForm()">
                            <i class="fas fa-times"></i> Hủy
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function showUpdateForm(orderId, currentStatus) {
    document.getElementById('orderIdInput').value = orderId;
    document.getElementById('newStatus').value = currentStatus;
    document.getElementById('updateStatusModal').style.display = 'flex';
}

function hideUpdateForm() {
    document.getElementById('updateStatusModal').style.display = 'none';
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('updateStatusModal');
    if (event.target == modal) {
        modal.style.display = 'none';
    }
}
</script>

</main>
<footer>
    <div class="container">
        <p>&copy; <?php echo date("Y"); ?> Admin Panel. All rights reserved.</p>
    </div>
</footer>
</body>
</html>
<?php $conn->close(); ?>