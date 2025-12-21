<?php
require_once 'config.php';

// 获取用户当日的扔瓶和捡瓶次数
function getUserDailyLimits($userId) {
    $conn = getDbConnection();
    $today = date('Y-m-d');
    
    // 检查用户是否为VIP
    $isVip = false;
    $vipLevel = 0;
    
    $vipStmt = $conn->prepare("SELECT is_vip, vip_level FROM users WHERE id = ?");
    $vipStmt->bind_param("i", $userId);
    $vipStmt->execute();
    $vipResult = $vipStmt->get_result();
    
    if ($vipResult->num_rows > 0) {
        $vipData = $vipResult->fetch_assoc();
        $isVip = $vipData['is_vip'] == 1;
        $vipLevel = $vipData['vip_level'];
    }
    $vipStmt->close();
    
    $stmt = $conn->prepare("SELECT * FROM daily_limits WHERE user_id = ? AND date = ?");
    $stmt->bind_param("is", $userId, $today);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // 今天还没有记录，创建一条
        $insertStmt = $conn->prepare("INSERT INTO daily_limits (user_id, date) VALUES (?, ?)");
        $insertStmt->bind_param("is", $userId, $today);
        $insertStmt->execute();
        $insertStmt->close();
        
        $limits = [
            'throw_count' => 0,
            'pick_count' => 0,
            'free_throws_used' => 0
        ];
    } else {
        $limits = $result->fetch_assoc();
    }
    
    // 添加VIP状态和额外限制
    $limits['is_vip'] = $isVip;
    $limits['vip_level'] = $vipLevel;
    
    // 从配置获取免费次数限制
    $standardFreeLimit = DAILY_BOTTLE_LIMIT; // 普通用户每日免费扔瓶次数
    $vipFreeLimit = VIP_DAILY_BOTTLE_LIMIT; // VIP用户每日免费扔瓶次数
    $vipExtraLimit = $vipFreeLimit - $standardFreeLimit; // VIP用户额外次数
    
    // 计算剩余的免费次数
// 对于VIP用户，免费次数包括标准免费次数和VIP额外次数
$totalFreeLimit = $isVip ? $vipFreeLimit : $standardFreeLimit;
$limits['free_throws_remaining'] = max(0, $totalFreeLimit - $limits['free_throws_used']);
    
// 计算VIP专属额外次数
if ($isVip) {
    // 总免费次数 = VIP用户每日免费次数
    $limits['vip_free_throws'] = $vipFreeLimit;
    
    // 计算VIP专属剩余次数
    // VIP专属次数是指超出标准免费次数的部分
    $totalUsed = $limits['free_throws_used'];
    $standardUsed = min($totalUsed, $standardFreeLimit);
    $vipUsed = max(0, $totalUsed - $standardFreeLimit);
    $limits['vip_free_throws_remaining'] = max(0, $vipExtraLimit - $vipUsed);
    
    // 普通免费次数剩余（即使是VIP用户，也需要显示标准免费次数的剩余）
    $limits['standard_free_throws_remaining'] = max(0, $standardFreeLimit - $standardUsed);
    
    // 添加调试信息
    error_log("VIP用户 {$userId} 的免费次数使用情况：");
    error_log("已使用总次数: {$totalUsed}");
    error_log("已使用普通次数: {$standardUsed}");
    error_log("已使用VIP额外次数: {$vipUsed}");
    error_log("普通免费剩余: {$limits['standard_free_throws_remaining']}");
    error_log("VIP专属剩余: {$limits['vip_free_throws_remaining']}");
    error_log("总免费剩余: {$limits['free_throws_remaining']}");
} else {
    // 非VIP用户没有VIP专属次数
    $limits['vip_free_throws_remaining'] = 0;
    $limits['standard_free_throws_remaining'] = $limits['free_throws_remaining'];
}
    
    $stmt->close();
    $conn->close();
    return $limits;
}

// 更新用户当日的扔瓶或捡瓶次数
function updateUserDailyLimit($userId, $type, $increment = 1) {
    $conn = getDbConnection();
    $today = date('Y-m-d');
    
    // 确保有今日记录
    $limits = getUserDailyLimits($userId);
    
    if ($type === 'throw') {
        $field = 'throw_count';
    } else if ($type === 'pick') {
        $field = 'pick_count';
    } else if ($type === 'free_throw') {
        $field = 'free_throws_used';
        
        // 增加安全检查，确保免费次数不会超过上限
        $maxFreeThrows = $limits['is_vip'] ? VIP_DAILY_BOTTLE_LIMIT : DAILY_BOTTLE_LIMIT;
        if ($limits['free_throws_used'] >= $maxFreeThrows) {
            $conn->close();
            return false; // 已达到免费次数上限
        }
    } else {
        $conn->close();
        return false;
    }
    
    $stmt = $conn->prepare("UPDATE daily_limits SET $field = $field + ? WHERE user_id = ? AND date = ?");
    $stmt->bind_param("iis", $increment, $userId, $today);
    $result = $stmt->execute();
    $stmt->close();
    $conn->close();
    
    return $result;
}

// 检查用户今天是否超过了扔瓶或捡瓶限制
function checkUserDailyLimit($userId, $type) {
    $limits = getUserDailyLimits($userId);
    $isVip = $limits['is_vip'];
    
    if ($type === 'throw') {
        return true; // 移除扔瓶总次数限制
    } else if ($type === 'pick') {
        // VIP用户和非VIP用户的捡瓶限制从配置获取
        $pickLimit = $isVip ? VIP_DAILY_PICK_LIMIT : DAILY_PICK_LIMIT;
        return $limits['pick_count'] < $pickLimit;
    } else if ($type === 'free_throw') {
        // VIP用户和非VIP用户的扔瓶限制从配置获取
        $freeThrowLimit = $isVip ? VIP_DAILY_BOTTLE_LIMIT : DAILY_BOTTLE_LIMIT;
        return $limits['free_throws_used'] < $freeThrowLimit;
    }
    
    return false;
}

// 获取用户积分
function getUserPoints($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT points FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $points = $user['points'];
    } else {
        $points = 0;
    }
    
    $stmt->close();
    $conn->close();
    return $points;
}

// 更新用户积分
function updateUserPoints($userId, $points, $action) {
    $conn = getDbConnection();
    
    // 更新用户积分
    $stmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
    $stmt->bind_param("ii", $points, $userId);
    $stmt->execute();
    $stmt->close();
    
    // 记录积分历史
    $stmt = $conn->prepare("INSERT INTO points_history (user_id, points, action) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $userId, $points, $action);
    $stmt->execute();
    $stmt->close();
    
    $conn->close();
    return true;
}

// 获取客户端IP地址
function getClientIpAddress() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    
    // 对于VIP显示完整IP，非VIP显示部分隐藏的IP
    return $ip;
}

// 获取IP地址的地理位置
function getLocationFromIp($ip) {
    return getLocationByIp($ip); // 直接调用已更新的getLocationByIp函数确保一致性
}

// 掩盖IP地址的部分数字（非VIP用户查看时使用）
function maskIpAddress($ip) {
    $parts = explode('.', $ip);
    if (count($parts) === 4) { // IPv4
        return "{$parts[0]}.{$parts[1]}.*.*";
    } else { // IPv6或其他格式
        return substr($ip, 0, strpos($ip, '.')) . ".*.*.*";
    }
}

// 创建漂流瓶
function createBottle($userId, $content, $isAnonymous = 0, $mood = '其他', $bottleType = 'text', $audioFile = null, $audioDuration = 0) {
    $conn = getDbConnection();
    
    // 检查是否在免费次数范围内
    $isFreeThrow = checkUserDailyLimit($userId, 'free_throw');
    
    // 如果不是免费的，检查用户是否有足够的积分
    if (!$isFreeThrow) {
        $userPoints = getUserPoints($userId);
        $requiredPoints = POINTS_PER_BOTTLE;
        if ($userPoints < $requiredPoints) {
            $conn->close();
            return ['success' => false, 'message' => "您的积分不足，扔漂流瓶需要{$requiredPoints}积分，您当前有{$userPoints}积分"];
        }
    }
    
    // 验证漂流瓶类型和内容
    if ($bottleType === 'voice') {
        if (empty($audioFile)) {
            $conn->close();
            return ['success' => false, 'message' => '语音文件不能为空'];
        }
        // 语音漂流瓶使用默认内容或空内容
        if (empty($content)) {
            $content = '[语音漂流瓶]';
        }
    } else {
        if (empty($content)) {
            $conn->close();
            return ['success' => false, 'message' => '漂流瓶内容不能为空'];
        }
    }
    
    // 获取IP地址和位置信息
    $ipAddress = getClientIpAddress();
    $location = getLocationFromIp($ipAddress);
    
    // 创建漂流瓶
    if ($bottleType === 'voice') {
        $stmt = $conn->prepare("INSERT INTO bottles (user_id, content, bottle_type, audio_file, audio_duration, is_anonymous, mood, ip_address, location) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isssissss", $userId, $content, $bottleType, $audioFile, $audioDuration, $isAnonymous, $mood, $ipAddress, $location);
    } else {
        $stmt = $conn->prepare("INSERT INTO bottles (user_id, content, bottle_type, is_anonymous, mood, ip_address, location) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississs", $userId, $content, $bottleType, $isAnonymous, $mood, $ipAddress, $location);
    }
    
    if ($stmt->execute()) {
        $bottleId = $conn->insert_id;
        $stmt->close();
        
        // 更新今日扔瓶计数
        updateUserDailyLimit($userId, 'throw');
        
        // 如果是免费次数内，更新免费次数使用计数
        if ($isFreeThrow) {
            updateUserDailyLimit($userId, 'free_throw');
        } else {
            // 不是免费的，扣除积分
            $pointsDeducted = POINTS_PER_BOTTLE;
            updateUserPoints($userId, -$pointsDeducted, '扔出漂流瓶');
        }
        
        // 增加经验值
        require_once __DIR__ . '/user.php';
        $expResult = updateUserExperience($userId, EXP_PER_BOTTLE, '发漂流瓶');
        
        $conn->close();
        return [
            'success' => true, 
            'bottle_id' => $bottleId,
            'is_free' => $isFreeThrow,
            'points_deducted' => $isFreeThrow ? 0 : POINTS_PER_BOTTLE,
            'experience_gained' => EXP_PER_BOTTLE,
            'level_up' => $expResult['level_up'] ?? false,
            'new_level' => $expResult['level'] ?? null
        ];
    } else {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => '创建漂流瓶失败，请稍后再试'];
    }
}

// 随机捡起一个漂流瓶
function pickRandomBottle($userId) {
    $conn = getDbConnection();
    
    // 检查用户今天是否已经超过了捡瓶限制
    if (!checkUserDailyLimit($userId, 'pick')) {
        $conn->close();
        return ['success' => false, 'message' => '您今天已达到捡漂流瓶的次数限制'];
    }
    
    // 检查用户是否是VIP
    $userLimits = getUserDailyLimits($userId);
    $isVip = $userLimits['is_vip'];
    
    // 准备SQL查询，VIP用户优先匹配高质量漂流瓶
    $orderBy = $isVip ? "ORDER BY b.quality_score DESC, RAND()" : "ORDER BY RAND()";
    
    // 查找一个当前用户未捡起过且不是自己扔的漂流瓶
    $query = "
        SELECT b.*, u.username, u.gender, u.is_vip as user_is_vip
        FROM bottles b
        JOIN users u ON b.user_id = u.id
        WHERE b.status = '漂流中' 
        AND b.user_id != ? 
        AND b.id NOT IN (
            SELECT bottle_id FROM pick_records WHERE user_id = ?
        )
        $orderBy 
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $userId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $bottle = $result->fetch_assoc();
        
        // 如果是匿名瓶子，隐藏用户名
        if ($bottle['is_anonymous'] == 1) {
            $bottle['username'] = '匿名用户';
        }
        
        // 处理IP地址显示（VIP可以看到完整IP，非VIP看到掩盖的IP）
        if ($isVip) {
            $bottle['ip_address'] = $bottle['ip_address'];
        } else {
            $bottle['ip_address'] = $bottle['ip_address'] ? maskIpAddress($bottle['ip_address']) : null;
        }
        
        // 更新漂流瓶状态
        $updateStmt = $conn->prepare("UPDATE bottles SET status = '已捡起' WHERE id = ?");
        $updateStmt->bind_param("i", $bottle['id']);
        $updateStmt->execute();
        $updateStmt->close();
        
        // 记录捡瓶子记录
        $recordStmt = $conn->prepare("INSERT INTO pick_records (bottle_id, user_id) VALUES (?, ?)");
        $recordStmt->bind_param("ii", $bottle['id'], $userId);
        $recordStmt->execute();
        $recordStmt->close();
        
        // 更新今日捡瓶计数
        updateUserDailyLimit($userId, 'pick');
        
        // 增加经验值
        require_once __DIR__ . '/user.php';
        $expResult = updateUserExperience($userId, EXP_PER_PICK, '捡漂流瓶');
        
        // 获取点赞数
        $likeStmt = $conn->prepare("SELECT COUNT(*) as like_count FROM likes WHERE bottle_id = ?");
        $likeStmt->bind_param("i", $bottle['id']);
        $likeStmt->execute();
        $likeResult = $likeStmt->get_result();
        $bottle['like_count'] = $likeResult->fetch_assoc()['like_count'];
        $likeStmt->close();
        
        // 获取评论
        $commentStmt = $conn->prepare("
            SELECT c.*, u.username, u.gender 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.bottle_id = ? 
            ORDER BY c.created_at
        ");
        $commentStmt->bind_param("i", $bottle['id']);
        $commentStmt->execute();
        $commentsResult = $commentStmt->get_result();
        
        $comments = [];
        while ($comment = $commentsResult->fetch_assoc()) {
            $comments[] = $comment;
        }
        $bottle['comments'] = $comments;
        $commentStmt->close();
        
        // 检查当前用户是否已点赞
        $hasLikedStmt = $conn->prepare("SELECT id FROM likes WHERE bottle_id = ? AND user_id = ?");
        $hasLikedStmt->bind_param("ii", $bottle['id'], $userId);
        $hasLikedStmt->execute();
        $hasLikedResult = $hasLikedStmt->get_result();
        $bottle['has_liked'] = ($hasLikedResult->num_rows > 0);
        $hasLikedStmt->close();
        
        // 添加经验值相关信息到返回结果
        $bottle['experience_gained'] = EXP_PER_PICK;
        $bottle['level_up'] = $expResult['level_up'] ?? false;
        $bottle['new_level'] = $expResult['level'] ?? null;
        
        $stmt->close();
        $conn->close();
        return ['success' => true, 'bottle' => $bottle];
    } else {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => '暂时没有可捡起的漂流瓶'];
    }
}

// 添加评论并再次扔出
function commentAndThrowBottle($bottleId, $userId, $content) {
    $conn = getDbConnection();
    
    // 先添加评论
    $stmt = $conn->prepare("INSERT INTO comments (bottle_id, user_id, content) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $bottleId, $userId, $content);
    
    if ($stmt->execute()) {
        $commentId = $conn->insert_id;
        
        // 更新漂流瓶状态为漂流中
        $updateStmt = $conn->prepare("UPDATE bottles SET status = '漂流中' WHERE id = ?");
        $updateStmt->bind_param("i", $bottleId);
        $updateStmt->execute();
        $updateStmt->close();
        
        // 获取漂流瓶所有者信息，用于发送消息
        $bottleStmt = $conn->prepare("SELECT user_id FROM bottles WHERE id = ?");
        $bottleStmt->bind_param("i", $bottleId);
        $bottleStmt->execute();
        $bottleResult = $bottleStmt->get_result();
        
        if ($bottleResult->num_rows === 1) {
            $bottleOwnerId = $bottleResult->fetch_assoc()['user_id'];
            
            // 创建消息（如果评论者不是瓶子的所有者）
            if ($bottleOwnerId != $userId) {
                $msgStmt = $conn->prepare("
                    INSERT INTO messages (user_id, bottle_id, from_user_id, comment_id, type, content) 
                    VALUES (?, ?, ?, ?, '评论', ?)
                ");
                $msgStmt->bind_param("iiiis", $bottleOwnerId, $bottleId, $userId, $commentId, $content);
                $msgStmt->execute();
                $msgStmt->close();
                
                // 给瓶子所有者增加积分，使用与点赞相同的积分值
                $pointsToAdd = POINTS_PER_LIKE;
                updateUserPoints($bottleOwnerId, $pointsToAdd, '收到漂流瓶评论');
            }
        }
        $bottleStmt->close();
        
        // 给评论者增加经验值
        require_once __DIR__ . '/user.php';
        $expResult = updateUserExperience($userId, EXP_PER_COMMENT, '评论漂流瓶');
        
        $stmt->close();
        $conn->close();
        return [
            'success' => true,
            'experience_gained' => EXP_PER_COMMENT,
            'level_up' => $expResult['level_up'] ?? false,
            'new_level' => $expResult['level'] ?? null
        ];
    } else {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => '评论失败，请稍后再试'];
    }
}

// 点赞漂流瓶
function likeBottle($bottleId, $userId) {
    $conn = getDbConnection();
    
    // 检查是否已经点赞
    $checkStmt = $conn->prepare("SELECT id FROM likes WHERE bottle_id = ? AND user_id = ?");
    $checkStmt->bind_param("ii", $bottleId, $userId);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        // 已经点赞过，取消点赞
        $likeId = $result->fetch_assoc()['id'];
        
        $deleteStmt = $conn->prepare("DELETE FROM likes WHERE bottle_id = ? AND user_id = ?");
        $deleteStmt->bind_param("ii", $bottleId, $userId);
        $success = $deleteStmt->execute();
        $deleteStmt->close();
        
        if ($success) {
            // 更新瓶子的点赞计数
            $updateStmt = $conn->prepare("UPDATE bottles SET likes = likes - 1 WHERE id = ?");
            $updateStmt->bind_param("i", $bottleId);
            $updateStmt->execute();
            $updateStmt->close();
            
            // 删除相关消息
            $deleteMsgStmt = $conn->prepare("DELETE FROM messages WHERE like_id = ?");
            $deleteMsgStmt->bind_param("i", $likeId);
            $deleteMsgStmt->execute();
            $deleteMsgStmt->close();
            
            $checkStmt->close();
            $conn->close();
            return ['success' => true, 'action' => 'unlike'];
        }
    } else {
        // 未点赞过，添加点赞
        $likeStmt = $conn->prepare("INSERT INTO likes (bottle_id, user_id) VALUES (?, ?)");
        $likeStmt->bind_param("ii", $bottleId, $userId);
        $success = $likeStmt->execute();
        $likeId = $conn->insert_id;
        $likeStmt->close();
        
        if ($success) {
            // 更新瓶子的点赞计数
            $updateStmt = $conn->prepare("UPDATE bottles SET likes = likes + 1 WHERE id = ?");
            $updateStmt->bind_param("i", $bottleId);
            $updateStmt->execute();
            $updateStmt->close();
            
            // 获取漂流瓶所有者信息，用于发送消息
            $bottleStmt = $conn->prepare("SELECT user_id FROM bottles WHERE id = ?");
            $bottleStmt->bind_param("i", $bottleId);
            $bottleStmt->execute();
            $bottleResult = $bottleStmt->get_result();
            
            if ($bottleResult->num_rows === 1) {
                $bottleOwnerId = $bottleResult->fetch_assoc()['user_id'];
                
                // 创建消息（如果点赞者不是瓶子的所有者）
                if ($bottleOwnerId != $userId) {
                    $msgStmt = $conn->prepare("
                        INSERT INTO messages (user_id, bottle_id, from_user_id, like_id, type) 
                        VALUES (?, ?, ?, ?, '点赞')
                    ");
                    $msgStmt->bind_param("iiii", $bottleOwnerId, $bottleId, $userId, $likeId);
                    $msgStmt->execute();
                    $msgStmt->close();
                    
                    // 给瓶子所有者增加积分
                    $pointsToAdd = POINTS_PER_LIKE; // 使用系统设置的点赞积分值
                    updateUserPoints($bottleOwnerId, $pointsToAdd, '收到漂流瓶点赞');
                }
            }
            $bottleStmt->close();
            
            $checkStmt->close();
            $conn->close();
            return ['success' => true, 'action' => 'like'];
        }
    }
    
    $checkStmt->close();
    $conn->close();
    return ['success' => false, 'message' => '操作失败，请稍后再试'];
}

// 获取用户的漂流瓶历史
function getUserBottles($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT b.*, 
               (SELECT COUNT(*) FROM likes WHERE bottle_id = b.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE bottle_id = b.id) as comment_count
        FROM bottles b
        WHERE b.user_id = ?
        ORDER BY b.throw_time DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bottles = [];
    while ($bottle = $result->fetch_assoc()) {
        $bottles[] = $bottle;
    }
    
    $stmt->close();
    $conn->close();
    
    return ['success' => true, 'bottles' => $bottles];
}

// 获取用户捡过的漂流瓶
function getUserPickedBottles($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT b.*, u.username, u.gender, pr.pick_time,
               (SELECT COUNT(*) FROM likes WHERE bottle_id = b.id) as like_count,
               (SELECT COUNT(*) FROM comments WHERE bottle_id = b.id) as comment_count
        FROM pick_records pr
        JOIN bottles b ON pr.bottle_id = b.id
        JOIN users u ON b.user_id = u.id
        WHERE pr.user_id = ?
        ORDER BY pr.pick_time DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bottles = [];
    while ($bottle = $result->fetch_assoc()) {
        // 处理匿名瓶子
        if ($bottle['is_anonymous'] == 1) {
            $bottle['username'] = '匿名用户';
        }
        
        // 获取该漂流瓶的评论
        $commentStmt = $conn->prepare("
            SELECT c.*, u.username, u.gender 
            FROM comments c
            JOIN users u ON c.user_id = u.id
            WHERE c.bottle_id = ? 
            ORDER BY c.created_at ASC
        ");
        $commentStmt->bind_param("i", $bottle['id']);
        $commentStmt->execute();
        $commentsResult = $commentStmt->get_result();
        
        $comments = [];
        while ($comment = $commentsResult->fetch_assoc()) {
            $comments[] = $comment;
        }
        $bottle['comments'] = $comments;
        $commentStmt->close();
        
        $bottles[] = $bottle;
    }
    
    $stmt->close();
    $conn->close();
    
    return ['success' => true, 'bottles' => $bottles];
}

// 获取用户的消息列表
function getUserMessages($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("
        SELECT m.*, b.content as bottle_content, 
               u.username as from_username, u.gender as from_gender
        FROM messages m
        JOIN bottles b ON m.bottle_id = b.id
        JOIN users u ON m.from_user_id = u.id
        WHERE m.user_id = ?
        ORDER BY m.created_at DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $messages = [];
    while ($message = $result->fetch_assoc()) {
        $messages[] = $message;
    }
    
    $stmt->close();
    $conn->close();
    
    return ['success' => true, 'messages' => $messages];
}

// 标记消息为已读
function markMessageAsRead($messageId, $userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $messageId, $userId);
    $success = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return ['success' => $success];
}

// 删除消息
function deleteMessage($messageId, $userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("DELETE FROM messages WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $messageId, $userId);
    $success = $stmt->execute();
    
    $stmt->close();
    $conn->close();
    
    return ['success' => $success];
}

// 获取未读消息数量
function getUnreadMessageCount($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM messages WHERE user_id = ? AND is_read = 0");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['count'];
    
    $stmt->close();
    $conn->close();
    
    return ['success' => true, 'count' => $count];
}
?> 