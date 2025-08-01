<link rel="stylesheet" href="style.css">
<script src="script.js" defer></script>
<?php
require_once 'trangsuc_shop/includes/db.php'; // Chú ý dùng dấu gạch chéo xuôi (/)
include_once 'includes/header.php';

// Lấy sản phẩm mới nhất (ví dụ 8 sản phẩm)
$sql = "SELECT p.*, c.name AS category_name FROM products p JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC LIMIT 8";
$result = $conn->query($sql);

// Lấy các khuyến mãi đang hoạt động, không cần mã code (code IS NULL)
// và áp dụng cho 'all' (hoặc bạn có thể hiển thị tất cả và ghi rõ điều kiện)
$sql_active_promotions = "SELECT * FROM promotions WHERE is_active = TRUE AND code IS NULL AND start_date <= NOW() AND end_date >= NOW() ORDER BY created_at DESC LIMIT 3"; // Giới hạn 3 khuyến mãi để hiển thị nổi bật
$result_active_promotions = $conn->query($sql_active_promotions);
?>

<!-- Hero Banner -->
<section class="banner-lon">
    <div class="banner-carousel">
    <div class="banner-slide active" style="background-image: url('img/banner_trangchu.jpg');">
    </div>
    <div class="banner-slide" style="background-image: url('img/banner_trangchu2.jpg');">
    </div>
    <div class="banner-slide" style="background-image: url('img/banner_trangchu3.jpg');">
    </div>
    <!-- Add more slides as needed -->
    <div class="banner-indicators">
        <span class="indicator active" data-slide="0"></span>
        <span class="indicator" data-slide="1"></span>
        <span class="indicator" data-slide="2"></span>
    </div>
</div>
</section>

<hr>

<?php if ($result_active_promotions->num_rows > 0): ?>
<section class="promotions-section container">
    <h2>Ưu Đãi Đặc Biệt</h2>
    <div class="promotion-list">
        <?php while($promo = $result_active_promotions->fetch_assoc()): ?>
            <div class="promotion-item">
                <h3><?php echo htmlspecialchars($promo['name']); ?></h3>
                <p><?php echo htmlspecialchars($promo['description']); ?></p>
                <?php
                if ($promo['type'] === 'percentage') {
                    echo "<p class='promo-value'>GIẢM " . htmlspecialchars($promo['value']) . "%</p>";
                } elseif ($promo['type'] === 'fixed_amount') {
                    echo "<p class='promo-value'>GIẢM " . number_format($promo['value'], 0, ',', '.') . " VNĐ</p>";
                } elseif ($promo['type'] === 'free_shipping') {
                    echo "<p class='promo-value'>MIỄN PHÍ VẬN CHUYỂN</p>";
                }
                if ($promo['min_order_amount'] > 0) {
                    echo "<p class='promo-condition'>Áp dụng cho đơn hàng từ " . number_format($promo['min_order_amount'], 0, ',', '.') . " VNĐ</p>";
                }
                ?>
                <span class="promo-date">Kết thúc: <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?></span>
            </div>
        <?php endwhile; ?>
    </div>
</section>
<hr>
<?php endif; ?>

<section id="featured-products">
    <h2>Sản phẩm nổi bật</h2>
    <div class="product-grid">
        <?php
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                ?>
                <div class="product-item">
                    <a href="product.php?id=<?php echo $row['id']; ?>">
                        <img src="images/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>">
                        <h3><?php echo $row['name']; ?></h3>
                    </a>
                    <p class="price"><?php echo number_format($row['price'], 0, ',', '.'); ?> VNĐ</p>
                    <a href="product.php?id=<?php echo $row['id']; ?>" class="details-btn">Xem chi tiết</a>
                </div>
                <?php
            }
        } else {
            echo "<p>Chưa có sản phẩm nào.</p>";
        }
        ?>
    </div>
</section>

<script>
// Banner carousel auto-slide functionality
document.addEventListener('DOMContentLoaded', function() {
    let currentBanner = 0;
    const slides = document.querySelectorAll('.banner-slide');
    const indicators = document.querySelectorAll('.banner-indicators .indicator');
    
    console.log('Found slides:', slides.length);
    console.log('Found indicators:', indicators.length);
    
    function showBanner(idx) {
        console.log('Showing banner:', idx);
        slides.forEach((slide, i) => {
            slide.classList.toggle('active', i === idx);
            if (indicators[i]) {
                indicators[i].classList.toggle('active', i === idx);
            }
        });
        currentBanner = idx;
    }
    
    function nextBanner() {
        let next = (currentBanner + 1) % slides.length;
        console.log('Next banner:', next);
        showBanner(next);
    }
    
    // Auto-slide every 4 seconds
    let bannerInterval = setInterval(nextBanner, 4000);
    console.log('Banner interval set to 4 seconds');
    
    // Click indicators to change banner
    indicators.forEach((ind, i) => {
        ind.addEventListener('click', () => {
            console.log('Indicator clicked:', i);
            showBanner(i);
            clearInterval(bannerInterval);
            bannerInterval = setInterval(nextBanner, 4000);
        });
    });
    
    // Initialize banner
    showBanner(0);
});
</script>

<?php include_once 'includes/footer.php'; ?>