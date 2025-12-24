<?php
// 开启输出缓冲，避免headers already sent错误
ob_start();

// 页面标题和操作按钮
$pageTitle = '评论管理';

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
$bottleId = isset($_GET['bottle_id']) ? (int)$_GET['bottle_id'] : 0;

// 处理删除操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 删除单个评论
    if (isset($_POST['confirm_delete']) && $action === 'delete' && $id > 0) {
        $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // 记录管理员操作
            $admin->logOperation('评论', '删除', "删除评论ID: $id");
            
            $message = '评论删除成功！';
            $messageType = 'success';
            
            // 重定向到列表页
            header("Location: comments.php?message=" . urlencode($message) . "&type=$messageType");
            exit;
        } else {
            $message = '评论删除失败: ' . $conn->error;
            $messageType = 'danger';
        }
        
        $stmt->close();
    }
    // 批量删除评论
    elseif (isset($_POST['confirm_delete_all']) && isset($_POST['bottle_id'])) {
        $bottleId = (int)$_POST['bottle_id'];
        
        $stmt = $conn->prepare("DELETE FROM comments WHERE bottle_id = ?");
        $stmt->bind_param("i", $bottleId);
        
        if ($stmt->execute()) {
            // 获取删除的行数
            $deletedCount = $stmt->affected_rows;
            
            // 记录管理员操作
            $admin->logOperation('评论', '批量删除', "删除漂流瓶ID: $bottleId 的所有评论，共 $deletedCount 条");
            
            $message = "成功删除 $deletedCount 条评论！";
            $messageType = 'success';
            
            // 重定向到漂流瓶详情页或评论列表
            if ($bottleId > 0) {
                header("Location: bottle_detail.php?id=$bottleId&message=" . urlencode($message) . "&type=$messageType");
            } else {
                header("Location: comments.php?message=" . urlencode($message) . "&type=$messageType");
            }
            exit;
        } else {
            $message = '批量删除评论失败: ' . $conn->error;
            $messageType = 'danger';
        }
        
        $stmt->close();
    }
}
// 处理GET请求的删除操作
elseif ($action === 'delete_all' && $bottleId > 0) {
    // 获取漂流瓶信息
    $bottleStmt = $conn->prepare("SELECT id, content FROM bottles WHERE id = ?");
    $bottleStmt->bind_param("i", $bottleId);
    $bottleStmt->execute();
    $bottleResult = $bottleStmt->get_result();
    
    if ($bottleResult->num_rows > 0) {
        $bottle = $bottleResult->fetch_assoc();
    } else {
        $message = '漂流瓶不存在！';
        $messageType = 'danger';
    }
    
    $bottleStmt->close();
}
// 处理单个评论删除
elseif ($action === 'delete' && $id > 0) {
    // 获取评论信息
    $stmt = $conn->prepare("SELECT c.id, c.content, c.bottle_id, u.username 
                           FROM comments c 
                           LEFT JOIN users u ON c.user_id = u.id 
                           WHERE c.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $deleteComment = $result->fetch_assoc();
    } else {
        $message = '评论不存在！';
        $messageType = 'danger';
    }
    
    $stmt->close();
}

// 从URL参数获取消息
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'] ?? 'info';
}

// 处理搜索和分页
$search = sanitizeInput($_GET['search'] ?? '');
$bottleFilter = isset($_GET['bottle_id']) ? (int)$_GET['bottle_id'] : 0;
$userIdFilter = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 15;
$offset = ($page - 1) * $limit;

// 构建查询条件
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(c.content LIKE ? OR u.username LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

if ($bottleFilter > 0) {
    $where[] = "c.bottle_id = ?";
    $params[] = $bottleFilter;
    $types .= 'i';
}

// 构建最终的WHERE子句
$whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';

// 准备查询语句
$countQuery = "
    SELECT COUNT(*) as total 
    FROM comments c 
    LEFT JOIN users u ON c.user_id = u.id 
    $whereClause
";

$query = "
    SELECT c.*, u.username as user_name, b.content as bottle_content 
    FROM comments c 
    LEFT JOIN users u ON c.user_id = u.id 
    LEFT JOIN bottles b ON c.bottle_id = b.id 
    $whereClause 
    ORDER BY c.created_at DESC 
    LIMIT ?, ?
";

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

// 查询评论列表
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
        error_log("评论列表查询错误: " . $e->getMessage());
        $result = false;
    }
} else {
    $result = $conn->query($query);
    if ($result === false) {
        error_log("评论列表查询失败: " . $conn->error);
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
$admin->logOperation('评论', '查看', '查看评论列表');
?>

<!-- 内容区域 -->
<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?><?php if ($userIdFilter > 0): ?> - 用户ID: <?php echo $userIdFilter; ?><?php endif; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">控制台</a></li>
        <li class="breadcrumb-item"><a href="comments.php">评论管理</a></li>
        <?php if ($userIdFilter > 0): ?>
        <li class="breadcrumb-item"><a href="user_detail.php?id=<?php echo $userIdFilter; ?>">用户详情</a></li>
        <?php endif; ?>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($action === 'delete' && isset($deleteComment)): ?>
    <!-- 删除评论确认页面 -->
    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white">
            <i class="fas fa-trash me-1"></i>
            删除评论
        </div>
        <div class="card-body">
            <p class="lead">您确定要删除以下评论吗？</p>
            <p><strong>评论ID：</strong> <?php echo $deleteComment['id']; ?></p>
            <p><strong>发布者：</strong> <?php echo htmlspecialchars($deleteComment['username'] ?? '未知用户'); ?></p>
            <p><strong>评论内容：</strong> <?php echo htmlspecialchars($deleteComment['content']); ?></p>
            <p class="text-danger">此操作不可撤销！</p>
            
            <form method="post" action="comments.php?action=delete&id=<?php echo $id; ?>">
                <button type="submit" name="confirm_delete" class="btn btn-danger">确认删除</button>
                <?php if ($deleteComment['bottle_id']): ?>
                <a href="bottle_detail.php?id=<?php echo $deleteComment['bottle_id']; ?>" class="btn btn-primary">查看关联漂流瓶</a>
                <?php endif; ?>
                <a href="comments.php" class="btn btn-secondary">返回列表</a>
            </form>
        </div>
    </div>
    
    <?php elseif ($action === 'delete_all' && isset($bottle)): ?>
    <!-- 批量删除评论确认页面 -->
    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white">
            <i class="fas fa-trash me-1"></i>
            批量删除评论
        </div>
        <div class="card-body">
            <p class="lead">您确定要删除此漂流瓶的所有评论吗？</p>
            <p><strong>漂流瓶ID：</strong> <?php echo $bottle['id']; ?></p>
            <p><strong>漂流瓶内容：</strong> <?php echo htmlspecialchars(mb_substr($bottle['content'], 0, 100)) . (mb_strlen($bottle['content']) > 100 ? '...' : ''); ?></p>
            <p class="text-danger">此操作不可撤销！</p>
            
            <form method="post" action="comments.php">
                <input type="hidden" name="bottle_id" value="<?php echo $bottle['id']; ?>">
                <button type="submit" name="confirm_delete_all" class="btn btn-danger">确认删除所有评论</button>
                <a href="bottle_detail.php?id=<?php echo $bottle['id']; ?>" class="btn btn-primary">查看漂流瓶</a>
                <a href="comments.php" class="btn btn-secondary">返回列表</a>
            </form>
        </div>
    </div>
    
    <?php else: ?>
    <!-- 搜索表单 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="comments.php" class="row g-3">
                <?php if ($userIdFilter > 0): ?>
                <input type="hidden" name="user_id" value="<?php echo $userIdFilter; ?>">
                <?php endif; ?>
                <div class="col-md-6">
                    <label for="search" class="form-label">搜索</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="评论内容/用户名" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-md-4">
                    <label for="bottle_id" class="form-label">漂流瓶ID</label>
                    <input type="number" class="form-control" id="bottle_id" name="bottle_id" placeholder="仅显示特定漂流瓶的评论" value="<?php echo $bottleFilter ? $bottleFilter : ''; ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-grid gap-2 w-100">
                        <button type="submit" class="btn btn-primary">搜索</button>
                        <a href="comments.php" class="btn btn-secondary">重置</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 评论列表 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-comments me-1"></i>
            评论列表
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>评论内容</th>
                            <th width="300">漂流瓶内容</th>
                            <th width="120">发布者</th>
                            <th width="120">IP地址</th>
                            <th width="150">发布时间</th>
                            <th width="120">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($comment = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $comment['id']; ?></td>
                                <td><?php echo htmlspecialchars($comment['content']); ?></td>
                                <td>
                                    <a href="bottle_detail.php?id=<?php echo $comment['bottle_id']; ?>" class="text-decoration-none">
                                        <?php 
                                        $bottleContent = htmlspecialchars($comment['bottle_content'] ?? '');
                                        echo mb_strlen($bottleContent) > 50 ? mb_substr($bottleContent, 0, 50) . '...' : $bottleContent; 
                                        ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($comment['user_name'] ?? '未知用户'); ?></td>
                                <td><?php echo htmlspecialchars($comment['ip_address'] ?? '未记录'); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></td>
                                <td>
                                    <a href="bottle_detail.php?id=<?php echo $comment['bottle_id']; ?>#comment-<?php echo $comment['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> 查看
                                    </a>
                                    <a href="comments.php?action=delete&id=<?php echo $comment['id']; ?>" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> 删除
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">没有找到评论</td>
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&bottle_id=<?php echo $bottleFilter; ?>">上一页</a>
                    </li>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&bottle_id=<?php echo $bottleFilter; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&bottle_id=<?php echo $bottleFilter; ?>">下一页</a>
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