-- 添加软删除字段到bottles和comments表
-- 执行此SQL前请先备份数据库

USE `driftbottle`;

-- 在bottles表添加软删除字段
ALTER TABLE `bottles` 
ADD COLUMN `is_deleted` TINYINT(1) DEFAULT 0 COMMENT '是否删除：0未删除，1已删除' AFTER `is_hidden`,
ADD INDEX `idx_is_deleted` (`is_deleted`);

-- 在comments表添加软删除字段
ALTER TABLE `comments` 
ADD COLUMN `is_deleted` TINYINT(1) DEFAULT 0 COMMENT '是否删除：0未删除，1已删除' AFTER `is_hidden`,
ADD INDEX `idx_is_deleted` (`is_deleted`);

-- 修改messages表的type字段，添加'举报反馈'类型
ALTER TABLE `messages` 
MODIFY COLUMN `type` ENUM('评论', '点赞', '举报反馈') NOT NULL;

