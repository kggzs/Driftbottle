-- 添加高德地图API Key到系统设置表
-- 如果已存在则更新，不存在则插入

INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_name`, `setting_group`, `setting_type`) 
VALUES ('AMAP_API_KEY', '9ae69cf1b2e4fd09bc1df0c394962dee', '高德地图API Key', 'basic', 'text')
ON DUPLICATE KEY UPDATE 
    `setting_value` = VALUES(`setting_value`),
    `setting_name` = VALUES(`setting_name`),
    `setting_group` = VALUES(`setting_group`),
    `setting_type` = VALUES(`setting_type`);

