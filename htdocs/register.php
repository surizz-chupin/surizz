<?php
// 溯日社区 - 注册页面
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
    <title>注册 - 溯日社区</title>
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
                <h2 class="card-title">用户注册</h2>
                <form id="registerForm">
                    <div class="form-group">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" id="username" name="username" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="email" class="form-label">邮箱</label>
                        <input type="email" id="email" name="email" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="password" class="form-label">密码</label>
                        <input type="password" id="password" name="password" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">确认密码</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width: 100%;">注册</button>
                </form>
                <div style="margin-top: 1rem; text-align: center;">
                    <p>已有账号？ <a href="login.php" class="nav-link">立即登录</a></p>
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
        document.getElementById('registerForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const username = formData.get('username');
            const email = formData.get('email');
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            
            if (password !== confirmPassword) {
                alert('两次输入的密码不一致');
                return;
            }
            
            try {
                const response = await fetch('api/auth.php?action=register', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ 
                        username: username,
                        email: email,
                        password: password,
                        confirm_password: confirmPassword
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('注册成功，请登录');
                    window.location.href = 'login.php';
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