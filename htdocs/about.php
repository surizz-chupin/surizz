<?php
// 溯日社区 - 关于我们
require_once 'includes/config.php';

session_start();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>关于我们 - 溯日社区</title>
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
            <div class="card">
                <h1 class="card-title">关于我们</h1>
                <div class="post-detail-content">
                    <h2>溯本求源，共筑思想栖息地</h2>
                    <p>溯日社区成立于2025年，是一个专注于知识分享、经验交流和思想碰撞的垂直领域社区平台。</p>
                    
                    <h3>我们的使命</h3>
                    <p>我们致力于为用户提供一个纯净、高质量的交流环境，让每一个想法都能在这里找到共鸣，每一次分享都能创造价值。</p>
                    
                    <h3>我们的愿景</h3>
                    <p>成为最受信赖的知识分享社区，连接志同道合的人们，共同探索知识的边界。</p>
                    
                    <h3>联系我们</h3>
                    <p>如果您有任何问题或建议，欢迎通过以下方式联系我们：</p>
                    <ul>
                        <li>邮箱: contact@suri.com</li>
                        <li>QQ群: 123456789</li>
                    </ul>
                </div>
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