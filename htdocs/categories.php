<?php
// 溯日社区 - 板块列表
require_once 'includes/config.php';
require_once 'includes/database.php';

session_start();
$db = new Database();
$categories = $db->getAllCategories();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>板块 - 溯日社区</title>
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
            <h1>社区板块</h1>
            <div class="category-posts">
                <?php foreach ($categories as $category): ?>
                <div class="post-card">
                    <div class="post-header">
                        <h3 class="post-title"><a href="posts.php?category=<?= $category['id'] ?>"><?= escape($category['name']) ?></a></h3>
                    </div>
                    <div class="post-content">
                        <p><?= escape($category['description'] ?? '暂无描述') ?></p>
                    </div>
                    <div class="post-stats">
                        <?php 
                        // 获取该板块下的帖子数量
                        $stmt = getDB()->prepare("SELECT COUNT(*) as count FROM posts WHERE category_id = ? AND status = 'published'");
                        $stmt->execute([$category['id']]);
                        $postCount = $stmt->fetch()['count'];
                        ?>
                        <span>帖子: <?= $postCount ?></span>
                        <a href="posts.php?category=<?= $category['id'] ?>" class="btn btn-outline">进入板块</a>
                    </div>
                </div>
                <?php endforeach; ?>
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
    </script>
</body>
</html>