<?php
// 溯日社区 - 发布帖子
require_once 'includes/config.php';
require_once 'includes/database.php';

session_start();

// 检查用户是否登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$categories = $db->getAllCategories();
$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>发布帖子 - 溯日社区</title>
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
            <div class="card">
                <h2 class="card-title">发布新帖子</h2>
                <form id="postForm">
                    <div class="form-group">
                        <label for="title" class="form-label">标题</label>
                        <input type="text" id="title" name="title" class="form-input" required maxlength="255">
                    </div>
                    <div class="form-group">
                        <label for="category_id" class="form-label">板块</label>
                        <select id="category_id" name="category_id" class="form-input" required>
                            <option value="">请选择板块</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>"><?= escape($category['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="content" class="form-label">内容</label>
                        <textarea id="content" name="content" class="form-input" rows="10" required placeholder="请输入帖子内容..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">发布帖子</button>
                    <a href="index.php" class="btn btn-outline">取消</a>
                </form>
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

        // 提交帖子
        document.getElementById('postForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const title = formData.get('title');
            const categoryId = formData.get('category_id');
            const content = formData.get('content');
            
            if (!title.trim()) {
                alert('标题不能为空');
                return;
            }
            
            if (!categoryId) {
                alert('请选择板块');
                return;
            }
            
            if (!content.trim()) {
                alert('内容不能为空');
                return;
            }
            
            try {
                const response = await fetch('api/posts.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        title: title,
                        content: content,
                        category_id: parseInt(categoryId)
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('帖子发布成功');
                    // 跳转到首页
                    window.location.href = 'index.php';
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