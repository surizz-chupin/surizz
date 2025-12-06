<?php
// 溯日社区 - 帖子详情页
require_once 'includes/config.php';
require_once 'includes/database.php';

session_start();
$db = new Database();

// 获取帖子ID
$postId = (int)($_GET['id'] ?? 0);
if ($postId <= 0) {
    header('Location: index.php');
    exit;
}

// 获取帖子详情
$post = $db->getPostById($postId);
if (!$post) {
    header('Location: index.php');
    exit;
}

// 增加浏览量
$db->incrementPostView($postId);

// 获取评论
$page = (int)($_GET['page'] ?? 1);
$limit = COMMENTS_PER_PAGE;
$offset = ($page - 1) * $limit;
$comments = $db->getCommentsByPostId($postId, $limit, $offset);

// 当前用户信息
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= escape($post['title']) ?> - 溯日社区</title>
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
            <div class="post-detail">
                <div class="post-detail-header">
                    <h1 class="post-detail-title"><?= escape($post['title']) ?></h1>
                    <div class="post-detail-meta">
                        <span>作者: <?= escape($post['username']) ?></span>
                        <span>发布时间: <?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></span>
                        <span>板块: <?= escape($post['category_name']) ?></span>
                        <span>浏览: <?= $post['view_count'] ?></span>
                    </div>
                </div>
                
                <div class="post-detail-content">
                    <?= nl2br(escape($post['content'])) ?>
                </div>
                
                <div class="post-actions">
                    <button class="btn btn-outline" onclick="toggleLike('post', <?= $post['id'] ?>)">
                        <span id="likeCount"><?= $post['like_count'] ?></span> 点赞
                    </button>
                    <button class="btn btn-outline">收藏</button>
                    <button class="btn btn-outline">分享</button>
                    <?php if ($currentUser && ($currentUser['id'] == $post['user_id'] || $currentUser['role'] === 'admin')): ?>
                        <a href="post_edit.php?id=<?= $post['id'] ?>" class="btn btn-primary">编辑</a>
                        <a href="post_delete.php?id=<?= $post['id'] ?>" class="btn btn-danger" onclick="return confirm('确定要删除这个帖子吗？')">删除</a>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 评论区 -->
            <div class="comments-section">
                <h3>评论 (<?= $post['comment_count'] ?>)</h3>
                
                <?php if ($currentUser): ?>
                <div class="comment-form">
                    <form id="commentForm">
                        <textarea id="commentContent" placeholder="写下你的评论..." required></textarea>
                        <button type="submit" class="btn btn-primary">发布评论</button>
                    </form>
                </div>
                <?php else: ?>
                <p><a href="login.php">登录</a>后可发表评论</p>
                <?php endif; ?>
                
                <div id="commentsList">
                    <?php foreach ($comments as $comment): ?>
                    <div class="comment">
                        <div class="comment-header">
                            <span class="comment-author"><?= escape($comment['username']) ?></span>
                            <span class="comment-time"><?= date('Y-m-d H:i', strtotime($comment['created_at'])) ?></span>
                        </div>
                        <div class="comment-content">
                            <?= nl2br(escape($comment['content'])) ?>
                        </div>
                        <div class="comment-actions">
                            <button class="btn btn-outline" onclick="toggleLike('comment', <?= $comment['id'] ?>)">
                                <span id="likeCount_<?= $comment['id'] ?>"><?= $comment['like_count'] ?></span> 点赞
                            </button>
                            <?php if ($currentUser && $currentUser['id'] == $comment['user_id']): ?>
                                <button class="btn btn-outline">删除</button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- 分页 -->
                <?php if (count($comments) >= $limit): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?= $postId ?>&page=<?= $page - 1 ?>">上一页</a>
                    <?php endif; ?>
                    
                    <span class="current">第 <?= $page ?> 页</span>
                    
                    <?php if (count($comments) == $limit): ?>
                        <a href="?id=<?= $postId ?>&page=<?= $page + 1 ?>">下一页</a>
                    <?php endif; ?>
                </div>
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
        
        // 点赞功能
        async function toggleLike(targetType, targetId) {
            if (!currentUser) {
                alert('请先登录');
                return;
            }
            
            try {
                const response = await fetch('api/like.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        target_type: targetType,
                        target_id: targetId
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // 更新点赞数显示
                    if (targetType === 'post' && targetId == <?= $post['id'] ?>) {
                        const likeCount = document.getElementById('likeCount');
                        likeCount.textContent = result.like_count;
                    } else if (targetType === 'comment') {
                        const likeCount = document.getElementById('likeCount_' + targetId);
                        likeCount.textContent = result.like_count;
                    }
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('网络错误，请重试');
            }
        }
        
        // 提交评论
        document.getElementById('commentForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const content = document.getElementById('commentContent').value.trim();
            if (!content) {
                alert('评论内容不能为空');
                return;
            }
            
            if (!currentUser) {
                alert('请先登录');
                return;
            }
            
            try {
                const response = await fetch('api/comments.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        post_id: <?= $post['id'] ?>,
                        content: content
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('评论发布成功');
                    // 重新加载页面以显示新评论
                    location.reload();
                } else {
                    alert(result.message);
                }
            } catch (error) {
                alert('网络错误，请重试');
            }
        });
    </script>
</body>
</html>