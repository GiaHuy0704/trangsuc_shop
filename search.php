<?php
require_once 'includes/db.php';
include_once 'includes/header.php';

$search_query = isset($_GET['query']) ? trim($_GET['query']) : '';
$products = [];

if (!empty($search_query)) {
    // Tìm kiếm sản phẩm theo tên hoặc mô tả
    $search_param = '%' . $search_query . '%';
    $stmt = $conn->prepare("SELECT id, name, price, image FROM products WHERE name LIKE ? OR description LIKE ? ORDER BY created_at DESC");
    $stmt->bind_param("ss", $search_param, $search_param);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
    $stmt->close();
}
?>

<h2>Kết Quả Tìm Kiếm cho "<?php echo htmlspecialchars($search_query); ?>"</h2>

<?php if (empty($search_query)): ?>
    <p>Vui lòng nhập từ khóa để tìm kiếm.</p>
<?php elseif (empty($products)): ?>
    <p>Không tìm thấy sản phẩm nào phù hợp với từ khóa "<?php echo htmlspecialchars($search_query); ?>".</p>
<?php else: ?>
    <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <div class="product-item">
                <a href="product.php?id=<?php echo $product['id']; ?>">
                    <img src="images/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                </a>
                <p class="price"><?php echo number_format($product['price'], 0, ',', '.'); ?> VNĐ</p>
                <a href="product.php?id=<?php echo $product['id']; ?>" class="details-btn">Xem chi tiết</a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>