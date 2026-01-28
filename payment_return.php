<?php
/* * 
 * 功能：支付页面跳转同步通知页面
 * 说明：用户支付完成后跳转回网站的页面
 */

// 开启错误报告（调试用，生产环境应关闭）
// error_reporting(E_ALL);
// ini_set('display_errors', 1);

// 确保文件路径正确
$baseDir = __DIR__;
require_once($baseDir . "/includes/config.php");
require_once($baseDir . "/includes/payment/lib/epay.config.php");
require_once($baseDir . "/includes/payment/lib/EpayCore.class.php");

// 获取支付配置
try {
    $epay_config = getPaymentConfig();
    $epay = new EpayCore($epay_config);
} catch (Exception $e) {
    die("支付配置加载失败: " . $e->getMessage());
}
?>
<!DOCTYPE HTML>
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>支付返回页面</title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="assets/css/bootstrap/bootstrap.min.css">
	<style>
		body {
			display: flex;
			justify-content: center;
			align-items: center;
			min-height: 100vh;
			background-color: #f5f5f5;
		}
		.container {
			background: white;
			padding: 2rem;
			border-radius: 8px;
			box-shadow: 0 2px 10px rgba(0,0,0,0.1);
			max-width: 500px;
			width: 100%;
		}
	</style>
	</head>
	<body>
		<div class="container">
<?php
// 计算得出通知验证结果
$verify_result = $epay->verify($_GET);

if($verify_result) {
    // 验证成功
    
    // 商户订单号
    $out_trade_no = $_GET['out_trade_no'] ?? '';
    
    // 支付平台交易号
    $trade_no = $_GET['trade_no'] ?? '';
    
    // 交易状态
    $trade_status = $_GET['trade_status'] ?? '';
    
    // 支付方式
    $payment_type = $_GET['type'] ?? '';
    
    if($_GET['trade_status'] == 'TRADE_SUCCESS') {
        // 支付成功，检查并处理积分（作为异步通知的备用）
        $conn = null;
        try {
            $conn = getDbConnection();
            
            // 查询订单状态
            $stmt = $conn->prepare("SELECT * FROM recharge_orders WHERE order_no = ?");
            $stmt->bind_param("s", $out_trade_no);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            $stmt->close();
            
            if ($order && $order['status'] == 0) {
                // 订单未处理，尝试处理（异步通知可能未到达）
                $conn->begin_transaction();
                
                $user_id = $order['user_id'];
                $points = $order['points'];
                
                // 更新订单状态
                $updateStmt = $conn->prepare("UPDATE recharge_orders SET status = 1, trade_no = ?, payment_type = ?, paid_at = NOW() WHERE order_no = ?");
                $updateStmt->bind_param("sss", $trade_no, $payment_type, $out_trade_no);
                $updateStmt->execute();
                $updateStmt->close();
                
                // 增加用户积分
                $pointsStmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
                $pointsStmt->bind_param("ii", $points, $user_id);
                $pointsStmt->execute();
                $pointsStmt->close();
                
                // 记录积分历史
                $action = "充值获得积分（订单号：{$out_trade_no}）";
                $historyStmt = $conn->prepare("INSERT INTO points_history (user_id, points, action) VALUES (?, ?, ?)");
                $historyStmt->bind_param("iis", $user_id, $points, $action);
                $historyStmt->execute();
                $historyStmt->close();
                
                $conn->commit();
                error_log("同步返回页面处理充值成功: {$out_trade_no}");
            }
            
            if ($conn) {
                $conn->close();
            }
        } catch (Exception $e) {
            if ($conn) {
                try {
                    $conn->rollback();
                } catch (Exception $e2) {}
                $conn->close();
            }
            error_log("同步返回页面处理充值失败: " . $e->getMessage());
        }
        
        // 支付成功
        echo "<div class='alert alert-success'>";
        echo "<h3><i class='fas fa-check-circle'></i> 支付成功</h3>";
        echo "<p>订单号：{$out_trade_no}</p>";
        echo "<p>交易号：{$trade_no}</p>";
        echo "<p>支付方式：{$payment_type}</p>";
        echo "<p class='text-success'><strong>积分已到账，请刷新页面查看</strong></p>";
        echo "<p class='mt-3'><a href='profile_info.html' class='btn btn-primary'>返回个人中心</a></p>";
        echo "</div>";
        
        // 跳转到个人中心（3秒后）
        echo "<script>setTimeout(function(){ window.location.href = 'profile_info.html'; }, 3000);</script>";
    } else {
        // 其他状态
        echo "<div class='alert alert-warning'>";
        echo "<h3>支付状态：{$trade_status}</h3>";
        echo "<p>订单号：{$out_trade_no}</p>";
        echo "<p class='mt-3'><a href='profile_info.html' class='btn btn-primary'>返回个人中心</a></p>";
        echo "</div>";
    }
} else {
    // 验证失败
    echo "<div class='alert alert-danger'>";
    echo "<h3><i class='fas fa-exclamation-triangle'></i> 验证失败</h3>";
    echo "<p>支付验证失败，请联系客服处理</p>";
    echo "<p class='mt-3'><a href='profile_info.html' class='btn btn-primary'>返回个人中心</a></p>";
    echo "</div>";
}
?>
		</div>
	</body>
</html>
