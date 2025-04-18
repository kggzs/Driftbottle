<?php
require_once '../includes/config.php';
require_once '../includes/admin.php';

// 初始化Admin类
$admin = new Admin();

// 如果已登录，跳转到首页
if ($admin->isLoggedIn()) {
    header("Location: index.php");
    exit;
}

// 处理登录请求
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = sanitizeInput($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = '用户名和密码不能为空';
    } else {
        $result = $admin->login($username, $password);
        if ($result['success']) {
            header("Location: index.php");
            exit;
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员登录 - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f5f5f5;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .login-form {
            width: 100%;
            max-width: 400px;
            padding: 30px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .login-form h1 {
            font-size: 24px;
            text-align: center;
            margin-bottom: 20px;
        }
        .login-form .form-floating {
            margin-bottom: 15px;
        }
        .login-form .btn-primary {
            width: 100%;
        }
    </style>
</head>
<body>
    <div class="login-form">
        <h1><?php echo SITE_NAME; ?>后台管理</h1>
        <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        <form method="post">
            <div class="form-floating mb-3">
                <input type="text" class="form-control" id="username" name="username" placeholder="用户名" required>
                <label for="username">用户名</label>
            </div>
            <div class="form-floating mb-3">
                <input type="password" class="form-control" id="password" name="password" placeholder="密码" required>
                <label for="password">密码</label>
            </div>
            <button type="submit" class="btn btn-primary">登录</button>
        </form>
        <div class="text-center mt-3">
            <a href="../index.html" class="text-decoration-none">返回前台首页</a>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 