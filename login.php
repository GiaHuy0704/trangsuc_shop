<?php
require_once 'includes/db.php';
// Bắt đầu session ở đây để lưu thông tin người dùng sau khi đăng nhập
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'includes/header.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = "Vui lòng điền đầy đủ tên người dùng và mật khẩu.";
    } else {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            // Xác minh mật khẩu
            if (password_verify($password, $user['password'])) {
                // Đăng nhập thành công, lưu thông tin vào session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $user['role'];

                // Chuyển hướng về trang chủ hoặc trang quản trị nếu là admin
                if ($user['role'] == 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            } else {
                $error_message = "Sai mật khẩu.";
            }
        } else {
            $error_message = "Tên người dùng không tồn tại.";
        }
        $stmt->close();
    }
}
?>

<div class="login-page">
    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-sign-in-alt"></i> Đăng Nhập</h2>
            <p class="login-subtitle">Chào mừng bạn quay trở lại!</p>
        </div>

        <?php if ($error_message): ?>
            <div class="flash-message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <form class="login-form" action="login.php" method="POST">
            <div class="form-group">
                <label for="username"><i class="fas fa-user"></i> Tên người dùng:</label>
                <input type="text" id="username" name="username" placeholder="Nhập tên người dùng" required>
            </div>

            <div class="form-group">
                <label for="password"><i class="fas fa-lock"></i> Mật khẩu:</label>
                <input type="password" id="password" name="password" placeholder="Nhập mật khẩu" required>
            </div>

            <button type="submit" class="login-submit">
                <i class="fas fa-sign-in-alt"></i> Đăng Nhập
            </button>
        </form>

        <div class="register-link">
            <p>Chưa có tài khoản? <a href="register.php"><i class="fas fa-user-plus"></i> Đăng ký ngay</a>.</p>
        </div>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>