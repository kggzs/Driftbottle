<?php
/* *
 * 功能：支付异步通知处理页面
 * 说明：处理支付平台的异步回调通知
 */

require_once("includes/config.php");
require_once("includes/payment/lib/epay.config.php");
require_once("includes/payment/lib/EpayCore.class.php");
require_once("includes/user.php");

// 获取支付配置
$epay_config = getPaymentConfig();
$epay = new EpayCore($epay_config);

// 获取回调数据（GET或POST）
$notify_data = array_merge($_GET, $_POST);

// 验证签名
$verify_result = $epay->verify($notify_data);

if($verify_result) {
    // 验证成功
    
    // 商户订单号
    $out_trade_no = $notify_data['out_trade_no'] ?? '';
    
    // 支付平台交易号
    $trade_no = $notify_data['trade_no'] ?? '';
    
    // 交易状态
    $trade_status = $notify_data['trade_status'] ?? '';
    
    // 支付方式
    $payment_type = $notify_data['type'] ?? '';
    
    // 支付金额
    $money = $notify_data['money'] ?? 0;
    
    if ($trade_status == 'TRADE_SUCCESS') {
        // 订单支付成功，处理业务逻辑
        $conn = null;
        try {
            $conn = getDbConnection();
            $conn->begin_transaction();
            
            // 查询订单（不限制status，允许重复处理检查）
            $stmt = $conn->prepare("SELECT * FROM recharge_orders WHERE order_no = ? FOR UPDATE");
            $stmt->bind_param("s", $out_trade_no);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            $stmt->close();
            
            if ($order) {
                // 检查订单是否已处理
                if ($order['status'] == 1) {
                    // 订单已处理，直接返回success
                    $conn->rollback();
                    $conn->close();
                    error_log("订单已处理: {$out_trade_no}");
                    echo "success";
                    exit;
                }
                
                // 订单存在且未支付，处理充值
                $user_id = $order['user_id'];
                $points = $order['points'];
                
                error_log("开始处理充值订单: {$out_trade_no}, 用户ID: {$user_id}, 积分: {$points}");
                
                // 更新订单状态
                $updateStmt = $conn->prepare("UPDATE recharge_orders SET status = 1, trade_no = ?, payment_type = ?, paid_at = NOW(), notify_data = ? WHERE order_no = ?");
                $notify_json = json_encode($notify_data, JSON_UNESCAPED_UNICODE);
                $updateStmt->bind_param("ssss", $trade_no, $payment_type, $notify_json, $out_trade_no);
                if (!$updateStmt->execute()) {
                    throw new Exception("更新订单状态失败: " . $updateStmt->error);
                }
                $updateStmt->close();
                
                // 增加用户积分
                $pointsStmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
                $pointsStmt->bind_param("ii", $points, $user_id);
                if (!$pointsStmt->execute()) {
                    throw new Exception("增加积分失败: " . $pointsStmt->error);
                }
                $pointsStmt->close();
                
                // 记录积分历史
                $action = "充值获得积分（订单号：{$out_trade_no}）";
                $historyStmt = $conn->prepare("INSERT INTO points_history (user_id, points, action) VALUES (?, ?, ?)");
                $historyStmt->bind_param("iis", $user_id, $points, $action);
                if (!$historyStmt->execute()) {
                    // 积分历史记录失败不影响主流程，只记录日志
                    error_log("记录积分历史失败: " . $historyStmt->error);
                }
                $historyStmt->close();
                
                $conn->commit();
                error_log("充值订单处理成功: {$out_trade_no}, 用户ID: {$user_id}, 积分: {$points}");
                
                // 返回success告诉支付平台处理成功
                echo "success";
            } else {
                // 订单不存在
                $conn->rollback();
                error_log("订单不存在: {$out_trade_no}");
                echo "success"; // 即使订单不存在，也返回success避免重复通知
            }
            
            if ($conn) {
                $conn->close();
            }
        } catch (Exception $e) {
            if ($conn) {
                $conn->rollback();
                $conn->close();
            }
            error_log("支付回调处理失败: " . $e->getMessage());
            echo "fail";
        }
    } else {
        // 其他交易状态
        echo "success";
    }
} else {
    // 验证失败
    error_log("支付回调验签失败: " . json_encode($notify_data));
    echo "fail";
}
