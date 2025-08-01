<?php
require_once '../../includes/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($user_id > 0) {
    // Kiểm tra xem người dùng có phải là admin đang cố xóa chính mình không
    if ($user_id == $_SESSION['user_id']) {
        $_SESSION['error_message'] = "Bạn không thể xóa tài khoản admin của chính mình.";
        header("Location: index.php");
        exit();
    }

    // Xóa khách hàng và các dữ liệu liên quan (nếu có)
    $conn->begin_transaction(); // Bắt đầu giao dịch

    try {
        // Xóa các đơn hàng của khách hàng (nếu có bảng orders)
        $stmt_delete_orders = $conn->prepare("DELETE FROM orders WHERE user_id = ?");
        $stmt_delete_orders->bind_param("i", $user_id);
        $stmt_delete_orders->execute();
        $stmt_delete_orders->close();

        // Xóa các đánh giá của khách hàng (nếu có bảng reviews)
        $stmt_delete_reviews = $conn->prepare("DELETE FROM reviews WHERE user_id = ?");
        $stmt_delete_reviews->bind_param("i", $user_id);
        $stmt_delete_reviews->execute();
        $stmt_delete_reviews->close();

        // Xóa khách hàng
        $stmt_delete_user = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'user'");
        $stmt_delete_user->bind_param("i", $user_id);

        if ($stmt_delete_user->execute()) {
            if ($stmt_delete_user->affected_rows > 0) {
                $_SESSION['success_message'] = "Khách hàng và các dữ liệu liên quan đã được xóa thành công.";
                $conn->commit(); // Hoàn tất giao dịch
            } else {
                $_SESSION['error_message'] = "Không tìm thấy khách hàng hoặc không có quyền xóa.";
            }
        } else {
            throw new Exception("Lỗi khi xóa khách hàng: " . $stmt_delete_user->error);
        }
        $stmt_delete_user->close();
    } catch (Exception $e) {
        $conn->rollback(); // Hoàn tác giao dịch nếu có lỗi
        $_SESSION['error_message'] = $e->getMessage();
    }
} else {
    $_SESSION['error_message'] = "ID khách hàng không hợp lệ.";
}

header("Location: index.php");
exit();
?>