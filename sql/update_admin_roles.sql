-- 更新超级管理员角色，确保拥有settings权限
UPDATE `admin_roles` 
SET `permissions` = '{"all":true,"users":true,"bottles":true,"comments":true,"announcements":true,"statistics":true,"settings":true}'
WHERE `name` = '超级管理员';

-- 如果超级管理员角色不存在，则创建
INSERT INTO `admin_roles` (`name`, `description`, `permissions`)
SELECT '超级管理员', '拥有系统所有权限', '{"all":true,"users":true,"bottles":true,"comments":true,"announcements":true,"statistics":true,"settings":true}'
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM `admin_roles` WHERE `name` = '超级管理员'); 