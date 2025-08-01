<?php
require_once 'includes/db.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Vui lòng điền đầy đủ thông tin!';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email không hợp lệ!';
    } else {
        // In a real application, you would save this to a database
        // For demo purposes, we'll just show a success message
        $success = 'Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất có thể.';
        
        // Clear form data
        $name = $email = $subject = $message = '';
    }
}

// Include the original header
require_once 'includes/header.php';
?>



<!-- Contact Hero -->
<section class="contact-hero">
    <div class="container">
        <h1><i class="fas fa-envelope"></i> Liên Hệ Với Chúng Tôi</h1>
        <p class="lead">Chúng tôi luôn sẵn sàng hỗ trợ bạn mọi lúc, mọi nơi</p>
    </div>
</section>

<!-- Contact Information -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <div class="col-lg-4 col-md-4 col-sm-6 mb-4">
                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <i class="fas fa-map-marker-alt"></i>
                    </div>
                    <h5>Địa Chỉ</h5>
                    <p>233A Phan Văn Trị, p.11, Q. Bình Thạnh, TP. Hồ Chí Minh</p>
                    <p>69/68 Đặng Thùy Trâm, p.13, Q. Bình Thạnh, TP. Hồ Chí Minh</p>
                    <p>45 Nguyễn Khắc Nhu, p. Cô Giang, Q. 1, TP. Hồ Chí Minh</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6 mb-4">
                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <i class="fas fa-phone"></i>
                    </div>
                    <h5>Điện Thoại</h5>
                    <p>+84 123 456 789<br>+84 987 654 321</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-4 col-sm-6 mb-4">
                <div class="contact-info-card">
                    <div class="contact-info-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h5>Email</h5>
                    <p>info@cartier.com<br>support@cartier.com</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Form and Map -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="row">
            <div class="col-lg-8">
                <div class="contact-form-card">
                    <h3 class="mb-4"><i class="fas fa-paper-plane"></i> Gửi Tin Nhắn</h3>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="name" class="form-label">Họ và tên *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name ?? ''); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject" class="form-label">Tiêu đề *</label>
                            <input type="text" class="form-control" id="subject" name="subject" value="<?php echo htmlspecialchars($subject ?? ''); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="message" class="form-label">Nội dung tin nhắn *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-contact">
                            <i class="fas fa-paper-plane"></i> Gửi Tin Nhắn
                        </button>
                    </form>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="map-container">
                    <iframe 
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3919.424177981303!2d106.6983153152608!3d10.776755992319!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31752f46f64b933f%3A0xf8a6e5b2a5a4f1f4!2zVHLGsOG7nW5nIMSQ4bqhaSBo4buNYyBDw7RuZyBuZ2jhu4cgVGjDtG5nIHRpbiB2aWV0!5e0!3m2!1svi!2s!4v1234567890"
                        width="100%" 
                        height="400" 
                        style="border:0;" 
                        allowfullscreen="" 
                        loading="lazy" 
                        referrerpolicy="no-referrer-when-downgrade">
                    </iframe>
                </div>
                
                <div class="card mt-3 working-hours-card">
                    <div class="card-body">
                        <h6 class="card-title"><i class="fas fa-clock"></i> Giờ Làm Việc</h6>
                        <ul class="list-unstyled">
                            <li><strong>Thứ 2 - Thứ 6:</strong> 8:00 - 18:00</li>
                            <li><strong>Thứ 7:</strong> 8:00 - 16:00</li>
                            <li><strong>Chủ nhật:</strong> 9:00 - 15:00</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ Section -->
<section class="py-5 faq-section">
    <div class="container">
        <h3 class="text-center mb-5"><i class="fas fa-question-circle"></i> Câu Hỏi Thường Gặp</h3>
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faq1">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapse1">
                                Làm thế nào để đặt hàng?
                            </button>
                        </h2>
                        <div id="collapse1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Bạn có thể đặt hàng trực tuyến thông qua website của chúng tôi. Chỉ cần chọn sản phẩm, thêm vào giỏ hàng và làm theo các bước thanh toán.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faq2">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse2">
                                Thời gian giao hàng là bao lâu?
                            </button>
                        </h2>
                        <div id="collapse2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Thời gian giao hàng thường từ 2-5 ngày làm việc tùy thuộc vào địa điểm giao hàng và phương thức vận chuyển bạn chọn.
                            </div>
                        </div>
                    </div>
                    
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="faq3">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse3">
                                Có chính sách đổi trả không?
                            </button>
                        </h2>
                        <div id="collapse3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Chúng tôi có chính sách đổi trả trong vòng 30 ngày kể từ ngày mua hàng với điều kiện sản phẩm còn nguyên vẹn và có hóa đơn mua hàng.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
// Include the original footer
require_once 'includes/footer.php';
?>