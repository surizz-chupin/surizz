<?php
// 溯日社区 - 首页
require_once 'includes/config.php';
require_once 'includes/database.php';

session_start();
$db = new Database();

// 获取热门帖子
$hotPosts = $db->getHotPosts(6);

// 获取最新帖子
$latestPosts = $db->getPosts(null, 6);

// 获取精华帖子
$essencePosts = $db->getEssencePosts(6);

// 获取所有板块
$categories = $db->getAllCategories();

// 当前用户信息
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>溯日社区 - 溯本求源，共筑思想栖息地</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="icon" href="images/favicon.ico" type="image/x-icon">
</head>
<body>
    <!-- 头部导航 -->
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
                <div class="user-menu" id="userMenu">
                    <?php if ($currentUser): ?>
                        <span>欢迎, <?= escape($currentUser['username']) ?></span>
                        <a href="profile.php" class="btn btn-outline">个人中心</a>
                        <button class="btn btn-outline" onclick="logout()">退出</button>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline" id="loginBtn">登录</a>
                        <a href="register.php" class="btn btn-primary" id="registerBtn">注册</a>
                    <?php endif; ?>
                </div>
            </nav>
        </div>
    </header>

    <!-- 主要内容 -->
    <main class="main-content">
        <div class="container">
            <!-- 搜索框 -->
            <div class="search-bar">
                <form id="searchForm" action="search.php" method="GET">
                    <input type="text" id="searchInput" name="q" placeholder="搜索帖子..." class="form-input">
                    <button type="submit">搜索</button>
                </form>
            </div>

            <!-- 热门帖子 -->
            <section class="category-card">
                <h2 class="category-title">热门帖子</h2>
                <div class="category-posts" id="hotPosts">
                    <?php if ($hotPosts): ?>
                        <?php foreach ($hotPosts as $post): ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <h3 class="post-title"><a href="post.php?id=<?= $post['id'] ?>"><?= escape($post['title']) ?></a></h3>
                                </div>
                                <div class="post-meta">
                                    <span>作者: <?= escape($post['username']) ?></span>
                                    <span>浏览: <?= $post['view_count'] ?></span>
                                </div>
                                <div class="post-stats">
                                    <span>评论: <?= $post['comment_count'] ?></span>
                                    <span>点赞: <?= $post['like_count'] ?></span>
                                    <a href="post.php?id=<?= $post['id'] ?>" class="btn btn-outline">查看详情</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>暂无热门帖子</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- 最新帖子 -->
            <section class="category-card">
                <h2 class="category-title">最新帖子</h2>
                <div class="category-posts" id="latestPosts">
                    <?php if ($latestPosts): ?>
                        <?php foreach ($latestPosts as $post): ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <h3 class="post-title"><a href="post.php?id=<?= $post['id'] ?>"><?= escape($post['title']) ?></a></h3>
                                </div>
                                <div class="post-meta">
                                    <span>作者: <?= escape($post['username']) ?></span>
                                    <span><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></span>
                                </div>
                                <div class="post-stats">
                                    <span>评论: <?= $post['comment_count'] ?></span>
                                    <span>点赞: <?= $post['like_count'] ?></span>
                                    <a href="post.php?id=<?= $post['id'] ?>" class="btn btn-outline">查看详情</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>暂无最新帖子</p>
                    <?php endif; ?>
                </div>
            </section>

            <!-- 精华帖子 -->
            <section class="category-card">
                <h2 class="category-title">精华帖子</h2>
                <div class="category-posts" id="essencePosts">
                    <?php if ($essencePosts): ?>
                        <?php foreach ($essencePosts as $post): ?>
                            <div class="post-card">
                                <div class="post-header">
                                    <h3 class="post-title"><a href="post.php?id=<?= $post['id'] ?>"><?= escape($post['title']) ?></a></h3>
                                </div>
                                <div class="post-meta">
                                    <span>作者: <?= escape($post['username']) ?></span>
                                    <span><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></span>
                                </div>
                                <div class="post-stats">
                                    <span>点赞: <?= $post['like_count'] ?></span>
                                    <span>收藏: --</span>
                                    <a href="post.php?id=<?= $post['id'] ?>" class="btn btn-outline">查看详情</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>暂无精华帖子</p>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>溯日社区</h3>
                    <p>溯本求源，共筑思想栖息地</p>
                </div>
                <div class="footer-section">
                    <h3>快速链接</h3>
                    <ul>
                        <li><a href="about.php">关于我们</a></li>
                        <li><a href="privacy.php">隐私政策</a></li>
                        <li><a href="terms.php">用户协议</a></li>
                        <li><a href="help.php">帮助中心</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>联系方式</h3>
                    <ul>
                        <li>邮箱: contact@suri.com</li>
                        <li>QQ群: 123456789</li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2025 溯日社区. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // 当前用户信息
        let currentUser = <?= $currentUser ? json_encode($currentUser) : 'null' ?>;
        
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
    <script src="js/main.js"></script>
</body>
</html>