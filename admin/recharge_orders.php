<?php
// 开启输出缓冲，避免headers already sent错误
ob_start();

$pageTitle = '充值订单管理';

// 引入头部
require_once 'includes/header.php';

// 检查权限（允许所有管理员访问，如果需要可以添加具体权限检查）
// 如果需要限制权限，可以在admin表中添加recharge_orders权限

// 初始化数据库连接
$conn = getDbConnection();

// 处理筛选和分页
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// 筛选条件
$statusFilter = isset($_GET['status']) ? (int)$_GET['status'] : -1;
$searchKeyword = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

// 构建查询条件
$whereConditions = [];
$params = [];
$paramTypes = '';

if ($statusFilter >= 0) {
    $whereConditions[] = "ro.status = ?";
    $params[] = $statusFilter;
    $paramTypes .= 'i';
}

if (!empty($searchKeyword)) {
    $whereConditions[] = "(ro.order_no LIKE ? OR ro.trade_no LIKE ? OR u.username LIKE ?)";
    $searchPattern = '%' . $searchKeyword . '%';
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $params[] = $searchPattern;
    $paramTypes .= 'sss';
}

$whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';

// 获取总数
$countSql = "SELECT COUNT(*) as total FROM recharge_orders ro 
             LEFT JOIN users u ON ro.user_id = u.id 
             $whereClause";
$countStmt = $conn->prepare($countSql);
if (!empty($params)) {
    $countStmt->bind_param($paramTypes, ...$params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $perPage);
$countStmt->close();

// 获取订单列表
$sql = "SELECT ro.*, u.username 
        FROM recharge_orders ro 
        LEFT JOIN users u ON ro.user_id = u.id 
        $whereClause 
        ORDER BY ro.created_at DESC 
        LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $offset;
$paramTypes .= 'ii';

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($paramTypes, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
$stmt->close();
$conn->close();
?>

<!-- 筛选和搜索 -->
<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="" class="row g-3">
            <div class="col-md-3">
                <label for="status" class="form-label">订单状态</label>
                <select class="form-select" id="status" name="status">
                    <option value="-1" <?php echo $statusFilter == -1 ? 'selected' : ''; ?>>全部状态</option>
                    <option value="0" <?php echo $statusFilter == 0 ? 'selected' : ''; ?>>待支付</option>
                    <option value="1" <?php echo $statusFilter == 1 ? 'selected' : ''; ?>>已支付</option>
                    <option value="2" <?php echo $statusFilter == 2 ? 'selected' : ''; ?>>已取消</option>
                    <option value="3" <?php echo $statusFilter == 3 ? 'selected' : ''; ?>>已退款</option>
                </select>
            </div>
            <div class="col-md-6">
                <label for="search" class="form-label">搜索</label>
                <input type="text" class="form-control" id="search" name="search" 
                       placeholder="订单号、交易号或用户名" value="<?php echo htmlspecialchars($searchKeyword); ?>">
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary me-2">
                    <i class="bi bi-search"></i> 搜索
                </button>
                <a href="recharge_orders.php" class="btn btn-secondary">
                    <i class="bi bi-arrow-clockwise"></i> 重置
                </a>
            </div>
        </form>
    </div>
</div>

<!-- 订单列表 -->
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0"><i class="fas fa-wallet"></i> 充值订单列表</h5>
        <span class="badge bg-primary">共 <?php echo $totalRecords; ?> 条记录</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>订单号</th>
                        <th>用户</th>
                        <th>充值金额</th>
                        <th>获得积分</th>
                        <th>积分比例</th>
                        <th>支付方式</th>
                        <th>交易号</th>
                        <th>状态</th>
                        <th>创建时间</th>
                        <th>支付时间</th>
                        <th>操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="12" class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <p class="text-muted">暂无订单记录</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo $order['id']; ?></td>
                        <td><code><?php echo htmlspecialchars($order['order_no']); ?></code></td>
                        <td>
                            <?php if ($order['username']): ?>
                            <a href="user_detail.php?id=<?php echo $order['user_id']; ?>" class="text-decoration-none">
                                <?php echo htmlspecialchars($order['username']); ?>
                            </a>
                            <?php else: ?>
                            <span class="text-muted">用户已删除</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-success fw-bold">¥<?php echo number_format($order['amount'], 2); ?></td>
                        <td><span class="text-warning fw-bold"><?php echo $order['points']; ?></span> 积分</td>
                        <td><?php echo number_format($order['points_ratio'], 2); ?> 积分/元</td>
                        <td>
                            <?php
                            $paymentTypes = [
                                'alipay' => '<span class="badge bg-primary">支付宝</span>',
                                'wxpay' => '<span class="badge bg-success">微信支付</span>',
                                'qqpay' => '<span class="badge bg-info">QQ钱包</span>',
                                'bank' => '<span class="badge bg-secondary">云闪付</span>'
                            ];
                            echo $paymentTypes[$order['payment_type']] ?? '<span class="badge bg-secondary">' . htmlspecialchars($order['payment_type']) . '</span>';
                            ?>
                        </td>
                        <td>
                            <?php if ($order['trade_no']): ?>
                            <code class="small"><?php echo htmlspecialchars($order['trade_no']); ?></code>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $statusMap = [
                                0 => '<span class="badge bg-warning">待支付</span>',
                                1 => '<span class="badge bg-success">已支付</span>',
                                2 => '<span class="badge bg-secondary">已取消</span>',
                                3 => '<span class="badge bg-danger">已退款</span>'
                            ];
                            echo $statusMap[$order['status']] ?? '<span class="badge bg-secondary">未知</span>';
                            ?>
                        </td>
                        <td><?php echo date('Y-m-d H:i:s', strtotime($order['created_at'])); ?></td>
                        <td>
                            <?php if ($order['paid_at']): ?>
                            <?php echo date('Y-m-d H:i:s', strtotime($order['paid_at'])); ?>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick="viewOrderDetail(<?php echo $order['id']; ?>)">
                                <i class="bi bi-eye"></i> 详情
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php if ($totalPages > 1): ?>
    <div class="card-footer">
        <nav aria-label="订单分页">
            <ul class="pagination justify-content-center mb-0">
                <?php if ($page > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchKeyword); ?>">上一页</a>
                </li>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchKeyword); ?>"><?php echo $i; ?></a>
                </li>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $statusFilter; ?>&search=<?php echo urlencode($searchKeyword); ?>">下一页</a>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>

<!-- 订单详情弹窗 -->
<div class="modal fade" id="orderDetailModal" tabindex="-1" aria-labelledby="orderDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="orderDetailModalLabel">订单详情</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="orderDetailContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">加载中...</span>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">关闭</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewOrderDetail(orderId) {
    const modal = new bootstrap.Modal(document.getElementById('orderDetailModal'));
    const content = document.getElementById('orderDetailContent');
    
    content.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">加载中...</span></div></div>';
    modal.show();
    
    // 获取订单详情
    fetch(`../api.php?action=get_recharge_order_detail&order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const order = data.order;
                content.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>订单号：</strong><code>${order.order_no}</code></p>
                            <p><strong>用户：</strong>${order.username || '用户已删除'}</p>
                            <p><strong>充值金额：</strong><span class="text-success fw-bold">¥${parseFloat(order.amount).toFixed(2)}</span></p>
                            <p><strong>获得积分：</strong><span class="text-warning fw-bold">${order.points} 积分</span></p>
                            <p><strong>积分比例：</strong>${parseFloat(order.points_ratio).toFixed(2)} 积分/元</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>支付方式：</strong>${order.payment_type || '-'}</p>
                            <p><strong>交易号：</strong>${order.trade_no ? '<code>' + order.trade_no + '</code>' : '-'}</p>
                            <p><strong>状态：</strong>${getStatusBadge(order.status)}</p>
                            <p><strong>创建时间：</strong>${new Date(order.created_at).toLocaleString()}</p>
                            <p><strong>支付时间：</strong>${order.paid_at ? new Date(order.paid_at).toLocaleString() : '-'}</p>
                        </div>
                    </div>
                    ${order.notify_data ? '<div class="mt-3"><strong>回调数据：</strong><pre class="bg-light p-2 rounded"><code>' + JSON.stringify(JSON.parse(order.notify_data), null, 2) + '</code></pre></div>' : ''}
                `;
            } else {
                content.innerHTML = '<div class="alert alert-danger">加载订单详情失败</div>';
            }
        })
        .catch(error => {
            content.innerHTML = '<div class="alert alert-danger">加载订单详情失败：' + error.message + '</div>';
        });
}

function getStatusBadge(status) {
    const statusMap = {
        0: '<span class="badge bg-warning">待支付</span>',
        1: '<span class="badge bg-success">已支付</span>',
        2: '<span class="badge bg-secondary">已取消</span>',
        3: '<span class="badge bg-danger">已退款</span>'
    };
    return statusMap[status] || '<span class="badge bg-secondary">未知</span>';
}
</script>

<?php
require_once 'includes/footer.php';
?>
