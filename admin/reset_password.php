<?php
// 管理员密码重置工具 - 用完后请立即删除此文件
require_once '../includes/config.php';

// 定义一个安全密钥，用于确认授权
$securityKey = md5(time()); // 每次访问生成新密钥

// 处理表单提交
$message = '';
$showForm = true;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 验证安全密钥
    if (!isset($_POST['security_key']) || $_POST['security_key'] !== $_SESSION['security_key']) {
        $message = '<div class="error">安全校验失败！</div>';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        
        if (empty($username) || empty($newPassword)) {
            $message = '<div class="error">用户名和新密码不能为空！</div>';
        } else {
            try {
                $conn = getDbConnection();
                
                // 检查用户是否存在
                $checkStmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
                $checkStmt->bind_param("s", $username);
                $checkStmt->execute();
                $result = $checkStmt->get_result();
                
                if ($result->num_rows === 0) {
                    $message = '<div class="error">用户名不存在！</div>';
                } else {
                    $userId = $result->fetch_assoc()['id'];
                    
                    // 更新密码
                    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                    $updateStmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
                    $updateStmt->bind_param("si", $hashedPassword, $userId);
                    
                    if ($updateStmt->execute()) {
                        $message = '<div class="success">密码重置成功！新密码已设置为: ' . htmlspecialchars($newPassword) . '</div>';
                        $showForm = false;
                    } else {
                        $message = '<div class="error">密码重置失败: ' . $conn->error . '</div>';
                    }
                    
                    $updateStmt->close();
                }
                
                $checkStmt->close();
                $conn->close();
                
            } catch (Exception $e) {
                $message = '<div class="error">操作失败: ' . $e->getMessage() . '</div>';
            }
        }
    }
}

// 存储安全密钥到会话
$_SESSION['security_key'] = $securityKey;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员密码重置工具</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 500px;
            margin: 30px auto;
            padding: 20px;
            background-color: #f5f5f5;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        .warning {
            background-color: #ffeeee;
            border-left: 4px solid #ff0000;
            padding: 10px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 3px;
            box-sizing: border-box;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 16px;
        }
        button:hover {
            background-color: #45a049;
        }
        .success {
            color: green;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .error {
            color: red;
            font-weight: bold;
            margin-bottom: 15px;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>
<body>
    <h1>管理员密码重置工具</h1>
    
    <div class="warning">
        <strong>警告：</strong> 此工具用于重置管理员密码。使用完成后，请立即删除此文件以确保系统安全。
    </div>
    
    <?php echo $message; ?>
    
    <?php if ($showForm): ?>
    <form method="post">
        <input type="hidden" name="security_key" value="<?php echo $securityKey; ?>">
        
        <div class="form-group">
            <label for="username">管理员用户名</label>
            <input type="text" id="username" name="username" value="admin" required>
        </div>
        
        <div class="form-group">
            <label for="new_password">新密码</label>
            <input type="password" id="new_password" name="new_password" value="admin" required>
        </div>
        
        <button type="submit">重置密码</button>
    </form>
    <?php else: ?>
    <p>
        <a href="login.php">点击此处返回登录页面</a>
    </p>
    <?php endif; ?>
    
    <div class="footer">
        提示：密码重置成功后，请立即删除此文件！
    </div>
</body>
</html> 