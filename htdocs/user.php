<?php
// 溯日社区 - 用户资料页
require_once 'includes/config.php';
require_once 'includes/database.php';

session_start();

$db = new Database();
$currentUser = getCurrentUser();

// 获取用户ID
$userId = (int)($_GET['id'] ?? 0);
if ($userId <= 0) {
    header('Location: index.php');
    exit;
}

// 获取用户信息
$user = $db->getUserById($userId);
if (!$user) {
    header('Location: index.php');
    exit;
}

// 获取用户发布的帖子
$stmt = getDB()->prepare("SELECT p.*, c.name as category_name FROM posts p LEFT JOIN categories c ON p.category_id = c.id WHERE p.user_id = ? AND p.status = 'published' ORDER BY p.created_at DESC LIMIT 10");
$stmt->execute([$userId]);
$userPosts = $stmt->fetchAll();

$isFollowing = false;
if ($currentUser) {
    $followStmt = getDB()->prepare("SELECT id FROM follows WHERE follower_id = ? AND followed_id = ?");
    $followStmt->execute([$currentUser['id'], $userId]);
    $isFollowing = $followStmt->fetch() !== false;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($user['username']) ?>的个人资料 - 溯日社区</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="navbar-brand">溯日社区</a>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="index.php" class="nav-link">首页</a></li>
                    <li class="nav-item"><a href="categories.php" class="nav-link">板块</a></li>
                    <li class="nav-item"><a href="posts.php" class="nav-link">帖子</a></li>
                    <?php if ($currentUser): ?>
                        <li class="nav-item"><a href="post_create.php" class="nav-link">发布</a></li>
                        <li class="nav-item"><a href="profile.php" class="nav-link">个人中心</a></li>
                    <?php endif; ?>
                </ul>
                <div class="user-menu">
                    <?php if ($currentUser): ?>
                        <span>欢迎, <?= escape($currentUser['username']) ?></span>
                        <a href="profile.php" class="btn btn-outline">个人中心</a>
                        <button class="btn btn-outline" onclick="logout()">退出</button>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline">登录</a>
                        <a href="register.php" class="btn btn-primary">注册</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="user-card">
                <h2 class="card-title"><?= escape($user['username']) ?>的个人资料</h2>
                <div style="display: flex; align-items: center; margin-bottom: 1.5rem;">
                    <img src="images/default_avatar.png" alt="头像" class="user-avatar">
                    <div>
                        <h3 class="user-name"><?= escape($user['username']) ?></h3>
                        <p class="user-info">注册时间: <?= date('Y-m-d', strtotime($user['created_at'])) ?></p>
                        <p class="user-info">角色: <?= $user['role'] === 'admin' ? '管理员' : '普通用户' ?></p>
                        <p class="user-info">积分: <?= $user['points'] ?></p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">个人简介</label>
                    <p><?= escape($user['bio'] ?? '暂无简介') ?></p>
                </div>
                
                <!-- 关注功能 -->
                <div class="form-group">
                    <?php if ($currentUser && $currentUser['id'] != $user['id']): ?>
                        <button class="btn <?= $isFollowing ? 'btn-danger' : 'btn-primary' ?>" onclick="toggleFollow(<?= $user['id'] ?>)">
                            <?= $isFollowing ? '取消关注' : '关注' ?>
                        </button>
                    <?php endif; ?>
                    <p>关注数: 
                        <?php
                        $followCountStmt = getDB()->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
                        $followCountStmt->execute([$user['id']]);
                        echo $followCountStmt->fetchColumn();
                        ?>
                    </p>
                    <p>粉丝数: 
                        <?php
                        $followerCountStmt = getDB()->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = ?");
                        $followerCountStmt->execute([$user['id']]);
                        echo $followerCountStmt->fetchColumn();
                        ?>
                    </p>
                </div>
            </div>
            
            <div class="card">
                <h3 class="card-title"><?= escape($user['username']) ?>发布的帖子</h3>
                <?php if ($userPosts): ?>
                    <div class="category-posts">
                        <?php foreach ($userPosts as $post): ?>
                        <div class="post-card">
                            <div class="post-header">
                                <h3 class="post-title"><a href="post.php?id=<?= $post['id'] ?>"><?= escape($post['title']) ?></a></h3>
                            </div>
                            <div class="post-meta">
                                <span><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></span>
                                <span>板块: <?= escape($post['category_name'] ?? '未知') ?></span>
                            </div>
                            <div class="post-stats">
                                <span>浏览: <?= $post['view_count'] ?></span>
                                <span>评论: <?= $post['comment_count'] ?></span>
                                <a href="post.php?id=<?= $post['id'] ?>" class="btn btn-outline">查看详情</a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p><?= escape($user['username']) ?>还没有发布任何帖子</p>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; 2025 溯日社区. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // 退出登录功能
        async function logout() {
            try {
                const response = await fetch('api/auth.php?action=logout', {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // 清除本地存储
                    localStorage.removeItem('currentUser');
                    // 重定向到首页
                    window.location.href = 'index.php';
                }
            } catch (error) {
                console.error('退出登录失败:', error);
            }
        }
        
        // 关注/取消关注功能
        async function toggleFollow(userId) {
            if (!currentUser) {
                alert('请先登录');
                return;
            }
            
            try {
                const isFollowing = document.querySelector('.btn').textContent.includes('取消关注');
                const response = await fetch('api/follow.php', {
                    method: isFollowing ? 'DELETE' : 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        user_id: userId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // 刷新页面以更新关注状态
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('网络错误，请重试');
            }
        }
    </script>
</body>
</html>