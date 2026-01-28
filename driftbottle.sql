-- 漂流瓶系统数据库初始化脚本
-- 版本: v1.5.0 (包含充值系统、支付功能)
-- 更新日期: 2024-12-24
-- 最后更新: 2026-01-28 (添加充值系统、支付功能)
-- 创建数据库
CREATE DATABASE IF NOT EXISTS `driftbottle` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 使用数据库
USE `driftbottle`;

-- 创建用户表
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `gender` ENUM('男', '女') NOT NULL,
    `signature` TEXT DEFAULT NULL,
    `points` INT(11) DEFAULT 0,
    `experience` INT(11) DEFAULT 0 COMMENT '用户经验值',
    `level` INT(11) DEFAULT 1 COMMENT '用户等级',
    `is_vip` TINYINT(1) DEFAULT 0,
    `vip_expire_date` DATE DEFAULT NULL,
    `vip_level` INT(11) DEFAULT 0,
    `status` TINYINT(1) DEFAULT 1 COMMENT '0:禁用 1:启用',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `register_ip` VARCHAR(50) DEFAULT NULL COMMENT '注册IP地址',
    `last_login_ip` VARCHAR(50) DEFAULT NULL COMMENT '上次登录IP地址',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_is_vip` (`is_vip`),
    INDEX `idx_status` (`status`),
    INDEX `idx_vip_expire_date` (`vip_expire_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建管理员表
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `real_name` VARCHAR(50) DEFAULT NULL,
    `email` VARCHAR(100) DEFAULT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `status` TINYINT(1) DEFAULT 1 COMMENT '0:禁用 1:启用',
    `role_id` INT(11) DEFAULT NULL,
    `last_login_time` TIMESTAMP NULL,
    `last_login_ip` VARCHAR(50) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_status` (`status`),
    INDEX `idx_role_id` (`role_id`),
    INDEX `idx_last_login_time` (`last_login_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建角色表
CREATE TABLE IF NOT EXISTS `admin_roles` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `description` VARCHAR(255) DEFAULT NULL,
    `permissions` TEXT DEFAULT NULL COMMENT 'JSON格式存储权限',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建管理员登录日志表
CREATE TABLE IF NOT EXISTS `admin_login_logs` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `login_ip` VARCHAR(50) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `status` TINYINT(1) DEFAULT 1 COMMENT '0:失败 1:成功',
    `description` TEXT DEFAULT NULL,
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE,
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建管理员操作日志表
CREATE TABLE IF NOT EXISTS `admin_operation_logs` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `admin_id` INT(11) NOT NULL,
    `module` VARCHAR(50) NOT NULL,
    `action` VARCHAR(50) NOT NULL,
    `ip` VARCHAR(50) DEFAULT NULL,
    `description` TEXT DEFAULT NULL,
    `data` TEXT DEFAULT NULL COMMENT 'JSON格式存储操作数据',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE,
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_module` (`module`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建漂流瓶表
CREATE TABLE IF NOT EXISTS `bottles` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `content` TEXT NOT NULL,
    `bottle_type` ENUM('text', 'voice') DEFAULT 'text' COMMENT '漂流瓶类型：text文字漂流瓶，voice语音漂流瓶',
    `audio_file` VARCHAR(255) DEFAULT NULL COMMENT '语音文件路径（仅语音漂流瓶使用）',
    `audio_duration` INT(11) DEFAULT NULL COMMENT '语音时长（秒）',
    `mood` ENUM('开心', '难过', '平静', '愤怒', '期待', '忧郁', '其他') DEFAULT '其他',
    `is_anonymous` TINYINT(1) DEFAULT 0,
    `likes` INT(11) DEFAULT 0,
    `ip_address` VARCHAR(255) DEFAULT NULL,
    `location` VARCHAR(255) DEFAULT NULL,
    `quality_score` INT(11) DEFAULT 0,
    `throw_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('漂流中', '已捡起') DEFAULT '漂流中',
    `is_hidden` TINYINT(1) DEFAULT 0 COMMENT '是否屏蔽：0未屏蔽，1已屏蔽',
    `is_deleted` TINYINT(1) DEFAULT 0 COMMENT '是否删除：0未删除，1已删除',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_throw_time` (`throw_time`),
    INDEX `idx_bottle_type` (`bottle_type`),
    INDEX `idx_quality_score` (`quality_score`),
    INDEX `idx_status_throw_time` (`status`, `throw_time`),
    INDEX `idx_is_hidden` (`is_hidden`),
    INDEX `idx_is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建评论表
CREATE TABLE IF NOT EXISTS `comments` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `parent_id` INT(11) DEFAULT NULL COMMENT '父评论ID，NULL表示一级评论',
    `reply_to_user_id` INT(11) DEFAULT NULL COMMENT '回复的目标用户ID',
    `bottle_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `ip_address` VARCHAR(50) DEFAULT NULL COMMENT '评论IP地址',
    `is_hidden` TINYINT(1) DEFAULT 0 COMMENT '是否屏蔽：0未屏蔽，1已屏蔽',
    `is_deleted` TINYINT(1) DEFAULT 0 COMMENT '是否删除：0未删除，1已删除',
    FOREIGN KEY (`bottle_id`) REFERENCES `bottles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`reply_to_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_bottle_id` (`bottle_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_parent_id` (`parent_id`),
    INDEX `idx_reply_to_user_id` (`reply_to_user_id`),
    INDEX `idx_ip_address` (`ip_address`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_bottle_created` (`bottle_id`, `created_at`),
    INDEX `idx_is_hidden` (`is_hidden`),
    INDEX `idx_is_deleted` (`is_deleted`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建点赞表
CREATE TABLE IF NOT EXISTS `likes` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `bottle_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`bottle_id`) REFERENCES `bottles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY (`bottle_id`, `user_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建捡瓶记录表
CREATE TABLE IF NOT EXISTS `pick_records` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `bottle_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `pick_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`bottle_id`) REFERENCES `bottles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_bottle_id` (`bottle_id`),
    INDEX `idx_pick_time` (`pick_time`),
    INDEX `idx_user_pick_time` (`user_id`, `pick_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建签到表
CREATE TABLE IF NOT EXISTS `checkins` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `checkin_date` DATE NOT NULL,
    `consecutive_days` INT(11) DEFAULT 1,
    `points_earned` INT(11) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY (`user_id`, `checkin_date`),
    INDEX `idx_checkin_date` (`checkin_date`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建积分历史表
CREATE TABLE IF NOT EXISTS `points_history` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `points` INT(11) NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_user_created` (`user_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建经验值历史表
CREATE TABLE IF NOT EXISTS `experience_history` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `experience` INT(11) NOT NULL COMMENT '获得的经验值（正数）或扣除的经验值（负数）',
    `action` VARCHAR(255) NOT NULL COMMENT '操作类型：发漂流瓶、捡漂流瓶、评论等',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='经验值历史记录表';

-- 创建消息中心表
CREATE TABLE IF NOT EXISTS `messages` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `bottle_id` INT(11) NOT NULL,
    `from_user_id` INT(11) DEFAULT NULL COMMENT '发送消息的用户ID，NULL表示系统消息',
    `comment_id` INT(11) DEFAULT NULL,
    `like_id` INT(11) DEFAULT NULL,
    `type` ENUM('评论', '点赞', '举报反馈') NOT NULL,
    `content` TEXT DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`bottle_id`) REFERENCES `bottles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`from_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_is_read` (`is_read`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_user_read_created` (`user_id`, `is_read`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建举报表
CREATE TABLE IF NOT EXISTS `reports` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `reporter_id` INT(11) NOT NULL COMMENT '举报人ID',
    `target_type` ENUM('bottle', 'comment') NOT NULL COMMENT '举报类型：bottle漂流瓶，comment评论',
    `target_id` INT(11) NOT NULL COMMENT '被举报内容ID（漂流瓶ID或评论ID）',
    `reason` TEXT NOT NULL COMMENT '举报理由',
    `status` ENUM('pending', 'approved', 'rejected') DEFAULT 'pending' COMMENT '审核状态：pending待审核，approved已通过，rejected已拒绝',
    `admin_id` INT(11) DEFAULT NULL COMMENT '审核管理员ID',
    `admin_action` VARCHAR(50) DEFAULT NULL COMMENT '管理员操作：delete删除，hide_bottle屏蔽漂流瓶，hide_comment屏蔽评论，no_action无操作',
    `admin_note` TEXT DEFAULT NULL COMMENT '管理员备注',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '举报时间',
    `reviewed_at` TIMESTAMP NULL COMMENT '审核时间',
    FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE SET NULL,
    INDEX `idx_reporter_id` (`reporter_id`),
    INDEX `idx_target_type` (`target_type`),
    INDEX `idx_target_id` (`target_id`),
    INDEX `idx_status` (`status`),
    INDEX `idx_admin_id` (`admin_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_status_created` (`status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='举报表';

-- 创建每日限制表
CREATE TABLE IF NOT EXISTS `daily_limits` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `date` DATE NOT NULL,
    `throw_count` INT(11) DEFAULT 0,
    `pick_count` INT(11) DEFAULT 0,
    `free_throws_used` INT(11) DEFAULT 0,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY (`user_id`, `date`),
    INDEX `idx_date` (`date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建公告表
CREATE TABLE IF NOT EXISTS `announcements` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `type` ENUM('普通', '重要', '紧急') DEFAULT '普通',
    `status` TINYINT(1) DEFAULT 1 COMMENT '0:隐藏 1:显示',
    `start_time` TIMESTAMP NULL,
    `end_time` TIMESTAMP NULL,
    `created_by` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_status` (`status`),
    INDEX `idx_type` (`type`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_start_time` (`start_time`),
    INDEX `idx_end_time` (`end_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建充值订单表
CREATE TABLE IF NOT EXISTS `recharge_orders` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `order_no` VARCHAR(50) NOT NULL UNIQUE COMMENT '商户订单号',
    `user_id` INT(11) NOT NULL COMMENT '用户ID',
    `amount` DECIMAL(10, 2) NOT NULL COMMENT '充值金额（元）',
    `points` INT(11) NOT NULL COMMENT '获得的积分',
    `points_ratio` DECIMAL(10, 2) NOT NULL COMMENT '积分比例（1元=多少积分）',
    `payment_type` VARCHAR(20) DEFAULT NULL COMMENT '支付方式：alipay,wxpay,qqpay,bank等',
    `trade_no` VARCHAR(50) DEFAULT NULL COMMENT '支付平台交易号',
    `status` TINYINT(1) DEFAULT 0 COMMENT '订单状态：0-待支付，1-已支付，2-已取消，3-已退款',
    `notify_data` TEXT DEFAULT NULL COMMENT '支付回调数据（JSON格式）',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `paid_at` TIMESTAMP NULL DEFAULT NULL COMMENT '支付时间',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_order_no` (`order_no`),
    INDEX `idx_trade_no` (`trade_no`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='充值订单表';

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
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_setting_group` (`setting_group`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入系统设置
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_name`, `setting_group`, `setting_type`) VALUES
-- 基本设置
('SITE_NAME', '漂流瓶', '网站名称', 'basic', 'text'),
('SITE_URL', 'http://localhost', '网站URL', 'basic', 'text'),
('ADMIN_EMAIL', 'admin@example.com', '管理员邮箱', 'basic', 'text'),
('AMAP_API_KEY', '9ae69cf1b2e4fd09bc1df0c394962dee', '高德地图API Key', 'basic', 'text'),
('ICP_LICENSE', '', 'ICP备案号', 'basic', 'text'),
('POLICE_LICENSE', '', '公安备案号', 'basic', 'text'),
('COPYRIGHT_INFO', '© 2023 漂流瓶 版权所有', '版权信息', 'basic', 'text'),
('WEBMASTER_EMAIL', 'admin@example.com', '站长邮箱', 'basic', 'text'),

-- 积分规则设置
('POINTS_PER_CHECKIN', '10', '每日签到积分', 'points', 'number'),
('POINTS_PER_WEEKLY_CHECKIN', '70', '连续签到7天额外奖励积分', 'points', 'number'),
('POINTS_PER_VIP_CHECKIN', '20', 'VIP会员每次签到额外积分', 'points', 'number'),
('POINTS_PER_BOTTLE', '1', '扔漂流瓶消耗积分', 'points', 'number'),
('POINTS_PER_LIKE', '1', '收到点赞获得积分', 'points', 'number'),
('POINTS_PER_REPORT_APPROVED', '5', '举报成功奖励积分', 'points', 'number'),

-- 每日限制设置
('DAILY_BOTTLE_LIMIT', '10', '普通用户每日扔瓶限制', 'limits', 'number'),
('DAILY_PICK_LIMIT', '20', '普通用户每日捡瓶限制', 'limits', 'number'),
('VIP_DAILY_BOTTLE_LIMIT', '15', 'VIP用户每日扔瓶限制', 'limits', 'number'),
('VIP_DAILY_PICK_LIMIT', '30', 'VIP用户每日捡瓶限制', 'limits', 'number'),

-- 内容限制设置
('MAX_BOTTLE_LENGTH', '500', '漂流瓶内容最大长度', 'content', 'number'),
('MAX_COMMENT_LENGTH', '200', '评论最大长度', 'content', 'number'),
('MAX_SIGNATURE_LENGTH', '50', '个性签名最大长度', 'content', 'number');

-- 添加VIP会员开通积分配置
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_name`, `setting_group`, `setting_type`) VALUES
-- VIP会员开通积分设置
('VIP_POINTS_1_MONTH', '100', 'VIP会员1个月开通积分', 'vip', 'number'),
('VIP_POINTS_3_MONTHS', '250', 'VIP会员3个月开通积分', 'vip', 'number'),
('VIP_POINTS_6_MONTHS', '450', 'VIP会员6个月开通积分', 'vip', 'number'),
('VIP_POINTS_12_MONTHS', '800', 'VIP会员12个月开通积分', 'vip', 'number');

-- 添加经验值规则配置
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_name`, `setting_group`, `setting_type`) VALUES
-- 经验值规则设置
('EXP_PER_BOTTLE', '10', '发漂流瓶获得经验值', 'experience', 'number'),
('EXP_PER_PICK', '5', '捡漂流瓶获得经验值', 'experience', 'number'),
('EXP_PER_COMMENT', '3', '评论获得经验值', 'experience', 'number')
ON DUPLICATE KEY UPDATE 
`setting_value` = VALUES(`setting_value`),
`setting_name` = VALUES(`setting_name`);

-- 添加支付配置
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_name`, `setting_group`, `setting_type`) VALUES
-- 支付配置设置
('PAYMENT_POINTS_RATIO', '100', '充值积分比例（1元=多少积分）', 'payment', 'number'),
('PAYMENT_MERCHANT_ID', '1000', '商户ID', 'payment', 'text'),
('PAYMENT_PLATFORM_PUBLIC_KEY', 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAmiBtRqnkhw5G3Q4jkAZITDV9dDI71+u9fx9sfITtA3wXEnyv93XT8aCpGL68yZQ7HaNJ/Qhf8F7dsxPL+FarXwDQI1jLDjvn7jMLkL4mjBLvId0sXKr5mUnHMmfOUAnEtlKJyCfr8YvPOJJJ3877VpyxEtFtUlv9xfxm19PRqYJbEl5rx+WHxAbW2angKdDtCTjCBBnoz8MVNi2a6mFVnDpGntPQGEz9A9f7n91IhVNLVBwFkj8WLiuJspZ0c3haVJGQ4H05mOp5c9Z3+YY1/QTU1TGPZetSUbidBwz0/5cL7tFE4m5WE74GDeu/ze4yv6355b/kZS3DEUK+6FZ5CwIDAQAB', '平台公钥', 'payment', 'textarea'),
('PAYMENT_MERCHANT_PRIVATE_KEY', 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDFBYRQ+62TuZ8o5Ac7NxyKhHDsfRzmM5CjdZeynpLT+4IMJoZeHF0gZY8qwevP/g91gOpVBHFD11GC4d4oqrGnxZ497ess0w4f7ONot6VJvGrvL6EmgvC5eriIQfwuk1hxL+ZrNa7iWFQajYoS3zXEjUQ4dxj3VO6cRbNuUt+4/adThM2W7PqOfV4oyxFSARUHpPKEO1/LuaRxrN6WVELG55g5ZpSqM9A2b3NBcawdVFL9KGNX+e3nckqyM4frO3bLRDXY3z8NgIkaVBI3ZPW3TyUBA6dkQymuT8EEZHtXiWg1otEw+kbTwW2iC7zoGvXJ8/kTcyxv5CslP24a0wMbAgMBAAECggEAXVwK4hEQpFKuL8M2BgJMfPrbQ8TZf9/pZvufAZ4Qt3CTpExRGaFZI7PcToeLxYh/LNAEunqbbWlHj7yV+DFCc9y56mCmMxxjsg8fh4yWP0WQanzoYQZlKY8UiES0SiG6JBBtoFnU4B6448g0KFMq+FN0g0k0RGczlkuVBe8xYkfB2uD375Kr/EWQWqvKDlzkcyzDih87HObH9JADp5qQpov62brIVUV1rFQhNZfT0bkJTeBQUMZt0SJF1zuLDRlx8aMYs5jiTgQ/uyL+N92DMkeddl7fj+ANUmrkpKiGuPYrbe5wEN6RY2OUOOsK2bIb7iKE8jIBACVoJjEjhJGxgQKBgQD7ty5/n1uT86pVvmRpNlzH/jJPx5i9UhzoMqQ4t2ntw/vWhpWJZ2vH47I1oyRDSDDPi3w3s5Xr3Af9NlZP63RaU5TTbg5BKFa+92PJMiS2osXpdRiQcD7kkHcZ5YZjCyJdQMWcNLrp7iUJN3+lLYHEPxlFuENGsBkBv1x9jtTMWwKBgQDIYANaNEBMGCcvWw8MMtS9rLlNAYtJjU6Dt9d1IWWaNLbfJ5q6OjfY04UU+wH+wF/r0J+lqSpsNQs7NlPbIamXUZ3W8Nd0l+BeC5qM8HrOqokFOO/laYWXaC8Reyr+rH9GeSoE9N9mHQpBefXVTBQ2pKC5TVSN7jvY2srlWstgQQKBgFcm5neTkl6YmBpV8GgpRViNX5gV0IGEQ7P1jLyCbK/BEpoFQRMw9rVf1d0SXkTZYuUJM3oJuNfP+Ago3xuOt1tq4vWNfmv67oXyG9+Wd/WwR/v76gRgiLYUethBixURztUgzwq1ix3hsXsOdyiWp/5tpm9oTArWf+IGAp0Kbg1PAoGAfhH6yfxqH/ZqYR83voMU2yobhFneWy6vIay/wRB8LqPQE2OFtHoAvUmISAUN4k0DjQk8CS0AZgiRwnWSGSN64pwVZTEvPkp4fnNqkBaWDgW6JDEIrxzPUs3YH3WRPZ8mjR6a03eGP2cyFrQ3ejZd2WuHPE9tTceAnBY85kVUBIECgYAIXl3fOb1YQNhfec5w0ifJ/YHB2+as46nIGI7rBMxqWgebRUPSlMP5Vs37eAlhm6u/nzu5loRUswFQnGdZ8/FKS+acfgpzcjnlomKKpnt0KZ/acf12AEjj+q6tcgqX1nSv9MEOX8yKcM8FG+gfXrj0lquu54nPyBcCBS0nxP9ArQ==', '商户私钥', 'payment', 'textarea'),
('PAYMENT_METHODS', 'alipay,wxpay,qqpay,bank', '可用支付方式（用逗号分隔：alipay,wxpay,qqpay,bank）', 'payment', 'text'),
('PAYMENT_DEFAULT_METHOD', 'alipay', '默认支付方式（alipay/wxpay/qqpay/bank）', 'payment', 'text')
ON DUPLICATE KEY UPDATE 
`setting_value` = VALUES(`setting_value`),
`setting_name` = VALUES(`setting_name`);

-- 更新超级管理员角色，确保拥有settings权限
INSERT INTO `admin_roles` (`name`, `description`, `permissions`)
VALUES ('超级管理员', '拥有系统所有权限', '{"all":true,"users":true,"bottles":true,"comments":true,"announcements":true,"statistics":true,"settings":true}')
ON DUPLICATE KEY UPDATE 
`permissions` = '{"all":true,"users":true,"bottles":true,"comments":true,"announcements":true,"statistics":true,"settings":true}';

-- 插入默认管理员账号(密码: admin)
-- 使用PHP password_hash()函数生成的哈希值，算法为PASSWORD_DEFAULT
INSERT INTO `admins` (`username`, `password`, `real_name`, `role_id`, `status`) 
VALUES ('admin', '$2y$10$iAOZ2qPkagwVG2yHwZIAqupNDvO7/.XQlO.qB8.XbtfxdS58Tb6Oa', '系统管理员', 1, 1)
ON DUPLICATE KEY UPDATE 
`password` = '$2y$10$iAOZ2qPkagwVG2yHwZIAqupNDvO7/.XQlO.qB8.XbtfxdS58Tb6Oa',
`real_name` = '系统管理员',
`role_id` = 1,
`status` = 1;

-- 插入测试用户数据
INSERT INTO `users` (`username`, `password`, `gender`, `points`, `status`) VALUES
('user1', '$2y$10$xEps7kW9TQBIwhX3HVjO.uJL5gufCTBKwQwCZlZIrRuMNQ66hE2QG', '男', 10, 1), -- 密码: password1
('user2', '$2y$10$tVf/vYK0ZPcGG8nzOqkqleG4JRTDgZ7wNPR.d1qCc4uxPYFTCp4Aq', '女', 15, 1), -- 密码: password2
('user3', '$2y$10$r6bXCVz.xeXPnkKRsXXXN.VljHXSgScYWJA0qFMSbUNQL0oN/YUkm', '男', 5, 1) -- 密码: password3
ON DUPLICATE KEY UPDATE
`password` = VALUES(`password`),
`gender` = VALUES(`gender`),
`points` = VALUES(`points`),
`status` = VALUES(`status`);

-- 插入测试漂流瓶数据
INSERT INTO `bottles` (`user_id`, `content`, `mood`, `is_anonymous`, `likes`, `status`) 
VALUES
(1, '今天天气真好，希望能有人看到我的漂流瓶！', '开心', 0, 0, '漂流中'),
(2, '这是一个秘密：我很喜欢吃冰淇淋，但从不告诉别人~', '其他', 1, 0, '漂流中'),
(3, '人生就像一场旅行，不必在乎目的地，在乎的是沿途的风景以及看风景的心情。', '平静', 0, 0, '漂流中'),
(1, '有时候，安静地听一首歌，就能想起很多事情。', '忧郁', 0, 0, '漂流中'),
(2, '希望捡到这个漂流瓶的人能够开心每一天！', '期待', 0, 0, '漂流中');

-- 插入测试公告
INSERT INTO `announcements` (`title`, `content`, `type`, `created_by`) 
VALUES 
('系统更新公告', '亲爱的用户，系统将于本周六凌晨2点进行例行维护，预计维护时间2小时。', '重要', 1),
('新功能上线', '漂流瓶新增表情包功能，让您的心情传递更生动！', '普通', 1)
ON DUPLICATE KEY UPDATE 
`content` = VALUES(`content`),
`type` = VALUES(`type`);

-- 设置外键检查
SET FOREIGN_KEY_CHECKS = 1; 