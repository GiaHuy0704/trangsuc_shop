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

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
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
        // Check if code already exists
        $stmt_check = $conn->prepare("SELECT id FROM promotions WHERE code = ?");
        $stmt_check->bind_param("s", $code);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $message = "Mã khuyến mãi đã tồn tại. Vui lòng chọn mã khác.";
        } else {
            $stmt = $conn->prepare("INSERT INTO promotions (code, type, value, min_amount, start_date, end_date, usage_limit, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssddssii", $code, $type, $value, $min_amount, $start_date, $end_date, $usage_limit, $is_active);

            if ($stmt->execute()) {
                header("Location: index.php?msg=add_success");
                exit();
            } else {
                $message = "Lỗi khi thêm khuyến mãi: " . $stmt->error;
            }
            $stmt->close();
        }
        $stmt_check->close();
    }
}

// Nhúng header riêng cho Admin Panel
require_once '../includes/admin_header.php';
?>

<div class="admin-create-promotions-page">
    <div class="container">
        <div class="admin-create-promotions-header">
            <h2><i class="fas fa-plus"></i> Thêm Khuyến Mãi Mới</h2>
        </div>

        <?php if ($message): ?>
            <div class="admin-create-promotions-flash-message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="admin-create-promotions-form-container">
            <form action="create.php" method="POST" class="admin-create-promotions-form">
                <div class="admin-create-promotions-form-group">
                    <label for="code">Mã Khuyến Mãi:</label>
                    <input type="text" id="code" name="code" class="admin-create-promotions-form-input" required 
                           value="<?php echo isset($_POST['code']) ? htmlspecialchars($_POST['code']) : ''; ?>"
                           placeholder="Nhập mã khuyến mãi...">
                </div>

                <div class="admin-create-promotions-form-group">
                    <label for="type">Loại Khuyến Mãi:</label>
                    <select id="type" name="type" class="admin-create-promotions-form-select" required>
                        <option value="percentage" <?php echo (isset($_POST['type']) && $_POST['type'] == 'percentage') ? 'selected' : ''; ?>>Phần trăm (%)</option>
                        <option value="fixed_amount" <?php echo (isset($_POST['type']) && $_POST['type'] == 'fixed_amount') ? 'selected' : ''; ?>>Số tiền cố định</option>
                        <option value="free_shipping" <?php echo (isset($_POST['type']) && $_POST['type'] == 'free_shipping') ? 'selected' : ''; ?>>Miễn phí vận chuyển</option>
                    </select>
                </div>

                <div class="admin-create-promotions-form-group">
                    <label for="value">Giá Trị:</label>
                    <input type="number" id="value" name="value" step="0.01" class="admin-create-promotions-form-input" required 
                           value="<?php echo isset($_POST['value']) ? htmlspecialchars($_POST['value']) : ''; ?>"
                           placeholder="Nhập giá trị khuyến mãi...">
                </div>

                <div class="admin-create-promotions-form-group">
                    <label for="min_amount">Đơn Hàng Tối Thiểu (nếu có):</label>
                    <input type="number" id="min_amount" name="min_amount" step="0.01" class="admin-create-promotions-form-input" 
                           value="<?php echo isset($_POST['min_amount']) ? htmlspecialchars($_POST['min_amount']) : '0'; ?>"
                           placeholder="Nhập giá trị tối thiểu...">
                </div>

                <div class="admin-create-promotions-form-group">
                    <label for="start_date">Ngày Bắt Đầu:</label>
                    <input type="datetime-local" id="start_date" name="start_date" class="admin-create-promotions-form-input" required 
                           value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>">
                </div>

                <div class="admin-create-promotions-form-group">
                    <label for="end_date">Ngày Kết Thúc:</label>
                    <input type="datetime-local" id="end_date" name="end_date" class="admin-create-promotions-form-input" required 
                           value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>">
                </div>

                <div class="admin-create-promotions-form-group">
                    <label for="usage_limit">Giới Hạn Số Lần Sử Dụng (để trống nếu không giới hạn):</label>
                    <input type="number" id="usage_limit" name="usage_limit" step="1" min="1" class="admin-create-promotions-form-input" 
                           value="<?php echo isset($_POST['usage_limit']) ? htmlspecialchars($_POST['usage_limit']) : ''; ?>"
                           placeholder="Nhập số lần sử dụng tối đa...">
                </div>

                <div class="admin-create-promotions-form-group">
                    <label for="is_active">
                        <input type="checkbox" id="is_active" name="is_active" value="1" class="admin-create-promotions-form-checkbox" 
                               <?php echo (!isset($_POST['is_active']) || $_POST['is_active']) ? 'checked' : ''; ?>> Hoạt động
                    </label>
                </div>

                <div class="admin-create-promotions-form-buttons">
                    <button type="submit" class="btn-create-promotion">
                        <i class="fas fa-plus"></i> Thêm Khuyến Mãi
                    </button>
                    <a href="index.php" class="btn-back-to-promotions">
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