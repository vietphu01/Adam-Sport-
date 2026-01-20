<?php
session_start();

// Kết nối CSDL
try {
    require_once 'config/database.php';
    $db = connectDB();
} catch (Exception $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

// Khởi tạo giỏ hàng
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Xử lý thêm vào giỏ hàng
if (isset($_POST['add_to_cart'])) {
    $product_id = intval($_POST['product_id'] ?? 0);
    
    if ($product_id) {
        try {
            // Lấy sản phẩm từ CSDL
            $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $found = false;
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['id'] == $product_id) {
                        // Kiểm tra tồn kho
                        if ($item['quantity'] < $product['stock']) {
                            $item['quantity']++;
                            $found = true;
                        } else {
                            $_SESSION['message'] = 'Sản phẩm đã hết hàng!';
                            $_SESSION['message_type'] = 'warning';
                        }
                        break;
                    }
                }
                
                if (!$found) {
                    if ($product['stock'] > 0) {
                        $_SESSION['cart'][] = [
                            'id' => $product['id'],
                            'name' => $product['name'],
                            'price' => floatval($product['price']),
                            'image_url' => $product['image_url'] ?: 'assets/images/products/default.jpg',
                            'quantity' => 1,
                            'stock' => $product['stock']
                        ];
                    } else {
                        $_SESSION['message'] = 'Sản phẩm đã hết hàng!';
                        $_SESSION['message_type'] = 'warning';
                    }
                }
                
                if (!isset($_SESSION['message'])) {
                    $_SESSION['message'] = 'Đã thêm "' . $product['name'] . '" vào giỏ hàng!';
                    $_SESSION['message_type'] = 'success';
                }
            }
        } catch (Exception $e) {
            $_SESSION['message'] = 'Lỗi khi thêm vào giỏ hàng!';
            $_SESSION['message_type'] = 'error';
        }
    }
    
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? 'cart.php'));
    exit;
}

// Xử lý xóa sản phẩm
if (isset($_GET['remove'])) {
    $remove_id = intval($_GET['remove']);
    foreach ($_SESSION['cart'] as $key => $item) {
        if ($item['id'] == $remove_id) {
            unset($_SESSION['cart'][$key]);
            $_SESSION['message'] = 'Đã xóa sản phẩm khỏi giỏ hàng!';
            $_SESSION['message_type'] = 'success';
            break;
        }
    }
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    header('Location: cart.php');
    exit;
}

// Cập nhật số lượng
if (isset($_POST['update_quantity'])) {
    if (isset($_POST['quantity']) && is_array($_POST['quantity'])) {
        foreach ($_POST['quantity'] as $id => $quantity) {
            $id = intval($id);
            $quantity = max(1, intval($quantity));
            
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $id) {
                    // Kiểm tra tồn kho
                    try {
                        $stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
                        $stmt->execute([$id]);
                        $product = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($product && $quantity <= $product['stock']) {
                            $item['quantity'] = $quantity;
                        } else {
                            $item['quantity'] = min($quantity, $product['stock'] ?? 1);
                            $_SESSION['message'] = 'Số lượng vượt quá tồn kho!';
                            $_SESSION['message_type'] = 'warning';
                        }
                    } catch (Exception $e) {
                        $item['quantity'] = $quantity;
                    }
                    break;
                }
            }
        }
    }
    
    if (!isset($_SESSION['message'])) {
        $_SESSION['message'] = 'Đã cập nhật giỏ hàng!';
        $_SESSION['message_type'] = 'success';
    }
    
    header('Location: cart.php');
    exit;
}

// Xóa toàn bộ giỏ hàng
if (isset($_POST['clear_cart'])) {
    $_SESSION['cart'] = [];
    $_SESSION['message'] = 'Đã xóa toàn bộ giỏ hàng!';
    $_SESSION['message_type'] = 'success';
    header('Location: cart.php');
    exit;
}
// Xử lý thanh toán - VERSION FIXED
if (isset($_POST['checkout'])) {
    if (empty($_SESSION['cart'])) {
        $_SESSION['message'] = 'Giỏ hàng trống!';
        $_SESSION['message_type'] = 'error';
        header('Location: cart.php');
        exit;
    }
    
    // Kiểm tra thông tin khách hàng
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $customer_address = trim($_POST['customer_address'] ?? '');
    $payment_method = $_POST['payment_method'] ?? 'cod';
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($customer_name) || empty($customer_phone)) {
        $_SESSION['message'] = 'Vui lòng nhập đầy đủ thông tin!';
        $_SESSION['message_type'] = 'error';
        header('Location: cart.php');
        exit;
    }
    
    try {
        $db->beginTransaction();
        
        // Tính tổng tiền
        $total_amount = 0;
        foreach ($_SESSION['cart'] as $item) {
            $total_amount += ($item['price'] * $item['quantity']);
        }
        
        // 1. Xử lý customer
        $customer_id = NULL;
        
        // 2. INSERT vào orders (KHÔNG có order_code)
        $stmt = $db->prepare("
            INSERT INTO orders (
                customer_id, 
                customer_name, 
                customer_phone, 
                customer_email, 
                customer_address, 
                total_amount, 
                status, 
                payment_method, 
                notes
            ) VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, ?)
        ");
        
        $stmt->execute([
            $customer_id,
            substr($customer_name, 0, 100), // Giới hạn 100 ký tự
            substr($customer_phone, 0, 20), // Giới hạn 20 ký tự
            $customer_email ? substr($customer_email, 0, 150) : NULL,
            $customer_address ?: NULL,
            (float)$total_amount,
            substr($payment_method, 0, 50), // Giới hạn 50 ký tự
            $notes ?: NULL
        ]);
        
        $order_id = $db->lastInsertId();
        
        // 3. INSERT vào order_items - ĐƠN GIẢN KHÔNG có created_at
        $stmt_items = $db->prepare("
            INSERT INTO order_items (
                order_id, 
                product_id, 
                product_name, 
                product_price, 
                quantity, 
                subtotal
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($_SESSION['cart'] as $item) {
            $subtotal = $item['price'] * $item['quantity'];
            $stmt_items->execute([
                $order_id,
                $item['id'],
                substr($item['name'], 0, 255), // Giới hạn 255 ký tự
                (float)$item['price'],
                (int)$item['quantity'],
                (float)$subtotal
            ]);
            
            // 4. Cập nhật tồn kho (nếu có)
            try {
                $stmt_stock = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt_stock->execute([(int)$item['quantity'], $item['id']]);
            } catch (Exception $e) {
                // Bỏ qua nếu lỗi
            }
        }
        
        $db->commit();
        
        // Thành công
        $_SESSION['last_order'] = [
            'order_id' => $order_id,
            'order_code' => '#' . str_pad($order_id, 6, '0', STR_PAD_LEFT),
            'customer_name' => $customer_name,
            'customer_phone' => $customer_phone,
            'total_amount' => $total_amount
        ];
        
        $_SESSION['cart'] = [];
        $_SESSION['message'] = 'Đặt hàng thành công! Cảm ơn bạn đã mua sắm.';
        $_SESSION['message_type'] = 'success';
        
        header('Location: order_success.php?order_id=' . $order_id);
        exit;
        
    } catch (Exception $e) {
        // Rollback và hiển thị lỗi
        try {
            $db->rollBack();
        } catch (Exception $rbError) {
            // Ignore rollback error
        }
        
        // Lấy thông tin lỗi chi tiết
        $error_info = $db->errorInfo();
        $error_msg = $e->getMessage();
        
        // Log lỗi
        error_log("ORDER ERROR: " . $error_msg);
        error_log("SQL Error Info: " . print_r($error_info, true));
        
        $_SESSION['message'] = "Lỗi khi đặt hàng: " . $error_msg;
        $_SESSION['message_type'] = 'error';
        
        header('Location: cart.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giỏ hàng - Adam Sport</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <style>
        /* Styles giỏ hàng đã được tối ưu */
        :root {
            --primary: #667eea;
            --secondary: #764ba2;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --light: #f8fafc;
            --dark: #1e293b;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, sans-serif;
        }
        
        .cart-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .cart-header {
            background: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .page-title {
            display: flex;
            align-items: center;
            gap: 15px;
            color: var(--dark);
            margin-bottom: 10px;
        }
        
        .page-title i {
            color: var(--primary);
            font-size: 2.5rem;
        }
        
        .cart-badge {
            background: var(--primary);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
        }
        
        /* Alert message */
        .alert {
            padding: 15px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: slideIn 0.3s ease;
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-10px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .alert-warning {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        /* Cart grid */
        .cart-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 30px;
        }
        
        @media (max-width: 992px) {
            .cart-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Cart items */
        .cart-items {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .cart-items-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 2px solid #e2e8f0;
            font-weight: 600;
            color: var(--dark);
        }
        
        .cart-item {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr auto;
            gap: 15px;
            padding: 20px 0;
            border-bottom: 1px solid #e2e8f0;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .cart-item:hover {
            background: var(--light);
        }
        
        .item-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .item-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            background: #f1f5f9;
        }
        
        .item-details h4 {
            margin: 0 0 5px 0;
            color: var(--dark);
            font-size: 16px;
        }
        
        .item-price {
            color: var(--primary);
            font-weight: 600;
        }
        
        .stock-info {
            font-size: 12px;
            color: #64748b;
        }
        
        .stock-warning {
            color: var(--danger);
            font-size: 12px;
        }
        
        /* Quantity control */
        .quantity-control {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .qty-btn {
            width: 32px;
            height: 32px;
            border: 1px solid #cbd5e1;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .qty-btn:hover {
            background: var(--light);
            border-color: var(--primary);
        }
        
        .qty-input {
            width: 50px;
            text-align: center;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            padding: 5px;
            font-weight: 600;
        }
        
        .item-total {
            font-weight: 700;
            color: var(--dark);
        }
        
        .btn-remove {
            color: var(--danger);
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            padding: 8px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        
        .btn-remove:hover {
            background: #fef2f2;
        }
        
        /* Cart summary */
        .cart-summary {
            background: white;
            border-radius: 20px;
            padding: 25px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            position: sticky;
            top: 20px;
        }
        
        .summary-title {
            font-size: 1.4rem;
            color: var(--dark);
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            color: #64748b;
        }
        
        .summary-total {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--dark);
            margin: 20px 0;
            padding-top: 20px;
            border-top: 2px solid #e2e8f0;
        }
        
        /* Checkout form */
        .checkout-form {
            margin-top: 25px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 5px;
            color: var(--dark);
            font-weight: 500;
        }
        
        .form-input {
            width: 100%;
            padding: 10px 15px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .form-input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .payment-methods {
            margin: 20px 0;
        }
        
        .payment-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-top: 10px;
        }
        
        .payment-option {
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .payment-option:hover, .payment-option.active {
            border-color: var(--primary);
            background: #f8faff;
        }
        
        .payment-option input {
            display: none;
        }
        
        .payment-icon {
            font-size: 24px;
            margin-bottom: 5px;
        }
        
        /* Buttons */
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-secondary {
            background: #e2e8f0;
            color: var(--dark);
        }
        
        .btn-secondary:hover {
            background: #cbd5e1;
        }
        
        .btn-danger {
            background: var(--danger);
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-block {
            width: 100%;
            margin-top: 20px;
        }
        
        /* Empty cart */
        .empty-cart {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        }
        
        .empty-cart-icon {
            font-size: 5rem;
            color: #cbd5e1;
            margin-bottom: 20px;
        }
        
        .btn-back {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
            text-decoration: none;
            font-weight: 600;
            margin-top: 20px;
        }
        
        /* Cart actions */
        .cart-actions {
            display: flex;
            gap: 15px;
            margin-top: 25px;
            padding-top: 25px;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
    <div class="cart-wrapper">
        <!-- Header -->
        <div class="cart-header">
            <h1 class="page-title">
                <i class="fas fa-shopping-cart"></i>
                Giỏ Hàng
                <span class="cart-badge">
                    <?php echo count($_SESSION['cart']); ?> sản phẩm
                </span>
            </h1>
            <p style="color: #64748b;">Quản lý và thanh toán đơn hàng của bạn</p>
        </div>

        <!-- Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?>">
                <i class="fas <?php 
                    switch($_SESSION['message_type']) {
                        case 'success': echo 'fa-check-circle'; break;
                        case 'error': echo 'fa-times-circle'; break;
                        case 'warning': echo 'fa-exclamation-triangle'; break;
                    }
                ?>"></i>
                <?php echo $_SESSION['message']; ?>
            </div>
            <?php unset($_SESSION['message'], $_SESSION['message_type']); ?>
        <?php endif; ?>

        <?php if (empty($_SESSION['cart'])): ?>
            <!-- Empty cart -->
            <div class="empty-cart">
                <i class="fas fa-shopping-cart empty-cart-icon"></i>
                <h2>Giỏ hàng trống</h2>
                <p>Chưa có sản phẩm nào trong giỏ hàng của bạn</p>
                <a href="products.php" class="btn btn-primary">
                    <i class="fas fa-store"></i> Mua sắm ngay
                </a>
            </div>
        <?php else: ?>
            <form method="POST" action="cart.php" id="cartForm">
                <div class="cart-grid">
                    <!-- Cart Items -->
                    <div class="cart-items">
                        <div class="cart-items-header">
                            <div>Sản phẩm</div>
                            <div>Đơn giá</div>
                            <div>Số lượng</div>
                            <div>Thành tiền</div>
                            <div></div>
                        </div>

                        <?php 
                        $total_amount = 0;
                        $total_items = 0;
                        
                        foreach ($_SESSION['cart'] as $index => $item): 
                            // Lấy thông tin tồn kho từ CSDL
                            $stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
                            $stmt->execute([$item['id']]);
                            $product_stock = $stmt->fetch(PDO::FETCH_ASSOC);
                            $stock = $product_stock['stock'] ?? 0;
                            
                            $item_total = $item['price'] * $item['quantity'];
                            $total_amount += $item_total;
                            $total_items += $item['quantity'];
                        ?>
                        <div class="cart-item">
                            <div class="item-info">
                                <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                     class="item-image"
                                     onerror="this.src='assets/images/products/default.jpg'">
                                <div class="item-details">
                                    <h4><?php echo htmlspecialchars($item['name']); ?></h4>
                                    <div class="item-price"><?php echo number_format($item['price'], 0, ',', '.'); ?> ₫</div>
                                    <div class="stock-info">
                                        Tồn kho: <?php echo $stock; ?> sản phẩm
                                        <?php if ($item['quantity'] > $stock): ?>
                                            <div class="stock-warning">
                                                <i class="fas fa-exclamation-triangle"></i> 
                                                Vượt quá tồn kho
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="item-price">
                                <?php echo number_format($item['price'], 0, ',', '.'); ?> ₫
                            </div>
                            
                            <div class="quantity-control">
                                <button type="button" class="qty-btn" onclick="updateQty(<?php echo $index; ?>, -1)">-</button>
                                <input type="number" 
                                       name="quantity[<?php echo $item['id']; ?>]" 
                                       value="<?php echo $item['quantity']; ?>"
                                       min="1" 
                                       max="<?php echo $stock; ?>"
                                       class="qty-input"
                                       onchange="updateTotal()"
                                       data-index="<?php echo $index; ?>">
                                <button type="button" class="qty-btn" onclick="updateQty(<?php echo $index; ?>, 1)">+</button>
                            </div>
                            
                            <div class="item-total">
                                <?php echo number_format($item_total, 0, ',', '.'); ?> ₫
                            </div>
                            
                            <a href="cart.php?remove=<?php echo $item['id']; ?>" 
                               class="btn-remove"
                               onclick="return confirm('Xóa sản phẩm này?')">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                        <?php endforeach; ?>
                        
                        <!-- Cart Actions -->
                        <div class="cart-actions">
                            <button type="submit" name="update_quantity" class="btn btn-secondary">
                                <i class="fas fa-sync-alt"></i> Cập nhật
                            </button>
                            <button type="submit" name="clear_cart" 
                                    class="btn btn-danger"
                                    onclick="return confirm('Xóa toàn bộ giỏ hàng?')">
                                <i class="fas fa-trash"></i> Xóa tất cả
                            </button>
                            <a href="products.php" class="btn" style="background: #e2e8f0;">
                                <i class="fas fa-plus"></i> Thêm sản phẩm
                            </a>
                        </div>
                    </div>

                    <!-- Checkout Form -->
                    <div class="cart-summary">
                        <h3 class="summary-title">Thông tin thanh toán</h3>
                        
                        <!-- Customer Info -->
                        <div class="form-group">
                            <label class="form-label">Họ tên *</label>
                            <input type="text" name="customer_name" class="form-input" required
                                   value="<?php echo $_SESSION['user']['name'] ?? ''; ?>"
                                   placeholder="Nguyễn Văn A">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Số điện thoại *</label>
                            <input type="tel" name="customer_phone" class="form-input" required
                                   value="<?php echo $_SESSION['user']['phone'] ?? ''; ?>"
                                   placeholder="0901234567">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Email</label>
                            <input type="email" name="customer_email" class="form-input"
                                   value="<?php echo $_SESSION['user']['email'] ?? ''; ?>"
                                   placeholder="email@example.com">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Địa chỉ giao hàng</label>
                            <textarea name="customer_address" class="form-input" rows="2"
                                      placeholder="Số nhà, đường, phường, quận, thành phố"></textarea>
                        </div>
                        
                        <!-- Order Summary -->
                        <div class="summary-row">
                            <span>Tạm tính (<?php echo $total_items; ?> sản phẩm)</span>
                            <span><?php echo number_format($total_amount, 0, ',', '.'); ?> ₫</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Phí vận chuyển</span>
                            <span>0 ₫</span>
                        </div>
                        
                        <div class="summary-total">
                            <span>Tổng cộng</span>
                            <span id="totalDisplay"><?php echo number_format($total_amount, 0, ',', '.'); ?> ₫</span>
                        </div>
                        
                        <!-- Payment Methods -->
                        <div class="payment-methods">
                            <label class="form-label">Phương thức thanh toán</label>
                            <div class="payment-options">
                                <label class="payment-option active">
                                    <input type="radio" name="payment_method" value="cod" checked>
                                    <div class="payment-icon">💵</div>
                                    <div>Tiền mặt</div>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="banking">
                                    <div class="payment-icon">🏦</div>
                                    <div>Chuyển khoản</div>
                                </label>
                                <label class="payment-option">
                                    <input type="radio" name="payment_method" value="momo">
                                    <div class="payment-icon">💜</div>
                                    <div>Momo</div>
                                </label>
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div class="form-group">
                            <label class="form-label">Ghi chú đơn hàng</label>
                            <textarea name="notes" class="form-input" rows="2"
                                      placeholder="Ghi chú thêm về đơn hàng..."></textarea>
                        </div>
                        
                        <!-- Checkout Button -->
                        <button type="submit" name="checkout" class="btn btn-primary btn-block">
                            <i class="fas fa-credit-card"></i>
                            Đặt hàng ngay
                        </button>
                        
                        <!-- Continue shopping -->
                        <a href="products.php" class="btn-back">
                            <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                        </a>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Quantity control
        function updateQty(index, change) {
            const inputs = document.querySelectorAll('.qty-input');
            const input = inputs[index];
            let value = parseInt(input.value) + change;
            const max = parseInt(input.max);
            
            if (value < 1) value = 1;
            if (value > max) value = max;
            
            input.value = value;
            updateTotal();
        }
        
        // Update total
        function updateTotal() {
            let total = 0;
            const items = document.querySelectorAll('.cart-item');
            
            items.forEach(item => {
                const price = parseInt(item.querySelector('.item-price').textContent.replace(/[^0-9]/g, ''));
                const qty = parseInt(item.querySelector('.qty-input').value);
                const itemTotal = price * qty;
                
                item.querySelector('.item-total').textContent = itemTotal.toLocaleString('vi-VN') + ' ₫';
                total += itemTotal;
            });
            
            document.getElementById('totalDisplay').textContent = total.toLocaleString('vi-VN') + ' ₫';
        }
        
        // Payment option selection
        document.querySelectorAll('.payment-option').forEach(option => {
            option.addEventListener('click', function() {
                document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('active'));
                this.classList.add('active');
                this.querySelector('input').checked = true;
            });
        });
        
        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            if (e.submitter.name === 'checkout') {
                const name = document.querySelector('input[name="customer_name"]').value.trim();
                const phone = document.querySelector('input[name="customer_phone"]').value.trim();
                
                if (!name || !phone) {
                    e.preventDefault();
                    Swal.fire('Thiếu thông tin', 'Vui lòng nhập đầy đủ họ tên và số điện thoại', 'warning');
                }
            }
        });
    </script>
</body>
</html>