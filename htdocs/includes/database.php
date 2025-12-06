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
}