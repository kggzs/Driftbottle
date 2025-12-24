<?php
/**
 * 举报系统相关函数
 */

require_once 'config.php';
require_once 'user.php';
require_once __DIR__ . '/bottle.php';

/**
 * 提交举报
 * @param int $reporterId 举报人ID
 * @param string $targetType 举报类型：bottle或comment
 * @param int $targetId 被举报内容ID
 * @param string $reason 举报理由
 * @return array
 */
function submitReport($reporterId, $targetType, $targetId, $reason) {
    $conn = getDbConnection();
    
    // 验证举报类型
    if (!in_array($targetType, ['bottle', 'comment'])) {
        $conn->close();
        return ['success' => false, 'message' => '无效的举报类型'];
    }
    
    // 验证目标是否存在，并检查是否是自己的内容
    if ($targetType === 'bottle') {
        $stmt = $conn->prepare("SELECT id, user_id FROM bottles WHERE id = ?");
    } else {
        $stmt = $conn->prepare("SELECT id, user_id FROM comments WHERE id = ?");
    }
    $stmt->bind_param("i", $targetId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => '被举报的内容不存在'];
    }
    
    $target = $result->fetch_assoc();
    $stmt->close();
    
    // 检查是否是自己的内容
    if ($target['user_id'] == $reporterId) {
        $conn->close();
        return ['success' => false, 'message' => '不能举报自己的内容'];
    }
    
    // 检查是否已被举报成功（已通过审核的举报）
    $approvedStmt = $conn->prepare("
        SELECT id FROM reports 
        WHERE target_type = ? AND target_id = ? AND status = 'approved'
    ");
    $approvedStmt->bind_param("si", $targetType, $targetId);
    $approvedStmt->execute();
    $approvedResult = $approvedStmt->get_result();
    if ($approvedResult->num_rows > 0) {
        $approvedStmt->close();
        $conn->close();
        return ['success' => false, 'message' => '该内容已被处理，无需重复举报'];
    }
    $approvedStmt->close();
    
    // 检查是否已经举报过（待审核的举报）
    $checkStmt = $conn->prepare("
        SELECT id FROM reports 
        WHERE reporter_id = ? AND target_type = ? AND target_id = ? AND status = 'pending'
    ");
    $checkStmt->bind_param("isi", $reporterId, $targetType, $targetId);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    if ($checkResult->num_rows > 0) {
        $checkStmt->close();
        $conn->close();
        return ['success' => false, 'message' => '您已经举报过此内容，请等待审核'];
    }
    $checkStmt->close();
    
    // 插入举报记录
    $insertStmt = $conn->prepare("
        INSERT INTO reports (reporter_id, target_type, target_id, reason, status) 
        VALUES (?, ?, ?, ?, 'pending')
    ");
    $insertStmt->bind_param("isis", $reporterId, $targetType, $targetId, $reason);
    
    if ($insertStmt->execute()) {
        $reportId = $conn->insert_id;
        $insertStmt->close();
        $conn->close();
        return ['success' => true, 'message' => '举报已提交，我们会尽快审核', 'report_id' => $reportId];
    } else {
        $error = $insertStmt->error;
        $insertStmt->close();
        $conn->close();
        return ['success' => false, 'message' => '提交举报失败：' . $error];
    }
}

/**
 * 审核举报（批量处理同一内容的多个举报）
 * @param int $reportId 举报ID（可以是任意一个同一内容的举报ID）
 * @param int $adminId 管理员ID
 * @param string $action 操作类型：delete删除，hide_bottle屏蔽漂流瓶，hide_comment屏蔽评论，no_action无操作
 * @param string $note 管理员备注
 * @param string $status 审核状态：approved通过，rejected拒绝
 * @return array
 */
function reviewReport($reportId, $adminId, $action, $note = '', $status = 'approved') {
    $conn = getDbConnection();
    $conn->begin_transaction();
    
    try {
        // 获取举报信息（可以审核已审核的记录，用于撤销）
        $stmt = $conn->prepare("SELECT target_type, target_id, status, admin_action FROM reports WHERE id = ?");
        $stmt->bind_param("i", $reportId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            $conn->rollback();
            $conn->close();
            return ['success' => false, 'message' => '举报不存在'];
        }
        
        $reportInfo = $result->fetch_assoc();
        $targetType = $reportInfo['target_type'];
        $targetId = $reportInfo['target_id'];
        $currentStatus = $reportInfo['status'];
        $adminAction = $reportInfo['admin_action'];
        $stmt->close();
        
        // 检查同一内容的所有举报状态分布
        $checkAllStmt = $conn->prepare("SELECT status, COUNT(*) as count FROM reports WHERE target_type = ? AND target_id = ? GROUP BY status");
        $checkAllStmt->bind_param("si", $targetType, $targetId);
        $checkAllStmt->execute();
        $allStatusResult = $checkAllStmt->get_result();
        $statusDistribution = [];
        while ($row = $allStatusResult->fetch_assoc()) {
            $statusDistribution[$row['status']] = $row['count'];
        }
        $checkAllStmt->close();
        
        $pendingCount = isset($statusDistribution['pending']) ? $statusDistribution['pending'] : 0;
        
        // 判断是撤销操作还是正常审核
        $isRevoke = false;
        
        // 如果是撤销操作（从已审核状态变为pending）
        if ($status === 'pending' && $currentStatus !== 'pending') {
            $isRevoke = true;
            // 撤销时，需要恢复被屏蔽或删除的内容
            if ($adminAction === 'hide_bottle' && $targetType === 'bottle') {
                // 恢复屏蔽的漂流瓶
                $unhideStmt = $conn->prepare("UPDATE bottles SET is_hidden = 0 WHERE id = ?");
                $unhideStmt->bind_param("i", $targetId);
                $unhideStmt->execute();
                $unhideStmt->close();
            } elseif ($adminAction === 'hide_comment' && $targetType === 'comment') {
                // 恢复屏蔽的评论
                $unhideStmt = $conn->prepare("UPDATE comments SET is_hidden = 0 WHERE id = ?");
                $unhideStmt->bind_param("i", $targetId);
                $unhideStmt->execute();
                $unhideStmt->close();
            }
            // 如果是软删除操作，需要恢复（设置is_deleted=0）
            if ($adminAction === 'delete') {
                if ($targetType === 'bottle') {
                    // 恢复被软删除的漂流瓶
                    $restoreStmt = $conn->prepare("UPDATE bottles SET is_deleted = 0 WHERE id = ?");
                    $restoreStmt->bind_param("i", $targetId);
                    $restoreStmt->execute();
                    $restoreStmt->close();
                } elseif ($targetType === 'comment') {
                    // 恢复被软删除的评论
                    $restoreStmt = $conn->prepare("UPDATE comments SET is_deleted = 0 WHERE id = ?");
                    $restoreStmt->bind_param("i", $targetId);
                    $restoreStmt->execute();
                    $restoreStmt->close();
                }
            }
            
            // 批量扣除之前奖励的积分（同一内容的所有举报人）
            $points = POINTS_PER_REPORT_APPROVED;
            $reportersStmt = $conn->prepare("
                SELECT DISTINCT reporter_id 
                FROM reports 
                WHERE target_type = ? AND target_id = ? AND status = ?
            ");
            $reportersStmt->bind_param("sis", $targetType, $targetId, $currentStatus);
            $reportersStmt->execute();
            $reportersResult = $reportersStmt->get_result();
            
            while ($reporter = $reportersResult->fetch_assoc()) {
                updateUserPoints($reporter['reporter_id'], -$points, "撤销举报奖励");
            }
            $reportersStmt->close();
        }
        
        // 如果是正常审核（非撤销），检查是否有待处理的举报
        if (!$isRevoke && $pendingCount === 0) {
            $conn->rollback();
            $conn->close();
            return ['success' => false, 'message' => '该举报已被处理，无法重复审核。如需撤销处理，请使用撤销功能。'];
        }
        
        // 根据操作类型执行相应操作（仅在非撤销且通过时）
        if ($status === 'approved' && !$isRevoke) {
            if ($action === 'delete') {
                if ($targetType === 'bottle') {
                    // 软删除漂流瓶（设置is_deleted=1）
                    $deleteStmt = $conn->prepare("UPDATE bottles SET is_deleted = 1 WHERE id = ?");
                } else {
                    // 软删除评论（设置is_deleted=1）
                    $deleteStmt = $conn->prepare("UPDATE comments SET is_deleted = 1 WHERE id = ?");
                }
                $deleteStmt->bind_param("i", $targetId);
                $deleteStmt->execute();
                $deleteStmt->close();
            } elseif ($action === 'hide_bottle' && $targetType === 'bottle') {
                // 屏蔽漂流瓶内容
                $hideStmt = $conn->prepare("UPDATE bottles SET is_hidden = 1 WHERE id = ?");
                $hideStmt->bind_param("i", $targetId);
                $hideStmt->execute();
                $hideStmt->close();
            } elseif ($action === 'hide_comment' && $targetType === 'comment') {
                // 屏蔽评论内容
                $hideStmt = $conn->prepare("UPDATE comments SET is_hidden = 1 WHERE id = ?");
                $hideStmt->bind_param("i", $targetId);
                $hideStmt->execute();
                $hideStmt->close();
            }
            
            // 批量奖励所有举报人积分（同一内容的多个举报）
            $points = POINTS_PER_REPORT_APPROVED;
            $reportersStmt = $conn->prepare("
                SELECT DISTINCT reporter_id 
                FROM reports 
                WHERE target_type = ? AND target_id = ? AND status = 'pending'
            ");
            $reportersStmt->bind_param("si", $targetType, $targetId);
            $reportersStmt->execute();
            $reportersResult = $reportersStmt->get_result();
            
            // 获取目标内容的ID（用于消息通知）
            $targetContentId = null;
            if ($targetType === 'bottle') {
                $targetContentId = $targetId;
            } else {
                // 如果是评论，需要获取评论所属的漂流瓶ID
                $commentStmt = $conn->prepare("SELECT bottle_id FROM comments WHERE id = ?");
                $commentStmt->bind_param("i", $targetId);
                $commentStmt->execute();
                $commentResult = $commentStmt->get_result();
                if ($commentResult->num_rows > 0) {
                    $targetContentId = $commentResult->fetch_assoc()['bottle_id'];
                }
                $commentStmt->close();
            }
            
            while ($reporter = $reportersResult->fetch_assoc()) {
                updateUserPoints($reporter['reporter_id'], $points, "举报成功奖励");
                
                // 向举报人发送审核通过的消息通知
                if ($targetContentId) {
                    $actionText = '';
                    switch ($action) {
                        case 'delete':
                            $actionText = $targetType === 'bottle' ? '已删除该漂流瓶' : '已删除该评论';
                            break;
                        case 'hide_bottle':
                            $actionText = '已屏蔽该漂流瓶内容';
                            break;
                        case 'hide_comment':
                            $actionText = '已屏蔽该评论内容';
                            break;
                        default:
                            $actionText = '已处理该举报';
                    }
                    
                    $messageContent = "您的举报已审核通过，管理员已" . $actionText . "。感谢您为维护社区环境做出的贡献！";
                    error_log("准备发送举报反馈消息: reporter_id={$reporter['reporter_id']}, targetContentId={$targetContentId}, action={$action}, messageContent={$messageContent}");
                    $msgStmt = $conn->prepare("
                        INSERT INTO messages (user_id, bottle_id, from_user_id, type, content) 
                        VALUES (?, ?, NULL, '举报反馈', ?)
                    ");
                    // from_user_id设为NULL表示系统消息
                    if ($msgStmt) {
                        $msgStmt->bind_param("iis", $reporter['reporter_id'], $targetContentId, $messageContent); // from_user_id为NULL，不需要绑定
                        if ($msgStmt->execute()) {
                            $messageId = $conn->insert_id;
                            error_log("成功发送举报反馈消息: message_id={$messageId}, reporter_id={$reporter['reporter_id']}, targetContentId={$targetContentId}");
                        } else {
                            error_log("发送举报反馈消息失败: reporter_id={$reporter['reporter_id']}, targetContentId={$targetContentId}, error=" . $msgStmt->error);
                        }
                        $msgStmt->close();
                    } else {
                        error_log("准备举报反馈消息SQL失败: reporter_id={$reporter['reporter_id']}, error=" . $conn->error);
                    }
                } else {
                    error_log("无法发送举报反馈消息: targetContentId为空, reporter_id={$reporter['reporter_id']}, targetType={$targetType}, targetId={$targetId}");
                }
            }
            $reportersStmt->close();
            
            // 向被举报人（内容发布者）发送处理通知
            // 获取被举报内容的所有者ID
            $ownerId = null;
            if ($targetType === 'bottle') {
                $ownerStmt = $conn->prepare("SELECT user_id FROM bottles WHERE id = ?");
                $ownerStmt->bind_param("i", $targetId);
                $ownerStmt->execute();
                $ownerResult = $ownerStmt->get_result();
                if ($ownerResult->num_rows > 0) {
                    $ownerId = $ownerResult->fetch_assoc()['user_id'];
                }
                $ownerStmt->close();
            } else {
                // 评论
                $ownerStmt = $conn->prepare("SELECT user_id, bottle_id FROM comments WHERE id = ?");
                $ownerStmt->bind_param("i", $targetId);
                $ownerStmt->execute();
                $ownerResult = $ownerStmt->get_result();
                if ($ownerResult->num_rows > 0) {
                    $ownerData = $ownerResult->fetch_assoc();
                    $ownerId = $ownerData['user_id'];
                    // 如果是评论，需要获取评论所属的漂流瓶ID用于消息
                    if (empty($targetContentId)) {
                        $targetContentId = $ownerData['bottle_id'];
                    }
                }
                $ownerStmt->close();
            }
            
            // 如果找到内容所有者，发送通知
            if ($ownerId && $targetContentId) {
                // 获取所有举报理由和管理员备注
                $reasonsStmt = $conn->prepare("
                    SELECT GROUP_CONCAT(DISTINCT reason SEPARATOR '；') as all_reasons
                    FROM reports 
                    WHERE target_type = ? AND target_id = ? AND status = 'pending'
                ");
                $reasonsStmt->bind_param("si", $targetType, $targetId);
                $reasonsStmt->execute();
                $reasonsResult = $reasonsStmt->get_result();
                $allReasons = '';
                if ($reasonsResult->num_rows > 0) {
                    $reasonsRow = $reasonsResult->fetch_assoc();
                    $allReasons = $reasonsRow['all_reasons'] ?? '';
                }
                $reasonsStmt->close();
                
                // 使用管理员备注（如果有），否则使用举报理由
                $processReason = !empty($note) ? $note : $allReasons;
                if (empty($processReason)) {
                    $processReason = '违反社区规范';
                }
                
                // 构造处理操作描述
                $actionDesc = '';
                switch ($action) {
                    case 'delete':
                        $actionDesc = $targetType === 'bottle' ? '删除' : '删除评论';
                        break;
                    case 'hide_bottle':
                        $actionDesc = '屏蔽漂流瓶内容';
                        break;
                    case 'hide_comment':
                        $actionDesc = '屏蔽评论内容';
                        break;
                    default:
                        $actionDesc = '处理';
                }
                
                $contentType = $targetType === 'bottle' ? '漂流瓶' : '评论';
                $messageContent = "您的{$contentType}因以下原因被{$actionDesc}：{$processReason}。请遵守社区规范，发布健康、积极的内容。";
                
                error_log("准备发送被举报人处理通知: owner_id={$ownerId}, targetContentId={$targetContentId}, action={$action}, reason={$processReason}");
                
                $ownerMsgStmt = $conn->prepare("
                    INSERT INTO messages (user_id, bottle_id, from_user_id, type, content) 
                    VALUES (?, ?, NULL, '举报反馈', ?)
                ");
                // from_user_id设为NULL表示系统消息
                if ($ownerMsgStmt) {
                    $ownerMsgStmt->bind_param("iis", $ownerId, $targetContentId, $messageContent);
                    if ($ownerMsgStmt->execute()) {
                        $ownerMessageId = $conn->insert_id;
                        error_log("成功发送被举报人处理通知: message_id={$ownerMessageId}, owner_id={$ownerId}, targetContentId={$targetContentId}");
                    } else {
                        error_log("发送被举报人处理通知失败: owner_id={$ownerId}, targetContentId={$targetContentId}, error=" . $ownerMsgStmt->error);
                    }
                    $ownerMsgStmt->close();
                } else {
                    error_log("准备被举报人处理通知SQL失败: owner_id={$ownerId}, error=" . $conn->error);
                }
            }
        }
        
        // 批量更新同一内容的所有举报状态
        // 正常审核时，只处理状态为 'pending' 的记录
        // 撤销操作时，处理状态为当前状态（approved 或 rejected）的记录
        $oldStatus = $isRevoke ? $currentStatus : 'pending';
        
        // 在执行更新前，先检查是否有匹配的记录，并获取实际的状态值（用于调试）
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count, GROUP_CONCAT(DISTINCT status) as actual_statuses FROM reports WHERE target_type = ? AND target_id = ? AND status = ?");
        $checkStmt->bind_param("sis", $targetType, $targetId, $oldStatus);
        $checkStmt->execute();
        $checkResult = $checkStmt->get_result();
        $checkRow = $checkResult->fetch_assoc();
        $matchCount = $checkRow['count'];
        $actualStatuses = $checkRow['actual_statuses'] ?? '';
        $checkStmt->close();
        
        // 如果匹配不到记录，检查一下数据库中该内容的所有记录状态，以便调试
        if ($matchCount === 0) {
            $debugStmt = $conn->prepare("SELECT status, COUNT(*) as cnt FROM reports WHERE target_type = ? AND target_id = ? GROUP BY status");
            $debugStmt->bind_param("si", $targetType, $targetId);
            $debugStmt->execute();
            $debugResult = $debugStmt->get_result();
            $statusList = [];
            while ($row = $debugResult->fetch_assoc()) {
                $statusList[] = $row['status'] . '(' . $row['cnt'] . '条)';
            }
            $debugStmt->close();
            
            $conn->rollback();
            $conn->close();
            $statusInfo = implode(', ', $statusList);
            $errorMsg = $isRevoke ? 
                "没有找到状态为 '{$currentStatus}' 的举报记录。当前该内容的举报状态分布：{$statusInfo}" : 
                "没有找到待处理的举报记录（status='pending'）。当前该内容的举报状态分布：{$statusInfo}";
            error_log("审核举报失败: target_type={$targetType}, target_id={$targetId}, 期望状态={$oldStatus}, 实际状态分布={$statusInfo}");
            return ['success' => false, 'message' => $errorMsg];
        }
        
        // 在执行UPDATE之前，再次验证记录状态（因为可能在其他操作中状态已改变）
        $recheckStmt = $conn->prepare("SELECT COUNT(*) as count FROM reports WHERE target_type = ? AND target_id = ? AND status = ?");
        $recheckStmt->bind_param("sis", $targetType, $targetId, $oldStatus);
        $recheckStmt->execute();
        $recheckResult = $recheckStmt->get_result();
        $recheckCount = $recheckResult->fetch_assoc()['count'];
        $recheckStmt->close();
        
        if ($recheckCount === 0) {
            $conn->rollback();
            $conn->close();
            error_log("审核举报失败: UPDATE前再次检查，发现没有匹配的记录。target_type={$targetType}, target_id={$targetId}, oldStatus={$oldStatus}, newStatus={$status}, 初始matchCount={$matchCount}");
            return ['success' => false, 'message' => '记录状态已发生变化，无法更新'];
        }
        
        // 如果撤销操作，reviewed_at 应该设置为 NULL
        if ($isRevoke) {
            $updateStmt = $conn->prepare("
                UPDATE reports 
                SET status = ?, admin_id = ?, admin_action = ?, admin_note = ?, reviewed_at = NULL 
                WHERE target_type = ? AND target_id = ? AND status = ?
            ");
        } else {
            $updateStmt = $conn->prepare("
                UPDATE reports 
                SET status = ?, admin_id = ?, admin_action = ?, admin_note = ?, reviewed_at = NOW() 
                WHERE target_type = ? AND target_id = ? AND status = ?
            ");
        }
        
        if (!$updateStmt) {
            $conn->rollback();
            $conn->close();
            error_log("审核举报失败: prepare UPDATE失败。错误: " . $conn->error);
            return ['success' => false, 'message' => '准备更新语句失败：' . $conn->error];
        }
        
        // 绑定参数：status (新), admin_id, admin_action, admin_note, target_type, target_id, status (旧)
        // SQL: UPDATE reports SET status=?, admin_id=?, admin_action=?, admin_note=?, reviewed_at=NOW() WHERE target_type=? AND target_id=? AND status=?
        // 参数顺序: status(新,s), admin_id(i), admin_action(s), admin_note(s), target_type(s), target_id(i), status(旧,s)
        // 参数类型字符串: "sisssis" (7个参数：s-i-s-s-s-i-s)
        error_log("准备执行UPDATE: target_type='{$targetType}', target_id={$targetId}, oldStatus='{$oldStatus}', newStatus='{$status}', adminId={$adminId}, action='{$action}', note='{$note}', recheckCount={$recheckCount}");
        
        if (!$updateStmt->bind_param("sisssis", $status, $adminId, $action, $note, $targetType, $targetId, $oldStatus)) {
            $error = $updateStmt->error;
            $updateStmt->close();
            $conn->rollback();
            $conn->close();
            error_log("审核举报失败: bind_param失败。错误: {$error}");
            return ['success' => false, 'message' => '参数绑定失败：' . $error];
        }
        
        // 执行UPDATE
        if (!$updateStmt->execute()) {
            $error = $updateStmt->error;
            $updateStmt->close();
            $conn->rollback();
            $conn->close();
            error_log("审核举报失败: UPDATE执行失败。错误: {$error}, target_type={$targetType}, target_id={$targetId}, oldStatus={$oldStatus}, newStatus={$status}, recheckCount={$recheckCount}");
            return ['success' => false, 'message' => '更新失败：' . $error];
        }
        
        $affectedRows = $updateStmt->affected_rows;
        $updateStmt->close();
        
        error_log("审核举报UPDATE执行结果: affected_rows={$affectedRows}, recheckCount={$recheckCount}, target_type={$targetType}, target_id={$targetId}, oldStatus={$oldStatus}, newStatus={$status}");
        
        // 如果affected_rows为0，但recheckCount>0，尝试直接查询看看是否能找到匹配的记录
        if ($affectedRows === 0 && $recheckCount > 0) {
            $debugStmt = $conn->prepare("SELECT id, status, target_type, target_id FROM reports WHERE target_type = ? AND target_id = ? AND status = ? LIMIT 5");
            $debugStmt->bind_param("sis", $targetType, $targetId, $oldStatus);
            $debugStmt->execute();
            $debugResult = $debugStmt->get_result();
            $debugRows = [];
            while ($row = $debugResult->fetch_assoc()) {
                $debugRows[] = "id={$row['id']}, status='{$row['status']}'";
            }
            $debugStmt->close();
            error_log("UPDATE后调试查询: 找到 " . count($debugRows) . " 条记录，详情: " . implode('; ', $debugRows));
        }
        
        // 检查更新是否成功
        // 注意：在某些MySQL配置下，affected_rows可能不准确，特别是在事务中
        if ($affectedRows === 0 && $matchCount > 0) {
            // 验证更新是否真的成功（检查是否有记录变成了新状态）
            $verifyStmt = $conn->prepare("SELECT COUNT(*) as count FROM reports WHERE target_type = ? AND target_id = ? AND status = ?");
            $verifyStmt->bind_param("sis", $targetType, $targetId, $status);
            $verifyStmt->execute();
            $verifyResult = $verifyStmt->get_result();
            $verifyCount = $verifyResult->fetch_assoc()['count'];
            $verifyStmt->close();
            
            // 同时检查旧状态的记录数是否减少
            $checkOldStmt = $conn->prepare("SELECT COUNT(*) as count FROM reports WHERE target_type = ? AND target_id = ? AND status = ?");
            $checkOldStmt->bind_param("sis", $targetType, $targetId, $oldStatus);
            $checkOldStmt->execute();
            $checkOldResult = $checkOldStmt->get_result();
            $oldCount = $checkOldResult->fetch_assoc()['count'];
            $checkOldStmt->close();
            
            // 如果新状态的记录数增加了，说明更新成功
            if ($verifyCount >= $matchCount) {
                // 验证成功：更新确实成功了，只是affected_rows不准确
                $affectedRows = $matchCount;
                error_log("审核举报: affected_rows为0但验证成功。target_type={$targetType}, target_id={$targetId}, oldStatus={$oldStatus}, newStatus={$status}, verifyCount={$verifyCount}, matchCount={$matchCount}");
            } else {
                // 验证失败：更新确实没有成功
                $conn->rollback();
                $conn->close();
                error_log("审核举报失败: 更新了0条记录，匹配到{$matchCount}条记录，验证后确认更新失败（新状态记录数={$verifyCount}，旧状态记录数={$oldCount}）。target_type={$targetType}, target_id={$targetId}, oldStatus={$oldStatus}, newStatus={$status}");
                return ['success' => false, 'message' => '更新失败，请联系技术支持'];
            }
        } elseif ($affectedRows === 0) {
            $conn->rollback();
            $conn->close();
            error_log("审核举报失败: 更新了0条记录，匹配到{$matchCount}条记录。target_type={$targetType}, target_id={$targetId}, oldStatus={$oldStatus}, newStatus={$status}");
            return ['success' => false, 'message' => '更新失败，请联系技术支持'];
        }
        
        // 如果审核被拒绝，也要通知举报人
        if ($status === 'rejected' && !$isRevoke) {
            // 获取目标内容的ID（用于消息通知）
            $targetContentId = null;
            if ($targetType === 'bottle') {
                $targetContentId = $targetId;
            } else {
                // 如果是评论，需要获取评论所属的漂流瓶ID
                $commentStmt = $conn->prepare("SELECT bottle_id FROM comments WHERE id = ?");
                $commentStmt->bind_param("i", $targetId);
                $commentStmt->execute();
                $commentResult = $commentStmt->get_result();
                if ($commentResult->num_rows > 0) {
                    $targetContentId = $commentResult->fetch_assoc()['bottle_id'];
                }
                $commentStmt->close();
            }
            
            // 获取所有举报人（状态已经是rejected的）
            $reportersStmt = $conn->prepare("
                SELECT DISTINCT reporter_id 
                FROM reports 
                WHERE target_type = ? AND target_id = ? AND status = 'rejected'
            ");
            $reportersStmt->bind_param("si", $targetType, $targetId);
            $reportersStmt->execute();
            $reportersResult = $reportersStmt->get_result();
            
            while ($reporter = $reportersResult->fetch_assoc()) {
                // 向举报人发送审核拒绝的消息通知
                if ($targetContentId) {
                    $messageContent = "您的举报已审核，经核实后未发现违规内容。感谢您的监督！";
                    error_log("准备发送举报反馈消息(拒绝): reporter_id={$reporter['reporter_id']}, targetContentId={$targetContentId}, messageContent={$messageContent}");
                    $msgStmt = $conn->prepare("
                        INSERT INTO messages (user_id, bottle_id, from_user_id, type, content) 
                        VALUES (?, ?, NULL, '举报反馈', ?)
                    ");
                    // from_user_id设为NULL表示系统消息
                    if ($msgStmt) {
                        $msgStmt->bind_param("iis", $reporter['reporter_id'], $targetContentId, $messageContent); // from_user_id为NULL，不需要绑定
                        if ($msgStmt->execute()) {
                            $messageId = $conn->insert_id;
                            error_log("成功发送举报反馈消息(拒绝): message_id={$messageId}, reporter_id={$reporter['reporter_id']}, targetContentId={$targetContentId}");
                        } else {
                            error_log("发送举报反馈消息失败(拒绝): reporter_id={$reporter['reporter_id']}, targetContentId={$targetContentId}, error=" . $msgStmt->error);
                        }
                        $msgStmt->close();
                    } else {
                        error_log("准备举报反馈消息SQL失败(拒绝): reporter_id={$reporter['reporter_id']}, error=" . $conn->error);
                    }
                } else {
                    error_log("无法发送举报反馈消息(拒绝): targetContentId为空, reporter_id={$reporter['reporter_id']}, targetType={$targetType}, targetId={$targetId}");
                }
            }
            $reportersStmt->close();
        }
        
        $conn->commit();
        $conn->close();
        
        $message = $isRevoke ? '撤销处理成功（已处理' . $affectedRows . '条举报）' : ($status === 'approved' ? '举报审核通过（已处理' . $affectedRows . '条举报）' : '举报已拒绝（已处理' . $affectedRows . '条举报）');
        
        return [
            'success' => true, 
            'message' => $message,
            'affected_rows' => $affectedRows
        ];
    } catch (Exception $e) {
        $conn->rollback();
        $conn->close();
        error_log("审核举报失败: " . $e->getMessage());
        return ['success' => false, 'message' => '审核失败：' . $e->getMessage()];
    }
}

/**
 * 获取举报列表（后台使用，合并显示同一内容的多次举报）
 * @param string $status 审核状态：pending待审核，approved已通过，rejected已拒绝，空为全部
 * @param int $page 页码
 * @param int $limit 每页数量
 * @return array
 */
function getReports($status = '', $page = 1, $limit = 20) {
    $conn = getDbConnection();
    
    $offset = ($page - 1) * $limit;
    $where = '';
    $params = [];
    $types = '';
    
    if (!empty($status)) {
        $where = "WHERE r.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    // 获取总数（按 target_type 和 target_id 分组统计）
    $countSql = "
        SELECT COUNT(DISTINCT CONCAT(target_type, '_', target_id, '_', IFNULL(status, ''))) as total
        FROM reports r
        $where
    ";
    
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $countResult = $countStmt->get_result();
    $total = $countResult->fetch_assoc()['total'];
    $countStmt->close();
    
    // 获取列表（按 target_type 和 target_id 分组，获取最新的举报记录和举报数量）
    $sql = "
        SELECT 
               MAX(r.id) as id,
               r.target_type,
               r.target_id,
               r.status,
               MAX(r.created_at) as created_at,
               MAX(r.reviewed_at) as reviewed_at,
               MAX(r.admin_id) as admin_id,
               MAX(r.admin_action) as admin_action,
               MAX(r.admin_note) as admin_note,
               MAX(u.username) as reporter_name,
               MAX(a.username) as admin_name,
               MAX(b.content) as bottle_content,
               MAX(c.content) as comment_content,
               MAX(bu.username) as bottle_owner_name,
               MAX(bu.id) as bottle_owner_id,
               MAX(cu.username) as comment_owner_name,
               MAX(cu.id) as comment_owner_id,
               COUNT(DISTINCT r.id) as report_count,
               GROUP_CONCAT(DISTINCT r.reason ORDER BY r.created_at DESC SEPARATOR '|') as all_reasons,
               GROUP_CONCAT(DISTINCT u.username ORDER BY r.created_at DESC SEPARATOR ', ') as all_reporters,
               MIN(r.created_at) as first_report_time
        FROM reports r
        LEFT JOIN users u ON r.reporter_id = u.id
        LEFT JOIN admins a ON r.admin_id = a.id
        LEFT JOIN bottles b ON r.target_type = 'bottle' AND r.target_id = b.id
        LEFT JOIN users bu ON b.user_id = bu.id
        LEFT JOIN comments c ON r.target_type = 'comment' AND r.target_id = c.id
        LEFT JOIN users cu ON c.user_id = cu.id
        $where
        GROUP BY r.target_type, r.target_id, r.status
        ORDER BY MAX(r.created_at) DESC
        LIMIT ? OFFSET ?
    ";
    
    $listParams = $params;
    $listParams[] = $limit;
    $listParams[] = $offset;
    $listTypes = $types . 'ii';
    
    $stmt = $conn->prepare($sql);
    if (!empty($listParams)) {
        $stmt->bind_param($listTypes, ...$listParams);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    $reports = [];
    while ($row = $result->fetch_assoc()) {
        // 处理举报理由列表（多个理由用|分隔）
        if (!empty($row['all_reasons'])) {
            $reasons = explode('|', $row['all_reasons']);
            $row['reason'] = $reasons[0]; // 显示第一个理由作为主理由
            $row['reason_list'] = $reasons; // 保存所有理由
        }
        $reports[] = $row;
    }
    
    $stmt->close();
    $conn->close();
    
    return [
        'success' => true,
        'reports' => $reports,
        'total' => $total,
        'page' => $page,
        'limit' => $limit,
        'total_pages' => ceil($total / $limit)
    ];
}

/**
 * 获取单个举报详情（包含同一内容的所有举报信息）
 * @param int $reportId 举报ID
 * @return array
 */
function getReportDetail($reportId) {
    $conn = getDbConnection();
    
    // 先获取这个举报的基本信息
    $stmt = $conn->prepare("
        SELECT target_type, target_id, status
        FROM reports
        WHERE id = ?
    ");
    $stmt->bind_param("i", $reportId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => '举报不存在'];
    }
    
    $reportInfo = $result->fetch_assoc();
    $stmt->close();
    
    // 获取同一内容的所有举报信息（合并显示）
    $stmt = $conn->prepare("
        SELECT 
               MAX(r.id) as id,
               r.target_type,
               r.target_id,
               r.status,
               MAX(r.created_at) as created_at,
               MAX(r.reviewed_at) as reviewed_at,
               MAX(r.admin_id) as admin_id,
               MAX(r.admin_action) as admin_action,
               MAX(r.admin_note) as admin_note,
               MAX(a.username) as admin_name,
               MAX(b.content) as bottle_content,
               b.is_hidden as bottle_is_hidden,
               MAX(c.content) as comment_content,
               c.is_hidden as comment_is_hidden,
               MAX(bu.username) as bottle_owner_name,
               MAX(bu.id) as bottle_owner_id,
               MAX(cu.username) as comment_owner_name,
               MAX(cu.id) as comment_owner_id,
               COUNT(DISTINCT r.id) as report_count,
               GROUP_CONCAT(DISTINCT r.reason ORDER BY r.created_at DESC SEPARATOR '|') as all_reasons,
               GROUP_CONCAT(DISTINCT u.username ORDER BY r.created_at DESC SEPARATOR ', ') as all_reporters,
               GROUP_CONCAT(DISTINCT CONCAT(u.username, ':', r.reason) ORDER BY r.created_at DESC SEPARATOR '||') as reporter_reasons,
               MIN(r.created_at) as first_report_time
        FROM reports r
        LEFT JOIN users u ON r.reporter_id = u.id
        LEFT JOIN admins a ON r.admin_id = a.id
        LEFT JOIN bottles b ON r.target_type = 'bottle' AND r.target_id = b.id
        LEFT JOIN users bu ON b.user_id = bu.id
        LEFT JOIN comments c ON r.target_type = 'comment' AND r.target_id = c.id
        LEFT JOIN users cu ON c.user_id = cu.id
        WHERE r.target_type = ? AND r.target_id = ? AND r.status = ?
        GROUP BY r.target_type, r.target_id, r.status
    ");
    $stmt->bind_param("sis", $reportInfo['target_type'], $reportInfo['target_id'], $reportInfo['status']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => '举报不存在'];
    }
    
    $report = $result->fetch_assoc();
    
    // 处理举报理由和举报人列表
    if (!empty($report['all_reasons'])) {
        $report['reason'] = explode('|', $report['all_reasons'])[0]; // 第一个理由作为主理由
        $report['reason_list'] = explode('|', $report['all_reasons']); // 所有理由列表
    }
    if (!empty($report['all_reporters'])) {
        $report['reporter_list'] = explode(', ', $report['all_reporters']); // 所有举报人列表
    }
    if (!empty($report['reporter_reasons'])) {
        // 解析举报人和理由的对应关系
        $reporterReasons = [];
        $items = explode('||', $report['reporter_reasons']);
        foreach ($items as $item) {
            if (strpos($item, ':') !== false) {
                list($username, $reason) = explode(':', $item, 2);
                if (!isset($reporterReasons[$username])) {
                    $reporterReasons[$username] = [];
                }
                if (!in_array($reason, $reporterReasons[$username])) {
                    $reporterReasons[$username][] = $reason;
                }
            }
        }
        $report['reporter_reasons_map'] = $reporterReasons;
    }
    
    $stmt->close();
    $conn->close();
    
    return ['success' => true, 'report' => $report];
}

