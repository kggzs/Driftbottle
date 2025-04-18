<?php
require_once '../includes/config.php';
require_once '../includes/admin.php';

// 初始化Admin类
$admin = new Admin();

// 执行登出操作
$admin->logout();

// 重定向到登录页
header("Location: login.php");
exit;
?> 