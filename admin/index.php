<?php
$pageTitle = '控制面板';

// 引入头部
require_once 'includes/header.php';

// 初始化数据库连接
$conn = getDbConnection();

// 优化：使用单个查询获取所有基本统计数据（减少8次查询为1次）
try {
    $statsQuery = "
        SELECT 
            (SELECT COUNT(*) FROM users) as user_total,
            (SELECT COUNT(*) FROM bottles) as bottle_total,
            (SELECT COUNT(*) FROM bottles WHERE status = '漂流中') as active_bottle_total,
            (SELECT COUNT(*) FROM comments) as comment_total,
            (SELECT COUNT(*) FROM users WHERE DATE(created_at) = CURDATE()) as today_user_total,
            (SELECT COUNT(*) FROM bottles WHERE DATE(throw_time) = CURDATE()) as today_bottle_total,
            (SELECT COUNT(*) FROM comments WHERE DATE(created_at) = CURDATE()) as today_comment_total,
            (SELECT COUNT(*) FROM users WHERE is_vip = 1) as vip_user_total
    ";
    $statsResult = $conn->query($statsQuery);
    if ($statsResult === false) {
        throw new Exception("查询统计数据失败: " . $conn->error);
    }
    $stats = $statsResult->fetch_assoc();
    
    $userTotal = (int)($stats['user_total'] ?? 0);
    $bottleTotal = (int)($stats['bottle_total'] ?? 0);
    $activeBottleTotal = (int)($stats['active_bottle_total'] ?? 0);
    $commentTotal = (int)($stats['comment_total'] ?? 0);
    $todayUserTotal = (int)($stats['today_user_total'] ?? 0);
    $todayBottleTotal = (int)($stats['today_bottle_total'] ?? 0);
    $todayCommentTotal = (int)($stats['today_comment_total'] ?? 0);
    $vipUserTotal = (int)($stats['vip_user_total'] ?? 0);
} catch (Exception $e) {
    error_log("统计查询错误: " . $e->getMessage());
    // 设置默认值
    $userTotal = $bottleTotal = $activeBottleTotal = $commentTotal = 0;
    $todayUserTotal = $todayBottleTotal = $todayCommentTotal = $vipUserTotal = 0;
}

// 优化：获取最近7天的数据趋势（减少21次查询为3次）
$dateLabels = [];
$userTrends = [];
$bottleTrends = [];
$commentTrends = [];

// 生成日期标签
for ($i = 6; $i >= 0; $i--) {
    $dateLabels[] = date('m-d', strtotime("-$i days"));
}

// 批量查询用户注册趋势（7天数据一次查询）
try {
    $userTrendQuery = "
        SELECT DATE(created_at) as date, COUNT(*) as total 
        FROM users 
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ";
    $userTrendResult = $conn->query($userTrendQuery);
    $userTrendData = [];
    if ($userTrendResult !== false) {
        while ($row = $userTrendResult->fetch_assoc()) {
            $userTrendData[$row['date']] = (int)$row['total'];
        }
    }
    
    // 填充7天数据，缺失的日期为0
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $userTrends[] = $userTrendData[$date] ?? 0;
    }
} catch (Exception $e) {
    error_log("统计用户趋势错误: " . $e->getMessage());
    $userTrends = array_fill(0, 7, 0);
}

// 批量查询漂流瓶趋势（7天数据一次查询）
try {
    $bottleTrendQuery = "
        SELECT DATE(throw_time) as date, COUNT(*) as total 
        FROM bottles 
        WHERE DATE(throw_time) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(throw_time)
        ORDER BY date ASC
    ";
    $bottleTrendResult = $conn->query($bottleTrendQuery);
    $bottleTrendData = [];
    if ($bottleTrendResult !== false) {
        while ($row = $bottleTrendResult->fetch_assoc()) {
            $bottleTrendData[$row['date']] = (int)$row['total'];
        }
    }
    
    // 填充7天数据，缺失的日期为0
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $bottleTrends[] = $bottleTrendData[$date] ?? 0;
    }
} catch (Exception $e) {
    error_log("统计漂流瓶趋势错误: " . $e->getMessage());
    $bottleTrends = array_fill(0, 7, 0);
}

// 批量查询评论趋势（7天数据一次查询）
try {
    $commentTrendQuery = "
        SELECT DATE(created_at) as date, COUNT(*) as total 
        FROM comments 
        WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
        GROUP BY DATE(created_at)
        ORDER BY date ASC
    ";
    $commentTrendResult = $conn->query($commentTrendQuery);
    $commentTrendData = [];
    if ($commentTrendResult !== false) {
        while ($row = $commentTrendResult->fetch_assoc()) {
            $commentTrendData[$row['date']] = (int)$row['total'];
        }
    }
    
    // 填充7天数据，缺失的日期为0
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $commentTrends[] = $commentTrendData[$date] ?? 0;
    }
} catch (Exception $e) {
    error_log("统计评论趋势错误: " . $e->getMessage());
    $commentTrends = array_fill(0, 7, 0);
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