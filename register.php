<?php
require_once 'includes/db.php';
include_once 'includes/header.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
     $phone_number = trim($_POST['phone_number'] ?? ''); // Thêm phone_number
    $address = trim($_POST['address'] ?? '');         // Thêm address

    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error_message = "Vui lòng điền đầy đủ tất cả các trường.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Mật khẩu xác nhận không khớp.";
    } elseif (strlen($password) < 6) {
        $error_message = "Mật khẩu phải có ít nhất 6 ký tự.";
    } else {
        // Kiểm tra xem username hoặc email đã tồn tại chưa
        $stmt_check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt_check->bind_param("ss", $username, $email);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $error_message = "Tên người dùng hoặc email đã tồn tại.";
        } else {
            // Hash mật khẩu trước khi lưu vào DB
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt_insert = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, 'user')");
            $stmt_insert->bind_param("sss", $username, $email, $hashed_password);

            if ($stmt_insert->execute()) {
                $success_message = "Đăng ký thành công! Bạn có thể <a href='login.php'>đăng nhập</a> ngay bây giờ.";
            } else {
                $error_message = "Có lỗi xảy ra khi đăng ký. Vui lòng thử lại.";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
}
?>

<div class="register-page">
    <div class="register-container">
        <div class="register-header">
            <h2><i class="fas fa-user-plus"></i> Đăng Ký Tài Khoản</h2>
            <p class="register-subtitle">Tạo tài khoản mới để bắt đầu mua sắm!</p>
        </div>

        <?php if ($error_message): ?>
            <div class="flash-message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="flash-message success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <form class="register-form" action="register.php" method="POST">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Tên người dùng:</label>
                <input type="text" id="username" name="username" placeholder="Nhập tên người dùng" required>
            </div>

            <div class="form-group">
                <label for="email"><i class="fas fa-envelope"></i> Email:</label>
                <input type="email" id="email" name="email" placeholder="Nhập địa chỉ email" required>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Mật khẩu:</label>
                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu (ít nhất 6 ký tự)" required>
            </div>

            <div class="form-group">
                <label for="confirm_password"><i class="fas fa-lock"></i> Xác nhận mật khẩu:</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Nhập lại mật khẩu" required>
            </div>

            <button type="submit" class="register-submit">
                <i class="fas fa-user-plus"></i> Đăng Ký
            </button>
        </form>

        <div class="login-link">
            <p>Đã có tài khoản? <a href="login.php"><i class="fas fa-sign-in-alt"></i> Đăng nhập ngay</a>.</p>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>