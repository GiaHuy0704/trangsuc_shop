<?php
require_once 'includes/db.php';
session_start();
include_once 'includes/header.php';

// Kiểm tra người dùng đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
$order_details = null;
$vat_rate = 0.08; // Lấy lại tỷ lệ VAT để hiển thị (hoặc bạn có thể lưu nó trong DB nếu muốn linh hoạt hơn)

if ($order_id > 0) {
    // Lấy tất cả các cột liên quan đến tổng tiền, địa chỉ, phương thức thanh toán, v.v.
    $stmt = $conn->prepare("SELECT id, total_amount, discount_amount, vat_amount, final_total_amount, shipping_address, payment_method, notes, status, order_date FROM orders WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $order_details = $result->fetch_assoc();
    }
    $stmt->close();
}
?>

<h2>Đặt Hàng Thành Công!</h2>

<?php if (isset($_SESSION['success_message'])): ?>
    <div class="flash-message success"><?php echo $_SESSION['success_message']; ?></div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>

<?php if ($order_details): ?>
    <p>Cảm ơn bạn đã đặt hàng! Dưới đây là thông tin chi tiết đơn hàng của bạn:</p>

    <h3>Thông tin đơn hàng:</h3>
    <ul>
        <li><strong>Mã đơn hàng:</strong> #<?php echo htmlspecialchars($order_details['id']); ?></li>
        <li><strong>Ngày đặt hàng:</strong> <?php echo date('d/m/Y H:i', strtotime($order_details['order_date'])); ?></li>
        <li><strong>Trạng thái:</strong> <?php echo htmlspecialchars($order_details['status']); ?></li>
        <li><strong>Địa chỉ giao hàng:</strong> <?php echo nl2br(htmlspecialchars($order_details['shipping_address'])); ?></li>
        <li><strong>Phương thức thanh toán:</strong> <?php echo htmlspecialchars($order_details['payment_method']); ?></li>
        <?php if (!empty($order_details['notes'])): ?>
            <li><strong>Ghi chú:</strong> <?php echo nl2br(htmlspecialchars($order_details['notes'])); ?></li>
        <?php endif; ?>
    </ul>

    <h3>Chi tiết thanh toán:</h3>
    <ul>
        <li><strong>Tổng tiền sản phẩm:</strong> <?php echo number_format($order_details['total_amount'], 0, ',', '.'); ?> VNĐ</li>
        <?php if ($order_details['discount_amount'] > 0): ?>
            <li style="color: #dc3545;"><strong>Giảm giá khuyến mãi:</strong> -<?php echo number_format($order_details['discount_amount'], 0, ',', '.'); ?> VNĐ</li>
        <?php endif; ?>
        <li><strong>VAT (<?php echo ($vat_rate * 100); ?>%):</strong> <?php echo number_format($order_details['vat_amount'], 0, ',', '.'); ?> VNĐ</li>
        <li style="font-weight: bold; font-size: 1.1em;"><strong>Tổng số tiền phải trả:</strong> <span style="color: #c08080;"><?php echo number_format($order_details['final_total_amount'], 0, ',', '.'); ?> VNĐ</span></li>
    </ul>


    <h3>Chi tiết sản phẩm:</h3>
    <table>
        <thead>
            <tr>
                <th>Sản Phẩm</th>
                <th>Số Lượng</th>
                <th>Giá tại thời điểm đặt (chưa VAT)</th> <th>Tổng (đã bao gồm VAT)</th> </tr>
        </thead>
        <tbody>
            <?php
            // Lấy chi tiết các sản phẩm trong đơn hàng
            $stmt_items = $conn->prepare("SELECT oi.quantity, oi.price_at_order, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ?");
            $stmt_items->bind_param("i", $order_id);
            $stmt_items->execute();
            $result_items = $stmt_items->get_result();
            while ($item = $result_items->fetch_assoc()):
                $item_subtotal = $item['quantity'] * $item['price_at_order']; // Tính tổng phụ của từng mặt hàng
                $item_vat_amount = $item_subtotal * $vat_rate; // Tính VAT cho từng mặt hàng
                $item_total_with_vat = $item_subtotal + $item_vat_amount; // Tính tổng đã bao gồm VAT của từng mặt hàng
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo number_format($item['price_at_order'], 0, ',', '.'); ?> VNĐ</td>
                    <td><?php echo number_format($item_total_with_vat, 0, ',', '.'); ?> VNĐ</td> </tr>
            <?php endwhile; ?>
            <?php $stmt_items->close(); ?>
        </tbody>
    </table>
    <p><a href="index.php" class="btn-primary">Tiếp tục mua sắm</a></p>

<?php else: ?>
    <p>Không tìm thấy thông tin đơn hàng hoặc bạn không có quyền truy cập.</p>
    <p><a href="index.php" class="btn-primary">Quay lại Trang Chủ</a></p>
<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>