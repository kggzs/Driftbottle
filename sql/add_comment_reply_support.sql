-- 为评论表添加回复支持
-- 添加 parent_id 字段用于标识回复的父评论
-- 如果 parent_id 为 NULL，表示这是一级评论
-- 如果 parent_id 不为 NULL，表示这是对某个评论的回复

ALTER TABLE `comments` 
ADD COLUMN `parent_id` INT(11) DEFAULT NULL COMMENT '父评论ID，NULL表示一级评论' AFTER `id`,
ADD COLUMN `reply_to_user_id` INT(11) DEFAULT NULL COMMENT '回复的目标用户ID' AFTER `parent_id`,
ADD INDEX `idx_parent_id` (`parent_id`),
ADD INDEX `idx_reply_to_user_id` (`reply_to_user_id`),
ADD FOREIGN KEY (`parent_id`) REFERENCES `comments`(`id`) ON DELETE CASCADE,
ADD FOREIGN KEY (`reply_to_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL;

