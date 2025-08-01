<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shop Trang Sức</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php"><img src="img/test/Logo.png" alt="Cartier Logo" style="height: 100px;"></a>
            </div>
            <div class="header-separator"></div>
            <nav>
                <ul>
                    <li><a href="index.php">Trang Chủ</a></li>
                    <li><a href="#">Sản Phẩm</a>
                        <ul>
                            <?php
                            // Bao gồm file kết nối DB
                            // Đảm bảo db.php không gọi session_start() nếu đã gọi ở đây
                            // Đường dẫn 'includes/db.php' là đúng từ thư mục gốc.
                            require_once 'includes/db.php';

                            // Lấy danh mục từ DB
                            $sql_categories = "SELECT * FROM categories";
                            $result_categories = $conn->query($sql_categories);

                            if ($result_categories->num_rows > 0) {
                                while($row_cat = $result_categories->fetch_assoc()) {
                                    echo '<li><a href="category.php?id=' . $row_cat['id'] . '">' . htmlspecialchars($row_cat['name']) . '</a></li>';
                                }
                            }
                            ?>
                        </ul>
                    </li>
                    <li><a href="about.php">Giới Thiệu</a></li>
                    <li><a href="contact.php">Liên Hệ</a></li>
                    <li><a href="promotions.php">Khuyến Mãi</a></li> 
                    
                    <?php
                    // Bắt đầu session ở đây nếu chưa có. Đảm bảo nó được gọi một lần và sớm nhất có thể.
                    if (session_status() == PHP_SESSION_NONE) {
                        session_start();
                    }

                    // Khởi tạo giỏ hàng nếu chưa có trong session
                    if (!isset($_SESSION['cart'])) {
                        $_SESSION['cart'] = [];
                    }

                    // Tính tổng số lượng sản phẩm trong giỏ hàng
                    $cart_item_count = 0;
                    // Nếu người dùng đã đăng nhập, lấy số lượng từ DB
                    if (isset($_SESSION['user_id'])) {
                        $stmt_cart_count = $conn->prepare("SELECT SUM(quantity) AS total_quantity FROM carts WHERE user_id = ?");
                        $stmt_cart_count->bind_param("i", $_SESSION['user_id']);
                        $stmt_cart_count->execute();
                        $result_cart_count = $stmt_cart_count->get_result();
                        $row_cart_count = $result_cart_count->fetch_assoc();
                        $cart_item_count = $row_cart_count['total_quantity'] ?? 0;
                        $stmt_cart_count->close();
                    } else {
                        // Nếu chưa đăng nhập, lấy từ session (giỏ hàng tạm)
                        foreach ($_SESSION['cart'] as $item) {
                            $cart_item_count += $item['quantity'];
                        }
                    }
                    ?>
                    <li>
                        <a href="cart.php">
                            Giỏ Hàng
                            <?php if ($cart_item_count > 0): ?>
                                <span class="cart-count">(<?php echo $cart_item_count; ?>)</span>
                            <?php endif; ?>
                        </a>
                    </li>

                    <?php
                    if (isset($_SESSION['user_id'])) {
                        // Người dùng đã đăng nhập
                        echo '<li><a href="logout.php">Đăng Xuất (' . htmlspecialchars($_SESSION['username']) . ')</a></li>';

                        // Kiểm tra vai trò người dùng để hiển thị liên kết Quản Trị và Quản Lý Khuyến Mãi
                        if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin') {
                            // LIÊN KẾT ĐẾN ADMIN PANEL
                            echo '<li><a href="admin/dashboard.php">Quản Trị</a></li>';

                        }
                    } else {
                        // Người dùng chưa đăng nhập
                        echo '<li><a href="login.php">Đăng Nhập</a></li>';
                        echo '<li><a href="register.php">Đăng Ký</a></li>';
                    }
                    ?>
                </ul>
            </nav>
            <div class="search-bar">
                <form action="index.php" method="GET">
                    <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Tìm</button>
                </form>
            </div>
        </div>
    </header>
    <main class="container">