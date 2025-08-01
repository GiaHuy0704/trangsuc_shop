<?php
require_once '../../includes/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category_name = '';
$error_message = '';

if ($category_id > 0) {
    // Lấy thông tin danh mục hiện tại
    $stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $category = $result->fetch_assoc();
        $category_name = htmlspecialchars($category['name']);
    } else {
        $error_message = "Danh mục không tìm thấy.";
    }
    $stmt->close();
} else {
    $error_message = "ID danh mục không hợp lệ.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $new_category_name = trim($_POST['name']);

    if (empty($new_category_name)) {
        $error_message = "Tên danh mục không được để trống.";
    } elseif ($category_id == 0) {
        $error_message = "Không thể cập nhật danh mục không hợp lệ.";
    } else {
        $stmt_update = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
        $stmt_update->bind_param("si", $new_category_name, $category_id);

        if ($stmt_update->execute()) {
            $_SESSION['success_message'] = "Danh mục đã được cập nhật thành công.";
            header("Location: index.php");
            exit();
        } else {
            $error_message = "Lỗi: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}

// Nhúng header riêng cho Admin Panel
require_once '../includes/admin_header.php';
?>

<div class="admin-edit-categories-page">
    <div class="container">
        <div class="admin-edit-categories-header">
            <h2><i class="fas fa-edit"></i> Chỉnh Sửa Danh Mục</h2>
        </div>

        <?php if ($error_message): ?>
            <div class="admin-edit-categories-flash-message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($category_id > 0 && !$error_message): ?>
            <!-- Current Category Info -->
            <div class="admin-current-category">
                <div class="admin-current-category-info">
                    <span class="category-id"><i class="fas fa-hashtag"></i> ID: <?php echo $category_id; ?></span>
                    <h3><i class="fas fa-folder"></i> <?php echo htmlspecialchars($category_name); ?></h3>
                    <p><i class="fas fa-info-circle"></i> Chỉnh sửa thông tin danh mục</p>
                </div>
            </div>

            <div class="admin-edit-categories-form-container">
                <form action="edit.php?id=<?php echo $category_id; ?>" method="POST" class="admin-edit-categories-form">
                    <div class="admin-edit-categories-form-group">
                        <label for="name">Tên Danh Mục:</label>
                        <input type="text" id="name" name="name" class="admin-edit-categories-form-input" required 
                               value="<?php echo $category_name; ?>"
                               placeholder="Nhập tên danh mục...">
                    </div>

                    <div class="admin-edit-categories-form-buttons">
                        <button type="submit" class="btn-update-category">
                            <i class="fas fa-save"></i> Cập Nhật Danh Mục
                        </button>
                        <a href="index.php" class="btn-back-to-categories-list">
                            <i class="fas fa-arrow-left"></i> Quay Lại
                        </a>
                    </div>
                </form>
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