-- 添加用户IP追踪功能
-- 添加注册IP和评论IP字段
-- 更新日期: 2024-12-20

-- 为users表添加注册IP字段
ALTER TABLE `users` 
ADD COLUMN `register_ip` VARCHAR(50) DEFAULT NULL COMMENT '注册IP地址' AFTER `created_at`;

-- 为comments表添加IP地址字段
ALTER TABLE `comments` 
ADD COLUMN `ip_address` VARCHAR(50) DEFAULT NULL COMMENT '评论IP地址' AFTER `created_at`;

-- 为comments表添加索引（可选，用于查询优化）
ALTER TABLE `comments` 
ADD INDEX `idx_ip_address` (`ip_address`);

