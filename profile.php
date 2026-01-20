<?php
session_start();
require_once 'config/database.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

try {
    $db = connectDB();
    
    // Lấy thông tin user
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    
    // Thống kê đơn hàng
    $stats_stmt = $db->prepare("
        SELECT 
            COUNT(*) as total_orders,
            SUM(CASE WHEN order_status = 'pending' THEN 1 ELSE 0 END) as pending_orders,
            SUM(CASE WHEN order_status = 'processing' THEN 1 ELSE 0 END) as processing_orders,
            SUM(CASE WHEN order_status = 'shipping' THEN 1 ELSE 0 END) as shipping_orders,
            SUM(CASE WHEN order_status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
            SUM(CASE WHEN order_status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
            SUM(final_amount) as total_spent
        FROM orders 
        WHERE user_id = ?
    ");
    $stats_stmt->execute([$user_id]);
    $stats = $stats_stmt->fetch();
    
    // Lấy đơn hàng gần đây (5 đơn)
    $recent_orders_stmt = $db->prepare("
        SELECT o.*, 
               COUNT(oi.id) as item_count,
               SUM(oi.quantity) as total_quantity
        FROM orders o
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.user_id = ?
        GROUP BY o.id
        ORDER BY o.created_at DESC 
        LIMIT 5
    ");
    $recent_orders_stmt->execute([$user_id]);
    $recent_orders = $recent_orders_stmt->fetchAll();
    
} catch(PDOException $e) {
    $error = "Lỗi hệ thống: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trang cá nhân - Adam Sport</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 40px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            color: #667eea;
            font-weight: bold;
            border: 4px solid rgba(255,255,255,0.3);
        }
        
        .profile-info h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
        }
        
        .profile-info p {
            margin: 5px 0;
            opacity: 0.9;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            text-align: center;
            border-left: 5px solid #007bff;
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card.pending {
            border-left-color: #ffc107;
        }
        
        .stat-card.processing {
            border-left-color: #17a2b8;
        }
        
        .stat-card.shipping {
            border-left-color: #6f42c1;
        }
        
        .stat-card.delivered {
            border-left-color: #28a745;
        }
        
        .stat-card.cancelled {
            border-left-color: #dc3545;
        }
        
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #333;
            display: block;
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }
        
        .sidebar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            height: fit-content;
        }
        
        .sidebar-title {
            font-size: 18px;
            margin-bottom: 20px;
            color: #333;
            padding-bottom: 10px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .sidebar-menu li {
            margin-bottom: 10px;
        }
        
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            color: #555;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .sidebar-menu a:hover,
        .sidebar-menu a.active {
            background: #f8f9fa;
            color: #007bff;
        }
        
        .sidebar-menu a i {
            width: 20px;
            text-align: center;
        }
        
        .content {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .content-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .content-header h2 {
            margin: 0;
            color: #333;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th {
            background: #f8f9fa;
            padding: 15px;
            text-align: left;
            color: #555;
            font-weight: 600;
            border-bottom: 2px solid #dee2e6;
        }
        
        .orders-table td {
            padding: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .orders-table tr:hover {
            background: #f8f9fa;
        }
        
        .order-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-shipping {
            background: #e2e3e5;
            color: #383d41;
        }
        
        .status-delivered {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .order-code {
            font-family: monospace;
            font-weight: bold;
            color: #007bff;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-outline {
            background: transparent;
            border: 1px solid #007bff;
            color: #007bff;
        }
        
        .btn-outline:hover {
            background: #007bff;
            color: white;
        }
        
        .btn-sm {
            padding: 6px 12px;
            font-size: 12px;
        }
        
        .no-orders {
            text-align: center;
            padding: 50px 20px;
            color: #666;
        }
        
        .no-orders i {
            font-size: 48px;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
            
            .profile-header {
                flex-direction: column;
                text-align: center;
                padding: 30px 20px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .orders-table {
                display: block;
                overflow-x: auto;
            }
        }
        
        @media (max-width: 576px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>
    
    <div class="profile-container">
        <!-- Profile Header -->
        <div class="profile-header">
            <div class="profile-avatar">
                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
            </div>
            <div class="profile-info">
                <h1><?php echo htmlspecialchars($user['full_name']); ?></h1>
                <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email']); ?></p>
                <?php if(!empty($user['phone'])): ?>
                    <p><i class="fas fa-phone"></i> <?php echo htmlspecialchars($user['phone']); ?></p>
                <?php endif; ?>
                <p>Thành viên từ: <?php echo date('d/m/Y', strtotime($user['created_at'])); ?></p>
            </div>
        </div>
        
        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card">
                <span class="stat-number"><?php echo $stats['total_orders'] ?? 0; ?></span>
                <span class="stat-label">Tổng đơn hàng</span>
            </div>
            
            <div class="stat-card pending">
                <span class="stat-number"><?php echo $stats['pending_orders'] ?? 0; ?></span>
                <span class="stat-label">Chờ xử lý</span>
            </div>
            
            <div class="stat-card processing">
                <span class="stat-number"><?php echo $stats['processing_orders'] ?? 0; ?></span>
                <span class="stat-label">Đang xử lý</span>
            </div>
            
            <div class="stat-card shipping">
                <span class="stat-number"><?php echo $stats['shipping_orders'] ?? 0; ?></span>
                <span class="stat-label">Đang giao</span>
            </div>
            
            <div class="stat-card delivered">
                <span class="stat-number"><?php echo $stats['delivered_orders'] ?? 0; ?></span>
                <span class="stat-label">Đã giao</span>
            </div>
            
            <div class="stat-card">
                <span class="stat-number"><?php echo number_format($stats['total_spent'] ?? 0); ?>₫</span>
                <span class="stat-label">Tổng chi tiêu</span>
            </div>
        </div>
        
        <!-- Dashboard -->
        <div class="dashboard-grid">
            <!-- Sidebar Menu -->
            <div class="sidebar">
                <h3 class="sidebar-title">TÀI KHOẢN CỦA TÔI</h3>
                <ul class="sidebar-menu">
                    <li><a href="profile.php" class="active"><i class="fas fa-user"></i> Trang cá nhân</a></li>
                    <li><a href="orders.php"><i class="fas fa-shopping-bag"></i> Đơn hàng của tôi</a></li>
                    <li><a href="address.php"><i class="fas fa-map-marker-alt"></i> Sổ địa chỉ</a></li>
                    <li><a href="wishlist.php"><i class="fas fa-heart"></i> Sản phẩm yêu thích</a></li>
                    <li><a href="change_password.php"><i class="fas fa-key"></i> Đổi mật khẩu</a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
                </ul>
            </div>
            
            <!-- Main Content -->
            <div class="content">
                <div class="content-header">
                    <h2><i class="fas fa-clock"></i> Đơn hàng gần đây</h2>
                    <a href="orders.php" class="btn btn-primary">Xem tất cả đơn hàng</a>
                </div>
                
                <?php if(!empty($recent_orders)): ?>
                    <div class="table-responsive">
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Mã đơn hàng</th>
                                    <th>Ngày đặt</th>
                                    <th>Sản phẩm</th>
                                    <th>Tổng tiền</th>
                                    <th>Trạng thái</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($recent_orders as $order): ?>
                                <tr>
                                    <td>
                                        <span class="order-code">#<?php echo $order['order_code']; ?></span>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($order['created_at'])); ?></td>
                                    <td>
                                        <?php echo $order['item_count']; ?> sản phẩm
                                        <small>(<?php echo $order['total_quantity']; ?> cái)</small>
                                    </td>
                                    <td><strong><?php echo number_format($order['final_amount']); ?>₫</strong></td>
                                    <td>
                                        <span class="order-status status-<?php echo $order['order_status']; ?>">
                                            <?php 
                                            $status_text = [
                                                'pending' => 'Chờ xử lý',
                                                'processing' => 'Đang xử lý',
                                                'shipping' => 'Đang giao hàng',
                                                'delivered' => 'Đã giao',
                                                'cancelled' => 'Đã hủy'
                                            ];
                                            echo $status_text[$order['order_status']] ?? $order['order_status'];
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="order_detail.php?id=<?php echo $order['id']; ?>" class="btn btn-outline btn-sm">
                                            <i class="fas fa-eye"></i> Chi tiết
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="no-orders">
                        <i class="fas fa-shopping-bag"></i>
                        <h3>Chưa có đơn hàng nào</h3>
                        <p>Hãy mua sắm và trải nghiệm dịch vụ của chúng tôi!</p>
                        <a href="products.php" class="btn btn-primary">Mua sắm ngay</a>
                    </div>
                <?php endif; ?>
                
                <!-- Quick Actions -->
                <div style="margin-top: 40px; padding-top: 30px; border-top: 2px solid #f0f0f0;">
                    <h3 style="margin-bottom: 20px;">Thao tác nhanh</h3>
                    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
                        <a href="products.php" class="btn btn-primary">
                            <i class="fas fa-shopping-cart"></i> Tiếp tục mua sắm
                        </a>
                        <a href="cart.php" class="btn btn-outline">
                            <i class="fas fa-cart-shopping"></i> Xem giỏ hàng
                        </a>
                        <a href="address.php" class="btn btn-outline">
                            <i class="fas fa-edit"></i> Cập nhật địa chỉ
                        </a>
                        <a href="change_password.php" class="btn btn-outline">
                            <i class="fas fa-key"></i> Đổi mật khẩu
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>
</body>
</html>