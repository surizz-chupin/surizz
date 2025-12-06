// 溯日社区JavaScript文件

// 全局变量
let currentUser = null;

// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    // 初始化用户信息
    checkLoginStatus();
    
    // 绑定事件
    bindEvents();
    
    // 加载首页内容
    loadHomePage();
});

// 检查登录状态
async function checkLoginStatus() {
    try {
        // 这里可以通过API检查用户登录状态
        // 暂时从本地存储获取用户信息
        const user = JSON.parse(localStorage.getItem('currentUser'));
        if (user) {
            currentUser = user;
            updateUIForLogin(user);
        }
    } catch (error) {
        console.error('检查登录状态失败:', error);
    }
}

// 更新UI以反映登录状态
function updateUIForLogin(user) {
    const loginBtn = document.getElementById('loginBtn');
    const registerBtn = document.getElementById('registerBtn');
    const userMenu = document.getElementById('userMenu');
    
    if (loginBtn) loginBtn.style.display = 'none';
    if (registerBtn) registerBtn.style.display = 'none';
    
    if (userMenu) {
        userMenu.style.display = 'flex';
        userMenu.innerHTML = `
            <span>欢迎, ${user.username}</span>
            <button class="btn btn-outline" onclick="logout()">退出</button>
        `;
    }
}

// 登录功能
async function login(username, password) {
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
            currentUser = result.user;
            localStorage.setItem('currentUser', JSON.stringify(result.user));
            updateUIForLogin(result.user);
            showMessage('登录成功', 'success');
            return true;
        } else {
            showMessage(result.message, 'error');
            return false;
        }
    } catch (error) {
        showMessage('网络错误，请重试', 'error');
        return false;
    }
}

// 注册功能
async function register(username, email, password, confirmPassword) {
    if (password !== confirmPassword) {
        showMessage('两次输入的密码不一致', 'error');
        return false;
    }
    
    try {
        const response = await fetch('api/auth.php?action=register', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ username, email, password, confirm_password: confirmPassword })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('注册成功，请登录', 'success');
            return true;
        } else {
            showMessage(result.message, 'error');
            return false;
        }
    } catch (error) {
        showMessage('网络错误，请重试', 'error');
        return false;
    }
}

// 退出登录
async function logout() {
    try {
        await fetch('api/auth.php?action=logout', {
            method: 'POST'
        });
        
        currentUser = null;
        localStorage.removeItem('currentUser');
        updateUIForLogout();
        showMessage('已退出登录', 'info');
    } catch (error) {
        console.error('退出登录失败:', error);
    }
}

// 更新UI以反映退出登录状态
function updateUIForLogout() {
    const loginBtn = document.getElementById('loginBtn');
    const registerBtn = document.getElementById('registerBtn');
    const userMenu = document.getElementById('userMenu');
    
    if (loginBtn) loginBtn.style.display = 'inline-block';
    if (registerBtn) registerBtn.style.display = 'inline-block';
    
    if (userMenu) {
        userMenu.style.display = 'none';
        userMenu.innerHTML = '';
    }
}

// 绑定事件
function bindEvents() {
    // 登录表单提交
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(loginForm);
            const username = formData.get('username');
            const password = formData.get('password');
            
            await login(username, password);
        });
    }
    
    // 注册表单提交
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(registerForm);
            const username = formData.get('username');
            const email = formData.get('email');
            const password = formData.get('password');
            const confirmPassword = formData.get('confirm_password');
            
            await register(username, email, password, confirmPassword);
        });
    }
    
    // 搜索功能
    const searchForm = document.getElementById('searchForm');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const keyword = document.getElementById('searchInput').value;
            searchPosts(keyword);
        });
    }
    
    // 发布帖子
    const postForm = document.getElementById('postForm');
    if (postForm) {
        postForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(postForm);
            const title = formData.get('title');
            const content = formData.get('content');
            const categoryId = formData.get('category_id');
            
            await createPost(title, content, categoryId);
        });
    }
}

// 加载首页内容
async function loadHomePage() {
    try {
        // 显示热门帖子
        await loadHotPosts();
        
        // 显示最新帖子
        await loadLatestPosts();
        
        // 显示精华帖子
        await loadEssencePosts();
    } catch (error) {
        console.error('加载首页内容失败:', error);
    }
}

// 加载热门帖子
async function loadHotPosts() {
    try {
        // 这里应该是API调用，暂时模拟数据
        const hotPosts = [
            { id: 1, title: '如何学习JavaScript', author: '开发者小王', views: 1200, comments: 45 },
            { id: 2, title: '前端开发趋势分析', author: '技术专家', views: 980, comments: 32 },
            { id: 3, title: 'Vue.js实战经验分享', author: '前端工程师', views: 756, comments: 28 }
        ];
        
        const container = document.getElementById('hotPosts');
        if (container) {
            container.innerHTML = hotPosts.map(post => `
                <div class="post-card">
                    <div class="post-header">
                        <h3 class="post-title">${post.title}</h3>
                    </div>
                    <div class="post-meta">
                        <span>作者: ${post.author}</span>
                        <span>浏览: ${post.views}</span>
                    </div>
                    <div class="post-stats">
                        <span>评论: ${post.comments}</span>
                        <a href="post.html?id=${post.id}" class="btn btn-outline">查看详情</a>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('加载热门帖子失败:', error);
    }
}

// 加载最新帖子
async function loadLatestPosts() {
    try {
        // 模拟数据
        const latestPosts = [
            { id: 4, title: '新项目启动讨论', author: '项目经理', time: '2小时前', comments: 5 },
            { id: 5, title: 'Python学习资源推荐', author: 'Python爱好者', time: '4小时前', comments: 12 },
            { id: 6, title: '生活中的小确幸', author: '生活分享者', time: '6小时前', comments: 8 }
        ];
        
        const container = document.getElementById('latestPosts');
        if (container) {
            container.innerHTML = latestPosts.map(post => `
                <div class="post-card">
                    <div class="post-header">
                        <h3 class="post-title">${post.title}</h3>
                    </div>
                    <div class="post-meta">
                        <span>作者: ${post.author}</span>
                        <span>${post.time}</span>
                    </div>
                    <div class="post-stats">
                        <span>评论: ${post.comments}</span>
                        <a href="post.html?id=${post.id}" class="btn btn-outline">查看详情</a>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('加载最新帖子失败:', error);
    }
}

// 加载精华帖子
async function loadEssencePosts() {
    try {
        // 模拟数据
        const essencePosts = [
            { id: 7, title: '高效学习方法总结', author: '学习达人', time: '1天前', likes: 156 },
            { id: 8, title: '职场沟通技巧', author: '职场专家', time: '3天前', likes: 98 },
            { id: 9, title: '健康生活指南', author: '健康顾问', time: '5天前', likes: 76 }
        ];
        
        const container = document.getElementById('essencePosts');
        if (container) {
            container.innerHTML = essencePosts.map(post => `
                <div class="post-card">
                    <div class="post-header">
                        <h3 class="post-title">${post.title}</h3>
                    </div>
                    <div class="post-meta">
                        <span>作者: ${post.author}</span>
                        <span>${post.time}</span>
                    </div>
                    <div class="post-stats">
                        <span>点赞: ${post.likes}</span>
                        <a href="post.html?id=${post.id}" class="btn btn-outline">查看详情</a>
                    </div>
                </div>
            `).join('');
        }
    } catch (error) {
        console.error('加载精华帖子失败:', error);
    }
}

// 搜索帖子
async function searchPosts(keyword) {
    try {
        // 这里应该是API调用
        showMessage(`搜索 "${keyword}" 的结果`, 'info');
    } catch (error) {
        console.error('搜索失败:', error);
    }
}

// 创建帖子
async function createPost(title, content, categoryId) {
    if (!currentUser) {
        showMessage('请先登录', 'error');
        return false;
    }
    
    try {
        const response = await fetch('api/posts.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ title, content, category_id: categoryId })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showMessage('帖子发布成功', 'success');
            // 重置表单
            document.getElementById('postForm').reset();
            return true;
        } else {
            showMessage(result.message, 'error');
            return false;
        }
    } catch (error) {
        showMessage('网络错误，请重试', 'error');
        return false;
    }
}

// 点赞功能
async function toggleLike(targetType, targetId) {
    if (!currentUser) {
        showMessage('请先登录', 'error');
        return false;
    }
    
    try {
        // 这里应该是API调用
        showMessage('点赞功能开发中', 'info');
    } catch (error) {
        console.error('点赞失败:', error);
    }
}

// 添加评论
async function addComment(postId, content) {
    if (!currentUser) {
        showMessage('请先登录', 'error');
        return false;
    }
    
    if (!content.trim()) {
        showMessage('评论内容不能为空', 'error');
        return false;
    }
    
    try {
        // 这里应该是API调用
        showMessage('评论发布成功', 'success');
        // 清空评论框
        document.getElementById('commentContent').value = '';
    } catch (error) {
        console.error('发布评论失败:', error);
    }
}

// 显示消息提示
function showMessage(message, type = 'info') {
    // 创建消息元素
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.textContent = message;
    
    // 添加到页面
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // 3秒后自动移除
    setTimeout(() => {
        alertDiv.remove();
    }, 3000);
}

// 防抖函数
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// 滚动到顶部
function scrollToTop() {
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// 加载更多内容（分页）
function loadMoreContent(page) {
    // 实现分页加载功能
    console.log('加载第', page, '页内容');
}