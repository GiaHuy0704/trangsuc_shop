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
$categories = [];
$form_data = [ // Lưu trữ dữ liệu form để giữ lại sau khi submit lỗi
    'name' => '',
    'description' => '',
    'price' => '',
    'category_id' => '',
    'stock' => ''
];

// Lấy danh sách danh mục để chọn
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$result_categories = $conn->query($sql_categories);
while ($row = $result_categories->fetch_assoc()) {
    $categories[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Lấy dữ liệu từ form và gán vào form_data để giữ lại giá trị
    $form_data['name'] = trim($_POST['name']);
    $form_data['description'] = trim($_POST['description']);
    $form_data['price'] = (float)$_POST['price'];
    $form_data['category_id'] = (int)$_POST['category_id'];
    $form_data['stock'] = (int)$_POST['stock'];
    $image_name = '';
    $upload_ok = true; // Biến cờ để kiểm soát quá trình upload và insert

    // Kiểm tra các trường bắt buộc trước
    if (empty($form_data['name']) || empty($form_data['description']) || $form_data['price'] <= 0 || $form_data['category_id'] <= 0 || $form_data['stock'] < 0) {
        $error_message = "Vui lòng điền đầy đủ và chính xác các trường (Giá phải lớn hơn 0, Số lượng tồn kho không âm).";
        $upload_ok = false; // Đặt cờ thành false nếu có lỗi nhập liệu
    }

    // Xử lý upload ảnh chỉ khi không có lỗi nhập liệu cơ bản
    if ($upload_ok) {
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $target_dir = "../../images/"; // Thư mục lưu ảnh
            $original_image_name = basename($_FILES["image"]["name"]);
            $imageFileType = strtolower(pathinfo($original_image_name, PATHINFO_EXTENSION));

            // Tạo tên file duy nhất bằng timestamp và tên gốc
            $image_name = time() . '_' . uniqid() . '.' . $imageFileType;
            $target_file = $target_dir . $image_name;

            // Kiểm tra định dạng ảnh
            $allowed_types = array("jpg", "png", "jpeg", "gif");
            if (!in_array($imageFileType, $allowed_types)) {
                $error_message = "Chỉ chấp nhận file JPG, JPEG, PNG & GIF.";
                $upload_ok = false;
            }
            // Kiểm tra kích thước ảnh (ví dụ: không quá 5MB)
            if ($_FILES["image"]["size"] > 5000000) {
                $error_message = "Kích thước ảnh quá lớn, tối đa 5MB.";
                $upload_ok = false;
            }

            // Nếu không có lỗi về định dạng/kích thước, tiến hành di chuyển file
            if ($upload_ok) {
                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $error_message = "Có lỗi khi tải ảnh lên server. Vui lòng thử lại.";
                    $upload_ok = false;
                }
            }
        } else {
            // Trường hợp không có file nào được chọn hoặc có lỗi upload PHP
            if ($_FILES['image']['error'] == UPLOAD_ERR_NO_FILE) {
                $error_message = "Vui lòng chọn ảnh cho sản phẩm.";
            } else {
                $error_message = "Có lỗi xảy ra trong quá trình tải ảnh: " . $_FILES['image']['error'];
            }
            $upload_ok = false;
        }
    }

    // Nếu mọi thứ đều OK (cả nhập liệu và upload ảnh) thì mới tiến hành insert vào DB
    if ($upload_ok && empty($error_message)) {
        $stmt = $conn->prepare("INSERT INTO products (name, description, price, image, category_id, stock) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssdsii", $form_data['name'], $form_data['description'], $form_data['price'], $image_name, $form_data['category_id'], $form_data['stock']);

        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Sản phẩm đã được thêm thành công.";
            header("Location: index.php");
            exit();
        } else {
            // Nếu có lỗi SQL, xóa ảnh đã upload (nếu có) để tránh rác file
            if (!empty($image_name) && file_exists($target_dir . $image_name)) {
                unlink($target_dir . $image_name);
            }
            $error_message = "Lỗi khi thêm sản phẩm vào cơ sở dữ liệu: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Nhúng header riêng cho Admin Panel
require_once '../includes/admin_header.php';
?>
<div class="admin-create-products-page">
    <div class="container">
        <div class="admin-create-header">
            <h2><i class="fas fa-plus-circle"></i> Thêm Sản Phẩm Mới</h2>
        </div>

        <?php if ($error_message): ?>
            <div class="admin-create-flash-message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <div class="admin-create-form-container">
            <form action="create.php" method="POST" enctype="multipart/form-data" class="admin-create-form">
                <div class="admin-form-group">
                    <label for="name">Tên Sản Phẩm:</label>
                    <input type="text" id="name" name="name" class="admin-form-input" required 
                           value="<?php echo htmlspecialchars($form_data['name']); ?>"
                           placeholder="Nhập tên sản phẩm...">
                </div>

                <div class="admin-form-group">
                    <label for="description">Mô Tả:</label>
                    <textarea id="description" name="description" rows="5" class="admin-form-textarea" required
                              placeholder="Nhập mô tả chi tiết về sản phẩm..."><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                </div>

                <div class="admin-form-group">
                    <label for="price">Giá (VNĐ):</label>
                    <input type="number" id="price" name="price" step="0.01" min="0" class="admin-form-input" required 
                           value="<?php echo htmlspecialchars($form_data['price']); ?>"
                           placeholder="Nhập giá sản phẩm...">
                </div>

                <div class="admin-form-group">
                    <label for="category_id">Danh Mục:</label>
                    <select id="category_id" name="category_id" class="admin-form-select" required>
                        <option value="">-- Chọn danh mục --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo ($form_data['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-form-group">
                    <label for="stock">Số Lượng Tồn Kho:</label>
                    <input type="number" id="stock" name="stock" min="0" class="admin-form-input" required 
                           value="<?php echo htmlspecialchars($form_data['stock']); ?>"
                           placeholder="Nhập số lượng tồn kho...">
                </div>

                <div class="admin-form-group">
                    <label for="image">Ảnh Sản Phẩm:</label>
                    <input type="file" id="image" name="image" accept="image/*" class="admin-form-file" required>
                </div>

                <div class="admin-form-buttons">
                    <button type="submit" class="btn-create-product">
                        <i class="fas fa-plus"></i> Thêm Sản Phẩm
                    </button>
                    <a href="index.php" class="btn-back-products">
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