<?php
// 开启输出缓冲，避免headers already sent错误
ob_start();

// 检查ID参数
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: announcements.php");
    exit;
}

$id = (int)$_GET['id'];
$pageTitle = '公告详情';

// 引入头部
require_once 'includes/header.php';

// 初始化数据库连接
$conn = getDbConnection();

// 查询公告详情
$stmt = $conn->prepare("SELECT a.*, CONCAT(u.username) as admin_name 
                      FROM announcements a 
                      LEFT JOIN admins u ON a.created_by = u.id 
                      WHERE a.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">公告不存在！</div>';
    require_once 'includes/footer.php';
    exit;
}

$announcement = $result->fetch_assoc();
$stmt->close();

// 记录管理员操作
$admin->logOperation('公告', '查看详情', "查看公告ID: $id, 标题: {$announcement['title']}");

// 返回按钮和编辑操作
$pageActions = '<a href="announcements.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> 返回公告列表</a> 
                <a href="announcements.php?action=edit&id=' . $id . '" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> 编辑公告
                </a>';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">控制台</a></li>
        <li class="breadcrumb-item"><a href="announcements.php">公告管理</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="m-0 font-weight-bold">公告信息</h5>
            <div>
                <?php echo $pageActions; ?>
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                    
                    <div class="mb-4">
                        <span class="badge bg-<?php 
                        switch ($announcement['type']) {
                            case '重要':
                                echo 'warning text-dark';
                                break;
                            case '紧急':
                                echo 'danger';
                                break;
                            default:
                                echo 'info';
                                break;
                        }
                        ?>">
                            <?php echo $announcement['type']; ?>
                        </span>
                        
                        <span class="badge bg-<?php echo $announcement['status'] ? 'success' : 'secondary'; ?>">
                            <?php echo $announcement['status'] ? '显示中' : '已隐藏'; ?>
                        </span>
                    </div>
                    
                    <div class="card p-3 bg-light mb-4">
                        <div class="card-body">
                            <p class="pre-wrap"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0">公告元数据</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">
                                    <strong>公告ID：</strong> <?php echo $announcement['id']; ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>发布者：</strong> <?php echo htmlspecialchars($announcement['admin_name']); ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>发布时间：</strong> <?php echo date('Y-m-d H:i:s', strtotime($announcement['created_at'])); ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>更新时间：</strong> <?php echo date('Y-m-d H:i:s', strtotime($announcement['updated_at'])); ?>
                                </li>
                                <li class="list-group-item">
                                    <strong>有效期：</strong><br>
                                    <?php if ($announcement['start_time'] || $announcement['end_time']): ?>
                                        <div class="mt-1">
                                            <span class="text-muted">开始：</span>
                                            <?php echo $announcement['start_time'] ? date('Y-m-d H:i', strtotime($announcement['start_time'])) : '不限制'; ?>
                                        </div>
                                        <div class="mt-1">
                                            <span class="text-muted">结束：</span>
                                            <?php echo $announcement['end_time'] ? date('Y-m-d H:i', strtotime($announcement['end_time'])) : '不限制'; ?>
                                        </div>
                                    <?php else: ?>
                                        <span class="text-muted">长期有效</span>
                                    <?php endif; ?>
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
                                <a href="announcements.php?action=edit&id=<?php echo $id; ?>" class="btn btn-primary">
                                    <i class="fas fa-edit"></i> 编辑公告
                                </a>
                                <?php if ($announcement['status']): ?>
                                <a href="announcements.php?action=toggle_status&id=<?php echo $id; ?>" class="btn btn-warning">
                                    <i class="fas fa-eye-slash"></i> 隐藏公告
                                </a>
                                <?php else: ?>
                                <a href="announcements.php?action=toggle_status&id=<?php echo $id; ?>" class="btn btn-success">
                                    <i class="fas fa-eye"></i> 显示公告
                                </a>
                                <?php endif; ?>
                                <a href="announcements.php?action=delete&id=<?php echo $id; ?>" class="btn btn-danger">
                                    <i class="fas fa-trash"></i> 删除公告
                                </a>
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