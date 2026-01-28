<?php
// 设置页面标题
$pageTitle = '系统设置';

// 引入头部文件
require_once 'includes/header.php';

// 检查权限
if (!$admin->hasPermission('settings')) {
    echo "<div class='alert alert-danger'>您没有权限访问此页面</div>";
    require_once 'includes/footer.php';
    exit;
}

// 处理表单提交
$message = '';
$messageType = '';

// 辅助函数：获取设置的默认元数据
function getSettingMetadata($key, $group) {
    // 设置名称映射表
    $nameMap = [
        'SITE_NAME' => '网站名称',
        'SITE_URL' => '网站URL',
        'ADMIN_EMAIL' => '管理员邮箱',
        'AMAP_API_KEY' => '高德地图API Key',
        'ICP_LICENSE' => 'ICP备案号',
        'POLICE_LICENSE' => '公安备案号',
        'COPYRIGHT_INFO' => '版权信息',
        'WEBMASTER_EMAIL' => '站长邮箱',
        'POINTS_PER_CHECKIN' => '每日签到积分',
        'POINTS_PER_WEEKLY_CHECKIN' => '连续签到7天额外奖励积分',
        'POINTS_PER_VIP_CHECKIN' => 'VIP会员每次签到额外积分',
        'POINTS_PER_BOTTLE' => '扔漂流瓶消耗积分',
        'POINTS_PER_LIKE' => '收到点赞获得积分',
        'POINTS_PER_REPORT_APPROVED' => '举报成功奖励积分',
        'DAILY_BOTTLE_LIMIT' => '普通用户每日扔瓶限制',
        'DAILY_PICK_LIMIT' => '普通用户每日捡瓶限制',
        'VIP_DAILY_BOTTLE_LIMIT' => 'VIP用户每日扔瓶限制',
        'VIP_DAILY_PICK_LIMIT' => 'VIP用户每日捡瓶限制',
        'MAX_BOTTLE_LENGTH' => '漂流瓶内容最大长度',
        'MAX_COMMENT_LENGTH' => '评论最大长度',
        'MAX_SIGNATURE_LENGTH' => '个性签名最大长度',
        'VIP_POINTS_1_MONTH' => 'VIP会员1个月开通积分',
        'VIP_POINTS_3_MONTHS' => 'VIP会员3个月开通积分',
        'VIP_POINTS_6_MONTHS' => 'VIP会员6个月开通积分',
        'VIP_POINTS_12_MONTHS' => 'VIP会员12个月开通积分',
        'EXP_PER_BOTTLE' => '发漂流瓶获得经验值',
        'EXP_PER_PICK' => '捡漂流瓶获得经验值',
        'EXP_PER_COMMENT' => '评论获得经验值',
        'PAYMENT_POINTS_RATIO' => '充值积分比例（1元=多少积分）',
        'PAYMENT_MERCHANT_ID' => '商户ID',
        'PAYMENT_PLATFORM_PUBLIC_KEY' => '平台公钥',
        'PAYMENT_MERCHANT_PRIVATE_KEY' => '商户私钥',
        'PAYMENT_METHODS' => '可用支付方式（用逗号分隔：alipay,wxpay,qqpay,bank）',
        'PAYMENT_DEFAULT_METHOD' => '默认支付方式（alipay/wxpay/qqpay/bank）',
    ];
    
    return isset($nameMap[$key]) ? $nameMap[$key] : $key;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    $conn = null;
    $connectionClosed = false;
    
    try {
        $conn = getDbConnection();
        
        // 开始事务，批量更新时使用事务提升性能
        $conn->begin_transaction();
        
        $updateCount = 0;
        $errors = [];
        
        // 使用INSERT ... ON DUPLICATE KEY UPDATE，包含所有必需字段
        // 基本设置
        if (isset($_POST['basic'])) {
            foreach ($_POST['basic'] as $key => $value) {
                $safeValue = sanitizeInput($value);
                $settingName = getSettingMetadata($key, 'basic');
                
                // 使用INSERT ... ON DUPLICATE KEY UPDATE，包含所有必需字段
                $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_name, setting_group, setting_type) 
                        VALUES (?, ?, ?, 'basic', 'text')
                        ON DUPLICATE KEY UPDATE setting_value = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $errors[] = "准备SQL失败 ({$key}): " . $conn->error;
                    continue;
                }
                $stmt->bind_param("ssss", $key, $safeValue, $settingName, $safeValue);
                if (!$stmt->execute()) {
                    $errors[] = "保存设置失败 ({$key}): " . $stmt->error;
                } else {
                    $updateCount++;
                }
                $stmt->close();
            }
        }
        
        // 积分规则设置
        if (isset($_POST['points'])) {
            foreach ($_POST['points'] as $key => $value) {
                $safeValue = (int)$value; // 确保积分值为整数
                $settingName = getSettingMetadata($key, 'points');
                
                $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_name, setting_group, setting_type) 
                        VALUES (?, ?, ?, 'points', 'number')
                        ON DUPLICATE KEY UPDATE setting_value = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $errors[] = "准备SQL失败 ({$key}): " . $conn->error;
                    continue;
                }
                $stmt->bind_param("ssss", $key, $safeValue, $settingName, $safeValue);
                if (!$stmt->execute()) {
                    $errors[] = "保存设置失败 ({$key}): " . $stmt->error;
                } else {
                    $updateCount++;
                }
                $stmt->close();
            }
        }
        
        // 每日限制设置
        if (isset($_POST['limits'])) {
            foreach ($_POST['limits'] as $key => $value) {
                $safeValue = (int)$value; // 确保限制值为整数
                $settingName = getSettingMetadata($key, 'limits');
                
                $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_name, setting_group, setting_type) 
                        VALUES (?, ?, ?, 'limits', 'number')
                        ON DUPLICATE KEY UPDATE setting_value = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $errors[] = "准备SQL失败 ({$key}): " . $conn->error;
                    continue;
                }
                $stmt->bind_param("ssss", $key, $safeValue, $settingName, $safeValue);
                if (!$stmt->execute()) {
                    $errors[] = "保存设置失败 ({$key}): " . $stmt->error;
                } else {
                    $updateCount++;
                }
                $stmt->close();
            }
        }
        
        // 内容限制设置
        if (isset($_POST['content'])) {
            foreach ($_POST['content'] as $key => $value) {
                $safeValue = (int)$value; // 确保限制值为整数
                $settingName = getSettingMetadata($key, 'content');
                
                $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_name, setting_group, setting_type) 
                        VALUES (?, ?, ?, 'content', 'number')
                        ON DUPLICATE KEY UPDATE setting_value = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $errors[] = "准备SQL失败 ({$key}): " . $conn->error;
                    continue;
                }
                $stmt->bind_param("ssss", $key, $safeValue, $settingName, $safeValue);
                if (!$stmt->execute()) {
                    $errors[] = "保存设置失败 ({$key}): " . $stmt->error;
                } else {
                    $updateCount++;
                }
                $stmt->close();
            }
        }
        
        // VIP会员价格设置
        if (isset($_POST['vip'])) {
            foreach ($_POST['vip'] as $key => $value) {
                $safeValue = (int)$value; // 确保积分值为整数
                $settingName = getSettingMetadata($key, 'vip');
                
                $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_name, setting_group, setting_type) 
                        VALUES (?, ?, ?, 'vip', 'number')
                        ON DUPLICATE KEY UPDATE setting_value = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $errors[] = "准备SQL失败 ({$key}): " . $conn->error;
                    continue;
                }
                $stmt->bind_param("ssss", $key, $safeValue, $settingName, $safeValue);
                if (!$stmt->execute()) {
                    $errors[] = "保存设置失败 ({$key}): " . $stmt->error;
                } else {
                    $updateCount++;
                }
                $stmt->close();
            }
        }
        
        // 经验值规则设置
        if (isset($_POST['experience'])) {
            foreach ($_POST['experience'] as $key => $value) {
                $safeValue = (int)$value; // 确保经验值为整数
                $settingName = getSettingMetadata($key, 'experience');
                
                $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_name, setting_group, setting_type) 
                        VALUES (?, ?, ?, 'experience', 'number')
                        ON DUPLICATE KEY UPDATE setting_value = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $errors[] = "准备SQL失败 ({$key}): " . $conn->error;
                    continue;
                }
                $stmt->bind_param("ssss", $key, $safeValue, $settingName, $safeValue);
                if (!$stmt->execute()) {
                    $errors[] = "保存设置失败 ({$key}): " . $stmt->error;
                } else {
                    $updateCount++;
                }
                $stmt->close();
            }
        }
        
        // 支付配置设置
        if (isset($_POST['payment'])) {
            foreach ($_POST['payment'] as $key => $value) {
                $safeValue = sanitizeInput($value);
                $settingName = getSettingMetadata($key, 'payment');
                
                // 判断字段类型
                $settingType = 'text';
                if ($key === 'PAYMENT_POINTS_RATIO') {
                    $settingType = 'number';
                    $safeValue = (float)$safeValue; // 确保为数字
                } else if ($key === 'PAYMENT_PLATFORM_PUBLIC_KEY' || $key === 'PAYMENT_MERCHANT_PRIVATE_KEY') {
                    $settingType = 'textarea';
                }
                
                $sql = "INSERT INTO system_settings (setting_key, setting_value, setting_name, setting_group, setting_type) 
                        VALUES (?, ?, ?, 'payment', ?)
                        ON DUPLICATE KEY UPDATE setting_value = ?";
                $stmt = $conn->prepare($sql);
                if ($stmt === false) {
                    $errors[] = "准备SQL失败 ({$key}): " . $conn->error;
                    continue;
                }
                $stmt->bind_param("sssss", $key, $safeValue, $settingName, $settingType, $safeValue);
                if (!$stmt->execute()) {
                    $errors[] = "保存设置失败 ({$key}): " . $stmt->error;
                } else {
                    $updateCount++;
                }
                $stmt->close();
            }
        }
        
        // 检查是否有错误
        if (!empty($errors)) {
            try {
                if ($conn && !$connectionClosed) {
                    $conn->rollback();
                    $conn->close();
                    $connectionClosed = true;
                }
            } catch (Exception $e) {
                error_log("回滚事务失败: " . $e->getMessage());
            }
            throw new Exception("保存设置时出现错误: " . implode("; ", $errors));
        }
        
        // 提交事务
        $conn->commit();
        $conn->close();
        $connectionClosed = true;
        
        // 清除设置缓存，强制下次重新加载
        clearSettingsCache();
        
        $message = '设置已成功保存！';
        $messageType = 'success';
        
        // 更新完成后重新加载settings
        header("Location: settings.php?success=1");
        exit;
        
    } catch (Exception $e) {
        // 发生错误时回滚事务
        if (isset($conn) && $conn instanceof mysqli && !$connectionClosed) {
            try {
                $conn->rollback();
            } catch (Exception $rollbackError) {
                // 忽略回滚错误，记录日志即可
                error_log("回滚事务失败: " . $rollbackError->getMessage());
            }
            try {
                $conn->close();
                $connectionClosed = true;
            } catch (Exception $closeError) {
                // 忽略关闭错误，记录日志即可
                error_log("关闭连接失败: " . $closeError->getMessage());
            }
        }
        $message = '保存设置时出错：' . $e->getMessage();
        $messageType = 'danger';
        error_log("保存设置异常: " . $e->getMessage());
    }
}

// 如果有成功的URL参数
if (isset($_GET['success']) && $_GET['success'] == 1) {
    $message = '设置已成功保存！';
    $messageType = 'success';
}

// 获取所有设置
$settings = [];
try {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM system_settings ORDER BY setting_group, id");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $settings[$row['setting_group']][] = $row;
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    $message = '获取设置时出错：' . $e->getMessage();
    $messageType = 'danger';
}
?>

<!-- 页面内容 -->
<div class="container-fluid">
    <h1 class="mt-2 mb-4"><i class="bi bi-gear me-2"></i>系统设置</h1>
    
    <?php if (!empty($message)): ?>
    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="basic-tab" data-bs-toggle="tab" data-bs-target="#basic" type="button" role="tab" aria-controls="basic" aria-selected="true">基本设置</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="points-tab" data-bs-toggle="tab" data-bs-target="#points" type="button" role="tab" aria-controls="points" aria-selected="false">积分规则</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="limits-tab" data-bs-toggle="tab" data-bs-target="#limits" type="button" role="tab" aria-controls="limits" aria-selected="false">用户限制</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="content-tab" data-bs-toggle="tab" data-bs-target="#content" type="button" role="tab" aria-controls="content" aria-selected="false">内容设置</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="vip-tab" data-bs-toggle="tab" data-bs-target="#vip" type="button" role="tab" aria-controls="vip" aria-selected="false">VIP价格设置</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="experience-tab" data-bs-toggle="tab" data-bs-target="#experience" type="button" role="tab" aria-controls="experience" aria-selected="false">经验值规则</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="payment-tab" data-bs-toggle="tab" data-bs-target="#payment" type="button" role="tab" aria-controls="payment" aria-selected="false">支付配置</button>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="tab-content" id="settingsTabsContent">
                    <!-- 基本设置 -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                        <h4 class="mb-3">基本设置</h4>
                        <?php if (isset($settings['basic'])): ?>
                        <div class="row">
                            <?php foreach ($settings['basic'] as $setting): ?>
                            <div class="col-md-6 mb-3">
                                <label for="<?php echo $setting['setting_key']; ?>" class="form-label"><?php echo $setting['setting_name']; ?></label>
                                <?php if ($setting['setting_key'] === 'AMAP_API_KEY'): ?>
                                <!-- 高德API Key使用密码输入框，带显示/隐藏功能 -->
                                <div class="input-group">
                                    <input type="password" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="basic[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" placeholder="请输入高德地图API Key">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('<?php echo $setting['setting_key']; ?>')">
                                        <i class="bi bi-eye" id="<?php echo $setting['setting_key']; ?>_icon"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">
                                    <i class="bi bi-info-circle"></i> 高德地图API Key用于IP地址定位功能，请妥善保管。获取API Key请访问：<a href="https://console.amap.com/dev/key/app" target="_blank">高德开放平台</a>
                                </small>
                                <?php else: ?>
                                <input type="text" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="basic[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">没有可用的基本设置</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 积分规则设置 -->
                    <div class="tab-pane fade" id="points" role="tabpanel" aria-labelledby="points-tab">
                        <h4 class="mb-3">积分规则设置</h4>
                        <?php if (isset($settings['points'])): ?>
                        <div class="row">
                            <?php foreach ($settings['points'] as $setting): ?>
                            <div class="col-md-6 mb-3">
                                <label for="<?php echo $setting['setting_key']; ?>" class="form-label"><?php echo $setting['setting_name']; ?></label>
                                <input type="number" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="points[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" min="0">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">没有可用的积分规则设置</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 用户限制设置 -->
                    <div class="tab-pane fade" id="limits" role="tabpanel" aria-labelledby="limits-tab">
                        <h4 class="mb-3">用户限制设置</h4>
                        <?php if (isset($settings['limits'])): ?>
                        <div class="row">
                            <?php foreach ($settings['limits'] as $setting): ?>
                            <div class="col-md-6 mb-3">
                                <label for="<?php echo $setting['setting_key']; ?>" class="form-label"><?php echo $setting['setting_name']; ?></label>
                                <input type="number" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="limits[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" min="0">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">没有可用的用户限制设置</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 内容设置 -->
                    <div class="tab-pane fade" id="content" role="tabpanel" aria-labelledby="content-tab">
                        <h4 class="mb-3">内容设置</h4>
                        <?php if (isset($settings['content'])): ?>
                        <div class="row">
                            <?php foreach ($settings['content'] as $setting): ?>
                            <div class="col-md-6 mb-3">
                                <label for="<?php echo $setting['setting_key']; ?>" class="form-label"><?php echo $setting['setting_name']; ?></label>
                                <input type="number" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="content[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" min="0">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">没有可用的内容设置</div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- VIP会员价格设置 -->
                    <div class="tab-pane fade" id="vip" role="tabpanel" aria-labelledby="vip-tab">
                        <h4 class="mb-3">VIP会员价格设置</h4>
                        <?php if (isset($settings['vip'])): ?>
                        <div class="row">
                            <?php foreach ($settings['vip'] as $setting): ?>
                            <div class="col-md-6 mb-3">
                                <label for="<?php echo $setting['setting_key']; ?>" class="form-label"><?php echo $setting['setting_name']; ?></label>
                                <input type="number" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="vip[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" min="0">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i> 说明：以上设置用于配置不同时长VIP会员的开通所需积分。更改配置后，网站前端将立即更新价格显示。
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> 未找到VIP价格设置。请确保已导入<code>vip_points_settings.sql</code>文件到数据库。
                            <div class="mt-2">
                                <a href="settings.php?reload=1" class="btn btn-sm btn-outline-primary">刷新页面</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 经验值规则设置 -->
                    <div class="tab-pane fade" id="experience" role="tabpanel" aria-labelledby="experience-tab">
                        <h4 class="mb-3">经验值规则设置</h4>
                        <?php if (isset($settings['experience'])): ?>
                        <div class="row">
                            <?php foreach ($settings['experience'] as $setting): ?>
                            <div class="col-md-6 mb-3">
                                <label for="<?php echo $setting['setting_key']; ?>" class="form-label"><?php echo $setting['setting_name']; ?></label>
                                <input type="number" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="experience[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" min="0">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i> 说明：以上设置用于配置不同操作获得的经验值。经验值用于计算用户等级，等级计算公式：level = floor(sqrt(experience / 100)) + 1
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> 未找到经验值规则设置。请确保已导入<code>add_user_level_system.sql</code>文件到数据库。
                            <div class="mt-2">
                                <a href="settings.php?reload=1" class="btn btn-sm btn-outline-primary">刷新页面</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 支付配置设置 -->
                    <div class="tab-pane fade" id="payment" role="tabpanel" aria-labelledby="payment-tab">
                        <h4 class="mb-3">支付配置设置</h4>
                        <?php if (isset($settings['payment'])): ?>
                        <div class="row">
                            <?php foreach ($settings['payment'] as $setting): ?>
                            <div class="col-md-12 mb-3">
                                <label for="<?php echo $setting['setting_key']; ?>" class="form-label"><?php echo $setting['setting_name']; ?></label>
                                <?php if ($setting['setting_key'] === 'PAYMENT_POINTS_RATIO'): ?>
                                <input type="number" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="payment[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>" min="1" step="0.01">
                                <small class="form-text text-muted">例如：100 表示 1元 = 100积分</small>
                                <?php elseif ($setting['setting_key'] === 'PAYMENT_PLATFORM_PUBLIC_KEY' || $setting['setting_key'] === 'PAYMENT_MERCHANT_PRIVATE_KEY'): ?>
                                <div class="input-group">
                                    <textarea class="form-control" id="<?php echo $setting['setting_key']; ?>" name="payment[<?php echo $setting['setting_key']; ?>]" rows="5" placeholder="请输入<?php echo $setting['setting_name']; ?>"><?php echo htmlspecialchars($setting['setting_value']); ?></textarea>
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('<?php echo $setting['setting_key']; ?>')">
                                        <i class="bi bi-eye" id="<?php echo $setting['setting_key']; ?>_icon"></i>
                                    </button>
                                </div>
                                <small class="form-text text-muted">请妥善保管密钥信息，不要泄露</small>
                                <?php else: ?>
                                <input type="text" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="payment[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class="bi bi-info-circle"></i> 说明：
                            <ul class="mb-0">
                                <li>支付接口地址已固定为：<code>https://pay.kggzs.cn/</code></li>
                                <li>积分比例：设置1元可以兑换多少积分</li>
                                <li>平台公钥和商户私钥：从支付平台获取，请妥善保管</li>
                                <li>商户ID：支付平台分配的商户标识</li>
                                <li>可用支付方式：用逗号分隔，可选值：<code>alipay</code>（支付宝）、<code>wxpay</code>（微信支付）、<code>qqpay</code>（QQ钱包）、<code>bank</code>（云闪付）</li>
                                <li>默认支付方式：用户打开充值弹窗时默认选中的支付方式</li>
                            </ul>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> 未找到支付配置。请确保已导入<code>add_recharge_system.sql</code>文件到数据库。
                            <div class="mt-2">
                                <a href="settings.php?reload=1" class="btn btn-sm btn-outline-primary">刷新页面</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                    <button type="submit" name="save_settings" class="btn btn-primary">
                        <i class="bi bi-save me-1"></i> 保存设置
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 如果URL中有指定的tab参数，激活对应的标签页
    const urlParams = new URLSearchParams(window.location.search);
    const tabParam = urlParams.get('tab');
    
    if (tabParam) {
        const tabElement = document.querySelector(`#${tabParam}-tab`);
        if (tabElement) {
            const tabTrigger = new bootstrap.Tab(tabElement);
            tabTrigger.show();
        }
    }
});

// 切换密码输入框的显示/隐藏
function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(inputId + '_icon');
    
    if (!input || !icon) return;
    
    // 如果是textarea，切换显示/隐藏
    if (input.tagName === 'TEXTAREA') {
        if (input.style.display === 'none') {
            input.style.display = 'block';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        } else {
            input.style.display = 'none';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    } else if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}
</script>

<?php
// 引入底部文件
require_once 'includes/footer.php';
?> 