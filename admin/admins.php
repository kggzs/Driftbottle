<?php
$pageTitle = '管理员设置';
$pageActions = '<a href="admins.php?action=add" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> 添加管理员</a>';

// 引入头部
require_once 'includes/header.php';

// 初始化数据库连接
$conn = getDbConnection();

// 当前管理员信息
$currentAdminData = $admin->getCurrentAdmin();

// 检查是否有管理员权限
if (!$admin->hasPermission('all')) {
    echo '<div class="alert alert-danger">您没有权限访问此页面</div>';
    require_once 'includes/footer.php';
    exit;
}

// 处理分页
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 处理管理员操作
$message = '';
$messageType = '';

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // 添加管理员
    if ($action === 'add') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = sanitizeInput($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $realName = sanitizeInput($_POST['real_name'] ?? '');
            $email = sanitizeInput($_POST['email'] ?? '');
            $roleId = (int)($_POST['role_id'] ?? 0);
            
            // 验证输入
            $errors = [];
            
            if (empty($username)) {
                $errors[] = '用户名不能为空';
            }
            
            if (empty($password)) {
                $errors[] = '密码不能为空';
            }
            
            if (empty($roleId)) {
                $errors[] = '请选择角色';
            }
            
            // 检查用户名是否已存在
            if (!empty($username)) {
                $checkStmt = $conn->prepare("SELECT id FROM admins WHERE username = ?");
                $checkStmt->bind_param("s", $username);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    $errors[] = '该用户名已存在';
                }
                
                $checkStmt->close();
            }
            
            if (empty($errors)) {
                // 加密密码
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // 插入新管理员
                $insertStmt = $conn->prepare("INSERT INTO admins (username, password, real_name, email, role_id, status) VALUES (?, ?, ?, ?, ?, 1)");
                $insertStmt->bind_param("ssssi", $username, $hashedPassword, $realName, $email, $roleId);
                
                if ($insertStmt->execute()) {
                    $newAdminId = $conn->insert_id;
                    
                    // 记录操作日志
                    $admin->logOperation('管理员', '添加', "添加管理员: $username");
                    
                    $message = "管理员 $username 添加成功！";
                    $messageType = 'success';
                    
                    // 重定向到管理员列表
                    header("Location: admins.php?message=" . urlencode($message) . "&type=$messageType");
                    exit;
                } else {
                    $message = '添加失败: ' . $conn->error;
                    $messageType = 'danger';
                }
                
                $insertStmt->close();
            } else {
                $message = implode('<br>', $errors);
                $messageType = 'danger';
            }
        }
        
        // 获取角色列表
        $rolesQuery = "SELECT id, name FROM admin_roles ORDER BY id";
        $rolesResult = $conn->query($rolesQuery);
        $roles = [];
        
        while ($role = $rolesResult->fetch_assoc()) {
            $roles[] = $role;
        }
    }
    
    // 编辑管理员
    if ($action === 'edit' && isset($_GET['id'])) {
        $adminId = (int)$_GET['id'];
        
        // 不能编辑自己
        if ($adminId === $admin->getCurrentAdminId()) {
            $message = '不能在此编辑自己的账号，请使用个人资料页面';
            $messageType = 'warning';
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $realName = sanitizeInput($_POST['real_name'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $roleId = (int)($_POST['role_id'] ?? 0);
                $status = (int)($_POST['status'] ?? 0);
                $password = $_POST['password'] ?? '';
                
                // 构建更新语句
                if (!empty($password)) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $updateStmt = $conn->prepare("UPDATE admins SET real_name = ?, email = ?, role_id = ?, status = ?, password = ? WHERE id = ?");
                    $updateStmt->bind_param("ssiisi", $realName, $email, $roleId, $status, $hashedPassword, $adminId);
                } else {
                    $updateStmt = $conn->prepare("UPDATE admins SET real_name = ?, email = ?, role_id = ?, status = ? WHERE id = ?");
                    $updateStmt->bind_param("ssiii", $realName, $email, $roleId, $status, $adminId);
                }
                
                if ($updateStmt->execute()) {
                    // 记录操作日志
                    $admin->logOperation('管理员', '编辑', "编辑管理员ID: $adminId");
                    
                    $message = '管理员信息更新成功！';
                    $messageType = 'success';
                    
                    // 重定向到管理员列表
                    header("Location: admins.php?message=" . urlencode($message) . "&type=$messageType");
                    exit;
                } else {
                    $message = '更新失败: ' . $conn->error;
                    $messageType = 'danger';
                }
                
                $updateStmt->close();
            }
            
            // 获取管理员信息
            $adminInfoStmt = $conn->prepare("SELECT * FROM admins WHERE id = ?");
            $adminInfoStmt->bind_param("i", $adminId);
            $adminInfoStmt->execute();
            $adminInfo = $adminInfoStmt->get_result()->fetch_assoc();
            $adminInfoStmt->close();
            
            if (!$adminInfo) {
                $message = '管理员不存在';
                $messageType = 'danger';
            } else {
                // 获取角色列表
                $rolesQuery = "SELECT id, name FROM admin_roles ORDER BY id";
                $rolesResult = $conn->query($rolesQuery);
                $roles = [];
                
                while ($role = $rolesResult->fetch_assoc()) {
                    $roles[] = $role;
                }
            }
        }
    }
    
    // 删除管理员
    if ($action === 'delete' && isset($_GET['id'])) {
        $adminId = (int)$_GET['id'];
        
        // 不能删除自己
        if ($adminId === $admin->getCurrentAdminId()) {
            $message = '不能删除自己的账号';
            $messageType = 'danger';
        } else {
            if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
                $deleteStmt = $conn->prepare("DELETE FROM admins WHERE id = ?");
                $deleteStmt->bind_param("i", $adminId);
                
                if ($deleteStmt->execute()) {
                    // 记录操作日志
                    $admin->logOperation('管理员', '删除', "删除管理员ID: $adminId");
                    
                    $message = '管理员删除成功！';
                    $messageType = 'success';
                    
                    // 重定向以避免刷新页面重复提交
                    header("Location: admins.php?message=" . urlencode($message) . "&type=$messageType");
                    exit;
                } else {
                    $message = '删除失败: ' . $conn->error;
                    $messageType = 'danger';
                }
                
                $deleteStmt->close();
            } else {
                // 获取要删除的管理员信息
                $adminInfoStmt = $conn->prepare("SELECT username, real_name FROM admins WHERE id = ?");
                $adminInfoStmt->bind_param("i", $adminId);
                $adminInfoStmt->execute();
                $adminInfo = $adminInfoStmt->get_result()->fetch_assoc();
                $adminInfoStmt->close();
            }
        }
    }
}

// 从URL参数获取消息
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'] ?? 'info';
}

// 记录管理员操作
$admin->logOperation('管理员', '查看', '查看管理员列表');

// 查询管理员列表
$countQuery = "SELECT COUNT(*) as total FROM admins";
$countResult = $conn->query($countQuery);
$total = $countResult->fetch_assoc()['total'];

// 计算总页数
$totalPages = ceil($total / $limit);

// 查询管理员列表
$query = "SELECT a.*, r.name as role_name 
          FROM admins a 
          LEFT JOIN admin_roles r ON a.role_id = r.id 
          ORDER BY a.id ASC 
          LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $offset, $limit);
$stmt->execute();
$result = $stmt->get_result();
?>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?>" role="alert">
    <?php echo $message; ?>
</div>
<?php endif; ?>

<?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
<!-- 添加管理员表单 -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="m-0">添加管理员</h5>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label for="username" class="form-label">用户名 <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="col-md-6">
                <label for="password" class="form-label">密码 <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="col-md-6">
                <label for="real_name" class="form-label">真实姓名</label>
                <input type="text" class="form-control" id="real_name" name="real_name">
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">邮箱</label>
                <input type="email" class="form-control" id="email" name="email">
            </div>
            <div class="col-md-6">
                <label for="role_id" class="form-label">角色 <span class="text-danger">*</span></label>
                <select class="form-select" id="role_id" name="role_id" required>
                    <option value="">选择角色</option>
                    <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">保存</button>
                <a href="admins.php" class="btn btn-secondary ms-2">取消</a>
            </div>
        </form>
    </div>
</div>
<?php elseif (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($adminInfo)): ?>
<!-- 编辑管理员表单 -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="m-0">编辑管理员</h5>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label for="username" class="form-label">用户名</label>
                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($adminInfo['username']); ?>" readonly>
            </div>
            <div class="col-md-6">
                <label for="password" class="form-label">密码（不修改请留空）</label>
                <input type="password" class="form-control" id="password" name="password">
                <div class="form-text">如果不需要修改密码，请留空</div>
            </div>
            <div class="col-md-6">
                <label for="real_name" class="form-label">真实姓名</label>
                <input type="text" class="form-control" id="real_name" name="real_name" value="<?php echo htmlspecialchars($adminInfo['real_name'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="email" class="form-label">邮箱</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($adminInfo['email'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="role_id" class="form-label">角色</label>
                <select class="form-select" id="role_id" name="role_id">
                    <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['id']; ?>" <?php echo $adminInfo['role_id'] == $role['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($role['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="status" class="form-label">状态</label>
                <select class="form-select" id="status" name="status">
                    <option value="1" <?php echo $adminInfo['status'] == 1 ? 'selected' : ''; ?>>启用</option>
                    <option value="0" <?php echo $adminInfo['status'] == 0 ? 'selected' : ''; ?>>禁用</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">保存更改</button>
                <a href="admins.php" class="btn btn-secondary ms-2">取消</a>
            </div>
        </form>
    </div>
</div>
<?php elseif (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($adminInfo)): ?>
<!-- 删除确认对话框 -->
<div class="card mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="m-0">确认删除</h5>
    </div>
    <div class="card-body">
        <p>您确定要删除管理员 <strong><?php echo htmlspecialchars($adminInfo['username']); ?></strong> (<?php echo htmlspecialchars($adminInfo['real_name'] ?? ''); ?>) 吗？此操作不可撤销。</p>
        <form method="post">
            <div class="d-flex justify-content-end">
                <a href="admins.php" class="btn btn-secondary me-2">取消</a>
                <button type="submit" name="confirm_delete" value="1" class="btn btn-danger">确认删除</button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>
<!-- 管理员列表 -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>用户名</th>
                        <th>真实姓名</th>
                        <th>角色</th>
                        <th>最后登录</th>
                        <th>状态</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($adminRow = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $adminRow['id']; ?></td>
                            <td><?php echo htmlspecialchars($adminRow['username']); ?></td>
                            <td><?php echo htmlspecialchars($adminRow['real_name'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($adminRow['role_name'] ?? ''); ?></td>
                            <td>
                                <?php if ($adminRow['last_login_time']): ?>
                                <?php echo date('Y-m-d H:i', strtotime($adminRow['last_login_time'])); ?>
                                <div class="small text-muted"><?php echo htmlspecialchars($adminRow['last_login_ip'] ?? ''); ?></div>
                                <?php else: ?>
                                <span class="text-muted">从未登录</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $adminRow['status'] ? 'success' : 'danger'; ?>">
                                    <?php echo $adminRow['status'] ? '启用' : '禁用'; ?>
                                </span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <a href="admins.php?action=edit&id=<?php echo $adminRow['id']; ?>" class="btn btn-sm btn-primary" title="编辑">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <?php if ($adminRow['id'] != $admin->getCurrentAdminId()): ?>
                                    <a href="admins.php?action=delete&id=<?php echo $adminRow['id']; ?>" class="btn btn-sm btn-danger" title="删除">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">没有找到管理员记录</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- 分页 -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                    }
                }
                
                for ($i = $startPage; $i <= $endPage; $i++) {
                    echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . '">' . $i . '</a></li>';
                }
                
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php
// 引入底部
require_once 'includes/footer.php';
?> 