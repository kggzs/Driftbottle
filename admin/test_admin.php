<?php
// 测试脚本 - 仅用于调试和修复管理员账号问题
require_once '../includes/config.php';

// 安全检查 - 仅允许本地访问
$clientIP = $_SERVER['REMOTE_ADDR'];
if ($clientIP !== '127.0.0.1' && $clientIP !== '::1' && $clientIP !== 'localhost') {
    die("出于安全考虑，此脚本仅限本地访问");
}

echo "<h1>管理员账号测试</h1>";

// 连接数据库
try {
    $conn = getDbConnection();
    echo "<p>数据库连接成功！</p>";
} catch (Exception $e) {
    die("<p>数据库连接失败: " . $e->getMessage() . "</p>");
}

// 检查管理员表和记录
$tableExistsQuery = "SHOW TABLES LIKE 'admins'";
$tableExists = $conn->query($tableExistsQuery)->num_rows > 0;

if (!$tableExists) {
    die("<p>管理员表不存在，请先导入数据库结构！</p>");
}

// 查询管理员信息
$adminQuery = "SELECT * FROM admins WHERE username = 'admin'";
$adminResult = $conn->query($adminQuery);

if ($adminResult->num_rows === 0) {
    echo "<p>未找到默认管理员账号，现在创建...</p>";
    
    // 创建默认管理员账号
    $password = 'admin';
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // 检查角色表
    $roleQuery = "SELECT id FROM admin_roles WHERE name = '超级管理员'";
    $roleResult = $conn->query($roleQuery);
    $roleId = 1;
    
    if ($roleResult->num_rows > 0) {
        $roleId = $roleResult->fetch_assoc()['id'];
    } else {
        // 创建角色
        $roleInsertQuery = "INSERT INTO admin_roles (name, description, permissions) VALUES ('超级管理员', '拥有系统所有权限', '{\"all\": true}')";
        $conn->query($roleInsertQuery);
        $roleId = $conn->insert_id;
        echo "<p>创建超级管理员角色，ID: $roleId</p>";
    }
    
    $insertQuery = "INSERT INTO admins (username, password, real_name, role_id, status) VALUES ('admin', ?, '系统管理员', ?, 1)";
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("si", $hashedPassword, $roleId);
    
    if ($stmt->execute()) {
        echo "<p>默认管理员账号创建成功！用户名: admin，密码: admin</p>";
    } else {
        echo "<p>创建失败: " . $conn->error . "</p>";
    }
    
    $stmt->close();
} else {
    $admin = $adminResult->fetch_assoc();
    echo "<p>找到管理员账号：ID: {$admin['id']}, 用户名: {$admin['username']}</p>";
    
    if (isset($_GET['reset']) && $_GET['reset'] === 'true') {
        // 重置管理员密码
        $password = 'admin';
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        $updateQuery = "UPDATE admins SET password = ? WHERE username = 'admin'";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("s", $hashedPassword);
        
        if ($stmt->execute()) {
            echo "<p>管理员密码已重置为: admin</p>";
            echo "<p>新密码哈希值: $hashedPassword</p>";
        } else {
            echo "<p>密码重置失败: " . $conn->error . "</p>";
        }
        
        $stmt->close();
    } else {
        echo "<p>当前密码哈希值: {$admin['password']}</p>";
        
        // 验证密码
        $testPassword = 'admin';
        if (password_verify($testPassword, $admin['password'])) {
            echo "<p class='success'>密码验证成功！'$testPassword' 是正确的密码。</p>";
        } else {
            echo "<p class='error'>密码验证失败！'$testPassword' 不是正确的密码。</p>";
            echo "<p><a href='?reset=true'>点击这里重置管理员密码为 'admin'</a></p>";
        }
    }
}

// 显示PHP环境信息
echo "<h2>PHP信息</h2>";
echo "<p>PHP版本: " . phpversion() . "</p>";
echo "<p>密码哈希算法: " . (defined('PASSWORD_ALGORITHM') ? PASSWORD_ALGORITHM : PASSWORD_DEFAULT) . "</p>";

// 测试密码哈希
echo "<h2>测试密码哈希</h2>";
$testPass = 'admin';
$hash = password_hash($testPass, PASSWORD_DEFAULT);
echo "<p>测试密码 'admin' 的哈希值: $hash</p>";
echo "<p>验证结果: " . (password_verify($testPass, $hash) ? '成功' : '失败') . "</p>";

$conn->close();
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h1, h2 { color: #333; }
    p { margin: 10px 0; line-height: 1.5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    a { color: blue; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>