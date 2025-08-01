<?php
require_once '../../includes/db.php'; // Điều chỉnh đường dẫn đến db.php
session_start();

// Kiểm tra quyền admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $order_id = isset($_POST['order_id']) ? (int)$_POST['order_id'] : 0;
    $new_status = isset($_POST['new_status']) ? $_POST['new_status'] : '';

    if ($order_id > 0 && !empty($new_status)) {
        // Cập nhật trạng thái đơn hàng
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Trạng thái đơn hàng #{$order_id} đã được cập nhật thành công!";
        } else {
            $_SESSION['error_message'] = "Lỗi khi cập nhật trạng thái đơn hàng: " . $conn->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Dữ liệu không hợp lệ để cập nhật trạng thái đơn hàng.";
    }
} else {
    $_SESSION['error_message'] = "Yêu cầu không hợp lệ.";
}

// Chuyển hướng về trang danh sách đơn hàng
header("Location: index.php");
exit();
?>