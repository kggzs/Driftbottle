<?php
$pageTitle = '修改密码';

// 引入头部
require_once 'includes/header.php';

// 处理密码修改请求
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // 验证输入
    if (empty($oldPassword)) {
        $message = '请输入旧密码';
        $messageType = 'danger';
    } elseif (empty($newPassword)) {
        $message = '请输入新密码';
        $messageType = 'danger';
    } elseif ($newPassword !== $confirmPassword) {
        $message = '两次输入的新密码不一致';
        $messageType = 'danger';
    } elseif (strlen($newPassword) < 6) {
        $message = '新密码长度至少为6个字符';
        $messageType = 'danger';
    } else {
        // 修改密码
        $result = $admin->changePassword($oldPassword, $newPassword);
        
        if ($result['success']) {
            $message = $result['message'];
            $messageType = 'success';
        } else {
            $message = $result['message'];
            $messageType = 'danger';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header">
                <h5 class="m-0">修改密码</h5>
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                <div class="alert alert-<?php echo $messageType; ?>" role="alert">
                    <?php echo $message; ?>
                </div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="mb-3">
                        <label for="old_password" class="form-label">旧密码</label>
                        <input type="password" class="form-control" id="old_password" name="old_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">新密码</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                        <div class="form-text">密码长度至少为6个字符</div>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">确认新密码</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">保存修改</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// 引入底部
require_once 'includes/footer.php';
?> 