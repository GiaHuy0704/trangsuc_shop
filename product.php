<?php
require_once 'includes/db.php';
// Bắt đầu session để lấy thông tin người dùng nếu họ muốn gửi đánh giá
// Và để sử dụng các flash message
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once 'includes/header.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$product = null;
$product_promotions = []; // Mảng để lưu các khuyến mãi áp dụng cho sản phẩm này

// Lấy thông báo flash message nếu có
$error_message = isset($_SESSION['error_message']) ? $_SESSION['error_message'] : '';
$success_message = isset($_SESSION['success_message']) ? $_SESSION['success_message'] : '';
unset($_SESSION['error_message']); // Xóa sau khi hiển thị
unset($_SESSION['success_message']); // Xóa sau khi hiển thị

if ($product_id > 0) {
    // Lấy thông tin sản phẩm
    $stmt_product = $conn->prepare("SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt_product->bind_param("i", $product_id);
    $stmt_product->execute();
    $result_product = $stmt_product->get_result();

    if ($result_product->num_rows > 0) {
        $product = $result_product->fetch_assoc();

        // Lấy các khuyến mãi áp dụng cho sản phẩm này
        $current_datetime = date('Y-m-d H:i:s');
        $stmt_product_promotions = $conn->prepare("
            SELECT DISTINCT p.*
            FROM promotions p
            LEFT JOIN promotion_product_map ppm ON p.id = ppm.promotion_id
            LEFT JOIN promotion_category_map pcm ON p.id = pcm.promotion_id
            WHERE p.is_active = TRUE
            AND p.code IS NULL -- Chỉ lấy khuyến mãi tự động (không cần mã code)
            AND p.start_date <= ? AND p.end_date >= ?
            AND (
                p.applies_to = 'all' OR
                (p.applies_to = 'products' AND ppm.product_id = ?) OR
                (p.applies_to = 'categories' AND pcm.category_id = ?)
            )
            ORDER BY p.created_at DESC
        ");
        $stmt_product_promotions->bind_param("ssii", $current_datetime, $current_datetime, $product_id, $product['category_id']);
        $stmt_product_promotions->execute();
        $result_product_promotions = $stmt_product_promotions->get_result();
        while ($promo_row = $result_product_promotions->fetch_assoc()) {
            $product_promotions[] = $promo_row;
        }
        $stmt_product_promotions->close();

    } else {
        $error_message .= "Sản phẩm không tìm thấy.";
    }
    $stmt_product->close();
} else {
    $error_message .= "ID sản phẩm không hợp lệ.";
}

// Xử lý gửi đánh giá
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    if (!isset($_SESSION['user_id'])) {
        $error_message .= "Bạn cần đăng nhập để gửi đánh giá.";
    } else {
        $user_id = $_SESSION['user_id'];
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);

        if ($rating < 1 || $rating > 5) {
            $error_message .= "Điểm đánh giá phải từ 1 đến 5.";
        } elseif (empty($comment)) {
            $error_message .= "Vui lòng nhập nội dung đánh giá.";
        } else {
            // Kiểm tra xem người dùng đã đánh giá sản phẩm này chưa
            $stmt_check_review = $conn->prepare("SELECT id FROM reviews WHERE product_id = ? AND user_id = ?");
            $stmt_check_review->bind_param("ii", $product_id, $user_id);
            $stmt_check_review->execute();
            $stmt_check_review->store_result();

            if ($stmt_check_review->num_rows > 0) {
                $error_message .= "Bạn đã đánh giá sản phẩm này rồi.";
            } else {
                $stmt_review = $conn->prepare("INSERT INTO reviews (product_id, user_id, rating, comment) VALUES (?, ?, ?, ?)");
                $stmt_review->bind_param("iiis", $product_id, $user_id, $rating, $comment);
                if ($stmt_review->execute()) {
                    $success_message .= "Đánh giá của bạn đã được gửi thành công!";
                    // Reset form fields
                    $_POST['rating'] = '';
                    $_POST['comment'] = '';
                } else {
                    $error_message .= "Có lỗi xảy ra khi gửi đánh giá. Vui lòng thử lại.";
                }
                $stmt_review->close();
            }
            $stmt_check_review->close();
        }
    }
}

// Lấy tất cả đánh giá của sản phẩm
$reviews = [];
if ($product_id > 0) {
    $stmt_reviews = $conn->prepare("SELECT r.*, u.username FROM reviews r JOIN users u ON r.user_id = u.id WHERE r.product_id = ? ORDER BY r.created_at DESC");
    $stmt_reviews->bind_param("i", $product_id);
    $stmt_reviews->execute();
    $result_reviews = $stmt_reviews->get_result();
    while ($row_review = $result_reviews->fetch_assoc()) {
        $reviews[] = $row_review;
    }
    $stmt_reviews->close();
}

?>

<div class="product-page">
    <div class="container">
        <?php if ($error_message): ?>
            <div class="flash-message error">
                <i class="fas fa-exclamation-triangle"></i>
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($success_message): ?>
            <div class="flash-message success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($product): ?>
            <div class="product-detail">
        <div class="image-gallery">
            <img src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='img/image.png'; this.onerror=null;">
        </div>
        <div class="info">
            <h2><i class="fas fa-gem"></i> <?php echo htmlspecialchars($product['name']); ?></h2>
            <p><strong><i class="fas fa-tags"></i> Danh mục:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
            <p class="price"><i class="fas fa-coins"></i> <?php echo number_format($product['price'], 0, ',', '.'); ?> VNĐ</p>
            <p><strong><i class="fas fa-box"></i> Tình trạng:</strong> <?php echo $product['stock'] > 0 ? 'Còn hàng (' . $product['stock'] . ')' : 'Hết hàng'; ?></p>

            <?php if (!empty($product_promotions)): // Hiển thị các khuyến mãi áp dụng ?>
            <div class="product-promotions">
                <h3><i class="fas fa-gift"></i> Ưu đãi áp dụng cho sản phẩm này:</h3>
                <ul>
                    <?php foreach ($product_promotions as $promo): ?>
                        <li>
                            <strong><i class="fas fa-percentage"></i> <?php echo htmlspecialchars($promo['name']); ?>:</strong>
                            <?php
                            if ($promo['type'] === 'percentage') {
                                echo "Giảm " . htmlspecialchars($promo['value']) . "%";
                            } elseif ($promo['type'] === 'fixed_amount') {
                                echo "Giảm " . number_format($promo['value'], 0, ',', '.') . " VNĐ";
                            } elseif ($promo['type'] === 'free_shipping') {
                                echo "Miễn phí vận chuyển";
                            }
                            if ($promo['min_order_amount'] > 0) {
                                echo " (Đơn hàng từ " . number_format($promo['min_order_amount'], 0, ',', '.') . " VNĐ)";
                            }
                            ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <p class="note"><i class="fas fa-info-circle"></i> Các ưu đãi này sẽ tự động áp dụng khi đủ điều kiện trong giỏ hàng.</p>
            </div>
            <?php endif; ?>

            <div class="description">
                <h3><i class="fas fa-info-circle"></i> Mô tả sản phẩm:</h3>
                <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
            </div>

            <form action="add_to_cart.php" method="POST">
                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                <label for="quantity"><i class="fas fa-sort-numeric-up"></i> Số lượng:</label>
                <input type="number" id="quantity" name="quantity" value="1" min="1" max="<?php echo $product['stock']; ?>">
                <?php if ($product['stock'] > 0): ?>
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                    </button>
                <?php else: ?>
                    <button type="button" class="btn-primary" disabled>
                        <i class="fas fa-times-circle"></i> Hết hàng
                    </button>
                <?php endif; ?>
            </form>
            </div>
    </div>

    <div class="reviews-section">
        <h3><i class="fas fa-star"></i> Đánh giá sản phẩm (<?php echo count($reviews); ?>)</h3>
        <?php if (!empty($reviews)): ?>
            <?php foreach ($reviews as $review): ?>
                <div class="review-item">
                    <strong><i class="fas fa-user"></i> <?php echo htmlspecialchars($review['username']); ?></strong>
                    <span class="rating"><i class="fas fa-star"></i> Đánh giá: <?php echo $review['rating']; ?>/5 sao</span>
                    <p><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                    <span class="date"><i class="fas fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($review['created_at'])); ?></span>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p><i class="fas fa-comment-slash"></i> Chưa có đánh giá nào cho sản phẩm này.</p>
        <?php endif; ?>

        <div class="review-form">
            <h4><i class="fas fa-edit"></i> Gửi đánh giá của bạn</h4>
            <?php if (isset($_SESSION['user_id'])): ?>
                <form action="product.php?id=<?php echo $product_id; ?>" method="POST">
                    <label for="rating"><i class="fas fa-star"></i> Điểm đánh giá:</label>
                    <select id="rating" name="rating" required>
                        <option value="">Chọn điểm...</option>
                        <option value="5" <?php echo (isset($_POST['rating']) && $_POST['rating'] == 5) ? 'selected' : ''; ?>>5 sao - Rất tốt</option>
                        <option value="4" <?php echo (isset($_POST['rating']) && $_POST['rating'] == 4) ? 'selected' : ''; ?>>4 sao - Tốt</option>
                        <option value="3" <?php echo (isset($_POST['rating']) && $_POST['rating'] == 3) ? 'selected' : ''; ?>>3 sao - Khá</option>
                        <option value="2" <?php echo (isset($_POST['rating']) && $_POST['rating'] == 2) ? 'selected' : ''; ?>>2 sao - Tạm được</option>
                        <option value="1" <?php echo (isset($_POST['rating']) && $_POST['rating'] == 1) ? 'selected' : ''; ?>>1 sao - Kém</option>
                    </select>

                    <label for="comment"><i class="fas fa-comment"></i> Nội dung đánh giá:</label>
                    <textarea id="comment" name="comment" rows="5" required><?php echo isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : ''; ?></textarea>

                    <input type="submit" name="submit_review" value="Gửi Đánh Giá">
                </form>
            <?php else: ?>
                <p><i class="fas fa-sign-in-alt"></i> Vui lòng <a href="login.php">đăng nhập</a> để gửi đánh giá.</p>
            <?php endif; ?>
        </div>
    </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>