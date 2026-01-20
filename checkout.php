<?php
session_start();
header('Content-Type: application/json');

try {
    require_once '../config/database.php';
    $db = connectDB();
    
    // Nhận dữ liệu từ client
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Kiểm tra giỏ hàng
    if (empty($_SESSION['cart'])) {
        echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống']);
        exit;
    }
    
    // Tạo mã đơn hàng
    $order_code = 'ADAM' . date('Ymd') . strtoupper(substr(uniqid(), 0, 6));
    
    // Tổng tiền
    $total_amount = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total_amount += ($item['price'] * $item['quantity']);
    }
    
    // Lưu đơn hàng vào CSDL
    $stmt = $db->prepare("
        INSERT INTO orders (order_code, customer_name, customer_phone, total_amount, payment_method, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    
    // Lấy thông tin khách hàng từ session (cần thêm sau)
    $customer_name = $_SESSION['user']['name'] ?? 'Khách vãng lai';
    $customer_phone = $_SESSION['user']['phone'] ?? '';
    
    $stmt->execute([
        $order_code,
        $customer_name,
        $customer_phone,
        $total_amount,
        $data['payment_method'] ?? 'cod'
    ]);
    
    $order_id = $db->lastInsertId();
    
    // Lưu chi tiết đơn hàng
    $stmt_items = $db->prepare("
        INSERT INTO order_items (order_id, product_id, product_name, product_price, quantity, subtotal)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($_SESSION['cart'] as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $stmt_items->execute([
            $order_id,
            $item['id'],
            $item['name'],
            $item['price'],
            $item['quantity'],
            $subtotal
        ]);
        
        // Cập nhật số lượng tồn kho
        $stmt_update = $db->prepare("
            UPDATE products SET stock = stock - ? WHERE id = ?
        ");
        $stmt_update->execute([$item['quantity'], $item['id']]);
    }
    
    // Xóa giỏ hàng
    $_SESSION['cart'] = [];
    
    // Trả về kết quả
    echo json_encode([
        'success' => true,
        'message' => 'Đặt hàng thành công',
        'order_id' => $order_id,
        'order_code' => $order_code
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Lỗi hệ thống: ' . $e->getMessage()
    ]);
}
?>