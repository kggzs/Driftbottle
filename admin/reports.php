<?php
// 开启输出缓冲，避免headers already sent错误
ob_start();

// 页面标题
$pageTitle = '举报管理';

// 引入头部
require_once 'includes/header.php';
require_once '../includes/report.php';

// 初始化数据库连接
$conn = getDbConnection();

// 当前管理员信息
$currentAdminData = $admin->getCurrentAdmin();
$currentAdminId = $currentAdminData['id'];

// 处理消息提示
$message = '';
$messageType = '';

// 从URL获取操作类型和ID
$action = $_GET['action'] ?? '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 处理审核操作
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['review_report']) && $action === 'review' && $id > 0) {
        $reviewStatus = sanitizeInput($_POST['status'] ?? 'approved');
        $reviewAction = sanitizeInput($_POST['action'] ?? 'no_action');
        $reviewNote = sanitizeInput($_POST['note'] ?? '');
        
        $result = reviewReport($id, $currentAdminId, $reviewAction, $reviewNote, $reviewStatus);
        
        if ($result['success']) {
            // 记录管理员操作
            $actionText = '';
            if ($reviewStatus === 'approved') {
                switch ($reviewAction) {
                    case 'delete':
                        $actionText = '删除';
                        break;
                    case 'hide_bottle':
                        $actionText = '屏蔽漂流瓶';
                        break;
                    case 'hide_comment':
                        $actionText = '屏蔽评论';
                        break;
                    default:
                        $actionText = '无操作';
                }
            } else {
                $actionText = '拒绝';
            }
            
            $admin->logOperation('举报', '审核', "举报ID: $id, 操作: $actionText");
            
            $message = $result['message'];
            $messageType = 'success';
            
            // 重定向到列表页
            header("Location: reports.php?message=" . urlencode($message) . "&type=$messageType");
            exit;
        } else {
            $message = $result['message'];
            $messageType = 'danger';
        }
    }
}

// 从URL参数获取消息
if (isset($_GET['message'])) {
    $message = $_GET['message'];
    $messageType = $_GET['type'] ?? 'info';
}

// 处理搜索和分页
$statusFilter = sanitizeInput($_GET['status'] ?? '');
$targetTypeFilter = sanitizeInput($_GET['target_type'] ?? '');
$search = sanitizeInput($_GET['search'] ?? '');
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// 获取举报列表
$reportsResult = getReports($statusFilter, $page, $limit);
if (!$reportsResult['success']) {
    $message = $reportsResult['message'] ?? '获取举报列表失败';
    $messageType = 'danger';
    $reports = [];
    $total = 0;
    $totalPages = 0;
} else {
    $reports = $reportsResult['reports'] ?? [];
    $total = $reportsResult['total'] ?? 0;
    $totalPages = $reportsResult['total_pages'] ?? 0;
    
    // 如果有搜索条件，进行过滤
    if (!empty($search)) {
        $reports = array_filter($reports, function($report) use ($search) {
            return stripos($report['reason'] ?? '', $search) !== false ||
                   stripos($report['reporter_name'] ?? '', $search) !== false ||
                   stripos($report['bottle_content'] ?? '', $search) !== false ||
                   stripos($report['comment_content'] ?? '', $search) !== false;
        });
    }
    
    // 过滤目标类型
    if (!empty($targetTypeFilter)) {
        $reports = array_filter($reports, function($report) use ($targetTypeFilter) {
            return ($report['target_type'] ?? '') === $targetTypeFilter;
        });
    }
}

// 记录管理员操作
$admin->logOperation('举报', '查看', '查看举报列表');
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
    
    <?php if ($action === 'review' && $id > 0): ?>
    <!-- 审核举报页面 -->
    <?php
    $reportDetail = getReportDetail($id);
    if (!$reportDetail['success'] || !isset($reportDetail['report'])):
    ?>
        <div class="alert alert-danger"><?php echo $reportDetail['message'] ?? '举报不存在'; ?></div>
        <a href="reports.php" class="btn btn-secondary">返回列表</a>
    <?php else:
        $report = $reportDetail['report'];
    ?>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-flag me-1"></i>
            审核举报 #<?php echo $report['id']; ?>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h5>举报信息</h5>
                    <table class="table table-bordered">
                        <tr>
                            <th width="150">举报类型</th>
                            <td><?php echo $report['target_type'] === 'bottle' ? '漂流瓶' : '评论'; ?></td>
                        </tr>
                        <tr>
                            <th>举报数量</th>
                            <td>
                                <span class="badge bg-info"><?php echo $report['report_count'] ?? 1; ?> 次举报</span>
                            </td>
                        </tr>
                        <tr>
                            <th>举报人</th>
                            <td>
                                <?php if (isset($report['reporter_list']) && count($report['reporter_list']) > 0): ?>
                                    <?php echo htmlspecialchars(implode('、', $report['reporter_list'])); ?>
                                <?php else: ?>
                                    <?php echo htmlspecialchars($report['reporter_name'] ?? '未知'); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>首次举报时间</th>
                            <td><?php echo isset($report['first_report_time']) ? date('Y-m-d H:i:s', strtotime($report['first_report_time'])) : date('Y-m-d H:i:s', strtotime($report['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>最新举报时间</th>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($report['created_at'])); ?></td>
                        </tr>
                        <tr>
                            <th>举报理由</th>
                            <td>
                                <?php if (isset($report['reporter_reasons_map']) && !empty($report['reporter_reasons_map'])): ?>
                                    <div class="report-reasons">
                                        <?php foreach ($report['reporter_reasons_map'] as $username => $reasons): ?>
                                            <div class="mb-2">
                                                <strong><?php echo htmlspecialchars($username); ?>:</strong>
                                                <ul class="mb-0">
                                                    <?php foreach ($reasons as $reason): ?>
                                                        <li><?php echo nl2br(htmlspecialchars($reason)); ?></li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php elseif (isset($report['reason_list']) && count($report['reason_list']) > 1): ?>
                                    <ul class="mb-0">
                                        <?php foreach ($report['reason_list'] as $reason): ?>
                                            <li><?php echo nl2br(htmlspecialchars($reason)); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <?php echo nl2br(htmlspecialchars($report['reason'] ?? '')); ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>被举报内容</h5>
                    <?php if ($report['target_type'] === 'bottle'): ?>
                        <table class="table table-bordered">
                            <tr>
                                <th width="150">漂流瓶ID</th>
                                <td><?php echo $report['target_id']; ?></td>
                            </tr>
                            <tr>
                                <th>发布者</th>
                                <td>
                                    <?php if (isset($report['bottle_owner_id'])): ?>
                                        <a href="user_detail.php?id=<?php echo $report['bottle_owner_id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($report['bottle_owner_name'] ?? '未知'); ?>
                                        </a>
                                    <?php else: ?>
                                        未知
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>是否已屏蔽</th>
                                <td><?php echo ($report['bottle_is_hidden'] ?? 0) ? '<span class="badge bg-warning">已屏蔽</span>' : '<span class="badge bg-success">未屏蔽</span>'; ?></td>
                            </tr>
                            <tr>
                                <th>内容预览</th>
                                <td>
                                    <?php 
                                    $content = $report['bottle_content'] ?? '';
                                    echo htmlspecialchars(mb_substr($content, 0, 200)) . (mb_strlen($content) > 200 ? '...' : ''); 
                                    ?>
                                </td>
                            </tr>
                            <tr>
                                <th>操作</th>
                                <td>
                                    <a href="bottle_detail.php?id=<?php echo $report['target_id']; ?>" class="btn btn-sm btn-info" target="_blank">
                                        <i class="fas fa-eye"></i> 查看详情
                                    </a>
                                    <?php if (isset($report['bottle_owner_id'])): ?>
                                    <a href="user_detail.php?id=<?php echo $report['bottle_owner_id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="fas fa-user"></i> 查看用户
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    <?php else: ?>
                        <table class="table table-bordered">
                            <tr>
                                <th width="150">评论ID</th>
                                <td><?php echo $report['target_id']; ?></td>
                            </tr>
                            <tr>
                                <th>评论者</th>
                                <td>
                                    <?php if (isset($report['comment_owner_id'])): ?>
                                        <a href="user_detail.php?id=<?php echo $report['comment_owner_id']; ?>" target="_blank">
                                            <?php echo htmlspecialchars($report['comment_owner_name'] ?? '未知'); ?>
                                        </a>
                                    <?php else: ?>
                                        未知
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <tr>
                                <th>是否已屏蔽</th>
                                <td><?php echo ($report['comment_is_hidden'] ?? 0) ? '<span class="badge bg-warning">已屏蔽</span>' : '<span class="badge bg-success">未屏蔽</span>'; ?></td>
                            </tr>
                            <tr>
                                <th>评论内容</th>
                                <td><?php echo nl2br(htmlspecialchars($report['comment_content'] ?? '')); ?></td>
                            </tr>
                            <tr>
                                <th>操作</th>
                                <td>
                                    <a href="comments.php?search=<?php echo urlencode($report['comment_content'] ?? ''); ?>" class="btn btn-sm btn-info" target="_blank">
                                        <i class="fas fa-eye"></i> 查看评论
                                    </a>
                                    <?php if (isset($report['comment_owner_id'])): ?>
                                    <a href="user_detail.php?id=<?php echo $report['comment_owner_id']; ?>" class="btn btn-sm btn-primary" target="_blank">
                                        <i class="fas fa-user"></i> 查看用户
                                    </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($report['status'] === 'pending'): ?>
            <form method="post" action="reports.php?action=review&id=<?php echo $id; ?>">
                <h5>审核操作</h5>
                <div class="mb-3">
                    <label class="form-label">审核结果</label>
                    <div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" id="status_approved" value="approved" checked>
                            <label class="form-check-label" for="status_approved">通过</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="status" id="status_rejected" value="rejected">
                            <label class="form-check-label" for="status_rejected">拒绝</label>
                        </div>
                    </div>
                </div>
            <?php else: ?>
            <div class="alert alert-info">
                <h5>当前审核状态：<?php 
                    $statusText = '';
                    $statusClass = '';
                    switch ($report['status']) {
                        case 'approved':
                            $statusText = '已通过';
                            $statusClass = 'success';
                            break;
                        case 'rejected':
                            $statusText = '已拒绝';
                            $statusClass = 'danger';
                            break;
                    }
                    echo '<span class="badge bg-' . $statusClass . '">' . $statusText . '</span>';
                ?></h5>
                <?php if ($report['admin_name']): ?>
                <p class="mb-1">审核人：<?php echo htmlspecialchars($report['admin_name']); ?></p>
                <?php endif; ?>
                <?php if ($report['reviewed_at']): ?>
                <p class="mb-1">审核时间：<?php echo date('Y-m-d H:i:s', strtotime($report['reviewed_at'])); ?></p>
                <?php endif; ?>
                <?php if ($report['admin_note']): ?>
                <p class="mb-1">审核备注：<?php echo nl2br(htmlspecialchars($report['admin_note'])); ?></p>
                <?php endif; ?>
            </div>
            <form method="post" action="reports.php?action=review&id=<?php echo $id; ?>">
                <h5>撤销处理（恢复到待审核状态）</h5>
                <div class="mb-3">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> 
                        撤销后，举报将恢复到待审核状态。如果之前执行了屏蔽操作，将会取消屏蔽；如果执行了删除操作，则无法恢复。
                    </div>
                    <input type="hidden" name="status" value="pending">
                    <input type="hidden" name="action" value="<?php echo htmlspecialchars($report['admin_action'] ?? 'no_action'); ?>">
                </div>
            <?php endif; ?>
                
                <div class="mb-3">
                    <label class="form-label">操作类型（仅当审核通过时生效）</label>
                    <select class="form-select" name="action" id="review_action">
                        <?php if ($report['target_type'] === 'bottle'): ?>
                            <option value="no_action">无操作</option>
                            <option value="hide_bottle">屏蔽漂流瓶内容</option>
                            <option value="delete">删除漂流瓶</option>
                        <?php else: ?>
                            <option value="no_action">无操作</option>
                            <option value="hide_comment">屏蔽评论</option>
                            <option value="delete">删除评论</option>
                        <?php endif; ?>
                    </select>
                    <small class="form-text text-muted">
                        <?php if ($report['target_type'] === 'bottle'): ?>
                            屏蔽漂流瓶：内容将不显示，但仍可被捡起。删除漂流瓶：永久删除，不可恢复。
                        <?php else: ?>
                            屏蔽评论：评论内容将不显示。删除评论：永久删除，不可恢复。
                        <?php endif; ?>
                    </small>
                </div>
                
                <div class="mb-3">
                    <label for="review_note" class="form-label">备注（可选）</label>
                    <textarea class="form-control" id="review_note" name="note" rows="3" placeholder="填写审核备注..."></textarea>
                </div>
                
                <?php if ($report['status'] === 'pending'): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    审核通过后，举报人将获得 <?php echo POINTS_PER_REPORT_APPROVED; ?> 积分奖励。同一内容的多个举报将批量处理。
                </div>
                <?php endif; ?>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="review_report" class="btn btn-<?php echo $report['status'] === 'pending' ? 'primary' : 'warning'; ?>">
                        <i class="fas fa-<?php echo $report['status'] === 'pending' ? 'check' : 'undo'; ?>"></i> 
                        <?php echo $report['status'] === 'pending' ? '提交审核' : '撤销处理'; ?>
                    </button>
                    <a href="reports.php" class="btn btn-secondary">返回列表</a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; endif; ?>
    
    <?php if ($action !== 'review' || !isset($reportDetail) || !$reportDetail['success']): ?>
    <!-- 搜索和筛选表单 -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" action="reports.php" class="row g-3">
                <div class="col-md-3">
                    <label for="status" class="form-label">审核状态</label>
                    <select class="form-select" id="status" name="status">
                        <option value="">全部</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>待审核</option>
                        <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>已通过</option>
                        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>已拒绝</option>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="target_type" class="form-label">举报类型</label>
                    <select class="form-select" id="target_type" name="target_type">
                        <option value="">全部</option>
                        <option value="bottle" <?php echo $targetTypeFilter === 'bottle' ? 'selected' : ''; ?>>漂流瓶</option>
                        <option value="comment" <?php echo $targetTypeFilter === 'comment' ? 'selected' : ''; ?>>评论</option>
                    </select>
                </div>
                
                <div class="col-md-4">
                    <label for="search" class="form-label">搜索</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="举报理由/举报人/内容" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                
                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-grid gap-2 w-100">
                        <button type="submit" class="btn btn-primary">搜索</button>
                        <a href="reports.php" class="btn btn-secondary">重置</a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 举报列表 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-flag me-1"></i>
            举报列表
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead>
                        <tr>
                            <th width="60">ID</th>
                            <th width="100">类型</th>
                            <th width="120">举报人</th>
                            <th>举报理由</th>
                            <th width="100">审核状态</th>
                            <th width="120">审核人</th>
                            <th width="100">操作类型</th>
                            <th width="150">举报时间</th>
                            <th width="120">操作</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reports)): ?>
                            <?php foreach ($reports as $report): ?>
                            <tr>
                                <td><?php echo $report['id']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $report['target_type'] === 'bottle' ? 'primary' : 'info'; ?>">
                                        <?php echo $report['target_type'] === 'bottle' ? '漂流瓶' : '评论'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php 
                                    $reporters = $report['all_reporters'] ?? $report['reporter_name'] ?? '未知';
                                    $reportCount = $report['report_count'] ?? 1;
                                    if ($reportCount > 1) {
                                        echo htmlspecialchars($reporters) . ' <span class="badge bg-info">' . $reportCount . '条举报</span>';
                                    } else {
                                        echo htmlspecialchars($reporters);
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    $reason = $report['reason'] ?? '';
                                    $reasonPreview = htmlspecialchars(mb_substr($reason, 0, 100)) . (mb_strlen($reason) > 100 ? '...' : '');
                                    if (isset($report['reason_list']) && count($report['reason_list']) > 1) {
                                        echo $reasonPreview . ' <span class="badge bg-secondary" title="共' . count($report['reason_list']) . '条不同理由">+' . (count($report['reason_list']) - 1) . '</span>';
                                    } else {
                                        echo $reasonPreview;
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    $status = $report['status'] ?? 'pending';
                                    $statusClass = '';
                                    $statusText = '';
                                    switch ($status) {
                                        case 'pending':
                                            $statusClass = 'warning';
                                            $statusText = '待审核';
                                            break;
                                        case 'approved':
                                            $statusClass = 'success';
                                            $statusText = '已通过';
                                            break;
                                        case 'rejected':
                                            $statusClass = 'danger';
                                            $statusText = '已拒绝';
                                            break;
                                    }
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                                </td>
                                <td><?php echo htmlspecialchars($report['admin_name'] ?? '-'); ?></td>
                                <td>
                                    <?php
                                    $actionType = $report['admin_action'] ?? '';
                                    if ($actionType) {
                                        $actionMap = [
                                            'delete' => '删除',
                                            'hide_bottle' => '屏蔽漂流瓶',
                                            'hide_comment' => '屏蔽评论',
                                            'no_action' => '无操作'
                                        ];
                                        echo $actionMap[$actionType] ?? $actionType;
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($report['created_at'])); ?></td>
                                <td>
                                    <a href="reports.php?action=review&id=<?php echo $report['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye"></i> 查看
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center">没有找到举报记录</td>
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
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($statusFilter); ?>&target_type=<?php echo urlencode($targetTypeFilter); ?>&search=<?php echo urlencode($search); ?>">上一页</a>
                    </li>
                    
                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($statusFilter); ?>&target_type=<?php echo urlencode($targetTypeFilter); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($statusFilter); ?>&target_type=<?php echo urlencode($targetTypeFilter); ?>&search=<?php echo urlencode($search); ?>">下一页</a>
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

