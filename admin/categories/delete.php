<?php
require_once '../../includes/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($category_id > 0) {
    // Kiểm tra xem có sản phẩm nào thuộc danh mục này không
    $stmt_check_products = $conn->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt_check_products->bind_param("i", $category_id);
    $stmt_check_products->execute();
    $stmt_check_products->bind_result($product_count);
    $stmt_check_products->fetch();
    $stmt_check_products->close();

    if ($product_count > 0) {
        $_SESSION['error_message'] = "Không thể xóa danh mục này vì có sản phẩm đang thuộc danh mục này. Vui lòng chuyển hoặc xóa các sản phẩm trước.";
    } else {
        $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $category_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Danh mục đã được xóa thành công.";
        } else {
            $_SESSION['error_message'] = "Lỗi: " . $stmt->error;
        }
        $stmt->close();
    }
} else {
    $_SESSION['error_message'] = "ID danh mục không hợp lệ.";
}

header("Location: index.php");
exit();
?>