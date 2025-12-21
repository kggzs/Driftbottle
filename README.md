# Driftbottle - 漂流瓶系统 🌊

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP Version](https://img.shields.io/badge/PHP-%3E%3D7.3-8892BF.svg)](https://www.php.net/)
[![MySQL Version](https://img.shields.io/badge/MySQL-%3E%3D5.6-4479A1.svg)](https://www.mysql.com/)

一个基于 Web 的匿名社交漂流瓶系统，让用户可以自由地扔出或捡起漂流瓶，分享心情，寻找共鸣。

## ✨ 系统概述

漂流瓶系统是一个轻量级的社交平台，旨在为用户提供一个安全、匿名的空间来分享内心感受、结交新朋友。它借鉴了“漂流瓶”这一经典概念，将用户的想法和情感以数字化的形式投入互联网的海洋，等待有缘人发现。

系统设计简洁直观，注重用户体验与互动性，同时将用户隐私和数据安全放在首位。无论您是想倾诉心事、寻求建议，还是仅仅想与陌生人分享一个有趣的想法，漂流瓶系统都能满足您的需求。

## 🚀 主要功能

### 👤 用户功能

- **注册与登录**：快速创建账户，安全登录。
- **个人资料**：管理个人信息，设置个性签名。
- **漂流瓶操作**：
    - **扔瓶子**：写下心情或想法，投入大海。
    - **语音漂流瓶**：🎤 录制语音消息，发布语音漂流瓶（支持录音、播放、时长显示）。
    - **捡瓶子**：随机捡起他人的漂流瓶（支持文字和语音两种类型）。
    - **互动**：评论、点赞漂流瓶。
    - **记录**：查看自己扔出和捡到的瓶子（支持语音播放）。
- **性别标识**：男性漂流瓶 (蓝色 🔵)，女性漂流瓶 (粉色 🌸)。
- **匿名选项**：可选择匿名发送，保护隐私。

### 💎 高级功能

- **VIP 会员**：购买 VIP 享受更多特权（如：更多扔/捡瓶次数、专属标识）。
- **签到系统**：每日签到获取积分，连续签到有额外奖励。
- **积分系统**：通过多种方式赚取积分，兑换系统特权。
- **用户等级系统**：🎯 通过发漂流瓶、捡漂流瓶、评论等操作获得经验值，自动升级等级，在个人主页显示等级和经验条。
- **IP 保护**：VIP 用户 IP 地址完全隐藏，普通用户部分隐藏。
- **消息中心**：接收系统通知和互动消息。

### 🛡️ 安全特性

- **防 XSS 攻击**：严格的输入过滤和内容安全策略 (CSP)。
- **防 SQL 注入**：使用参数化查询和输入验证。
- **防 CSRF 攻击**：实施 CSRF 令牌验证。
- **会话安全**：防止会话固定攻击。
- **数据验证**：前后端双重数据校验。

### ⚙️ 管理员功能

- **用户管理**：查看、封禁/解封用户，重置密码，设置用户经验值和等级。
- **内容管理**：管理漂流瓶、评论，发布公告。
- **系统设置**：配置基础参数、积分规则、经验值规则、VIP 特权、安全策略。
- **数据统计**：监控用户活跃度、漂流瓶数据、系统运行状态。

## 🛠️ 技术栈

| 类型     | 技术          |
| :------- | :------------ |
| 前端     | HTML, CSS, JavaScript |
| 后端     | PHP 7.3+      |
| 数据库   | MySQL 5.6+    |
| Web 服务器 | Apache/Nginx  |

## 🗄️ 数据库结构

系统主要数据表如下：

| 表名                 | 描述             |
| :------------------- | :--------------- |
| `users`              | 用户信息         |
| `bottles`            | 漂流瓶内容（支持文字和语音两种类型） |
| `comments`           | 评论数据         |
| `likes`              | 点赞记录         |
| `pick_records`       | 捡瓶记录         |
| `checkins`           | 签到记录         |
| `points_history`     | 积分历史         |
| `experience_history` | 经验值历史       |
| `announcements`      | 系统公告         |
| `admin_roles`        | 管理员角色       |
| `admins`             | 管理员账号       |
| `admin_login_logs`   | 管理员登录日志   |
| `admin_operation_logs` | 管理员操作日志   |
| `daily_limits`       | 用户每日限制     |
| `messages`           | 消息中心         |
| `system_settings`    | 系统配置         |

## 📦 安装与部署

### ✅ 环境要求

- **PHP**: 7.3 或更高版本 (推荐 7.4 / 8.0)
- **MySQL**: 5.6 或更高版本 / MariaDB 10.3+
- **Web 服务器**: Apache 2.4+ / Nginx 1.18+
- **PHP 扩展**: `mysqli`, `mbstring`, `json`

### 📜 部署步骤

1.  **获取代码**
    ```bash
    # 克隆仓库
    git clone https://github.com/kggzs/Driftbottle.git /path/to/web/driftbottle
    # 或者下载 ZIP 包解压
    ```

2.  **配置 Web 服务器**
    - 将网站根目录指向 `/path/to/web/driftbottle`。
    - (可选) 配置 URL 重写规则（如果需要伪静态，但当前版本已不推荐）。

3.  **数据库设置**
    1.  创建数据库和用户：
        ```sql
        CREATE DATABASE driftbottle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
        CREATE USER 'driftbottle_user'@'localhost' IDENTIFIED BY 'your_secure_password';
        GRANT ALL PRIVILEGES ON driftbottle.* TO 'driftbottle_user'@'localhost';
        FLUSH PRIVILEGES;
        ```
    2.  导入数据库结构：
        ```bash
        mysql -u driftbottle_user -p driftbottle < driftbottle.sql
        ```
    3.  配置数据库连接：
        - 编辑 `includes/config.php` 文件，修改以下常量：
          ```php
          define('DB_HOST', 'localhost');
          define('DB_USER', 'driftbottle_user');
          define('DB_PASS', 'your_secure_password');
          define('DB_NAME', 'driftbottle');
          ```

4.  **设置目录权限**
    - 确保 Web 服务器用户对以下目录有写入权限：
      ```bash
      # 根据您的服务器环境调整命令
      chmod -R 755 /path/to/web/driftbottle
      chmod -R 777 /path/to/web/driftbottle/assets/images/uploads # 如果有上传功能
      chmod -R 777 /path/to/web/driftbottle/uploads/audio # 语音文件存储目录
      chmod -R 777 /path/to/web/driftbottle/logs
      ```

5.  **IP 地址库配置 (可选)**
    - 如需显示 IP 归属地，下载纯真 IP 数据库 `qqwry.dat`。
    - 将 `qqwry.dat` 文件放入 `ip/` 或 `includes/ip/` 目录（请根据 `ip_location.php` 中的实际路径确认）。

6.  **访问测试**
    - 打开浏览器访问您的网站地址。
    - **默认管理员账号**: `admin` / `admin`
    - **重要**: 首次登录后请务必修改管理员密码！
    - **安全提示**: `admin/test_admin.php` 和 `admin/reset_password.php` 是密码重置工具，**生产环境请务必删除**！

### ⬆️ 数据库更新

- 项目包含一些用于更新早期数据库结构的 SQL 脚本（位于 `sql/` 目录或根目录）。
- 如果您是从旧版本升级，请按需执行这些脚本：
  ```bash
  mysql -u driftbottle_user -p driftbottle < sql/update_script_name.sql
  ```
- 主要更新脚本包括：
    - `update_user_status.sql`: 添加用户封禁状态。
    - `update_admin_roles.sql`: 更新管理员角色权限。
    - `system_settings.sql`: 初始化或更新系统设置。
    - `vip_points_settings.sql`: VIP 和积分相关配置。
    - `update_announcements.sql`: 公告系统相关更新。
    - `add_voice_bottle_fields.sql`: 添加语音漂流瓶功能（`bottle_type`、`audio_file`、`audio_duration` 字段）。
    - `add_user_level_system.sql`: 添加用户等级系统（`experience`、`level` 字段和 `experience_history` 表）。

## ❓ 常见问题 (FAQ)

1.  **页面显示空白或 500 错误？**
    - 检查 PHP 错误日志 (`logs/php_errors.log` 或服务器配置的日志路径)。
    - 确认 PHP 版本和所需扩展已安装并启用。
    - 检查文件和目录权限。

2.  **无法连接数据库？**
    - 仔细核对 `includes/config.php` 中的数据库连接信息。
    - 确保 MySQL 服务正在运行。
    - 检查数据库用户权限是否正确授予。

3.  **图片上传失败？**
    - 检查 `assets/images/uploads` (或实际上传目录) 是否存在且具有写入权限。
    - 检查 PHP 配置中的 `upload_max_filesize` 和 `post_max_size` 限制。

4.  **语音功能无法使用？**
    - 确保 `uploads/audio/` 目录存在且具有写入权限（755 或 777）。
    - 检查是否已执行 `sql/add_voice_bottle_fields.sql` 数据库更新脚本。
    - 语音录制功能需要 HTTPS 环境（生产环境要求）。
    - 首次使用需要用户授权麦克风权限。
    - 检查浏览器是否支持 `MediaRecorder API`（Chrome、Firefox、Edge 等现代浏览器）。

5.  **IP 地址归属地显示不正确？**
    - 确认 `qqwry.dat` 文件存在于正确路径且文件完整。
    - 纯真 IP 库需要定期更新。

6.  **API 调用失败或无响应？**
    - 确认 API 请求格式为 `api.php?action=your_action`。
    - 打开浏览器开发者工具 (F12)，检查“网络 (Network)”和“控制台 (Console)”选项卡是否有错误信息。
    - 检查服务器端的 PHP 或 Web 服务器错误日志。
    - 尝试清除浏览器缓存。

## 🔌 API 接口

系统 API 通过 `api.php` 文件提供服务，使用 `action` GET 参数指定调用的端点。

**调用格式:**
```
GET /api.php?action=endpoint_name&param1=value1&...
POST /api.php?action=endpoint_name (with POST data)
```

**主要端点示例:**

- `check_auth`: 检查登录状态
- `login`: 用户登录
- `register`: 用户注册
- `logout`: 用户登出
- `create_bottle`: 创建漂流瓶（支持文字和语音两种类型）
- `upload_audio`: 上传语音文件（用于语音漂流瓶）
- `pick_bottle`: 捡起漂流瓶（自动识别文字/语音类型）
- `comment_bottle`: 评论漂流瓶
- `like_bottle`: 点赞漂流瓶
- `user_bottles`: 获取用户扔出的瓶子
- `user_picked_bottles`: 获取用户捡到的瓶子
- `get_announcements`: 获取系统公告
- `get_basic_settings`: 获取系统基本配置
- `get_experience_config`: 获取经验值规则配置
- `get_user_level_info`: 获取用户等级信息
- ... (更多接口请参考 `api.php` 源码)

## 📁 项目结构

```
driftbottle/
├── admin/                  # 管理员后台模块
│   ├── includes/           # 后台公共文件 (header, footer)
│   ├── *.php               # 各管理页面 (用户、瓶子、评论等)
│   └── ...
├── assets/                 # 静态资源 (CSS, JS, Images, Fonts)
│   ├── css/
│   ├── js/
│   │   ├── app.js          # 主要前端逻辑
│   │   └── utils.js        # 工具函数
│   ├── images/
│   └── fonts/
├── includes/               # 后端核心类库和配置文件
│   ├── config.php          # 数据库和系统配置
│   ├── user.php            # 用户类
│   ├── bottle.php          # 漂流瓶类
│   ├── security.php        # 安全处理类
│   ├── validator.php       # 数据验证类
│   ├── ip_location.php     # IP 定位类
│   ├── admin.php           # 管理员类
│   └── ip/                 # IP 数据库存放目录
├── ip/                     # (可能冗余) IP 数据库目录
├── logs/                   # 日志文件目录
├── sql/                    # SQL 脚本目录
├── uploads/                # 用户上传文件目录
│   └── audio/              # 语音文件存储目录
├── api.php                 # API 入口文件
├── index.html              # 前台首页
├── login.html              # 登录页
├── register.html           # 注册页
├── profile.html            # 个人资料页
├── throw.html              # 扔瓶子页
├── pick.html               # 捡瓶子页
├── driftbottle.sql         # 完整数据库结构
├── .htaccess               # Apache 配置文件 (伪静态已停用)
├── nginx.htaccess          # Nginx 配置文件 (伪静态已停用)
├── CHANGELOG.md            # 版本更新日志
├── LICENSE                 # 开源许可证
└── README.md               # 本文档
```

## ⏳ 更新历史

- **v1.2.0** (2024-12-20): 
    - 🎯 **新增用户等级系统**：
        - 通过发漂流瓶、捡漂流瓶、评论等操作自动获得经验值
        - 根据经验值自动计算用户等级（等级公式：level = floor(sqrt(experience / 100)) + 1）
        - 在个人主页和用户公开主页显示等级徽章和经验条
        - 后台管理员可以查看和设置用户经验值
        - 后台系统设置中可以配置各项操作获得的经验值
        - 经验值历史记录功能
    - 数据库新增字段：`users.experience`、`users.level`
    - 数据库新增表：`experience_history`（经验值历史记录表）
    - 新增 API 端点：`get_experience_config`、`get_user_level_info`
- **v1.1.0** (2024-12-13): 
    - 🎤 **新增语音漂流瓶功能**：
        - 支持录制和发布语音漂流瓶
        - 支持播放语音内容
        - 显示语音时长信息
        - 前端页面（扔瓶、捡瓶、个人中心）全面支持语音播放
        - 管理员后台支持语音播放和文件管理
        - 删除漂流瓶时自动删除关联的语音文件
    - 数据库新增字段：`bottles.bottle_type`、`bottles.audio_file`、`bottles.audio_duration`
    - 新增 API 端点：`upload_audio`（语音文件上传）
- **v1.0.2** (2025-04-21): 弃用伪静态 URL 格式，改为 `?action=` 参数；优化前端错误处理。
- **v1.0.1** (2025-04-20): 增强安全措施；增加 VIP 会员和签到系统。
- **v1.0.0** (初始版本): 实现基础的漂流瓶扔/捡、评论、点赞功能。

详细更新内容请查阅 <mcfile name="CHANGELOG.md" path="\CHANGELOG.md"></mcfile> 文件。

## 💡 未来规划 (待定)

- [x] ~~语音漂流瓶功能~~ ✅ 已实现
- [x] ~~用户等级与成就系统~~ ✅ 已实现
- [ ] 漂流瓶内容分类/标签系统
- [ ] 用户间私信功能
- [ ] 漂流瓶收藏夹
- [ ] 更丰富的用户个性化设置
- [ ] 移动端适配或 App 开发

## 📜 开源协议

本项目基于 **MIT 许可证** 开源。详情请见 <mcfile name="LICENSE" path="\LICENSE"></mcfile> 文件。

## 🔒 安全建议

1.  **定期备份**: 定期备份数据库和重要文件。
2.  **强密码**: 使用复杂且唯一的数据库密码和管理员密码，并定期更换。
3.  **HTTPS**: 部署 SSL 证书，启用 HTTPS 加密传输。
4.  **删除安装/调试文件**: 生产环境中务必删除 `admin/test_admin.php`, `admin/reset_password.php` 等调试或密码重置工具。
5.  **更新依赖**: 保持 PHP、MySQL、Web 服务器等软件为最新稳定版本。
6.  **安全审计**: 定期审查代码和服务器配置，关注安全漏洞。
7.  **日志监控**: 定期检查 `logs/` 目录下的日志文件，监控异常活动。
8.  **CSP 策略**: 根据需要调整 `includes/security.php` 中的内容安全策略 (CSP)。

## 📫 联系与支持

- **QQ**: 1724464998
- **GitHub Issues**: <https://github.com/kggzs/Driftbottle/issues>

## ⭐ Star History

[![Star History Chart](https://api.star-history.com/svg?repos=kggzs/Driftbottle&type=Date)](https://www.star-history.com/#kggzs/Driftbottle&Date)

## 📸 系统截图

以下为系统部分界面截图：

**首页**
![系统首页](assets/images/首页.png)
*主要功能入口和公告展示*

**扔漂流瓶**
![扔漂流瓶页面](assets/images/丢漂流瓶.png)
*编辑内容、选择心情、设置匿名和位置共享*

**捡漂流瓶**
![捡漂流瓶页面](assets/images/捡漂流瓶.png)
*查看瓶子内容、点赞、评论互动*

**个人中心**
![个人中心页面](assets/images/个人中心.png)
*管理个人资料、VIP、签到、消息等*
