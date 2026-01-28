<?php
// 引入必要的文件
require_once '../includes/config.php';
require_once '../includes/admin.php';

// 初始化Admin类
$admin = new Admin();

// 检查是否已登录，未登录则跳转到登录页
if (!$admin->isLoggedIn()) {
    header("Location: login.php");
    exit;
}

// 获取当前页面路径，用于导航高亮
$currentPage = basename($_SERVER['PHP_SELF']);

// 设置缓冲区，避免headers already sent错误
ob_start();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo SITE_NAME; ?>后台管理</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.1.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <style>
        body {
            font-family: 'Microsoft YaHei', 'Segoe UI', sans-serif;
            background-color: #f5f5f5;
            overflow-x: hidden;
        }
        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            z-index: 100;
            padding: 48px 0 0;
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
            background-color: #343a40;
            width: 250px;
            transition: all 0.3s;
        }
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: 0.5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }
        .sidebar .nav-link {
            font-weight: 500;
            color: #c2c7d0;
            padding: 0.75rem 1rem;
        }
        .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link:hover {
            color: #fff;
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .navbar-brand {
            padding-top: 0.75rem;
            padding-bottom: 0.75rem;
            font-size: 1rem;
            background-color: rgba(0, 0, 0, 0.25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, 0.25);
        }
        .navbar .navbar-toggler {
            top: 0.25rem;
            right: 1rem;
        }
        .navbar .form-control {
            padding: 0.75rem 1rem;
            border-width: 0;
            border-radius: 0;
        }
        .main-content {
            margin-left: 250px;
            padding: 70px 20px 20px;
            transition: all 0.3s;
            min-height: 100vh;
        }
        @media (max-width: 768px) {
            .sidebar {
                margin-left: -250px;
            }
            .main-content {
                margin-left: 0;
            }
            .sidebar.show {
                margin-left: 0;
            }
            .main-content.shift {
                margin-left: 250px;
            }
        }
        .card {
            margin-bottom: 20px;
            border: none;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .card-header {
            font-weight: bold;
            background-color: #f8f9fa;
        }
        .sb-nav-link-icon {
            display: inline-block;
            width: 20px;
            margin-right: 10px;
        }
        /* 确保表格在移动设备上的响应性 */
        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }
        /* 统一表单元素样式 */
        .form-control, .form-select {
            box-shadow: none !important;
        }
        .form-control:focus, .form-select:focus {
            border-color: #80bdff;
        }
    </style>
</head>
<body>
    <!-- 顶部导航栏 -->
    <nav class="navbar navbar-dark bg-dark fixed-top p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 mr-0 px-3" href="index.php"><?php echo SITE_NAME; ?>后台管理</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" id="sidebarToggle">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="ms-auto pe-3">
            <div class="dropdown">
                <a class="nav-link dropdown-toggle text-white" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-person-circle"></i> <?php echo $admin->getCurrentAdminUsername(); ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                    <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person"></i> 个人资料</a></li>
                    <li><a class="dropdown-item" href="change_password.php"><i class="bi bi-key"></i> 修改密码</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right"></i> 退出登录</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- 侧边栏 -->
    <nav class="sidebar">
        <div class="sidebar-sticky">
            <ul class="nav flex-column">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'index.php' ? 'active' : ''; ?>" href="index.php">
                        <i class="bi bi-speedometer2"></i>
                        控制面板
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'users.php' ? 'active' : ''; ?>" href="users.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-users"></i></div>
                        用户管理
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'bottles.php' ? 'active' : ''; ?>" href="bottles.php">
                        <i class="bi bi-envelope"></i>
                        漂流瓶管理
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'comments.php' ? 'active' : ''; ?>" href="comments.php">
                        <i class="bi bi-chat-dots"></i>
                        评论管理
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                        <i class="fas fa-flag"></i>
                        举报管理
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'announcements.php' ? 'active' : ''; ?>" href="announcements.php">
                        <div class="sb-nav-link-icon"><i class="fas fa-bullhorn"></i></div>
                        公告管理
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'statistics.php' ? 'active' : ''; ?>" href="statistics.php">
                        <i class="bi bi-bar-chart"></i>
                        数据统计
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'recharge_orders.php' ? 'active' : ''; ?>" href="recharge_orders.php">
                        <i class="fas fa-wallet"></i>
                        充值订单
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                        <i class="bi bi-gear"></i>
                        系统设置
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo in_array($currentPage, ['admins.php', 'admin_roles.php']) ? 'active' : ''; ?>" href="admins.php">
                        <i class="bi bi-shield-lock"></i>
                        管理员设置
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentPage == 'logs.php' ? 'active' : ''; ?>" href="logs.php">
                        <i class="bi bi-journal-text"></i>
                        系统日志
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <!-- 主内容区 -->
    <main class="main-content"><?php if (isset($pageTitle) && !isset($hidePageHeader)): ?>
        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h2><?php echo $pageTitle; ?></h2>
            <?php if (isset($pageActions)): ?>
            <div class="btn-toolbar mb-2 mb-md-0">
                <?php echo $pageActions; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
</body>
</html> 