<?php
// 开启输出缓冲，避免headers already sent错误
ob_start();

// 检查ID参数
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: bottles.php");
    exit;
}

$id = (int)$_GET['id'];
$pageTitle = '漂流瓶详情';

// 引入头部
require_once 'includes/header.php';

// 初始化数据库连接
$conn = getDbConnection();

// 处理消息提示
$message = '';
$messageType = '';

// 删除评论
if (isset($_GET['action']) && $_GET['action'] === 'delete_comment' && isset($_GET['comment_id'])) {
    $commentId = (int)$_GET['comment_id'];
    
    $stmt = $conn->prepare("DELETE FROM comments WHERE id = ?");
    $stmt->bind_param("i", $commentId);
    
    if ($stmt->execute()) {
        // 记录管理员操作
        $admin->logOperation('评论', '删除', "删除评论ID: $commentId");
        
        $message = '评论删除成功！';
        $messageType = 'success';
    } else {
        $message = '评论删除失败: ' . $conn->error;
        $messageType = 'danger';
    }
    
    $stmt->close();
}

// 查询漂流瓶详情
$stmt = $conn->prepare("SELECT b.id, b.user_id, b.content, b.mood, b.throw_time, 
                        b.status, b.ip_address, b.location, b.is_anonymous, b.likes, 
                        b.bottle_type, b.audio_file, b.audio_duration, u.username
                        FROM bottles b 
                        LEFT JOIN users u ON b.user_id = u.id 
                        WHERE b.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">漂流瓶不存在！</div>';
    require_once 'includes/footer.php';
    exit;
}

$bottle = $result->fetch_assoc();
$stmt->close();

// 记录管理员操作
$admin->logOperation('漂流瓶', '查看详情', "查看漂流瓶ID: $id");

// 查询评论
$commentStmt = $conn->prepare("
    SELECT c.*, u.username 
    FROM comments c 
    LEFT JOIN users u ON c.user_id = u.id 
    WHERE c.bottle_id = ? 
    ORDER BY c.created_at DESC
");
$commentStmt->bind_param("i", $id);
$commentStmt->execute();
$commentResult = $commentStmt->get_result();
$comments = [];

while ($comment = $commentResult->fetch_assoc()) {
    $comments[] = $comment;
}

$commentStmt->close();

// 返回按钮和操作
$pageActions = '<a href="bottles.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> 返回漂流瓶列表</a> ';

// 根据当前状态添加切换按钮
if ($bottle['status'] === '漂流中' || $bottle['status'] === 'active') {
    $pageActions .= '<a href="bottles.php?action=toggle_status&id=' . $id . '" class="btn btn-warning">
                    <i class="fas fa-eye-slash"></i> 隐藏漂流瓶
                 </a> ';
} else {
    $pageActions .= '<a href="bottles.php?action=toggle_status&id=' . $id . '" class="btn btn-success">
                    <i class="fas fa-eye"></i> 显示漂流瓶
                 </a> ';
}

$pageActions .= '<a href="bottles.php?action=delete&id=' . $id . '" class="btn btn-danger">
                <i class="fas fa-trash"></i> 删除漂流瓶
              </a>';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">控制台</a></li>
        <li class="breadcrumb-item"><a href="bottles.php">漂流瓶管理</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold">漂流瓶信息</h5>
            <div>
                <?php echo $pageActions; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="mb-3">
                        <span class="badge bg-<?php echo ($bottle['status'] === '漂流中' || $bottle['status'] === 'active') ? 'success' : 'secondary'; ?>">
                            <?php echo ($bottle['status'] === '漂流中' || $bottle['status'] === 'active') ? '显示中' : '已隐藏'; ?>
                        </span>
                        
                        <?php if (isset($bottle['mood']) && $bottle['mood']): ?>
                        <span class="badge bg-primary">
                            <?php echo htmlspecialchars($bottle['mood']); ?>
                        </span>
                        <?php endif; ?>
                        
                        <?php if (isset($bottle['category']) && $bottle['category']): ?>
                        <span class="badge bg-info">
                            <?php echo htmlspecialchars($bottle['category']); ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="card p-3 bg-light mb-4">
                        <div class="card-body">
                            <?php if (isset($bottle['bottle_type']) && $bottle['bottle_type'] === 'voice' && !empty($bottle['audio_file'])): ?>
                                <!-- 语音漂流瓶 -->
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-microphone"></i> 这是一个语音漂流瓶
                                </div>
                                <audio controls class="w-100 mb-3">
                                    <source src="../<?php echo htmlspecialchars($bottle['audio_file']); ?>" type="audio/webm">
                                    <source src="../<?php echo htmlspecialchars($bottle['audio_file']); ?>" type="audio/mpeg">
                                    您的浏览器不支持音频播放
                                </audio>
                                <?php if (isset($bottle['audio_duration']) && $bottle['audio_duration'] > 0): ?>
                                    <small class="text-muted">时长: <?php 
                                        $minutes = floor($bottle['audio_duration'] / 60);
                                        $seconds = $bottle['audio_duration'] % 60;
                                        echo sprintf('%d:%02d', $minutes, $seconds);
                                    ?></small>
                                <?php endif; ?>
                                <?php if (!empty($bottle['content']) && $bottle['content'] !== '[语音漂流瓶]'): ?>
                                    <div class="mt-3">
                                        <p class="text-muted small mb-1">文字说明：</p>
                                        <p class="pre-wrap"><?php echo nl2br(htmlspecialchars($bottle['content'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <!-- 文字漂流瓶 -->
                                <p class="pre-wrap"><?php echo nl2br(htmlspecialchars($bottle['content'])); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- 评论列表 -->
                    <h5 class="mt-4 mb-3">评论列表 (<?php echo count($comments); ?>)</h5>
                    
                    <?php if (count($comments) > 0): ?>
                        <?php foreach ($comments as $comment): ?>
                        <div class="card mb-3">
                            <div class="card-header d-flex justify-content-between align-items-center bg-light py-2">
                                <div>
                                    <strong><?php echo htmlspecialchars($comment['username'] ?? '未知用户'); ?></strong>
                                    <small class="text-muted ms-2">
                                        <?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?>
                                        | IP: <?php echo !empty($comment['ip_address']) ? htmlspecialchars($comment['ip_address']) : '未记录'; ?>
                                    </small>
                                </div>
                                <div>
                                    <a href="bottle_detail.php?id=<?php echo $id; ?>&action=delete_comment&comment_id=<?php echo $comment['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('确定要删除此评论吗？')">
                                        <i class="fas fa-trash"></i> 删除
                                    </a>
                                </div>
                            </div>
                            <div class="card-body py-2">
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-info">暂无评论</div>
                    <?php endif; ?>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0">漂流瓶元数据</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>漂流瓶ID：</strong> <?php echo $bottle['id']; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>发布者：</strong> <?php echo htmlspecialchars($bottle['username'] ?? '未知用户'); ?>
                                    <?php if ($bottle['user_id']): ?>
                                    <a href="user_detail.php?id=<?php echo $bottle['user_id']; ?>" class="btn btn-sm btn-outline-primary ms-2">
                                        查看用户
                                    </a>
                                    <?php endif; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>发布时间：</strong> <?php echo isset($bottle['throw_time']) ? date('Y-m-d H:i:s', strtotime($bottle['throw_time'])) : '未知'; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>状态：</strong> 
                                    <span class="badge bg-<?php echo ($bottle['status'] === '漂流中' || $bottle['status'] === 'active') ? 'success' : 'secondary'; ?>">
                                        <?php echo ($bottle['status'] === '漂流中' || $bottle['status'] === 'active') ? '显示' : '隐藏'; ?>
                                    </span>
                                </li>
                                <?php if (isset($bottle['mood']) && $bottle['mood']): ?>
                                <li class="list-group-item">
                                    <strong>心情：</strong> 
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($bottle['mood']); ?>
                                    </span>
                                </li>
                                <?php endif; ?>
                                <?php if (isset($bottle['bottle_type'])): ?>
                                <li class="list-group-item">
                                    <strong>类型：</strong> 
                                    <?php if ($bottle['bottle_type'] === 'voice'): ?>
                                        <span class="badge bg-warning"><i class="fas fa-microphone"></i> 语音漂流瓶</span>
                                    <?php else: ?>
                                        <span class="badge bg-info"><i class="fas fa-pen"></i> 文字漂流瓶</span>
                                    <?php endif; ?>
                                </li>
                                <?php endif; ?>
                                <?php if (isset($bottle['bottle_type']) && $bottle['bottle_type'] === 'voice' && !empty($bottle['audio_file'])): ?>
                                <li class="list-group-item">
                                    <strong>语音文件：</strong> 
                                    <a href="../<?php echo htmlspecialchars($bottle['audio_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-download"></i> 下载
                                    </a>
                                </li>
                                <?php if (isset($bottle['audio_duration']) && $bottle['audio_duration'] > 0): ?>
                                <li class="list-group-item">
                                    <strong>语音时长：</strong> 
                                    <?php 
                                        $minutes = floor($bottle['audio_duration'] / 60);
                                        $seconds = $bottle['audio_duration'] % 60;
                                        echo sprintf('%d:%02d', $minutes, $seconds);
                                    ?>
                                </li>
                                <?php endif; ?>
                                <?php endif; ?>
                                <?php if (isset($bottle['category']) && $bottle['category']): ?>
                                <li class="list-group-item">
                                    <strong>分类：</strong> <?php echo htmlspecialchars($bottle['category']); ?>
                                </li>
                                <?php endif; ?>
                                <li class="list-group-item">
                                    <strong>IP地址：</strong> <?php echo $bottle['ip_address'] ?? '未记录'; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>评论数量：</strong> <?php echo count($comments); ?>
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h6 class="m-0">操作</h6>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <?php if ($bottle['status'] === '漂流中' || $bottle['status'] === 'active'): ?>
                                <a href="bottles.php?action=toggle_status&id=<?php echo $id; ?>" class="btn btn-warning">
                                    <i class="fas fa-eye-slash"></i> 隐藏漂流瓶
                                </a>
                                <?php else: ?>
                                <a href="bottles.php?action=toggle_status&id=<?php echo $id; ?>" class="btn btn-success">
                                    <i class="fas fa-eye"></i> 显示漂流瓶
                                </a>
                                <?php endif; ?>
                                <a href="bottles.php?action=delete&id=<?php echo $id; ?>" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> 删除漂流瓶
                                </a>
                                <?php if (count($comments) > 0): ?>
                                <a href="#" class="btn btn-secondary" id="deleteAllComments" 
                                   onclick="if(confirm('确定要删除此漂流瓶的所有评论吗？')) { window.location.href='comments.php?action=delete_all&bottle_id=<?php echo $id; ?>'; } return false;">
                                    <i class="fas fa-comments-slash"></i> 删除所有评论
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.pre-wrap {
    white-space: pre-wrap;
}
</style>

<?php
// 关闭数据库连接
$conn->close();

// 引入底部
require_once 'includes/footer.php';
?> 