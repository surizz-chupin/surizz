<?php
// 溯日社区 - 管理员后台
require_once '../includes/config.php';
require_once '../includes/database.php';

session_start();

// 检查管理员权限
if (!isAdmin()) {
    header('Location: ../index.php');
    exit;
}

$db = new Database();

// 获取统计数据
$totalUsers = $db->getTotalUsers();
$totalPosts = $db->getTotalPosts();
$totalComments = $db->getTotalComments();
$recentUsers = $db->getRecentUsers(10);
$recentPosts = $db->getRecentPosts(10);

$currentUser = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员后台 - 溯日社区</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .admin-panel {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #007bff;
        }
        .stat-label {
            font-size: 1em;
            color: #6c757d;
        }
        .admin-section {
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .admin-section h3 {
            margin-top: 0;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        th {
            background-color: #f8f9fa;
        }
        .admin-actions {
            display: flex;
            gap: 10px;
            margin-top: 10px;
        }
        .btn-admin {
            padding: 5px 10px;
            font-size: 0.9em;
        }
    </style>
</head>
<body>
    <header class="header">
        <div class="container">
            <nav class="navbar">
                <a href="../index.php" class="navbar-brand">溯日社区</a>
                <ul class="nav-menu">
                    <li class="nav-item"><a href="../index.php" class="nav-link">首页</a></li>
                    <li class="nav-item"><a href="../categories.php" class="nav-link">板块</a></li>
                    <li class="nav-item"><a href="index.php" class="nav-link active">管理后台</a></li>
                </ul>
                <div class="user-menu">
                    <span>管理员: <?= escape($currentUser['username']) ?></span>
                    <a href="../profile.php" class="btn btn-outline">个人中心</a>
                    <button class="btn btn-outline" onclick="logout()">退出</button>
                </div>
            </nav>
        </div>
    </header>

    <main class="main-content">
        <div class="admin-panel">
            <h1>管理员后台</h1>
            
            <!-- 统计卡片 -->
            <div class="stats-cards">
                <div class="stat-card">
                    <div class="stat-number"><?= $totalUsers ?></div>
                    <div class="stat-label">用户总数</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $totalPosts ?></div>
                    <div class="stat-label">帖子总数</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $totalComments ?></div>
                    <div class="stat-label">评论总数</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">3</div>
                    <div class="stat-label">板块数量</div>
                </div>
            </div>
            
            <!-- 最近用户 -->
            <div class="admin-section">
                <h3>最近注册用户</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>用户名</th>
                                <th>邮箱</th>
                                <th>注册时间</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentUsers as $user): ?>
                            <tr>
                                <td><?= $user['id'] ?></td>
                                <td><?= escape($user['username']) ?></td>
                                <td><?= escape($user['email']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $user['status'] ?>"><?= $user['status'] === 'active' ? '正常' : '封禁' ?></span>
                                </td>
                                <td>
                                    <div class="admin-actions">
                                        <?php if ($user['status'] === 'active'): ?>
                                            <a href="user_ban.php?id=<?= $user['id'] ?>" class="btn btn-danger btn-admin" onclick="return confirm('确定要封禁此用户吗？')">封禁</a>
                                        <?php else: ?>
                                            <a href="user_unban.php?id=<?= $user['id'] ?>" class="btn btn-success btn-admin" onclick="return confirm('确定要解封此用户吗？')">解封</a>
                                        <?php endif; ?>
                                        <a href="user_edit.php?id=<?= $user['id'] ?>" class="btn btn-outline btn-admin">编辑</a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- 最近帖子 -->
            <div class="admin-section">
                <h3>最近发布帖子</h3>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>标题</th>
                                <th>作者</th>
                                <th>板块</th>
                                <th>发布时间</th>
                                <th>状态</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentPosts as $post): ?>
                            <tr>
                                <td><?= $post['id'] ?></td>
                                <td><a href="../post.php?id=<?= $post['id'] ?>" target="_blank"><?= escape($post['title']) ?></a></td>
                                <td><?= escape($post['username']) ?></td>
                                <td><?= escape($post['category_name']) ?></td>
                                <td><?= date('Y-m-d H:i', strtotime($post['created_at'])) ?></td>
                                <td>
                                    <span class="status-badge <?= $post['status'] ?>"><?= $post['status'] === 'published' ? '已发布' : ($post['status'] === 'draft' ? '草稿' : '已删除') ?></span>
                                </td>
                                <td>
                                    <div class="admin-actions">
                                        <?php if ($post['status'] !== 'deleted'): ?>
                                            <a href="post_delete.php?id=<?= $post['id'] ?>" class="btn btn-danger btn-admin" onclick="return confirm('确定要删除此帖子吗？')">删除</a>
                                        <?php endif; ?>
                                        <?php if ($post['is_essence']): ?>
                                            <a href="post_unessence.php?id=<?= $post['id'] ?>" class="btn btn-outline btn-admin">取消精华</a>
                                        <?php else: ?>
                                            <a href="post_essence.php?id=<?= $post['id'] ?>" class="btn btn-primary btn-admin">设为精华</a>
                                        <?php endif; ?>
                                        <?php if ($post['is_pinned']): ?>
                                            <a href="post_unpin.php?id=<?= $post['id'] ?>" class="btn btn-outline btn-admin">取消置顶</a>
                                        <?php else: ?>
                                            <a href="post_pin.php?id=<?= $post['id'] ?>" class="btn btn-warning btn-admin">置顶</a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- 系统管理 -->
            <div class="admin-section">
                <h3>系统管理</h3>
                <div class="admin-actions">
                    <a href="categories.php" class="btn btn-primary">板块管理</a>
                    <a href="users.php" class="btn btn-primary">用户管理</a>
                    <a href="posts.php" class="btn btn-primary">帖子管理</a>
                    <a href="reports.php" class="btn btn-primary">举报管理</a>
                    <a href="settings.php" class="btn btn-primary">系统设置</a>
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
                const response = await fetch('../api/auth.php?action=logout', {
                    method: 'POST'
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // 清除本地存储
                    localStorage.removeItem('currentUser');
                    // 重定向到首页
                    window.location.href = '../index.php';
                }
            } catch (error) {
                console.error('退出登录失败:', error);
            }
        }
    </script>
</body>
</html>