<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    // Nếu chưa đăng nhập, sử dụng session ID hoặc tạo một session giỏ hàng tạm thời
    // Để đơn giản, ở đây ta sẽ yêu cầu đăng nhập trước
    $_SESSION['error_message'] = "Vui lòng đăng nhập để thêm sản phẩm vào giỏ hàng.";
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['product_id']) && isset($_POST['quantity'])) {
    $user_id = $_SESSION['user_id'];
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    if ($product_id <= 0 || $quantity <= 0) {
        $_SESSION['error_message'] = "Dữ liệu không hợp lệ.";
        header("Location: product.php?id=" . $product_id);
        exit();
    }

    // Lấy thông tin sản phẩm để kiểm tra số lượng tồn kho
    $stmt_product = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stmt_product->bind_param("i", $product_id);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();
    $product = $result_product->fetch_assoc();
    $stmt_product->close();

    if (!$product) {
        $_SESSION['error_message'] = "Sản phẩm không tồn tại.";
        header("Location: index.php");
        exit();
    }

    if ($quantity > $product['stock']) {
        $_SESSION['error_message'] = "Số lượng sản phẩm trong kho không đủ.";
        header("Location: product.php?id=" . $product_id);
        exit();
    }

    // Kiểm tra xem sản phẩm đã có trong giỏ hàng của người dùng chưa
    $stmt_check_cart = $conn->prepare("SELECT id, quantity FROM carts WHERE user_id = ? AND product_id = ?");
    $stmt_check_cart->bind_param("ii", $user_id, $product_id);
    $stmt_check_cart->execute();
    $result_check_cart = $stmt_check_cart->get_result();

    if ($result_check_cart->num_rows > 0) {
        // Sản phẩm đã có trong giỏ, cập nhật số lượng
        $cart_item = $result_check_cart->fetch_assoc();
        $new_quantity = $cart_item['quantity'] + $quantity;

        if ($new_quantity > $product['stock']) {
             $_SESSION['error_message'] = "Tổng số lượng sản phẩm trong giỏ và bạn vừa thêm vượt quá số lượng tồn kho.";
             header("Location: product.php?id=" . $product_id);
             exit();
        }

        $stmt_update_cart = $conn->prepare("UPDATE carts SET quantity = ? WHERE id = ?");
        $stmt_update_cart->bind_param("ii", $new_quantity, $cart_item['id']);
        if ($stmt_update_cart->execute()) {
            $_SESSION['success_message'] = "Đã cập nhật số lượng sản phẩm trong giỏ hàng.";
        } else {
            $_SESSION['error_message'] = "Lỗi khi cập nhật giỏ hàng: " . $conn->error;
        }
        $stmt_update_cart->close();
    } else {
        // Sản phẩm chưa có trong giỏ, thêm mới
        $stmt_insert_cart = $conn->prepare("INSERT INTO carts (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt_insert_cart->bind_param("iii", $user_id, $product_id, $quantity);
        if ($stmt_insert_cart->execute()) {
            $_SESSION['success_message'] = "Sản phẩm đã được thêm vào giỏ hàng.";
        } else {
            $_SESSION['error_message'] = "Lỗi khi thêm vào giỏ hàng: " . $conn->error;
        }
        $stmt_insert_cart->close();
    }
    $stmt_check_cart->close();

    header("Location: product.php?id=" . $product_id);
    exit();

} else {
    $_SESSION['error_message'] = "Yêu cầu không hợp lệ.";
    header("Location: index.php");
    exit();
}
$conn->close();
?>