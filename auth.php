<?php
session_start();
require_once 'config/database.php';

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    // Đăng ký user mới
    public function register($data) {
        $errors = [];
        
        // Validate dữ liệu
        if (empty($data['full_name'])) {
            $errors[] = "Họ tên không được để trống";
        }
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Email không hợp lệ";
        } else {
            // Kiểm tra email đã tồn tại
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->rowCount() > 0) {
                $errors[] = "Email này đã được sử dụng";
            }
        }
        
        if (strlen($data['password']) < 6) {
            $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";
        }
        
        if ($data['password'] !== $data['confirm_password']) {
            $errors[] = "Mật khẩu xác nhận không khớp";
        }
        
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }
        
        // Hash password
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (full_name, email, password, phone, address, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->execute([
                $data['full_name'],
                $data['email'],
                $hashedPassword,
                $data['phone'] ?? '',
                $data['address'] ?? ''
            ]);
            
            return ['success' => true, 'message' => 'Đăng ký thành công!'];
            
        } catch(PDOException $e) {
            return ['success' => false, 'errors' => ['Có lỗi xảy ra: ' . $e->getMessage()]];
        }
    }
    
    // Đăng nhập
    public function login($email, $password) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM users 
                WHERE email = ? AND is_active = 1
            ");
            
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Lưu thông tin user vào session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_avatar'] = $user['avatar'];
                $_SESSION['is_admin'] = $user['is_admin'];
                
                // Cập nhật last login
                $this->updateLastLogin($user['id']);
                
                return ['success' => true, 'user' => $user];
            }
            
            return ['success' => false, 'error' => 'Email hoặc mật khẩu không đúng'];
            
        } catch(PDOException $e) {
            return ['success' => false, 'error' => 'Có lỗi xảy ra: ' . $e->getMessage()];
        }
    }
    
    private function updateLastLogin($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$userId]);
    }
    
    // Kiểm tra user đã đăng nhập
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Kiểm tra admin
    public static function isAdmin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
    }
    
    // Đăng xuất
    public static function logout() {
        session_destroy();
        header('Location: index.php');
        exit();
    }
    
    // Lấy thông tin user hiện tại
    public static function getUser() {
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'name' => $_SESSION['user_name'],
                'avatar' => $_SESSION['user_avatar'] ?? 'default_avatar.jpg',
                'is_admin' => $_SESSION['is_admin'] ?? 0
            ];
        }
        return null;
    }
}

// Khởi tạo auth
$auth = new Auth($pdo);

// Xử lý logout
if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    Auth::logout();
}
?>