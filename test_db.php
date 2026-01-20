<?php
require_once 'config/database.php';

try {
    $db = connectDB();
    echo "✅ Kết nối database thành công!<br>";
    
    // Đếm số sản phẩm
    $stmt = $db->query("SELECT COUNT(*) as total FROM products");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📦 Số sản phẩm: " . $result['total'] . "<br>";
    
    // Đếm số danh mục
    $stmt = $db->query("SELECT COUNT(*) as total FROM categories");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "📁 Số danh mục: " . $result['total'] . "<br>";
    
} catch(PDOException $e) {
    echo "❌ Lỗi kết nối database: " . $e->getMessage();
}
?>