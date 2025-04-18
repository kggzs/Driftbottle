<?php
// 开启输出缓冲，避免headers already sent错误
ob_start();

// 页面标题
$pageTitle = '个人资料';

// 引入头部
require_once 'includes/header.php';

// 初始化数据库连接
$conn = getDbConnection();

// 记录管理员操作
$admin->logOperation('系统', '查看', '查看个人资料');

// 获取当前管理员信息
$adminData = $admin->getCurrentAdmin();

// 处理表单提交
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        // 更新个人信息
        try {
            $displayName = sanitizeInput($_POST['display_name']);
            $email = sanitizeInput($_POST['email']);
            $phone = sanitizeInput($_POST['phone']);
            $adminId = $admin->getCurrentAdminId();
            
            // 验证邮箱格式
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("邮箱格式不正确");
            }
            
            // 更新数据库
            $stmt = $conn->prepare("UPDATE admins SET display_name = ?, email = ?, phone = ? WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("准备SQL语句失败: " . $conn->error);
            }
            $stmt->bind_param("sssi", $displayName, $email, $phone, $adminId);
            
            if (!$stmt->execute()) {
                throw new Exception("更新个人资料失败: " . $stmt->error);
            }
            
            // 记录操作日志
            $admin->logOperation('系统', '更新', '更新个人资料');
            
            // 更新成功
            $message = "个人资料已成功更新！";
            $messageType = "success";
            
            // 刷新管理员数据
            $adminData = $admin->getCurrentAdmin();
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = "danger";
        }
    } elseif (isset($_POST['upload_avatar'])) {
        // 上传头像
        try {
            // 检查是否有文件上传
            if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
                throw new Exception("头像上传失败，请选择有效的图片文件");
            }
            
            // 获取文件信息
            $uploadFile = $_FILES['avatar'];
            $fileName = $uploadFile['name'];
            $fileTmpName = $uploadFile['tmp_name'];
            $fileSize = $uploadFile['size'];
            $fileError = $uploadFile['error'];
            $fileType = $uploadFile['type'];
            
            // 检查文件类型
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception("只允许上传JPG、PNG或GIF格式的图片");
            }
            
            // 检查文件大小 (最大2MB)
            $maxSize = 2 * 1024 * 1024;
            if ($fileSize > $maxSize) {
                throw new Exception("图片大小不能超过2MB");
            }
            
            // 创建上传目录
            $uploadDir = '../uploads/avatars/';
            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                throw new Exception("无法创建上传目录");
            }
            
            // 生成唯一文件名
            $adminId = $admin->getCurrentAdminId();
            $extension = pathinfo($fileName, PATHINFO_EXTENSION);
            $newFileName = 'admin_' . $adminId . '_' . time() . '.' . $extension;
            $targetPath = $uploadDir . $newFileName;
            
            // 移动上传的文件
            if (!move_uploaded_file($fileTmpName, $targetPath)) {
                throw new Exception("文件上传失败，请稍后重试");
            }
            
            // 更新数据库中的头像路径
            $avatarPath = 'uploads/avatars/' . $newFileName;
            $stmt = $conn->prepare("UPDATE admins SET avatar = ? WHERE id = ?");
            if ($stmt === false) {
                throw new Exception("准备SQL语句失败: " . $conn->error);
            }
            $stmt->bind_param("si", $avatarPath, $adminId);
            
            if (!$stmt->execute()) {
                throw new Exception("更新头像信息失败: " . $stmt->error);
            }
            
            // 记录操作日志
            $admin->logOperation('系统', '更新', '更新个人头像');
            
            // 更新成功
            $message = "头像已成功更新！";
            $messageType = "success";
            
            // 刷新管理员数据
            $adminData = $admin->getCurrentAdmin();
            
        } catch (Exception $e) {
            $message = $e->getMessage();
            $messageType = "danger";
        }
    }
}

// 获取管理员登录历史
try {
    $adminId = $admin->getCurrentAdminId();
    $loginHistoryQuery = "SELECT created_at, login_ip, user_agent, status FROM admin_login_logs 
                         WHERE admin_id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt = $conn->prepare($loginHistoryQuery);
    if ($stmt === false) {
        throw new Exception("准备登录历史查询失败: " . $conn->error);
    }
    $stmt->bind_param("i", $adminId);
    if (!$stmt->execute()) {
        throw new Exception("执行登录历史查询失败: " . $stmt->error);
    }
    $loginHistoryResult = $stmt->get_result();
    $loginHistory = [];
    
    while ($row = $loginHistoryResult->fetch_assoc()) {
        $loginHistory[] = $row;
    }
} catch (Exception $e) {
    error_log("获取登录历史失败: " . $e->getMessage());
    $loginHistory = [];
}

// 获取最近操作记录
try {
    $recentActivitiesQuery = "SELECT module, action, description, ip, created_at FROM admin_operation_logs 
                              WHERE admin_id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt = $conn->prepare($recentActivitiesQuery);
    if ($stmt === false) {
        throw new Exception("准备操作记录查询失败: " . $conn->error);
    }
    $stmt->bind_param("i", $adminId);
    if (!$stmt->execute()) {
        throw new Exception("执行操作记录查询失败: " . $stmt->error);
    }
    $recentActivitiesResult = $stmt->get_result();
    $recentActivities = [];
    
    while ($row = $recentActivitiesResult->fetch_assoc()) {
        $recentActivities[] = $row;
    }
} catch (Exception $e) {
    error_log("获取最近操作记录失败: " . $e->getMessage());
    $recentActivities = [];
}
?>

<!-- 内容区域 -->
<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">控制台</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <!-- 消息提示 -->
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="row">
        <!-- 个人资料卡片 -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-user me-1"></i> 个人资料
                </div>
                <div class="card-body text-center">
                    <!-- 头像 -->
                    <div class="mb-3">
                        <?php 
                        $avatarPath = !empty($adminData['avatar']) ? '../' . $adminData['avatar'] : 'https://via.placeholder.com/150?text=头像';
                        ?>
                        <img src="<?php echo $avatarPath; ?>" alt="Admin Avatar" class="rounded-circle img-fluid" style="width: 150px; height: 150px; object-fit: cover;">
                    </div>
                    
                    <h5 class="mb-1"><?php echo htmlspecialchars($adminData['display_name'] ?? $adminData['username']); ?></h5>
                    <p class="text-muted mb-3"><?php echo htmlspecialchars($adminData['role_name'] ?? '管理员'); ?></p>
                    
                    <!-- 上传头像表单 -->
                    <form method="post" enctype="multipart/form-data" class="mb-3">
                        <div class="input-group mb-3">
                            <input type="file" class="form-control" id="avatar" name="avatar" accept="image/*" required>
                            <button class="btn btn-primary" type="submit" name="upload_avatar">上传头像</button>
                        </div>
                    </form>
                    
                    <!-- 基本信息 -->
                    <div class="text-start">
                        <div class="mb-2">
                            <strong><i class="fas fa-user me-2"></i>用户名:</strong> <?php echo htmlspecialchars($adminData['username']); ?>
                        </div>
                        <div class="mb-2">
                            <strong><i class="fas fa-envelope me-2"></i>邮箱:</strong> <?php echo htmlspecialchars($adminData['email'] ?? '未设置'); ?>
                        </div>
                        <div class="mb-2">
                            <strong><i class="fas fa-phone me-2"></i>电话:</strong> <?php echo htmlspecialchars($adminData['phone'] ?? '未设置'); ?>
                        </div>
                        <div class="mb-2">
                            <strong><i class="fas fa-calendar me-2"></i>创建时间:</strong> <?php echo date('Y-m-d H:i:s', strtotime($adminData['created_at'])); ?>
                        </div>
                        <div class="mb-2">
                            <strong><i class="fas fa-clock me-2"></i>最后登录:</strong> <?php echo date('Y-m-d H:i:s', strtotime($adminData['last_login_time'])); ?>
                        </div>
                        <div class="mb-2">
                            <strong><i class="fas fa-map-marker-alt me-2"></i>最后登录IP:</strong> <?php echo htmlspecialchars($adminData['last_login_ip']); ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="change_password.php" class="btn btn-outline-primary">修改密码</a>
                </div>
            </div>
        </div>
        
        <!-- 编辑个人资料 -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-edit me-1"></i> 编辑个人资料
                </div>
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label for="display_name" class="form-label">显示名称</label>
                            <input type="text" class="form-control" id="display_name" name="display_name" value="<?php echo htmlspecialchars($adminData['display_name'] ?? ''); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">邮箱地址</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($adminData['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">电话号码</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($adminData['phone'] ?? ''); ?>">
                        </div>
                        <button type="submit" name="update_profile" class="btn btn-primary">保存更改</button>
                    </form>
                </div>
            </div>
            
            <!-- 最近登录记录 -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-history me-1"></i> 最近登录记录
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>IP地址</th>
                                    <th>状态</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($loginHistory) > 0): ?>
                                    <?php foreach ($loginHistory as $log): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($log['login_ip']); ?></td>
                                        <td>
                                            <?php if ($log['status'] == 1): ?>
                                                <span class="badge bg-success">成功</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">失败</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="3" class="text-center">暂无登录记录</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- 最近操作记录 -->
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-clipboard-list me-1"></i> 最近操作记录
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>时间</th>
                                    <th>模块</th>
                                    <th>操作</th>
                                    <th>描述</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($recentActivities) > 0): ?>
                                    <?php foreach ($recentActivities as $activity): ?>
                                    <tr>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($activity['created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($activity['module']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                        <td><?php echo htmlspecialchars($activity['description']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">暂无操作记录</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 关闭数据库连接
$conn->close();

// 引入底部
require_once 'includes/footer.php';
?> 