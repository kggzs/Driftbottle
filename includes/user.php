<?php
require_once 'config.php';

// 用户注册
function registerUser($username, $password, $gender) {
    $conn = getDbConnection();
    
    // 检查用户名是否已存在
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => '用户名已存在'];
    }
    
    // 加密密码
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // 插入新用户
    $stmt = $conn->prepare("INSERT INTO users (username, password, gender) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashedPassword, $gender);
    
    if ($stmt->execute()) {
        $userId = $conn->insert_id;
        $stmt->close();
        $conn->close();
        return ['success' => true, 'user_id' => $userId];
    } else {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => '注册失败，请稍后再试'];
    }
}

// 用户登录
function loginUser($username, $password) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT id, username, password, gender, points, signature, is_vip, vip_expire_date, vip_level FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        if (password_verify($password, $user['password'])) {
            // 检查VIP是否过期
            $isVip = $user['is_vip'];
            $vipExpireDate = $user['vip_expire_date'];
            
            if ($isVip && $vipExpireDate) {
                $today = date('Y-m-d');
                if ($vipExpireDate < $today) {
                    // VIP已过期，更新状态
                    $updateStmt = $conn->prepare("UPDATE users SET is_vip = 0, vip_expire_date = NULL WHERE id = ?");
                    $updateStmt->bind_param("i", $user['id']);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    $isVip = 0;
                    $user['is_vip'] = 0;
                    $user['vip_expire_date'] = null;
                }
            }
            
            // 登录成功，设置会话
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['gender'] = $user['gender'];
            $_SESSION['points'] = $user['points'];
            $_SESSION['signature'] = $user['signature'];
            $_SESSION['is_vip'] = $user['is_vip'];
            $_SESSION['vip_level'] = $user['vip_level'];
            
            $stmt->close();
            $conn->close();
            return ['success' => true, 'user' => $user];
        }
    }
    
    $stmt->close();
    $conn->close();
    return ['success' => false, 'message' => '用户名或密码错误'];
}

// 用户签到
function userCheckin($userId) {
    // 记录开始执行
    error_log('开始执行userCheckin函数，用户ID: ' . $userId);
    
    $conn = getDbConnection();
    
    try {
        // 开始事务
        error_log('准备开始事务处理');
        $conn->begin_transaction();
        error_log('事务开始成功');
        
        $today = date('Y-m-d');
        
        // 检查今天是否已经签到
        error_log('检查用户是否今日已签到');
        $checkStmt = $conn->prepare("SELECT id FROM checkins WHERE user_id = ? AND checkin_date = ?");
        $checkStmt->bind_param("is", $userId, $today);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        
        if ($checkResult->num_rows > 0) {
            error_log('用户今日已签到，返回提示信息');
            $checkStmt->close();
            $conn->rollback();
            $conn->close();
            return ['success' => false, 'message' => '今天已经签到过了'];
        }
        $checkStmt->close();
        
        // 获取昨天的签到记录
        error_log('获取昨天的签到记录');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        $consecutiveDays = 1;
        $pointsEarned = POINTS_PER_CHECKIN; // 使用配置中的基础签到积分值
        
        $yesterdayStmt = $conn->prepare("
            SELECT consecutive_days 
            FROM checkins 
            WHERE user_id = ? AND checkin_date = ?
        ");
        $yesterdayStmt->bind_param("is", $userId, $yesterday);
        $yesterdayStmt->execute();
        $yesterdayResult = $yesterdayStmt->get_result();
        
        if ($yesterdayResult->num_rows > 0) {
            // 昨天签到了，连续签到天数+1
            $consecutiveDays = $yesterdayResult->fetch_assoc()['consecutive_days'] + 1;
            error_log('用户连续签到天数: ' . $consecutiveDays);
            
            // 如果连续签到满7天，额外奖励积分
            if ($consecutiveDays % 7 === 0) {
                $pointsEarned += POINTS_PER_WEEKLY_CHECKIN;
                error_log('用户连续签到7天，额外奖励' . POINTS_PER_WEEKLY_CHECKIN . '积分');
            }
        }
        $yesterdayStmt->close();
        
        // 检查用户是否是VIP，VIP每日签到额外加积分
        error_log('检查用户VIP状态');
        $isVipStmt = $conn->prepare("SELECT is_vip FROM users WHERE id = ?");
        $isVipStmt->bind_param("i", $userId);
        $isVipStmt->execute();
        $isVipResult = $isVipStmt->get_result();
        
        $isVip = false;
        $vipBonusPoints = 0;
        
        if ($isVipResult->num_rows > 0) {
            $isVip = $isVipResult->fetch_assoc()['is_vip'] == 1;
            if ($isVip) {
                $vipBonusPoints = POINTS_PER_VIP_CHECKIN;
                $pointsEarned += $vipBonusPoints;
                error_log('用户是VIP，额外奖励' . POINTS_PER_VIP_CHECKIN . '积分');
            }
        }
        $isVipStmt->close();
        
        // 记录今天的签到
        error_log('插入今天的签到记录');
        $checkinStmt = $conn->prepare("
            INSERT INTO checkins (user_id, checkin_date, consecutive_days, points_earned) 
            VALUES (?, ?, ?, ?)
        ");
        $checkinStmt->bind_param("isii", $userId, $today, $consecutiveDays, $pointsEarned);
        $checkinSuccess = $checkinStmt->execute();
        $checkinStmt->close();
        
        if ($checkinSuccess) {
            error_log('签到记录插入成功，开始更新用户积分');
            
            // 更新用户积分 - 直接使用SQL语句更新，避免循环引用
            $updatePointsStmt = $conn->prepare("UPDATE users SET points = points + ? WHERE id = ?");
            $updatePointsStmt->bind_param("ii", $pointsEarned, $userId);
            $updatePointsStmt->execute();
            $updatePointsStmt->close();
            
            // 记录积分历史
            error_log('记录积分历史');
            $action = '每日签到';
            $historyStmt = $conn->prepare("INSERT INTO points_history (user_id, points, action) VALUES (?, ?, ?)");
            $historyStmt->bind_param("iis", $userId, $pointsEarned, $action);
            $historyStmt->execute();
            $historyStmt->close();
            
            // 更新会话中的积分
            if (isset($_SESSION['points'])) {
                $_SESSION['points'] += $pointsEarned;
                error_log('会话中的积分已更新: ' . $_SESSION['points']);
            }
            
            // 提交事务
            error_log('所有操作完成，准备提交事务');
            $conn->commit();
            error_log('事务提交成功');
            $conn->close();
            
            error_log('签到成功，返回结果');
            return [
                'success' => true, 
                'consecutive_days' => $consecutiveDays,
                'points_earned' => $pointsEarned,
                'is_weekly_bonus' => ($consecutiveDays % 7 === 0),
                'is_vip' => $isVip,
                'vip_bonus_points' => $vipBonusPoints
            ];
        } else {
            error_log('签到记录插入失败，回滚事务');
            $conn->rollback();
            $conn->close();
            return ['success' => false, 'message' => '签到失败，请稍后再试'];
        }
    } catch (Exception $e) {
        // 发生异常时回滚事务
        error_log('签到过程发生异常: ' . $e->getMessage());
        if ($conn && $conn->connect_errno === 0) {
            try {
                $conn->rollback();
                error_log('事务已回滚');
            } catch (Exception $rollbackException) {
                error_log('回滚事务时发生异常: ' . $rollbackException->getMessage());
            }
        }
        
        if ($conn) {
            $conn->close();
        }
        
        // 记录详细错误信息
        error_log('签到异常: ' . $e->getMessage() . ' 在文件 ' . $e->getFile() . ' 行 ' . $e->getLine());
        
        return ['success' => false, 'message' => '签到处理异常，请稍后再试', 'error' => DEBUG_MODE ? $e->getMessage() : null];
    }
}

// 获取用户签到状态
function getUserCheckinStatus($userId) {
    $conn = getDbConnection();
    $today = date('Y-m-d');
    
    // 检查今天是否已签到
    $checkStmt = $conn->prepare("SELECT id FROM checkins WHERE user_id = ? AND checkin_date = ?");
    $checkStmt->bind_param("is", $userId, $today);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    $hasCheckedToday = ($checkResult->num_rows > 0);
    $checkStmt->close();
    
    // 获取最近的签到记录
    $recentStmt = $conn->prepare("
        SELECT consecutive_days, checkin_date 
        FROM checkins 
        WHERE user_id = ? 
        ORDER BY checkin_date DESC 
        LIMIT 1
    ");
    $recentStmt->bind_param("i", $userId);
    $recentStmt->execute();
    $recentResult = $recentStmt->get_result();
    
    $consecutiveDays = 0;
    $lastCheckinDate = null;
    
    if ($recentResult->num_rows > 0) {
        $recentCheckin = $recentResult->fetch_assoc();
        $consecutiveDays = $recentCheckin['consecutive_days'];
        $lastCheckinDate = $recentCheckin['checkin_date'];
        
        // 如果最后一次签到不是今天，且不是昨天，则连续签到天数重置为0
        if (!$hasCheckedToday) {
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            if ($lastCheckinDate != $yesterday) {
                $consecutiveDays = 0;
            }
        }
    }
    $recentStmt->close();
    
    // 获取本月累计签到天数
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
    
    $monthStmt = $conn->prepare("
        SELECT COUNT(*) as monthly_checkins
        FROM checkins 
        WHERE user_id = ? AND checkin_date BETWEEN ? AND ?
    ");
    $monthStmt->bind_param("iss", $userId, $monthStart, $monthEnd);
    $monthStmt->execute();
    $monthResult = $monthStmt->get_result();
    $monthlyCheckins = $monthResult->fetch_assoc()['monthly_checkins'];
    $monthStmt->close();
    
    $conn->close();
    
    return [
        'success' => true,
        'has_checked_today' => $hasCheckedToday,
        'consecutive_days' => $consecutiveDays,
        'last_checkin_date' => $lastCheckinDate,
        'monthly_checkins' => $monthlyCheckins
    ];
}

// 更新用户个性签名
function updateUserSignature($userId, $signature) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("UPDATE users SET signature = ? WHERE id = ?");
    $stmt->bind_param("si", $signature, $userId);
    $success = $stmt->execute();
    
    if ($success) {
        // 更新会话中的签名
        $_SESSION['signature'] = $signature;
    }
    
    $stmt->close();
    $conn->close();
    
    return ['success' => $success];
}

// 检查用户是否为VIP
function isUserVip($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT is_vip, vip_expire_date FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $isVip = false;
    $vipExpireDate = null;
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $isVip = $user['is_vip'] == 1;
        $vipExpireDate = $user['vip_expire_date'];
        
        // 检查VIP是否过期
        if ($isVip && $vipExpireDate) {
            $today = date('Y-m-d');
            if ($vipExpireDate < $today) {
                // VIP已过期，更新状态
                $updateStmt = $conn->prepare("UPDATE users SET is_vip = 0, vip_expire_date = NULL WHERE id = ?");
                $updateStmt->bind_param("i", $userId);
                $updateStmt->execute();
                $updateStmt->close();
                
                $isVip = false;
                $vipExpireDate = null;
            }
        }
    }
    
    $stmt->close();
    $conn->close();
    
    return [
        'is_vip' => $isVip,
        'vip_expire_date' => $vipExpireDate
    ];
}

// 设置用户为VIP
function setUserVip($userId, $months = 1, $level = 1) {
    $conn = getDbConnection();
    
    // 检查用户当前VIP状态
    $vipStatus = isUserVip($userId);
    $isVip = $vipStatus['is_vip'];
    $currentExpireDate = $vipStatus['vip_expire_date'];
    
    // 计算新的过期日期
    if ($isVip && $currentExpireDate) {
        // 如果已经是VIP，则在当前过期日期上增加时间
        $newExpireDate = date('Y-m-d', strtotime($currentExpireDate . " + $months months"));
    } else {
        // 如果不是VIP，则从今天开始计算
        $newExpireDate = date('Y-m-d', strtotime("+ $months months"));
    }
    
    // 更新用户VIP状态
    $stmt = $conn->prepare("UPDATE users SET is_vip = 1, vip_level = ?, vip_expire_date = ? WHERE id = ?");
    $stmt->bind_param("isi", $level, $newExpireDate, $userId);
    $success = $stmt->execute();
    
    // 如果成功更新，同时更新会话中的VIP状态
    if ($success && isset($_SESSION['user_id']) && $_SESSION['user_id'] == $userId) {
        $_SESSION['is_vip'] = 1;
        $_SESSION['vip_level'] = $level;
    }
    
    $stmt->close();
    $conn->close();
    
    return [
        'success' => $success,
        'is_vip' => true,
        'vip_level' => $level,
        'vip_expire_date' => $newExpireDate
    ];
}

// 获取用户信息
function getUserInfo($userId) {
    $conn = getDbConnection();
    
    $stmt = $conn->prepare("SELECT id, username, gender, signature, points, is_vip, vip_level, vip_expire_date, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $stmt->close();
        
        // 检查VIP是否过期
        if ($user['is_vip'] && $user['vip_expire_date']) {
            $today = date('Y-m-d');
            if ($user['vip_expire_date'] < $today) {
                // VIP已过期，更新状态
                $updateStmt = $conn->prepare("UPDATE users SET is_vip = 0, vip_expire_date = NULL WHERE id = ?");
                $updateStmt->bind_param("i", $userId);
                $updateStmt->execute();
                $updateStmt->close();
                
                $user['is_vip'] = 0;
                $user['vip_expire_date'] = null;
            }
        }
        
        // 获取用户扔出的漂流瓶数量
        $stmt = $conn->prepare("SELECT COUNT(*) as bottle_count FROM bottles WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $bottleResult = $stmt->get_result();
        $bottleCount = $bottleResult->fetch_assoc()['bottle_count'];
        $user['bottle_count'] = $bottleCount;
        
        // 获取用户收到的点赞数
        $stmt = $conn->prepare("SELECT COUNT(*) as like_count FROM likes WHERE bottle_id IN (SELECT id FROM bottles WHERE user_id = ?)");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $likeResult = $stmt->get_result();
        $likeCount = $likeResult->fetch_assoc()['like_count'];
        $user['like_count'] = $likeCount;
        
        // 获取用户签到信息
        $user['checkin_status'] = getUserCheckinStatus($userId);
        
        $stmt->close();
        $conn->close();
        return ['success' => true, 'user' => $user];
    }
    
    $stmt->close();
    $conn->close();
    return ['success' => false, 'message' => '用户不存在'];
}

// 用户登出
function logoutUser() {
    // 清除所有会话变量
    $_SESSION = [];
    
    // 销毁会话
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    session_destroy();
    return true;
}
?> 