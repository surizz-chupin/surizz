<?php
// 溯日社区 - 登录页面
require_once 'includes/config.php';

session_start();

// 如果已登录，跳转到首页
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>登录 - 溯日社区</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="index.php" class="navbar-brand">溯日社区</a>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="container">
            <div class="card" style="max-width: 400px; margin: 2rem auto;">
                <h2 class="card-title">用户登录</h2>
                <form id="loginForm">
                    <div class="form-group">
                        <label for="username" class="form-label">用户名或邮箱</label>
                        <input type="text" id="username" name="username" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">登录</button>
                </form>
                <div style="margin-top: 1rem; text-align: center;">
                    <p>还没有账号？ <a href="register.php" class="nav-link">立即注册</a></p>
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
        document.getElementById('loginForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const username = formData.get('username');
            const password = formData.get('password');
            
            try {
                const response = await fetch('api/auth.php?action=login', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ username, password })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // 存储用户信息到本地
                    localStorage.setItem('currentUser', JSON.stringify(result.user));
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