-- 为漂流瓶表添加语音功能字段
-- 执行此SQL前请先备份数据库

USE `driftbottle`;

-- 添加漂流瓶类型字段：'text' 文字漂流瓶, 'voice' 语音漂流瓶
ALTER TABLE `bottles` 
ADD COLUMN `bottle_type` ENUM('text', 'voice') DEFAULT 'text' AFTER `content`;

-- 添加语音文件路径字段
ALTER TABLE `bottles` 
ADD COLUMN `audio_file` VARCHAR(255) DEFAULT NULL AFTER `bottle_type`;

-- 添加语音时长字段（秒）
ALTER TABLE `bottles` 
ADD COLUMN `audio_duration` INT(11) DEFAULT NULL AFTER `audio_file`;

