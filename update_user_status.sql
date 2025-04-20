-- 为用户表添加status字段
ALTER TABLE `users` ADD COLUMN `status` TINYINT(1) DEFAULT 1 COMMENT '0:禁用 1:启用' AFTER `vip_level`;

-- 更新所有现有用户的状态为启用
UPDATE `users` SET `status` = 1 WHERE `status` IS NULL;

-- 更新完成信息
SELECT 'User status field added and all existing users enabled' as 'Update Status'; 