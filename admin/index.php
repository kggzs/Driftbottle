<?php
$pageTitle = '控制面板';

// 引入头部
require_once 'includes/header.php';

// 初始化数据库连接
$conn = getDbConnection();

// 获取基本统计数据
// 1. 用户总数
try {
    $userQuery = "SELECT COUNT(*) as total FROM users";
    $userResult = $conn->query($userQuery);
    if ($userResult === false) {
        throw new Exception("查询用户总数失败: " . $conn->error);
    }
    $userTotal = $userResult->fetch_assoc()['total'] ?? 0;
} catch (Exception $e) {
    error_log("统计用户总数错误: " . $e->getMessage());
    $userTotal = 0;
}

// 2. 漂流瓶总数
try {
    $bottleQuery = "SELECT COUNT(*) as total FROM bottles";
    $bottleResult = $conn->query($bottleQuery);
    if ($bottleResult === false) {
        throw new Exception("查询漂流瓶总数失败: " . $conn->error);
    }
    $bottleTotal = $bottleResult->fetch_assoc()['total'] ?? 0;
} catch (Exception $e) {
    error_log("统计漂流瓶总数错误: " . $e->getMessage());
    $bottleTotal = 0;
}

// 3. 漂流中的瓶子数量
try {
    $activeBottleQuery = "SELECT COUNT(*) as total FROM bottles WHERE status = '漂流中'";
    $activeBottleResult = $conn->query($activeBottleQuery);
    if ($activeBottleResult === false) {
        throw new Exception("查询漂流中瓶子数量失败: " . $conn->error);
    }
    $activeBottleTotal = $activeBottleResult->fetch_assoc()['total'] ?? 0;
} catch (Exception $e) {
    error_log("统计漂流中瓶子数量错误: " . $e->getMessage());
    $activeBottleTotal = 0;
}

// 4. 评论总数
try {
    $commentQuery = "SELECT COUNT(*) as total FROM comments";
    $commentResult = $conn->query($commentQuery);
    if ($commentResult === false) {
        throw new Exception("查询评论总数失败: " . $conn->error);
    }
    $commentTotal = $commentResult->fetch_assoc()['total'] ?? 0;
} catch (Exception $e) {
    error_log("统计评论总数错误: " . $e->getMessage());
    $commentTotal = 0;
}

// 5. 今日新增用户
try {
    $todayUserQuery = "SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = CURDATE()";
    $todayUserResult = $conn->query($todayUserQuery);
    if ($todayUserResult === false) {
        throw new Exception("查询今日新增用户失败: " . $conn->error);
    }
    $todayUserTotal = $todayUserResult->fetch_assoc()['total'] ?? 0;
} catch (Exception $e) {
    error_log("统计今日新增用户错误: " . $e->getMessage());
    $todayUserTotal = 0;
}

// 6. 今日新增漂流瓶
try {
    $todayBottleQuery = "SELECT COUNT(*) as total FROM bottles WHERE DATE(throw_time) = CURDATE()";
    $todayBottleResult = $conn->query($todayBottleQuery);
    if ($todayBottleResult === false) {
        throw new Exception("查询今日新增漂流瓶失败: " . $conn->error);
    }
    $todayBottleTotal = $todayBottleResult->fetch_assoc()['total'] ?? 0;
} catch (Exception $e) {
    error_log("统计今日新增漂流瓶错误: " . $e->getMessage());
    $todayBottleTotal = 0;
}

// 7. 今日评论数
try {
    $todayCommentQuery = "SELECT COUNT(*) as total FROM comments WHERE DATE(created_at) = CURDATE()";
    $todayCommentResult = $conn->query($todayCommentQuery);
    if ($todayCommentResult === false) {
        throw new Exception("查询今日评论数失败: " . $conn->error);
    }
    $todayCommentTotal = $todayCommentResult->fetch_assoc()['total'] ?? 0;
} catch (Exception $e) {
    error_log("统计今日评论数错误: " . $e->getMessage());
    $todayCommentTotal = 0;
}

// 8. VIP用户数量
try {
    $vipUserQuery = "SELECT COUNT(*) as total FROM users WHERE is_vip = 1";
    $vipUserResult = $conn->query($vipUserQuery);
    if ($vipUserResult === false) {
        throw new Exception("查询VIP用户数量失败: " . $conn->error);
    }
    $vipUserTotal = $vipUserResult->fetch_assoc()['total'] ?? 0;
} catch (Exception $e) {
    error_log("统计VIP用户数量错误: " . $e->getMessage());
    $vipUserTotal = 0;
}

// 获取最近7天的数据趋势
$dateLabels = [];
$userTrends = [];
$bottleTrends = [];
$commentTrends = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dateLabels[] = date('m-d', strtotime("-$i days"));
    
    // 用户注册趋势
    try {
        $userTrendQuery = "SELECT COUNT(*) as total FROM users WHERE DATE(created_at) = '$date'";
        $userTrendResult = $conn->query($userTrendQuery);
        if ($userTrendResult === false) {
            throw new Exception("查询用户趋势失败: " . $conn->error);
        }
        $userTrends[] = $userTrendResult->fetch_assoc()['total'] ?? 0;
    } catch (Exception $e) {
        error_log("统计用户趋势错误: " . $e->getMessage());
        $userTrends[] = 0;
    }
    
    // 漂流瓶趋势
    try {
        $bottleTrendQuery = "SELECT COUNT(*) as total FROM bottles WHERE DATE(throw_time) = '$date'";
        $bottleTrendResult = $conn->query($bottleTrendQuery);
        if ($bottleTrendResult === false) {
            throw new Exception("查询漂流瓶趋势失败: " . $conn->error);
        }
        $bottleTrends[] = $bottleTrendResult->fetch_assoc()['total'] ?? 0;
    } catch (Exception $e) {
        error_log("统计漂流瓶趋势错误: " . $e->getMessage());
        $bottleTrends[] = 0;
    }
    
    // 评论趋势
    try {
        $commentTrendQuery = "SELECT COUNT(*) as total FROM comments WHERE DATE(created_at) = '$date'";
        $commentTrendResult = $conn->query($commentTrendQuery);
        if ($commentTrendResult === false) {
            throw new Exception("查询评论趋势失败: " . $conn->error);
        }
        $commentTrends[] = $commentTrendResult->fetch_assoc()['total'] ?? 0;
    } catch (Exception $e) {
        error_log("统计评论趋势错误: " . $e->getMessage());
        $commentTrends[] = 0;
    }
}

// 获取最新10个漂流瓶
try {
    $latestBottlesQuery = "SELECT b.id, b.content, b.mood, b.throw_time, b.status, u.username, b.is_anonymous 
                          FROM bottles b 
                          LEFT JOIN users u ON b.user_id = u.id 
                          ORDER BY b.throw_time DESC 
                          LIMIT 10";
    $latestBottlesResult = $conn->query($latestBottlesQuery);
    if ($latestBottlesResult === false) {
        throw new Exception("查询最新漂流瓶失败: " . $conn->error);
    }
    $latestBottles = [];
    while ($row = $latestBottlesResult->fetch_assoc()) {
        $latestBottles[] = $row;
    }
} catch (Exception $e) {
    error_log("获取最新漂流瓶错误: " . $e->getMessage());
    $latestBottles = [];
}

// 获取系统最新10条日志
try {
    $latestLogsQuery = "SELECT al.*, a.username 
                       FROM admin_operation_logs al 
                       LEFT JOIN admins a ON al.admin_id = a.id 
                       ORDER BY al.created_at DESC 
                       LIMIT 10";
    $latestLogsResult = $conn->query($latestLogsQuery);
    if ($latestLogsResult === false) {
        throw new Exception("查询最新日志失败: " . $conn->error);
    }
    $latestLogs = [];
    while ($row = $latestLogsResult->fetch_assoc()) {
        $latestLogs[] = $row;
    }
} catch (Exception $e) {
    error_log("获取最新日志错误: " . $e->getMessage());
    $latestLogs = [];
}
?>

<div class="row">
    <!-- 统计卡片 -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col-auto">
                        <i class="bi bi-people fs-1 text-primary"></i>
                    </div>
                    <div class="col ms-3">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">总用户数</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $userTotal; ?></div>
                        <div class="text-xs text-muted">今日新增: <?php echo $todayUserTotal; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col-auto">
                        <i class="bi bi-envelope fs-1 text-success"></i>
                    </div>
                    <div class="col ms-3">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">总漂流瓶数</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $bottleTotal; ?></div>
                        <div class="text-xs text-muted">漂流中: <?php echo $activeBottleTotal; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col-auto">
                        <i class="bi bi-chat-dots fs-1 text-info"></i>
                    </div>
                    <div class="col ms-3">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">总评论数</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $commentTotal; ?></div>
                        <div class="text-xs text-muted">今日评论: <?php echo $todayCommentTotal; ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col-auto">
                        <i class="bi bi-person-badge fs-1 text-warning"></i>
                    </div>
                    <div class="col ms-3">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">VIP用户数</div>
                        <div class="h5 mb-0 font-weight-bold"><?php echo $vipUserTotal; ?></div>
                        <div class="text-xs text-muted">占比: <?php echo $userTotal > 0 ? round(($vipUserTotal / $userTotal) * 100) : 0; ?>%</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 数据趋势图 -->
    <div class="col-xl-8 col-lg-7">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">最近7天数据趋势</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                    <canvas id="trendChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- 漂流瓶心情分布 -->
    <div class="col-xl-4 col-lg-5">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">漂流瓶心情分布</h6>
            </div>
            <div class="card-body">
                <div class="chart-pie">
                    <canvas id="moodChart" style="min-height: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- 最新漂流瓶 -->
    <div class="col-xl-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">最新漂流瓶</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>内容</th>
                                <th>用户</th>
                                <th>心情</th>
                                <th>时间</th>
                                <th>状态</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestBottles as $bottle): ?>
                            <tr>
                                <td><?php echo $bottle['id']; ?></td>
                                <td><?php echo mb_substr($bottle['content'], 0, 20) . (mb_strlen($bottle['content']) > 20 ? '...' : ''); ?></td>
                                <td><?php echo $bottle['is_anonymous'] ? '匿名用户' : $bottle['username']; ?></td>
                                <td><?php echo $bottle['mood']; ?></td>
                                <td><?php echo date('m-d H:i', strtotime($bottle['throw_time'])); ?></td>
                                <td><?php echo $bottle['status']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 text-center">
                    <a href="bottles.php" class="btn btn-sm btn-primary">查看全部</a>
                </div>
            </div>
        </div>
    </div>

    <!-- 最新操作日志 -->
    <div class="col-xl-6">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold">最新操作日志</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>管理员</th>
                                <th>模块</th>
                                <th>操作</th>
                                <th>时间</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($latestLogs as $log): ?>
                            <tr>
                                <td><?php echo $log['username']; ?></td>
                                <td><?php echo $log['module']; ?></td>
                                <td><?php echo $log['action']; ?></td>
                                <td><?php echo date('m-d H:i', strtotime($log['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 text-center">
                    <a href="logs.php" class="btn btn-sm btn-primary">查看全部</a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// 获取心情分布数据
try {
    $moodQuery = "SELECT mood, COUNT(*) as total FROM bottles WHERE mood IS NOT NULL GROUP BY mood ORDER BY total DESC";
    $moodResult = $conn->query($moodQuery);
    if ($moodResult === false) {
        throw new Exception("查询心情分布失败: " . $conn->error);
    }
    $moodLabels = [];
    $moodData = [];
    $backgroundColor = [
        'rgba(255, 99, 132, 0.7)',
        'rgba(54, 162, 235, 0.7)',
        'rgba(255, 206, 86, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(153, 102, 255, 0.7)',
        'rgba(255, 159, 64, 0.7)',
        'rgba(201, 203, 207, 0.7)'
    ];

    $i = 0;
    while ($row = $moodResult->fetch_assoc()) {
        if (!empty($row['mood'])) {
            $moodLabels[] = $row['mood'];
            $moodData[] = (int)$row['total'];
            $i++;
        }
    }
} catch (Exception $e) {
    error_log("获取心情分布数据错误: " . $e->getMessage());
    $moodLabels = [];
    $moodData = [];
}
?>

<!-- 引入Chart.js脚本 -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// 确保页面加载完成后再初始化图表
document.addEventListener('DOMContentLoaded', function() {
    // 数据趋势图
    var ctx = document.getElementById('trendChart');
    if (ctx) {
        var trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($dateLabels); ?>,
                datasets: [{
                    label: '新增用户',
                    data: <?php echo json_encode($userTrends); ?>,
                    backgroundColor: 'rgba(78, 115, 223, 0.05)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(78, 115, 223, 1)',
                    pointBorderColor: 'rgba(78, 115, 223, 1)',
                    pointHoverRadius: 5,
                    tension: 0.3
                }, {
                    label: '新增漂流瓶',
                    data: <?php echo json_encode($bottleTrends); ?>,
                    backgroundColor: 'rgba(28, 200, 138, 0.05)',
                    borderColor: 'rgba(28, 200, 138, 1)',
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(28, 200, 138, 1)',
                    pointBorderColor: 'rgba(28, 200, 138, 1)',
                    pointHoverRadius: 5,
                    tension: 0.3
                }, {
                    label: '新增评论',
                    data: <?php echo json_encode($commentTrends); ?>,
                    backgroundColor: 'rgba(54, 185, 204, 0.05)',
                    borderColor: 'rgba(54, 185, 204, 1)',
                    pointRadius: 3,
                    pointBackgroundColor: 'rgba(54, 185, 204, 1)',
                    pointBorderColor: 'rgba(54, 185, 204, 1)',
                    pointHoverRadius: 5,
                    tension: 0.3
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        });
    }

    // 心情分布图
    var ctxPie = document.getElementById('moodChart');
    if (ctxPie && <?php echo !empty($moodLabels) ? 'true' : 'false'; ?>) {
        var moodChart = new Chart(ctxPie, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode($moodLabels); ?>,
                datasets: [{
                    data: <?php echo json_encode($moodData); ?>,
                    backgroundColor: <?php echo json_encode(array_slice($backgroundColor, 0, count($moodLabels))); ?>,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                        display: true
                    }
                }
            }
        });
    } else if (ctxPie) {
        // 如果没有数据，显示提示信息
        var ctx = ctxPie.getContext('2d');
        ctx.font = '14px Arial';
        ctx.textAlign = 'center';
        ctx.fillText('暂无心情数据', ctxPie.width / 2, ctxPie.height / 2);
    }
});
</script>

<?php
// 记录管理员操作
$admin->logOperation('系统', '访问', '访问控制面板');

// 关闭数据库连接
$conn->close();

// 引入底部
require_once 'includes/footer.php';
?> 