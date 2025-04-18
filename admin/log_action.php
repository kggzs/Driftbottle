<?php
// 日志管理操作处理

// 引入配置文件
require_once '../includes/config.php';
require_once '../includes/admin.php';

// 初始化管理员
$admin = new Admin();

// 检查是否已登录
if (!$admin->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// 检查请求方法
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: logs.php");
    exit;
}

// 初始化数据库连接
$conn = getDbConnection();

// 获取操作类型
$action = isset($_POST['action']) ? sanitizeInput($_POST['action']) : '';

// 初始化消息
$message = '';
$messageType = 'info';

// 根据操作类型执行相应的操作
switch ($action) {
    case 'clear_error_logs':
        // 清理指定天数之前的错误日志
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        
        try {
            // 检查表是否存在
            $checkTableQuery = "SHOW TABLES LIKE 'system_error_logs'";
            $tableCheck = $conn->query($checkTableQuery);
            
            if ($tableCheck && $tableCheck->num_rows > 0) {
                // 构建删除查询
                $deleteQuery = "DELETE FROM system_error_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
                $stmt = $conn->prepare($deleteQuery);
                
                if ($stmt === false) {
                    throw new Exception("准备删除查询失败: " . $conn->error);
                }
                
                $stmt->bind_param("i", $days);
                
                if (!$stmt->execute()) {
                    throw new Exception("执行删除查询失败: " . $stmt->error);
                }
                
                $deletedCount = $stmt->affected_rows;
                $stmt->close();
                
                // 记录管理员操作
                $admin->logOperation('系统', '清理', "清理了{$days}天前的{$deletedCount}条错误日志");
                
                $message = "成功清理了 {$deletedCount} 条 {$days} 天前的错误日志";
                $messageType = 'success';
            } else {
                $message = "错误日志表不存在";
                $messageType = 'warning';
            }
        } catch (Exception $e) {
            error_log("清理错误日志失败: " . $e->getMessage());
            $message = "清理错误日志失败: " . $e->getMessage();
            $messageType = 'danger';
        }
        break;
        
    case 'clear_login_logs':
        // 清理指定天数之前的登录日志
        $days = isset($_POST['days']) ? intval($_POST['days']) : 30;
        
        try {
            // 检查表是否存在
            $checkTableQuery = "SHOW TABLES LIKE 'admin_login_logs'";
            $tableCheck = $conn->query($checkTableQuery);
            
            if ($tableCheck && $tableCheck->num_rows > 0) {
                // 构建删除查询
                $deleteQuery = "DELETE FROM admin_login_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
                $stmt = $conn->prepare($deleteQuery);
                
                if ($stmt === false) {
                    throw new Exception("准备删除查询失败: " . $conn->error);
                }
                
                $stmt->bind_param("i", $days);
                
                if (!$stmt->execute()) {
                    throw new Exception("执行删除查询失败: " . $stmt->error);
                }
                
                $deletedCount = $stmt->affected_rows;
                $stmt->close();
                
                // 记录管理员操作
                $admin->logOperation('系统', '清理', "清理了{$days}天前的{$deletedCount}条登录日志");
                
                $message = "成功清理了 {$deletedCount} 条 {$days} 天前的登录日志";
                $messageType = 'success';
            } else {
                $message = "登录日志表不存在";
                $messageType = 'warning';
            }
        } catch (Exception $e) {
            error_log("清理登录日志失败: " . $e->getMessage());
            $message = "清理登录日志失败: " . $e->getMessage();
            $messageType = 'danger';
        }
        break;
        
    case 'clear_operation_logs':
        // 清理指定天数之前的操作日志
        $days = isset($_POST['days']) ? intval($_POST['days']) : 90;
        
        try {
            // 构建删除查询
            $deleteQuery = "DELETE FROM admin_operation_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $stmt = $conn->prepare($deleteQuery);
            
            if ($stmt === false) {
                throw new Exception("准备删除查询失败: " . $conn->error);
            }
            
            $stmt->bind_param("i", $days);
            
            if (!$stmt->execute()) {
                throw new Exception("执行删除查询失败: " . $stmt->error);
            }
            
            $deletedCount = $stmt->affected_rows;
            $stmt->close();
            
            // 记录管理员操作
            $admin->logOperation('系统', '清理', "清理了{$days}天前的{$deletedCount}条操作日志");
            
            $message = "成功清理了 {$deletedCount} 条 {$days} 天前的操作日志";
            $messageType = 'success';
        } catch (Exception $e) {
            error_log("清理操作日志失败: " . $e->getMessage());
            $message = "清理操作日志失败: " . $e->getMessage();
            $messageType = 'danger';
        }
        break;
        
    case 'export_logs':
        // 导出日志功能（可以在这里实现导出为CSV或Excel的功能）
        $logType = isset($_POST['log_type']) ? sanitizeInput($_POST['log_type']) : 'operation';
        
        // 这里可以添加导出日志的代码
        // ...
        
        $message = "导出日志功能暂未实现";
        $messageType = 'warning';
        break;
        
    default:
        $message = "未知操作";
        $messageType = 'warning';
}

// 关闭数据库连接
$conn->close();

// 重定向回日志页面
$redirectUrl = "logs.php";
if (!empty($_POST['log_type'])) {
    $redirectUrl .= "?type=" . urlencode($_POST['log_type']);
}
$redirectUrl .= (strpos($redirectUrl, '?') !== false ? '&' : '?') . "message=" . urlencode($message) . "&type=" . urlencode($messageType);

header("Location: $redirectUrl");
exit;
?> 