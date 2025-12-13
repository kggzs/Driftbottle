<?php
// 开启输出缓冲，避免headers already sent错误
ob_start();

// 错误处理函数
function handleError($errno, $errstr, $errfile, $errline) {
    error_log("错误: [$errno] $errstr - $errfile:$errline");
    
    // 检查是否是头部已发送错误
    if (strpos($errstr, 'headers already sent') !== false) {
        // 清空之前的输出缓冲
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        // 显示友好的错误消息
        echo '<div class="alert alert-danger">系统遇到问题，请检查应用日志获取详细信息。</div>';
        
        // 停止脚本进一步执行
        exit();
    }
    
    // 正常返回让PHP继续处理错误
    return false;
}

// 设置错误处理函数
set_error_handler('handleError', E_ALL);

// 页面标题和操作按钮
$pageTitle = '漂流瓶管理';

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

// 处理删除或状态变更操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 删除漂流瓶
    if (isset($_POST['confirm_delete']) && $action === 'delete' && $id > 0) {
        // 先查询漂流瓶信息，获取音频文件路径
        $selectStmt = $conn->prepare("SELECT audio_file FROM bottles WHERE id = ?");
        $selectStmt->bind_param("i", $id);
        $selectStmt->execute();
        $selectResult = $selectStmt->get_result();
        
        $audioFile = null;
        if ($selectResult->num_rows > 0) {
            $bottleData = $selectResult->fetch_assoc();
            $audioFile = $bottleData['audio_file'];
        }
        $selectStmt->close();
        
        // 删除数据库记录
        $stmt = $conn->prepare("DELETE FROM bottles WHERE id = ?");
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            // 删除语音文件（如果存在）
            if ($audioFile && !empty($audioFile)) {
                $audioFilePath = __DIR__ . '/../' . $audioFile;
                if (file_exists($audioFilePath)) {
                    @unlink($audioFilePath);
                }
            }
            
            // 同时删除相关评论
            $commentStmt = $conn->prepare("DELETE FROM comments WHERE bottle_id = ?");
            $commentStmt->bind_param("i", $id);
            $commentStmt->execute();
            $commentStmt->close();
            
            // 记录管理员操作
            $admin->logOperation('漂流瓶', '删除', "删除漂流瓶ID: $id" . ($audioFile ? "（包含语音文件）" : ""));
            
            $message = '漂流瓶删除成功！' . ($audioFile ? '（语音文件已删除）' : '');
            $messageType = 'success';
            
            // 重定向到列表页
            header("Location: bottles.php?message=" . urlencode($message) . "&type=$messageType");
            exit;
        } else {
            $message = '漂流瓶删除失败: ' . $conn->error;
            $messageType = 'danger';
        }
        
        $stmt->close();
    }
}
// 处理状态更改操作
else if ($action === 'toggle_status' && $id > 0) {
    // 获取当前状态
    $stmt = $conn->prepare("SELECT status FROM bottles WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $currentStatus = $result->fetch_assoc()['status'];
        
        // 确保使用正确的枚举值，与数据库定义一致
        $newStatus = '';
        if ($currentStatus === '漂流中') {
            $newStatus = '已捡起';
            $statusText = '隐藏';
        } else {
            $newStatus = '漂流中'; 
            $statusText = '显示';
        }
        
        // 更新状态
        $updateStmt = $conn->prepare("UPDATE bottles SET status = ? WHERE id = ?");
        $updateStmt->bind_param("si", $newStatus, $id);
        
        if ($updateStmt->execute()) {
            // 记录管理员操作
            $admin->logOperation('漂流瓶', '修改状态', "漂流瓶ID: $id, 新状态: $statusText");
            
            $message = "漂流瓶已设为" . $statusText . "！";
            $messageType = 'success';
            
            // 重定向到列表页
            header("Location: bottles.php?message=" . urlencode($message) . "&type=$messageType");
            exit;
        } else {
            $message = '状态更新失败: ' . $conn->error;
            $messageType = 'danger';
            error_log("状态更新失败: " . $conn->error . ", 尝试更新的值为: $newStatus");
        }
        
        $updateStmt->close();
    } else {
        $message = '漂流瓶不存在！';
        $messageType = 'danger';
    }
    
    $stmt->close();
}

// 从URL参数获取消息
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'] ?? 'info';
}

// 如果是删除操作，获取漂流瓶数据用于确认
$deleteBottle = null;

if ($action === 'delete' && $id > 0) {
    $stmt = $conn->prepare("SELECT b.id, b.content, b.bottle_type, b.audio_file, u.username 
                            FROM bottles b 
                            LEFT JOIN users u ON b.user_id = u.id 
                            WHERE b.id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $deleteBottle = $result->fetch_assoc();
    } else {
        $message = '漂流瓶不存在！';
        $messageType = 'danger';
    }
    
    $stmt->close();
}

// 处理搜索和分页
$search = sanitizeInput($_GET['search'] ?? '');
$status = sanitizeInput($_GET['status'] ?? '');
$mood = sanitizeInput($_GET['mood'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// 构建查询条件
$where = [];
$params = [];
$types = '';

if (!empty($search)) {
    $where[] = "(b.content LIKE ? OR u.username LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $types .= 'ss';
}

if (!empty($status)) {
    // 修改status字段的查询方式，根据SQL文件中的定义调整
    if ($status === 'active') {
        $where[] = "b.status = '漂流中'";
    } else if ($status === 'hidden') {
        $where[] = "b.status = '已捡起'";
    } else {
        $where[] = "b.status = ?";
        $params[] = $status;
        $types .= 's';
    }
}

if (!empty($mood)) {
    $where[] = "b.mood = ?";
    $params[] = $mood;
    $types .= 's';
}

// 构建最终的WHERE子句
$whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : '';

// 准备查询语句
$countQuery = "SELECT COUNT(*) as total FROM bottles b LEFT JOIN users u ON b.user_id = u.id $whereClause";
$query = "SELECT b.*, u.username, b.throw_time as created_at, 
          (SELECT COUNT(*) FROM comments c WHERE c.bottle_id = b.id) as comment_count 
          FROM bottles b 
          LEFT JOIN users u ON b.user_id = u.id 
          $whereClause 
          ORDER BY b.throw_time DESC 
          LIMIT ?, ?";

// 检查bottles表是否存在
$checkTableQuery = "SHOW TABLES LIKE 'bottles'";
$tableExists = $conn->query($checkTableQuery);

if ($tableExists === false || $tableExists->num_rows === 0) {
    // 表不存在，显示错误消息
    error_log("bottles表不存在，请先导入数据库结构");
    $message = "漂流瓶功能未完全设置，请先导入数据库结构（driftbottle.sql）。";
    $messageType = "danger";
    
    $total = 0;
    $totalPages = 0;
    $result = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
} else {
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
    
    // 查询漂流瓶列表
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
            error_log("漂流瓶列表查询错误: " . $e->getMessage());
            $result = false;
        }
    } else {
        $result = $conn->query($query);
        if ($result === false) {
            error_log("漂流瓶直接查询失败: " . $conn->error);
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
    
    // 获取漂流瓶心情选项
    $moodOptions = [];
    $moodQuery = "SELECT DISTINCT mood FROM bottles WHERE mood IS NOT NULL AND mood != ''";
    $moodResult = $conn->query($moodQuery);
    if ($moodResult && $moodResult->num_rows > 0) {
        while ($row = $moodResult->fetch_assoc()) {
            $moodOptions[] = $row['mood'];
        }
    }
}

// 记录管理员操作
$admin->logOperation('漂流瓶', '查看', '查看漂流瓶列表');
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
    
    <?php if ($action === 'delete' && $deleteBottle): ?>
    <!-- 删除确认页面 -->
    <div class="card mb-4 border-danger">
        <div class="card-header bg-danger text-white">
            <i class="fas fa-trash me-1"></i>
            删除漂流瓶
        </div>
        <div class="card-body">
            <p class="lead">您确定要删除以下漂流瓶吗？</p>
            <p><strong>ID：</strong> <?php echo $deleteBottle['id']; ?></p>
            <p><strong>发布者：</strong> <?php echo htmlspecialchars($deleteBottle['username'] ?? '未知用户'); ?></p>
            <p><strong>类型：</strong> 
                <?php if (isset($deleteBottle['bottle_type']) && $deleteBottle['bottle_type'] === 'voice'): ?>
                    <span class="badge bg-warning"><i class="fas fa-microphone"></i> 语音漂流瓶</span>
                <?php else: ?>
                    <span class="badge bg-info"><i class="fas fa-pen"></i> 文字漂流瓶</span>
                <?php endif; ?>
            </p>
            <p><strong>内容：</strong> <?php echo htmlspecialchars(mb_substr($deleteBottle['content'], 0, 100)) . (mb_strlen($deleteBottle['content']) > 100 ? '...' : ''); ?></p>
            <?php if (isset($deleteBottle['bottle_type']) && $deleteBottle['bottle_type'] === 'voice' && !empty($deleteBottle['audio_file'])): ?>
            <p class="text-warning"><i class="fas fa-exclamation-triangle"></i> 此漂流瓶包含语音文件，删除时将一并删除！</p>
            <?php endif; ?>
            <p class="text-danger">此操作不可撤销！相关评论也将被删除！</p>
            
            <form method="post" action="bottles.php?action=delete&id=<?php echo $id; ?>">
                <button type="submit" name="confirm_delete" class="btn btn-danger">确认删除</button>
                <a href="bottles.php" class="btn btn-secondary">取消</a>
            </form>
        </div>
    </div>
    
    <?php else: ?>
    <!-- 搜索表单 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="bottles.php" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">搜索</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="内容/用户名" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-md-3">
                    <label for="mood" class="form-label">心情</label>
                    <select class="form-select" id="mood" name="mood">
                        <option value="">全部</option>
                        <?php foreach ($moodOptions as $option): ?>
                        <option value="<?php echo htmlspecialchars($option); ?>" <?php echo $mood === $option ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($option); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="status" class="form-label">状态</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">全部</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>显示</option>
                        <option value="hidden" <?php echo $status === 'hidden' ? 'selected' : ''; ?>>隐藏</option>
                    </select>
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-grid gap-2 w-100">
                        <button type="submit" class="btn btn-primary">搜索</button>
                        <a href="bottles.php" class="btn btn-secondary">重置</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 漂流瓶列表 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-envelope me-1"></i>
            漂流瓶列表
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th>内容</th>
                            <th width="100">心情</th>
                            <th width="80">评论数</th>
                            <th width="100">状态</th>
                            <th width="120">发布者</th>
                            <th width="150">发布时间</th>
                            <th width="180">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($bottle = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $bottle['id']; ?></td>
                                <td>
                                    <?php 
                                    // 显示漂流瓶类型标识
                                    if (isset($bottle['bottle_type']) && $bottle['bottle_type'] === 'voice'): ?>
                                        <span class="badge bg-warning me-1"><i class="fas fa-microphone"></i> 语音</span>
                                    <?php endif;
                                    
                                    $content = htmlspecialchars($bottle['content']);
                                    echo mb_strlen($content) > 100 ? mb_substr($content, 0, 100) . '...' : $content; 
                                    ?>
                                </td>
                                <td>
                                    <?php if (isset($bottle['mood']) && $bottle['mood']): ?>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($bottle['mood']); ?>
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-secondary">其他</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo $bottle['comment_count']; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo ($bottle['status'] === '漂流中' || $bottle['status'] === 'active') ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo ($bottle['status'] === '漂流中' || $bottle['status'] === 'active') ? '显示' : '隐藏'; ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($bottle['username'] ?? '未知用户'); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($bottle['created_at'])); ?></td>
                                <td>
                                    <a href="bottle_detail.php?id=<?php echo $bottle['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="fas fa-eye"></i> 查看
                                    </a>
                                    <?php if ($bottle['status'] === '漂流中' || $bottle['status'] === 'active'): ?>
                                    <a href="bottles.php?action=toggle_status&id=<?php echo $bottle['id']; ?>" class="btn btn-sm btn-warning">
                                        <i class="fas fa-eye-slash"></i> 隐藏
                                    </a>
                                    <?php else: ?>
                                    <a href="bottles.php?action=toggle_status&id=<?php echo $bottle['id']; ?>" class="btn btn-sm btn-success">
                                        <i class="fas fa-eye"></i> 显示
                                    </a>
                                    <?php endif; ?>
                                    <a href="bottles.php?action=delete&id=<?php echo $bottle['id']; ?>" class="btn btn-sm btn-danger">
                                        <i class="fas fa-trash"></i> 删除
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center">没有找到漂流瓶</td>
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&mood=<?php echo urlencode($mood); ?>&status=<?php echo $status; ?>">上一页</a>
                    </li>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&mood=<?php echo urlencode($mood); ?>&status=<?php echo $status; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&mood=<?php echo urlencode($mood); ?>&status=<?php echo $status; ?>">下一页</a>
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