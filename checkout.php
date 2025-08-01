<?php
require_once 'includes/db.php';
session_start();
// Không cần require_once 'includes/functions.php'; nếu bạn không dùng formatPhoneNumber()
include_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = [];
$sub_total = 0; // Tổng tiền sản phẩm trước khi áp dụng khuyến mãi
$discount_amount = 0; // Số tiền giảm giá từ khuyến mãi
$total_after_discount = 0; // Tổng tiền sau khi áp dụng khuyến mãi, trước VAT
$vat_rate = 0.08; // Tỷ lệ VAT 8%
$vat_amount = 0; // Số tiền VAT
$final_total_amount = 0; // Tổng tiền cuối cùng sau khi áp dụng khuyến mãi và VAT
$promotion_id = null; // ID khuyến mãi đã áp dụng
$error_message = '';
$success_message = '';

// Lấy thông tin người dùng (để điền sẵn địa chỉ và số điện thoại)
// Đảm bảo cột 'shipping_address' và 'phone_number' tồn tại trong bảng 'users'
$stmt_user_info = $conn->prepare("SELECT shipping_address, phone_number FROM users WHERE id = ?");
$stmt_user_info->bind_param("i", $user_id);
$stmt_user_info->execute();
$result_user_info = $stmt_user_info->get_result();
$user_info = $result_user_info->fetch_assoc();
$stmt_user_info->close();

// Sử dụng null coalescing operator (??) để tránh lỗi nếu các cột này không có giá trị
$user_shipping_address = $user_info['shipping_address'] ?? '';
$user_phone_number = $user_info['phone_number'] ?? '';


// Lấy thông tin giỏ hàng để hiển thị
$stmt_cart = $conn->prepare("
    SELECT c.quantity, p.id AS product_id, p.name, p.price, p.stock
    FROM carts c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$result_cart = $stmt_cart->get_result();

if ($result_cart->num_rows == 0) {
    $_SESSION['error_message'] = "Giỏ hàng của bạn đang trống. Vui lòng thêm sản phẩm trước khi thanh toán.";
    header("Location: cart.php");
    exit();
}

while ($row = $result_cart->fetch_assoc()) {
    if ($row['quantity'] > $row['stock']) {
        $_SESSION['error_message'] = "Số lượng sản phẩm '{$row['name']}' trong giỏ hàng vượt quá số lượng tồn kho. Vui lòng điều chỉnh lại.";
        header("Location: cart.php");
        exit();
    }
    $cart_items[] = $row;
    $sub_total += ($row['quantity'] * $row['price']);
}
$stmt_cart->close();

// Lấy thông tin khuyến mãi đã áp dụng từ session (đã tính toán ở cart.php)
$applied_promotion = isset($_SESSION['applied_promotion']) ? $_SESSION['applied_promotion'] : null;

// Tính toán lại tổng tiền sau khuyến mãi (nếu có)
if ($applied_promotion) {
    $promotion_id = $applied_promotion['id'];
    $discount_amount = $applied_promotion['discount_amount']; // Lấy discount_amount đã tính ở cart.php

    // Đảm bảo giảm giá không làm tổng tiền âm
    if ($discount_amount > $sub_total) {
        $discount_amount = $sub_total;
    }
}
$total_after_discount = $sub_total - $discount_amount;

// Tính VAT
$vat_amount = $total_after_discount * $vat_rate;
$final_total_amount = $total_after_discount + $vat_amount;


// Xử lý khi người dùng gửi form đặt hàng
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $order_phone_number = trim($_POST['order_phone_number'] ?? ''); // Lấy số điện thoại từ form
    $payment_method = $_POST['payment_method'] ?? '';
    $notes = trim($_POST['notes'] ?? '');

    // Nếu bạn muốn lưu chuỗi rỗng thay vì NULL vào DB nếu người dùng không nhập gì
    if (empty($order_phone_number)) {
        $order_phone_number = ''; // Giả định cột là NOT NULL hoặc bạn muốn lưu rỗng
    }

    if (empty($shipping_address)) {
        $error_message = "Vui lòng nhập địa chỉ giao hàng.";
    } elseif (empty($order_phone_number)) { // Kiểm tra nếu số điện thoại là bắt buộc
        $error_message = "Vui lòng nhập số điện thoại giao hàng.";
    } else {
        $conn->begin_transaction(); // Bắt đầu giao dịch

        try {
            // 1. Chèn vào bảng orders
            // Thêm các cột vat_amount, final_total_amount và delivery_phone_number
            // Câu lệnh INSERT hiện có 11 cột để bind: user_id, total_amount, discount_amount, vat_amount, final_total_amount, shipping_address, delivery_phone_number, payment_method, notes, promotion_id, status, order_date
            $stmt_order = $conn->prepare("INSERT INTO orders (user_id, total_amount, discount_amount, vat_amount, final_total_amount, shipping_address, delivery_phone_number, payment_method, notes, promotion_id, status, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
            
            if ($stmt_order === false) {
                throw new Exception("Lỗi chuẩn bị câu lệnh INSERT orders: " . $conn->error);
            }

            // Chuỗi bind_param:
            // i (user_id)
            // d (total_amount)
            // d (discount_amount)
            // d (vat_amount)
            // d (final_total_amount)
            // s (shipping_address)
            // s (delivery_phone_number) <-- Đã thêm
            // s (payment_method)
            // s (notes)
            // i (promotion_id)
            // Tổng cộng 10 tham số (loại 's' cho status và 'NOW()' cho order_date không cần bind)
            // Nên là: iddddssssi
            $stmt_order->bind_param("iddddssssi",
                $user_id,
                $sub_total,
                $discount_amount,
                $vat_amount,
                $final_total_amount,
                $shipping_address,
                $order_phone_number, // Biến cho cột delivery_phone_number
                $payment_method,
                $notes,
                $promotion_id
            ); 
            
            if (!$stmt_order->execute()) {
                throw new Exception("Lỗi khi tạo đơn hàng: " . $stmt_order->error);
            }
            $order_id = $stmt_order->insert_id; // Lấy ID của đơn hàng vừa tạo
            $stmt_order->close();

            // 2. Chèn vào bảng order_items và cập nhật số lượng tồn kho
            $stmt_order_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_order) VALUES (?, ?, ?, ?)");
            $stmt_update_stock = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");

            foreach ($cart_items as $item) {
                // Kiểm tra lại số lượng tồn kho một lần nữa trước khi đặt hàng
                $current_stock_check_stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
                $current_stock_check_stmt->bind_param("i", $item['product_id']);
                $current_stock_check_stmt->execute();
                $current_stock_result = $current_stock_check_stmt->get_result();
                $current_stock = $current_stock_result->fetch_assoc()['stock'];
                $current_stock_check_stmt->close();

                if ($item['quantity'] > $current_stock) {
                    throw new Exception("Sản phẩm '" . htmlspecialchars($item['name']) . "' không đủ số lượng tồn kho. Chỉ còn " . $current_stock . " sản phẩm.");
                }

                // Chèn vào order_items
                $stmt_order_item->bind_param("iiid", $order_id, $item['product_id'], $item['quantity'], $item['price']);
                if (!$stmt_order_item->execute()) {
                    throw new Exception("Lỗi khi thêm chi tiết đơn hàng cho sản phẩm " . htmlspecialchars($item['name']) . ": " . $stmt_order_item->error);
                }

                // Cập nhật số lượng tồn kho
                $stmt_update_stock->bind_param("iii", $item['quantity'], $item['product_id'], $item['quantity']);
                if (!$stmt_update_stock->execute()) {
                    throw new Exception("Lỗi khi cập nhật số lượng tồn kho cho sản phẩm " . htmlspecialchars($item['name']) . ": " . $stmt_update_stock->error);
                }
                // Kiểm tra xem có hàng nào bị ảnh hưởng không, nếu không thì có thể là stock < quantity
                if ($stmt_update_stock->affected_rows === 0) {
                    throw new Exception("Không thể cập nhật tồn kho cho sản phẩm " . htmlspecialchars($item['name']) . ". Có thể số lượng không đủ.");
                }
            }
            $stmt_order_item->close();
            $stmt_update_stock->close();

            // 3. Cập nhật usage_count trong bảng promotions và ghi vào promotion_uses (nếu có khuyến mãi)
            if ($applied_promotion) {
                // Tăng usage_count của mã khuyến mãi
                $stmt_promo_count = $conn->prepare("UPDATE promotions SET usage_count = usage_count + 1 WHERE id = ?");
                $stmt_promo_count->bind_param("i", $promotion_id);
                if (!$stmt_promo_count->execute()) {
                    throw new Exception("Lỗi khi cập nhật lượt sử dụng khuyến mãi: " . $stmt_promo_count->error);
                }
                $stmt_promo_count->close();

                // Ghi lại lịch sử sử dụng mã khuyến mãi của người dùng
                // Đảm bảo cột 'use_date' tồn tại trong bảng promotion_uses
                $stmt_promo_use = $conn->prepare("INSERT INTO promotion_uses (promotion_id, user_id, order_id, use_date) VALUES (?, ?, ?, NOW())");
                $stmt_promo_use->bind_param("iii", $promotion_id, $user_id, $order_id);
                if (!$stmt_promo_use->execute()) {
                    throw new Exception("Lỗi khi ghi lịch sử sử dụng khuyến mãi: " . $stmt_promo_use->error);
                }
                $stmt_promo_use->close();

                // Xóa khuyến mãi khỏi session sau khi đã lưu vào DB
                unset($_SESSION['applied_promotion']);
            }

            // 4. Xóa giỏ hàng của người dùng
            unset($_SESSION['cart']); // Xóa giỏ hàng PHP session (nếu bạn dùng cho người dùng chưa đăng nhập)
            $stmt_clear_cart = $conn->prepare("DELETE FROM carts WHERE user_id = ?"); // Xóa giỏ hàng trong DB
            $stmt_clear_cart->bind_param("i", $user_id);
            if (!$stmt_clear_cart->execute()) {
                throw new Exception("Lỗi khi xóa giỏ hàng: " . $stmt_clear_cart->error);
            }
            $stmt_clear_cart->close();

            $conn->commit(); // Hoàn tất giao dịch nếu tất cả các bước đều thành công
            $_SESSION['success_message'] = "Đơn hàng của bạn đã được đặt thành công! Mã đơn hàng: #" . $order_id;
            header("Location: order_success.php?order_id=" . $order_id); // Chuyển hướng đến trang xác nhận đơn hàng
            exit();

        } catch (Exception $e) {
            $conn->rollback(); // Hoàn tác giao dịch nếu có bất kỳ lỗi nào
            $error_message = "Lỗi khi đặt hàng: " . $e->getMessage();
        }
    }
}
?>

<?php include_once 'includes/header.php'; // Đảm bảo header được include ở đây để hiển thị thông báo lỗi/thành công và thanh điều hướng ?>

<div class="container">
    <h2>Thông Tin Thanh Toán</h2>

    <?php if ($error_message): ?>
        <div class="flash-message error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <h3>Các sản phẩm trong giỏ hàng:</h3>
    <table>
        <thead>
            <tr>
                <th>Sản Phẩm</th>
                <th>Giá</th>
                <th>Số Lượng</th>
                <th>Tổng Cộng</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cart_items as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo number_format($item['price'], 0, ',', '.'); ?> VNĐ</td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo number_format($item['quantity'] * $item['price'], 0, ',', '.'); ?> VNĐ</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" style="text-align: right; font-weight: bold;">Tổng tiền sản phẩm:</td>
                <td style="font-weight: bold;"><?php echo number_format($sub_total, 0, ',', '.'); ?> VNĐ</td>
            </tr>
            <?php if ($discount_amount > 0): ?>
                <tr>
                    <td colspan="3" style="text-align: right; font-weight: bold;">Giảm giá khuyến mãi:</td>
                    <td style="font-weight: bold; color: #dc3545;">-<?php echo number_format($discount_amount, 0, ',', '.'); ?> VNĐ</td>
                </tr>
            <?php endif; ?>
            <tr>
                <td colspan="3" style="text-align: right; font-weight: bold;">VAT (<?php echo ($vat_rate * 100); ?>%):</td>
                <td><?php echo number_format($vat_amount, 0, ',', '.'); ?> VNĐ</td>
            </tr>
            <tr>
                <td colspan="3" style="text-align: right; font-weight: bold; font-size: 1.2em;">Tổng số tiền phải trả:</td>
                <td style="font-weight: bold; font-size: 1.2em; color: #c08080;"><?php echo number_format($final_total_amount, 0, ',', '.'); ?> VNĐ</td>
            </tr>
        </tfoot>
    </table>

    <form action="checkout.php" method="POST" style="margin-top: 30px;">
        <h3>Địa chỉ giao hàng và phương thức thanh toán:</h3>
        <div class="form-group">
            <label for="shipping_address">Địa chỉ giao hàng:</label>
            <textarea id="shipping_address" name="shipping_address" rows="5" required><?php echo isset($_POST['shipping_address']) ? htmlspecialchars($_POST['shipping_address']) : htmlspecialchars($user_shipping_address); ?></textarea>
        </div>
        
        <div class="form-group">
            <label for="order_phone_number">Số điện thoại giao hàng:</label>
            <input type="text" id="order_phone_number" name="order_phone_number" value="<?php echo isset($_POST['order_phone_number']) ? htmlspecialchars($_POST['order_phone_number']) : htmlspecialchars($user_phone_number); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="payment_method">Phương thức thanh toán:</label>
            <select id="payment_method" name="payment_method" required>
                <option value="">-- Chọn phương thức --</option>
                <option value="COD" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'COD') ? 'selected' : ''; ?>>Thanh toán khi nhận hàng (COD)</option>
                <option value="Bank Transfer" <?php echo (isset($_POST['payment_method']) && $_POST['payment_method'] == 'Bank Transfer') ? 'selected' : ''; ?>>Chuyển khoản ngân hàng</option>
            </select>
        </div>

        <div class="form-group">
            <label for="notes">Ghi chú (Tùy chọn):</label>
            <textarea id="notes" name="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
        </div>

        <input type="submit" value="Xác nhận đặt hàng">
    </form>
</div>

<?php include_once 'includes/footer.php'; ?>

<style>
/* Thêm CSS cơ bản cho form-group nếu chưa có trong style.css của bạn */
.form-group {
    margin-bottom: 15px;
}
.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: bold;
}
.form-group input[type="text"],
.form-group textarea,
.form-group select {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
    box-sizing: border-box; /* Đảm bảo padding không làm tăng chiều rộng tổng thể */
}

input[type="submit"] {
    display: block;
    width: 100%;
    padding: 10px 15px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 18px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

input[type="submit"]:hover {
    background-color: #0056b3;
}

/* Flash message styling */
.flash-message {
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 4px;
    text-align: center;
}

.flash-message.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}
</style>