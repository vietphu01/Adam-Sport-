<?php
session_start();

if (!isset($_SESSION['last_order']) && !isset($_GET['order_id'])) {
    header('Location: cart.php');
    exit;
}

try {
    require_once 'config/database.php';
    $db = connectDB();
    
    $order_id = $_GET['order_id'] ?? $_SESSION['last_order']['order_id'] ?? 0;
    
    // Lấy thông tin đơn hàng
    $stmt = $db->prepare("
        SELECT o.*, c.customer_email, c.customer_address 
        FROM orders o
        LEFT JOIN customers c ON o.customer_id = c.id
        WHERE o.id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order && isset($_SESSION['last_order'])) {
        // Nếu không tìm thấy trong DB, sử dụng session data
        $order = $_SESSION['last_order'];
        $order['id'] = $order_id;
    }
    
    // Lấy chi tiết đơn hàng
    $stmt_items = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $stmt_items->execute([$order_id]);
    $items = $stmt_items->fetchAll();
    
} catch (Exception $e) {
    // Nếu có lỗi, vẫn hiển thị thông tin từ session
    if (isset($_SESSION['last_order'])) {
        $order = $_SESSION['last_order'];
        $items = [];
    } else {
        die("Error: " . $e->getMessage());
    }
}

// Tạo order_code từ order_id
$order_code = 'ORD' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt hàng thành công - Adam Sport</title>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', sans-serif;
            padding: 20px;
        }
        
        .success-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 700px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
            animation: fadeIn 0.5s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .success-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .success-icon {
            color: #10b981;
            font-size: 5rem;
            margin-bottom: 20px;
        }
        
        .order-details {
            background: #f8fafc;
            border-radius: 12px;
            padding: 25px;
            margin: 25px 0;
        }
        
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            padding-bottom: 12px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .detail-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .detail-label {
            color: #64748b;
            font-weight: 500;
        }
        
        .detail-value {
            color: #1e293b;
            font-weight: 600;
            text-align: right;
        }
        
        .order-items {
            margin-top: 25px;
        }
        
        .item-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        
        .btn {
            flex: 1;
            padding: 15px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: all 0.3s ease;
        }
        
        .btn:hover {
            transform: translateY(-2px);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: #1e293b;
        }
        
        .btn-primary:hover {
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary:hover {
            background: #cbd5e1;
        }
        
        .whatsapp-btn {
            background: #25D366;
            color: white;
            margin-top: 15px;
        }
        
        .whatsapp-btn:hover {
            background: #128C7E;
            box-shadow: 0 8px 25px rgba(37, 211, 102, 0.3);
        }
    </style>
</head>
<body>
    <div class="success-card">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 style="color: #1e293b; margin-bottom: 10px;">Đặt hàng thành công!</h1>
            <p style="color: #64748b; font-size: 1.1rem;">Cảm ơn bạn đã mua sắm tại Adam Sport</p>
        </div>
        
        <div class="order-details">
            <div class="detail-row">
                <span class="detail-label">Mã đơn hàng:</span>
                <span class="detail-value">#<?php echo $order_code; ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Khách hàng:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['customer_name'] ?? $order['customer_name'] ?? ''); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Số điện thoại:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['customer_phone'] ?? $order['customer_phone'] ?? ''); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['customer_email'] ?? $order['customer_email'] ?? ''); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Tổng tiền:</span>
                <span class="detail-value" style="color: #10b981; font-size: 1.2rem;">
                    <?php echo number_format($order['total_amount'] ?? 0, 0, ',', '.'); ?> ₫
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Phương thức thanh toán:</span>
                <span class="detail-value">
                    <?php 
                    $payment_methods = [
                        'cod' => 'Tiền mặt khi nhận hàng',
                        'banking' => 'Chuyển khoản ngân hàng',
                        'momo' => 'Ví MoMo'
                    ];
                    echo $payment_methods[$order['payment_method'] ?? 'cod'] ?? 'Tiền mặt';
                    ?>
                </span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Trạng thái:</span>
                <span class="detail-value" style="color: #f59e0b;">
                    <i class="fas fa-clock"></i> Đang xử lý
                </span>
            </div>
            <?php if (!empty($order['notes'])): ?>
            <div class="detail-row">
                <span class="detail-label">Ghi chú:</span>
                <span class="detail-value"><?php echo htmlspecialchars($order['notes']); ?></span>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($items)): ?>
        <div class="order-items">
            <h3 style="color: #1e293b; margin-bottom: 15px;">Chi tiết đơn hàng:</h3>
            <?php foreach ($items as $item): ?>
            <div class="item-row">
                <span><?php echo htmlspecialchars($item['product_name']); ?> x <?php echo $item['quantity']; ?></span>
                <span style="font-weight: 600;"><?php echo number_format($item['subtotal'], 0, ',', '.'); ?> ₫</span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <div class="btn-group">
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-home"></i> Về trang chủ
            </a>
            <a href="products.php" class="btn btn-primary">
                <i class="fas fa-shopping-cart"></i> Tiếp tục mua sắm
            </a>
        </div>
        
        <!-- Nút hỗ trợ qua WhatsApp -->
        <a href="https://wa.me/84901234567?text=Tôi%20vừa%20đặt%20đơn%20hàng%20<?php echo $order_code; ?>%20cần%20hỗ%20trợ" 
           target="_blank" 
           class="btn whatsapp-btn">
            <i class="fab fa-whatsapp"></i> Hỗ trợ qua WhatsApp
        </a>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
</body>
</html>