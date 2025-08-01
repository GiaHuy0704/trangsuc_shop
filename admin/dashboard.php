<?php
require_once '../includes/db.php';
// Bắt đầu session và kiểm tra quyền admin
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') { // Đã sửa từ 'user_role' thành 'role'
    header("Location: ../login.php"); // Chuyển hướng nếu không phải admin
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        /* CSS riêng cho admin */
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

        /* Thêm style cho phần links quản lý nội dung */
        .admin-dashboard-links h3 {
            color: #c08080; /* Màu đỏ đô */
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 1.5em;
        }

        .admin-dashboard-links ul {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex; /* Dùng flexbox để các mục nằm ngang */
            flex-wrap: wrap; /* Cho phép xuống dòng nếu không đủ chỗ */
            gap: 15px; /* Khoảng cách giữa các liên kết */
        }

        .admin-dashboard-links ul li {
            background-color: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            flex: 1 1 calc(33% - 20px); /* 3 cột, trừ gap */
            min-width: 180px; /* Đảm bảo kích thước tối thiểu */
        }

        .admin-dashboard-links ul li:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .admin-dashboard-links ul li a {
            display: block;
            padding: 20px;
            text-align: center;
            text-decoration: none;
            color: #333;
            font-weight: bold;
            font-size: 1.1em;
        }
        .admin-dashboard-links ul li a:hover {
            color: #c08080; /* Màu đỏ đô khi hover */
        }

        @media (max-width: 768px) {
            .admin-nav ul {
                flex-direction: column; /* Xếp chồng menu admin trên mobile */
                gap: 10px;
            }
            .admin-dashboard-links ul li {
                flex: 1 1 calc(50% - 15px); /* 2 cột trên màn hình nhỏ */
            }
        }

        @media (max-width: 480px) {
            .admin-dashboard-links ul li {
                flex: 1 1 100%; /* 1 cột trên màn hình rất nhỏ */
            }
        }

    </style>

</head> 
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="dashboard.php"><h1>Admin Panel</h1></a>
            </div>
            <nav class="admin-nav">
                <ul>
                    <li><a href="/trangsuc_shop/index.php">Trang Chủ</a></li>
                    <li><a href="dashboard.php">Tổng Quan</a></li>
                    <li><a href="products/index.php">Quản Lý Sản Phẩm</a></li>
                    <li><a href="categories/index.php">Quản Lý Danh Mục</a></li>
                    <li><a href="promotions/index.php">Quản Lý Khuyến Mãi</a></li>
                    <li><a href="users/index.php">Quản lý người dùng</a></li>
                    <li><a href="orders/index.php">Quản lý đơn hàng</a></li> <li><a href="../logout.php">Đăng Xuất</a></li>
                </ul>
            </nav>
        </div>
    </header> 
    <main class="container">
        <h2>Chào mừng, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
        <p>Đây là trang quản trị của bạn. Bạn có thể quản lý các sản phẩm, danh mục và khuyến mãi tại đây.</p>

        <h3>Thống kê tổng quan</h3>
        <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div style="background-color: #e0f7fa; padding: 15px; border-radius: 5px;">
                <h4>Tổng số sản phẩm</h4>
                <?php
                $stmt_prod_count = $conn->query("SELECT COUNT(*) as total_products FROM products");
                $prod_count = $stmt_prod_count->fetch_assoc();
                echo "<p style='font-size: 2em; font-weight: bold;'>".$prod_count['total_products']."</p>";
                ?>
            </div>
            <div style="background-color: #e8f5e9; padding: 15px; border-radius: 5px;">
                <h4>Tổng số danh mục</h4>
                <?php
                $stmt_cat_count = $conn->query("SELECT COUNT(*) as total_categories FROM categories");
                $cat_count = $stmt_cat_count->fetch_assoc();
                echo "<p style='font-size: 2em; font-weight: bold;'>".$cat_count['total_categories']."</p>";
                ?>
            </div>
            <div style="background-color: #fff3e0; padding: 15px; border-radius: 5px;">
                <h4>Tổng số người dùng</h4>
                <?php
                $stmt_user_count = $conn->query("SELECT COUNT(*) as total_users FROM users WHERE role = 'user'");
                $user_count = $stmt_user_count->fetch_assoc();
                echo "<p style='font-size: 2em; font-weight: bold;'>".$user_count['total_users']."</p>";
                ?>
            </div>
            </div>

        <div class="admin-dashboard-links">
            <h3>Quản lý Nội dung</h3>
            <ul>
                <li><a href="categories/index.php">Quản lý Danh mục</a></li>
                <li><a href="products/index.php">Quản lý Sản phẩm</a></li>
                <li><a href="promotions/index.php">Quản lý Khuyến mãi</a></li> <li><a href="orders/index.php">Quản lý Đơn hàng</a></li>
                <li><a href="users/index.php">Quản lý Người dùng</a></li>
            </ul>
        </div>

    </main>
    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Admin Panel. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?>