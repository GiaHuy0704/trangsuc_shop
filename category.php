<?php
require_once 'includes/db.php';
include_once 'includes/header.php'; // header.php đã chứa session_start() và khởi tạo giỏ hàng

// Lấy category_id từ URL
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category_name = "Tất cả sản phẩm";
$products = [];

// --- Xử lý bộ lọc ---
$min_price = 0; // Khởi tạo giá trị tối thiểu
$max_price = PHP_FLOAT_MAX; // Khởi tạo giá trị tối đa rất lớn
$price_range_selected = isset($_GET['price_range']) ? $_GET['price_range'] : 'all'; // Lấy giá trị từ select box

switch ($price_range_selected) {
    case 'under_1m':
        $min_price = 0;
        $max_price = 1000000;
        break;
    case '1m_5m':
        $min_price = 1000000;
        $max_price = 5000000;
        break;
    case 'over_5m':
        $min_price = 5000000;
        $max_price = PHP_FLOAT_MAX;
        break;
    case 'all':
    default:
        $min_price = 0;
        $max_price = PHP_FLOAT_MAX;
        break;
}

// Xử lý giá trị search
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// --- Xử lý phân trang ---
$products_per_page = 8; // Số sản phẩm trên mỗi trang
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $products_per_page;

// Xây dựng truy vấn SQL động
$sql = "SELECT id, name, price, image FROM products WHERE 1=1";
$params = [];
$types = "";

if ($category_id > 0) {
    // Lấy tên danh mục
    $stmt_cat = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $stmt_cat->bind_param("i", $category_id);
    $stmt_cat->execute();
    $result_cat = $stmt_cat->get_result();
    if ($result_cat->num_rows > 0) {
        $cat_row = $result_cat->fetch_assoc();
        $category_name = htmlspecialchars($cat_row['name']);
    }
    $stmt_cat->close();

    $sql .= " AND category_id = ?";
    $params[] = $category_id;
    $types .= "i";
}

// Thêm điều kiện tìm kiếm
if (!empty($search_query)) {
    $sql .= " AND name LIKE ?";
    $params[] = '%' . $search_query . '%';
    $types .= "s";
}

// Thêm điều kiện lọc giá
// Kiểm tra nếu min_price không phải 0 hoặc max_price không phải PHP_FLOAT_MAX thì mới thêm điều kiện
if (!($min_price == 0 && $max_price == PHP_FLOAT_MAX)) {
    $sql .= " AND price BETWEEN ? AND ?";
    $params[] = $min_price;
    $params[] = $max_price;
    $types .= "dd"; // 'd' cho float/double
}

// Sắp xếp sản phẩm
$sql .= " ORDER BY created_at DESC";

// --- Đếm tổng số sản phẩm cho phân trang ---
$count_sql = "SELECT COUNT(*) AS total_products FROM products WHERE 1=1";
$count_params = [];
$count_types = "";

if ($category_id > 0) {
    $count_sql .= " AND category_id = ?";
    $count_params[] = $category_id;
    $count_types .= "i";
}
if (!empty($search_query)) {
    $count_sql .= " AND name LIKE ?";
    $count_params[] = '%' . $search_query . '%';
    $count_types .= "s";
}
if (!($min_price == 0 && $max_price == PHP_FLOAT_MAX)) {
    $count_sql .= " AND price BETWEEN ? AND ?";
    $count_params[] = $min_price;
    $count_params[] = $max_price;
    $count_types .= "dd";
}

$stmt_count = $conn->prepare($count_sql);
if (!empty($count_params)) {
    $stmt_count->bind_param($count_types, ...$count_params);
}
$stmt_count->execute();
$result_count = $stmt_count->get_result();
$total_products = $result_count->fetch_assoc()['total_products'];
$total_pages = ceil($total_products / $products_per_page);
$stmt_count->close();

// Thêm LIMIT và OFFSET vào truy vấn chính
$sql .= " LIMIT ? OFFSET ?";
$params[] = $products_per_page;
$params[] = $offset;
$types .= "ii";

// Chuẩn bị và thực thi truy vấn chính
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}
$stmt->close();
?>

<div class="category-page">
    <div class="container">
        <h2><i class="fas fa-tags"></i> <?php echo $category_name; ?></h2>

        <div class="filter-section">
            <h3>Bộ lọc sản phẩm</h3>
            <form action="category.php" method="GET">
                <?php if ($category_id > 0): ?>
                    <input type="hidden" name="id" value="<?php echo $category_id; ?>">
                <?php endif; ?>
                <?php if (!empty($search_query)): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search_query); ?>">
                <?php endif; ?>

                <div class="filter-group">
                    <label for="price_range">Mức giá:</label>
                    <select name="price_range" id="price_range">
                        <option value="all" <?php echo ($price_range_selected == 'all') ? 'selected' : ''; ?>>Tất cả</option>
                        <option value="under_1m" <?php echo ($price_range_selected == 'under_1m') ? 'selected' : ''; ?>>Dưới 1.000.000 VNĐ</option>
                        <option value="1m_5m" <?php echo ($price_range_selected == '1m_5m') ? 'selected' : ''; ?>>1.000.000 - 5.000.000 VNĐ</option>
                        <option value="over_5m" <?php echo ($price_range_selected == 'over_5m') ? 'selected' : ''; ?>>Trên 5.000.000 VNĐ</option>
                    </select>
                </div>
                <button type="submit" class="btn-primary">
                    <i class="fas fa-filter"></i> Áp dụng lọc
                </button>
            </form>
        </div>

        <div class="product-grid">
            <?php
            if (!empty($products)) {
                foreach ($products as $product) {
                    ?>
                    <div class="product-item">
                        <a href="product.php?id=<?php echo $product['id']; ?>">
                            <img src="images/<?php echo htmlspecialchars($product['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                                 onerror="this.src='img/image.png'; this.onerror=null;">
                            <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        </a>
                        <p class="price"><?php echo number_format($product['price'], 0, ',', '.'); ?> VNĐ</p>
                        <a href="product.php?id=<?php echo $product['id']; ?>" class="details-btn">
                            <i class="fas fa-eye"></i> Xem chi tiết
                        </a>
                        <form action="add_to_cart.php" method="post">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="quantity" value="1">
                            <button type="submit" class="btn-primary">
                                <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                            </button>
                        </form>
                    </div>
                    <?php
                }
            } else {
                echo '<div class="no-products"><p>Không có sản phẩm nào phù hợp với lựa chọn của bạn.</p></div>';
            }
            ?>
        </div>

        <?php if ($total_pages > 1): ?>
            <div class="pagination">
                <?php
                // Xây dựng lại các tham số URL hiện có (trừ 'page')
                $current_params = $_GET;
                unset($current_params['page']);
                $base_url = 'category.php?' . http_build_query($current_params);

                if ($current_page > 1) {
                    echo '<a href="' . $base_url . '&page=' . ($current_page - 1) . '"><i class="fas fa-chevron-left"></i> Trước</a>';
                }

                for ($i = 1; $i <= $total_pages; $i++) {
                    $class = ($i == $current_page) ? 'current-page' : '';
                    echo '<a class="' . $class . '" href="' . $base_url . '&page=' . $i . '">' . $i . '</a>';
                }

                if ($current_page < $total_pages) {
                    echo '<a href="' . $base_url . '&page=' . ($current_page + 1) . '">Sau <i class="fas fa-chevron-right"></i></a>';
                }
                ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include_once 'includes/footer.php'; ?>