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

-- 确保至少有一个用户存在（如果没有的话）
INSERT IGNORE INTO `users` (`id`, `username`, `password`, `gender`) 
VALUES (1, 'admin', '$2y$10$xEps7kW9TQBIwhX3HVjO.uJL5gufCTBKwQwCZlZIrRuMNQ66hE2QG', '男');

-- 插入测试公告
INSERT INTO `announcements` (`title`, `content`, `type`, `created_by`) 
VALUES 
('系统更新公告', '亲爱的用户，系统将于本周六凌晨2点进行例行维护，预计维护时间2小时。', '重要', 1),
('新功能上线', '漂流瓶新增表情包功能，让您的心情传递更生动！', '普通', 1)
ON DUPLICATE KEY UPDATE 
    `title` = VALUES(`title`),
    `content` = VALUES(`content`),
    `type` = VALUES(`type`); 