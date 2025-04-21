-- 创建系统设置表
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(50) NOT NULL UNIQUE,
    `setting_value` TEXT NOT NULL,
    `setting_name` VARCHAR(255) NOT NULL,
    `setting_group` VARCHAR(50) NOT NULL,
    `setting_type` ENUM('text', 'number', 'textarea', 'switch', 'select') NOT NULL DEFAULT 'text',
    `options` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入初始数据
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_name`, `setting_group`, `setting_type`) VALUES
-- 基本设置
('SITE_NAME', '漂流瓶', '网站名称', 'basic', 'text'),
('SITE_URL', 'http://localhost', '网站URL', 'basic', 'text'),
('ADMIN_EMAIL', 'admin@example.com', '管理员邮箱', 'basic', 'text'),

-- 积分规则设置
('POINTS_PER_CHECKIN', '10', '每日签到积分', 'points', 'number'),
('POINTS_PER_WEEKLY_CHECKIN', '70', '连续签到7天额外奖励积分', 'points', 'number'),
('POINTS_PER_VIP_CHECKIN', '20', 'VIP会员每次签到额外积分', 'points', 'number'),
('POINTS_PER_BOTTLE', '1', '扔漂流瓶消耗积分', 'points', 'number'),
('POINTS_PER_LIKE', '1', '收到点赞获得积分', 'points', 'number'),

-- 每日限制设置
('DAILY_BOTTLE_LIMIT', '10', '普通用户每日扔瓶限制', 'limits', 'number'),
('DAILY_PICK_LIMIT', '20', '普通用户每日捡瓶限制', 'limits', 'number'),
('VIP_DAILY_BOTTLE_LIMIT', '15', 'VIP用户每日扔瓶限制', 'limits', 'number'),
('VIP_DAILY_PICK_LIMIT', '30', 'VIP用户每日捡瓶限制', 'limits', 'number'),

-- 内容限制设置
('MAX_BOTTLE_LENGTH', '500', '漂流瓶内容最大长度', 'content', 'number'),
('MAX_COMMENT_LENGTH', '200', '评论最大长度', 'content', 'number'),
('MAX_SIGNATURE_LENGTH', '50', '个性签名最大长度', 'content', 'number'); 