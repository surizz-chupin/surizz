<?php
// 溯日社区 - 个人中心
require_once 'includes/config.php';
require_once 'includes/database.php';

session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$currentUser = getCurrentUser();

// 获取用户发布的帖子
$userPosts = $db->getPosts(null, 10, 0); // 这里需要修改为获取特定用户的帖子
$stmt = getDB()->prepare("SELECT * FROM posts WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([$_SESSION['user_id']]);
$userPosts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>个人中心 - 溯日社区</title>
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
                    <li class="nav-item"><a href="post_create.php" class="nav-link">发布</a></li>
                </ul>
                <div class="user-menu">
                    <span>欢迎, <?= escape($currentUser['username']) ?></span>
                    <a href="profile.php" class="btn btn-outline">个人中心</a>
                    <button class="btn btn-outline" onclick="logout()">退出</button>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="user-card">
                <h2 class="card-title">个人中心</h2>
                <div style="display: flex; align-items: center; margin-bottom: 1.5rem;">
                    <img src="images/default_avatar.png" alt="头像" class="user-avatar" id="userAvatar">
                    <div>
                        <h3 class="user-name"><?= escape($currentUser['username']) ?></h3>
                        <p class="user-info">注册时间: <?= date('Y-m-d', strtotime($currentUser['created_at'])) ?></p>
                        <p class="user-info">角色: <?= $currentUser['role'] === 'admin' ? '管理员' : '普通用户' ?></p>
                        <p class="user-info">积分: <?= $currentUser['points'] ?? 0 ?></p>
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="form-label">个人简介</label>
                    <textarea class="form-input" rows="3" placeholder="编辑个人简介..." id="bio"><?= escape($currentUser['bio'] ?? '') ?></textarea>
                    <button class="btn btn-primary" style="margin-top: 0.5rem;" onclick="updateBio()">更新简介</button>
                </div>
                
                <!-- 关注功能 -->
                <div class="form-group">
                    <h3>关注信息</h3>
                    <p>关注数: 
                        <?php
                        $followCountStmt = getDB()->prepare("SELECT COUNT(*) FROM follows WHERE follower_id = ?");
                        $followCountStmt->execute([$currentUser['id']]);
                        echo $followCountStmt->fetchColumn();
                        ?>
                    </p>
                    <p>粉丝数: 
                        <?php
                        $followerCountStmt = getDB()->prepare("SELECT COUNT(*) FROM follows WHERE followed_id = ?");
                        $followerCountStmt->execute([$currentUser['id']]);
                        echo $followerCountStmt->fetchColumn();
                        ?>
                    </p>
                </div>
            </div>
            
            <div class="card">
                <h3 class="card-title">我的帖子</h3>
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
                    <p>您还没有发布任何帖子</p>
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
        
        // 更新个人简介
        async function updateBio() {
            const bio = document.getElementById('bio').value;
            
            try {
                const response = await fetch('api/profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        action: 'update_bio',
                        bio: bio
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('简介更新成功');
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