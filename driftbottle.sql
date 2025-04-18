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
    `is_vip` TINYINT(1) DEFAULT 0,
    `vip_expire_date` DATE DEFAULT NULL,
    `vip_level` INT(11) DEFAULT 0,
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
    `login_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `login_ip` VARCHAR(50) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `status` TINYINT(1) DEFAULT 1 COMMENT '0:失败 1:成功',
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

-- 插入测试数据（可选）
-- 插入测试用户数据
INSERT INTO `users` (`username`, `password`, `gender`, `points`) VALUES
('user1', '$2y$10$xEps7kW9TQBIwhX3HVjO.uJL5gufCTBKwQwCZlZIrRuMNQ66hE2QG', '男', 10), -- 密码: password1
('user2', '$2y$10$tVf/vYK0ZPcGG8nzOqkqleG4JRTDgZ7wNPR.d1qCc4uxPYFTCp4Aq', '女', 15), -- 密码: password2
('user3', '$2y$10$r6bXCVz.xeXPnkKRsXXXN.VljHXSgScYWJA0qFMSbUNQL0oN/YUkm', '男', 5); -- 密码: password3

-- 插入测试漂流瓶数据
INSERT INTO `bottles` (`user_id`, `content`, `mood`, `is_anonymous`, `likes`, `status`) VALUES
(1, '今天天气真好，希望能有人看到我的漂流瓶！', '开心', 0, 0, '漂流中'),
(2, '这是一个秘密：我很喜欢吃冰淇淋，但从不告诉别人~', '其他', 1, 0, '漂流中'),
(3, '人生就像一场旅行，不必在乎目的地，在乎的是沿途的风景以及看风景的心情。', '平静', 0, 0, '漂流中'),
(1, '有时候，安静地听一首歌，就能想起很多事情。', '忧郁', 0, 0, '漂流中'),
(2, '希望捡到这个漂流瓶的人能够开心每一天！', '期待', 0, 0, '漂流中');

-- 插入测试公告
INSERT INTO `announcements` (`title`, `content`, `type`, `created_by`) VALUES
('系统更新公告', '亲爱的用户，系统将于本周六凌晨2点进行例行维护，预计维护时间2小时。', '重要', 1),
('新功能上线', '漂流瓶新增表情包功能，让您的心情传递更生动！', '普通', 1);

-- 插入超级管理员角色
INSERT INTO `admin_roles` (`name`, `description`, `permissions`) VALUES
('超级管理员', '拥有系统所有权限', '{"all": true}');

-- 插入默认管理员账号(密码: admin)
-- 使用PHP password_hash()函数生成的哈希值，算法为PASSWORD_DEFAULT
INSERT INTO `admins` (`username`, `password`, `real_name`, `role_id`, `status`) VALUES
('admin', '$2y$10$iAOZ2qPkagwVG2yHwZIAqupNDvO7/.XQlO.qB8.XbtfxdS58Tb6Oa', '系统管理员', 1, 1);

-- 设置外键检查（如果需要）
SET FOREIGN_KEY_CHECKS = 1; 