<?php
require_once 'includes/db.php'; // Bao gồm file kết nối cơ sở dữ liệu
session_start(); // Bắt đầu session (nếu cần cho thông báo hoặc thông tin người dùng)
include_once 'includes/header.php'; // Bao gồm header của trang web

// Lấy danh sách các khuyến mãi đang hoạt động
$promotions = [];
// Chỉ lấy các khuyến mãi đang hoạt động và có thể hiển thị công khai
$stmt = $conn->prepare("SELECT name, code, type, value, start_date, end_date, min_amount, description FROM promotions WHERE is_active = TRUE AND start_date <= NOW() AND end_date >= NOW() AND is_public = TRUE ORDER BY end_date ASC");
// Giả định bạn có một cột 'is_public' trong bảng promotions để kiểm soát xem mã có được hiển thị cho người dùng thường hay không.
// Nếu chưa có, bạn cần thêm cột này vào bảng promotions (kiểu BOOLEAN hoặc TINYINT(1) với DEFAULT 1)

$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $promotions[] = $row;
}
$stmt->close();
?>

<div class="container main-content">
    <h2>Các Chương Trình Khuyến Mãi Hiện Có</h2>

    <?php if (empty($promotions)): ?>
        <p>Hiện tại chưa có chương trình khuyến mãi nào. Vui lòng quay lại sau!</p>
    <?php else: ?>
        <div class="promotions-list">
            <?php foreach ($promotions as $promo): ?>
                <div class="promotion-card">
                    <h3><?php echo htmlspecialchars($promo['name']); ?></h3>
                    <p>Mô tả: <?php echo htmlspecialchars($promo['description'] ?? 'Không có mô tả.'); ?></p>
                    <p>
                        Loại:
                        <?php
                        if ($promo['type'] === 'percentage') {
                            echo "Giảm " . htmlspecialchars($promo['value']) . "%";
                        } elseif ($promo['type'] === 'fixed_amount') {
                            echo "Giảm " . number_format($promo['value'], 0, ',', '.') . " VNĐ";
                        } elseif ($promo['type'] === 'free_shipping') {
                            echo "Miễn phí vận chuyển";
                        }
                        ?>
                    </p>
                    <?php if ($promo['min_amount'] > 0): ?>
                        <p>Đơn hàng tối thiểu: <?php echo number_format($promo['min_amount'], 0, ',', '.'); ?> VNĐ</p>
                    <?php endif; ?>
                    <p>Thời gian áp dụng: từ <?php echo date('d/m/Y', strtotime($promo['start_date'])); ?> đến <?php echo date('d/m/Y', strtotime($promo['end_date'])); ?></p>
                    
                    <div class="coupon-code-box">
                        <span class="code-display" id="code-<?php echo $promo['code']; ?>"><?php echo htmlspecialchars($promo['code']); ?></span>
                        <button class="copy-button" data-clipboard-target="#code-<?php echo $promo['code']; ?>">Sao chép</button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include_once 'includes/footer.php'; // Bao gồm footer của trang web ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/clipboard.js/2.0.8/clipboard.min.js"></script>
<script>
    // Khởi tạo ClipboardJS
    new ClipboardJS('.copy-button');

    // Thêm feedback cho người dùng khi sao chép thành công
    document.querySelectorAll('.copy-button').forEach(button => {
        button.addEventListener('click', function() {
            const originalText = this.textContent;
            this.textContent = 'Đã sao chép!';
            setTimeout(() => {
                this.textContent = originalText;
            }, 1500); // Đặt lại sau 1.5 giây
        });
    });
</script>

<style>
    /* CSS cho trang khuyến mãi */
    .promotions-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .promotion-card {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        background-color: #fff;
    }

    .promotion-card h3 {
        color: #c08080;
        margin-top: 0;
        margin-bottom: 10px;
    }

    .promotion-card p {
        margin-bottom: 5px;
        line-height: 1.5;
    }

    .coupon-code-box {
        display: flex;
        align-items: center;
        margin-top: 15px;
        background-color: #f2f2f2;
        border: 1px dashed #c08080;
        border-radius: 5px;
        padding: 10px;
    }

    .code-display {
        flex-grow: 1;
        font-family: monospace;
        font-size: 1.1em;
        font-weight: bold;
        color: #333;
        padding-right: 10px;
        white-space: nowrap; /* Ngăn ngắt dòng */
        overflow: hidden;    /* Ẩn phần bị tràn */
        text-overflow: ellipsis; /* Hiển thị dấu ba chấm nếu tràn */
    }

    .copy-button {
        background-color: #c08080;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.9em;
        transition: background-color 0.3s ease;
    }

    .copy-button:hover {
        background-color: #a06a6a;
    }
</style>