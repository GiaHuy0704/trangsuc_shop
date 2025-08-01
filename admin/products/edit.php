<?php
require_once '../../includes/db.php';
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../../login.php");
    exit();
}

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
$error_message = '';
$categories = [];

// Khởi tạo form_data với giá trị mặc định hoặc từ DB
$form_data = [
    'name' => '',
    'description' => '',
    'price' => '',
    'category_id' => '',
    'stock' => '',
    'image' => '' // Sẽ lưu tên ảnh cũ hoặc ảnh mới
];

// Lấy danh sách danh mục
$sql_categories = "SELECT id, name FROM categories ORDER BY name ASC";
$result_categories = $conn->query($sql_categories);
while ($row = $result_categories->fetch_assoc()) {
    $categories[] = $row;
}

if ($product_id > 0) {
    // Lấy thông tin sản phẩm hiện tại
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        // Gán dữ liệu sản phẩm hiện tại vào form_data khi tải trang
        $form_data['name'] = $product['name'];
        $form_data['description'] = $product['description'];
        $form_data['price'] = $product['price'];
        $form_data['category_id'] = $product['category_id'];
        $form_data['stock'] = $product['stock'];
        $form_data['image'] = $product['image']; // Ảnh hiện tại
    } else {
        $error_message = "Sản phẩm không tìm thấy.";
    }
    $stmt->close();
} else {
    $error_message = "ID sản phẩm không hợp lệ.";
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $product) {
    // Lấy dữ liệu từ form submit
    $form_data['name'] = trim($_POST['name']);
    $form_data['description'] = trim($_POST['description']);
    $form_data['price'] = (float)$_POST['price'];
    $form_data['category_id'] = (int)$_POST['category_id'];
    $form_data['stock'] = (int)$_POST['stock'];

    $current_image_db = $product['image']; // Ảnh đang lưu trong DB
    $image_name_for_db = $current_image_db; // Mặc định giữ ảnh cũ
    $upload_ok = true; // Biến cờ để kiểm soát quá trình upload và update

    // --- Kiểm tra các trường bắt buộc trước ---
    if (empty($form_data['name']) || empty($form_data['description']) || $form_data['price'] <= 0 || $form_data['category_id'] <= 0 || $form_data['stock'] < 0) {
        $error_message = "Vui lòng điền đầy đủ và chính xác các trường (Giá phải lớn hơn 0, Số lượng tồn kho không âm).";
        $upload_ok = false;
    }

    // --- Xử lý upload ảnh mới (chỉ khi có file được chọn) ---
    if ($upload_ok && isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../../images/";
        $original_file_name = basename($_FILES["image"]["name"]);
        $imageFileType = strtolower(pathinfo($original_file_name, PATHINFO_EXTENSION));

        // Tạo tên file duy nhất để lưu
        $new_image_name_unique = time() . '_' . uniqid() . '.' . $imageFileType;
        $target_file_path = $target_dir . $new_image_name_unique;

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
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file_path)) {
                // Ảnh mới đã được upload thành công, cập nhật tên ảnh cho DB
                $image_name_for_db = $new_image_name_unique;
            } else {
                $error_message = "Có lỗi khi tải ảnh mới lên server. Vui lòng thử lại.";
                $upload_ok = false;
            }
        }
    }
    // Xử lý trường hợp có lỗi upload file PHP khác (ví dụ: dung lượng quá giới hạn post_max_size)
    else if ($upload_ok && isset($_FILES['image']) && $_FILES['image']['error'] != UPLOAD_ERR_NO_FILE) {
         $error_message = "Có lỗi xảy ra trong quá trình tải ảnh: Mã lỗi " . $_FILES['image']['error'];
         $upload_ok = false;
    }


    // --- Thực hiện cập nhật vào cơ sở dữ liệu nếu không có lỗi nào ---
    if ($upload_ok && empty($error_message)) {
        $stmt_update = $conn->prepare("UPDATE products SET name = ?, description = ?, price = ?, image = ?, category_id = ?, stock = ? WHERE id = ?");
        $stmt_update->bind_param("ssdsiii", $form_data['name'], $form_data['description'], $form_data['price'], $image_name_for_db, $form_data['category_id'], $form_data['stock'], $product_id);

        if ($stmt_update->execute()) {
            // Chỉ xóa ảnh cũ sau khi UPDATE DB thành công và nếu ảnh mới khác ảnh cũ
            if (!empty($current_image_db) && $image_name_for_db != $current_image_db && file_exists($target_dir . $current_image_db)) {
                unlink($target_dir . $current_image_db);
            }
            $_SESSION['success_message'] = "Sản phẩm đã được cập nhật thành công.";
            header("Location: index.php");
            exit();
        } else {
            // Nếu có lỗi SQL, và nếu có ảnh mới đã được upload thành công, hãy xóa nó đi
            if (!empty($new_image_name_unique) && file_exists($target_dir . $new_image_name_unique) && $image_name_for_db == $new_image_name_unique) {
                unlink($target_dir . $new_image_name_unique);
            }
            $error_message = "Lỗi khi cập nhật sản phẩm vào cơ sở dữ liệu: " . $stmt_update->error;
        }
        $stmt_update->close();
    }
}

// Nhúng header riêng cho Admin Panel
require_once '../includes/admin_header.php';
?>
<div class="admin-edit-products-page">
    <div class="container">
        <div class="admin-edit-header">
            <h2><i class="fas fa-edit"></i> Chỉnh Sửa Sản Phẩm</h2>
        </div>

        <?php if ($error_message): ?>
            <div class="admin-edit-flash-message error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($product): // Chỉ hiển thị form nếu tìm thấy sản phẩm ?>
            <!-- Current Product Info -->
            <div class="admin-current-product">
                <?php if (!empty($form_data['image'])): ?>
                    <img src="../../images/<?php echo htmlspecialchars($form_data['image']); ?>" 
                         alt="<?php echo htmlspecialchars($form_data['name']); ?>" 
                         class="admin-current-product-image"
                         onerror="this.src='../../img/image.png'; this.onerror=null;">
                <?php endif; ?>
                <div class="admin-current-product-info">
                    <span class="product-id"><i class="fas fa-hashtag"></i> ID: <?php echo $product_id; ?></span>
                    <h3><i class="fas fa-box"></i> <?php echo htmlspecialchars($form_data['name']); ?></h3>
                    <p><i class="fas fa-coins"></i> Giá: <?php echo number_format($form_data['price'], 0, ',', '.'); ?> VNĐ</p>
                    <p><i class="fas fa-boxes"></i> Tồn kho: <?php echo $form_data['stock']; ?> sản phẩm</p>
                </div>
            </div>

            <div class="admin-edit-form-container">
                <form action="edit.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data" class="admin-edit-form">
                    <div class="admin-edit-form-group">
                        <label for="name">Tên Sản Phẩm:</label>
                        <input type="text" id="name" name="name" class="admin-edit-form-input" required 
                               value="<?php echo htmlspecialchars($form_data['name']); ?>"
                               placeholder="Nhập tên sản phẩm...">
                    </div>

                    <div class="admin-edit-form-group">
                        <label for="description">Mô Tả:</label>
                        <textarea id="description" name="description" rows="5" class="admin-edit-form-textarea" required
                                  placeholder="Nhập mô tả chi tiết về sản phẩm..."><?php echo htmlspecialchars($form_data['description']); ?></textarea>
                    </div>

                    <div class="admin-edit-form-group">
                        <label for="price">Giá (VNĐ):</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" class="admin-edit-form-input" required 
                               value="<?php echo htmlspecialchars($form_data['price']); ?>"
                               placeholder="Nhập giá sản phẩm...">
                    </div>

                    <div class="admin-edit-form-group">
                        <label for="category_id">Danh Mục:</label>
                        <select id="category_id" name="category_id" class="admin-edit-form-select" required>
                            <option value="">-- Chọn danh mục --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo ($form_data['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="admin-edit-form-group">
                        <label for="stock">Số Lượng Tồn Kho:</label>
                        <input type="number" id="stock" name="stock" min="0" class="admin-edit-form-input" required 
                               value="<?php echo htmlspecialchars($form_data['stock']); ?>"
                               placeholder="Nhập số lượng tồn kho...">
                    </div>

                    <div class="admin-edit-form-group">
                        <label for="image">Ảnh Sản Phẩm (Để trống nếu không đổi ảnh):</label>
                        <?php if (!empty($form_data['image'])): ?>
                            <div class="admin-current-image-section">
                                <p><i class="fas fa-image"></i> Ảnh hiện tại:</p>
                                <img src="../../images/<?php echo htmlspecialchars($form_data['image']); ?>" 
                                     alt="Current Image" 
                                     onerror="this.src='../../img/image.png'; this.onerror=null;">
                            </div>
                        <?php endif; ?>
                        <input type="file" id="image" name="image" accept="image/*" class="admin-edit-form-file">
                    </div>

                    <div class="admin-edit-form-buttons">
                        <button type="submit" class="btn-update-product">
                            <i class="fas fa-save"></i> Cập Nhật Sản Phẩm
                        </button>
                        <a href="index.php" class="btn-back-to-products">
                            <i class="fas fa-arrow-left"></i> Quay Lại
                        </a>
                    </div>
                </form>
            </div>
        <?php elseif (!$product && !$error_message): // Chỉ hiện thông báo không tìm thấy nếu không có lỗi ID ban đầu ?>
            <div class="admin-loading-state">
                <i class="fas fa-spinner"></i>
                <p>Đang tải thông tin sản phẩm...</p>
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