<?php
// 开启输出缓冲，避免headers already sent错误
ob_start();

$pageTitle = '用户管理';
$pageActions = '<a href="users.php?action=add" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> 添加用户</a>';

// 引入头部
require_once 'includes/header.php';

// 初始化数据库连接
$conn = getDbConnection();

// 处理用户操作
$message = '';
$messageType = '';

if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // 添加用户
    if ($action === 'add') {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 获取表单数据
            $username = sanitizeInput($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';
            $gender = sanitizeInput($_POST['gender'] ?? '');
            $signature = sanitizeInput($_POST['signature'] ?? '');
            $points = (int)($_POST['points'] ?? 0);
            $is_vip = isset($_POST['is_vip']) ? 1 : 0;
            $vip_level = (int)($_POST['vip_level'] ?? 0);
            $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
            
            // 验证数据
            $errors = [];
            
            if (empty($username)) {
                $errors[] = '用户名不能为空';
            }
            
            if (empty($password)) {
                $errors[] = '密码不能为空';
            }
            
            if (empty($gender)) {
                $errors[] = '请选择性别';
            }
            
            // 检查用户名是否已存在
            $checkStmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $checkStmt->bind_param("s", $username);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();
            
            if ($checkResult->num_rows > 0) {
                $errors[] = '用户名已存在';
            }
            
            $checkStmt->close();
            
            if (empty($errors)) {
                // 创建用户
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $vip_expire_date = $is_vip ? date('Y-m-d', strtotime('+1 year')) : null;
                
                // 获取注册IP（后台添加用户时，记录管理员IP）
                require_once __DIR__ . '/../includes/bottle.php';
                $registerIp = getClientIpAddress();
                
                $insertStmt = $conn->prepare("INSERT INTO users (username, password, gender, signature, points, is_vip, vip_level, vip_expire_date, status, register_ip) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $insertStmt->bind_param("ssssiissis", $username, $hashedPassword, $gender, $signature, $points, $is_vip, $vip_level, $vip_expire_date, $status, $registerIp);
                
                if ($insertStmt->execute()) {
                    $newUserId = $conn->insert_id;
                    
                    // 记录操作日志
                    $admin->logOperation('用户', '添加', "添加用户: $username (ID: $newUserId)");
                    
                    // 记录积分历史
                    if ($points > 0) {
                        $pointsAction = "管理员初始设置";
                        $pointsStmt = $conn->prepare("INSERT INTO points_history (user_id, points, action) VALUES (?, ?, ?)");
                        $pointsStmt->bind_param("iis", $newUserId, $points, $pointsAction);
                        $pointsStmt->execute();
                        $pointsStmt->close();
                    }
                    
                    $message = "用户 $username 创建成功！";
                    $messageType = 'success';
                    
                    // 重定向到用户列表
                    header("Location: users.php?message=" . urlencode($message) . "&type=$messageType");
                    exit;
                } else {
                    $message = '创建用户失败: ' . $conn->error;
                    $messageType = 'danger';
                }
                
                $insertStmt->close();
            } else {
                $message = implode('<br>', $errors);
                $messageType = 'danger';
            }
        }
    }
    
    // 删除用户
    if ($action === 'delete' && isset($_GET['id'])) {
        $userId = (int)$_GET['id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
            $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
            $deleteStmt->bind_param("i", $userId);
            
            if ($deleteStmt->execute()) {
                // 记录操作日志
                $admin->logOperation('用户', '删除', "删除用户ID: $userId");
                
                $message = '用户删除成功！';
                $messageType = 'success';
                
                // 重定向以避免刷新页面重复提交
                header("Location: users.php?message=" . urlencode($message) . "&type=$messageType");
                exit;
            } else {
                $message = '删除失败: ' . $conn->error;
                $messageType = 'danger';
            }
            
            $deleteStmt->close();
        } else {
            // 获取要删除的用户信息
            $userInfoStmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
            $userInfoStmt->bind_param("i", $userId);
            $userInfoStmt->execute();
            $userInfo = $userInfoStmt->get_result()->fetch_assoc();
            $userInfoStmt->close();
        }
    }
    
    // 禁用/启用用户 VIP 状态
    if ($action === 'toggle_vip' && isset($_GET['id'])) {
        $userId = (int)$_GET['id'];
        
        // 获取当前VIP状态
        $vipStmt = $conn->prepare("SELECT is_vip FROM users WHERE id = ?");
        $vipStmt->bind_param("i", $userId);
        $vipStmt->execute();
        $currentVip = $vipStmt->get_result()->fetch_assoc()['is_vip'];
        $vipStmt->close();
        
        // 反转VIP状态
        $newVipStatus = $currentVip ? 0 : 1;
        $vipExpireDate = $newVipStatus ? date('Y-m-d', strtotime('+1 year')) : null;
        
        $updateStmt = $conn->prepare("UPDATE users SET is_vip = ?, vip_expire_date = ? WHERE id = ?");
        $updateStmt->bind_param("isi", $newVipStatus, $vipExpireDate, $userId);
        
        if ($updateStmt->execute()) {
            // 记录操作日志
            $status = $newVipStatus ? '开启' : '关闭';
            $admin->logOperation('用户', 'VIP状态修改', "用户ID: $userId, $status VIP");
            
            $message = "用户VIP状态已" . ($newVipStatus ? '开启' : '关闭');
            $messageType = 'success';
            
            // 重定向以避免刷新页面重复提交
            header("Location: users.php?message=" . urlencode($message) . "&type=$messageType");
            exit;
        } else {
            $message = '操作失败: ' . $conn->error;
            $messageType = 'danger';
        }
        
        $updateStmt->close();
    }
    
    // 禁用/启用用户账号
    if ($action === 'toggle_status' && isset($_GET['id'])) {
        $userId = (int)$_GET['id'];
        
        // 获取当前状态
        $statusStmt = $conn->prepare("SELECT status, username FROM users WHERE id = ?");
        $statusStmt->bind_param("i", $userId);
        $statusStmt->execute();
        $result = $statusStmt->get_result();
        $userData = $result->fetch_assoc();
        $currentStatus = $userData['status'];
        $username = $userData['username'];
        $statusStmt->close();
        
        // 反转状态
        $newStatus = $currentStatus ? 0 : 1;
        
        $updateStmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $newStatus, $userId);
        
        if ($updateStmt->execute()) {
            // 记录操作日志
            $statusText = $newStatus ? '启用' : '禁用';
            $admin->logOperation('用户', '账号状态修改', "用户: $username (ID: $userId), $statusText 账号");
            
            $message = "用户 $username 的账号已" . ($newStatus ? '启用' : '禁用');
            $messageType = 'success';
            
            // 重定向以避免刷新页面重复提交
            header("Location: users.php?message=" . urlencode($message) . "&type=$messageType");
            exit;
        } else {
            $message = '操作失败: ' . $conn->error;
            $messageType = 'danger';
        }
        
        $updateStmt->close();
    }
}

// 从URL参数获取消息
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'] ?? 'info';
}

// 处理搜索条件
$search = sanitizeInput($_GET['search'] ?? '');
$gender = sanitizeInput($_GET['gender'] ?? '');
$vip = isset($_GET['vip']) ? (int)$_GET['vip'] : -1;
$status = isset($_GET['status']) ? (int)$_GET['status'] : -1;
$sortBy = sanitizeInput($_GET['sort_by'] ?? 'id');
$sortOrder = sanitizeInput($_GET['sort_order'] ?? 'desc');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 构建查询条件
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(username LIKE ? OR signature LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

if (!empty($gender)) {
    $where[] = "gender = ?";
    $params[] = $gender;
    $types .= 's';
}

if ($vip >= 0) {
    $where[] = "is_vip = ?";
    $params[] = $vip;
    $types .= 'i';
}

if ($status >= 0) {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 'i';
}

// 构建最终的WHERE子句
$whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';

// 验证排序字段
$allowedSortFields = ['id', 'username', 'points', 'is_vip', 'created_at'];
if (!in_array($sortBy, $allowedSortFields)) {
    $sortBy = 'id';
}

// 验证排序方向
$sortOrder = strtolower($sortOrder) === 'asc' ? 'ASC' : 'DESC';

// 准备查询语句
$countQuery = "SELECT COUNT(*) as total FROM users $whereClause";
$query = "SELECT * FROM users $whereClause ORDER BY $sortBy $sortOrder LIMIT ?, ?";

// 创建计数查询的参数和类型(不包含分页)
$countParams = $params;
$countTypes = $types;

// 为列表查询添加分页参数
$params[] = $offset;
$params[] = $limit;
$types .= 'ii';

// 执行统计查询
if (!empty($countParams)) {
    try {
        $countStmt = $conn->prepare($countQuery);
        if ($countStmt === false) {
            throw new Exception("准备计数查询失败: " . $conn->error);
        }
        $countStmt->bind_param($countTypes, ...$countParams);
        $countStmt->execute();
        $totalResult = $countStmt->get_result();
        $total = $totalResult->fetch_assoc()['total'];
        $countStmt->close();
    } catch (Exception $e) {
        error_log("用户统计查询错误: " . $e->getMessage());
        $total = 0;
    }
} else {
    $totalResult = $conn->query($countQuery);
    if ($totalResult === false) {
        error_log("用户计数查询执行失败: " . $conn->error);
        $total = 0;
    } else {
        $total = $totalResult->fetch_assoc()['total'];
    }
}

// 计算总页数
$totalPages = ceil($total / $limit);

// 查询用户列表
if (!empty($params)) {
    try {
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("准备用户查询失败: " . $conn->error);
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } catch (Exception $e) {
        error_log("用户列表查询错误: " . $e->getMessage());
        $result = false;
    }
} else {
    $result = $conn->query($query);
    if ($result === false) {
        error_log("用户列表查询失败: " . $conn->error);
        $result = false;
    }
}

// 如果查询失败，创建一个空结果集
if (!$result) {
    $result = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// 记录管理员操作
$admin->logOperation('用户', '查看', '查看用户列表');
?>

<!-- 搜索表单 -->
<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-3">
            <div class="col-md-3">
                <label for="search" class="form-label">搜索</label>
                <input type="text" class="form-control" id="search" name="search" placeholder="用户名/签名" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-2">
                <label for="gender" class="form-label">性别</label>
                <select class="form-select" id="gender" name="gender">
                    <option value="">全部</option>
                    <option value="男" <?php echo $gender === '男' ? 'selected' : ''; ?>>男</option>
                    <option value="女" <?php echo $gender === '女' ? 'selected' : ''; ?>>女</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="vip" class="form-label">VIP状态</label>
                <select class="form-select" id="vip" name="vip">
                    <option value="-1">全部</option>
                    <option value="1" <?php echo $vip === 1 ? 'selected' : ''; ?>>是</option>
                    <option value="0" <?php echo $vip === 0 ? 'selected' : ''; ?>>否</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">账号状态</label>
                <select class="form-select" id="status" name="status">
                    <option value="-1">全部</option>
                    <option value="1" <?php echo isset($_GET['status']) && (int)$_GET['status'] === 1 ? 'selected' : ''; ?>>启用</option>
                    <option value="0" <?php echo isset($_GET['status']) && (int)$_GET['status'] === 0 ? 'selected' : ''; ?>>禁用</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="sort_by" class="form-label">排序</label>
                <div class="input-group">
                    <select class="form-select" id="sort_by" name="sort_by">
                        <option value="id" <?php echo $sortBy === 'id' ? 'selected' : ''; ?>>ID</option>
                        <option value="username" <?php echo $sortBy === 'username' ? 'selected' : ''; ?>>用户名</option>
                        <option value="points" <?php echo $sortBy === 'points' ? 'selected' : ''; ?>>积分</option>
                        <option value="is_vip" <?php echo $sortBy === 'is_vip' ? 'selected' : ''; ?>>VIP状态</option>
                        <option value="created_at" <?php echo $sortBy === 'created_at' ? 'selected' : ''; ?>>注册时间</option>
                    </select>
                    <select class="form-select" id="sort_order" name="sort_order">
                        <option value="desc" <?php echo $sortOrder === 'DESC' ? 'selected' : ''; ?>>降序</option>
                        <option value="asc" <?php echo $sortOrder === 'ASC' ? 'selected' : ''; ?>>升序</option>
                    </select>
                </div>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">搜索</button>
            </div>
        </form>
    </div>
</div>

<?php if ($message): ?>
<div class="alert alert-<?php echo $messageType; ?>" role="alert">
    <?php echo $message; ?>
</div>
<?php endif; ?>

<?php if (isset($action) && $action === 'add'): ?>
<!-- 添加用户表单 -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="m-0">添加用户</h5>
    </div>
    <div class="card-body">
        <form method="post" class="row g-3">
            <div class="col-md-6">
                <label for="username" class="form-label">用户名 <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="username" name="username" required value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>
            <div class="col-md-6">
                <label for="password" class="form-label">密码 <span class="text-danger">*</span></label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="col-md-6">
                <label for="gender" class="form-label">性别 <span class="text-danger">*</span></label>
                <select class="form-select" id="gender" name="gender" required>
                    <option value="">请选择</option>
                    <option value="男" <?php echo isset($_POST['gender']) && $_POST['gender'] === '男' ? 'selected' : ''; ?>>男</option>
                    <option value="女" <?php echo isset($_POST['gender']) && $_POST['gender'] === '女' ? 'selected' : ''; ?>>女</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="points" class="form-label">初始积分</label>
                <input type="number" class="form-control" id="points" name="points" value="<?php echo htmlspecialchars($_POST['points'] ?? '0'); ?>" min="0">
            </div>
            <div class="col-md-12">
                <label for="signature" class="form-label">个性签名</label>
                <textarea class="form-control" id="signature" name="signature" rows="2"><?php echo htmlspecialchars($_POST['signature'] ?? ''); ?></textarea>
            </div>
            <div class="col-md-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="is_vip" name="is_vip" <?php echo isset($_POST['is_vip']) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="is_vip">
                        VIP会员
                    </label>
                </div>
            </div>
            <div class="col-md-6" id="vipOptions" <?php echo isset($_POST['is_vip']) ? '' : 'style="display: none;"'; ?>>
                <label for="vip_level" class="form-label">VIP等级</label>
                <select class="form-select" id="vip_level" name="vip_level">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <option value="<?php echo $i; ?>" <?php echo isset($_POST['vip_level']) && (int)$_POST['vip_level'] === $i ? 'selected' : ''; ?>>
                        VIP <?php echo $i; ?>
                    </option>
                    <?php endfor; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="status" class="form-label">账号状态</label>
                <select class="form-select" id="status" name="status">
                    <option value="1" <?php echo !isset($_POST['status']) || (int)$_POST['status'] === 1 ? 'selected' : ''; ?>>启用</option>
                    <option value="0" <?php echo isset($_POST['status']) && (int)$_POST['status'] === 0 ? 'selected' : ''; ?>>禁用</option>
                </select>
            </div>
            <div class="col-12">
                <button type="submit" class="btn btn-primary">创建用户</button>
                <a href="users.php" class="btn btn-secondary">取消</a>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isVipCheckbox = document.getElementById('is_vip');
    const vipOptionsDiv = document.getElementById('vipOptions');
    
    if (isVipCheckbox && vipOptionsDiv) {
        isVipCheckbox.addEventListener('change', function() {
            vipOptionsDiv.style.display = this.checked ? 'block' : 'none';
        });
    }
});
</script>
<?php elseif (isset($action) && $action === 'delete' && isset($userInfo)): ?>
<!-- 删除确认对话框 -->
<div class="card mb-4">
    <div class="card-header bg-danger text-white">
        <h5 class="m-0">确认删除</h5>
    </div>
    <div class="card-body">
        <p>您确定要删除用户 <strong><?php echo htmlspecialchars($userInfo['username']); ?></strong> 吗？此操作不可撤销，该用户的所有漂流瓶、评论等数据都将被删除。</p>
        <form method="post">
            <div class="d-flex justify-content-end">
                <a href="users.php" class="btn btn-secondary me-2">取消</a>
                <button type="submit" name="confirm_delete" value="1" class="btn btn-danger">确认删除</button>
            </div>
        </form>
    </div>
</div>
<?php else: ?>

<!-- 用户列表 -->
<div class="card">
    <div class="card-header py-3 d-flex justify-content-between align-items-center">
        <h6 class="m-0 font-weight-bold">用户列表 (共<?php echo $total; ?>条记录)</h6>
        <div class="btn-group">
            <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                批量操作
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><button class="dropdown-item batch-action" data-action="vip">设为VIP</button></li>
                <li><button class="dropdown-item batch-action" data-action="cancel-vip">取消VIP</button></li>
                <li><hr class="dropdown-divider"></li>
                <li><button class="dropdown-item batch-action" data-action="enable">启用账号</button></li>
                <li><button class="dropdown-item batch-action" data-action="disable">禁用账号</button></li>
                <li><hr class="dropdown-divider"></li>
                <li><button class="dropdown-item batch-action text-danger" data-action="delete">删除</button></li>
            </ul>
        </div>
    </div>
    <div class="card-body">
        <form id="batch-form" method="post" action="batch_users.php">
            <input type="hidden" name="action" id="batch-action" value="">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="select-all">
                                </div>
                            </th>
                            <th>ID</th>
                            <th>用户名</th>
                            <th>性别</th>
                            <th>签名</th>
                            <th>积分</th>
                            <th>经验值/等级</th>
                            <th>VIP状态</th>
                            <th>账号状态</th>
                            <th>注册时间</th>
                            <th>操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($user = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="form-check">
                                        <input class="form-check-input user-checkbox" type="checkbox" name="user_ids[]" value="<?php echo $user['id']; ?>">
                                    </div>
                                </td>
                                <td><?php echo $user['id']; ?></td>
                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                <td><?php echo $user['gender']; ?></td>
                                <td><?php echo mb_substr(htmlspecialchars($user['signature'] ?? ''), 0, 20) . (mb_strlen($user['signature'] ?? '') > 20 ? '...' : ''); ?></td>
                                <td><?php echo $user['points']; ?></td>
                                <td>
                                    <?php 
                                    $experience = $user['experience'] ?? 0;
                                    $level = $user['level'] ?? 1;
                                    ?>
                                    <span class="badge bg-info">Lv.<?php echo $level; ?></span>
                                    <div class="small text-muted mt-1">
                                        经验: <?php echo $experience; ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['is_vip'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['is_vip'] ? 'VIP' . $user['vip_level'] : '普通用户'; ?>
                                    </span>
                                    <?php if ($user['is_vip'] && $user['vip_expire_date']): ?>
                                    <div class="small text-muted">
                                        到期: <?php echo date('Y-m-d', strtotime($user['vip_expire_date'])); ?>
                                    </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['status'] ? 'success' : 'secondary'; ?>">
                                        <?php echo $user['status'] ? '启用' : '禁用'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <a href="user_detail.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info" title="查看详情">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="users.php?action=toggle_vip&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-<?php echo $user['is_vip'] ? 'warning' : 'success'; ?>" title="<?php echo $user['is_vip'] ? '取消VIP' : '设为VIP'; ?>">
                                            <i class="bi bi-star<?php echo $user['is_vip'] ? '-fill' : ''; ?>"></i>
                                        </a>
                                        <a href="users.php?action=toggle_status&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-<?php echo $user['status'] ? 'secondary' : 'primary'; ?>" title="<?php echo $user['status'] ? '禁用账号' : '启用账号'; ?>">
                                            <i class="bi bi-<?php echo $user['status'] ? 'lock' : 'unlock'; ?>"></i>
                                        </a>
                                        <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-danger" title="删除用户">
                                            <i class="bi bi-trash"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">没有找到符合条件的用户</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </form>
        
        <!-- 分页 -->
        <?php if ($totalPages > 1): ?>
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($gender) ? '&gender=' . urlencode($gender) : ''; ?><?php echo $vip >= 0 ? '&vip=' . $vip : ''; ?><?php echo $status >= 0 ? '&status=' . $status : ''; ?><?php echo !empty($sortBy) ? '&sort_by=' . $sortBy : ''; ?><?php echo !empty($sortOrder) ? '&sort_order=' . $sortOrder : ''; ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                
                if ($startPage > 1) {
                    echo '<li class="page-item"><a class="page-link" href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($gender) ? '&gender=' . urlencode($gender) : '') . ($vip >= 0 ? '&vip=' . $vip : '') . ($status >= 0 ? '&status=' . $status : '') . (!empty($sortBy) ? '&sort_by=' . $sortBy : '') . (!empty($sortOrder) ? '&sort_order=' . $sortOrder : '') . '">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                    }
                }
                
                for ($i = $startPage; $i <= $endPage; $i++) {
                    echo '<li class="page-item ' . ($page == $i ? 'active' : '') . '"><a class="page-link" href="?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($gender) ? '&gender=' . urlencode($gender) : '') . ($vip >= 0 ? '&vip=' . $vip : '') . ($status >= 0 ? '&status=' . $status : '') . (!empty($sortBy) ? '&sort_by=' . $sortBy : '') . (!empty($sortOrder) ? '&sort_order=' . $sortOrder : '') . '">' . $i . '</a></li>';
                }
                
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                    }
                    echo '<li class="page-item"><a class="page-link" href="?page=' . $totalPages . (!empty($search) ? '&search=' . urlencode($search) : '') . (!empty($gender) ? '&gender=' . urlencode($gender) : '') . ($vip >= 0 ? '&vip=' . $vip : '') . ($status >= 0 ? '&status=' . $status : '') . (!empty($sortBy) ? '&sort_by=' . $sortBy : '') . (!empty($sortOrder) ? '&sort_order=' . $sortOrder : '') . '">' . $totalPages . '</a></li>';
                }
                ?>
                
                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($gender) ? '&gender=' . urlencode($gender) : ''; ?><?php echo $vip >= 0 ? '&vip=' . $vip : ''; ?><?php echo $status >= 0 ? '&status=' . $status : ''; ?><?php echo !empty($sortBy) ? '&sort_by=' . $sortBy : ''; ?><?php echo !empty($sortOrder) ? '&sort_order=' . $sortOrder : ''; ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- 批量操作确认弹窗 -->
<div class="modal fade" id="batchConfirmModal" tabindex="-1" aria-labelledby="batchConfirmModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="batchConfirmModalLabel">确认操作</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="batchConfirmMessage">您确定要执行此操作吗？</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                <button type="button" class="btn btn-primary" id="batchConfirmButton">确认</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 全选/取消全选
    const selectAllCheckbox = document.getElementById('select-all');
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            userCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
        });
    }
    
    // 批量操作
    const batchActions = document.querySelectorAll('.batch-action');
    const batchForm = document.getElementById('batch-form');
    const batchActionInput = document.getElementById('batch-action');
    const batchConfirmModal = new bootstrap.Modal(document.getElementById('batchConfirmModal'));
    const batchConfirmMessage = document.getElementById('batchConfirmMessage');
    const batchConfirmButton = document.getElementById('batchConfirmButton');
    
    if (batchActions.length > 0 && batchForm && batchActionInput) {
        batchActions.forEach(action => {
            action.addEventListener('click', function() {
                const actionType = this.dataset.action;
                const selectedUsers = document.querySelectorAll('.user-checkbox:checked');
                
                if (selectedUsers.length === 0) {
                    alert('请至少选择一个用户');
                    return;
                }
                
                let confirmMessage = '';
                switch (actionType) {
                    case 'vip':
                        confirmMessage = `您确定要将选中的 ${selectedUsers.length} 个用户设为VIP吗？`;
                        break;
                    case 'cancel-vip':
                        confirmMessage = `您确定要取消选中的 ${selectedUsers.length} 个用户的VIP权限吗？`;
                        break;
                    case 'delete':
                        confirmMessage = `您确定要删除选中的 ${selectedUsers.length} 个用户吗？此操作不可撤销！`;
                        break;
                }
                
                batchActionInput.value = actionType;
                batchConfirmMessage.textContent = confirmMessage;
                
                // 更新确认按钮的样式
                if (actionType === 'delete') {
                    batchConfirmButton.classList.remove('btn-primary');
                    batchConfirmButton.classList.add('btn-danger');
                } else {
                    batchConfirmButton.classList.remove('btn-danger');
                    batchConfirmButton.classList.add('btn-primary');
                }
                
                batchConfirmModal.show();
            });
        });
        
        // 确认按钮事件
        if (batchConfirmButton) {
            batchConfirmButton.addEventListener('click', function() {
                batchForm.submit();
            });
        }
    }
});
</script>

<?php
// 引入底部
require_once 'includes/footer.php';
?>