<?php
require_once '../../includes/db.php';
session_start();

// Kiểm tra quyền admin
// Đảm bảo biến session 'user_role' được sử dụng nhất quán với login.php và header.php
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') { // Đã sửa từ 'role' thành 'user_role'
    header("Location: ../../login.php");
    exit();
}

$promotion_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($promotion_id <= 0) {
    $_SESSION['error_message'] = "ID khuyến mãi không hợp lệ."; // Lưu thông báo lỗi vào session
    header("Location: index.php");
    exit();
}

try {
    $conn->begin_transaction();

    // Xóa các liên kết trong promotion_product_map (nếu bảng này tồn tại và được sử dụng)
    // Bạn cần đảm bảo bảng promotion_product_map đã được tạo trong CSDL
    $stmt_map_product = $conn->prepare("DELETE FROM promotion_product_map WHERE promotion_id = ?");
    $stmt_map_product->bind_param("i", $promotion_id);
    $stmt_map_product->execute();
    $stmt_map_product->close();

    // Xóa các liên kết trong promotion_category_map (nếu bảng này tồn tại và được sử dụng)
    // Bạn cần đảm bảo bảng promotion_category_map đã được tạo trong CSDL
    $stmt_map_category = $conn->prepare("DELETE FROM promotion_category_map WHERE promotion_id = ?");
    $stmt_map_category->bind_param("i", $promotion_id);
    $stmt_map_category->execute();
    $stmt_map_category->close();

    // Cuối cùng, xóa khuyến mãi
    $stmt_delete = $conn->prepare("DELETE FROM promotions WHERE id = ?");
    $stmt_delete->bind_param("i", $promotion_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    $conn->commit();
    $_SESSION['success_message'] = "Xóa khuyến mãi thành công!"; // Lưu thông báo thành công vào session
    header("Location: index.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error_message'] = "Lỗi khi xóa khuyến mãi: " . $e->getMessage(); // Lưu thông báo lỗi vào session
    header("Location: index.php");
    exit();
}
// Không cần đóng kết nối $conn ở đây vì nó sẽ được đóng tự động khi script kết thúc
// hoặc khi bạn gọi $conn->close() ở cuối index.php nếu muốn.
?>