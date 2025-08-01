<?php
require_once '../../includes/db.php';
// Bắt đầu session và kiểm tra quyền admin
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$promotion = null;
$message = '';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM promotions WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows == 1) {
        $promotion = $result->fetch_assoc();
    } else {
        header("Location: index.php");
        exit();
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $promotion) {
    $id = $_POST['id'];
    $code = $_POST['code'];
    $type = $_POST['type'];
    $value = $_POST['value'];
    $min_amount = $_POST['min_amount'] ?? 0;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $usage_limit = $_POST['usage_limit'] === '' ? NULL : (int)$_POST['usage_limit'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    // Basic validation
    if (empty($code) || empty($type) || empty($value) || empty($start_date) || empty($end_date)) {
        $message = "Vui lòng điền đầy đủ các trường bắt buộc.";
    } else {
        // Check if code already exists for other promotions
        $stmt_check = $conn->prepare("SELECT id FROM promotions WHERE code = ? AND id != ?");
        $stmt_check->bind_param("si", $code, $id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = "Mã khuyến mãi đã tồn tại cho khuyến mãi khác. Vui lòng chọn mã khác.";
        } else {
            $stmt = $conn->prepare("UPDATE promotions SET code = ?, type = ?, value = ?, min_amount = ?, start_date = ?, end_date = ?, usage_limit = ?, is_active = ? WHERE id = ?");
            $stmt->bind_param("ssddssiii", $code, $type, $value, $min_amount, $start_date, $end_date, $usage_limit, $is_active, $id);

            if ($stmt->execute()) {
                header("Location: index.php?msg=edit_success");
                exit();
            } else {
                $message = "Lỗi khi cập nhật khuyến mãi: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

if (!$promotion && !isset($_GET['id'])) {
    header("Location: index.php"); // Redirect if no ID is provided in GET
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh Sửa Khuyến Mãi</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        /* CSS riêng cho admin và form */
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
        .form-container {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            max-width: 600px;
            margin: 20px auto;
        }
        .form-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        .form-container input[type="text"],
        .form-container input[type="number"],
        .form-container input[type="datetime-local"],
        .form-container select {
            width: calc(100% - 20px);
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .form-container input[type="checkbox"] {
            margin-right: 10px;
        }
        .form-container button {
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        .form-container button:hover {
            background-color: #0056b3;
        }
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            color: white;
        }
        .message.error {
            background-color: #dc3545;
        }
        .message.success {
            background-color: #28a745;
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
                    <li><a href="../dashboard.php">Tổng Quan</a></li>
                    <li><a href="../products/index.php">Quản Lý Sản Phẩm</a></li>
                    <li><a href="../categories/index.php">Quản Lý Danh Mục</a></li>
                    <li><a href="index.php">Quản Lý Khuyến Mãi</a></li>
                    <li><a href="../../logout.php">Đăng Xuất</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="container">
        <h2>Chỉnh Sửa Khuyến Mãi</h2>
        <?php if ($message): ?>
            <p class="message <?php echo strpos($message, 'Lỗi') !== false ? 'error' : 'success'; ?>"><?php echo $message; ?></p>
        <?php endif; ?>

        <?php if ($promotion): ?>
            <div class="form-container">
                <form action="edit.php" method="POST">
                    <input type="hidden" name="id" value="<?php echo htmlspecialchars($promotion['id']); ?>">

                    <label for="code">Mã Khuyến Mãi:</label>
                    <input type="text" id="code" name="code" value="<?php echo htmlspecialchars($promotion['code']); ?>" required><br>

                    <label for="type">Loại Khuyến Mãi:</label>
                    <select id="type" name="type" required>
                        <option value="percentage" <?php echo ($promotion['type'] == 'percentage') ? 'selected' : ''; ?>>Phần trăm (%)</option>
                        <option value="fixed_amount" <?php echo ($promotion['type'] == 'fixed_amount') ? 'selected' : ''; ?>>Số tiền cố định</option>
                        <option value="free_shipping" <?php echo ($promotion['type'] == 'free_shipping') ? 'selected' : ''; ?>>Miễn phí vận chuyển</option>
                    </select><br>

                    <label for="value">Giá Trị:</label>
                    <input type="number" id="value" name="value" step="0.01" value="<?php echo htmlspecialchars($promotion['value']); ?>" required><br>

                    <label for="min_amount">Đơn Hàng Tối Thiểu (nếu có):</label>
                    <input type="number" id="min_amount" name="min_amount" step="0.01" value="<?php echo htmlspecialchars($promotion['min_amount']); ?>"><br>

                    <label for="start_date">Ngày Bắt Đầu:</label>
                    <input type="datetime-local" id="start_date" name="start_date" value="<?php echo date('Y-m-d\TH:i', strtotime($promotion['start_date'])); ?>" required><br>

                    <label for="end_date">Ngày Kết Thúc:</label>
                    <input type="datetime-local" id="end_date" name="end_date" value="<?php echo date('Y-m-d\TH:i', strtotime($promotion['end_date'])); ?>" required><br>

                    <label for="usage_limit">Giới Hạn Số Lần Sử Dụng (để trống nếu không giới hạn):</label>
                    <input type="number" id="usage_limit" name="usage_limit" step="1" min="1" value="<?php echo htmlspecialchars($promotion['usage_limit']); ?>"><br>

                    <label for="is_active">
                        <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo $promotion['is_active'] ? 'checked' : ''; ?>> Hoạt động
                    </label><br>

                    <button type="submit">Cập Nhật Khuyến Mãi</button>
                </form>
            </div>
        <?php else: ?>
            <p>Không tìm thấy khuyến mãi để chỉnh sửa.</p>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Admin Panel. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?>