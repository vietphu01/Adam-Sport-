<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// LẤY TẤT CẢ SẢN PHẨM TỪ DATABASE
try {
    $db = connectDB();
    
    // Lấy danh mục cho filter
    $category_stmt = $db->query("SELECT id, name FROM categories ORDER BY name");
    $categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy sản phẩm
    $category_filter = $_GET['category'] ?? '';
    if ($category_filter && is_numeric($category_filter)) {
        $stmt = $db->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.is_active = 1 AND p.category_id = ?
            ORDER BY p.created_at DESC
        ");
        $stmt->execute([$category_filter]);
    } else {
        $stmt = $db->query("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.is_active = 1
            ORDER BY p.created_at DESC
        ");
    }
    
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    $products = [];
    $categories = [];
    error_log("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sản phẩm - Adam Sport</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1>🏸 Adam Sport</h1>
            </div>
            <nav class="nav">
                <a href="index.php" class="nav-link">Trang chủ</a>
                <a href="products.php" class="nav-link active">Sản phẩm</a>
                <a href="cart.php" class="nav-link cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    Giỏ hàng <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                </a>
            </nav>
        </div>
    </header>

    <div class="container">
        <h1>Tất cả sản phẩm</h1>
        
        <div class="products-filter">
            <a href="products.php" class="filter-btn <?php echo empty($category_filter) ? 'active' : ''; ?>">
                Tất cả
            </a>
            <?php foreach ($categories as $category): ?>
            <a href="products.php?category=<?php echo $category['id']; ?>" 
               class="filter-btn <?php echo $category_filter == $category['id'] ? 'active' : ''; ?>">
                <?php echo htmlspecialchars($category['name']); ?>
            </a>
            <?php endforeach; ?>
        </div>

        <div class="products-grid">
            <?php if (!empty($products)): ?>
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <img src="<?php echo $product['image_url']; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         onerror="this.src='assets/images/products/default.jpg'">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="product-description"><?php echo htmlspecialchars($product['description'] ?? $product['name']); ?></p>
                    <p class="product-price"><?php echo number_format($product['price']); ?> VNĐ</p>
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                        <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                        <input type="hidden" name="product_image" value="<?php echo $product['image_url']; ?>">
                        <button type="submit" name="add_to_cart" class="btn btn-add-cart">
                            <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="grid-column: 1 / -1; text-align: center; padding: 3rem;">
                    <i class="fas fa-search fa-3x" style="color: #bdc3c7; margin-bottom: 1rem;"></i>
                    <h3>Không tìm thấy sản phẩm</h3>
                    <p>Không có sản phẩm nào trong danh mục này.</p>
                    <a href="products.php" class="btn btn-primary">Xem tất cả sản phẩm</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

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


    <script src="assets/js/script.js"></script>
</body>
</html>