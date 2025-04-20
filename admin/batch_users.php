<?php
/**
 * 批量处理用户
 */
require_once '../includes/config.php';
require_once '../includes/admin.php';

// 初始化管理员
$admin = new Admin();

// 检查是否已登录
if (!$admin->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// 初始化数据库连接
$conn = getDbConnection();

// 检查表单提交
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action']) || !isset($_POST['user_ids']) || !is_array($_POST['user_ids'])) {
    header("Location: users.php");
    exit;
}

$action = sanitizeInput($_POST['action']);
$userIds = array_map('intval', $_POST['user_ids']);

// 检查是否有选择用户
if (empty($userIds)) {
    header("Location: users.php?message=" . urlencode('请选择至少一个用户') . "&type=warning");
    exit;
}

// 执行相应的操作
$message = '';
$messageType = 'info';

switch ($action) {
    // 设为VIP
    case 'vip':
        try {
            // 设置VIP到期日期为一年后
            $vipExpireDate = date('Y-m-d', strtotime('+1 year'));
            
            // 构建问号占位符
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            
            // 准备更新语句
            $sql = "UPDATE users SET is_vip = 1, vip_level = 1, vip_expire_date = ? WHERE id IN ($placeholders)";
            
            // 准备语句
            $stmt = $conn->prepare($sql);
            
            // 合并参数：第一个是到期日期，后面是用户ID
            $params = array_merge([$vipExpireDate], $userIds);
            
            // 绑定参数类型：第一个是字符串(s)，剩下的都是整数(i)
            $types = 's' . str_repeat('i', count($userIds));
            
            // 绑定参数值
            $stmt->bind_param($types, ...$params);
            
            // 执行更新
            if ($stmt->execute()) {
                $count = $stmt->affected_rows;
                $message = "成功将 $count 个用户设置为VIP！";
                $messageType = 'success';
                
                // 记录操作日志
                $admin->logOperation('用户', '批量设置VIP', "设置用户IDs: " . implode(', ', $userIds));
            } else {
                $message = "操作失败: " . $conn->error;
                $messageType = 'danger';
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $message = "操作异常: " . $e->getMessage();
            $messageType = 'danger';
        }
        break;
        
    // 取消VIP
    case 'cancel-vip':
        try {
            // 构建问号占位符
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $sql = "UPDATE users SET is_vip = 0, vip_expire_date = NULL WHERE id IN ($placeholders)";
            
            // 准备语句
            $stmt = $conn->prepare($sql);
            
            // 绑定参数类型
            $types = str_repeat('i', count($userIds));
            
            // 绑定参数值
            $stmt->bind_param($types, ...$userIds);
            
            // 执行更新
            if ($stmt->execute()) {
                $count = $stmt->affected_rows;
                $message = "成功取消 $count 个用户的VIP权限！";
                $messageType = 'success';
                
                // 记录操作日志
                $admin->logOperation('用户', '批量取消VIP', "取消用户IDs: " . implode(', ', $userIds));
            } else {
                $message = "操作失败: " . $conn->error;
                $messageType = 'danger';
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $message = "操作异常: " . $e->getMessage();
            $messageType = 'danger';
        }
        break;
        
    // 删除用户
    case 'delete':
        try {
            // 构建问号占位符
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            
            // 准备删除语句
            $sql = "DELETE FROM users WHERE id IN ($placeholders)";
            
            // 准备语句
            $stmt = $conn->prepare($sql);
            
            // 绑定参数类型
            $types = str_repeat('i', count($userIds));
            
            // 绑定参数值
            $stmt->bind_param($types, ...$userIds);
            
            // 执行删除
            if ($stmt->execute()) {
                $count = $stmt->affected_rows;
                $message = "成功删除 $count 个用户！";
                $messageType = 'success';
                
                // 记录操作日志
                $admin->logOperation('用户', '批量删除', "删除用户IDs: " . implode(', ', $userIds));
            } else {
                $message = "删除失败: " . $conn->error;
                $messageType = 'danger';
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $message = "操作异常: " . $e->getMessage();
            $messageType = 'danger';
        }
        break;
        
    // 启用账号
    case 'enable':
        try {
            // 构建问号占位符
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $sql = "UPDATE users SET status = 1 WHERE id IN ($placeholders)";
            
            // 准备语句
            $stmt = $conn->prepare($sql);
            
            // 绑定参数类型
            $types = str_repeat('i', count($userIds));
            
            // 绑定参数值
            $stmt->bind_param($types, ...$userIds);
            
            // 执行更新
            if ($stmt->execute()) {
                $count = $stmt->affected_rows;
                $message = "成功启用 $count 个用户账号！";
                $messageType = 'success';
                
                // 记录操作日志
                $admin->logOperation('用户', '批量启用账号', "启用用户IDs: " . implode(', ', $userIds));
            } else {
                $message = "操作失败: " . $conn->error;
                $messageType = 'danger';
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $message = "操作异常: " . $e->getMessage();
            $messageType = 'danger';
        }
        break;
        
    // 禁用账号
    case 'disable':
        try {
            // 构建问号占位符
            $placeholders = implode(',', array_fill(0, count($userIds), '?'));
            $sql = "UPDATE users SET status = 0 WHERE id IN ($placeholders)";
            
            // 准备语句
            $stmt = $conn->prepare($sql);
            
            // 绑定参数类型
            $types = str_repeat('i', count($userIds));
            
            // 绑定参数值
            $stmt->bind_param($types, ...$userIds);
            
            // 执行更新
            if ($stmt->execute()) {
                $count = $stmt->affected_rows;
                $message = "成功禁用 $count 个用户账号！";
                $messageType = 'success';
                
                // 记录操作日志
                $admin->logOperation('用户', '批量禁用账号', "禁用用户IDs: " . implode(', ', $userIds));
            } else {
                $message = "操作失败: " . $conn->error;
                $messageType = 'danger';
            }
            
            $stmt->close();
        } catch (Exception $e) {
            $message = "操作异常: " . $e->getMessage();
            $messageType = 'danger';
        }
        break;
        
    default:
        $message = "未知操作";
        $messageType = 'warning';
        break;
}

// 关闭数据库连接
$conn->close();

// 重定向回用户列表页面
header("Location: users.php?message=" . urlencode($message) . "&type=$messageType");
exit; 