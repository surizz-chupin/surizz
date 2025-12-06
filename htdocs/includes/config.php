<?php
// 溯日社区配置文件
// 数据库连接配置

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'suri_forum');

// 网站配置
define('SITE_NAME', '溯日社区');
define('SITE_URL', 'http://localhost');
define('SITE_DESC', '溯本求源，共筑思想栖息地');

// 上传配置
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', 'jpg,jpeg,png,gif,doc,docx,pdf');

// 分页配置
define('POSTS_PER_PAGE', 20);
define('COMMENTS_PER_PAGE', 20);

// 安全配置
define('HASH_COST', 10);

// 创建数据库连接
function getDB() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", 
                      DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        die("数据库连接失败: " . $e->getMessage());
    }
}

// 安全函数：防止XSS
function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// 验证用户登录状态
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 获取当前登录用户信息
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    $db = getDB();
    $stmt = $db->prepare("SELECT id, username, email, avatar, role FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// 检查用户权限
function isAdmin() {
    $user = getCurrentUser();
    return $user && $user['role'] === 'admin';
}