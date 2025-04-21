-- 添加VIP会员开通积分配置
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_name`, `setting_group`, `setting_type`) VALUES
-- VIP会员开通积分设置
('VIP_POINTS_1_MONTH', '100', 'VIP会员1个月开通积分', 'vip', 'number'),
('VIP_POINTS_3_MONTHS', '250', 'VIP会员3个月开通积分', 'vip', 'number'),
('VIP_POINTS_6_MONTHS', '450', 'VIP会员6个月开通积分', 'vip', 'number'),
('VIP_POINTS_12_MONTHS', '800', 'VIP会员12个月开通积分', 'vip', 'number'); 