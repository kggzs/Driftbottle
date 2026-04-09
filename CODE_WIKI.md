# Driftbottle Code Wiki

## 目录
1. [项目概述](#项目概述)
2. [技术架构](#技术架构)
3. [项目结构](#项目结构)
4. [核心模块说明](#核心模块说明)
5. [关键类与函数](#关键类与函数)
6. [数据库结构](#数据库结构)
7. [API接口文档](#api接口文档)
8. [安全机制](#安全机制)
9. [部署与运行](#部署与运行)

---

## 项目概述

### 项目简介
**Driftbottle** 是一个基于 Web 技术的匿名社交漂流瓶系统，将传统的"漂流瓶"概念数字化，为用户提供一个安全、私密的情感分享平台。

### 核心特性
- 🌊 **漂流瓶核心功能**：扔瓶子、捡瓶子、评论、点赞
- 🎤 **语音漂流瓶**：支持录制和播放语音内容
- 💬 **二级评论回复**：支持对评论进行回复，实现层级结构
- 🎯 **用户等级系统**：通过互动获取经验值，自动升级
- 💎 **VIP会员系统**：更多特权与功能
- 💰 **在线充值系统**：支持多种支付方式
- 🚨 **举报系统**：完善的内容审核机制
- 🛡️ **多重安全防护**：XSS/SQL注入防护、CSRF令牌验证等

### 设计理念
- **数字海洋**：将互联网比作海洋，每个漂流瓶都是用户情感的载体
- **情感连接**：通过随机匹配，让陌生人之间产生有意义的交流
- **隐私保护**：在开放交流的同时，最大程度保护用户隐私
- **持续成长**：通过等级系统和积分机制，激励用户持续参与

---

## 技术架构

### 技术栈
| 类型     | 技术          |
| :------- | :------------ |
| 前端     | HTML5, CSS3, JavaScript (ES6+) |
| UI框架   | Bootstrap 5   |
| 后端     | PHP 7.3+      |
| 数据库   | MySQL 5.6+ / MariaDB 10.3+ |
| Web服务器 | Apache 2.4+ / Nginx 1.18+ |
| 图标库   | Font Awesome  |

### 架构模式
- **前后端分离**：清晰的 API 设计，通过 `api.php` 统一处理所有请求
- **模块化架构**：功能模块独立，易于定制和二次开发
- **MVC雏形**：虽然没有严格的 MVC 框架，但代码组织遵循类似原则

### 请求处理流程
```
前端页面 → api.php → 路由分发 → 功能函数 → 数据库操作 → JSON响应
```

---

## 项目结构

```
driftbottle/
├── admin/                  # 管理员后台模块
│   ├── includes/           # 后台公共文件
│   │   ├── header.php      # 后台头部
│   │   └── footer.php      # 后台底部
│   ├── index.php           # 后台首页
│   ├── users.php           # 用户管理
│   ├── bottles.php         # 漂流瓶管理
│   ├── comments.php        # 评论管理
│   ├── reports.php         # 举报管理
│   ├── recharge_orders.php # 充值订单管理
│   ├── settings.php        # 系统设置
│   └── ...
├── assets/                 # 静态资源
│   ├── css/                # 样式文件
│   │   ├── bootstrap/      # Bootstrap框架
│   │   ├── fontawesome/    # 图标库
│   │   └── style.css       # 自定义样式
│   ├── js/                 # JavaScript文件
│   │   ├── app.js          # 主要前端逻辑
│   │   ├── utils.js        # 工具函数
│   │   └── profile.js      # 个人中心逻辑
│   ├── images/             # 图片资源
│   └── fonts/              # 字体文件
├── docs/                   # 文档目录
│   ├── features/           # 功能文档
│   └── ...
├── includes/               # 后端核心类库
│   ├── config.php          # 数据库和系统配置
│   ├── user.php            # 用户功能函数
│   ├── bottle.php          # 漂流瓶功能函数
│   ├── security.php        # 安全中间件类
│   ├── validator.php       # 数据验证类
│   ├── ip_location.php     # IP定位类
│   ├── admin.php           # 管理员功能类
│   ├── report.php          # 举报功能类
│   └── payment/            # 支付相关文件
│       └── lib/
│           ├── EpayCore.class.php
│           └── epay.config.php
├── logs/                   # 日志文件目录
├── sql/                    # SQL脚本目录
├── uploads/                # 用户上传文件目录
│   └── audio/              # 语音文件存储
├── api.php                 # API入口文件
├── payment_notify.php      # 支付异步回调处理
├── payment_return.php      # 支付同步返回处理
├── index.html              # 前台首页
├── login.html              # 登录页
├── register.html           # 注册页
├── profile.html            # 个人资料页
├── throw.html              # 扔瓶子页
├── pick.html               # 捡瓶子页
├── driftbottle.sql         # 完整数据库结构
└── README.md               # 项目说明
```

---

## 核心模块说明

### 1. 配置模块 ([config.php](file:///workspace/includes/config.php))

**职责**：
- 管理数据库连接配置
- 提供系统设置的缓存机制
- 定义常用的安全过滤函数
- 处理会话管理

**核心功能**：
```php
getDbConnection()        // 获取数据库连接
loadAllSettings()        // 批量加载所有设置到缓存
getSetting($key, $default) // 获取单个设置
updateSetting($key, $value) // 更新设置
sanitizeInput($data)     // 安全过滤输入
isLoggedIn()             // 检查用户是否已登录
getCurrentUserId()       // 获取当前登录用户ID
```

### 2. 用户模块 ([user.php](file:///workspace/includes/user.php))

**职责**：
- 用户注册与登录
- 用户信息管理
- 签到系统
- 积分与等级系统
- VIP会员管理

**核心功能**：
```php
registerUser($username, $password, $gender) // 用户注册
loginUser($username, $password)              // 用户登录
logoutUser()                                  // 用户登出
userCheckin($userId)                          // 用户签到
getUserPoints($userId)                        // 获取用户积分
getUserLevelInfo($userId)                     // 获取用户等级信息
```

### 3. 漂流瓶模块 ([bottle.php](file:///workspace/includes/bottle.php))

**职责**：
- 创建和管理漂流瓶
- 随机捡瓶机制
- 评论与点赞
- 每日限制管理

**核心功能**：
```php
createBottle($userId, $content, $isAnonymous, $bottleType, $audioFile, $audioDuration)
pickBottle($userId)
commentBottle($userId, $bottleId, $content, $parentId, $replyToUserId, $throwBack)
likeBottle($userId, $bottleId)
getUserBottles($userId, $page, $pageSize)
getUserPickedBottles($userId, $page, $pageSize)
getUserDailyLimits($userId)
updateUserDailyLimit($userId, $type, $increment)
```

### 4. 安全模块 ([security.php](file:///workspace/includes/security.php))

**职责**：
- 提供安全中间件
- XSS防护
- CSRF防护
- 输入验证
- 内容安全策略(CSP)

**核心类**：`Security`

**核心方法**：
```php
Security::init()                          // 初始化安全设置
Security::generateCsrfToken()             // 生成CSRF令牌
Security::validateCsrfToken()             // 验证CSRF令牌
Security::validateInput($data, $rules)    // 验证输入
Security::purifyHtml($html)               // 净化HTML
Security::detectMaliciousInput($input)    // 检测恶意输入
Security::outputJson($data)                // 安全输出JSON
```

### 5. 管理员模块 ([admin.php](file:///workspace/includes/admin.php))

**职责**：
- 管理员认证
- 权限管理
- 后台操作日志

**核心类**：`Admin`

**核心方法**：
```php
$admin->login($username, $password)       // 管理员登录
$admin->logout()                           // 管理员登出
$admin->isLoggedIn()                       // 检查是否登录
$admin->hasPermission($permission)         // 检查权限
```

### 6. API入口模块 ([api.php](file:///workspace/api.php))

**职责**：
- 统一处理所有API请求
- 路由分发
- 请求参数验证
- 响应格式化

**核心流程**：
1. 初始化安全中间件
2. 解析请求端点
3. 验证输入参数
4. 调用对应功能函数
5. 返回JSON响应

---

## 关键类与函数

### 1. 配置相关函数

#### `getDbConnection()`
**位置**：[includes/config.php](file:///workspace/includes/config.php#L35-L56)

**功能**：创建并返回MySQL数据库连接

**返回值**：mysqli连接对象

**说明**：使用配置文件中的DB_HOST、DB_USER、DB_PASS、DB_NAME常量建立连接，设置字符集为utf8mb4。

---

#### `loadAllSettings($forceReload = false)`
**位置**：[includes/config.php](file:///workspace/includes/config.php#L59-L94)

**功能**：批量加载所有系统设置到静态缓存

**参数**：
- `$forceReload`：是否强制重新加载

**返回值**：设置数组

**说明**：使用静态变量缓存，避免重复查询数据库，提升性能。

---

### 2. 用户相关函数

#### `registerUser($username, $password, $gender)`
**位置**：[includes/user.php](file:///workspace/includes/user.php#L5-L40)

**功能**：用户注册

**参数**：
- `$username`：用户名
- `$password`：密码
- `$gender`：性别（'男'或'女'）

**返回值**：
```php
[
    'success' => true/false,
    'user_id' => 用户ID (成功时),
    'message' => 提示信息
]
```

---

#### `loginUser($username, $password)`
**位置**：[includes/user.php](file:///workspace/includes/user.php#L43-L110)

**功能**：用户登录

**参数**：
- `$username`：用户名
- `$password`：密码

**返回值**：
```php
[
    'success' => true/false,
    'user' => 用户信息数组 (成功时),
    'message' => 提示信息
]
```

**说明**：
- 验证账号状态（是否被禁用）
- 检查VIP是否过期并自动更新状态
- 更新最后登录IP
- 设置会话变量

---

#### `userCheckin($userId)`
**位置**：[includes/user.php](file:///workspace/includes/user.php#L113-L...)

**功能**：用户签到

**参数**：
- `$userId`：用户ID

**返回值**：签到结果数组

**说明**：
- 检查今日是否已签到
- 计算连续签到天数
- 发放签到积分
- 记录签到历史

---

### 3. 漂流瓶相关函数

#### `createBottle($userId, $content, $isAnonymous, $bottleType, $audioFile, $audioDuration)`
**功能**：创建漂流瓶

**参数**：
- `$userId`：用户ID
- `$content`：漂流瓶内容（文字或描述）
- `$isAnonymous`：是否匿名
- `$bottleType`：类型（'text'或'voice'）
- `$audioFile`：语音文件路径（语音类型时）
- `$audioDuration`：语音时长（秒）

**返回值**：创建结果数组

---

#### `pickBottle($userId)`
**功能**：随机捡取漂流瓶

**参数**：
- `$userId`：用户ID

**返回值**：
```php
[
    'success' => true/false,
    'bottle' => 漂流瓶信息 (成功时),
    'message' => 提示信息
]
```

**说明**：
- 过滤用户已捡过的瓶子
- 过滤用户自己的瓶子
- 过滤已删除或隐藏的瓶子
- 记录捡瓶历史

---

#### `commentBottle($userId, $bottleId, $content, $parentId, $replyToUserId, $throwBack)`
**功能**：评论漂流瓶

**参数**：
- `$userId`：用户ID
- `$bottleId`：漂流瓶ID
- `$content`：评论内容
- `$parentId`：父评论ID（回复时使用）
- `$replyToUserId`：被回复用户ID
- `$throwBack`：是否丢回大海

**返回值**：评论结果数组

---

### 4. Security类

#### `Security::init()`
**位置**：[includes/security.php](file:///workspace/includes/security.php#L12-L26)

**功能**：初始化安全设置

**说明**：
- 设置内容安全策略(CSP)
- 设置安全相关HTTP头
- 防止会话固定攻击
- 验证CSRF令牌（POST请求）

---

#### `Security::generateCsrfToken()`
**位置**：[includes/security.php](file:///workspace/includes/security.php#L95-L100)

**功能**：生成CSRF令牌

**返回值**：CSRF令牌字符串

---

#### `Security::validateInput($data, $rules)`
**功能**：验证输入数据

**参数**：
- `$data`：输入数据数组
- `$rules`：验证规则数组

**返回值**：
```php
[
    'valid' => true/false,
    'data' => 验证后的数据,
    'errors' => 错误信息数组
]
```

**支持的验证规则**：
- `required`：是否必填
- `filter`：数据类型过滤
- `min_length`：最小长度
- `max_length`：最大长度
- `pattern`：正则表达式
- `message`：错误提示

---

### 5. Admin类

#### `$admin->login($username, $password)`
**位置**：[includes/admin.php](file:///workspace/includes/admin.php#L67-L...)

**功能**：管理员登录

**参数**：
- `$username`：用户名
- `$password`：密码

**返回值**：登录结果数组

---

## 数据库结构

### 主要数据表

| 表名                 | 描述             |
| :------------------- | :--------------- |
| `users`              | 用户信息         |
| `bottles`            | 漂流瓶内容       |
| `comments`           | 评论数据（支持二级回复） |
| `likes`              | 点赞记录         |
| `pick_records`       | 捡瓶记录         |
| `checkins`           | 签到记录         |
| `points_history`     | 积分历史         |
| `experience_history` | 经验值历史       |
| `messages`           | 消息中心         |
| `reports`            | 举报记录         |
| `recharge_orders`    | 充值订单         |
| `admins`             | 管理员账号       |
| `admin_roles`        | 管理员角色       |
| `admin_login_logs`   | 管理员登录日志   |
| `admin_operation_logs` | 管理员操作日志 |
| `daily_limits`       | 用户每日限制     |
| `announcements`      | 系统公告         |
| `system_settings`    | 系统配置         |

### 核心表结构详解

#### users表（用户表）
| 字段名             | 类型          | 说明                     |
| :----------------- | :------------ | :---------------------- |
| `id`               | INT(11)       | 主键，自增               |
| `username`         | VARCHAR(50)   | 用户名，唯一             |
| `password`         | VARCHAR(255)  | 密码（加密存储）         |
| `gender`           | ENUM          | 性别（男/女）            |
| `signature`        | TEXT          | 个性签名                 |
| `points`           | INT(11)       | 积分                     |
| `experience`       | INT(11)       | 经验值                   |
| `level`            | INT(11)       | 用户等级                 |
| `is_vip`           | TINYINT(1)    | 是否VIP                  |
| `vip_expire_date`  | DATE          | VIP过期日期              |
| `vip_level`        | INT(11)       | VIP等级                  |
| `status`           | TINYINT(1)    | 账号状态（0禁用/1启用）  |
| `register_ip`      | VARCHAR(50)   | 注册IP地址               |
| `last_login_ip`    | VARCHAR(50)   | 上次登录IP地址           |
| `created_at`       | TIMESTAMP     | 创建时间                 |
| `updated_at`       | TIMESTAMP     | 更新时间                 |

#### bottles表（漂流瓶表）
| 字段名             | 类型          | 说明                     |
| :----------------- | :------------ | :---------------------- |
| `id`               | INT(11)       | 主键，自增               |
| `user_id`          | INT(11)       | 用户ID                   |
| `content`          | TEXT          | 内容                     |
| `bottle_type`      | ENUM          | 类型（text/voice）       |
| `audio_file`       | VARCHAR(255)  | 语音文件路径             |
| `audio_duration`   | INT(11)       | 语音时长（秒）           |
| `is_anonymous`     | TINYINT(1)    | 是否匿名                 |
| `status`           | VARCHAR(20)   | 状态（drifting/picked）  |
| `throw_time`       | TIMESTAMP     | 扔出时间                 |
| `ip_address`       | VARCHAR(50)   | IP地址                   |
| `is_hidden`        | TINYINT(1)    | 是否隐藏                 |
| `is_deleted`       | TINYINT(1)    | 是否删除（软删除）       |

#### comments表（评论表）
| 字段名             | 类型          | 说明                     |
| :----------------- | :------------ | :---------------------- |
| `id`               | INT(11)       | 主键，自增               |
| `bottle_id`        | INT(11)       | 漂流瓶ID                 |
| `user_id`          | INT(11)       | 用户ID                   |
| `content`          | TEXT          | 评论内容                 |
| `parent_id`        | INT(11)       | 父评论ID（回复时）       |
| `reply_to_user_id` | INT(11)       | 被回复用户ID             |
| `ip_address`       | VARCHAR(50)   | IP地址                   |
| `created_at`       | TIMESTAMP     | 创建时间                 |
| `is_hidden`        | TINYINT(1)    | 是否隐藏                 |
| `is_deleted`       | TINYINT(1)    | 是否删除（软删除）       |

#### system_settings表（系统配置表）
| 字段名             | 类型          | 说明                     |
| :----------------- | :------------ | :---------------------- |
| `id`               | INT(11)       | 主键，自增               |
| `setting_key`      | VARCHAR(100)  | 配置键，唯一             |
| `setting_value`    | TEXT          | 配置值                   |
| `setting_group`    | VARCHAR(50)   | 配置分组                 |
| `description`      | VARCHAR(255)  | 配置描述                 |
| `created_at`       | TIMESTAMP     | 创建时间                 |
| `updated_at`       | TIMESTAMP     | 更新时间                 |

---

## API接口文档

### 通用说明

**请求格式**：
```
GET /api.php?action=endpoint_name&param1=value1&...
POST /api.php?action=endpoint_name (with POST data)
```

**响应格式**：
```json
{
    "success": true/false,
    "message": "提示信息",
    "data": {...}
}
```

### 用户认证接口

#### `register` - 用户注册
**方法**：POST

**参数**：
```json
{
    "username": "用户名",
    "password": "密码",
    "gender": "男/女"
}
```

**响应**：
```json
{
    "success": true,
    "user_id": 123
}
```

---

#### `login` - 用户登录
**方法**：POST

**参数**：
```json
{
    "username": "用户名",
    "password": "密码"
}
```

**响应**：
```json
{
    "success": true,
    "user": {
        "id": 123,
        "username": "用户名",
        "gender": "男",
        "points": 100,
        "experience": 500,
        "level": 3,
        "is_vip": 1
    }
}
```

---

#### `logout` - 用户登出
**方法**：POST/GET

**响应**：
```json
{
    "success": true
}
```

---

#### `check_auth` - 检查登录状态
**方法**：GET

**响应**：
```json
{
    "success": true,
    "loggedIn": true,
    "user_id": 123,
    "username": "用户名",
    "gender": "男",
    "points": 100,
    "experience": 500,
    "level": 3,
    "is_vip": 1,
    "daily_limits": {...},
    "unread_messages": 5
}
```

---

### 漂流瓶接口

#### `create_bottle` - 创建漂流瓶
**方法**：POST

**参数**：
```json
{
    "content": "漂流瓶内容",
    "is_anonymous": false,
    "bottle_type": "text",
    "audio_file": "语音文件路径（voice类型时）",
    "audio_duration": 10
}
```

**响应**：
```json
{
    "success": true,
    "bottle_id": 123
}
```

---

#### `upload_audio` - 上传语音文件
**方法**：POST

**参数**：FormData（包含audio文件）

**响应**：
```json
{
    "success": true,
    "file_path": "uploads/audio/voice_xxx.webm",
    "duration": 10
}
```

---

#### `pick_bottle` - 捡漂流瓶
**方法**：GET/POST

**响应**：
```json
{
    "success": true,
    "bottle": {
        "id": 123,
        "content": "内容",
        "bottle_type": "text",
        "user_gender": "男",
        "is_anonymous": false,
        "throw_time": "2024-01-01 00:00:00",
        "likes_count": 5,
        "comments_count": 3
    }
}
```

---

#### `comment_bottle` - 评论漂流瓶
**方法**：POST

**参数**：
```json
{
    "bottle_id": 123,
    "content": "评论内容",
    "parent_id": null,
    "reply_to_user_id": null,
    "throw_back": true
}
```

**响应**：
```json
{
    "success": true,
    "comment_id": 456
}
```

---

#### `like_bottle` - 点赞漂流瓶
**方法**：POST

**参数**：
```json
{
    "bottle_id": 123
}
```

**响应**：
```json
{
    "success": true,
    "liked": true/false
}
```

---

#### `user_bottles` - 获取用户扔出的瓶子
**方法**：GET

**参数**：
- `page`: 页码（默认1）
- `page_size`: 每页数量（默认10）

**响应**：
```json
{
    "success": true,
    "bottles": [...],
    "total": 100,
    "page": 1,
    "page_size": 10
}
```

---

#### `user_picked_bottles` - 获取用户捡到的瓶子
**方法**：GET

**参数**：
- `page`: 页码（默认1）
- `page_size`: 每页数量（默认10）

**响应**：同 `user_bottles`

---

### 系统接口

#### `get_announcements` - 获取系统公告
**方法**：GET

**响应**：
```json
{
    "success": true,
    "announcements": [...]
}
```

---

#### `get_basic_settings` - 获取系统基本配置
**方法**：GET

**响应**：
```json
{
    "success": true,
    "settings": {
        "site_name": "漂流瓶",
        "site_url": "http://localhost",
        ...
    }
}
```

---

#### `get_experience_config` - 获取经验值规则配置
**方法**：GET

**响应**：
```json
{
    "success": true,
    "config": {
        "exp_per_bottle": 10,
        "exp_per_pick": 5,
        "exp_per_comment": 3
    }
}
```

---

#### `get_user_level_info` - 获取用户等级信息
**方法**：GET

**响应**：
```json
{
    "success": true,
    "level": 3,
    "experience": 500,
    "next_level_exp": 900,
    "progress": 55.5
}
```

---

#### `get_payment_config` - 获取支付配置
**方法**：GET

**响应**：
```json
{
    "success": true,
    "points_ratio": 100,
    "payment_methods": ["alipay", "wxpay"],
    "default_method": "alipay"
}
```

---

#### `create_recharge_order` - 创建充值订单
**方法**：POST

**参数**：
```json
{
    "amount": 10,
    "payment_method": "alipay"
}
```

**响应**：
```json
{
    "success": true,
    "order_id": "ORD123456",
    "pay_url": "支付链接"
}
```

---

#### `get_recharge_orders` - 获取用户充值订单
**方法**：GET

**参数**：
- `page`: 页码
- `page_size`: 每页数量
- `status`: 订单状态（可选）

**响应**：
```json
{
    "success": true,
    "orders": [...],
    "total": 50
}
```

---

## 安全机制

### 1. 输入验证与过滤
- **sanitizeInput()**：对输入进行HTML转义，防止XSS
- **sanitizeHtml()**：允许基本HTML标签但过滤危险内容
- **sanitizeUrl()**：过滤URL，只允许http/https协议
- **sanitizeSql()**：使用mysqli real_escape_string防止SQL注入
- **参数化查询**：所有数据库查询使用prepare+bind_param

### 2. XSS防护
- 内容安全策略(CSP)：限制资源加载来源
- 输出转义：所有用户输入在输出到HTML前进行转义
- HTML净化：使用purifyHtml()方法过滤危险标签和属性

### 3. CSRF防护
- CSRF令牌生成：Security::generateCsrfToken()
- CSRF令牌验证：Security::validateCsrfToken()
- 支持多种令牌传递方式：HTTP头、POST数据、JSON数据

### 4. 会话安全
- 会话固定攻击防护：每30分钟重新生成会话ID
- 会话超时：配置SESSION_LIFETIME
- 安全HTTP头设置：
  - X-Frame-Options: SAMEORIGIN（防止点击劫持）
  - X-XSS-Protection: 1; mode=block
  - X-Content-Type-Options: nosniff
  - Strict-Transport-Security（HTTPS时）

### 5. 密码安全
- 使用password_hash()加密密码（PASSWORD_DEFAULT）
- 使用password_verify()验证密码

### 6. 恶意输入检测
- Security::detectMaliciousInput()检测常见攻击模式
- 记录攻击尝试到日志

---

## 部署与运行

### 环境要求
- **PHP**: 7.3 或更高版本 (推荐 7.4 / 8.0)
- **MySQL**: 5.6 或更高版本 / MariaDB 10.3+
- **Web服务器**: Apache 2.4+ / Nginx 1.18+
- **PHP扩展**: `mysqli`, `mbstring`, `json`

### 部署步骤

1. **获取代码**
   ```bash
   git clone https://github.com/kggzs/Driftbottle.git /path/to/web/driftbottle
   ```

2. **配置Web服务器**
   - 将网站根目录指向 `/path/to/web/driftbottle`
   - 配置URL重写规则（可选）

3. **数据库设置**
   ```sql
   CREATE DATABASE driftbottle CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'driftbottle_user'@'localhost' IDENTIFIED BY 'your_secure_password';
   GRANT ALL PRIVILEGES ON driftbottle.* TO 'driftbottle_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

   导入数据库结构：
   ```bash
   mysql -u driftbottle_user -p driftbottle < driftbottle.sql
   ```

4. **配置数据库连接**
   编辑 [includes/config.php](file:///workspace/includes/config.php)：
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'driftbottle_user');
   define('DB_PASS', 'your_secure_password');
   define('DB_NAME', 'driftbottle');
   ```

5. **设置目录权限**
   ```bash
   chmod -R 755 /path/to/web/driftbottle
   chmod -R 777 /path/to/web/driftbottle/uploads/audio
   chmod -R 777 /path/to/web/driftbottle/logs
   ```

6. **访问测试**
   - 默认管理员账号: `admin` / `admin`
   - 生产环境请务必删除 `admin/test_admin.php` 和 `admin/reset_password.php`

### 配置说明

#### 系统设置
系统设置存储在 `system_settings` 表中，可通过后台管理系统进行配置：
- 基本设置：网站名称、URL、管理员邮箱
- 积分规则：签到积分、点赞积分等
- 用户限制：每日扔瓶/捡瓶次数
- VIP设置：VIP开通积分
- 经验值规则：各项操作获得的经验值
- 支付配置：支付方式、积分比例、密钥等

#### 调试模式
编辑 [includes/config.php](file:///workspace/includes/config.php#L3)：
```php
define('DEBUG_MODE', true); // 开启调试模式
```

### 常见问题

#### 页面显示空白或500错误
- 检查 PHP 错误日志 (`logs/php_errors.log`)
- 确认 PHP 版本和所需扩展已安装
- 检查文件和目录权限

#### 无法连接数据库
- 核对 [includes/config.php](file:///workspace/includes/config.php#L24-L27) 中的数据库连接信息
- 确保 MySQL 服务正在运行
- 检查数据库用户权限

#### 语音功能无法使用
- 确保 `uploads/audio/` 目录存在且有写入权限
- 语音录制功能需要 HTTPS 环境
- 检查浏览器是否支持 MediaRecorder API

#### 支付成功后积分没有到账
- 确认 `payment_notify.php` 可以正常访问
- 检查服务器日志
- 确认支付配置是否正确

---

## 更新日志

详细更新日志请参考 [CHANGELOG.md](file:///workspace/CHANGELOG.md)

### 主要版本

- **v1.5.0** (2026-01-28): 新增在线充值系统
- **v1.4.0** (2024-12-24): 新增举报系统和软删除功能
- **v1.3.0** (2024-12-20): 新增IP地址追踪和评论回复功能
- **v1.2.0** (2024-12-20): 新增用户等级系统
- **v1.1.0** (2024-12-13): 新增语音漂流瓶功能
- **v1.0.0**: 初始版本

---

## 许可证

本项目基于 **MIT 许可证** 开源。详情请见 [LICENSE](file:///workspace/LICENSE) 文件。

---

## 联系与支持

- QQ: 1724464998
- GitHub Issues: https://github.com/kggzs/Driftbottle/issues
