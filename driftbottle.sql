-- 漂流瓶系统数据库初始化脚本
-- 版本: v1.2.0 (包含语音漂流瓶功能和用户等级系统)
-- 更新日期: 2024-12-20
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
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
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
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
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
    FOREIGN KEY (`admin_id`) REFERENCES `admins`(`id`) ON DELETE CASCADE
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
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建评论表
CREATE TABLE IF NOT EXISTS `comments` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `bottle_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `content` TEXT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`bottle_id`) REFERENCES `bottles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建点赞表
CREATE TABLE IF NOT EXISTS `likes` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `bottle_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`bottle_id`) REFERENCES `bottles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY (`bottle_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建捡瓶记录表
CREATE TABLE IF NOT EXISTS `pick_records` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `bottle_id` INT(11) NOT NULL,
    `user_id` INT(11) NOT NULL,
    `pick_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`bottle_id`) REFERENCES `bottles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
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
    UNIQUE KEY (`user_id`, `checkin_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建积分历史表
CREATE TABLE IF NOT EXISTS `points_history` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `points` INT(11) NOT NULL,
    `action` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
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
    `from_user_id` INT(11) NOT NULL,
    `comment_id` INT(11) DEFAULT NULL,
    `like_id` INT(11) DEFAULT NULL,
    `type` ENUM('评论', '点赞') NOT NULL,
    `content` TEXT DEFAULT NULL,
    `is_read` TINYINT(1) DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`bottle_id`) REFERENCES `bottles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`from_user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 创建每日限制表
CREATE TABLE IF NOT EXISTS `daily_limits` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT(11) NOT NULL,
    `date` DATE NOT NULL,
    `throw_count` INT(11) DEFAULT 0,
    `pick_count` INT(11) DEFAULT 0,
    `free_throws_used` INT(11) DEFAULT 0,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY (`user_id`, `date`)
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
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- 插入系统设置
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