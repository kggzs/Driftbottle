// 封装API请求函数
const api = {
    // 通用请求函数
    async request(endpoint, method = 'GET', data = null) {
        const options = {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        };
        
        if (data && (method === 'POST' || method === 'PUT')) {
            options.body = JSON.stringify(data);
        }
        
        try {
            // 改用传统的GET参数格式代替伪静态
            const url = `api.php?action=${endpoint}`;
            const response = await fetch(url, options);
            
            // 检查响应状态
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            // 尝试解析JSON响应
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new TypeError(`Expected JSON response but got ${contentType}`);
            }
            
            const text = await response.text();
            try {
                return JSON.parse(text);
            } catch (e) {
                Logger.error('JSON解析错误:', e);
                Logger.error('原始响应:', text);
                throw new Error('服务器返回的数据格式不正确');
            }
        } catch (error) {
            Logger.error('API请求错误:', error);
            return { 
                success: false, 
                message: error.message || '网络请求失败',
                error: error
            };
        }
    },
    
    // 用户相关
    async register(username, password, gender) {
        return await this.request('register', 'POST', { username, password, gender });
    },
    
    async login(username, password) {
        return await this.request('login', 'POST', { username, password });
    },
    
    async logout() {
        return await this.request('logout', 'POST');
    },
    
    async checkAuth() {
        return await this.request('check_auth');
    },
    
    async getUserInfo(userId = null) {
        const params = userId ? { user_id: userId } : {};
        return await this.request('user_info', 'GET', params);
    },
    
    // 漂流瓶相关
    async createBottle(content, isAnonymous = false) {
        return await this.request('create_bottle', 'POST', { 
            content, 
            is_anonymous: isAnonymous ? 1 : 0 
        });
    },
    
    async pickBottle() {
        return await this.request('pick_bottle', 'POST');
    },
    
    async commentBottle(bottleId, content) {
        return await this.request('comment_bottle', 'POST', { bottle_id: bottleId, content });
    },
    
    async likeBottle(bottleId) {
        return await this.request('like_bottle', 'POST', { bottle_id: bottleId });
    },
    
    async getUserBottles(userId = null) {
        const params = userId ? { user_id: userId } : {};
        return await this.request('user_bottles', 'GET', params);
    },
    
    async getUserPickedBottles() {
        return await this.request('user_picked_bottles', 'GET');
    },
    
    // 获取公告列表
    async getAnnouncements() {
        return await this.request('get_announcements', 'GET');
    },
    
    // 获取网站基本设置
    async getBasicSettings() {
        return await this.request('get_basic_settings', 'GET');
    }
};

// 工具函数
const utils = {
    // 显示提示消息
    showAlert(message, type = 'info', container = '.alert-container', autoHide = true) {
        const alertContainer = document.querySelector(container);
        if (!alertContainer) return;
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.textContent = message;
        
        alertContainer.innerHTML = '';
        alertContainer.appendChild(alertDiv);
        
        if (autoHide) {
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }
    },
    
    // 格式化日期
    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleString('zh-CN', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit'
        });
    },
    
    // 检查用户是否已登录，如未登录则跳转到登录页
    async checkLogin() {
        const response = await api.checkAuth();
        if (!response.success || !response.loggedIn) {
            window.location.href = 'login.html';
            return false;
        }
        return true;
    },
    
    // 更新导航栏状态
    async updateNavBar() {
        const navbarContainer = document.querySelector('.navbar-nav');
        if (!navbarContainer) return;
        
        const response = await api.checkAuth();
        
        navbarContainer.innerHTML = '';
        
        if (response.success && response.loggedIn) {
            // 已登录状态
            navbarContainer.innerHTML = `
                <li class="nav-item"><a href="index.html" class="nav-link">首页</a></li>
                <li class="nav-item"><a href="throw.html" class="nav-link">扔漂流瓶</a></li>
                <li class="nav-item"><a href="pick.html" class="nav-link">捡漂流瓶</a></li>
                <li class="nav-item"><a href="profile.html" class="nav-link">个人中心</a></li>
                <li class="nav-item"><a href="#" class="nav-link logout-btn">退出登录</a></li>
            `;
            
            // 绑定退出登录事件
            document.querySelector('.logout-btn').addEventListener('click', async (e) => {
                e.preventDefault();
                const response = await api.logout();
                if (response.success) {
                    window.location.href = 'login.html';
                }
            });
        } else {
            // 未登录状态
            navbarContainer.innerHTML = `
                <li class="nav-item"><a href="index.html" class="nav-link">首页</a></li>
                <li class="nav-item"><a href="login.html" class="nav-link">登录</a></li>
                <li class="nav-item"><a href="register.html" class="nav-link">注册</a></li>
            `;
        }
    }
};

// 全局用户信息
window.userInfo = null;

// 全局网站设置
window.siteSettings = null;

// 页面加载完成后执行
document.addEventListener('DOMContentLoaded', async function() {
    try {
        // 打印当前页面路径，帮助调试
        const currentPage = window.location.pathname.split('/').pop() || 'index.html';
        document.title = '[DEBUG] ' + document.title;
        console.log('======= 页面初始化开始 =======');
        console.log('当前页面路径:', window.location.pathname);
        console.log('当前页面名称:', currentPage);
        
        // 加载网站基本设置
        await loadSiteSettings();
        
        // 初始化导航栏
        await utils.updateNavBar();
        
        // 根据当前页面执行不同的初始化函数
        if (currentPage === 'index.html' || currentPage === '') {
            console.log('初始化首页');
            await announcements.init();
        } else if (currentPage === 'register.html') {
            console.log('初始化注册页面');
            initRegisterPage();
        } else if (currentPage === 'login.html') {
            console.log('初始化登录页面');
            initLoginPage();
        } else if (currentPage === 'throw.html') {
            console.log('初始化扔漂流瓶页面');
            await initThrowBottlePage();
        } else if (currentPage === 'pick.html') {
            console.log('初始化捡漂流瓶页面');
            await initPickBottlePage();
        } else if (currentPage === 'profile.html') {
            console.log('初始化个人中心页面');
            await initProfilePage();
        } else {
            console.log('未知页面，不执行特定初始化');
        }
        
        console.log('======= 页面初始化完成 =======');
    } catch (error) {
        console.error('页面初始化出错:', error.message, error.stack);
    }
});

// 加载网站基本设置
async function loadSiteSettings() {
    try {
        const response = await api.getBasicSettings();
        if (response.success) {
            window.siteSettings = response.settings;
            
            // 更新页面标题
            if (window.siteSettings.SITE_NAME) {
                // 更新网站名称
                updateSiteName(window.siteSettings.SITE_NAME);
            }
        }
    } catch (error) {
        console.error('加载网站设置失败:', error);
    }
}

// 更新网站名称
function updateSiteName(siteName) {
    // 更新页面标题
    document.title = document.title.replace('漂流瓶', siteName);
    
    // 更新导航栏品牌名称
    const navbarBrand = document.querySelector('.navbar-brand');
    if (navbarBrand) {
        navbarBrand.textContent = siteName;
    }
    
    // 更新其他可能包含网站名称的元素
    const siteNameElements = document.querySelectorAll('.site-name');
    siteNameElements.forEach(el => {
        el.textContent = siteName;
    });
}

// 检查用户登录状态
function checkAuth(callback) {
    fetch('api.php?action=check_auth')
    .then(response => response.json())
    .then(data => {
        if (data.success && data.loggedIn) {
            // 保存用户信息到全局变量
            window.userInfo = {
                id: data.user_id,
                username: data.username,
                gender: data.gender,
                points: data.points || 0,
                signature: data.signature || '',
                unread_messages: data.unread_messages || 0,
                daily_limits: data.daily_limits || {}
            };
            callback(true);
        } else {
            callback(false);
        }
    })
    .catch(error => {
        Logger.error('Auth check error:', error);
        callback(false);
    });
}

// 显示提醒
function showAlert(message, type = 'success') {
    const alertContainer = document.querySelector('.alert-container');
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    alertContainer.appendChild(alertDiv);
    
    // 自动关闭提醒
    setTimeout(() => {
        alertDiv.classList.remove('show');
        setTimeout(() => alertDiv.remove(), 300);
    }, 3000);
}

// 初始化导航栏
function initNavbar() {
    const navbarNav = document.querySelector('.navbar-nav');
    
    if (window.userInfo) {
        // 用户已登录，显示登录后的导航
        navbarNav.innerHTML = `
            <li class="nav-item">
                <a class="nav-link" href="index.html">首页</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="throw.html">扔漂流瓶</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="pick.html">捡漂流瓶</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="profile.html">个人中心 ${window.userInfo.unread_messages > 0 ? `<span class="badge bg-danger">${window.userInfo.unread_messages}</span>` : ''}</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" id="logoutBtn">退出</a>
            </li>
        `;
        
        // 添加退出登录事件
        document.getElementById('logoutBtn').addEventListener('click', function(e) {
            e.preventDefault();
            
            fetch('api.php?action=logout')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.userInfo = null;
                    window.location.href = 'login.html';
                }
            })
            .catch(error => Logger.error('Logout error:', error));
        });
    } else {
        // 用户未登录，显示登录/注册导航
        navbarNav.innerHTML = `
            <li class="nav-item">
                <a class="nav-link" href="index.html">首页</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="login.html">登录</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="register.html">注册</a>
            </li>
        `;
    }
}

// 加载用户漂流瓶
function loadUserBottles() {
    fetch('api.php?action=user_bottles')
    .then(response => response.json())
    .then(data => {
        const container = document.querySelector('.bottles-container');
        
        if (data.success && data.bottles.length > 0) {
            // 排序漂流瓶，最新的在前
            const bottles = data.bottles.sort((a, b) => new Date(b.throw_time) - new Date(a.throw_time));
            
            let html = '<div class="row">';
            bottles.forEach(bottle => {
                const throwTime = new Date(bottle.throw_time).toLocaleString();
                const isAnonymous = bottle.is_anonymous == 1 ? '<span class="badge bg-secondary">匿名</span>' : '';
                
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-info">${bottle.mood || '其他'}</span>
                                    ${isAnonymous}
                                </div>
                                <small class="text-muted">${throwTime}</small>
                            </div>
                            <div class="card-body">
                                <p class="card-text">${bottle.content}</p>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between">
                                    <span><i class="fas fa-heart text-danger"></i> ${bottle.like_count || 0} 点赞</span>
                                    <span><i class="fas fa-comment text-primary"></i> ${bottle.comment_count || 0} 评论</span>
                                    <span class="badge ${bottle.status === '漂流中' ? 'bg-success' : 'bg-secondary'}">${bottle.status}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            container.innerHTML = html;
        } else {
            container.innerHTML = `
                <div class="text-center">
                    <p>你还没有扔出过漂流瓶，去<a href="throw.html">扔一个</a>吧！</p>
                </div>
            `;
        }
    })
    .catch(error => {
        Logger.error('Error:', error);
        document.querySelector('.bottles-container').innerHTML = `
            <div class="alert alert-danger">
                加载漂流瓶失败，请稍后再试
            </div>
        `;
    });
}

// 加载用户捡到的漂流瓶
function loadUserPickedBottles() {
    fetch('api.php?action=user_picked_bottles')
    .then(response => response.json())
    .then(data => {
        const container = document.querySelector('.picked-bottles-container');
        
        if (data.success && data.bottles.length > 0) {
            // 排序漂流瓶，最近捡到的在前
            const bottles = data.bottles.sort((a, b) => new Date(b.pick_time) - new Date(a.pick_time));
            
            let html = '<div class="row">';
            bottles.forEach(bottle => {
                const pickTime = new Date(bottle.pick_time).toLocaleString();
                
                html += `
                    <div class="col-md-6 mb-3">
                        <div class="card h-100">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-info">${bottle.mood || '其他'}</span>
                                    <span class="text-primary">${bottle.username}</span>
                                </div>
                                <small class="text-muted">捡到于 ${pickTime}</small>
                            </div>
                            <div class="card-body">
                                <p class="card-text">${bottle.content}</p>
                            </div>
                            <div class="card-footer">
                                <div class="d-flex justify-content-between">
                                    <span><i class="fas fa-heart text-danger"></i> ${bottle.like_count || 0} 点赞</span>
                                    <span><i class="fas fa-comment text-primary"></i> ${bottle.comment_count || 0} 评论</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            
            container.innerHTML = html;
        } else {
            container.innerHTML = `
                <div class="text-center">
                    <p>你还没有捡到过漂流瓶，去<a href="pick.html">捡一个</a>吧！</p>
                </div>
            `;
        }
    })
    .catch(error => {
        Logger.error('Error:', error);
        document.querySelector('.picked-bottles-container').innerHTML = `
            <div class="alert alert-danger">
                加载捡到的漂流瓶失败，请稍后再试
            </div>
        `;
    });
}

// 公告系统相关函数
const announcements = {
    currentIndex: 0,
    items: [],
    
    async init() {
        const container = document.getElementById('announcements-container');
        if (!container) return;
        
        try {
            const response = await api.getAnnouncements();
            if (response.success && response.announcements.length > 0) {
                this.items = response.announcements;
                this.updateDisplay();
                this.setupControls();
            } else {
                container.innerHTML = '<p class="text-center text-muted">暂无公告</p>';
            }
        } catch (error) {
            Logger.error('获取公告失败:', error);
            container.innerHTML = '<p class="text-center text-danger">获取公告失败</p>';
        }
    },
    
    updateDisplay() {
        if (this.items.length === 0) return;
        
        const container = document.getElementById('announcements-container');
        const announcement = this.items[this.currentIndex];
        
        container.innerHTML = `
            <div class="announcement-item">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h4 class="mb-0">
                        <span class="badge bg-${announcement.type_class} me-2">${announcement.type}</span>
                        ${announcement.title}
                    </h4>
                    <small class="text-muted">${utils.formatDate(announcement.created_at)}</small>
                </div>
                <div class="announcement-content">${announcement.content}</div>
            </div>
        `;
        //<div class="text-end mt-2">
        //<small class="text-muted">发布者：${announcement.creator_name}</small>
        //</div>
        // 更新控制按钮状态
        const prevBtn = document.querySelector('.announcement-prev');
        const nextBtn = document.querySelector('.announcement-next');
        
        if (prevBtn) prevBtn.disabled = this.currentIndex === 0;
        if (nextBtn) nextBtn.disabled = this.currentIndex === this.items.length - 1;
    },
    
    setupControls() {
        const prevBtn = document.querySelector('.announcement-prev');
        const nextBtn = document.querySelector('.announcement-next');
        
        if (prevBtn) {
            prevBtn.disabled = this.currentIndex === 0;
            prevBtn.addEventListener('click', () => {
                if (this.currentIndex > 0) {
                    this.currentIndex--;
                    this.updateDisplay();
                }
            });
        }
        
        if (nextBtn) {
            nextBtn.disabled = this.currentIndex === this.items.length - 1;
            nextBtn.addEventListener('click', () => {
                if (this.currentIndex < this.items.length - 1) {
                    this.currentIndex++;
                    this.updateDisplay();
                }
            });
        }
    }
};

// 初始化注册页面
function initRegisterPage() {
    console.log('开始初始化注册页面...');
    const registerForm = document.getElementById('registerForm');
    
    // 如果表单不存在，则退出
    if (!registerForm) {
        console.log('注册表单不存在，退出初始化');
        return;
    }
    
    console.log('注册表单存在，添加提交事件监听器');
    registerForm.addEventListener('submit', async (e) => {
        console.log('注册表单提交事件触发');
        e.preventDefault();
        
        try {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const genderEl = document.querySelector('input[name="gender"]:checked');
            
            console.log('表单元素检查:');
            console.log('- username元素:', username ? '存在' : '不存在');
            console.log('- password元素:', password ? '存在' : '不存在');
            console.log('- confirm_password元素:', confirmPassword ? '存在' : '不存在');
            console.log('- 已选中的gender元素:', genderEl ? '存在' : '不存在');
            
            const usernameValue = username ? username.value : null;
            const passwordValue = password ? password.value : null;
            const confirmPasswordValue = confirmPassword ? confirmPassword.value : null;
            const gender = genderEl ? genderEl.value : null;
            
            // 简单验证
            if (!usernameValue || !passwordValue || !confirmPasswordValue || !gender) {
                console.log('表单验证失败，有字段为空');
                utils.showAlert('请填写所有字段', 'danger');
                return;
            }
            
            if (passwordValue !== confirmPasswordValue) {
                console.log('密码不匹配');
                utils.showAlert('两次输入的密码不一致', 'danger');
                return;
            }
            
            console.log('表单验证成功，发送注册请求');
            // 发送注册请求
            const response = await api.register(usernameValue, passwordValue, gender);
            
            if (response.success) {
                console.log('注册成功');
                utils.showAlert('注册成功，即将跳转到登录页面', 'success');
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            } else {
                console.log('注册失败:', response.message);
                utils.showAlert(response.message || '注册失败，请稍后再试', 'danger');
            }
        } catch (error) {
            console.error('注册过程中发生错误:', error);
            utils.showAlert('注册过程中发生错误', 'danger');
        }
    });
    
    console.log('注册页面初始化完成');
}

// 初始化登录页面
function initLoginPage() {
    console.log('开始初始化登录页面...');
    const loginForm = document.getElementById('loginForm');
    
    // 如果表单不存在，则退出
    if (!loginForm) {
        console.log('登录表单不存在，退出初始化');
        return;
    }
    
    console.log('登录表单存在，添加提交事件监听器');
    loginForm.addEventListener('submit', async (e) => {
        console.log('登录表单提交事件触发');
        e.preventDefault();
        
        try {
            const username = document.getElementById('username');
            const password = document.getElementById('password');
            
            console.log('表单元素检查:');
            console.log('- username元素:', username ? '存在' : '不存在');
            console.log('- password元素:', password ? '存在' : '不存在');
            
            const usernameValue = username ? username.value : null;
            const passwordValue = password ? password.value : null;
            
            // 简单验证
            if (!usernameValue || !passwordValue) {
                console.log('表单验证失败，有字段为空');
                utils.showAlert('请填写用户名和密码', 'danger');
                return;
            }
            
            console.log('表单验证成功，发送登录请求');
            // 发送登录请求
            const response = await api.login(usernameValue, passwordValue);
            
            if (response.success) {
                console.log('登录成功');
                utils.showAlert('登录成功，即将跳转到首页', 'success');
                setTimeout(() => {
                    window.location.href = 'index.html';
                }, 1500);
            } else {
                console.log('登录失败:', response.message);
                utils.showAlert(response.message || '登录失败，请检查用户名和密码', 'danger');
            }
        } catch (error) {
            console.error('登录过程中发生错误:', error);
            utils.showAlert('登录过程中发生错误', 'danger');
        }
    });
    
    console.log('登录页面初始化完成');
}

// 初始化扔漂流瓶页面
async function initThrowBottlePage() {
    // 检查登录状态
    if (!(await utils.checkLogin())) return;
    
    const throwBottleForm = document.getElementById('throwBottleForm');
    
    // 如果表单不存在，则退出
    if (!throwBottleForm) {
        console.log('扔漂流瓶表单不存在');
        return;
    }
    
    throwBottleForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const content = document.getElementById('bottleContent').value;
        const isAnonymous = document.getElementById('isAnonymous').checked;
        
        // 简单验证
        if (!content) {
            utils.showAlert('请输入漂流瓶内容', 'danger');
            return;
        }
        
        // 发送创建漂流瓶请求
        const response = await api.createBottle(content, isAnonymous);
        
        if (response.success) {
            utils.showAlert('漂流瓶已成功扔出', 'success');
            document.getElementById('bottleContent').value = '';
            document.getElementById('isAnonymous').checked = false;
        } else {
            utils.showAlert(response.message || '扔漂流瓶失败，请稍后再试', 'danger');
        }
    });
}

// 初始化捡漂流瓶页面
async function initPickBottlePage() {
    // 检查登录状态
    if (!(await utils.checkLogin())) return;
    
    const pickBottleBtn = document.getElementById('pickBottleBtn');
    const bottleContainer = document.querySelector('.bottle-container');
    
    // 防止重复绑定事件
    if (pickBottleBtn && !pickBottleBtn.hasAttribute('data-init')) {
        // 标记按钮已初始化
        pickBottleBtn.setAttribute('data-init', 'true');
        
        pickBottleBtn.addEventListener('click', async () => {
            // 禁用按钮防止重复点击
            pickBottleBtn.disabled = true;
            pickBottleBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> 正在寻找漂流瓶...';
            bottleContainer.innerHTML = '<div class="text-center"><p>正在寻找漂流瓶...</p></div>';
            
            try {
                const response = await api.pickBottle();
                
                if (response.success && response.bottle) {
                    const bottle = response.bottle;
                    
                    // 调用页面上的 displayBottle 函数显示漂流瓶内容
                    if (typeof displayBottle === 'function') {
                        displayBottle(bottle);
                    } else {
                        // 如果没有页面自定义的 displayBottle 函数，使用默认实现
                        const genderClass = bottle.gender === '男' ? 'bottle-male' : 'bottle-female';
                        
                        let commentsHtml = '';
                        if (bottle.comments && bottle.comments.length > 0) {
                            commentsHtml = `
                                <div class="comments">
                                    <h5>历史评论 (${bottle.comments.length})</h5>
                                    ${bottle.comments.map(comment => `
                                        <div class="comment">
                                            <div class="comment-header">
                                                <span class="comment-author ${comment.gender === '男' ? 'comment-male' : 'comment-female'}">
                                                    ${comment.username}
                                                </span>
                                                <span class="comment-time">${utils.formatDate(comment.created_at)}</span>
                                            </div>
                                            <div class="comment-content">${comment.content}</div>
                                        </div>
                                    `).join('')}
                                </div>
                            `;
                        }
                        
                        bottleContainer.innerHTML = `
                            <div class="bottle ${genderClass}" data-id="${bottle.id}">
                                <div class="bottle-header">
                                    <span class="bottle-author">${bottle.username}</span>
                                    <span class="bottle-time">${utils.formatDate(bottle.throw_time)}</span>
                                </div>
                                <div class="bottle-content">${bottle.content}</div>
                                <div class="bottle-actions">
                                    <div>
                                        <button class="btn ${bottle.has_liked ? 'btn-danger' : 'btn-outline-danger'} like-btn" onclick="likeBottle(${bottle.id}, this)">
                                            <i class="fas fa-heart"></i> <span class="like-count">${bottle.like_count || 0}</span> 点赞
                                        </button>
                                    </div>
                                    <div class="comment-section">
                                        <textarea id="commentContent" class="form-control mb-2" placeholder="写下你的评论..."></textarea>
                                        <button class="btn btn-primary submit-comment-btn">评论并扔回大海</button>
                                    </div>
                                    ${commentsHtml}
                                </div>
                            </div>
                        `;
                        
                        // 绑定提交评论事件
                        document.querySelector('.submit-comment-btn').addEventListener('click', async function() {
                            const bottleId = document.querySelector('.bottle').dataset.id;
                            const content = document.getElementById('commentContent').value;
                            
                            if (!content) {
                                utils.showAlert('请输入评论内容', 'danger');
                                return;
                            }
                            
                            const response = await api.commentBottle(bottleId, content);
                            
                            if (response.success) {
                                utils.showAlert('评论成功，漂流瓶已重新扔回大海', 'success');
                                bottleContainer.innerHTML = '';
                                // 重新启用捡瓶按钮
                                pickBottleBtn.disabled = false;
                                pickBottleBtn.innerHTML = '<i class="fas fa-search"></i> 随机捡一个漂流瓶';
                            } else {
                                utils.showAlert(response.message || '评论失败，请稍后再试', 'danger');
                            }
                        });
                    }
                    
                    // 调用页面上的 updatePickedCount 函数更新已捡起的瓶子数量
                    if (typeof updatePickedCount === 'function') {
                        updatePickedCount();
                    }
                    
                    // 更新剩余次数
                    if (typeof getDailyLimits === 'function') {
                        getDailyLimits();
                    }
                } else {
                    bottleContainer.innerHTML = `
                        <div class="text-center">
                            <p>${response.message || '暂时没有可捡起的漂流瓶，请稍后再试'}</p>
                        </div>
                    `;
                    // 重新启用捡瓶按钮
                    pickBottleBtn.disabled = false;
                    pickBottleBtn.innerHTML = '<i class="fas fa-search"></i> 随机捡一个漂流瓶';
                }
            } catch (error) {
                Logger.error('Error:', error);
                bottleContainer.innerHTML = '<div class="text-center"><p>捡瓶失败，请稍后再试</p></div>';
                pickBottleBtn.disabled = false;
                pickBottleBtn.innerHTML = '<i class="fas fa-search"></i> 随机捡一个漂流瓶';
            }
        });
    }
}

// 初始化个人中心页面
async function initProfilePage() {
    // 检查登录状态
    if (!(await utils.checkLogin())) return;
    
    const profileContainer = document.querySelector('.profile-container');
    const bottlesContainer = document.querySelector('.bottles-container');
    const pickedBottlesContainer = document.querySelector('.picked-bottles-container');
    
    // 获取用户信息
    const userResponse = await api.getUserInfo();
    
    if (userResponse.success && userResponse.user) {
        const user = userResponse.user;
        const genderClass = user.gender === '男' ? '' : 'female';
        
        // 计算经验条进度
        const experience = user.experience || 0;
        const level = user.level || 1;
        const currentLevelExp = user.current_level_exp || 0;
        const nextLevelExp = user.next_level_exp || 0;
        const expProgress = user.exp_progress || 0;
        const expNeeded = nextLevelExp - experience;
        
        // 更新个人资料
        profileContainer.innerHTML = `
            <div class="profile-header">
                <div class="profile-avatar ${genderClass}">${user.username.charAt(0).toUpperCase()}</div>
                <h2 class="profile-username">
                    ${user.username} 
                    <span class="badge bg-info ms-2">Lv.${level}</span>
                    ${user.is_vip ? '<span class="badge bg-warning text-dark"><i class="fas fa-crown"></i> VIP</span>' : ''}
                </h2>
                <p>性别: ${user.gender} · 注册时间: ${utils.formatDate(user.created_at)}</p>
            </div>
            
            <!-- 经验条 -->
            <div class="mb-3 mt-3">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <small class="text-muted">经验值: ${experience}</small>
                    <small class="text-muted">距离下一级还需: ${expNeeded > 0 ? expNeeded : 0}</small>
                </div>
                <div class="progress" style="height: 20px;">
                    <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" 
                         role="progressbar" 
                         style="width: ${expProgress}%" 
                         aria-valuenow="${expProgress}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                        ${expProgress.toFixed(1)}%
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <small class="text-muted">Lv.${level}</small>
                    <small class="text-muted">Lv.${level + 1}</small>
                </div>
            </div>
            
            <div class="profile-stats">
                <div class="stat-item">
                    <div class="stat-value">${user.bottle_count}</div>
                    <div class="stat-label">扔出的漂流瓶</div>
                </div>
                <div class="stat-item">
                    <div class="stat-value">${user.like_count}</div>
                    <div class="stat-label">收到的点赞</div>
                </div>
            </div>
        `;
    } else {
        profileContainer.innerHTML = '<div class="alert alert-danger">加载用户信息失败</div>';
    }
    
    // 获取用户扔出的漂流瓶
    const bottlesResponse = await api.getUserBottles();
    
    if (bottlesResponse.success && bottlesResponse.bottles) {
        if (bottlesResponse.bottles.length === 0) {
            bottlesContainer.innerHTML = '<div class="text-center"><p>你还没有扔出过漂流瓶</p></div>';
        } else {
            bottlesContainer.innerHTML = bottlesResponse.bottles.map(bottle => `
                <div class="bottle">
                    <div class="bottle-header">
                        <span class="bottle-author">${bottle.is_anonymous == 1 ? '匿名' : '你'}</span>
                        <span class="bottle-time">${utils.formatDate(bottle.throw_time)}</span>
                    </div>
                    <div class="bottle-content">${bottle.content}</div>
                    <div class="bottle-actions">
                        <div>
                            <span class="bottle-btn">
                                <i class="fas fa-heart"></i> ${bottle.like_count} 点赞
                            </span>
                            <span class="bottle-btn">
                                <i class="fas fa-comment"></i> ${bottle.comment_count} 评论
                            </span>
                        </div>
                        <div>
                            <span class="bottle-status">${bottle.status}</span>
                        </div>
                    </div>
                </div>
            `).join('');
        }
    } else {
        bottlesContainer.innerHTML = '<div class="alert alert-danger">加载漂流瓶失败</div>';
    }
    
    // 获取用户捡到的漂流瓶
    const pickedBottlesResponse = await api.getUserPickedBottles();
    
    if (pickedBottlesResponse.success && pickedBottlesResponse.bottles) {
        if (pickedBottlesResponse.bottles.length === 0) {
            pickedBottlesContainer.innerHTML = '<div class="text-center"><p>你还没有捡到过漂流瓶</p></div>';
        } else {
            pickedBottlesContainer.innerHTML = pickedBottlesResponse.bottles.map(bottle => {
                const genderClass = bottle.gender === '男' ? 'bottle-male' : 'bottle-female';
                
                return `
                    <div class="bottle ${genderClass}">
                        <div class="bottle-header">
                            <span class="bottle-author">${bottle.username}</span>
                            <span class="bottle-time">捡到时间: ${utils.formatDate(bottle.pick_time)}</span>
                        </div>
                        <div class="bottle-content">${bottle.content}</div>
                        <div class="bottle-actions">
                            <div>
                                <span class="bottle-btn">
                                    <i class="fas fa-heart"></i> ${bottle.like_count} 点赞
                                </span>
                                <span class="bottle-btn">
                                    <i class="fas fa-comment"></i> ${bottle.comment_count} 评论
                                </span>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }
    } else {
        pickedBottlesContainer.innerHTML = '<div class="alert alert-danger">加载捡到的漂流瓶失败</div>';
    }
} 