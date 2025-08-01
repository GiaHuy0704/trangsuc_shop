<?php
require_once '../../includes/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id > 0) {
    // Lấy tên ảnh để xóa file ảnh vật lý
    $stmt_get_image = $conn->prepare("SELECT image FROM products WHERE id = ?");
    $stmt_get_image->bind_param("i", $product_id);
    $stmt_get_image->execute();
    $result_image = $stmt_get_image->get_result();
    $image_to_delete = '';
    if ($result_image->num_rows > 0) {
        $row_image = $result_image->fetch_assoc();
        $image_to_delete = $row_image['image'];
    }
    $stmt_get_image->close();

    // Xóa sản phẩm và các đánh giá liên quan (nếu có)
    $conn->begin_transaction(); // Bắt đầu giao dịch

    try {
        // Xóa đánh giá của sản phẩm
        $stmt_delete_reviews = $conn->prepare("DELETE FROM reviews WHERE product_id = ?");
        $stmt_delete_reviews->bind_param("i", $product_id);
        $stmt_delete_reviews->execute();
        $stmt_delete_reviews->close();

        // Xóa sản phẩm
        $stmt_delete_product = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt_delete_product->bind_param("i", $product_id);

        if ($stmt_delete_product->execute()) {
            // Xóa file ảnh vật lý sau khi xóa thành công trong DB
            if (!empty($image_to_delete) && file_exists('../../images/' . $image_to_delete)) {
                unlink('../../images/' . $image_to_delete);
            }
            $_SESSION['success_message'] = "Sản phẩm và các đánh giá liên quan đã được xóa thành công.";
            $conn->commit(); // Hoàn tất giao dịch
        } else {
            throw new Exception("Lỗi khi xóa sản phẩm: " . $stmt_delete_product->error);
        }
        $stmt_delete_product->close();
    } catch (Exception $e) {
        $conn->rollback(); // Hoàn tác giao dịch nếu có lỗi
        $_SESSION['error_message'] = $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "ID sản phẩm không hợp lệ.";
}

header("Location: index.php");
exit();
?>