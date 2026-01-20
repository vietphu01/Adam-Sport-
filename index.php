<?php
session_start();
// XỬ LÝ TRẠNG THÁI CHATBOT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat_message'])) {
    // Luôn mở chatbot khi có tin nhắn mới
    $_SESSION['chatbot_open'] = true;
}

// Kiểm tra trạng thái chatbot
$chatbot_open = $_SESSION['chatbot_open'] ?? false;

require_once 'config/database.php';

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// LẤY SẢN PHẨM NỔI BẬT TỪ DATABASE
try {
    $db = connectDB();
    
    // Sản phẩm nổi bật (8 sản phẩm mới nhất)
    $stmt = $db->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.is_active = 1 
        ORDER BY p.created_at DESC 
        LIMIT 8
    ");
    $featured_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Sản phẩm bán chạy (có thể dựa trên số lượng tồn kho thấp)
    $stmt = $db->query("
        SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.is_active = 1 
        ORDER BY p.stock ASC, p.created_at DESC 
        LIMIT 4
    ");
    $bestseller_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Thống kê
    $stats_stmt = $db->query("SELECT COUNT(*) as total_products FROM products WHERE is_active = 1");
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
} catch(PDOException $e) {
    $featured_products = [];
    $bestseller_products = [];
    $stats = ['total_products' => 0];
    error_log("Database error: " . $e->getMessage());
}

// XỬ LÝ TÌM KIẾM THÔNG MINH
$search_results = [];
$search_query = '';
$search_filters = [
    'category' => $_GET['category'] ?? '',
    'price_min' => $_GET['price_min'] ?? '',
    'price_max' => $_GET['price_max'] ?? '',
    'in_stock' => isset($_GET['in_stock']) ? true : false,
    'sort_by' => $_GET['sort_by'] ?? 'relevance'
];

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = trim($_GET['search']);
    try {
        $db = connectDB();
        
        // Tách từ khóa thành các từ riêng biệt để tìm kiếm tốt hơn
        $keywords = preg_split('/\s+/', $search_query);
        
        // Xây dựng câu truy vấn linh hoạt
        $sql = "
            SELECT p.*, c.name as category_name,
                   (CASE 
                      WHEN p.name LIKE ? THEN 10
                      WHEN p.name LIKE ? THEN 8
                      WHEN p.description LIKE ? THEN 6
                      WHEN p.name LIKE ? THEN 4
                      WHEN p.description LIKE ? THEN 2
                      ELSE 0
                   END) as relevance_score
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.is_active = 1 
        ";
        
        $params = [];
        
        // Thêm điều kiện tìm kiếm theo từ khóa
        $keyword_conditions = [];
        foreach ($keywords as $keyword) {
            if (strlen($keyword) >= 2) { // Chỉ tìm từ có 2 ký tự trở lên
                $keyword_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
                $params[] = "%$keyword%";
                $params[] = "%$keyword%";
            }
        }
        
        if (!empty($keyword_conditions)) {
            $sql .= " AND (" . implode(" OR ", $keyword_conditions) . ")";
        } else {
            // Nếu không có từ khóa hợp lệ, tìm tất cả sản phẩm
            $sql .= " AND 1=1";
        }
        
        // Thêm các bộ lọc
        if (!empty($search_filters['category']) && is_numeric($search_filters['category'])) {
            $sql .= " AND p.category_id = ?";
            $params[] = $search_filters['category'];
        }
        
        if (!empty($search_filters['price_min']) && is_numeric($search_filters['price_min'])) {
            $sql .= " AND p.price >= ?";
            $params[] = $search_filters['price_min'];
        }
        
        if (!empty($search_filters['price_max']) && is_numeric($search_filters['price_max'])) {
            $sql .= " AND p.price <= ?";
            $params[] = $search_filters['price_max'];
        }
        
        if ($search_filters['in_stock']) {
            $sql .= " AND p.stock > 0";
        }
        
        // Sắp xếp kết quả
        switch ($search_filters['sort_by']) {
            case 'price_asc':
                $sql .= " ORDER BY p.price ASC";
                break;
            case 'price_desc':
                $sql .= " ORDER BY p.price DESC";
                break;
            case 'name_asc':
                $sql .= " ORDER BY p.name ASC";
                break;
            case 'name_desc':
                $sql .= " ORDER BY p.name DESC";
                break;
            case 'newest':
                $sql .= " ORDER BY p.created_at DESC";
                break;
            case 'stock':
                $sql .= " ORDER BY p.stock DESC";
                break;
            default: // relevance
                $sql .= " ORDER BY relevance_score DESC, p.created_at DESC";
                break;
        }
        
        // Thêm các tham số relevance (phải thêm sau cùng vì chúng là tham số của SELECT)
        array_unshift($params, 
            "%$search_query%",      // Tên chứa toàn bộ cụm từ
            "% " . $search_query . " %", // Tên chứa cụm từ như một từ riêng biệt
            "%$search_query%",      // Mô tả chứa toàn bộ cụm từ
            "%" . implode("%", $keywords) . "%", // Tên chứa tất cả từ khóa (không quan tâm thứ tự)
            "%" . implode("%", $keywords) . "%"  // Mô tả chứa tất cả từ khóa
        );
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $search_results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Gợi ý tìm kiếm và sửa lỗi chính tả
        $search_suggestions = generateSearchSuggestions($search_query, $db);
        
    } catch(PDOException $e) {
        error_log("Search error: " . $e->getMessage());
        $search_error = "Có lỗi xảy ra khi tìm kiếm. Vui lòng thử lại!";
    }
}

// Hàm tạo gợi ý tìm kiếm
function generateSearchSuggestions($query, $db) {
    $suggestions = [];
    
    try {
        // Gợi ý từ sản phẩm có tên tương tự
        $stmt = $db->prepare("
            SELECT DISTINCT name 
            FROM products 
            WHERE name LIKE ? AND is_active = 1 
            ORDER BY 
                CASE 
                    WHEN name = ? THEN 1
                    WHEN name LIKE ? THEN 2
                    ELSE 3
                END,
                name
            LIMIT 5
        ");
        $stmt->execute(["%$query%", $query, "$query%"]);
        $product_suggestions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Gợi ý từ danh mục
        $stmt = $db->prepare("
            SELECT DISTINCT c.name 
            FROM categories c
            JOIN products p ON c.id = p.category_id
            WHERE c.name LIKE ? AND p.is_active = 1
            LIMIT 3
        ");
        $stmt->execute(["%$query%"]);
        $category_suggestions = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $suggestions = array_merge($product_suggestions, $category_suggestions);
        
    } catch(PDOException $e) {
        error_log("Search suggestions error: " . $e->getMessage());
    }
    
    return array_slice($suggestions, 0, 5); // Giới hạn 5 gợi ý
}

// XỬ LÝ CHATBOT AI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['chat_message'])) {
    $user_message = trim($_POST['chat_message']);
    
    if (!empty($user_message)) {
        // Khởi tạo lịch sử chat nếu chưa có
        if (!isset($_SESSION['chat_history'])) {
            $_SESSION['chat_history'] = [];
        }
        
        // Thêm tin nhắn user vào lịch sử
        $_SESSION['chat_history'][] = [
            'type' => 'user',
            'message' => $user_message,
            'time' => time()
        ];
        
        // Gọi AI chatbot API
        $ai_response = callAIChatbot($user_message);
        
        // Thêm phản hồi AI vào lịch sử
        $_SESSION['chat_history'][] = [
            'type' => 'bot', 
            'message' => $ai_response,
            'time' => time()
        ];
        
        // Giới hạn lịch sử chat (giữ 20 tin nhắn gần nhất)
        if (count($_SESSION['chat_history']) > 20) {
            $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -20);
        }
        
        // Redirect để tránh resubmit form
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    }
}

function callAIChatbot($message) {
    $url = "http://localhost:5001/api/ai-chat";
    
    $data = [
        'message' => $message,
        'session_id' => session_id()
    ];
    
    $options = [
        'http' => [
            'header'  => "Content-Type: application/json\r\n",
            'method'  => 'POST', 
            'content' => json_encode($data, JSON_UNESCAPED_UNICODE),
            'timeout' => 20,
            'ignore_errors' => true
        ]
    ];
    
    $context = stream_context_create($options);
    
    // Thử kết nối 2 lần
    for ($i = 0; $i < 2; $i++) {
        try {
            $result = @file_get_contents($url, false, $context);
            
            if ($result !== FALSE) {
                $response_data = json_decode($result, true);
                if (isset($response_data['reply']) && !empty($response_data['reply'])) {
                    return $response_data['reply'];
                }
            }
            
            // Nếu lỗi, đợi 1 giây rồi thử lại
            if ($i < 1) {
                sleep(1);
            }
            
        } catch (Exception $e) {
            error_log("AI Chatbot Attempt " . ($i+1) . " failed: " . $e->getMessage());
            if ($i < 1) sleep(1);
        }
    }
    
    // Nếu cả 2 lần đều thất bại, dùng fallback thông minh
    return getSmartFallbackResponse($message);
}

function getSmartFallbackResponse($message) {
    $message_lower = strtolower($message);
    
    // Phân tích câu hỏi phức tạp
    if (strpos($message_lower, 'mới chơi') !== false && 
        (strpos($message_lower, '1.5') !== false || strpos($message_lower, '1,5') !== false)) {
        
        return "💫 **GỢI Ý CHO NGƯỜI MỚI CHƠI - 1.5 TRIỆU:**\n\n🏸 **Lining Windstorm 72** (1.2TR)\n• Nhẹ 75g, cân bằng even - dễ sử dụng\n• Phù hợp người mới bắt đầu\n• Tặng kèm 3 cầu Victor\n\n🏸 **Yonex Nanoray 10F** (1.5TR)\n• Lực đánh ổn định, dễ phát lực  \n• Nâng cao kỹ thuật cơ bản\n• Bảo hành 6 tháng chính hãng\n\n🏸 **Victor Bravesword 12** (1.3TR)\n• Độ cứng trung bình, êm tay\n• Bền bỉ, tập luyện lâu dài\n\n🔧 **LỜI KHUYÊN:** Nên chọn vợt nhẹ, cân bằng để dễ làm quen!\n\nBạn muốn tôi tư vấn kỹ hơn về vợt nào?";
    }
    
    // Fallback đơn giản
    $responses = [
        'chào' => "Xin chào bạn! 🏸 Tôi là chuyên gia tư vấn Adam Sport. Tôi có thể giúp gì về vợt cầu lông, giày badminton, và phụ kiện chính hãng?",
        'vợt' => "Tôi có thể tư vấn vợt cầu lông phù hợp! Hãy cho tôi biết:\n• Trình độ của bạn?\n• Ngân sách cụ thể?\n• Thương hiệu ưa thích?",
        'giày' => "👟 **GIÀY CẦU LÔNG:**\n• Yonex Eclipsion Z2 (2.8TR)\n• Mizuno Wave Lightning (2.2TR)  \n• Victor P9200 (2.5TR)\n\nBạn cần size bao nhiêu?",
        'khuyến mãi' => "🎁 **KHUYẾN MÃI ADAM SPORT:**\n• Giảm 10% đơn >3TR\n• Tặng 3 cầu khi mua vợt\n• Free ship nội thành\n• Hotline: 0788 500 585"
    ];
    
    foreach ($responses as $keyword => $response) {
        if (strpos($message_lower, $keyword) !== false) {
            return $response;
        }
    }
    
    return "Tôi có thể tư vấn chuyên sâu về dụng cụ cầu lông! 🏸\n\nHãy cho tôi biết:\n• Bạn cần tư vấn vợt/giày/phụ kiện?\n• Trình độ và ngân sách?\n• Thương hiệu yêu thích?\n\nTôi sẽ đề xuất sản phẩm phù hợp nhất! 💪";
}


// Hàm sửa lỗi chính tả đơn giản (có thể tích hợp thư viện nâng cao sau)
function spellCheck($word) {
    $common_mistakes = [
        'vot' => 'vợt',
        'cau long' => 'cầu lông',
        'giay' => 'giày',
        'yonex' => 'yonex',
        'victor' => 'victor',
        'lining' => 'lining'
    ];
    
    return $common_mistakes[strtolower($word)] ?? $word;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Adam Sport - Dụng cụ cầu lông chính hãng</title>
    <meta name="description" content="Adam Sport chuyên cung cấp dụng cụ cầu lông chính hãng: Vợt Yonex, Victor, Lining, Giày thể thao, Cầu lông và phụ kiện.">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/chatbot.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body>
    <!-- Header -->
    <header class="header">
        <div class="container">
            <div class="logo">
                <h1><i class="fas table-tennis-paddle-ball"></i> Adam Sport</h1>
                <p class="logo-tagline">Dụng cụ cầu lông chính hãng</p>
            </div>
            
            <nav class="nav">
                <a href="index.php" class="nav-link active">
                    <i class="fas fa-home"></i> Trang chủ
                </a>
                <a href="products.php" class="nav-link">
                    <i class="fas fa-store"></i> Sản phẩm
                </a>
                <a href="introduce.php" class="nav-link">
                    <i class="fas fa-info-circle"></i> Giới thiệu 
                    <i class="fas fa-phone"></i> Liên hệ
                </a>
                <a href="cart.php" class="nav-link cart-link">
                    <i class="fas fa-shopping-cart"></i>
                    Giỏ hàng <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                </a>
                <a href="order_tracking.php" class="nav-link">
                    <i class="fas fa-clipboard-list"></i> Đơn hàng
                </a>
                <a href="login.php" class="nav-link admin-link">
                    <i class="fas fa-user-cog"></i> Đăng nhập
                </a>
                
            </nav>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Chuyên Dụng Cụ Cầu Lông Chính Hãng</h1>
                <p class="hero-subtitle">Yonex • Victor • Lining • Mizuno • VICTOR</p>
                <p class="hero-description">Cung cấp các sản phẩm chất lượng cao với giá tốt nhất thị trường</p>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number"><?php echo $stats['total_products']; ?>+</span>
                        <span class="stat-label">Sản phẩm</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">100%</span>
                        <span class="stat-label">Chính hãng</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">24/7</span>
                        <span class="stat-label">Hỗ trợ</span>
                    </div>
                </div>
                <div class="hero-actions">
                    <a href="products.php" class="btn btn-primary btn-large">
                        <i class="fas fa-shopping-bag"></i> Mua sắm ngay
                    </a>
                    <a href="#featured" class="btn btn-secondary btn-large">
                        <i class="fas fa-star"></i> Sản phẩm nổi bật
                    </a>
                    <!-- Thanh tìm kiếm nâng cao -->
                    <div class="search-bar">
                        <form method="GET" action="index.php" class="search-form" id="searchForm">
                            <h3>Tìm kiếm thông minh</h3>
                            <div class="search-container">
                                <input type="text" name="search" placeholder="Tìm kiếm sản phẩm, thương hiệu, danh mục..." 
                                    value="<?php echo htmlspecialchars($search_query); ?>" 
                                    class="search-input" 
                                    id="searc   hInput"
                                    autocomplete="off">
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        
                        <!-- Gợi ý tìm kiếm -->
                        <?php if (!empty($search_suggestions) && !empty($search_query)): ?>
                        <div class="search-suggestions">
                            <p><strong>Gợi ý tìm kiếm:</strong></p>
                            <?php foreach ($search_suggestions as $suggestion): ?>
                                <a href="index.php?search=<?php echo urlencode($suggestion); ?>" class="suggestion-item">
                                    <i class="fas fa-search"></i> <?php echo htmlspecialchars($suggestion); ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
    </section>

    <!-- Kết quả tìm kiếm -->
    <?php if (!empty($search_query)): ?>
    <section class="search-results">
        <div class="container"> 
            <h2>Kết quả tìm kiếm cho "<?php echo htmlspecialchars($search_query); ?>"</h2>
            <p class="search-count">Tìm thấy <?php echo count($search_results); ?> sản phẩm</p>
            
            <?php if (!empty($search_results)): ?>
            <div class="products-grid">
                <?php foreach ($search_results as $product): ?>
                <div class="product-card">
                    <div class="product-badge">MỚI</div>
                    <img src="<?php echo $product['image_url']; ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>"
                         onerror="this.src='assets/images/products/default.jpg'">
                    <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                    <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                    <p class="product-description"><?php echo htmlspecialchars($product['description'] ?? $product['name']); ?></p>
                    <p class="product-price"><?php echo number_format($product['price']); ?> VNĐ</p>
                    <div class="product-stock">
                        <i class="fas fa-box"></i> 
                        <?php echo $product['stock'] > 0 ? 'Còn ' . $product['stock'] . ' sản phẩm' : 'Hết hàng'; ?>
                    </div>
                    <form method="POST" action="cart.php">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                        <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                        <input type="hidden" name="product_image" value="<?php echo $product['image_url']; ?>">
                        <button type="submit" name="add_to_cart" class="btn btn-add-cart" 
                                <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                            <i class="fas fa-cart-plus"></i> 
                            <?php echo $product['stock'] > 0 ? 'Thêm vào giỏ' : 'Hết hàng'; ?>
                        </button>
                    </form>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="no-results">
                <i class="fas fa-search fa-3x"></i>
                <h3>Không tìm thấy sản phẩm phù hợp</h3>
                <p>Hãy thử tìm kiếm với từ khóa khác hoặc <a href="products.php">xem tất cả sản phẩm</a></p>
            </div>
            <?php endif; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Products -->
    <section id="featured" class="featured-products">
        <div class="container">
            <div class="section-header">
                <h2>Sản Phẩm Mới Nhất</h2>
                <p>Khám phá những sản phẩm mới nhất từ các thương hiệu hàng đầu</p>
                <a href="products.php" class="view-all">Xem tất cả <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="products-grid">
                <?php if (!empty($featured_products)): ?>
                    <?php foreach ($featured_products as $product): ?>
                    <div class="product-card">
                        <div class="product-badge">MỚI</div>
                        <img src="<?php echo $product['image_url']; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='assets/images/products/default.jpg'">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                        <p class="product-description"><?php echo htmlspecialchars($product['description'] ?? $product['name']); ?></p>
                        <p class="product-price"><?php echo number_format($product['price']); ?> VNĐ</p>
                        <div class="product-stock">
                            <i class="fas fa-box"></i> 
                            <?php echo $product['stock'] > 0 ? 'Còn ' . $product['stock'] . ' sản phẩm' : 'Hết hàng'; ?>
                        </div>
                        <form method="POST" action="cart.php">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                            <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                            <input type="hidden" name="product_image" value="<?php echo $product['image_url']; ?>">
                            <button type="submit" name="add_to_cart" class="btn btn-add-cart" 
                                    <?php echo $product['stock'] <= 0 ? 'disabled' : ''; ?>>
                                <i class="fas fa-cart-plus"></i> 
                                <?php echo $product['stock'] > 0 ? 'Thêm vào giỏ' : 'Hết hàng'; ?>
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-products">
                        <i class="fas fa-box-open fa-3x"></i>
                        <h3>Chưa có sản phẩm nào</h3>
                        <p>Hãy thêm sản phẩm trong trang quản trị!</p>
                        <a href="admin/login.php" class="btn btn-primary">Đăng nhập Admin</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Bestseller Products -->
    <section class="bestseller-products">
        <div class="container">
            <div class="section-header">
                <h2>Sản Phẩm Bán Chạy</h2>
                <p>Những sản phẩm được yêu thích nhất</p>
            </div>
            <div class="products-grid">
                <?php if (!empty($bestseller_products)): ?>
                    <?php foreach ($bestseller_products as $product): ?>
                    <div class="product-card bestseller-card">
                        <div class="product-badge hot">HOT</div>
                        <img src="<?php echo $product['image_url']; ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>"
                             onerror="this.src='assets/images/products/default.jpg'">
                        <h3><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                        <p class="product-price"><?php echo number_format($product['price']); ?> VNĐ</p>
                        <div class="product-stock">
                            <i class="fas fa-fire"></i> 
                            <?php echo $product['stock'] > 0 ? 'Còn ' . $product['stock'] . ' sản phẩm' : 'Sắp hết hàng'; ?>
                        </div>
                        <form method="POST" action="cart.php">
                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                            <input type="hidden" name="product_name" value="<?php echo htmlspecialchars($product['name']); ?>">
                            <input type="hidden" name="product_price" value="<?php echo $product['price']; ?>">
                            <input type="hidden" name="product_image" value="<?php echo $product['image_url']; ?>">
                            <button type="submit" name="add_to_cart" class="btn btn-add-cart">
                                <i class="fas fa-cart-plus"></i> Thêm vào giỏ
                            </button>
                        </form>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="footer-content">
            <div class="footer-section footer-about">
                <h3>THÔNG TIN CHUNG</h3>
                <p>Adam Sports là hệ thống cửa hàng cầu lông với hơn 50 chi nhánh trên toàn quốc, cung cấp sỉ và lẻ các ngữ hàng dụng cụ cầu lông từ phong trào tới chuyên nghiệp.</p>
                <p>Với sứ mệnh: "Adam Sports cam kết mang đến những sản phẩm, dịch vụ chất lượng tốt nhất phục vụ cho người chơi thể thao để nâng cao sức khỏe của chính mình."</p>
                <p>Tầm nhìn: "Trở thành nhà phân phối và sản xuất thể thao lớn nhất Việt Nam"</p>
            </div>
            
            <div class="footer-section footer-contact">
                <h3>THÔNG TIN LIÊN HỆ</h3>
                <div class="contact-info">
                    <p><strong>Hệ thống cửa hàng:</strong> 1 Super Center, 5 shop Premium và 75 cửa hàng trên toàn quốc</p>
                    <p><a href="#" class="footer-link">Xem tất cả các cửa hàng Adam Sports</a></p>
                    <p><strong>Hotline:</strong> 0788 500 585</p>
                    <p><strong>Email:</strong> info@adamsport.com</p>
                    <p><strong>Hợp tác kinh doanh:</strong> 0947 542 259 (Ms. Thảo)</p>
                    <p><strong>Hotline kỹ thuật:</strong> 0911 057 171</p>
                    <p><strong>Nhượng quyền thương hiệu:</strong> 0334 741 141 (Mr. Hậu)</p>
                    <p><strong>Than phiền dịch vụ:</strong> 0334 741 141 (Mr. Hậu)</p>
                </div>
            </div>
            
            <div class="footer-section footer-policies">
                <h3>CHÍNH SÁCH</h3>
                <ul>
                    <li><a href="#" class="footer-link">Thông tin vận chuyển và giao nhận</a></li>
                    <li><a href="#" class="footer-link">Chính sách đổi trả hoàn tiền</a></li>
                    <li><a href="#" class="footer-link">Chính sách bảo hành</a></li>
                    <li><a href="#" class="footer-link">Chính sách xử lý khiếu nại</a></li>
                    <li><a href="#" class="footer-link">Chính sách vận chuyển</a></li>
                    <li><a href="#" class="footer-link">Điều khoản sử dụng</a></li>
                    <li><a href="#" class="footer-link">Chính sách Bảo Mật Thông Tin</a></li>
                    <li><a href="#" class="footer-link">Chính sách nhượng quyền</a></li>
                </ul>
            </div>
            
            <div class="footer-section footer-guides">
                <h3>HƯỚNG DẪN</h3>
                <ul>
                    <li><a href="#" class="footer-link">Danh sách các tài khoản chính thức của các shopping hệ thống Adam Sports</a></li>
                    <li><a href="#" class="footer-link">Hướng dẫn cách chọn vợt cầu lông cho người mới chơi</a></li>
                    <li><a href="#" class="footer-link">Hướng dẫn thanh toán</a></li>
                    <li><a href="#" class="footer-link">Kiểm tra bảo hành</a></li>
                    <li><a href="#" class="footer-link">Kiểm tra đơn hàng</a></li>
                    <li><a href="#" class="footer-link">Hướng dẫn mua hàng</a></li>
                </ul>
            </div>
        </div>
        
        <div class="footer-company">
            <h3>CÔNG TY TNHH ADAM SPORTS</h3>
            <p><strong>Địa chỉ:</strong> 390/2 Hà Huy Giáp, Phường Thanh Lộc, Quận 12, TP.HCM</p>
            <p><strong>Email:</strong> info@adamsport.com</p>
            <p><strong>Mã số thuế:</strong> 0314496379 do Sở KH và ĐT TP Hồ Chí Minh cấp ngày 05/07/2017</p>
            <p><strong>Giám đốc/Chủ sở hữu website:</strong> Nguyễn Phùng Hà Lan</p>
        </div>
        
        <div class="footer-bottom">
            <div class="footer-bottom-content">
                <p>&copy; 2024 Adam Sport. All rights reserved.</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                    <a href="#"><i class="fab fa-tiktok"></i></a>
                    <a href="#"><i class="fab fa-zalo"></i></a>
                </div>
            </div>
        </div>
    </div>
</footer>
<!-- Modern Chatbot Widget - SIMPLE VERSION -->
<div id="chatbot-widget" class="chatbot-widget">
    <button class="chatbot-toggle" onclick="toggleChatbot()">
        <i class="fas fa-paper-plane"></i>
        <span class="notification-dot" id="chatNotification"></span>
    </button>
    
    <div class="chatbot-container <?php echo $chatbot_open ? 'show' : ''; ?>" id="chatbotContainer">
        <div class="chatbot-header">
            <div class="chatbot-title">
                <i class="fas fa-paper-plane"></i>
                <h4>Adam Sport</h4>
                <span class="online-status">
                    <span class="dot"></span> Online
                </span>
            </div>
            <div class="chatbot-actions">
                <button class="btn-clear" onclick="clearChat()" title="Xóa chat">
                    <i class="fas fa-trash"></i>
                </button>
                <button class="chatbot-close" onclick="closeChatbot()" title="Đóng">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
        
        <div class="chatbot-messages" id="chatbotMessages">
            <!-- Messages will be loaded here -->
            <div class="welcome-message">
                <div class="message bot-message">
                    <div class="message-content">
                        <strong>Adam Sport :</strong> 👋 <strong>Chào bạn!</strong> Tôi là chuyên gia tư vấn Adam Sport
                    </div>
                    <div class="message-time">Bây giờ</div>
                </div>
                <div class="message bot-message">
                    <div class="message-content">
                        Tôi có thể giúp bạn tìm dụng cụ cầu lông phù hợp nhất! 🏸
                    </div>
                </div>
                <div class="quick-suggestions">
                    <button onclick="sendQuickMessage('Tư vấn vợt cho người mới tập')" class="quick-suggestion">
                        🏸 Vợt mới tập
                    </button>
                    <button onclick="sendQuickMessage('Giày cầu lông nào êm chân?')" class="quick-suggestion">
                        👟 Giày êm chân
                    </button>
                    <button onclick="sendQuickMessage('Khuyến mãi gì hiện nay?')" class="quick-suggestion">
                        🎁 Khuyến mãi
                    </button>
                </div>
            </div>
        </div>
        
        <div class="typing-indicator" id="typingIndicator" style="display: none;">
            <div class="typing-dots">
                <span></span>
                <span></span>
                <span></span>
            </div>
            <span class="typing-text">đang nhập...</span>
        </div>
        
        <div class="chatbot-input-area">
            <div class="input-group">
                <input type="text" 
                       id="chatbotInput" 
                       class="chatbot-input" 
                       placeholder="Nhập câu hỏi của bạn..."
                       autocomplete="off">
                <button onclick="sendMessage()" class="btn-send">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div class="input-hints">
                <small>Ví dụ: "Vợt cho người mới tập", "Giày êm chân"</small>
            </div>
        </div>
    </div>
</div>
<script>
// Chatbot Manager đơn giản
let chatbotOpen = <?php echo $chatbot_open ? 'true' : 'false'; ?>;
let isTyping = false;

function toggleChatbot() {
    chatbotOpen = !chatbotOpen;
    const container = document.getElementById('chatbotContainer');
    
    if (chatbotOpen) {
        container.classList.add('show');
        setTimeout(() => {
            document.getElementById('chatbotInput').focus();
            scrollToBottom();
        }, 300);
    } else {
        container.classList.remove('show');
    }
    
    // Save state
    saveChatbotState(chatbotOpen);
}

function closeChatbot() {
    chatbotOpen = false;
    document.getElementById('chatbotContainer').classList.remove('show');
    saveChatbotState(false);
}

async function sendMessage() {
    const input = document.getElementById('chatbotInput');
    const message = input.value.trim();
    
    if (!message || isTyping) return;
    
    // Add user message
    addMessage(message, 'user');
    input.value = '';
    
    // Show typing
    showTyping();
    
    try {
        // Send to server
        const response = await fetch('/AdamShop/api/chat.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                message: message,
                action: 'chat'
            })
        });
        
        const data = await response.json();
        
        // Hide typing
        hideTyping();
        
        if (data.status === 'success') {
            addMessage(data.reply, 'bot');
        } else {
            addMessage('Xin lỗi, có lỗi xảy ra!', 'bot');
        }
        
    } catch (error) {
        console.error('Chat error:', error);
        hideTyping();
        addMessage('Không thể kết nối. Vui lòng thử lại!', 'bot');
    }
}

function addMessage(content, type) {
    const messagesContainer = document.getElementById('chatbotMessages');
    
    // Remove welcome message if first user message
    if (type === 'user' && document.querySelector('.welcome-message')) {
        const welcome = document.querySelector('.welcome-message');
        if (welcome) welcome.style.display = 'none';
    }
    
    const messageDiv = document.createElement('div');
    messageDiv.className = `message ${type}-message`;
    
    const time = new Date().toLocaleTimeString('vi-VN', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    
    messageDiv.innerHTML = `
        <div class="message-content">
            <strong>${type === 'user' ? 'Bạn' : 'AI'}:</strong> ${content}
        </div>
        <div class="message-time">${time}</div>
    `;
    
    messagesContainer.appendChild(messageDiv);
    scrollToBottom();
}

function showTyping() {
    isTyping = true;
    document.getElementById('typingIndicator').style.display = 'flex';
    scrollToBottom();
}

function hideTyping() {
    isTyping = false;
    document.getElementById('typingIndicator').style.display = 'none';
}

function scrollToBottom() {
    const messagesContainer = document.getElementById('chatbotMessages');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
}

function sendQuickMessage(message) {
    document.getElementById('chatbotInput').value = message;
    sendMessage();
}

async function clearChat() {
    if (confirm('Xóa toàn bộ chat?')) {
        try {
            await fetch('/AdamShop/api/chat.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ action: 'clear' })
            });
            
            // Reload chat
            const messagesContainer = document.getElementById('chatbotMessages');
            messagesContainer.innerHTML = `
                <div class="welcome-message">
                    <div class="message bot-message">
                        <div class="message-content">
                            <strong>AI:</strong> 👋 <strong>Chào bạn!</strong> Tôi là chuyên gia tư vấn Adam Sport
                        </div>
                        <div class="message-time">Bây giờ</div>
                    </div>
                    <div class="message bot-message">
                        <div class="message-content">
                            Tôi có thể giúp bạn tìm dụng cụ cầu lông phù hợp nhất! 🏸
                        </div>
                    </div>
                    <div class="quick-suggestions">
                        <button onclick="sendQuickMessage('Tư vấn vợt cho người mới tập')" class="quick-suggestion">
                            🏸 Vợt mới tập
                        </button>
                        <button onclick="sendQuickMessage('Giày cầu lông nào êm chân?')" class="quick-suggestion">
                            👟 Giày êm chân
                        </button>
                        <button onclick="sendQuickMessage('Khuyến mãi gì hiện nay?')" class="quick-suggestion">
                            🎁 Khuyến mãi
                        </button>
                    </div>
                </div>
            `;
            
        } catch (error) {
            console.error('Failed to clear chat:', error);
        }
    }
}

async function saveChatbotState(isOpen) {
    try {
        await fetch('/AdamShop/save_chatbot_state.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `chatbot_open=${isOpen ? '1' : '0'}`
        });
    } catch (error) {
        console.error('Failed to save state:', error);
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    // Auto open if it was open
    if (chatbotOpen) {
        document.getElementById('chatbotContainer').classList.add('show');
    }
    
    // Enter key
    const chatInput = document.getElementById('chatbotInput');
    if (chatInput) {
        chatInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                sendMessage();
            }
        });
    }
});
</script>
</body>
</html>