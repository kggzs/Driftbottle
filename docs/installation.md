# 安装与部署指南

## 环境要求

- **PHP**: 7.3 或更高版本 (推荐 7.4 / 8.0)
- **MySQL**: 5.6 或更高版本 / MariaDB 10.3+
- **Web 服务器**: Apache 2.4+ / Nginx 1.18+
- **PHP 扩展**: `mysqli`, `mbstring`, `json`

## 部署步骤

### 1. 获取代码

```bash
# 克隆仓库
git clone https://github.com/kggzs/Driftbottle.git /path/to/web/driftbottle
# 或者下载 ZIP 包解压
```

### 2. 配置 Web 服务器

- 将网站根目录指向 `/path/to/web/driftbottle`
- (可选) 配置 URL 重写规则（当前版本不推荐使用伪静态）

### 3. 数据库设置

#### 3.1 创建数据库和用户

```sql
CREATE DATABASE driftbottle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'driftbottle_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON driftbottle.* TO 'driftbottle_user'@'localhost';
FLUSH PRIVILEGES;
```

#### 3.2 导入数据库结构

```bash
mysql -u driftbottle_user -p driftbottle < driftbottle.sql
```

#### 3.3 配置数据库连接

编辑 `includes/config.php` 文件，修改以下常量：

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'driftbottle_user');
define('DB_PASS', 'your_secure_password');
define('DB_NAME', 'driftbottle');
```

### 4. 设置目录权限

确保 Web 服务器用户对以下目录有写入权限：

```bash
# Linux/Mac
chmod -R 755 /path/to/web/driftbottle
chmod -R 777 /path/to/web/driftbottle/uploads/audio
chmod -R 777 /path/to/web/driftbottle/logs

# Windows (PowerShell)
# 确保 IIS_IUSRS 或对应服务账户有写入权限
```

### 5. IP 地址定位配置

- 项目使用高德地图 IP 定位 API 进行 IP 地址归属地查询
- API Key 已包含在数据库初始化脚本中，首次安装无需额外配置
- 如需更换 API Key，可在后台管理系统 → 系统设置 → 基本设置中修改"高德地图API Key"
- 获取 API Key 请访问：[高德开放平台](https://console.amap.com/dev/key/app)

### 6. 访问测试

- 打开浏览器访问您的网站地址
- **默认管理员账号**: `admin` / `admin`
- **重要**: 首次登录后请务必修改管理员密码！
- **安全提示**: `admin/test_admin.php` 和 `admin/reset_password.php` 是密码重置工具，**生产环境请务必删除**！

## 数据库更新脚本

项目包含一些用于更新早期数据库结构的 SQL 脚本（位于 `sql/` 目录）。如果您是从旧版本升级，请按需执行这些脚本：

```bash
mysql -u driftbottle_user -p driftbottle < sql/update_script_name.sql
```

### 主要更新脚本说明

| 脚本文件 | 说明 |
|---------|------|
| `update_user_status.sql` | 添加用户封禁状态字段 |
| `update_admin_roles.sql` | 更新管理员角色权限 |
| `system_settings.sql` | 初始化或更新系统设置表 |
| `vip_points_settings.sql` | VIP 和积分相关配置 |
| `update_announcements.sql` | 公告系统相关更新 |
| `add_voice_bottle_fields.sql` | 添加语音漂流瓶功能字段 |
| `add_user_level_system.sql` | 添加用户等级系统字段和表 |
| `add_description_field.sql` | 添加用户个性签名字段 |
| `add_comment_reply_support.sql` | 添加评论回复支持（parent_id、reply_to_user_id字段） |

**注意**: 
- v1.3.0 版本已包含IP追踪功能（`users.register_ip`、`users.last_login_ip`、`comments.ip_address`），这些字段已合并到主数据库文件 `driftbottle.sql` 中，无需单独执行 `add_user_ip_tracking.sql` 脚本。
- v1.3.1 版本已包含数据库索引优化和高德地图API Key配置，所有优化已合并到主数据库文件 `driftbottle.sql` 中，无需单独执行 `add_amap_api_key.sql` 脚本。

## 常见问题

### 页面显示空白或 500 错误

- 检查 PHP 错误日志 (`logs/php_errors.log` 或服务器配置的日志路径)
- 确认 PHP 版本和所需扩展已安装并启用
- 检查文件和目录权限

### 无法连接数据库

- 仔细核对 `includes/config.php` 中的数据库连接信息
- 确保 MySQL 服务正在运行
- 检查数据库用户权限是否正确授予

### 语音功能无法使用

- 确保 `uploads/audio/` 目录存在且具有写入权限（755 或 777）
- 检查是否已执行 `sql/add_voice_bottle_fields.sql` 数据库更新脚本
- 语音录制功能需要 HTTPS 环境（生产环境要求）
- 首次使用需要用户授权麦克风权限
- 检查浏览器是否支持 `MediaRecorder API`（Chrome、Firefox、Edge 等现代浏览器）

### IP 地址归属地显示不正确

- 项目使用高德地图 IP 定位 API，需要确保服务器能够访问外网
- 检查后台管理系统 → 系统设置 → 基本设置中的"高德地图API Key"是否配置正确且有效
- 查看 PHP 错误日志确认是否有网络请求失败的错误

### API 调用失败或无响应

- 确认 API 请求格式为 `api.php?action=your_action`
- 打开浏览器开发者工具 (F12)，检查"网络 (Network)"和"控制台 (Console)"选项卡是否有错误信息
- 检查服务器端的 PHP 或 Web 服务器错误日志
- 尝试清除浏览器缓存

## 安全建议

1. **定期备份**: 定期备份数据库和重要文件
2. **强密码**: 使用复杂且唯一的数据库密码和管理员密码，并定期更换
3. **HTTPS**: 部署 SSL 证书，启用 HTTPS 加密传输
4. **删除安装/调试文件**: 生产环境中务必删除 `admin/test_admin.php`, `admin/reset_password.php` 等调试或密码重置工具
5. **更新依赖**: 保持 PHP、MySQL、Web 服务器等软件为最新稳定版本
6. **安全审计**: 定期审查代码和服务器配置，关注安全漏洞
7. **日志监控**: 定期检查 `logs/` 目录下的日志文件，监控异常活动

