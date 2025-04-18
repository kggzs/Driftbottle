<?php
// 调试模式配置
define('DEBUG_MODE', true); // 设置为 true 开启调试模式，false 关闭调试模式

// 错误报告配置 
if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
    
    // 确保日志目录存在
    if (!file_exists(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0777, true);
    }
} else {
    error_reporting(E_ERROR | E_PARSE);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../logs/php_errors.log');
}

// 数据库配置
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'driftbottle');

// 其他系统配置
define('SITE_NAME', '漂流瓶');
define('SITE_URL', 'http://localhost');
define('ADMIN_EMAIL', 'kggzs@vip.qq.com');

// 安全配置
define('SESSION_LIFETIME', 86400); // 会话有效期（秒）
define('MAX_LOGIN_ATTEMPTS', 5); // 最大登录尝试次数
define('LOGIN_TIMEOUT', 300); // 登录超时时间（秒）

// 功能限制配置
define('MAX_BOTTLE_LENGTH', 500); // 漂流瓶内容最大长度
define('MAX_COMMENT_LENGTH', 200); // 评论最大长度
define('MAX_SIGNATURE_LENGTH', 50); // 个性签名最大长度

// 积分规则配置
define('POINTS_PER_CHECKIN', 10); // 每次签到获得积分
define('POINTS_PER_WEEKLY_CHECKIN', 70); // 连续签到7天额外奖励积分
define('POINTS_PER_VIP_CHECKIN', 20); // VIP会员每次签到额外获得积分
define('POINTS_PER_BOTTLE', 1); // 每次扔漂流瓶获得积分
define('POINTS_PER_LIKE', 1); // 每次收到点赞获得积分

// 每日限制配置
define('DAILY_BOTTLE_LIMIT', 5); // 普通用户每日扔瓶限制
define('DAILY_PICK_LIMIT', 5); // 普通用户每日捡瓶限制
define('VIP_DAILY_BOTTLE_LIMIT', 20); // VIP用户每日扔瓶限制
define('VIP_DAILY_PICK_LIMIT', 20); // VIP用户每日捡瓶限制

// 创建数据库连接
function getDbConnection() {
    try {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        
        // 检查连接
        if ($conn->connect_error) {
            error_log("数据库连接失败: " . $conn->connect_error);
            throw new Exception("数据库连接失败: " . $conn->connect_error);
        }
        
        // 设置字符集
        if (!$conn->set_charset("utf8mb4")) {
            error_log("设置字符集失败: " . $conn->error);
            throw new Exception("设置字符集失败: " . $conn->error);
        }
        
        return $conn;
    } catch (Exception $e) {
        error_log("数据库连接异常: " . $e->getMessage());
        throw $e;
    }
}

// 确保会话已启动
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 检查用户是否已登录
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// 获取当前登录用户ID
function getCurrentUserId() {
    return isLoggedIn() ? $_SESSION['user_id'] : 0;
}

// 安全过滤输入
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?> 