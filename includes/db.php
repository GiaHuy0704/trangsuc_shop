<?php
$servername = "sql100.infinityfree.com"; // Hostname từ InfinityFree
$username = "if0_39609411";              // Username của MySQL
$password = "Gia07huy04"; // Copy từ InfinityFree
$dbname = "if0_39609411_ten";           // Đổi "ten" thành tên database bà tạo

// Tạo kết nối
$conn = new mysqli($servername, $username, $password, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Thiết lập bảng mã UTF-8
$conn->set_charset("utf8mb4");

// Nếu cần quản lý session người dùng
// session_start();
?>
