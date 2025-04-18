<?php
// 开启输出缓冲，避免headers already sent错误
ob_start();

// 页面标题
$pageTitle = '数据统计';

// 引入头部
require_once 'includes/header.php';

// 初始化数据库连接
$conn = getDbConnection();

// 记录管理员操作
$admin->logOperation('系统', '查看', '查看数据统计页面');

// 获取时间范围参数
$timeRange = isset($_GET['time_range']) ? $_GET['time_range'] : 'all';

// 设置时间过滤条件
$timeFilter = '';
$startDate = null;
$endDate = date('Y-m-d H:i:s');

switch ($timeRange) {
    case 'today':
        $startDate = date('Y-m-d 00:00:00');
        $timeFilter = "WHERE DATE(throw_time) = CURDATE()";
        break;
    case 'yesterday':
        $startDate = date('Y-m-d 00:00:00', strtotime('-1 day'));
        $endDate = date('Y-m-d 23:59:59', strtotime('-1 day'));
        $timeFilter = "WHERE throw_time BETWEEN '$startDate' AND '$endDate'";
        break;
    case 'week':
        $startDate = date('Y-m-d 00:00:00', strtotime('-7 days'));
        $timeFilter = "WHERE throw_time >= '$startDate'";
        break;
    case 'month':
        $startDate = date('Y-m-d 00:00:00', strtotime('-30 days'));
        $timeFilter = "WHERE throw_time >= '$startDate'";
        break;
    default:
        $timeFilter = "";
}

// 获取漂流瓶总数
$bottleQuery = "SELECT COUNT(*) as total FROM bottles" . ($timeFilter ? " $timeFilter" : "");
$bottleResult = $conn->query($bottleQuery);
$bottleTotal = ($bottleResult && $bottleResult->num_rows > 0) ? $bottleResult->fetch_assoc()['total'] : 0;

// 获取漂流中的瓶子数量
$activeBottleQuery = "SELECT COUNT(*) as total FROM bottles WHERE status = '漂流中' OR status = 'active'" . ($timeFilter ? " AND " . str_replace("WHERE", "", $timeFilter) : "");
$activeBottleResult = $conn->query($activeBottleQuery);
$activeBottleTotal = ($activeBottleResult && $activeBottleResult->num_rows > 0) ? $activeBottleResult->fetch_assoc()['total'] : 0;

// 获取已捡起的瓶子数量
$pickedBottleQuery = "SELECT COUNT(*) as total FROM bottles WHERE status = '已捡起' OR status = 'hidden'" . ($timeFilter ? " AND " . str_replace("WHERE", "", $timeFilter) : "");
$pickedBottleResult = $conn->query($pickedBottleQuery);
$pickedBottleTotal = ($pickedBottleResult && $pickedBottleResult->num_rows > 0) ? $pickedBottleResult->fetch_assoc()['total'] : 0;

// 获取评论总数
$commentQuery = "SELECT COUNT(*) as total FROM comments" . ($timeFilter ? " WHERE " . str_replace("throw_time", "created_at", str_replace("WHERE", "", $timeFilter)) : "");
$commentResult = $conn->query($commentQuery);
$commentTotal = ($commentResult && $commentResult->num_rows > 0) ? $commentResult->fetch_assoc()['total'] : 0;

// 获取用户总数
$userQuery = "SELECT COUNT(*) as total FROM users";
$userResult = $conn->query($userQuery);
$userTotal = ($userResult && $userResult->num_rows > 0) ? $userResult->fetch_assoc()['total'] : 0;

// 获取VIP用户数量
$vipQuery = "SELECT COUNT(*) as total FROM users WHERE is_vip = 1";
$vipResult = $conn->query($vipQuery);
$vipTotal = ($vipResult && $vipResult->num_rows > 0) ? $vipResult->fetch_assoc()['total'] : 0;

// 获取最活跃的用户（发瓶最多）
$activeUserQuery = "SELECT u.id, u.username, COUNT(b.id) as bottle_count 
                   FROM users u 
                   JOIN bottles b ON u.id = b.user_id 
                   " . ($timeFilter ? str_replace("WHERE", "WHERE", $timeFilter) . " AND " : "WHERE ") . "
                   b.user_id IS NOT NULL 
                   GROUP BY u.id 
                   ORDER BY bottle_count DESC 
                   LIMIT 5";
$activeUserResult = $conn->query($activeUserQuery);
$activeUsers = [];
if ($activeUserResult && $activeUserResult->num_rows > 0) {
    while ($row = $activeUserResult->fetch_assoc()) {
        $activeUsers[] = $row;
    }
}

// 获取心情统计
$moodQuery = "SELECT mood, COUNT(*) as count FROM bottles " . 
             ($timeFilter ? "$timeFilter AND " : "WHERE ") . 
             "mood IS NOT NULL GROUP BY mood ORDER BY count DESC";
$moodResult = $conn->query($moodQuery);
$moodStats = [];
if ($moodResult && $moodResult->num_rows > 0) {
    while ($row = $moodResult->fetch_assoc()) {
        $moodStats[] = $row;
    }
}

// 获取每日漂流瓶数量趋势（最近7天）
$dateLabels = [];
$bottleCounts = [];

for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $dateLabels[] = date('m-d', strtotime($date));
    
    $dailyQuery = "SELECT COUNT(*) as count FROM bottles WHERE DATE(throw_time) = '$date'";
    $dailyResult = $conn->query($dailyQuery);
    $dailyCount = ($dailyResult && $dailyResult->num_rows > 0) ? $dailyResult->fetch_assoc()['count'] : 0;
    
    $bottleCounts[] = $dailyCount;
}

// 关闭数据库连接
$conn->close();
?>

<!-- 内容区域 -->
<div class="container-fluid px-4">
    <h1 class="mt-4"><?php echo $pageTitle; ?></h1>
    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="index.php">控制台</a></li>
        <li class="breadcrumb-item active"><?php echo $pageTitle; ?></li>
    </ol>
    
    <!-- 时间筛选器 -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-calendar me-1"></i>
            时间范围
        </div>
        <div class="card-body">
            <form method="get" class="d-flex">
                <div class="btn-group" role="group">
                    <a href="?time_range=all" class="btn btn-outline-primary <?php echo $timeRange == 'all' ? 'active' : ''; ?>">全部时间</a>
                    <a href="?time_range=today" class="btn btn-outline-primary <?php echo $timeRange == 'today' ? 'active' : ''; ?>">今天</a>
                    <a href="?time_range=yesterday" class="btn btn-outline-primary <?php echo $timeRange == 'yesterday' ? 'active' : ''; ?>">昨天</a>
                    <a href="?time_range=week" class="btn btn-outline-primary <?php echo $timeRange == 'week' ? 'active' : ''; ?>">最近7天</a>
                    <a href="?time_range=month" class="btn btn-outline-primary <?php echo $timeRange == 'month' ? 'active' : ''; ?>">最近30天</a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- 概览数据卡片 -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><?php echo $bottleTotal; ?></h5>
                            <div class="small">漂流瓶总数</div>
                        </div>
                        <div class="display-4">
                            <i class="bi bi-envelope"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <div class="small text-white">
                        <span class="me-2">漂流中: <?php echo $activeBottleTotal; ?></span>
                        <span>已捡起: <?php echo $pickedBottleTotal; ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><?php echo $commentTotal; ?></h5>
                            <div class="small">评论总数</div>
                        </div>
                        <div class="display-4">
                            <i class="bi bi-chat-dots"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <div class="small text-white">
                        <span>平均每瓶 <?php echo $bottleTotal > 0 ? round($commentTotal / $bottleTotal, 2) : 0; ?> 条评论</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><?php echo $userTotal; ?></h5>
                            <div class="small">用户总数</div>
                        </div>
                        <div class="display-4">
                            <i class="bi bi-people"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <div class="small text-white">
                        <span>VIP用户: <?php echo $vipTotal; ?> (<?php echo $userTotal > 0 ? round(($vipTotal / $userTotal) * 100, 1) : 0; ?>%)</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="mb-0"><?php echo count($moodStats) > 0 ? $moodStats[0]['mood'] : '无数据'; ?></h5>
                            <div class="small">最热门心情</div>
                        </div>
                        <div class="display-4">
                            <i class="bi bi-emoji-smile"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <div class="small text-white">
                        <span>共有 <?php echo count($moodStats) > 0 ? $moodStats[0]['count'] : 0; ?> 个漂流瓶使用此心情</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 图表区域 -->
    <div class="row">
        <!-- 漂流瓶数量趋势 -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-line me-1"></i>
                    每日漂流瓶数量趋势（最近7天）
                </div>
                <div class="card-body">
                    <canvas id="bottleTrendChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
        
        <!-- 心情分布 -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    心情分布
                </div>
                <div class="card-body">
                    <canvas id="moodChart" width="100%" height="50"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- 数据表格 -->
    <div class="row">
        <!-- 最活跃用户 -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-trophy me-1"></i>
                    最活跃用户（发瓶最多）
                </div>
                <div class="card-body">
                    <?php if (count($activeUsers) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>排名</th>
                                    <th>用户名</th>
                                    <th>漂流瓶数量</th>
                                    <th>操作</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activeUsers as $index => $user): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo $user['bottle_count']; ?></td>
                                    <td>
                                        <a href="user_detail.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-primary">查看</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">暂无数据</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- 心情统计 -->
        <div class="col-lg-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-list me-1"></i>
                    心情统计
                </div>
                <div class="card-body">
                    <?php if (count($moodStats) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th>心情</th>
                                    <th>数量</th>
                                    <th>占比</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($moodStats as $mood): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($mood['mood']); ?></td>
                                    <td><?php echo $mood['count']; ?></td>
                                    <td><?php echo $bottleTotal > 0 ? round(($mood['count'] / $bottleTotal) * 100, 2) : 0; ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">暂无数据</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 引入Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// 漂流瓶数量趋势图
var bottleTrendCtx = document.getElementById('bottleTrendChart').getContext('2d');
var bottleTrendChart = new Chart(bottleTrendCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode($dateLabels); ?>,
        datasets: [{
            label: '漂流瓶数量',
            data: <?php echo json_encode($bottleCounts); ?>,
            backgroundColor: 'rgba(75, 192, 192, 0.2)',
            borderColor: 'rgba(75, 192, 192, 1)',
            borderWidth: 2,
            tension: 0.1
        }]
    },
    options: {
        scales: {
            y: {
                beginAtZero: true,
                precision: 0
            }
        },
        plugins: {
            legend: {
                display: true
            }
        }
    }
});

// 心情分布图
var moodCtx = document.getElementById('moodChart').getContext('2d');
var moodData = <?php 
    $moodLabels = [];
    $moodCounts = [];
    $backgroundColors = [
        'rgba(255, 99, 132, 0.7)',
        'rgba(54, 162, 235, 0.7)',
        'rgba(255, 206, 86, 0.7)',
        'rgba(75, 192, 192, 0.7)',
        'rgba(153, 102, 255, 0.7)',
        'rgba(255, 159, 64, 0.7)',
        'rgba(199, 199, 199, 0.7)'
    ];
    
    foreach ($moodStats as $index => $mood) {
        $moodLabels[] = $mood['mood'];
        $moodCounts[] = $mood['count'];
    }
    
    echo json_encode([
        'labels' => $moodLabels,
        'counts' => $moodCounts,
        'colors' => array_slice($backgroundColors, 0, count($moodLabels))
    ]);
?>;

var moodChart = new Chart(moodCtx, {
    type: 'pie',
    data: {
        labels: moodData.labels,
        datasets: [{
            data: moodData.counts,
            backgroundColor: moodData.colors,
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'right'
            }
        }
    }
});
</script>

<?php
// 引入底部
require_once 'includes/footer.php';
?> 