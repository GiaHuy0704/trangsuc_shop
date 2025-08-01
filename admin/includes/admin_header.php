<?php
// admin/includes/admin_header.php

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        /* CSS riêng cho admin - matching dashboard.php */
        .admin-nav {
            background-color: #f2f2f2;
            padding: 15px;
            border-bottom: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .admin-nav ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            gap: 20px;
        }
        .admin-nav ul li a {
            text-decoration: none;
            color: #333;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
        }
        .admin-nav ul li a:hover {
            background-color: #ddd;
        }

        /* Flash messages */
        .message {
            padding: 12px;
            margin: 20px 0;
            border-radius: 6px;
            font-weight: bold;
        }
        .message.success {
            background-color: #28a745;
            color: white;
        }
        .message.error {
            background-color: #dc3545;
            color: white;
        }

        @media (max-width: 768px) {
            .admin-nav ul {
                flex-direction: column; /* Xếp chồng menu admin trên mobile */
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="../dashboard.php"><h1>Admin Panel</h1></a>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="/trangsuc_shop/index.php">Trang Chủ</a></li>
                    <li><a href="../dashboard.php">Tổng Quan</a></li>
                    <li><a href="../products/index.php">Quản Lý Sản Phẩm</a></li>
                    <li><a href="../categories/index.php">Quản Lý Danh Mục</a></li>
                    <li><a href="../promotions/index.php">Quản Lý Khuyến Mãi</a></li>
                    <li><a href="../users/index.php">Quản lý người dùng</a></li>
                    <li><a href="../orders/index.php">Quản lý đơn hàng</a></li>
                    <li><a href="../../logout.php">Đăng Xuất</a></li>
                </ul>
            </nav>
        </div>
    </header>
    <main class="container">
        <?php
        if (isset($_SESSION['success_message'])) {
            echo '<p class="message success">' . htmlspecialchars($_SESSION['success_message']) . '</p>';
            unset($_SESSION['success_message']);
        }
        if (isset($_SESSION['error_message'])) {
            echo '<p class="message error">' . htmlspecialchars($_SESSION['error_message']) . '</p>';
            unset($_SESSION['error_message']);
        }
        ?>