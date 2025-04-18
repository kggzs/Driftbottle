<?php
// 开启输出缓冲，避免headers already sent错误
ob_start();

// 页面标题
$pageTitle = '系统日志';

// 引入头部
require_once 'includes/header.php';

// 初始化数据库连接
$conn = getDbConnection();

// 记录管理员操作
$admin->logOperation('系统', '查看', '查看系统日志');

// 获取消息参数
$message = isset($_GET['message']) ? $_GET['message'] : '';
$messageType = isset($_GET['type']) ? $_GET['type'] : 'info';

// 获取日志类型参数
$logType = isset($_GET['type']) ? sanitizeInput($_GET['type']) : 'operation';
$validTypes = ['operation', 'error', 'login'];
if (!in_array($logType, $validTypes)) {
    $logType = 'operation';
}

// 获取分页参数
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = 20; // 每页显示数量
$offset = ($page - 1) * $limit;

// 获取搜索参数
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$startDate = isset($_GET['start_date']) ? sanitizeInput($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? sanitizeInput($_GET['end_date']) : '';
$admin_id = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : 0;
$module = isset($_GET['module']) ? sanitizeInput($_GET['module']) : '';

// 初始化筛选条件
$whereClause = "";
$params = [];
$types = "";

// 根据日志类型设置查询表和字段
switch ($logType) {
    case 'operation':
        $table = 'admin_operation_logs';
        $countQuery = "SELECT COUNT(*) as total FROM $table al LEFT JOIN admins a ON al.admin_id = a.id";
        $baseQuery = "SELECT al.*, a.username FROM $table al LEFT JOIN admins a ON al.admin_id = a.id";
        break;
    case 'error':
        $table = 'system_error_logs';
        $countQuery = "SELECT COUNT(*) as total FROM $table";
        $baseQuery = "SELECT * FROM $table";
        break;
    case 'login':
        $table = 'admin_login_logs';
        $countQuery = "SELECT COUNT(*) as total FROM $table all LEFT JOIN admins a ON all.admin_id = a.id";
        $baseQuery = "SELECT all.*, a.username FROM $table all LEFT JOIN admins a ON all.admin_id = a.id";
        break;
}

// 添加搜索条件
if (!empty($search)) {
    if ($logType == 'operation') {
        $whereClause .= empty($whereClause) ? "WHERE " : " AND ";
        $whereClause .= "(al.description LIKE ? OR al.module LIKE ? OR al.action LIKE ? OR a.username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= "ssss";
    } elseif ($logType == 'error') {
        $whereClause .= empty($whereClause) ? "WHERE " : " AND ";
        $whereClause .= "(message LIKE ? OR file LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= "ss";
    } elseif ($logType == 'login') {
        $whereClause .= empty($whereClause) ? "WHERE " : " AND ";
        $whereClause .= "(all.ip LIKE ? OR all.user_agent LIKE ? OR a.username LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $types .= "sss";
    }
}

// 添加日期范围条件
if (!empty($startDate)) {
    $whereClause .= empty($whereClause) ? "WHERE " : " AND ";
    if ($logType == 'operation') {
        $whereClause .= "DATE(al.created_at) >= ?";
    } elseif ($logType == 'error') {
        $whereClause .= "DATE(created_at) >= ?";
    } elseif ($logType == 'login') {
        $whereClause .= "DATE(all.created_at) >= ?";
    }
    $params[] = $startDate;
    $types .= "s";
}

if (!empty($endDate)) {
    $whereClause .= empty($whereClause) ? "WHERE " : " AND ";
    if ($logType == 'operation') {
        $whereClause .= "DATE(al.created_at) <= ?";
    } elseif ($logType == 'error') {
        $whereClause .= "DATE(created_at) <= ?";
    } elseif ($logType == 'login') {
        $whereClause .= "DATE(all.created_at) <= ?";
    }
    $params[] = $endDate;
    $types .= "s";
}

// 添加管理员筛选条件
if ($admin_id > 0 && ($logType == 'operation' || $logType == 'login')) {
    $whereClause .= empty($whereClause) ? "WHERE " : " AND ";
    if ($logType == 'operation') {
        $whereClause .= "al.admin_id = ?";
    } elseif ($logType == 'login') {
        $whereClause .= "all.admin_id = ?";
    }
    $params[] = $admin_id;
    $types .= "i";
}

// 添加模块筛选条件
if (!empty($module) && $logType == 'operation') {
    $whereClause .= empty($whereClause) ? "WHERE " : " AND ";
    $whereClause .= "al.module = ?";
    $params[] = $module;
    $types .= "s";
}

// 完成查询语句
$countQuery .= " $whereClause";
$query = "$baseQuery $whereClause ORDER BY created_at DESC LIMIT ?, ?";

// 创建计数查询的参数和类型(不包含分页)
$countParams = $params;
$countTypes = $types;

// 为列表查询添加分页参数
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

// 执行统计查询
try {
    if (!empty($countParams)) {
        $countStmt = $conn->prepare($countQuery);
        if ($countStmt === false) {
            throw new Exception("准备计数查询失败: " . $conn->error);
        }
        $countStmt->bind_param($countTypes, ...$countParams);
        if (!$countStmt->execute()) {
            throw new Exception("执行计数查询失败: " . $countStmt->error);
        }
        $totalResult = $countStmt->get_result();
        $total = $totalResult->fetch_assoc()['total'];
        $countStmt->close();
    } else {
        $totalResult = $conn->query($countQuery);
        if ($totalResult === false) {
            throw new Exception("计数查询执行失败: " . $conn->error);
        }
        $total = $totalResult->fetch_assoc()['total'];
    }
} catch (Exception $e) {
    error_log("统计查询错误: " . $e->getMessage());
    $total = 0;
}

// 计算总页数
$totalPages = ceil($total / $limit);

// 执行日志列表查询
try {
    if (!empty($params)) {
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("准备查询失败: " . $conn->error);
        }
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            throw new Exception("执行查询失败: " . $stmt->error);
        }
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
        if ($result === false) {
            throw new Exception("日志列表查询失败: " . $conn->error);
        }
    }
} catch (Exception $e) {
    error_log("日志列表查询错误: " . $e->getMessage());
    $result = false;
}

// 如果查询失败，创建一个空结果集
if (!$result) {
    $result = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// 获取管理员列表（用于筛选）
try {
    $adminQuery = "SELECT id, username FROM admins ORDER BY username";
    $adminResult = $conn->query($adminQuery);
    if ($adminResult === false) {
        throw new Exception("查询管理员列表失败: " . $conn->error);
    }
    $adminList = [];
    while ($admin = $adminResult->fetch_assoc()) {
        $adminList[] = $admin;
    }
} catch (Exception $e) {
    error_log("获取管理员列表错误: " . $e->getMessage());
    $adminList = [];
}

// 获取模块列表（用于筛选）
try {
    $moduleQuery = "SELECT DISTINCT module FROM admin_operation_logs ORDER BY module";
    $moduleResult = $conn->query($moduleQuery);
    if ($moduleResult === false) {
        throw new Exception("查询模块列表失败: " . $conn->error);
    }
    $moduleList = [];
    while ($moduleRow = $moduleResult->fetch_assoc()) {
        $moduleList[] = $moduleRow['module'];
    }
} catch (Exception $e) {
    error_log("获取模块列表错误: " . $e->getMessage());
    $moduleList = [];
}

// 检查系统错误日志表是否存在
$errorLogTableExists = false;
try {
    $checkTableQuery = "SHOW TABLES LIKE 'system_error_logs'";
    $tableCheck = $conn->query($checkTableQuery);
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $errorLogTableExists = true;
    }
} catch (Exception $e) {
    error_log("检查错误日志表错误: " . $e->getMessage());
}

// 检查管理员登录日志表是否存在
$loginLogTableExists = false;
try {
    $checkTableQuery = "SHOW TABLES LIKE 'admin_login_logs'";
    $tableCheck = $conn->query($checkTableQuery);
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $loginLogTableExists = true;
    }
} catch (Exception $e) {
    error_log("检查登录日志表错误: " . $e->getMessage());
}
?>

<!-- 内容区域 -->
<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">控制台</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <!-- 显示消息提示 -->
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <!-- 日志切换选项卡 -->
    <div class="mb-4">
        <ul class="nav nav-tabs">
            <li class="nav-item">
                <a class="nav-link <?php echo $logType == 'operation' ? 'active' : ''; ?>" href="?type=operation">操作日志</a>
            </li>
            <?php if ($errorLogTableExists): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $logType == 'error' ? 'active' : ''; ?>" href="?type=error">错误日志</a>
            </li>
            <?php endif; ?>
            <?php if ($loginLogTableExists): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $logType == 'login' ? 'active' : ''; ?>" href="?type=login">登录日志</a>
            </li>
            <?php endif; ?>
        </ul>
    </div>
    
    <!-- 搜索和筛选 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-search me-1"></i>
            搜索和筛选
        </div>
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <input type="hidden" name="type" value="<?php echo $logType; ?>">
                
                <div class="col-md-3">
                    <label for="search" class="form-label">关键词搜索</label>
                    <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="搜索内容...">
                </div>
                
                <div class="col-md-2">
                    <label for="start_date" class="form-label">开始日期</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                </div>
                
                <div class="col-md-2">
                    <label for="end_date" class="form-label">结束日期</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                </div>
                
                <?php if ($logType == 'operation' || $logType == 'login'): ?>
                <div class="col-md-2">
                    <label for="admin_id" class="form-label">管理员</label>
                    <select class="form-select" id="admin_id" name="admin_id">
                        <option value="0">全部管理员</option>
                        <?php foreach ($adminList as $adminItem): ?>
                        <option value="<?php echo $adminItem['id']; ?>" <?php echo $admin_id == $adminItem['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($adminItem['username']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <?php if ($logType == 'operation'): ?>
                <div class="col-md-2">
                    <label for="module" class="form-label">模块</label>
                    <select class="form-select" id="module" name="module">
                        <option value="">全部模块</option>
                        <?php foreach ($moduleList as $moduleItem): ?>
                        <option value="<?php echo $moduleItem; ?>" <?php echo $module == $moduleItem ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($moduleItem); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">筛选</button>
                </div>
                
                <div class="col-md-1">
                    <a href="?type=<?php echo $logType; ?>" class="btn btn-secondary w-100">重置</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 日志列表 -->
    <div class="card mb-4">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-list me-1"></i>
                    <?php 
                    if ($logType == 'operation') echo '操作日志列表';
                    elseif ($logType == 'error') echo '错误日志列表';
                    elseif ($logType == 'login') echo '登录日志列表';
                    ?>
                </div>
                <div>
                    总共 <?php echo $total; ?> 条记录
                </div>
            </div>
        </div>
        <div class="card-body">
            <?php if ($total > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <?php if ($logType == 'operation' || $logType == 'login'): ?>
                            <th>管理员</th>
                            <?php endif; ?>
                            <?php if ($logType == 'operation'): ?>
                            <th>模块</th>
                            <th>操作</th>
                            <th>描述</th>
                            <?php elseif ($logType == 'error'): ?>
                            <th>错误类型</th>
                            <th>错误信息</th>
                            <th>文件</th>
                            <th>行号</th>
                            <?php elseif ($logType == 'login'): ?>
                            <th>状态</th>
                            <th>IP地址</th>
                            <th>用户代理</th>
                            <?php endif; ?>
                            <th>时间</th>
                            <?php if ($logType == 'error'): ?>
                            <th>操作</th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <?php if ($logType == 'operation' || $logType == 'login'): ?>
                            <td><?php echo htmlspecialchars($row['username'] ?? '未知'); ?></td>
                            <?php endif; ?>
                            <?php if ($logType == 'operation'): ?>
                            <td><?php echo htmlspecialchars($row['module']); ?></td>
                            <td><?php echo htmlspecialchars($row['action']); ?></td>
                            <td><?php echo htmlspecialchars($row['description']); ?></td>
                            <?php elseif ($logType == 'error'): ?>
                            <td><?php echo htmlspecialchars($row['type']); ?></td>
                            <td><?php echo htmlspecialchars($row['message']); ?></td>
                            <td><?php echo htmlspecialchars($row['file']); ?></td>
                            <td><?php echo $row['line']; ?></td>
                            <?php elseif ($logType == 'login'): ?>
                            <td>
                                <?php if ($row['status'] == 'success'): ?>
                                <span class="badge bg-success">成功</span>
                                <?php else: ?>
                                <span class="badge bg-danger">失败</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($row['ip']); ?></td>
                            <td class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($row['user_agent']); ?>">
                                <?php echo htmlspecialchars($row['user_agent']); ?>
                            </td>
                            <?php endif; ?>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?></td>
                            <?php if ($logType == 'error'): ?>
                            <td>
                                <button type="button" class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#errorDetail<?php echo $row['id']; ?>">
                                    详情
                                </button>
                                
                                <!-- 错误详情模态框 -->
                                <div class="modal fade" id="errorDetail<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="errorDetailLabel<?php echo $row['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="errorDetailLabel<?php echo $row['id']; ?>">错误详情 #<?php echo $row['id']; ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <div class="mb-3">
                                                    <strong>错误类型:</strong> <?php echo htmlspecialchars($row['type']); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>错误信息:</strong> <?php echo htmlspecialchars($row['message']); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>文件:</strong> <?php echo htmlspecialchars($row['file']); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>行号:</strong> <?php echo $row['line']; ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>错误时间:</strong> <?php echo date('Y-m-d H:i:s', strtotime($row['created_at'])); ?>
                                                </div>
                                                <div class="mb-3">
                                                    <strong>堆栈跟踪:</strong>
                                                    <pre class="border p-3 bg-light"><?php echo htmlspecialchars($row['trace']); ?></pre>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 分页 -->
            <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-center mt-4">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?type=<?php echo $logType; ?>&page=1<?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($startDate) ? '&start_date=' . urlencode($startDate) : ''; ?><?php echo !empty($endDate) ? '&end_date=' . urlencode($endDate) : ''; ?><?php echo $admin_id > 0 ? '&admin_id=' . $admin_id : ''; ?><?php echo !empty($module) ? '&module=' . urlencode($module) : ''; ?>" aria-label="首页">
                                <span aria-hidden="true">&laquo;&laquo;</span>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?type=<?php echo $logType; ?>&page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($startDate) ? '&start_date=' . urlencode($startDate) : ''; ?><?php echo !empty($endDate) ? '&end_date=' . urlencode($endDate) : ''; ?><?php echo $admin_id > 0 ? '&admin_id=' . $admin_id : ''; ?><?php echo !empty($module) ? '&module=' . urlencode($module) : ''; ?>" aria-label="上一页">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, min($page - 2, $totalPages - 4));
                        $endPage = min($totalPages, max($page + 2, 5));
                        
                        for ($i = $startPage; $i <= $endPage; $i++) {
                        ?>
                        <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?type=<?php echo $logType; ?>&page=<?php echo $i; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($startDate) ? '&start_date=' . urlencode($startDate) : ''; ?><?php echo !empty($endDate) ? '&end_date=' . urlencode($endDate) : ''; ?><?php echo $admin_id > 0 ? '&admin_id=' . $admin_id : ''; ?><?php echo !empty($module) ? '&module=' . urlencode($module) : ''; ?>"><?php echo $i; ?></a>
                        </li>
                        <?php } ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?type=<?php echo $logType; ?>&page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($startDate) ? '&start_date=' . urlencode($startDate) : ''; ?><?php echo !empty($endDate) ? '&end_date=' . urlencode($endDate) : ''; ?><?php echo $admin_id > 0 ? '&admin_id=' . $admin_id : ''; ?><?php echo !empty($module) ? '&module=' . urlencode($module) : ''; ?>" aria-label="下一页">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <li class="page-item">
                            <a class="page-link" href="?type=<?php echo $logType; ?>&page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search=' . urlencode($search) : ''; ?><?php echo !empty($startDate) ? '&start_date=' . urlencode($startDate) : ''; ?><?php echo !empty($endDate) ? '&end_date=' . urlencode($endDate) : ''; ?><?php echo $admin_id > 0 ? '&admin_id=' . $admin_id : ''; ?><?php echo !empty($module) ? '&module=' . urlencode($module) : ''; ?>" aria-label="末页">
                                <span aria-hidden="true">&raquo;&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
            
            <?php else: ?>
            <div class="alert alert-info">暂无日志记录</div>
            <?php endif; ?>
        </div>
    </div>
    
    <?php if ($logType == 'error'): ?>
    <!-- 清理错误日志按钮 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-broom me-1"></i>
            日志管理
        </div>
        <div class="card-body">
            <form method="post" action="log_action.php" onsubmit="return confirm('确定要清理日志吗？此操作不可恢复！')">
                <input type="hidden" name="action" value="clear_error_logs">
                <input type="hidden" name="log_type" value="<?php echo $logType; ?>">
                <div class="input-group">
                    <select class="form-select" name="days" style="max-width: 200px;">
                        <option value="7">7天前的日志</option>
                        <option value="15">15天前的日志</option>
                        <option value="30" selected>30天前的日志</option>
                        <option value="90">90天前的日志</option>
                    </select>
                    <button type="submit" class="btn btn-warning">清理选定日志</button>
                </div>
            </form>
        </div>
    </div>
    <?php elseif ($logType == 'login'): ?>
    <!-- 清理登录日志按钮 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-broom me-1"></i>
            日志管理
        </div>
        <div class="card-body">
            <form method="post" action="log_action.php" onsubmit="return confirm('确定要清理日志吗？此操作不可恢复！')">
                <input type="hidden" name="action" value="clear_login_logs">
                <input type="hidden" name="log_type" value="<?php echo $logType; ?>">
                <div class="input-group">
                    <select class="form-select" name="days" style="max-width: 200px;">
                        <option value="7">7天前的日志</option>
                        <option value="15">15天前的日志</option>
                        <option value="30" selected>30天前的日志</option>
                        <option value="90">90天前的日志</option>
                    </select>
                    <button type="submit" class="btn btn-warning">清理选定日志</button>
                </div>
            </form>
        </div>
    </div>
    <?php elseif ($logType == 'operation'): ?>
    <!-- 清理操作日志按钮 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-broom me-1"></i>
            日志管理
        </div>
        <div class="card-body">
            <form method="post" action="log_action.php" onsubmit="return confirm('确定要清理日志吗？此操作不可恢复！')">
                <input type="hidden" name="action" value="clear_operation_logs">
                <input type="hidden" name="log_type" value="<?php echo $logType; ?>">
                <div class="input-group">
                    <select class="form-select" name="days" style="max-width: 200px;">
                        <option value="30">30天前的日志</option>
                        <option value="60">60天前的日志</option>
                        <option value="90" selected>90天前的日志</option>
                        <option value="180">180天前的日志</option>
                    </select>
                    <button type="submit" class="btn btn-warning">清理选定日志</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// 日期选择器增强
document.addEventListener('DOMContentLoaded', function() {
    const startDate = document.getElementById('start_date');
    const endDate = document.getElementById('end_date');
    
    // 确保结束日期不早于开始日期
    if (startDate && endDate) {
        startDate.addEventListener('change', function() {
            if (endDate.value && new Date(endDate.value) < new Date(startDate.value)) {
                endDate.value = startDate.value;
            }
        });
        
        endDate.addEventListener('change', function() {
            if (startDate.value && new Date(endDate.value) < new Date(startDate.value)) {
                startDate.value = endDate.value;
            }
        });
    }
});
</script>

<?php
// 引入底部
require_once 'includes/footer.php';
?> 