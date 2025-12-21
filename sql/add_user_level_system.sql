-- 用户等级系统数据库更新脚本
-- 添加经验值和等级字段，创建经验值历史表

USE `driftbottle`;

-- 在users表中添加经验值和等级字段
ALTER TABLE `users` 
ADD COLUMN `experience` INT(11) DEFAULT 0 COMMENT '用户经验值' AFTER `points`,
ADD COLUMN `level` INT(11) DEFAULT 1 COMMENT '用户等级' AFTER `experience`;

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

-- 在system_settings表中添加经验值规则配置
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_name`, `setting_group`, `setting_type`) VALUES
('EXP_PER_BOTTLE', '10', '发漂流瓶获得经验值', 'experience', 'number'),
('EXP_PER_PICK', '5', '捡漂流瓶获得经验值', 'experience', 'number'),
('EXP_PER_COMMENT', '3', '评论获得经验值', 'experience', 'number')
ON DUPLICATE KEY UPDATE 
`setting_value` = VALUES(`setting_value`),
`setting_name` = VALUES(`setting_name`);

-- 为现有用户初始化经验值和等级（默认经验值0，等级1）
UPDATE `users` SET `experience` = 0, `level` = 1 WHERE `experience` IS NULL OR `level` IS NULL;
