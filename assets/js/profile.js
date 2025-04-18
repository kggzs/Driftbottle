// 签到按钮点击事件
$('#checkinButton').on('click', function() {
    // 如果按钮已经被禁用，直接返回
    if ($(this).prop('disabled')) {
        return;
    }
    
    // 禁用按钮并显示加载状态
    $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> 签到中...');
    
    $.ajax({
        url: 'api/user_checkin.php',
        method: 'POST',
        success: function(response) {
            if (response.success) {
                // 更新签到状态和积分
                updateCheckinStatus(true);
                updatePoints(response.points_earned);
                
                // 显示签到成功消息
                showMessage('签到成功！获得 ' + response.points_earned + ' 积分', 'success');
                
                // 如果是连续签到7天，显示额外奖励消息
                if (response.is_weekly_bonus) {
                    showMessage('恭喜获得连续签到7天额外奖励！', 'success');
                }
                
                // 如果是VIP，显示VIP奖励消息
                if (response.is_vip) {
                    showMessage('VIP用户获得额外 ' + response.vip_bonus_points + ' 积分奖励！', 'success');
                }
            } else {
                showMessage(response.message || '签到失败，请稍后再试', 'error');
            }
        },
        error: function() {
            showMessage('网络错误，请稍后再试', 'error');
        },
        complete: function() {
            // 延迟1秒后恢复按钮状态，防止快速重复点击
            setTimeout(function() {
                $('#checkinButton').prop('disabled', false).html('签到');
            }, 1000);
        }
    });
});

// 更新签到状态
function updateCheckinStatus(hasCheckedIn) {
    const $button = $('#checkinButton');
    if (hasCheckedIn) {
        $button.prop('disabled', true)
               .removeClass('btn-primary')
               .addClass('btn-secondary')
               .html('今日已签到');
    } else {
        $button.prop('disabled', false)
               .removeClass('btn-secondary')
               .addClass('btn-primary')
               .html('签到');
    }
} 