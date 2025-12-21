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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_settings'])) {
    try {
        $conn = getDbConnection();
        
        // 基本设置
        if (isset($_POST['basic'])) {
            foreach ($_POST['basic'] as $key => $value) {
                $safeValue = sanitizeInput($value);
                updateSetting($key, $safeValue);
            }
        }
        
        // 积分规则设置
        if (isset($_POST['points'])) {
            foreach ($_POST['points'] as $key => $value) {
                $safeValue = (int)$value; // 确保积分值为整数
                updateSetting($key, $safeValue);
            }
        }
        
        // 每日限制设置
        if (isset($_POST['limits'])) {
            foreach ($_POST['limits'] as $key => $value) {
                $safeValue = (int)$value; // 确保限制值为整数
                updateSetting($key, $safeValue);
            }
        }
        
        // 内容限制设置
        if (isset($_POST['content'])) {
            foreach ($_POST['content'] as $key => $value) {
                $safeValue = (int)$value; // 确保限制值为整数
                updateSetting($key, $safeValue);
            }
        }
        
        // VIP会员价格设置
        if (isset($_POST['vip'])) {
            foreach ($_POST['vip'] as $key => $value) {
                $safeValue = (int)$value; // 确保积分值为整数
                updateSetting($key, $safeValue);
            }
        }
        
        // 经验值规则设置
        if (isset($_POST['experience'])) {
            foreach ($_POST['experience'] as $key => $value) {
                $safeValue = (int)$value; // 确保经验值为整数
                updateSetting($key, $safeValue);
            }
        }
        
        $message = '设置已成功保存！';
        $messageType = 'success';
        
        // 更新完成后重新加载settings
        header("Location: settings.php?success=1");
        exit;
        
    } catch (Exception $e) {
        $message = '保存设置时出错：' . $e->getMessage();
        $messageType = 'danger';
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
                                <input type="text" class="form-control" id="<?php echo $setting['setting_key']; ?>" name="basic[<?php echo $setting['setting_key']; ?>]" value="<?php echo htmlspecialchars($setting['setting_value']); ?>">
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
</script>

<?php
// 引入底部文件
require_once 'includes/footer.php';
?> 