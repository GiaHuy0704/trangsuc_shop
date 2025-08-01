<?php
require_once '../../includes/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $category_name = trim($_POST['name']);

    if (empty($category_name)) {
        $error_message = "Tên danh mục không được để trống.";
    } else {
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $category_name);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Danh mục đã được thêm thành công.";
            header("Location: index.php");
            exit();
        } else {
            $error_message = "Lỗi: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Nhúng header riêng cho Admin Panel
require_once '../includes/admin_header.php';
?>

<div class="admin-create-categories-page">
    <div class="container">
        <div class="admin-create-categories-header">
            <h2><i class="fas fa-plus"></i> Thêm Danh Mục Mới</h2>
        </div>

        <?php if ($error_message): ?>
            <div class="admin-create-categories-flash-message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="admin-create-categories-form-container">
            <form action="create.php" method="POST" class="admin-create-categories-form">
                <div class="admin-create-categories-form-group">
                    <label for="name">Tên Danh Mục:</label>
                    <input type="text" id="name" name="name" class="admin-create-categories-form-input" required 
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>"
                           placeholder="Nhập tên danh mục...">
                </div>

                <div class="admin-create-categories-form-buttons">
                    <button type="submit" class="btn-create-category">
                        <i class="fas fa-plus"></i> Thêm Danh Mục
                    </button>
                    <a href="index.php" class="btn-back-to-categories">
                        <i class="fas fa-arrow-left"></i> Quay Lại
                    </a>
                </div>
            </form>
        </div>
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