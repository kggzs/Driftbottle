<?php
// 开启输出缓冲，避免headers already sent错误
ob_start();

// 页面标题和动作按钮
$pageTitle = '公告管理';
$pageActions = '<a href="announcements.php?action=add" class="btn btn-sm btn-primary"><i class="bi bi-plus"></i> 添加公告</a>';

// 引入头部
require_once 'includes/header.php';

// 初始化数据库连接
$conn = getDbConnection();

// 处理消息提示
$message = '';
$messageType = '';

// 从URL获取操作类型和ID
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 添加或编辑公告
    if (isset($_POST['save_announcement'])) {
        // 获取表单数据
        $title = sanitizeInput($_POST['title'] ?? '');
        $content = sanitizeInput($_POST['content'] ?? '');
        $type = sanitizeInput($_POST['type'] ?? '普通');
        $status = isset($_POST['status']) ? 1 : 0;
        
        // 验证数据
        $errors = [];
        
        if (empty($title)) {
            $errors[] = '标题不能为空';
        }
        
        if (empty($content)) {
            $errors[] = '内容不能为空';
        }
        
        if (!in_array($type, ['普通', '重要', '紧急'])) {
            $errors[] = '类型无效';
        }
        
        // 处理日期时间格式
        if (!empty($_POST['start_time']) && strtotime($_POST['start_time']) === false) {
            $errors[] = '开始时间格式不正确';
        }
        
        if (!empty($_POST['end_time']) && strtotime($_POST['end_time']) === false) {
            $errors[] = '结束时间格式不正确';
        }
        
        $start_time = !empty($_POST['start_time']) && strtotime($_POST['start_time']) !== false 
                      ? date('Y-m-d H:i:s', strtotime($_POST['start_time'])) 
                      : null;
                      
        $end_time = !empty($_POST['end_time']) && strtotime($_POST['end_time']) !== false 
                    ? date('Y-m-d H:i:s', strtotime($_POST['end_time'])) 
                    : null;
        
        // 如果没有错误，保存公告
        if (empty($errors)) {
            // 检查是添加还是更新
            if ($action === 'edit' && $id > 0) {
                // 更新公告
                $stmt = $conn->prepare("UPDATE announcements SET 
                                      title = ?, 
                                      content = ?, 
                                      type = ?, 
                                      status = ?,
                                      start_time = ?,
                                      end_time = ?
                                      WHERE id = ?");
                
                $stmt->bind_param("sssisii", $title, $content, $type, $status, $start_time, $end_time, $id);
                
                if ($stmt->execute()) {
                    // 记录管理员操作
                    $admin->logOperation('公告', '编辑', "编辑公告ID: $id, 标题: $title");
                    
                    $message = '公告更新成功！';
                    $messageType = 'success';
                    
                    // 重定向到列表页
                    header("Location: announcements.php?message=" . urlencode($message) . "&type=$messageType");
                    exit;
                } else {
                    $message = '公告更新失败: ' . $conn->error;
                    $messageType = 'danger';
                }
            } else {
                // 添加公告
                $admin_id = $admin->getCurrentAdminId();
                
                $stmt = $conn->prepare("INSERT INTO announcements 
                                      (title, content, type, status, start_time, end_time, created_by) 
                                      VALUES (?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->bind_param("sssisii", $title, $content, $type, $status, $start_time, $end_time, $admin_id);
                
                if ($stmt->execute()) {
                    $newId = $conn->insert_id;
                    
                    // 记录管理员操作
                    $admin->logOperation('公告', '添加', "添加公告ID: $newId, 标题: $title");
                    
                    $message = '公告添加成功！';
                    $messageType = 'success';
                    
                    // 重定向到列表页
                    header("Location: announcements.php?message=" . urlencode($message) . "&type=$messageType");
                    exit;
                } else {
                    $message = '公告添加失败: ' . $conn->error;
                    $messageType = 'danger';
                }
            }
            
            $stmt->close();
        } else {
            $message = implode('<br>', $errors);
            $messageType = 'danger';
        }
    }
    
    // 删除公告
    if (isset($_POST['confirm_delete']) && $action === 'delete' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // 记录管理员操作
            $admin->logOperation('公告', '删除', "删除公告ID: $id");
            
            $message = '公告删除成功！';
            $messageType = 'success';
            
            // 重定向到列表页
            header("Location: announcements.php?message=" . urlencode($message) . "&type=$messageType");
            exit;
        } else {
            $message = '公告删除失败: ' . $conn->error;
            $messageType = 'danger';
        }
        
        $stmt->close();
    }
}
// 处理切换状态操作
else if ($action === 'toggle_status' && $id > 0) {
    // 获取当前状态
    $stmt = $conn->prepare("SELECT status FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $currentStatus = $result->fetch_assoc()['status'];
        $newStatus = $currentStatus ? 0 : 1;
        
        // 更新状态
        $updateStmt = $conn->prepare("UPDATE announcements SET status = ? WHERE id = ?");
        $updateStmt->bind_param("ii", $newStatus, $id);
        
        if ($updateStmt->execute()) {
            // 记录管理员操作
            $statusText = $newStatus ? '显示' : '隐藏';
            $admin->logOperation('公告', '修改状态', "公告ID: $id, 新状态: $statusText");
            
            $message = "公告已设为" . $statusText . "！";
            $messageType = 'success';
            
            // 重定向到列表页
            header("Location: announcements.php?message=" . urlencode($message) . "&type=$messageType");
            exit;
        } else {
            $message = '状态更新失败: ' . $conn->error;
            $messageType = 'danger';
        }
        
        $updateStmt->close();
    } else {
        $message = '公告不存在！';
        $messageType = 'danger';
    }
    
    $stmt->close();
}

// 从URL参数获取消息
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'] ?? 'info';
}

// 初始化编辑的公告数据
$editAnnouncement = null;

// 如果是编辑操作，获取公告数据
if ($action === 'edit' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $editAnnouncement = $result->fetch_assoc();
    } else {
        $message = '公告不存在！';
        $messageType = 'danger';
    }
    
    $stmt->close();
}

// 如果是删除操作，获取公告数据用于确认
$deleteAnnouncement = null;

if ($action === 'delete' && $id > 0) {
    $stmt = $conn->prepare("SELECT title FROM announcements WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $deleteAnnouncement = $result->fetch_assoc();
    } else {
        $message = '公告不存在！';
        $messageType = 'danger';
    }
    
    $stmt->close();
}

// 处理搜索和分页
$search = sanitizeInput($_GET['search'] ?? '');
$type = sanitizeInput($_GET['type'] ?? '');
$status = isset($_GET['status']) ? (int)$_GET['status'] : -1;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 构建查询条件
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(title LIKE ? OR content LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

if (!empty($type)) {
    $where[] = "type = ?";
    $params[] = $type;
    $types .= 's';
}

if ($status >= 0) {
    $where[] = "status = ?";
    $params[] = $status;
    $types .= 'i';
}

// 构建最终的WHERE子句
$whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';

// 准备查询语句
$countQuery = "SELECT COUNT(*) as total FROM announcements $whereClause";
$query = "SELECT a.*, CONCAT(u.username) as admin_name 
          FROM announcements a 
          LEFT JOIN admins u ON a.created_by = u.id 
          $whereClause 
          ORDER BY a.id DESC 
          LIMIT ?, ?";

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
        error_log("统计查询错误: " . $e->getMessage());
        $total = 0;
    }
} else {
    $totalResult = $conn->query($countQuery);
    if ($totalResult === false) {
        error_log("计数查询执行失败: " . $conn->error);
        $total = 0;
    } else {
        $total = $totalResult->fetch_assoc()['total'];
    }
}

// 计算总页数
$totalPages = ceil($total / $limit);

// 查询公告列表
if (!empty($params)) {
    try {
        $stmt = $conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("准备查询失败: " . $conn->error);
        }
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } catch (Exception $e) {
        error_log("公告列表查询错误: " . $e->getMessage());
        $result = false;
    }
} else {
    $result = $conn->query($query);
    if ($result === false) {
        error_log("公告列表查询失败: " . $conn->error);
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
$admin->logOperation('公告', '查看', '查看公告列表');
?>

<!-- 内容区域 -->
<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">控制台</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($action === 'add' || ($action === 'edit' && $editAnnouncement)): ?>
    <!-- 添加/编辑公告表单 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-bullhorn me-1"></i>
            <?php echo $action === 'add' ? '添加公告' : '编辑公告'; ?>
        </div>
        <div class="card-body">
            <form method="post" action="announcements.php?action=<?php echo $action; ?><?php echo $action === 'edit' ? '&id=' . $id : ''; ?>">
                <div class="mb-3">
                    <label for="title" class="form-label">标题 <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="title" name="title" required 
                           value="<?php echo $editAnnouncement ? htmlspecialchars($editAnnouncement['title']) : ''; ?>">
                </div>
                
                <div class="mb-3">
                    <label for="content" class="form-label">内容 <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="content" name="content" rows="6" required><?php echo $editAnnouncement ? htmlspecialchars($editAnnouncement['content']) : ''; ?></textarea>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="type" class="form-label">类型</label>
                        <select class="form-select" id="type" name="type">
                            <option value="普通" <?php echo ($editAnnouncement && $editAnnouncement['type'] === '普通') ? 'selected' : ''; ?>>普通</option>
                            <option value="重要" <?php echo ($editAnnouncement && $editAnnouncement['type'] === '重要') ? 'selected' : ''; ?>>重要</option>
                            <option value="紧急" <?php echo ($editAnnouncement && $editAnnouncement['type'] === '紧急') ? 'selected' : ''; ?>>紧急</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="start_time" class="form-label">开始时间 <small class="text-muted">(可选)</small></label>
                        <input type="datetime-local" class="form-control" id="start_time" name="start_time" 
                               value="<?php echo $editAnnouncement && $editAnnouncement['start_time'] ? date('Y-m-d\TH:i', strtotime($editAnnouncement['start_time'])) : ''; ?>">
                        <div class="form-text">设置公告开始生效的时间</div>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="end_time" class="form-label">结束时间 <small class="text-muted">(可选)</small></label>
                        <input type="datetime-local" class="form-control" id="end_time" name="end_time" 
                               value="<?php echo $editAnnouncement && $editAnnouncement['end_time'] ? date('Y-m-d\TH:i', strtotime($editAnnouncement['end_time'])) : ''; ?>">
                        <div class="form-text">设置公告自动下线的时间</div>
                    </div>
                </div>
                
                <div class="mb-3 form-check">
                    <input type="checkbox" class="form-check-input" id="status" name="status" 
                           <?php echo (!$editAnnouncement || ($editAnnouncement && $editAnnouncement['status'] == 1)) ? 'checked' : ''; ?>>
                    <label class="form-check-label" for="status">显示公告</label>
                </div>
                
                <div class="mt-4">
                    <button type="submit" name="save_announcement" class="btn btn-primary">保存公告</button>
                    <a href="announcements.php" class="btn btn-secondary">取消</a>
                </div>
            </form>
        </div>
    </div>
    
    <?php elseif ($action === 'delete' && $deleteAnnouncement): ?>
    <!-- 删除确认页面 -->
    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white">
            <i class="fas fa-trash me-1"></i>
            删除公告
        </div>
        <div class="card-body">
            <p class="lead">您确定要删除以下公告吗？</p>
            <p><strong>标题：</strong> <?php echo htmlspecialchars($deleteAnnouncement['title']); ?></p>
            <p class="text-danger">此操作不可撤销！</p>
            
            <form method="post" action="announcements.php?action=delete&id=<?php echo $id; ?>">
                <button type="submit" name="confirm_delete" class="btn btn-danger">确认删除</button>
                <a href="announcements.php" class="btn btn-secondary">取消</a>
            </form>
        </div>
    </div>
    
    <?php else: ?>
    <!-- 搜索表单 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="announcements.php" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">搜索</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="标题/内容" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="type" class="form-label">类型</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">全部</option>
                        <option value="普通" <?php echo $type === '普通' ? 'selected' : ''; ?>>普通</option>
                        <option value="重要" <?php echo $type === '重要' ? 'selected' : ''; ?>>重要</option>
                        <option value="紧急" <?php echo $type === '紧急' ? 'selected' : ''; ?>>紧急</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="status" class="form-label">状态</label>
                    <select class="form-select" id="status" name="status">
                        <option value="-1">全部</option>
                        <option value="1" <?php echo $status === 1 ? 'selected' : ''; ?>>显示</option>
                        <option value="0" <?php echo $status === 0 ? 'selected' : ''; ?>>隐藏</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-grid gap-2 w-100">
                        <button type="submit" class="btn btn-primary">搜索</button>
                        <a href="announcements.php" class="btn btn-secondary">重置</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 公告列表 -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <i class="fas fa-bullhorn me-1"></i>
                公告列表
            </div>
            <div>
                <?php echo $pageActions; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>标题</th>
                            <th>类型</th>
                            <th>状态</th>
                            <th>有效期</th>
                            <th>发布人</th>
                            <th>发布时间</th>
                            <th width="150">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($announcement = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $announcement['id']; ?></td>
                                <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                                <td>
                                    <?php 
                                    $typeBadgeClass = '';
                                    switch ($announcement['type']) {
                                        case '重要':
                                            $typeBadgeClass = 'bg-warning text-dark';
                                            break;
                                        case '紧急':
                                            $typeBadgeClass = 'bg-danger';
                                            break;
                                        default:
                                            $typeBadgeClass = 'bg-info';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $typeBadgeClass; ?>">
                                        <?php echo $announcement['type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $announcement['status'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $announcement['status'] ? '显示' : '隐藏'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($announcement['start_time'] || $announcement['end_time']): ?>
                                    <small>
                                        <?php echo $announcement['start_time'] ? date('Y-m-d H:i', strtotime($announcement['start_time'])) : '不限制'; ?>
                                        至
                                        <?php echo $announcement['end_time'] ? date('Y-m-d H:i', strtotime($announcement['end_time'])) : '不限制'; ?>
                                    </small>
                                    <?php else: ?>
                                    <small class="text-muted">长期有效</small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($announcement['admin_name']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($announcement['created_at'])); ?></td>
                                <td>
                                    <a href="announcement_detail.php?id=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> 查看
                                    </a>
                                    <a href="announcements.php?action=edit&id=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> 编辑
                                    </a>
                                    <?php if ($announcement['status']): ?>
                                    <a href="announcements.php?action=toggle_status&id=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-eye-slash"></i> 隐藏
                                    </a>
                                    <?php else: ?>
                                    <a href="announcements.php?action=toggle_status&id=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-eye"></i> 显示
                                    </a>
                                    <?php endif; ?>
                                    <a href="announcements.php?action=delete&id=<?php echo $announcement['id']; ?>" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> 删除
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">没有找到公告</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- 分页 -->
            <?php if ($totalPages > 1): ?>
            <nav>
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&status=<?php echo $status; ?>">上一页</a>
                    </li>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&status=<?php echo $status; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&type=<?php echo urlencode($type); ?>&status=<?php echo $status; ?>">下一页</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
        <div class="card-footer">
            <small class="text-muted">总记录数: <?php echo $total; ?></small>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php
// 关闭数据库连接
$conn->close();

// 引入底部
require_once 'includes/footer.php';
?> 