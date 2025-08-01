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
$user = null;
$error_message = '';

// Khởi tạo form_data với giá trị mặc định hoặc từ DB
$form_data = [
    'username' => '',
    'email' => '',
    'phone_number' => '',
    'address' => '',
    'role' => ''
];

if ($user_id > 0) {
    // Lấy thông tin khách hàng hiện tại
    $stmt = $conn->prepare("SELECT id, username, email, phone_number, address, role FROM users WHERE id = ? AND role = 'user'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // Gán dữ liệu khách hàng hiện tại vào form_data khi tải trang
        $form_data['username'] = $user['username'];
        $form_data['email'] = $user['email'];
        $form_data['phone_number'] = $user['phone_number'];
        $form_data['address'] = $user['address'];
        $form_data['role'] = $user['role'];
    } else {
        $error_message = "Khách hàng không tìm thấy.";
    }
    $stmt->close();
} else {
    $error_message = "ID khách hàng không hợp lệ.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user) {
    // Lấy dữ liệu từ form submit
    $form_data['username'] = trim($_POST['username']);
    $form_data['email'] = trim($_POST['email']);
    $password = trim($_POST['password']);
    $form_data['phone_number'] = trim($_POST['phone_number']);
    $form_data['address'] = trim($_POST['address']);

    // Kiểm tra các trường bắt buộc
    if (empty($form_data['username']) || empty($form_data['email'])) {
        $error_message = "Tên đăng nhập và email không được để trống.";
    } elseif (!filter_var($form_data['email'], FILTER_VALIDATE_EMAIL)) {
        $error_message = "Email không hợp lệ.";
    } else {
        // Kiểm tra trùng lặp username/email (trừ chính user đang sửa)
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt_check->bind_param("ssi", $form_data['username'], $form_data['email'], $user_id);
        $stmt_check->execute();
        $check_result = $stmt_check->get_result();
        if ($check_result->num_rows > 0) {
            $error_message = "Tên đăng nhập hoặc email đã tồn tại.";
        } else {
            if (!empty($password)) {
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, phone_number = ?, address = ? WHERE id = ? AND role = 'user'");
                $stmt->bind_param("sssssi", $form_data['username'], $form_data['email'], $hashed_password, $form_data['phone_number'], $form_data['address'], $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, phone_number = ?, address = ? WHERE id = ? AND role = 'user'");
                $stmt->bind_param("ssssi", $form_data['username'], $form_data['email'], $form_data['phone_number'], $form_data['address'], $user_id);
            }

            if ($stmt->execute()) {
                $_SESSION['success_message'] = "Thông tin khách hàng đã được cập nhật thành công.";
                header("Location: index.php");
                exit();
            } else {
                $error_message = "Lỗi khi cập nhật khách hàng vào cơ sở dữ liệu: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Nhúng header riêng cho Admin Panel
require_once '../includes/admin_header.php';
?>
<div class="admin-edit-users-page">
    <div class="container">
        <div class="admin-edit-header">
            <h2><i class="fas fa-edit"></i> Chỉnh Sửa Khách Hàng</h2>
        </div>

        <?php if ($error_message): ?>
            <div class="admin-edit-flash-message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($user): // Chỉ hiển thị form nếu tìm thấy khách hàng ?>
            <!-- Current User Info -->
            <div class="admin-current-user">
                <div class="admin-current-user-info">
                    <span class="user-id"><i class="fas fa-hashtag"></i> ID: <?php echo $user_id; ?></span>
                    <h3><i class="fas fa-user"></i> <?php echo htmlspecialchars($form_data['username']); ?></h3>
                    <p><i class="fas fa-envelope"></i> Email: <?php echo htmlspecialchars($form_data['email']); ?></p>
                    <p><i class="fas fa-phone"></i> Số điện thoại: <?php echo htmlspecialchars($form_data['phone_number'] ?? 'N/A'); ?></p>
                    <p><i class="fas fa-user-tag"></i> Vai trò: <?php echo htmlspecialchars($form_data['role']); ?></p>
                </div>
            </div>

            <div class="admin-edit-form-container">
                <form action="edit.php?id=<?php echo $user_id; ?>" method="POST" class="admin-edit-form">
                    <div class="admin-edit-form-group">
                        <label for="username">Tên Đăng Nhập:</label>
                        <input type="text" id="username" name="username" class="admin-edit-form-input" required 
                               value="<?php echo htmlspecialchars($form_data['username']); ?>"
                               placeholder="Nhập tên đăng nhập...">
                    </div>

                    <div class="admin-edit-form-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" class="admin-edit-form-input" required 
                               value="<?php echo htmlspecialchars($form_data['email']); ?>"
                               placeholder="Nhập email...">
                    </div>

                    <div class="admin-edit-form-group">
                        <label for="phone_number">Số Điện Thoại:</label>
                        <input type="text" id="phone_number" name="phone_number" class="admin-edit-form-input" 
                               value="<?php echo htmlspecialchars($form_data['phone_number']); ?>"
                               placeholder="Nhập số điện thoại...">
                    </div>

                    <div class="admin-edit-form-group">
                        <label for="address">Địa Chỉ:</label>
                        <textarea id="address" name="address" rows="3" class="admin-edit-form-textarea"
                                  placeholder="Nhập địa chỉ..."><?php echo htmlspecialchars($form_data['address']); ?></textarea>
                    </div>

                    <div class="admin-edit-form-group">
                        <label for="password">Mật Khẩu (Để trống nếu không đổi):</label>
                        <input type="password" id="password" name="password" class="admin-edit-form-input" 
                               placeholder="Nhập mật khẩu mới...">
                        <small class="admin-form-help">Để trống nếu bạn không muốn thay đổi mật khẩu.</small>
                    </div>

                    <div class="admin-edit-form-buttons">
                        <button type="submit" class="btn-update-user">
                            <i class="fas fa-save"></i> Cập Nhật Khách Hàng
                        </button>
                        <a href="index.php" class="btn-back-to-users">
                            <i class="fas fa-arrow-left"></i> Quay Lại
                        </a>
                    </div>
                </form>
            </div>
        <?php elseif (!$user && !$error_message): // Chỉ hiện thông báo không tìm thấy nếu không có lỗi ID ban đầu ?>
            <div class="admin-loading-state">
                <i class="fas fa-spinner"></i>
                <p>Đang tải thông tin khách hàng...</p>
            </div>
        <?php endif; ?>
    </div>
</div>
    <footer>
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Admin Panel. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
<?php $conn->close(); ?>