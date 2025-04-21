# Driftbottle - 漂流瓶系统

一个基于Web的漂流瓶系统，用户可以扔出自己的漂流瓶，也可以捡起其他用户的漂流瓶，进行交流互动。通过匿名交流，让用户分享心情、寻找共鸣。

## 🌟 系统概述

漂流瓶系统是一个轻量级的社交平台，专为用户提供一个分享内心感受、结交新朋友的空间。它通过"漂流瓶"这一古老而浪漫的方式，将用户的情感和思考以数字化形式传递到互联网的海洋，等待被他人发现。

系统设计简洁直观，强调用户体验和互动性，同时注重隐私保护和数据安全。无论是想诉说心事、寻求解答，还是只是想与陌生人分享一个有趣的想法，漂流瓶系统都能满足用户多样化的社交需求。

## 🌟 功能特点

### 基础功能
- **用户管理**：注册、登录、个人资料管理
- **漂流瓶操作**：扔出漂流瓶、捡起漂流瓶、评论、点赞
- **性别标识**：男性漂流瓶（蓝色）、女性漂流瓶（粉色）
- **匿名功能**：可选择匿名发送漂流瓶，保护隐私

### 高级功能
- **VIP会员系统**：购买VIP获取更多特权（发送配额、特殊标识）
- **签到系统**：每日签到获取积分，连续签到奖励递增
- **积分系统**：多种方式获取积分，用于系统内特权兑换
- **个性化设置**：个性签名、用户偏好设置
- **安全保护**：IP地址记录与隐私保护措施
- **账号管理**：管理员可封禁违规账号
- **公告系统**：发布系统公告与重要通知

### 安全特性
- **防XSS攻击**：输入过滤与内容安全策略(CSP)
- **防SQL注入**：参数化查询与输入验证
- **防CSRF攻击**：CSRF令牌验证
- **会话安全**：会话固定攻击防护
- **数据验证**：严格的前后端数据验证机制
- **IP保护**：非VIP用户IP地址部分隐藏

## 📋 技术栈

- **前端**：HTML, CSS, JavaScript
- **后端**：PHP 7.3+
- **数据库**：MySQL 5.6+
- **服务器**：Apache/Nginx

## 📊 数据库结构

系统包含以下主要数据表：
- `users` - 用户信息
- `bottles` - 漂流瓶内容
- `comments` - 评论数据
- `likes` - 点赞记录
- `pick_records` - 捡瓶记录
- `checkins` - 签到记录
- `points_history` - 积分历史
- `announcements` - 系统公告
- `admin_roles` - 管理员角色
- `admins` - 管理员账号
- `admin_login_logs` - 管理员登录日志
- `admin_operation_logs` - 管理员操作日志
- `daily_limits` - 用户每日限制
- `messages` - 消息中心
- `system_settings` - 系统配置

## 🚀 安装与部署

### 环境要求

- PHP 7.3+ (推荐 PHP 7.4 或 8.0)
- MySQL 5.6+ 或 MariaDB 10.3+
- Web服务器：Apache 2.4+ 或 Nginx 1.18+
- 确保PHP启用以下扩展：mysqli, mbstring, json

### 详细部署步骤

#### 1. 获取代码

```bash
# 克隆仓库
git clone https://github.com/kggzs/Driftbottle/ /path/to/web/driftbottle
# 或直接下载源码包解压到网站目录
```

#### 2. 配置Web服务器

**Apache 配置**:
```apache
<VirtualHost *:80>
    ServerName yoursite.com
    DocumentRoot /path/to/web/driftbottle
    
    <Directory /path/to/web/driftbottle>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/driftbottle_error.log
    CustomLog ${APACHE_LOG_DIR}/driftbottle_access.log combined
</VirtualHost>
```

**Nginx 配置**:
```nginx
server {
    listen 80;
    server_name yoursite.com;
    root /path/to/web/driftbottle;
    index index.html index.php;
    
    # 注意：系统已不再使用伪静态功能，使用传统API调用方式
    location / {
        try_files $uri $uri/ /index.html;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock; # 根据您的PHP版本调整
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

#### 3. 数据库配置

1. 创建MySQL数据库
```sql
CREATE DATABASE driftbottle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'driftbottle_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON driftbottle.* TO 'driftbottle_user'@'localhost';
FLUSH PRIVILEGES;
```

2. 导入数据库结构
```bash
mysql -u driftbottle_user -p driftbottle < driftbottle.sql
```

3. 配置数据库连接
   - 修改 `includes/config.php` 文件中的数据库连接信息
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'driftbottle_user');
define('DB_PASS', 'your_secure_password');
define('DB_NAME', 'driftbottle');
```

#### 4. 目录权限设置

确保以下目录可写：
```bash
chmod -R 755 /path/to/web/driftbottle
chmod -R 777 /path/to/web/driftbottle/assets/images
chmod -R 777 /path/to/web/driftbottle/logs
```

#### 5. IP地址库配置（可选）

如果需要显示IP地址所在地区：
1. 下载纯真IP数据库 `qqwry.dat` 文件
2. 将其放入 `ip/` 目录中

#### 6. 访问测试

1. 打开浏览器，访问您配置的网站地址
2. 默认管理员账号：
   - 用户名：admin
   - 密码：admin
3. 首次登录后请立即修改默认密码
4. 注意admin/test_admin.php admin/reset_password.php 都属于管理员密码重置工具，上线运营后及时删除

### 数据库更新

当需要更新数据库结构或系统设置时，可使用以下SQL脚本：

- `update_user_status.sql` - 添加用户封禁功能
- `update_admin_roles.sql` - 更新管理员角色权限
- `system_settings.sql` - 系统设置项配置
- `vip_points_settings.sql` - VIP会员积分配置
- `update_announcements.sql` - 公告系统更新

执行更新脚本示例：
```bash
mysql -u driftbottle_user -p driftbottle < update_script.sql
```

## 🔧 常见问题排查

1. **页面显示空白**
   - 检查PHP错误日志 (`logs/php_errors.log`)
   - 确认PHP版本兼容
   - 检查文件权限

2. **数据库连接错误**
   - 验证数据库连接信息是否正确
   - 确保MySQL服务正在运行
   - 检查用户权限设置

3. **上传图片失败**
   - 检查 `assets/images/` 目录权限
   - 检查PHP上传配置 (`upload_max_filesize` 和 `post_max_size`)

4. **IP位置显示异常**
   - 检查 `ip/qqwry.dat` 文件是否存在且完整
   - 可能需要更新IP数据库

5. **安全警告问题**
   - 检查 `security.php` 中的安全策略配置
   - 更新内容安全策略(CSP)设置
   - 检查 `validator.php` 中的输入验证规则

6. **API调用问题**
   - 确保所有前端API调用使用 `api.php?action=endpoint` 格式
   - 检查浏览器控制台是否有JavaScript错误
   - 检查服务器日志中是否有API相关错误
   - 清除浏览器缓存，确保加载最新的JavaScript文件

## 📝 API接口说明

### API调用格式

系统API使用传统的GET参数格式进行调用，如：
```
api.php?action=endpoint
```

注意：系统最初设计使用伪静态格式 (`api.php/endpoint`)，现已更改为更稳定的传统格式。

### 主要API端点

- `check_auth` - 检查用户登录状态
- `login` - 用户登录
- `register` - 用户注册
- `logout` - 用户退出
- `create_bottle` - 创建漂流瓶
- `pick_bottle` - 捡起漂流瓶
- `comment_bottle` - 评论漂流瓶
- `like_bottle` - 点赞漂流瓶
- `user_bottles` - 获取用户发出的漂流瓶
- `user_picked_bottles` - 获取用户捡到的漂流瓶
- `get_announcements` - 获取系统公告
- `get_basic_settings` - 获取基本设置

## 📚 项目结构

```
driftbottle/
├── admin/                  # 管理员后台
│   ├── includes/           # 管理员后台功能类
│   ├── index.php           # 管理后台主页
│   ├── bottles.php         # 漂流瓶管理
│   ├── users.php           # 用户管理
│   ├── comments.php        # 评论管理
│   ├── announcements.php   # 公告管理
│   ├── settings.php        # 系统设置
│   ├── statistics.php      # 统计数据
│   └── ...                 # 其他管理功能
├── assets/                 # 静态资源文件
│   ├── css/                # 样式文件
│   ├── js/                 # JavaScript文件
│   │   ├── app.js          # 主要应用逻辑
│   │   └── utils.js        # 工具函数和日志记录
│   ├── images/             # 图片文件
│   └── fonts/              # 字体文件
├── includes/               # PHP功能类
│   ├── config.php          # 配置文件
│   ├── user.php            # 用户类
│   ├── bottle.php          # 漂流瓶类
│   ├── security.php        # 安全类
│   ├── validator.php       # 验证器类
│   ├── ip_location.php     # IP定位类
│   └── admin.php           # 管理员类
├── ip/                     # IP地址库
├── logs/                   # 日志目录
├── api.php                 # API接口
├── index.html              # 首页
├── login.html              # 登录页
├── register.html           # 注册页
├── profile.html            # 个人资料页
├── throw.html              # 扔漂流瓶页面
├── pick.html               # 捡漂流瓶页面
├── driftbottle.sql         # 数据库完整结构
├── system_settings.sql     # 系统设置SQL
├── update_*.sql            # 数据库更新脚本
├── .htaccess               # Apache配置（已停用伪静态）
├── nginx.htaccess          # Nginx配置（已停用伪静态）
├── CHANGELOG.md            # 更新日志
└── README.md               # 说明文档
```

## 📅 更新历史

- **v1.0.2** (2025-04-21)：取消使用伪静态功能，优化前端错误处理
- **v1.0.1** (2025-04-20)：增强安全功能，添加VIP会员系统和签到系统
- **v1.0.0** (初始版本)：基础漂流瓶功能实现

有关更详细的更新信息，请查看 [CHANGELOG.md](CHANGELOG.md) 文件。

## 🔮 未来规划

- 漂流瓶内容分类系统
- 用户间私信功能
- 漂流瓶收藏功能
- 更多的用户个性化设置
- 用户等级系统
- 移动端应用开发

## 📄 开源协议

本项目使用 MIT 许可证 - 详见 [LICENSE](LICENSE) 文件。

## 🔒 用户指南

### 普通用户功能
1. **注册与登录**
   - 通过 `register.html` 注册新账号
   - 使用 `login.html` 登录系统

2. **个人资料管理**
   - 在 `profile.html` 编辑个人资料
   - 修改个性签名
   - 查看积分历史

3. **漂流瓶操作**
   - 在 `throw.html` 扔出漂流瓶
   - 在 `pick.html` 捡起他人漂流瓶
   - 对漂流瓶评论和点赞
   - 查看自己的漂流瓶历史

4. **VIP功能**
   - 使用积分购买VIP会员
   - 享受扩展的每日扔瓶和捡瓶配额
   - 获得评论特殊标识
   - IP地址完全保护

5. **积分获取**
   - 每日签到
   - 连续签到额外奖励
   - 漂流瓶被点赞获得积分
   - 特殊活动奖励

### 管理员功能
1. **用户管理**
   - 查看用户列表
   - 封禁/解封用户
   - 重置用户密码
   - 查看用户详细信息

2. **内容管理**
   - 管理漂流瓶内容
   - 审核和删除不当评论
   - 发布系统公告

3. **系统设置**
   - 配置系统参数
   - 设置积分规则
   - 配置VIP特权
   - 调整安全策略

4. **数据统计**
   - 查看用户活跃度
   - 查看漂流瓶数据统计
   - 系统运行状态监控

## 📝 功能更新日志

查看 [CHANGELOG.md](CHANGELOG.md) 获取最新功能和改进信息。

## 🔒 安全建议

1. 定期更新数据库密码和管理员密码
2. 为PHP配置OPcache提高性能和安全性
3. 使用HTTPS加密传输数据
4. 定期备份数据库和关键文件
5. 检查并更新内容安全策略(CSP)
6. 定期审查安全日志

## 📫 联系与支持

如有问题或建议，请联系QQ：1724464998

## Star History

[![Star History Chart](https://api.star-history.com/svg?repos=kggzs/Driftbottle&type=Date)](https://www.star-history.com/#kggzs/Driftbottle&Date) 
