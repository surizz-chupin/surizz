<?php
// 数据库操作类

require_once 'config.php';

class Database {
    private $pdo;
    
    public function __construct() {
        $this->pdo = getDB();
    }
    
    // 用户相关操作
    public function registerUser($username, $email, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
        return $stmt->execute([$username, $email, $hashedPassword]);
    }
    
    public function authenticateUser($username, $password) {
        $stmt = $this->pdo->prepare("SELECT id, username, password, role FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
    
    public function getUserById($id) {
        $stmt = $this->pdo->prepare("SELECT id, username, email, avatar, bio, role FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // 板块相关操作
    public function getAllCategories() {
        $stmt = $this->pdo->query("SELECT * FROM categories ORDER BY sort_order ASC, id ASC");
        return $stmt->fetchAll();
    }
    
    public function getCategoryById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    // 帖子相关操作
    public function getPosts($categoryId = null, $limit = POSTS_PER_PAGE, $offset = 0, $orderBy = 'created_at DESC') {
        $sql = "SELECT p.*, u.username, u.avatar, c.name as category_name 
                FROM posts p 
                JOIN users u ON p.user_id = u.id 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 'published'";
        
        $params = [];
        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        
        $sql .= " ORDER BY " . $orderBy . " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function getPostById($id) {
        $stmt = $this->pdo->prepare("SELECT p.*, u.username, u.avatar, c.name as category_name 
                                     FROM posts p 
                                     JOIN users u ON p.user_id = u.id 
                                     JOIN categories c ON p.category_id = c.id 
                                     WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public function createPost($title, $content, $userId, $categoryId) {
        $stmt = $this->pdo->prepare("INSERT INTO posts (title, content, user_id, category_id) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$title, $content, $userId, $categoryId]);
    }
    
    public function updatePost($id, $title, $content, $categoryId) {
        $stmt = $this->pdo->prepare("UPDATE posts SET title = ?, content = ?, category_id = ? WHERE id = ?");
        return $stmt->execute([$title, $content, $categoryId, $id]);
    }
    
    public function deletePost($id) {
        $stmt = $this->pdo->prepare("UPDATE posts SET status = 'deleted' WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    public function incrementPostView($id) {
        $stmt = $this->pdo->prepare("UPDATE posts SET view_count = view_count + 1 WHERE id = ?");
        return $stmt->execute([$id]);
    }
    
    // 评论相关操作
    public function getCommentsByPostId($postId, $limit = COMMENTS_PER_PAGE, $offset = 0) {
        $sql = "SELECT c.*, u.username, u.avatar 
                FROM comments c 
                JOIN users u ON c.user_id = u.id 
                WHERE c.post_id = ? AND c.status = 'published' 
                ORDER BY c.created_at ASC 
                LIMIT ? OFFSET ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$postId, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    public function createComment($content, $userId, $postId, $parentId = null) {
        $sql = "INSERT INTO comments (content, user_id, post_id, parent_id) VALUES (?, ?, ?, ?)";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$content, $userId, $postId, $parentId]);
    }
    
    // 点赞相关操作
    public function toggleLike($userId, $targetType, $targetId) {
        $stmt = $this->pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND target_type = ? AND target_id = ?");
        $stmt->execute([$userId, $targetType, $targetId]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            // 取消点赞
            $stmt = $this->pdo->prepare("DELETE FROM likes WHERE user_id = ? AND target_type = ? AND target_id = ?");
            $stmt->execute([$userId, $targetType, $targetId]);
            
            if ($targetType === 'post') {
                $stmt = $this->pdo->prepare("UPDATE posts SET like_count = like_count - 1 WHERE id = ?");
            } else {
                $stmt = $this->pdo->prepare("UPDATE comments SET like_count = like_count - 1 WHERE id = ?");
            }
            $stmt->execute([$targetId]);
            
            return false;
        } else {
            // 添加点赞
            $stmt = $this->pdo->prepare("INSERT INTO likes (user_id, target_type, target_id) VALUES (?, ?, ?)");
            $stmt->execute([$userId, $targetType, $targetId]);
            
            if ($targetType === 'post') {
                $stmt = $this->pdo->prepare("UPDATE posts SET like_count = like_count + 1 WHERE id = ?");
            } else {
                $stmt = $this->pdo->prepare("UPDATE comments SET like_count = like_count + 1 WHERE id = ?");
            }
            $stmt->execute([$targetId]);
            
            return true;
        }
    }
    
    // 搜索功能
    public function searchPosts($keyword, $limit = POSTS_PER_PAGE, $offset = 0) {
        $sql = "SELECT p.*, u.username, u.avatar, c.name as category_name 
                FROM posts p 
                JOIN users u ON p.user_id = u.id 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 'published' 
                AND (p.title LIKE ? OR p.content LIKE ?)
                ORDER BY p.created_at DESC 
                LIMIT ? OFFSET ?";
        $searchTerm = '%' . $keyword . '%';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $limit, $offset]);
        return $stmt->fetchAll();
    }
    
    // 获取热门帖子
    public function getHotPosts($limit = 10) {
        $sql = "SELECT p.*, u.username, u.avatar, c.name as category_name 
                FROM posts p 
                JOIN users u ON p.user_id = u.id 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 'published' 
                ORDER BY p.view_count DESC, p.like_count DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    // 获取精华帖子
    public function getEssencePosts($limit = 10) {
        $sql = "SELECT p.*, u.username, u.avatar, c.name as category_name 
                FROM posts p 
                JOIN users u ON p.user_id = u.id 
                JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 'published' AND p.is_essence = 1
                ORDER BY p.created_at DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    // 管理员功能：获取统计数据
    public function getTotalUsers() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM users");
        return $stmt->fetchColumn();
    }
    
    public function getTotalPosts() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM posts WHERE status = 'published'");
        return $stmt->fetchColumn();
    }
    
    public function getTotalComments() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM comments WHERE status = 'published'");
        return $stmt->fetchColumn();
    }
    
    public function getRecentUsers($limit = 10) {
        $sql = "SELECT id, username, email, status, created_at FROM users ORDER BY created_at DESC LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    public function getRecentPosts($limit = 10) {
        $sql = "SELECT p.*, u.username, c.name as category_name 
                FROM posts p 
                JOIN users u ON p.user_id = u.id 
                JOIN categories c ON p.category_id = c.id 
                ORDER BY p.created_at DESC 
                LIMIT ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    // 管理员功能：管理用户
    public function banUser($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        return $stmt->execute(['banned', $userId]);
    }
    
    public function unbanUser($userId) {
        $stmt = $this->pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
        return $stmt->execute(['active', $userId]);
    }
    
    // 管理员功能：管理帖子
    public function pinPost($postId) {
        $stmt = $this->pdo->prepare("UPDATE posts SET is_pinned = 1 WHERE id = ?");
        return $stmt->execute([$postId]);
    }
    
    public function unpinPost($postId) {
        $stmt = $this->pdo->prepare("UPDATE posts SET is_pinned = 0 WHERE id = ?");
        return $stmt->execute([$postId]);
    }
    
    public function essencePost($postId) {
        $stmt = $this->pdo->prepare("UPDATE posts SET is_essence = 1 WHERE id = ?");
        return $stmt->execute([$postId]);
    }
    
    public function unessencePost($postId) {
        $stmt = $this->pdo->prepare("UPDATE posts SET is_essence = 0 WHERE id = ?");
        return $stmt->execute([$postId]);
    }
    
    // 积分系统
    public function getUserPoints($userId) {
        $stmt = $this->pdo->prepare("SELECT points FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result ? $result['points'] : 0;
    }
    
    public function addPoints($userId, $points, $reason = '') {
        $stmt = $this->pdo->prepare("UPDATE users SET points = points + ? WHERE id = ?");
        $success = $stmt->execute([$points, $userId]);
        
        if ($success && $reason) {
            // 记录积分变动日志（如果需要的话，可以创建积分日志表）
            $logStmt = $this->pdo->prepare("INSERT INTO points_log (user_id, points, reason, created_at) VALUES (?, ?, ?, NOW())");
            $logStmt->execute([$userId, $points, $reason]);
        }
        
        return $success;
    }
    
    // 举报功能
    public function reportContent($userId, $targetType, $targetId, $reason) {
        $stmt = $this->pdo->prepare("INSERT INTO reports (user_id, target_type, target_id, reason, status) VALUES (?, ?, ?, ?, 'pending')");
        return $stmt->execute([$userId, $targetType, $targetId, $reason]);
    }
    
    public function getReports($status = null, $limit = 20, $offset = 0) {
        $sql = "SELECT r.*, u.username as reporter_name, 
                       CASE 
                         WHEN r.target_type = 'post' THEN (SELECT title FROM posts WHERE id = r.target_id)
                         WHEN r.target_type = 'comment' THEN (SELECT content FROM comments WHERE id = r.target_id)
                         WHEN r.target_type = 'user' THEN (SELECT username FROM users WHERE id = r.target_id)
                       END as target_content
                FROM reports r
                JOIN users u ON r.user_id = u.id";
        
        $params = [];
        if ($status) {
            $sql .= " WHERE r.status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY r.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
    
    public function updateReportStatus($reportId, $status) {
        $stmt = $this->pdo->prepare("UPDATE reports SET status = ? WHERE id = ?");
        return $stmt->execute([$status, $reportId]);
    }
    
    // 检查是否已点赞
    public function isLiked($userId, $targetType, $targetId) {
        $stmt = $this->pdo->prepare("SELECT id FROM likes WHERE user_id = ? AND target_type = ? AND target_id = ?");
        $stmt->execute([$userId, $targetType, $targetId]);
        return $stmt->fetch() !== false;
    }
    
    // 获取点赞数
    public function getLikeCount($targetType, $targetId) {
        if ($targetType === 'post') {
            $stmt = $this->pdo->prepare("SELECT like_count FROM posts WHERE id = ?");
            $stmt->execute([$targetId]);
            $result = $stmt->fetch();
            return $result ? $result['like_count'] : 0;
        } else {
            $stmt = $this->pdo->prepare("SELECT like_count FROM comments WHERE id = ?");
            $stmt->execute([$targetId]);
            $result = $stmt->fetch();
            return $result ? $result['like_count'] : 0;
        }
    }
}