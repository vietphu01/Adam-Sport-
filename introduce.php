<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adam Sport - Giới Thiệu</title>
     <link rel="stylesheet" href="assets/css/introduce.css">
     <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Features Section -->
    <section class="features">
        <div class="container">
            <div>
                <a  href="index.php" class="btn btn-secondary">Quay Lại Trang Chủ</a>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <i class="fas fa-shield-alt"></i>
                    <h3>Chính Hãng 100%</h3>
                    <p>Cam kết sản phẩm chính hãng với đầy đủ chứng từ</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-shipping-fast"></i>
                    <h3>Giao Hàng Nhanh</h3>
                    <p>Miễn phí vận chuyển cho đơn hàng từ 3 triệu</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-undo-alt"></i>
                    <h3>Đổi Trả Dễ Dàng</h3>
                    <p>Đổi trả trong vòng 7 ngày nếu có lỗi từ NSX</p>
                </div>
                <div class="feature-card">
                    <i class="fas fa-headset"></i>
                    <h3>Hỗ Trợ 24/7</h3>
                    <p>Đội ngũ tư vấn chuyên nghiệp, nhiệt tình</p>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about">
        <div class="container">
            <div class="about-content">
                <div class="about-text">
                    <h2>Về Adam Sport</h2>
                    <p>Adam Sport là địa chỉ tin cậy chuyên cung cấp các dụng cụ cầu lông chính hãng từ các thương hiệu hàng đầu thế giới như Yonex, Victor, Lining, Mizuno.</p>
                    <p>Với hơn 5 năm kinh nghiệm trong lĩnh vực thể thao, chúng tôi cam kết mang đến cho khách hàng những sản phẩm chất lượng nhất với giá cả cạnh tranh.</p>
                    <div class="about-features">
                        <div class="about-feature">
                            <i class="fas fa-check"></i>
                            <span>5000+ khách hàng tin tưởng</span>
                        </div>
                        <div class="about-feature">
                            <i class="fas fa-check"></i>
                            <span>Giao hàng toàn quốc</span>
                        </div>
                        <div class="about-feature">
                            <i class="fas fa-check"></i>
                            <span>Bảo hành chính hãng</span>
                        </div>
                    </div>
                </div>
                <div class="about-image">
                    <img src="https://images.unsplash.com/photo-1551632811-561732d1e306?w=500&h=400&fit=crop" alt="Adam Sport Store">
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
<section id="contact" class="contact">
    <div class="container">
        <h2>Liên Hệ Với Chúng Tôi</h2>
        
        <!-- Hiển thị thông báo -->
        <?php if (isset($_SESSION['contact_success'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $_SESSION['contact_success']; ?>
                <?php unset($_SESSION['contact_success']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['contact_errors'])): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-triangle"></i>
                <ul>
                    <?php foreach ($_SESSION['contact_errors'] as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php unset($_SESSION['contact_errors']); ?>
            </div>
        <?php endif; ?>
        
        <div class="contact-grid">
            <div class="contact-info">
                <div class="contact-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <div>
                        <h3>Địa chỉ</h3>
                        <p>123 Nguyễn Văn Linh, Quận Hải Châu, Đà Nẵng</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fas fa-phone"></i>
                    <div>
                        <h3>Điện thoại</h3>
                        <p>0788 500 585</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fas fa-envelope"></i>
                    <div>
                        <h3>Email</h3>
                        <p>info@adamsport.com</p>
                    </div>
                </div>
                <div class="contact-item">
                    <i class="fas fa-clock"></i>
                    <div>
                        <h3>Giờ làm việc</h3>
                        <p>Thứ 2 - Chủ nhật: 8:00 - 22:00</p>
                    </div>
                </div>
            </div>
            <div class="contact-form">
                <h3>Gửi tin nhắn cho chúng tôi</h3>
                <form method="POST" action="contact_process.php">
                    <div class="form-group">
                        <input type="text" name="name" placeholder="Họ và tên *" required 
                               value="<?php echo htmlspecialchars($_SESSION['contact_old_data']['name'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <input type="email" name="email" placeholder="Email *" required
                               value="<?php echo htmlspecialchars($_SESSION['contact_old_data']['email'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <input type="tel" name="phone" placeholder="Số điện thoại"
                               value="<?php echo htmlspecialchars($_SESSION['contact_old_data']['phone'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <textarea name="message" placeholder="Nội dung tin nhắn *" rows="5" required><?php echo htmlspecialchars($_SESSION['contact_old_data']['message'] ?? ''); ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-paper-plane"></i> Gửi tin nhắn
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>
    <!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section footer-about">
                <h3>THÔNG TIN CHUNG</h3>
                <p>Adam Sports là hệ thống cửa hàng cầu lông với hơn 50 chi nhánh trên toàn quốc, cung cấp sỉ và lẻ các ngữ hàng dụng cụ cầu lông từ phong trào tới chuyên nghiệp.</p>
                <p>Với sứ mệnh: "Adam Sports cam kết mang đến những sản phẩm, dịch vụ chất lượng tốt nhất phục vụ cho người chơi thể thao để nâng cao sức khỏe của chính mình."</p>
                <p>Tầm nhìn: "Trở thành nhà phân phối và sản xuất thể thao lớn nhất Việt Nam"</p>
            </div>
            
            <div class="footer-section footer-contact">
                <h3>THÔNG TIN LIÊN HỆ</h3>
                <div class="contact-info">
                    <p><strong>Hệ thống cửa hàng:</strong> 1 Super Center, 5 shop Premium và 75 cửa hàng trên toàn quốc</p>
                    <p><a href="#" class="footer-link">Xem tất cả các cửa hàng Adam Sports</a></p>
                    <p><strong>Hotline:</strong> 0788 500 585</p>
                    <p><strong>Email:</strong> info@adamsport.com</p>
                    <p><strong>Hợp tác kinh doanh:</strong> 0947 542 259 (Ms. Thảo)</p>
                    <p><strong>Hotline kỹ thuật:</strong> 0911 057 171</p>
                    <p><strong>Nhượng quyền thương hiệu:</strong> 0334 741 141 (Mr. Hậu)</p>
                    <p><strong>Than phiền dịch vụ:</strong> 0334 741 141 (Mr. Hậu)</p>
                </div>
            </div>
            
            <div class="footer-section footer-policies">
                <h3>CHÍNH SÁCH</h3>
                <ul>
                    <li><a href="#" class="footer-link">Thông tin vận chuyển và giao nhận</a></li>
                    <li><a href="#" class="footer-link">Chính sách đổi trả hoàn tiền</a></li>
                    <li><a href="#" class="footer-link">Chính sách bảo hành</a></li>
                    <li><a href="#" class="footer-link">Chính sách xử lý khiếu nại</a></li>
                    <li><a href="#" class="footer-link">Chính sách vận chuyển</a></li>
                    <li><a href="#" class="footer-link">Điều khoản sử dụng</a></li>
                    <li><a href="#" class="footer-link">Chính sách Bảo Mật Thông Tin</a></li>
                    <li><a href="#" class="footer-link">Chính sách nhượng quyền</a></li>
                </ul>
            </div>
            
            <div class="footer-section footer-guides">
                <h3>HƯỚNG DẪN</h3>
                <ul>
                    <li><a href="#" class="footer-link">Danh sách các tài khoản chính thức của các shopping hệ thống Adam Sports</a></li>
                    <li><a href="#" class="footer-link">Hướng dẫn cách chọn vợt cầu lông cho người mới chơi</a></li>
                    <li><a href="#" class="footer-link">Hướng dẫn thanh toán</a></li>
                    <li><a href="#" class="footer-link">Kiểm tra bảo hành</a></li>
                    <li><a href="#" class="footer-link">Kiểm tra đơn hàng</a></li>
                    <li><a href="#" class="footer-link">Hướng dẫn mua hàng</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-company">
            <h3>CÔNG TY TNHH ADAM SPORTS</h3>
            <p><strong>Địa chỉ:</strong> 390/2 Hà Huy Giáp, Phường Thanh Lộc, Quận 12, TP.HCM</p>
            <p><strong>Email:</strong> info@adamsport.com</p>
            <p><strong>Mã số thuế:</strong> 0314496379 do Sở KH và ĐT TP Hồ Chí Minh cấp ngày 05/07/2017</p>
            <p><strong>Giám đốc/Chủ sở hữu website:</strong> Nguyễn Phùng Hà Lan</p>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p>&copy; 2024 Adam Sport. All rights reserved.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                    <a href="#"><i class="fab fa-zalo"></i></a>
                </div>
            </div>
        </div>
    </div>
</footer>

</body>
</html>