<?php
// contact_process.php
session_start();

require_once 'config/database.php';

// Kiểm tra nếu form được submit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Lấy và validate dữ liệu
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    
    $errors = [];
    
    // Validate dữ liệu
    if (empty($name)) {
        $errors[] = "Vui lòng nhập họ và tên";
    }
    
    if (empty($email)) {
        $errors[] = "Vui lòng nhập email";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email không hợp lệ";
    }
    
    if (empty($message)) {
        $errors[] = "Vui lòng nhập nội dung tin nhắn";
    } elseif (strlen($message) < 10) {
        $errors[] = "Nội dung tin nhắn quá ngắn (tối thiểu 10 ký tự)";
    }
    
    // Nếu có lỗi, hiển thị thông báo
    if (!empty($errors)) {
        $_SESSION['contact_errors'] = $errors;
        $_SESSION['contact_old_data'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'message' => $message
        ];
        header('Location: introduce.php#contact');
        exit;
    }
    
    try {
        // Kết nối database
        $db = connectDB();
        
        // Lưu tin nhắn vào database
        $stmt = $db->prepare("
            INSERT INTO contact_messages (name, email, phone, message, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $name,
            $email,
            $phone,
            $message,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        ]);
        
        // Gửi email thông báo (tùy chọn)
        sendNotificationEmail($name, $email, $phone, $message);
        
        // Thông báo thành công
        $_SESSION['contact_success'] = "Cảm ơn bạn đã liên hệ! Chúng tôi sẽ phản hồi sớm nhất.";
        
        // Xóa dữ liệu cũ
        unset($_SESSION['contact_old_data']);
        
        header('Location: introduce.php#contact');
        exit;
        
    } catch (PDOException $e) {
        error_log("Contact form error: " . $e->getMessage());
        $_SESSION['contact_errors'] = ["Có lỗi xảy ra khi gửi tin nhắn. Vui lòng thử lại sau."];
        header('Location: introduce.php#contact');
        exit;
    }
} else {
    // Nếu không phải POST request, chuyển hướng về trang liên hệ
    header('Location: introduce.php#contact');
    exit;
}

// Hàm gửi email thông báo
function sendNotificationEmail($name, $email, $phone, $message) {
    $to = "info@adamsport.com";
    $subject = "Tin nhắn liên hệ mới từ Adam Sport";
    
    $email_content = "
    <h2>Tin nhắn liên hệ mới</h2>
    <p><strong>Họ và tên:</strong> $name</p>
    <p><strong>Email:</strong> $email</p>
    <p><strong>Điện thoại:</strong> " . ($phone ?: 'Không cung cấp') . "</p>
    <p><strong>Nội dung:</strong></p>
    <div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>
        " . nl2br(htmlspecialchars($message)) . "
    </div>
    <p><strong>Thời gian:</strong> " . date('d/m/Y H:i:s') . "</p>
    <p><strong>IP:</strong> " . $_SERVER['REMOTE_ADDR'] . "</p>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Adam Sport <noreply@adamsport.com>" . "\r\n";
    
    @mail($to, $subject, $email_content, $headers);
}
?>