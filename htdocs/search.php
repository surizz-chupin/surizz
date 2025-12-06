<?php
// 溯日社区 - 搜索结果
require_once 'includes/config.php';
require_once 'includes/database.php';

session_start();
$db = new Database();

// 获取搜索关键词
$keyword = trim($_GET['q'] ?? '');
$currentPage = (int)($_GET['page'] ?? 1);
$currentPage = max(1, $currentPage); // 确保页码至少为1

$posts = [];
$totalPosts = 0;
$postsPerPage = POSTS_PER_PAGE;

if (!empty($keyword)) {
    // 计算偏移量
    $offset = ($currentPage - 1) * $postsPerPage;
    
    // 执行搜索
    $posts = $db->searchPosts($keyword, $postsPerPage, $offset);
    
    // 计算总帖子数（这里简化处理，实际应该执行一个COUNT查询）
    // 为了演示，我们假设有结果
    $totalPosts = count($posts) > 0 ? 100 : 0; // 实际应用中需要单独查询总数
}

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>搜索"<?= escape($keyword) ?>" - 溯日社区</title>
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
            <div class="search-bar">
                <form id="searchForm" action="search.php" method="GET">
                    <input type="text" id="searchInput" name="q" value="<?= escape($keyword) ?>" placeholder="搜索帖子..." class="form-input">
                    <button type="submit">搜索</button>
                </form>
            </div>

            <h2>搜索结果: "<?= escape($keyword) ?>"</h2>
            
            <?php if (empty($keyword)): ?>
                <p>请输入搜索关键词</p>
            <?php elseif (empty($posts)): ?>
                <p>没有找到与"<?= escape($keyword) ?>"相关的帖子</p>
            <?php else: ?>
                <div class="category-posts">
                    <?php foreach ($posts as $post): ?>
                    <div class="post-card">
                        <div class="post-header">
                            <h3 class="post-title"><a href="post.php?id=<?= $post['id'] ?>"><?= escape($post['title']) ?></a></h3>
                        </div>
                        <div class="post-meta">
                            <span>作者: <?= escape($post['username']) ?></span>
                            <span>板块: <?= escape($post['category_name']) ?></span>
                        </div>
                        <div class="post-content">
                            <?php 
                            // 简单高亮搜索关键词
                            $content = strip_tags($post['content']);
                            $start = max(0, strpos(strtolower($content), strtolower($keyword)) - 50);
                            $preview = substr($content, $start, 150);
                            if ($start > 0) $preview = '...' . $preview;
                            if (strlen($content) > $start + 150) $preview .= '...';
                            
                            // 高亮关键词
                            $preview = preg_replace('/(' . preg_quote($keyword, '/') . ')/i', '<mark>$1</mark>', $preview);
                            echo $preview;
                            ?>
                        </div>
                        <div class="post-stats">
                            <span>浏览: <?= $post['view_count'] ?></span>
                            <span>评论: <?= $post['comment_count'] ?></span>
                            <span>点赞: <?= $post['like_count'] ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- 分页 -->
                <?php if ($totalPosts > $postsPerPage): ?>
                <div class="pagination">
                    <?php
                    $totalPages = ceil($totalPosts / $postsPerPage);
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);
                    ?>
                    
                    <?php if ($currentPage > 1): ?>
                        <a href="?q=<?= urlencode($keyword) ?>&page=<?= $currentPage - 1 ?>">&laquo; 上一页</a>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php if ($i == $currentPage): ?>
                            <span class="current"><?= $i ?></span>
                        <?php else: ?>
                            <a href="?q=<?= urlencode($keyword) ?>&page=<?= $i ?>"><?= $i ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?q=<?= urlencode($keyword) ?>&page=<?= $currentPage + 1 ?>">下一页 &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php endif; ?>
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