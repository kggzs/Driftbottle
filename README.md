# Driftbottle - 漂流瓶系统

一个基于Web的漂流瓶系统，用户可以扔出自己的漂流瓶，也可以捡起其他用户的漂流瓶，进行交流互动。通过匿名交流，让用户分享心情、寻找共鸣。

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

## 🚀 安装与部署

### 环境要求

- PHP 7.3+ (推荐 PHP 7.4 或 8.0)
- MySQL 5.6+ 或 MariaDB 10.3+
- Web服务器：Apache 2.4+ 或 Nginx 1.18+
- 确保PHP启用以下扩展：mysqli, mbstring, json

### 详细部署步骤

#### 1. 获取代码

```bash
# 克隆仓库（如果使用Git）
git clone https://github.com/kggzs/Driftbottle /path/to/web/driftbottle
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
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
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

## 📚 项目结构

```
driftbottle/
├── admin/              # 管理员后台
├── assets/             # 静态资源文件
│   ├── css/            # 样式文件
│   ├── js/             # JavaScript文件
│   ├── images/         # 图片文件
│   └── fonts/          # 字体文件
├── includes/           # PHP功能类
│   ├── config.php      # 配置文件
│   ├── user.php        # 用户类
│   └── ...             # 其他功能类
├── ip/                 # IP地址库
├── logs/               # 日志目录
├── api.php             # API接口
├── index.html          # 首页
└── *.html              # 其他页面
```

## 📝 功能更新日志

查看 [CHANGELOG.md](CHANGELOG.md) 获取最新功能和改进信息。

## 🔒 安全建议

1. 定期更新数据库密码和管理员密码
2. 为PHP配置OPcache提高性能和安全性
3. 使用HTTPS加密传输数据
4. 定期备份数据库和关键文件

## 📫 联系与支持

如有问题或建议，请联系QQ：1724464998

## 📄 许可证

本项目采用MIT许可证。详情请参阅 `LICENSE` 文件。 
