<?php
// 检查用户ID
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: users.php");
    exit;
}

$userId = (int)$_GET['id'];
$pageTitle = '用户详情';

// 引入头部
require_once 'includes/header.php';

// 初始化数据库连接
$conn = getDbConnection();

// 查询用户基本信息
$userQuery = "SELECT * FROM users WHERE id = ?";
$userStmt = $conn->prepare($userQuery);
$userStmt->bind_param("i", $userId);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows === 0) {
    echo '<div class="alert alert-danger">用户不存在！</div>';
    require_once 'includes/footer.php';
    exit;
}

$user = $userResult->fetch_assoc();
$userStmt->close();

// 查询用户漂流瓶数量
$bottleCountQuery = "SELECT COUNT(*) as total FROM bottles WHERE user_id = ?";
$bottleCountStmt = $conn->prepare($bottleCountQuery);
$bottleCountStmt->bind_param("i", $userId);
$bottleCountStmt->execute();
$bottleCount = $bottleCountStmt->get_result()->fetch_assoc()['total'];
$bottleCountStmt->close();

// 查询用户评论数量
$commentCountQuery = "SELECT COUNT(*) as total FROM comments WHERE user_id = ?";
$commentCountStmt = $conn->prepare($commentCountQuery);
$commentCountStmt->bind_param("i", $userId);
$commentCountStmt->execute();
$commentCount = $commentCountStmt->get_result()->fetch_assoc()['total'];
$commentCountStmt->close();

// 查询用户点赞数量
$likeCountQuery = "SELECT COUNT(*) as total FROM likes WHERE user_id = ?";
$likeCountStmt = $conn->prepare($likeCountQuery);
$likeCountStmt->bind_param("i", $userId);
$likeCountStmt->execute();
$likeCount = $likeCountStmt->get_result()->fetch_assoc()['total'];
$likeCountStmt->close();

// 查询用户捡瓶数量
$pickCountQuery = "SELECT COUNT(*) as total FROM pick_records WHERE user_id = ?";
$pickCountStmt = $conn->prepare($pickCountQuery);
$pickCountStmt->bind_param("i", $userId);
$pickCountStmt->execute();
$pickCount = $pickCountStmt->get_result()->fetch_assoc()['total'];
$pickCountStmt->close();

// 查询用户签到情况
$checkinQuery = "SELECT * FROM checkins WHERE user_id = ? ORDER BY checkin_date DESC LIMIT 1";
$checkinStmt = $conn->prepare($checkinQuery);
$checkinStmt->bind_param("i", $userId);
$checkinStmt->execute();
$checkinResult = $checkinStmt->get_result();
$lastCheckin = $checkinResult->num_rows > 0 ? $checkinResult->fetch_assoc() : null;
$checkinStmt->close();

// 查询用户最近的积分记录
$pointsHistoryQuery = "SELECT * FROM points_history WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
$pointsHistoryStmt = $conn->prepare($pointsHistoryQuery);
$pointsHistoryStmt->bind_param("i", $userId);
$pointsHistoryStmt->execute();
$pointsHistoryResult = $pointsHistoryStmt->get_result();
$pointsHistory = [];
while ($row = $pointsHistoryResult->fetch_assoc()) {
    $pointsHistory[] = $row;
}
$pointsHistoryStmt->close();

// 查询用户最近的漂流瓶
$recentBottlesQuery = "SELECT b.*, COUNT(l.id) as like_count FROM bottles b 
                       LEFT JOIN likes l ON b.id = l.bottle_id 
                       WHERE b.user_id = ? 
                       GROUP BY b.id 
                       ORDER BY b.throw_time DESC 
                       LIMIT 5";
$recentBottlesStmt = $conn->prepare($recentBottlesQuery);
$recentBottlesStmt->bind_param("i", $userId);
$recentBottlesStmt->execute();
$recentBottlesResult = $recentBottlesStmt->get_result();
$recentBottles = [];
while ($row = $recentBottlesResult->fetch_assoc()) {
    $recentBottles[] = $row;
}
$recentBottlesStmt->close();

// 查询用户最近的评论
$recentCommentsQuery = "SELECT c.*, b.id as bottle_id, b.content as bottle_content 
                        FROM comments c 
                        LEFT JOIN bottles b ON c.bottle_id = b.id 
                        WHERE c.user_id = ? 
                        ORDER BY c.created_at DESC 
                        LIMIT 5";
$recentCommentsStmt = $conn->prepare($recentCommentsQuery);
$recentCommentsStmt->bind_param("i", $userId);
$recentCommentsStmt->execute();
$recentCommentsResult = $recentCommentsStmt->get_result();
$recentComments = [];
while ($row = $recentCommentsResult->fetch_assoc()) {
    $recentComments[] = $row;
}
$recentCommentsStmt->close();

// 处理编辑用户请求
if (isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    // 获取表单数据
    $username = sanitizeInput($_POST['username'] ?? '');
    $signature = sanitizeInput($_POST['signature'] ?? '');
    $points = (int)($_POST['points'] ?? 0);
    $experience = (int)($_POST['experience'] ?? 0);
    $is_vip = isset($_POST['is_vip']) ? 1 : 0;
    $vip_level = (int)($_POST['vip_level'] ?? 0);
    
    if ($is_vip) {
        $vip_expire_date = $_POST['vip_expire_date'] ?? null;
    } else {
        $vip_expire_date = null;
    }
    
    // 更新用户信息
    $updateQuery = "UPDATE users SET 
                    username = ?, 
                    signature = ?, 
                    points = ?, 
                    is_vip = ?, 
                    vip_level = ?, 
                    vip_expire_date = ?,
                    status = ?
                    WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $status = isset($_POST['status']) ? (int)$_POST['status'] : 1;
    $updateStmt->bind_param("ssiisii", $username, $signature, $points, $is_vip, $vip_level, $vip_expire_date, $status, $userId);
    
    if ($updateStmt->execute()) {
        // 记录日志
        $admin->logOperation('用户', '编辑', "编辑用户ID: $userId");
        
        // 记录积分变动（如果有）
        if ($points != $user['points']) {
            $pointsDiff = $points - $user['points'];
            $pointsAction = "管理员调整";
            
            $pointsLogQuery = "INSERT INTO points_history (user_id, points, action) VALUES (?, ?, ?)";
            $pointsLogStmt = $conn->prepare($pointsLogQuery);
            $pointsLogStmt->bind_param("iis", $userId, $pointsDiff, $pointsAction);
            $pointsLogStmt->execute();
            $pointsLogStmt->close();
        }
        
        // 处理经验值变动（如果有）
        $currentExperience = $user['experience'] ?? 0;
        if ($experience != $currentExperience) {
            require_once __DIR__ . '/../includes/user.php';
            $expResult = setUserExperience($userId, $experience);
            if ($expResult['success']) {
                // 经验值设置成功
            }
        }
        
        // 显示成功消息
        echo '<div class="alert alert-success">用户信息更新成功！</div>';
        
        // 重新查询用户信息以显示最新数据
        $userStmt = $conn->prepare($userQuery);
        $userStmt->bind_param("i", $userId);
        $userStmt->execute();
        $user = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();
    } else {
        echo '<div class="alert alert-danger">用户信息更新失败：' . $conn->error . '</div>';
    }
    
    $updateStmt->close();
}

// 返回按钮和编辑操作
$pageActions = '<a href="users.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> 返回用户列表</a> 
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editUserModal">
                    <i class="bi bi-pencil"></i> 编辑用户
                </button>';
?>

<div class="row">
    <!-- 用户基本信息 -->
    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="m-0">用户基本信息</h5>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <div class="avatar bg-<?php echo $user['gender'] == '男' ? 'primary' : 'danger'; ?> text-white rounded-circle mb-3" style="width: 100px; height: 100px; line-height: 100px; font-size: 40px; margin: 0 auto;">
                        <?php echo mb_substr($user['username'], 0, 1); ?>
                    </div>
                    <h5><?php echo htmlspecialchars($user['username']); ?></h5>
                    <p class="text-muted"><?php echo $user['gender']; ?> | 
                        <?php if ($user['is_vip']): ?>
                            <span class="badge bg-warning text-dark">VIP <?php echo $user['vip_level']; ?></span>
                        <?php else: ?>
                            <span class="badge bg-secondary">普通用户</span>
                        <?php endif; ?>
                    </p>
                    <p><?php echo $user['signature'] ? htmlspecialchars($user['signature']) : '<span class="text-muted">暂无签名</span>'; ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">账号ID</label>
                    <p class="form-control"><?php echo $user['id']; ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">积分</label>
                    <p class="form-control"><?php echo $user['points']; ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">经验值</label>
                    <p class="form-control">
                        <?php 
                        $experience = $user['experience'] ?? 0;
                        $level = $user['level'] ?? 1;
                        echo $experience . ' (等级 ' . $level . ')';
                        ?>
                    </p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">账号状态</label>
                    <p class="form-control">
                        <span class="badge bg-<?php echo $user['status'] ? 'success' : 'danger'; ?>">
                            <?php echo $user['status'] ? '启用' : '禁用'; ?>
                        </span>
                    </p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">注册时间</label>
                    <p class="form-control"><?php echo date('Y-m-d H:i:s', strtotime($user['created_at'])); ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">注册IP</label>
                    <p class="form-control"><?php echo htmlspecialchars($user['register_ip'] ?? '未记录'); ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">上次登录IP</label>
                    <p class="form-control"><?php echo htmlspecialchars($user['last_login_ip'] ?? '未记录'); ?></p>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">最后更新时间</label>
                    <p class="form-control"><?php echo date('Y-m-d H:i:s', strtotime($user['updated_at'])); ?></p>
                </div>
                
                <?php if ($user['is_vip']): ?>
                <div class="mb-3">
                    <label class="form-label">VIP到期时间</label>
                    <p class="form-control">
                        <?php echo $user['vip_expire_date'] ? date('Y-m-d', strtotime($user['vip_expire_date'])) : '永久'; ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <?php if ($lastCheckin): ?>
                <div class="mb-3">
                    <label class="form-label">最近签到</label>
                    <p class="form-control">
                        <?php echo date('Y-m-d', strtotime($lastCheckin['checkin_date'])); ?> 
                        (连续<?php echo $lastCheckin['consecutive_days']; ?>天)
                    </p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- 用户统计数据 -->
    <div class="col-xl-8">
        <div class="row">
            <div class="col-md-6 col-xl-3 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto">
                                <i class="bi bi-envelope-paper fs-2 text-primary"></i>
                            </div>
                            <div class="col ms-3">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">漂流瓶</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $bottleCount; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-xl-3 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto">
                                <i class="bi bi-chat-dots fs-2 text-success"></i>
                            </div>
                            <div class="col ms-3">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">评论</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $commentCount; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-xl-3 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto">
                                <i class="bi bi-hand-thumbs-up fs-2 text-info"></i>
                            </div>
                            <div class="col ms-3">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">点赞</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $likeCount; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6 col-xl-3 mb-4">
                <div class="card border-left-warning shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col-auto">
                                <i class="bi bi-search fs-2 text-warning"></i>
                            </div>
                            <div class="col ms-3">
                                <div class="text-xs font-weight-bold text-uppercase mb-1">捡瓶</div>
                                <div class="h5 mb-0 font-weight-bold"><?php echo $pickCount; ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- 最近漂流瓶 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="m-0">最近发布的漂流瓶</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentBottles)): ?>
                <p class="text-center text-muted">该用户尚未发布漂流瓶</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>内容</th>
                                <th>心情</th>
                                <th>点赞数</th>
                                <th>发布时间</th>
                                <th>状态</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBottles as $bottle): ?>
                            <tr>
                                <td><?php echo $bottle['id']; ?></td>
                                <td><?php echo mb_substr(htmlspecialchars($bottle['content']), 0, 30) . (mb_strlen($bottle['content']) > 30 ? '...' : ''); ?></td>
                                <td><?php echo $bottle['mood']; ?></td>
                                <td><?php echo $bottle['like_count']; ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($bottle['throw_time'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $bottle['status'] === '漂流中' ? 'primary' : 'success'; ?>">
                                        <?php echo $bottle['status']; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center">
                    <a href="bottles.php?user_id=<?php echo $userId; ?>" class="btn btn-sm btn-primary">查看全部漂流瓶</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 最近评论 -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="m-0">最近发表的评论</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentComments)): ?>
                <p class="text-center text-muted">该用户尚未发表评论</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>评论内容</th>
                                <th>关联漂流瓶</th>
                                <th>IP地址</th>
                                <th>发布时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentComments as $comment): ?>
                            <tr>
                                <td><?php echo $comment['id']; ?></td>
                                <td><?php echo mb_substr(htmlspecialchars($comment['content']), 0, 50) . (mb_strlen($comment['content']) > 50 ? '...' : ''); ?></td>
                                <td>
                                    <a href="bottle_detail.php?id=<?php echo $comment['bottle_id']; ?>" class="text-decoration-none">
                                        <?php 
                                        $bottleContent = htmlspecialchars($comment['bottle_content'] ?? '');
                                        echo mb_strlen($bottleContent) > 30 ? mb_substr($bottleContent, 0, 30) . '...' : $bottleContent; 
                                        ?>
                                    </a>
                                </td>
                                <td><?php echo htmlspecialchars($comment['ip_address'] ?? '未记录'); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($comment['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center">
                    <a href="comments.php?user_id=<?php echo $userId; ?>" class="btn btn-sm btn-primary">查看全部评论</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- 积分历史 -->
        <div class="card">
            <div class="card-header">
                <h5 class="m-0">最近积分变动</h5>
            </div>
            <div class="card-body">
                <?php if (empty($pointsHistory)): ?>
                <p class="text-center text-muted">暂无积分记录</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>变动</th>
                                <th>原因</th>
                                <th>时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pointsHistory as $record): ?>
                            <tr>
                                <td class="<?php echo $record['points'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                    <?php echo $record['points'] > 0 ? '+' . $record['points'] : $record['points']; ?>
                                </td>
                                <td><?php echo htmlspecialchars($record['action']); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($record['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 编辑用户弹窗 -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="user_detail.php?id=<?php echo $userId; ?>">
                <input type="hidden" name="action" value="edit_user">
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">编辑用户</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="username" class="form-label">用户名</label>
                        <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="signature" class="form-label">个性签名</label>
                        <textarea class="form-control" id="signature" name="signature" rows="3"><?php echo htmlspecialchars($user['signature'] ?? ''); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="points" class="form-label">积分</label>
                        <input type="number" class="form-control" id="points" name="points" value="<?php echo $user['points']; ?>">
                    </div>
                    <div class="mb-3">
                        <label for="experience" class="form-label">经验值</label>
                        <input type="number" class="form-control" id="experience" name="experience" value="<?php echo $user['experience'] ?? 0; ?>" min="0">
                        <small class="form-text text-muted">当前等级: <?php echo $user['level'] ?? 1; ?></small>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="is_vip" name="is_vip" <?php echo $user['is_vip'] ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="is_vip">VIP 会员</label>
                    </div>
                    <div class="mb-3" id="vipOptions" <?php echo $user['is_vip'] ? '' : 'style="display: none;"'; ?>>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="vip_level" class="form-label">VIP 等级</label>
                                <select class="form-select" id="vip_level" name="vip_level">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $user['vip_level'] == $i ? 'selected' : ''; ?>>
                                        VIP <?php echo $i; ?>
                                    </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="vip_expire_date" class="form-label">到期日期</label>
                                <input type="date" class="form-control" id="vip_expire_date" name="vip_expire_date" value="<?php echo $user['vip_expire_date'] ? date('Y-m-d', strtotime($user['vip_expire_date'])) : date('Y-m-d', strtotime('+1 year')); ?>">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="status" class="form-label">账号状态</label>
                        <select class="form-select" id="status" name="status">
                            <option value="1" <?php echo $user['status'] ? 'selected' : ''; ?>>启用</option>
                            <option value="0" <?php echo !$user['status'] ? 'selected' : ''; ?>>禁用</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
                    <button type="submit" class="btn btn-primary">保存修改</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// VIP 选项联动显示/隐藏
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

<?php
// 记录管理员操作
$admin->logOperation('用户', '查看详情', "查看用户ID: $userId");

// 引入底部
require_once 'includes/footer.php';
?> 