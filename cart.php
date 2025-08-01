<?php
require_once 'includes/db.php';
session_start();
include_once 'includes/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$cart_items = [];
$sub_total = 0; // Tổng tiền sản phẩm trước VAT và khuyến mãi
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['error_message']);
unset($_SESSION['success_message']);

// Lấy các sản phẩm trong giỏ hàng của người dùng (kèm category_id để kiểm tra khuyến mãi theo danh mục)
$stmt_cart = $conn->prepare("
    SELECT c.id AS cart_item_id, c.quantity, p.id AS product_id, p.name, p.price, p.image, p.stock, p.category_id
    FROM carts c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt_cart->bind_param("i", $user_id);
$stmt_cart->execute();
$result_cart = $stmt_cart->get_result();

while ($row = $result_cart->fetch_assoc()) {
    $cart_items[] = $row;
    $sub_total += ($row['quantity'] * $row['price']);
}
$stmt_cart->close();


// --- BẮT ĐẦU LOGIC XỬ LÝ KHUYẾN MÃI ---

// Xử lý khi người dùng áp dụng mã khuyến mãi
if (isset($_POST['apply_coupon'])) {
    $coupon_code = trim($_POST['coupon_code']);
    $message = ''; // Thông báo cho người dùng
    $is_coupon_applied = false; // Biến cờ để kiểm tra thành công

    if (!empty($coupon_code)) {
        // Lấy thông tin khuyến mãi từ DB
        $stmt = $conn->prepare("SELECT * FROM promotions WHERE code = ? AND is_active = TRUE AND start_date <= NOW() AND end_date >= NOW()");
        $stmt->bind_param("s", $coupon_code);
        $stmt->execute();
        $result = $stmt->get_result();
        $promotion = $result->fetch_assoc();
        $stmt->close();

        if ($promotion) {
            // Kiểm tra giới hạn sử dụng toàn cục
            if ($promotion['usage_limit'] !== null && $promotion['usage_count'] >= $promotion['usage_limit']) {
                $message = "Mã khuyến mãi đã hết lượt sử dụng.";
            } else {
                // Kiểm tra người dùng đã sử dụng mã này chưa (nếu giới hạn 1 lần/user)
                // Giả định bạn muốn mỗi người dùng chỉ dùng 1 lần cho mỗi mã
                // Nếu muốn giới hạn số lần cho từng người dùng, bạn sẽ cần thêm cột usage_per_user_limit vào bảng promotions
                $stmt_user_promo_use = $conn->prepare("SELECT COUNT(*) AS use_count FROM promotion_uses WHERE promotion_id = ? AND user_id = ?");
                $stmt_user_promo_use->bind_param("ii", $promotion['id'], $user_id);
                $stmt_user_promo_use->execute();
                $user_promo_use_result = $stmt_user_promo_use->get_result();
                $user_promo_use_count = $user_promo_use_result->fetch_assoc()['use_count'];
                $stmt_user_promo_use->close();

                // Điều kiện giả định: mỗi user chỉ được dùng 1 lần cho mỗi mã
                if ($user_promo_use_count > 0) {
                    $message = "Bạn đã sử dụng mã khuyến mãi này rồi.";
                } else {
                    // Kiểm tra đơn hàng tối thiểu
                    // Lưu ý: min_amount có thể được lưu trữ là min_order_amount trong DB
                    $min_order_amount_check = $promotion['min_amount'] ?? $promotion['min_order_amount'] ?? 0;
                    if ($sub_total < $min_order_amount_check) {
                        $message = "Đơn hàng của bạn phải đạt " . number_format($min_order_amount_check, 0, ',', '.') . " VNĐ để áp dụng mã này.";
                    } else {
                        // Kiểm tra nếu khuyến mãi áp dụng cho sản phẩm/danh mục cụ thể
                        $is_applicable = true;
                        if ($promotion['applies_to'] === 'specific_products') {
                            $is_applicable = false; // Mặc định là không áp dụng cho đến khi tìm thấy sản phẩm hợp lệ
                            $stmt_map = $conn->prepare("SELECT product_id FROM promotion_product_map WHERE promotion_id = ?");
                            $stmt_map->bind_param("i", $promotion['id']);
                            $stmt_map->execute();
                            $result_map = $stmt_map->get_result();
                            $applicable_product_ids = [];
                            while ($row = $result_map->fetch_assoc()) {
                                $applicable_product_ids[] = $row['product_id'];
                            }
                            $stmt_map->close();

                            foreach ($cart_items as $item) {
                                if (in_array($item['product_id'], $applicable_product_ids)) {
                                    $is_applicable = true; // Có ít nhất 1 sản phẩm trong giỏ hàng áp dụng được
                                    break;
                                }
                            }
                            if (!$is_applicable) {
                                $message = "Mã khuyến mãi này chỉ áp dụng cho một số sản phẩm cụ thể không có trong giỏ hàng của bạn.";
                            }
                        } elseif ($promotion['applies_to'] === 'specific_categories') {
                            $is_applicable = false; // Mặc định là không áp dụng cho đến khi tìm thấy danh mục hợp lệ
                            $stmt_map = $conn->prepare("SELECT category_id FROM promotion_category_map WHERE promotion_id = ?");
                            $stmt_map->bind_param("i", $promotion['id']);
                            $stmt_map->execute();
                            $result_map = $stmt_map->get_result();
                            $applicable_category_ids = [];
                            while ($row = $result_map->fetch_assoc()) {
                                $applicable_category_ids[] = $row['category_id'];
                            }
                            $stmt_map->close();

                            foreach ($cart_items as $item) {
                                if (in_array($item['category_id'], $applicable_category_ids)) {
                                    $is_applicable = true; // Có ít nhất 1 sản phẩm trong giỏ hàng thuộc danh mục áp dụng được
                                    break;
                                }
                            }
                            if (!$is_applicable) {
                                $message = "Mã khuyến mãi này chỉ áp dụng cho một số danh mục sản phẩm không có trong giỏ hàng của bạn.";
                            }
                        }

                        if ($is_applicable) {
                            // Mã hợp lệ, lưu vào session
                            $_SESSION['applied_promotion'] = [
                                'id' => $promotion['id'],
                                'code' => $promotion['code'],
                                'type' => $promotion['type'],
                                'value' => (float)$promotion['value'], // Đảm bảo là số float
                                'name' => $promotion['name'],
                                'discount_amount' => 0, // Sẽ tính toán chi tiết sau
                                'min_order_amount' => $min_order_amount_check // Lưu lại để kiểm tra nếu giỏ hàng thay đổi
                            ];
                            $message = "Mã khuyến mãi '" . htmlspecialchars($promotion['name']) . "' đã được áp dụng!";
                            $is_coupon_applied = true;
                        }
                    }
                }
            }
        } else {
            $message = "Mã khuyến mãi không hợp lệ hoặc đã hết hạn.";
        }
    } else {
        $message = "Vui lòng nhập mã khuyến mãi.";
    }
    // Lưu thông báo vào session để hiển thị sau khi redirect
    $_SESSION[($is_coupon_applied) ? 'success_message' : 'error_message'] = $message;
    header('Location: cart.php'); // Redirect để tránh gửi lại form
    exit();
}

// Xử lý khi người dùng bỏ áp dụng mã khuyến mãi
if (isset($_GET['remove_coupon']) && isset($_SESSION['applied_promotion'])) {
    unset($_SESSION['applied_promotion']);
    $_SESSION['success_message'] = 'Mã khuyến mãi đã được gỡ bỏ.';
    header('Location: cart.php');
    exit();
}

// Tính toán giảm giá và tổng tiền cuối cùng
$discount_amount = 0;
$total_after_discount = $sub_total; // Tổng tiền sau giảm giá (trước VAT)

if (isset($_SESSION['applied_promotion'])) {
    $promo = $_SESSION['applied_promotion'];

    // Cần kiểm tra lại điều kiện áp dụng nếu giỏ hàng thay đổi sau khi áp dụng mã
    // hoặc chỉ dựa vào sub_total hiện tại. Để đơn giản, ta chỉ dựa vào sub_total.
    if ($sub_total < ($promo['min_order_amount'] ?? 0)) { // Kiểm tra min_order_amount nếu có
        unset($_SESSION['applied_promotion']); // Gỡ bỏ nếu không còn đủ điều kiện
        $_SESSION['error_message'] = "Mã khuyến mãi đã gỡ bỏ vì đơn hàng không còn đủ điều kiện tối thiểu.";
        header('Location: cart.php');
        exit();
    }

    if ($promo['type'] === 'percentage') {
        $discount_amount = $sub_total * ($promo['value'] / 100);
    } elseif ($promo['type'] === 'fixed_amount') {
        $discount_amount = $promo['value'];
    } elseif ($promo['type'] === 'free_shipping') {
        // Miễn phí vận chuyển: giả sử phí vận chuyển là một hằng số
        // Bạn cần quản lý phí vận chuyển riêng biệt trong hệ thống của mình
        // Ví dụ: $shipping_cost = 30000;
        // $discount_amount = $shipping_cost;
        // Hiện tại, chúng ta chỉ giảm giá trên sản phẩm, miễn phí vận chuyển sẽ được xử lý ở bước thanh toán
        $discount_amount = 0; // Đặt về 0 nếu chỉ có free_shipping và không ảnh hưởng đến tổng tiền sản phẩm
        // Lưu thông tin free shipping để truyền qua checkout
        $_SESSION['applied_promotion']['is_free_shipping'] = true;
    }

    // Đảm bảo giảm giá không làm tổng tiền âm
    if ($discount_amount > $sub_total) {
        $discount_amount = $sub_total;
    }
    $total_after_discount = $sub_total - $discount_amount;
    $_SESSION['applied_promotion']['discount_amount'] = $discount_amount; // Cập nhật số tiền giảm thực tế vào session
}

// --- KẾT THÚC LOGIC XỬ LÝ KHUYẾN MÃI ---


// --- BẮT ĐẦU LOGIC TÍNH THUẾ VAT ---
$vat_rate = 0.08; // 8% VAT
$vat_amount = $total_after_discount * $vat_rate;
$final_total_amount = $total_after_discount + $vat_amount;
// --- KẾT THÚC LOGIC TÍNH THUẾ VAT ---


// Xử lý cập nhật số lượng hoặc xóa sản phẩm khỏi giỏ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (!isset($_POST['apply_coupon']))) { // Đảm bảo không xử lý lại khi là apply_coupon
    if (isset($_POST['update_cart'])) {
        $cart_item_id_to_update = (int)$_POST['cart_item_id'];
        $new_quantity = (int)$_POST['new_quantity'];
        $product_id_for_update = (int)$_POST['product_id'];

        if ($cart_item_id_to_update > 0 && $new_quantity > 0 && $product_id_for_update > 0) {
            // Lấy số lượng tồn kho của sản phẩm
            $stmt_stock = $conn->prepare("SELECT stock FROM products WHERE id = ?");
            $stmt_stock->bind_param("i", $product_id_for_update);
            $stmt_stock->execute();
            $result_stock = $stmt_stock->get_result();
            $product_stock = $result_stock->fetch_assoc()['stock'];
            $stmt_stock->close();

            if ($new_quantity > $product_stock) {
                $_SESSION['error_message'] = "Số lượng cập nhật vượt quá số lượng tồn kho của sản phẩm.";
            } else {
                $stmt_update = $conn->prepare("UPDATE carts SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt_update->bind_param("iii", $new_quantity, $cart_item_id_to_update, $user_id);
                if ($stmt_update->execute()) {
                    $_SESSION['success_message'] = "Giỏ hàng đã được cập nhật.";
                } else {
                    $_SESSION['error_message'] = "Lỗi khi cập nhật giỏ hàng: " . $conn->error;
                }
                $stmt_update->close();
            }
        } else {
            $_SESSION['error_message'] = "Dữ liệu cập nhật không hợp lệ.";
        }
    } elseif (isset($_POST['remove_item'])) {
        $cart_item_id_to_remove = (int)$_POST['cart_item_id'];
        if ($cart_item_id_to_remove > 0) {
            $stmt_remove = $conn->prepare("DELETE FROM carts WHERE id = ? AND user_id = ?");
            $stmt_remove->bind_param("ii", $cart_item_id_to_remove, $user_id);
            if ($stmt_remove->execute()) {
                $_SESSION['success_message'] = "Sản phẩm đã được xóa khỏi giỏ hàng.";
            } else {
                $_SESSION['error_message'] = "Lỗi khi xóa sản phẩm khỏi giỏ hàng: " . $conn->error;
            }
            $stmt_remove->close();
        } else {
            $_SESSION['error_message'] = "Dữ liệu xóa không hợp lệ.";
        }
    }
    // Chỉ redirect nếu có cập nhật/xóa, không phải apply_coupon vì nó đã redirect bên trên
    header("Location: cart.php");
    exit();
}
?>

<div class="cart-page">
    <div class="container">
        <h2><i class="fas fa-shopping-cart"></i> Giỏ Hàng Của Bạn</h2>

        <?php if ($error_message): ?>
            <div class="flash-message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        <?php if ($success_message): ?>
            <div class="flash-message success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cart_items)): ?>
            <div class="cart-empty">
                <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #8b5412; margin-bottom: 1rem;"></i>
                <p>Giỏ hàng của bạn đang trống.</p>
                <a href="index.php">
                    <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                </a>
            </div>
        <?php else: ?>
            <div class="cart-table-container">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Sản Phẩm</th>
                            <th>Ảnh</th>
                            <th>Giá</th>
                            <th>Số Lượng</th>
                            <th>Tổng Cộng</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td>
                                    <img src="images/<?php echo htmlspecialchars($item['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="cart-product-image"
                                         onerror="this.src='img/image.png'; this.onerror=null;">
                                </td>
                                <td class="cart-price"><?php echo number_format($item['price'], 0, ',', '.'); ?> VNĐ</td>
                                <td>
                                    <form action="cart.php" method="POST" class="quantity-controls">
                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                        <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                        <input type="number" name="new_quantity" value="<?php echo $item['quantity']; ?>" 
                                               min="1" max="<?php echo $item['stock']; ?>" class="quantity-input">
                                        <button type="submit" name="update_cart" class="btn-update">
                                            <i class="fas fa-sync-alt"></i> Cập nhật
                                        </button>
                                    </form>
                                </td>
                                <td class="cart-price"><?php echo number_format($item['quantity'] * $item['price'], 0, ',', '.'); ?> VNĐ</td>
                                <td>
                                    <form action="cart.php" method="POST" style="display: inline-block;">
                                        <input type="hidden" name="cart_item_id" value="<?php echo $item['cart_item_id']; ?>">
                                        <button type="submit" name="remove_item" class="btn-remove" 
                                                onclick="return confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?');">
                                            <i class="fas fa-trash"></i> Xóa
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Cart Summary -->
            <div class="cart-summary">
                <div class="cart-summary-row">
                    <span class="cart-summary-label">Tổng tiền sản phẩm:</span>
                    <span class="cart-summary-value"><?php echo number_format($sub_total, 0, ',', '.'); ?> VNĐ</span>
                </div>
                <?php if (isset($_SESSION['applied_promotion']) && $_SESSION['applied_promotion']['discount_amount'] > 0): ?>
                    <div class="cart-summary-row">
                        <span class="cart-summary-label">Giảm giá khuyến mãi (<?php echo htmlspecialchars($_SESSION['applied_promotion']['name']); ?>):</span>
                        <span class="cart-summary-value cart-summary-discount">-<?php echo number_format($_SESSION['applied_promotion']['discount_amount'], 0, ',', '.'); ?> VNĐ</span>
                    </div>
                <?php endif; ?>
                <div class="cart-summary-row">
                    <span class="cart-summary-label">VAT (<?php echo ($vat_rate * 100); ?>%):</span>
                    <span class="cart-summary-value"><?php echo number_format($vat_amount, 0, ',', '.'); ?> VNĐ</span>
                </div>
                <div class="cart-summary-row">
                    <span class="cart-summary-label">Tổng thanh toán:</span>
                    <span class="cart-summary-value cart-summary-total"><?php echo number_format($final_total_amount, 0, ',', '.'); ?> VNĐ</span>
                </div>
            </div>

            <!-- Coupon Section -->
            <div class="coupon-section">
                <h3><i class="fas fa-tag"></i> Mã Khuyến Mãi</h3>
                <?php if (isset($_SESSION['applied_promotion'])): ?>
                    <div class="applied-coupon">
                        <p>
                            <i class="fas fa-check-circle"></i> 
                            Mã khuyến mãi đã áp dụng: <strong><?php echo htmlspecialchars($_SESSION['applied_promotion']['name']); ?></strong>
                        </p>
                    </div>
                    <div style="text-align: center;">
                        <a href="cart.php?remove_coupon=true" class="btn-remove-coupon">
                            <i class="fas fa-times"></i> Gỡ bỏ mã
                        </a>
                    </div>
                <?php else: ?>
                    <form action="cart.php" method="post" class="coupon-form">
                        <input type="text" name="coupon_code" placeholder="Nhập mã khuyến mãi" class="coupon-input">
                        <button type="submit" name="apply_coupon" class="btn-apply-coupon">
                            <i class="fas fa-check"></i> Áp dụng
                        </button>
                    </form>
                <?php endif; ?>
            </div>

            <!-- Checkout Section -->
            <div class="checkout-section">
                <a href="checkout.php" class="btn-checkout">
                    <i class="fas fa-credit-card"></i> Tiến hành đặt hàng
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>