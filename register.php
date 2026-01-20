<?php
session_start();
require_once 'config/database.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Kiểm tra lỗi
    if (empty($full_name)) $errors[] = "Vui lòng nhập họ tên";
    if (empty($email)) $errors[] = "Vui lòng nhập email";
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email không hợp lệ";
    if (strlen($password) < 6) $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";
    if ($password !== $confirm_password) $errors[] = "Mật khẩu xác nhận không khớp";
    
    if (empty($errors)) {
        try {
            $db = connectDB();
            
            // Kiểm tra email đã tồn tại chưa
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                $errors[] = "Email này đã được đăng ký";
            } else {
                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                
                // Thêm user vào database
                $stmt = $db->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
                $stmt->execute([$full_name, $email, $hashed_password]);
                
                $success = "Đăng ký thành công! <a href='login.php' style='color:#007bff;'>Đăng nhập ngay</a>";
            }
            
        } catch(PDOException $e) {
            $errors[] = "Lỗi hệ thống, vui lòng thử lại!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký - Adam Sport</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .register-box {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
            width: 100%;
            max-width: 450px;
        }
        
        .logo {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo h1 {
            color: #007bff;
            font-size: 28px;
        }
        
        .logo p {
            color: #666;
            margin-top: 5px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: bold;
        }
        
        .form-group input {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            transition: border 0.3s;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #007bff;
        }
        
        .btn-register {
            width: 100%;
            padding: 14px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 10px;
        }
        
        .btn-register:hover {
            background: #218838;
        }
        
        .error-box {
            background: #ffebee;
            color: #d32f2f;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: 1px solid #ffcdd2;
        }
        
        .error-box ul {
            margin-left: 20px;
        }
        
        .success-box {
            background: #e8f5e9;
            color: #388e3c;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #c8e6c9;
        }
        
        .links {
            text-align: center;
            margin-top: 25px;
            color: #666;
        }
        
        .links a {
            color: #007bff;
            text-decoration: none;
        }
        
        .links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-box">
        <div class="logo">
            <h1>🏸 Adam Sport</h1>
            <p>Đăng ký tài khoản mới</p>
        </div>
        
        <?php if(!empty($errors)): ?>
            <div class="error-box">
                <strong>Lỗi:</strong>
                <ul>
                    <?php foreach($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if($success): ?>
            <div class="success-box"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label>Họ và tên:</label>
                <input type="text" name="full_name" required placeholder="Nguyễn Văn A">
            </div>
            
            <div class="form-group">
                <label>Email:</label>
                <input type="email" name="email" required placeholder="example@gmail.com">
            </div>
            
            <div class="form-group">
                <label>Mật khẩu:</label>
                <input type="password" name="password" required placeholder="Ít nhất 6 ký tự">
            </div>
            
            <div class="form-group">
                <label>Xác nhận mật khẩu:</label>
                <input type="password" name="confirm_password" required placeholder="Nhập lại mật khẩu">
            </div>
            
            <button type="submit" class="btn-register">Đăng ký tài khoản</button>
        </form>
        
        <div class="links">
            <p>Đã có tài khoản? <a href="login.php">Đăng nhập ngay</a></p>
            <p><a href="index.php">← Quay lại trang chủ</a></p>
        </div>
    </div>
</body>
</html>