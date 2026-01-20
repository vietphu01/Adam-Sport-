<?php
session_start();

// ==============================================
// KẾT NỐI DATABASE
// ==============================================
$host = 'localhost';
$dbname = 'sport_shop';
$username = 'root';
$password = '';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối database: " . $e->getMessage());
}

// ==============================================
// KIỂM TRA VÀ LẤY DỮ LIỆU ĐƠN HÀNG
// ==============================================
$order_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($order_id <= 0) {
    header('Location: orders.php');
    exit();
}

try {
    // 1. Lấy thông tin chính của đơn hàng (THEO CẤU TRÚC BẢNG CỦA BẠN)
    $stmt = $db->prepare("
        SELECT 
            id,
            customer_name,
            customer_phone,
            customer_email,
            customer_address,
            total_amount,
            status,
            payment_method,
            notes,
            created_at,
            updated_at
        FROM orders 
        WHERE id = ?
    ");
    $stmt->execute([$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) {
        header('Location: orders.php?error=not_found');
        exit();
    }
    
    // 2. Lấy chi tiết sản phẩm trong đơn hàng
    $items_stmt = $db->prepare("
        SELECT 
            oi.*,
            p.name as product_name,
            p.image_url as product_image,
            p.description as product_description
        FROM order_items oi
        LEFT JOIN products p ON oi.product_id = p.id
        WHERE oi.order_id = ?
        ORDER BY oi.id ASC
    ");
    $items_stmt->execute([$order_id]);
    $order_items = $items_stmt->fetchAll();
    
    // 3. Lấy lịch sử trạng thái đơn hàng (nếu có bảng này)
    $status_history = [];
    try {
        $history_stmt = $db->prepare("
            SELECT * FROM order_status_history 
            WHERE order_id = ? 
            ORDER BY created_at DESC
        ");
        $history_stmt->execute([$order_id]);
        $status_history = $history_stmt->fetchAll();
    } catch (Exception $e) {
        // Bảng có thể không tồn tại, không sao
    }
    
    // 4. Tính tổng số lượng sản phẩm
    $total_quantity = 0;
    $total_amount_calculated = 0;
    foreach ($order_items as $item) {
        $total_quantity += $item['quantity'];
        if (isset($item['product_price']) && isset($item['quantity'])) {
            $total_amount_calculated += $item['product_price'] * $item['quantity'];
        }
    }
    
} catch (PDOException $e) {
    die("Lỗi khi lấy dữ liệu: " . $e->getMessage());
}

// ==============================================
// HÀM HIỂN THỊ THÂN THIỆN
// ==============================================
function formatStatus($status) {
    // Chuẩn hóa status
    $status = strtolower(trim($status ?? ''));
    
    $statuses = [
        'pending' => ['text' => 'Chờ xử lý', 'class' => 'pending', 'icon' => 'fa-clock'],
        'processing' => ['text' => 'Đang xử lý', 'class' => 'processing', 'icon' => 'fa-cog'],
        'shipping' => ['text' => 'Đang giao hàng', 'class' => 'shipping', 'icon' => 'fa-shipping-fast'],
        'delivered' => ['text' => 'Đã giao hàng', 'class' => 'delivered', 'icon' => 'fa-check-circle'],
        'cancelled' => ['text' => 'Đã hủy', 'class' => 'cancelled', 'icon' => 'fa-times-circle'],
        'completed' => ['text' => 'Hoàn thành', 'class' => 'delivered', 'icon' => 'fa-check-circle']
    ];
    
    if (isset($statuses[$status])) {
        return $statuses[$status];
    }
    
    return ['text' => $status ?: 'Không xác định', 'class' => 'default', 'icon' => 'fa-info-circle'];
}

function formatPaymentMethod($method) {
    $method = strtolower(trim($method ?? ''));
    
    $methods = [
        'cod' => 'Thanh toán khi nhận hàng (COD)',
        'banking' => 'Chuyển khoản ngân hàng',
        'momo' => 'Ví MoMo',
        'zalopay' => 'Ví ZaloPay',
        'credit_card' => 'Thẻ tín dụng'
    ];
    
    return $methods[$method] ?? $method;
}

// Hàm tạo mã đơn hàng
function generateOrderCode($order_id) {
    return 'AD' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
}

// ==============================================
// XỬ LÝ HÀNH ĐỘNG
// ==============================================
$action_result = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['cancel_order'])) {
        try {
            $db->beginTransaction();
            
            // Cập nhật trạng thái đơn hàng (DÙNG status KHÔNG PHẢI order_status)
            $update_stmt = $db->prepare("
                UPDATE orders 
                SET status = 'cancelled', 
                    updated_at = NOW() 
                WHERE id = ? AND status IN ('pending', 'processing')
            ");
            $update_stmt->execute([$order_id]);
            
            // Thêm vào lịch sử (nếu bảng tồn tại)
            try {
                $history_stmt = $db->prepare("
                    INSERT INTO order_status_history (order_id, status, note, created_at) 
                    VALUES (?, 'cancelled', ?, NOW())
                ");
                $reason = $_POST['cancel_reason'] ?? 'Khách hàng hủy đơn';
                $history_stmt->execute([$order_id, $reason]);
            } catch (Exception $e) {
                // Bảng có thể không tồn tại
            }
            
            // Hoàn lại số lượng tồn kho
            foreach ($order_items as $item) {
                if (isset($item['product_id'])) {
                    $restock_stmt = $db->prepare("
                        UPDATE products 
                        SET stock = stock + ? 
                        WHERE id = ?
                    ");
                    $restock_stmt->execute([$item['quantity'], $item['product_id']]);
                }
            }
            
            $db->commit();
            
            header('Location: order_detail.php?id=' . $order_id . '&success=cancelled');
            exit();
            
        } catch (Exception $e) {
            $db->rollBack();
            $action_result = '<div class="alert alert-error">Lỗi khi hủy đơn hàng: ' . $e->getMessage() . '</div>';
        }
    }
}

// Kiểm tra thông báo thành công
if (isset($_GET['success'])) {
    if ($_GET['success'] === 'cancelled') {
        $action_result = '<div class="alert alert-success">Đơn hàng đã được hủy thành công!</div>';
    }
}

// Tạo order_code (vì bảng không có cột này)
$order_code = generateOrderCode($order['id']);

// Lấy thông tin status
$status_info = formatStatus($order['status']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi Tiết Đơn Hàng #<?php echo $order_code; ?> - Adam Sport</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ===== RESET & GLOBAL ===== */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        :root {
            --primary-color: #007bff;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --warning-color: #ffc107;
            --info-color: #17a2b8;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
            --border-color: #dee2e6;
            --shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }
        
        a {
            color: var(--primary-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        a:hover {
            color: #0056b3;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        /* ===== HEADER ===== */
        .page-header {
            background: white;
            padding: 25px 0;
            margin-bottom: 30px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .header-left h1 {
            color: var(--dark-color);
            font-size: 1.8rem;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .header-left .order-code {
            font-family: monospace;
            background: var(--light-color);
            padding: 5px 12px;
            border-radius: 6px;
            font-size: 1.2rem;
        }
        
        .order-date {
            color: var(--secondary-color);
            font-size: 0.95rem;
        }
        
        .header-right {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
        }
        
        /* ===== STATUS BADGE ===== */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9rem;
        }
        
        .status-badge.pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-badge.processing {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-badge.shipping {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-badge.delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-badge.default {
            background: #e9ecef;
            color: #495057;
        }
        
        /* ===== BUTTONS ===== */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .btn-success {
            background: var(--success-color);
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: var(--danger-color);
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid var(--border-color);
            color: var(--dark-color);
        }
        
        .btn-outline:hover {
            background: var(--light-color);
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 0.875rem;
        }
        
        /* ===== ALERTS ===== */
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        
        /* ===== MAIN CONTENT ===== */
        .main-content {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 30px;
            margin-bottom: 40px;
        }
        
        @media (max-width: 992px) {
            .main-content {
                grid-template-columns: 1fr;
            }
        }
        
        /* ===== ORDER SUMMARY ===== */
        .order-summary {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .section-title {
            font-size: 1.2rem;
            color: var(--dark-color);
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--light-color);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }
        
        .info-item {
            margin-bottom: 15px;
        }
        
        .info-label {
            font-size: 0.9rem;
            color: var(--secondary-color);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-value {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        /* ===== ORDER ITEMS ===== */
        .order-items {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th {
            background: var(--light-color);
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: var(--dark-color);
            border-bottom: 2px solid var(--border-color);
        }
        
        .items-table td {
            padding: 20px 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .items-table tr:hover {
            background: #fafafa;
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }
        
        .product-cell {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid var(--border-color);
        }
        
        .product-info {
            flex: 1;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--dark-color);
        }
        
        .product-sku {
            font-size: 0.85rem;
            color: var(--secondary-color);
        }
        
        .price-cell {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .quantity-cell {
            text-align: center;
        }
        
        .subtotal-cell {
            font-weight: 600;
            color: var(--dark-color);
            text-align: right;
        }
        
        /* ===== PAYMENT SUMMARY ===== */
        .payment-summary {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow);
            position: sticky;
            top: 20px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--border-color);
        }
        
        .summary-row:last-child {
            border-bottom: none;
        }
        
        .summary-row.total {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--dark-color);
            padding-top: 20px;
            border-top: 2px solid var(--border-color);
        }
        
        .summary-label {
            color: var(--secondary-color);
        }
        
        .summary-value {
            font-weight: 500;
            color: var(--dark-color);
        }
        
        /* ===== STATUS TIMELINE ===== */
        .status-timeline {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 30px;
        }
        
        .timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .timeline::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border-color);
        }
        
        .timeline-item {
            position: relative;
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .timeline-item:last-child {
            margin-bottom: 0;
            border-bottom: none;
        }
        
        .timeline-dot {
            position: absolute;
            left: -33px;
            top: 0;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            background: white;
            border: 3px solid var(--border-color);
        }
        
        .timeline-item.current .timeline-dot {
            border-color: var(--primary-color);
            background: var(--primary-color);
        }
        
        .timeline-item.completed .timeline-dot {
            border-color: var(--success-color);
            background: var(--success-color);
        }
        
        .timeline-content {
            background: var(--light-color);
            padding: 15px;
            border-radius: 8px;
        }
        
        .timeline-time {
            font-size: 0.85rem;
            color: var(--secondary-color);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .timeline-status {
            font-weight: 600;
            color: var(--dark-color);
            margin-bottom: 8px;
        }
        
        .timeline-note {
            color: var(--secondary-color);
            font-size: 0.9rem;
        }
        
        /* ===== ACTIONS SECTION ===== */
        .actions-section {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin-bottom: 40px;
        }
        
        .actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        /* ===== CANCEL MODAL ===== */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.show {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            animation: modalSlide 0.3s ease;
        }
        
        @keyframes modalSlide {
            from { transform: translateY(-30px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 20px;
        }
        
        .modal-footer {
            padding: 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        /* ===== RESPONSIVE ===== */
        @media (max-width: 768px) {
            .container {
                padding: 0 15px;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .header-right {
                width: 100%;
                justify-content: flex-start;
            }
            
            .items-table {
                display: block;
                overflow-x: auto;
            }
            
            .product-cell {
                flex-direction: column;
                text-align: center;
                align-items: flex-start;
            }
            
            .product-image {
                width: 100%;
                height: auto;
                max-width: 150px;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
            
            .actions-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* ===== PRINT STYLES ===== */
        @media print {
            .header-right,
            .actions-section,
            .btn-print,
            .modal {
                display: none !important;
            }
            
            body {
                background: white;
            }
            
            .container {
                max-width: 100%;
                padding: 0;
            }
            
            .page-header {
                padding: 20px 0;
                border-bottom: 2px solid #000;
            }
            
            .main-content {
                display: block;
            }
            
            .order-summary,
            .order-items,
            .payment-summary,
            .status-timeline {
                box-shadow: none;
                border: 1px solid #ddd;
                margin-bottom: 20px;
                page-break-inside: avoid;
            }
            
            .items-table th {
                background: #f2f2f2;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="page-header">
        <div class="container">
            <div class="header-content">
                <div class="header-left">
                    <h1>
                        <i class="fas fa-file-invoice"></i>
                        Chi Tiết Đơn Hàng
                        <span class="order-code">#<?php echo $order_code; ?></span>
                    </h1>
                    <div class="order-date">
                        Đặt ngày: <?php echo date('H:i d/m/Y', strtotime($order['created_at'])); ?>
                        <?php if ($order['updated_at'] != $order['created_at']): ?>
                            <br>Cập nhật: <?php echo date('H:i d/m/Y', strtotime($order['updated_at'])); ?>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="header-right">
                    <a href="order_tracking.php" class="btn btn-outline">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="fas fa-print"></i> In hóa đơn
                    </button>
                    <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                        <button onclick="showCancelModal()" class="btn btn-danger">
                            <i class="fas fa-times"></i> Hủy đơn hàng
                        </button>
                    <?php endif; ?>
                    <?php if (!empty($order['customer_phone'])): ?>
                    <a href="order_tracking.php?order=<?php echo $order['id']; ?>&phone=<?php echo urlencode($order['customer_phone']); ?>" 
                       class="btn btn-primary">
                        <i class="fas fa-truck"></i> Theo dõi
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Alerts -->
        <?php echo $action_result; ?>
        
        <!-- Main Content -->
        <div class="main-content">
            <!-- Left Column: Order Details -->
            <div class="left-column">
                <!-- Order Status -->
                <div class="order-summary">
                    <h2 class="section-title">
                        <i class="fas fa-info-circle"></i>
                        Thông tin đơn hàng
                        <span class="status-badge <?php echo $status_info['class']; ?>">
                            <i class="fas <?php echo $status_info['icon']; ?>"></i>
                            <?php echo $status_info['text']; ?>
                        </span>
                    </h2>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-barcode"></i>
                                Mã đơn hàng
                            </div>
                            <div class="info-value">#<?php echo $order_code; ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-user"></i>
                                Khách hàng
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($order['customer_name'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-phone"></i>
                                Điện thoại
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($order['customer_phone'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <?php if (!empty($order['customer_email'])): ?>
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-envelope"></i>
                                Email
                            </div>
                            <div class="info-value"><?php echo htmlspecialchars($order['customer_email']); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-map-marker-alt"></i>
                                Địa chỉ
                            </div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($order['customer_address'] ?? 'Chưa có địa chỉ')); ?></div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-credit-card"></i>
                                Phương thức TT
                            </div>
                            <div class="info-value"><?php echo formatPaymentMethod($order['payment_method'] ?? 'cod'); ?></div>
                        </div>
                        
                        <?php if (!empty($order['notes'])): ?>
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-sticky-note"></i>
                                Ghi chú
                            </div>
                            <div class="info-value"><?php echo nl2br(htmlspecialchars($order['notes'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Order Items -->
                <div class="order-items">
                    <h2 class="section-title">
                        <i class="fas fa-shopping-cart"></i>
                        Sản phẩm đã đặt
                        <?php if (!empty($order_items)): ?>
                            <span style="font-size: 0.9rem; color: var(--secondary-color);">
                                (<?php echo $total_quantity; ?> sản phẩm)
                            </span>
                        <?php endif; ?>
                    </h2>
                    
                    <?php if (!empty($order_items)): ?>
                        <div class="table-responsive">
                            <table class="items-table">
                                <thead>
                                    <tr>
                                        <th width="50%">Sản phẩm</th>
                                        <th width="15%">Đơn giá</th>
                                        <th width="15%">Số lượng</th>
                                        <th width="20%">Thành tiền</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $total_calculated = 0;
                                    foreach ($order_items as $item): 
                                        $product_price = $item['product_price'] ?? 0;
                                        $quantity = $item['quantity'] ?? 0;
                                        $subtotal = $product_price * $quantity;
                                        $total_calculated += $subtotal;
                                    ?>
                                    <tr>
                                        <td>
                                           <div class="product-cell">
                                                <div class="product-icon" style="width: 80px; height: 80px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center; margin-right: 15px; color: white;">
                                                    <i class="fas fa-shopping-bag" style="font-size: 24px;"></i>
                                                </div>
                                                
                                                <div class="product-info">
                                                    <div class="product-name">
                                                        <?php echo htmlspecialchars($item['product_name'] ?? 'Sản phẩm không xác định'); ?>
                                                    </div>
                                                    <?php if (!empty($item['product_description'])): ?>
                                                        <div style="font-size: 0.85rem; color: #666; margin-top: 5px;">
                                                            <?php echo substr(htmlspecialchars($item['product_description']), 0, 100); ?>...
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="price-cell">
                                            <?php echo number_format($product_price); ?> ₫
                                        </td>
                                        <td class="quantity-cell">
                                            <?php echo $quantity; ?>
                                        </td>
                                        <td class="subtotal-cell">
                                            <?php echo number_format($subtotal); ?> ₫
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div style="text-align: center; padding: 40px 20px; color: #666;">
                            <i class="fas fa-shopping-cart fa-3x" style="color: #ddd; margin-bottom: 20px;"></i>
                            <p>Không có sản phẩm trong đơn hàng này</p>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Status Timeline -->
                <div class="status-timeline">
                    <h2 class="section-title">
                        <i class="fas fa-history"></i>
                        Lịch sử trạng thái
                    </h2>
                    
                    <div class="timeline">
                        <?php if (!empty($status_history)): ?>
                            <?php foreach ($status_history as $history): ?>
                            <div class="timeline-item <?php echo ($history['status'] ?? '') === $order['status'] ? 'current' : 'completed'; ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-time">
                                        <i class="far fa-clock"></i>
                                        <?php echo date('H:i d/m/Y', strtotime($history['created_at'])); ?>
                                    </div>
                                    <div class="timeline-status">
                                        <?php echo formatStatus($history['status'] ?? '')['text']; ?>
                                    </div>
                                    <?php if (!empty($history['note'])): ?>
                                        <div class="timeline-note">
                                            <?php echo htmlspecialchars($history['note']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Default timeline based on current status -->
                            <?php
                            $status_steps = [
                                'pending' => ['label' => 'Đơn hàng được tạo', 'icon' => 'fa-clipboard-check'],
                                'processing' => ['label' => 'Đang xử lý đơn hàng', 'icon' => 'fa-cog'],
                                'shipping' => ['label' => 'Đang giao hàng', 'icon' => 'fa-shipping-fast'],
                                'delivered' => ['label' => 'Giao hàng thành công', 'icon' => 'fa-check-circle'],
                                'cancelled' => ['label' => 'Đơn hàng đã hủy', 'icon' => 'fa-times-circle']
                            ];
                            
                            $current_step = $order['status'];
                            $step_keys = array_keys($status_steps);
                            $current_index = array_search($current_step, $step_keys);
                            
                            foreach ($status_steps as $step => $step_info):
                                $step_index = array_search($step, $step_keys);
                                $is_active = ($step == $current_step);
                                $is_past = ($current_index !== false && $step_index !== false && $step_index < $current_index);
                                $is_future = ($current_index !== false && $step_index !== false && $step_index > $current_index);
                            ?>
                            <div class="timeline-item <?php echo $is_active ? 'current' : ($is_past ? 'completed' : ''); ?>">
                                <div class="timeline-dot"></div>
                                <div class="timeline-content">
                                    <div class="timeline-time">
                                        <i class="fas <?php echo $step_info['icon']; ?>"></i>
                                        <?php echo $step_info['label']; ?>
                                    </div>
                                    <?php if ($is_active): ?>
                                    <div class="timeline-status">
                                        <span class="status-badge <?php echo formatStatus($step)['class']; ?>">
                                            <i class="fas <?php echo formatStatus($step)['icon']; ?>"></i>
                                            <?php echo formatStatus($step)['text']; ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Payment Summary -->
            <div class="right-column">
                <!-- Payment Summary -->
                <div class="payment-summary">
                    <h2 class="section-title">
                        <i class="fas fa-calculator"></i>
                        Thanh toán
                    </h2>
                    
                    <?php
                    // Lấy total_amount từ database hoặc tính từ order_items
                    $total_amount = $order['total_amount'] ?? $total_calculated ?? 0;
                    ?>
                    
                    <div class="summary-row total">
                        <span class="summary-label">Tổng thanh toán:</span>
                        <span class="summary-value" style="color: var(--danger-color); font-size: 1.3rem;">
                            <?php echo number_format($total_amount); ?> ₫
                        </span>
                    </div>
                    
                    <!-- Additional info -->
                    <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-boxes"></i>
                                Số lượng sản phẩm
                            </div>
                            <div class="info-value">
                                <?php echo $total_quantity; ?> sản phẩm
                            </div>
                        </div>
                        
                        <div class="info-item" style="margin-top: 10px;">
                            <div class="info-label">
                                <i class="fas fa-calendar"></i>
                                Trạng thái
                            </div>
                            <div class="info-value">
                                <span class="status-badge <?php echo $status_info['class']; ?>">
                                    <i class="fas <?php echo $status_info['icon']; ?>"></i>
                                    <?php echo $status_info['text']; ?>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Order Actions -->
                <div class="actions-section">
                    <h2 class="section-title">
                        <i class="fas fa-cogs"></i>
                        Thao tác
                    </h2>
                    
                    <div class="actions-grid">
                        <?php if (!empty($order['customer_phone'])): ?>
                        <a href="order_tracking.php?order=<?php echo $order['id']; ?>&phone=<?php echo urlencode($order['customer_phone']); ?>" 
                           class="btn btn-primary">
                            <i class="fas fa-truck"></i> Theo dõi đơn
                        </a>
                        <?php endif; ?>
                        
                        <button onclick="window.print()" class="btn btn-secondary">
                            <i class="fas fa-print"></i> In hóa đơn
                        </button>
                        
                        <?php if ($order['status'] == 'delivered'): ?>
                            <a href="#" class="btn btn-success">
                                <i class="fas fa-star"></i> Đánh giá
                            </a>
                            <a href="#" class="btn btn-outline">
                                <i class="fas fa-redo"></i> Mua lại
                            </a>
                        <?php endif; ?>
                        
                        <?php if (in_array($order['status'], ['pending', 'processing'])): ?>
                            <button onclick="showCancelModal()" class="btn btn-danger">
                                <i class="fas fa-times"></i> Hủy đơn
                            </button>
                        <?php endif; ?>
                        
                        <a href="contact.php" class="btn btn-outline">
                            <i class="fas fa-headset"></i> Hỗ trợ
                        </a>
                    </div>
                </div>
                
                <!-- Quick Contact -->
                <div class="order-summary">
                    <h2 class="section-title">
                        <i class="fas fa-headset"></i>
                        Hỗ trợ khách hàng
                    </h2>
                    
                    <div class="info-grid">
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-phone-alt"></i>
                                Hotline
                            </div>
                            <div class="info-value">
                                <a href="tel:0788500585">0788 500 585</a>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-envelope"></i>
                                Email
                            </div>
                            <div class="info-value">
                                <a href="mailto:info@adamsport.com">info@adamsport.com</a>
                            </div>
                        </div>
                        
                        <div class="info-item">
                            <div class="info-label">
                                <i class="fas fa-clock"></i>
                                Giờ làm việc
                            </div>
                            <div class="info-value">
                                8:00 - 22:00<br>
                                Tất cả các ngày
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Cancel Order Modal -->
    <div id="cancelModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3 style="margin: 0;">
                    <i class="fas fa-exclamation-triangle" style="color: var(--danger-color);"></i>
                    Hủy đơn hàng
                </h3>
                <button onclick="hideCancelModal()" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <form method="POST">
                <div class="modal-body">
                    <p>Bạn có chắc chắn muốn hủy đơn hàng <strong>#<?php echo $order_code; ?></strong>?</p>
                    
                    <div class="form-group">
                        <label for="cancel_reason">
                            <i class="fas fa-comment"></i> Lý do hủy (không bắt buộc):
                        </label>
                        <textarea id="cancel_reason" name="cancel_reason" class="form-control" 
                                  placeholder="Vui lòng cho chúng tôi biết lý do bạn hủy đơn hàng này..."></textarea>
                    </div>
                    
                    <div style="background: #fff3cd; padding: 15px; border-radius: 6px; margin-top: 20px;">
                        <i class="fas fa-info-circle"></i> 
                        <strong>Lưu ý:</strong> Đơn hàng đã hủy không thể khôi phục. Mọi hoàn tiền sẽ được xử lý trong 3-5 ngày làm việc.
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" onclick="hideCancelModal()" class="btn btn-outline">
                        <i class="fas fa-times"></i> Hủy bỏ
                    </button>
                    <button type="submit" name="cancel_order" class="btn btn-danger">
                        <i class="fas fa-check"></i> Xác nhận hủy
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
    // Modal functions
    function showCancelModal() {
        document.getElementById('cancelModal').classList.add('show');
        document.getElementById('cancel_reason').focus();
    }
    
    function hideCancelModal() {
        document.getElementById('cancelModal').classList.remove('show');
    }
    
    // Close modal when clicking outside
    document.getElementById('cancelModal').addEventListener('click', function(e) {
        if (e.target === this) {
            hideCancelModal();
        }
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            hideCancelModal();
        }
        if (e.key === 'p' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            window.print();
        }
    });
    
    // Share order link
    function shareOrder() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            alert('Đã sao chép link chi tiết đơn hàng!');
        });
    }
    </script>
</body>
</html>