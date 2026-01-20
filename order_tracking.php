<?php
session_start();

// ==============================================
// KẾT NỐI DATABASE - ĐƠN GIẢN
// ==============================================
$host = 'localhost';
$dbname = 'sport_shop';  // Thay tên database của bạn
$username = 'root';     // Thay username của bạn
$password = '';         // Thay password của bạn

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối database. Vui lòng thử lại sau!");
}

// ==============================================
// XỬ LÝ TRA CỨU BẰNG SĐT
// ==============================================
$phone = '0765701720';
$orders = [];
$error = '';
$search_performed = true;
        try {
            // Tìm tất cả đơn hàng của số điện thoại này
            $stmt = $db->prepare("
                SELECT * FROM orders 
                WHERE customer_phone = ? 
                ORDER BY created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$phone]);
            $orders = $stmt->fetchAll();
            
        } catch (Exception $e) {
            $error = "Lỗi hệ thống: " . $e->getMessage();
        }

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tra Đơn Hàng Bằng SĐT - Adam Sport</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding: 30px;
            background: rgba(255,255,255,0.1);
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }
        
        .header h1 {
            color: white;
            font-size: 2.2rem;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
        }
        
        .header p {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
        }
        
        .search-box {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-bottom: 30px;
        }
        
        .search-form {
            max-width: 500px;
            margin: 0 auto;
        }
        
        .form-title {
            color: #2c3e50;
            margin-bottom: 25px;
            text-align: center;
            font-size: 1.3rem;
        }
        
        .phone-input-group {
            position: relative;
            margin-bottom: 20px;
        }
        
        .phone-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 1.2rem;
        }
        
        .phone-input {
            width: 100%;
            padding: 15px 15px 15px 50px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 1.1rem;
            transition: all 0.3s;
        }
        
        .phone-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.2);
        }
        
        .btn-search {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        
        .btn-search:hover {
            transform: translateY(-2px);
        }
        
        .error-message {
            background: #fee;
            color: #e74c3c;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #c3e6cb;
            text-align: center;
        }
        
        /* Hiển thị kết quả */
        .results-section {
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .results-title {
            color: #2c3e50;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .results-count {
            background: #667eea;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        
        .no-orders {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }
        
        .no-orders i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .orders-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .order-card {
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            transition: all 0.3s;
            position: relative;
        }
        
        .order-card:hover {
            border-color: #667eea;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.1);
            transform: translateY(-3px);
        }
        
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 15px;
        }
        
        .order-code {
            font-family: monospace;
            font-weight: bold;
            color: #667eea;
            font-size: 1.1rem;
        }
        
        .order-date {
            color: #666;
            font-size: 0.9rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-shipping { background: #d1ecf1; color: #0c5460; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .order-info {
            margin-bottom: 15px;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 8px;
        }
        
        .info-label {
            width: 100px;
            color: #666;
            font-size: 0.9rem;
        }
        
        .info-value {
            flex: 1;
            color: #333;
            font-weight: 500;
        }
        
        .order-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-detail {
            flex: 1;
            padding: 8px 15px;
            background: #f8f9fa;
            color: #495057;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-detail:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }
        
        .btn-track {
            flex: 1;
            padding: 8px 15px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            text-align: center;
            font-size: 0.9rem;
            transition: all 0.3s;
        }
        
        .btn-track:hover {
            background: #5a67d8;
        }
        
        .order-total {
            border-top: 1px solid #eee;
            padding-top: 10px;
            margin-top: 10px;
            text-align: right;
            font-weight: bold;
            color: #e74c3c;
        }
        
        .customer-info {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .customer-info h3 {
            margin-bottom: 15px;
            color: #2c3e50;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 30px;
        }
        
        .page-btn {
            padding: 8px 15px;
            border: 1px solid #dee2e6;
            background: white;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .page-btn:hover {
            background: #f8f9fa;
        }
        
        .page-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        
        @media (max-width: 768px) {
            .orders-grid {
                grid-template-columns: 1fr;
            }
            
            .header h1 {
                font-size: 1.8rem;
                flex-direction: column;
                gap: 10px;
            }
            
            .results-header {
                flex-direction: column;
                gap: 15px;
                text-align: center;
            }
            
            .order-actions {
                flex-direction: column;
            }
        }
        .nav-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: #28196dff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-weight: 500;
            transition: all 0.2s;
            margin-bottom: 20px;
        }
        
        .nav-btn:hover {
            background: #134e76ff;
            transform: translateY(-2px);
        }
        
        .nav-btn.primary {
            background: #28a745;
        }
        
        .nav-btn.primary:hover {
            background: #218838;
        }
        
        .nav-btn.secondary {
            background: #6c757d;
        }
        
        .nav-btn.secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <a style="float-left" href="index.php" class="nav-btn">
                <i class="fas fa-arrow-left"></i> Quay lại Trang chủ
        </a>
       
        <?php if ($search_performed && empty($error)): ?>
            <!-- Results Section -->
            <div class="results-section">
                <?php if (!empty($orders)): ?>
                    <?php
                    // Lấy thông tin khách hàng từ đơn hàng đầu tiên
                    $customer_name = $orders[0]['customer_name'];
                    $customer_email = $orders[0]['customer_email'] ?? '';
                    $customer_address = $orders[0]['customer_address'] ?? '';
                    ?>
                    
                    <!-- Customer Info -->
                    <div class="customer-info">
                        <h3><i class="fas fa-user"></i> Thông tin khách hàng</h3>
                        <div class="info-grid">
                            <div>
                                <strong>Số điện thoại:</strong><br>
                                <span style="font-size: 1.2rem; color: #2c3e50;"><?php echo htmlspecialchars($phone); ?></span>
                            </div>
                            <div>
                                <strong>Họ tên:</strong><br>
                                <span><?php echo htmlspecialchars($customer_name); ?></span>
                            </div>
                            <?php if ($customer_email): ?>
                            <div>
                                <strong>Email:</strong><br>
                                <span><?php echo htmlspecialchars($customer_email); ?></span>
                            </div>
                            <?php endif; ?>
                            <?php if ($customer_address): ?>
                            <div>
                                <strong>Địa chỉ:</strong><br>
                                <span><?php echo htmlspecialchars($customer_address); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Results Header -->
                    <div class="results-header">
                        <h3 class="results-title">
                            <i class="fas fa-shopping-bag"></i> 
                            Danh sách đơn hàng
                        </h3>
                        <div class="results-count">
                            <?php echo count($orders); ?> đơn hàng
                        </div>
                    </div>
                    
                    <!-- Orders Grid -->
                    <div class="orders-grid">
                        <?php foreach ($orders as $order): ?>
                        <div class="order-card">
                            <div class="order-header">
                                <div>
                                    <div class="order-code">#<?php echo str_pad($order['id'], 6, '0', STR_PAD_LEFT); ?></div>
                                    <div class="order-date">
                                        <?php echo date('d/m/Y', strtotime($order['created_at'])); ?>
                                    </div>
                                </div>
                                <div class="status-badge status-<?php echo $order['status']; ?>">
                                    <?php 
                                    $status_text = [
                                        'pending' => 'Chờ xử lý',
                                        'processing' => 'Đang xử lý',
                                        'shipping' => 'Đang giao',
                                        'delivered' => 'Đã giao',
                                        'cancelled' => 'Đã hủy'
                                    ];
                                    echo $status_text[$order['status']] ?? $order['status'];
                                    ?>
                                </div>
                            </div>
                            
                            <div class="order-info">
                                <div class="info-row">
                                    <div class="info-label">Khách hàng:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                                </div>
                                <div class="info-row">
                                    <div class="info-label">Điện thoại:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($order['customer_phone']); ?></div>
                                </div>
                                <?php if (!empty($order['customer_email'])): ?>
                                <div class="info-row">
                                    <div class="info-label">Email:</div>
                                    <div class="info-value"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="order-total">
                                Tổng tiền: <?php echo number_format($order['total_amount']); ?> ₫
                            </div>
                            
                            <div class="order-actions">
                                <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn-detail">
                                    <i class="fas fa-eye"></i> Chi tiết
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                    </div>
                    
                <?php else: ?>
                    <!-- No Orders Found -->
                    <div class="no-orders">
                        <i class="fas fa-search"></i>
                        <h3>Không tìm thấy đơn hàng</h3>
                        <p>Không có đơn hàng nào được tìm thấy với số điện thoại: <strong><?php echo htmlspecialchars($phone); ?></strong></p>
                        <div style="margin-top: 20px; color: #666;">
                            <p>Có thể:</p>
                            <ul style="text-align: left; margin: 15px 0; padding-left: 20px;">
                                <li>Số điện thoại chưa từng đặt hàng</li>
                                <li>Bạn đã nhập sai số điện thoại</li>
                                <li>Đơn hàng đã quá cũ hoặc đã xóa</li>
                            </ul>
                            <p>Vui lòng kiểm tra lại hoặc liên hệ hotline: <strong>0788 500 585</strong></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
    // Format số điện thoại khi nhập
    document.querySelector('input[name="phone"]').addEventListener('input', function() {
        this.value = this.value.replace(/\D/g, '');
    });
    
    // Auto focus vào ô nhập
    document.addEventListener('DOMContentLoaded', function() {
        const phoneInput = document.querySelector('input[name="phone"]');
        if (phoneInput && !phoneInput.value) {
            phoneInput.focus();
        }
    });
    
    // Share link tra cứu
    function shareTrackingLink() {
        const phone = document.querySelector('input[name="phone"]').value;
        if (phone) {
            const url = window.location.href.split('?')[0] + '?phone=' + phone;
            navigator.clipboard.writeText(url).then(() => {
                alert('Đã sao chép link tra cứu! Bạn có thể chia sẻ link này.');
            });
        }
    }
    </script>
</body>
</html>