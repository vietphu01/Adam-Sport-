<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

try {
    $db = connectDB();
    
    // Xây dựng query với filter
    $sql = "
        SELECT o.*, 
               COUNT(oi.id) as item_count,
               SUM(oi.quantity) as total_quantity
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
    ";
    
    $params = [$user_id];
    
    // Filter theo status
    if ($status_filter !== 'all' && in_array($status_filter, ['pending', 'processing', 'shipping', 'delivered', 'cancelled'])) {
        $sql .= " AND o.order_status = ?";
        $params[] = $status_filter;
    }
    
    // Filter theo tìm kiếm
    if (!empty($search)) {
        $sql .= " AND (o.order_code LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)";
        $search_term = "%$search%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
    }
    
    $sql .= " GROUP BY o.id ORDER BY o.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
    
    // Đếm theo status
    $count_stmt = $db->prepare("
        SELECT 
            order_status,
            COUNT(*) as count
        FROM orders 
        WHERE user_id = ?
        GROUP BY order_status
    ");
    $count_stmt->execute([$user_id]);
    $status_counts = $count_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
} catch(PDOException $e) {
    $error = "Lỗi hệ thống: " . $e->getMessage();
}

$status_labels = [
    'all' => 'Tất cả',
    'pending' => 'Chờ xử lý',
    'processing' => 'Đang xử lý',
    'shipping' => 'Đang giao hàng',
    'delivered' => 'Đã giao',
    'cancelled' => 'Đã hủy'
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đơn hàng của tôi - Adam Sport</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .orders-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .page-header {
            margin-bottom: 30px;
        }
        
        .page-header h1 {
            color: #333;
            margin-bottom: 10px;
        }
        
        .page-description {
            color: #666;
            font-size: 16px;
        }
        
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        
        .status-filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        
        .status-filter {
            padding: 10px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 25px;
            background: white;
            color: #666;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .status-filter:hover {
            border-color: #007bff;
            color: #007bff;
        }
        
        .status-filter.active {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .status-count {
            background: rgba(255,255,255,0.2);
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 12px;
        }
        
        .search-box {
            display: flex;
            gap: 10px;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 16px;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .search-btn {
            padding: 12px 30px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }
        
        .search-btn:hover {
            background: #0056b3;
        }
        
        .order-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
            overflow: hidden;
        }
        
        .order-header {
            background: #f8f9fa;
            padding: 20px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .order-info {
            display: flex;
            gap: 30px;
            flex-wrap: wrap;
        }
        
        .order-code {
            font-family: monospace;
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
        }
        
        .order-date {
            color: #666;
        }
        
        .order-actions {
            display: flex;
            gap: 10px;
        }
        
        .order-body {
            padding: 25px;
        }
        
        .order-items {
            display: flex;
            flex-direction: column;
            gap: 15px;
            margin-bottom: 25px;
        }
        
        .order-item {
            display: flex;
            align-items: center;
            gap: 20px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 8px;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 8px;
            object-fit: cover;
        }
        
        .item-info {
            flex: 1;
        }
        
        .item-name {
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        
        .item-price {
            color: #007bff;
            font-weight: bold;
        }
        
        .item-quantity {
            color: #666;
        }
        
        .order-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .summary-item.total {
            font-weight: bold;
            font-size: 18px;
            color: #333;
            border-bottom: none;
        }
        
        .order-footer {
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .order-status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        
        .status-timeline {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        
        .timeline-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            flex: 1;
        }
        
        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            border: 2px solid #e0e0e0;
        }
        
        .timeline-step.active .timeline-icon {
            background: #007bff;
            border-color: #007bff;
            color: white;
        }
        
        .timeline-step.completed .timeline-icon {
            background: #28a745;
            border-color: #28a745;
            color: white;
        }
        
        .timeline-label {
            font-size: 12px;
            color: #666;
            text-align: center;
        }
        
        .timeline-line {
            flex: 1;
            height: 2px;
            background: #e0e0e0;
        }
        
        .no-orders {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .no-orders i {
            font-size: 60px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .order-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .order-info {
                flex-direction: column;
                gap: 10px;
            }
            
            .order-actions {
                width: 100%;
                justify-content: flex-start;
            }
            
            .order-item {
                flex-direction: column;
                text-align: center;
            }
            
            .status-timeline {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .timeline-step {
                flex-direction: row;
                width: 100%;
                gap: 15px;
            }
            
            .timeline-line {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="orders-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1><i class="fas fa-shopping-bag"></i> Đơn hàng của tôi</h1>
            <p class="page-description">Theo dõi và quản lý tất cả đơn hàng của bạn tại đây</p>
        </div>
        
        <!-- Filter Section -->
        <div class="filter-section">
            <!-- Status Filters -->
            <div class="status-filters">
                <?php foreach($status_labels as $status => $label): ?>
                    <a href="?status=<?php echo $status; ?>" 
                       class="status-filter <?php echo ($status_filter == $status) ? 'active' : ''; ?>">
                        <?php echo $label; ?>
                        <?php if($status !== 'all' && isset($status_counts[$status])): ?>
                            <span class="status-count"><?php echo $status_counts[$status]; ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
            
            <!-- Search Box -->
            <form method="GET" class="search-box">
                <input type="text" 
                       name="search" 
                       placeholder="Tìm kiếm theo mã đơn, tên, số điện thoại..." 
                       value="<?php echo htmlspecialchars($search); ?>"
                       class="search-input">
                <button type="submit" class="search-btn">
                    <i class="fas fa-search"></i> Tìm kiếm
                </button>
            </form>
        </div>
        
        <!-- Orders List -->
        <?php if(!empty($orders)): ?>
            <div class="orders-list">
                <?php foreach($orders as $order): 
                    // Lấy chi tiết sản phẩm trong đơn hàng
                    $items_stmt = $db->prepare("
                        SELECT oi.*, p.image_url 
                        FROM order_items oi 
                        LEFT JOIN products p ON oi.product_id = p.id 
                        WHERE oi.order_id = ?
                    ");
                    $items_stmt->execute([$order['id']]);
                    $items = $items_stmt->fetchAll();
                    
                    // Lấy lịch sử trạng thái
                    $history_stmt = $db->prepare("SELECT * FROM order_status_history WHERE order_id = ? ORDER BY created_at");
                    $history_stmt->execute([$order['id']]);
                    $history = $history_stmt->fetchAll();
                ?>
                <div class="order-card">
                    <!-- Order Header -->
                    <div class="order-header">
                        <div class="order-info">
                            <div>
                                <strong>Mã đơn:</strong>
                                <span class="order-code">#<?php echo $order['order_code']; ?></span>
                            </div>
                            <div>
                                <strong>Ngày đặt:</strong>
                                <span class="order-date"><?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?></span>
                            </div>
                            <div>
                                <strong>Thanh toán:</strong>
                                <span><?php echo ucfirst($order['payment_method']); ?></span>
                            </div>
                        </div>
                        <div class="order-actions">
                            <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> Chi tiết
                            </a>
                            <?php if($order['order_status'] == 'pending'): ?>
                                <a href="#" class="btn btn-outline" onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                    <i class="fas fa-times"></i> Hủy đơn
                                </a>
                            <?php endif; ?>
                            <?php if($order['order_status'] == 'delivered'): ?>
                                <a href="#" class="btn btn-outline">
                                    <i class="fas fa-redo"></i> Mua lại
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Order Body -->
                    <div class="order-body">
                        <!-- Order Items -->
                        <div class="order-items">
                            <?php foreach($items as $item): ?>
                            <div class="order-item">
                                <img src="<?php echo $item['image_url'] ?? 'assets/images/products/default.jpg'; ?>" 
                                     alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                     class="item-image"
                                     onerror="this.src='assets/images/products/default.jpg'">
                                <div class="item-info">
                                    <div class="item-name"><?php echo htmlspecialchars($item['product_name']); ?></div>
                                    <div class="item-price"><?php echo number_format($item['product_price']); ?>₫</div>
                                </div>
                                <div class="item-quantity">Số lượng: <?php echo $item['quantity']; ?></div>
                                <div class="item-subtotal"><strong><?php echo number_format($item['subtotal']); ?>₫</strong></div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Order Summary -->
                        <div class="order-summary">
                            <div class="summary-item">
                                <span>Tổng tiền hàng:</span>
                                <span><?php echo number_format($order['total_amount']); ?>₫</span>
                            </div>
                            <div class="summary-item">
                                <span>Phí vận chuyển:</span>
                                <span><?php echo number_format($order['shipping_fee']); ?>₫</span>
                            </div>
                            <div class="summary-item">
                                <span>Giảm giá:</span>
                                <span>-<?php echo number_format($order['discount_amount']); ?>₫</span>
                            </div>
                            <div class="summary-item total">
                                <span>Tổng thanh toán:</span>
                                <span><?php echo number_format($order['final_amount']); ?>₫</span>
                            </div>
                        </div>
                        
                        <!-- Status Timeline -->
                        <div class="status-timeline">
                            <?php
                            $status_steps = [
                                'pending' => ['icon' => 'fa-clock', 'label' => 'Chờ xử lý'],
                                'processing' => ['icon' => 'fa-cog', 'label' => 'Đang xử lý'],
                                'shipping' => ['icon' => 'fa-shipping-fast', 'label' => 'Đang giao'],
                                'delivered' => ['icon' => 'fa-check-circle', 'label' => 'Đã giao']
                            ];
                            
                            $current_step = array_search($order['order_status'], array_keys($status_steps));
                            $step_index = 0;
                            
                            foreach($status_steps as $step => $step_info):
                                $is_active = ($step == $order['order_status']);
                                $is_completed = array_search($step, array_keys($status_steps)) < $current_step;
                            ?>
                            <?php if($step_index > 0): ?>
                                <div class="timeline-line <?php echo $is_completed ? 'completed' : ''; ?>"></div>
                            <?php endif; ?>
                            
                            <div class="timeline-step <?php echo $is_active ? 'active' : ($is_completed ? 'completed' : ''); ?>">
                                <div class="timeline-icon">
                                    <i class="fas <?php echo $step_info['icon']; ?>"></i>
                                </div>
                                <div class="timeline-label"><?php echo $step_info['label']; ?></div>
                            </div>
                            <?php $step_index++; endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Order Footer -->
                    <div class="order-footer">
                        <div>
                            <span class="order-status-badge status-<?php echo $order['order_status']; ?>">
                                <?php 
                                $status_text = [
                                    'pending' => 'Chờ xử lý',
                                    'processing' => 'Đang xử lý',
                                    'shipping' => 'Đang giao hàng',
                                    'delivered' => 'Đã giao thành công',
                                    'cancelled' => 'Đã hủy'
                                ];
                                echo $status_text[$order['order_status']] ?? $order['order_status'];
                                ?>
                            </span>
                        </div>
                        <div>
                            <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-outline">
                                <i class="fas fa-print"></i> In hóa đơn
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="no-orders">
                <i class="fas fa-box-open"></i>
                <h2>Không tìm thấy đơn hàng nào</h2>
                <p><?php echo $status_filter !== 'all' ? "Không có đơn hàng ở trạng thái này." : "Bạn chưa có đơn hàng nào."; ?></p>
                <a href="products.php" class="btn btn-primary" style="margin-top: 20px;">
                    <i class="fas fa-shopping-cart"></i> Mua sắm ngay
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
    function cancelOrder(orderId) {
        if(confirm('Bạn có chắc chắn muốn hủy đơn hàng này?')) {
            fetch('cancel_order.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ order_id: orderId })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Đã hủy đơn hàng thành công!');
                    location.reload();
                } else {
                    alert('Có lỗi xảy ra: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Có lỗi xảy ra khi hủy đơn hàng!');
            });
        }
    }
    </script>
</body>
</html>