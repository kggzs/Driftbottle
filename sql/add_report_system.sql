-- 举报系统数据库更新脚本
-- 添加举报表、屏蔽字段等

USE `driftbottle`;

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

-- 在bottles表添加屏蔽字段
ALTER TABLE `bottles` 
ADD COLUMN `is_hidden` TINYINT(1) DEFAULT 0 COMMENT '是否屏蔽：0未屏蔽，1已屏蔽' AFTER `status`,
ADD INDEX `idx_is_hidden` (`is_hidden`);

-- 在comments表添加屏蔽字段
ALTER TABLE `comments` 
ADD COLUMN `is_hidden` TINYINT(1) DEFAULT 0 COMMENT '是否屏蔽：0未屏蔽，1已屏蔽' AFTER `ip_address`,
ADD INDEX `idx_is_hidden` (`is_hidden`);

-- 在system_settings表中添加举报成功积分配置
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_name`, `setting_group`, `setting_type`) VALUES
('POINTS_PER_REPORT_APPROVED', '5', '举报成功奖励积分', 'points', 'number')
ON DUPLICATE KEY UPDATE 
`setting_value` = VALUES(`setting_value`),
`setting_name` = VALUES(`setting_name`);

