-- 添加description字段到admin_login_logs表
ALTER TABLE `admin_login_logs` 
ADD COLUMN `description` TEXT DEFAULT NULL AFTER `status`;

-- 更新表名以匹配代码中使用的名称
ALTER TABLE `admin_login_logs` 
CHANGE COLUMN `login_time` `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP; 