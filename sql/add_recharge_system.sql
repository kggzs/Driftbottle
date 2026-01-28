-- 创建充值订单表
CREATE TABLE IF NOT EXISTS `recharge_orders` (
    `id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `order_no` VARCHAR(50) NOT NULL UNIQUE COMMENT '商户订单号',
    `user_id` INT(11) NOT NULL COMMENT '用户ID',
    `amount` DECIMAL(10, 2) NOT NULL COMMENT '充值金额（元）',
    `points` INT(11) NOT NULL COMMENT '获得的积分',
    `points_ratio` DECIMAL(10, 2) NOT NULL COMMENT '积分比例（1元=多少积分）',
    `payment_type` VARCHAR(20) DEFAULT NULL COMMENT '支付方式：alipay,wxpay,qqpay,bank等',
    `trade_no` VARCHAR(50) DEFAULT NULL COMMENT '支付平台交易号',
    `status` TINYINT(1) DEFAULT 0 COMMENT '订单状态：0-待支付，1-已支付，2-已取消，3-已退款',
    `notify_data` TEXT DEFAULT NULL COMMENT '支付回调数据（JSON格式）',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
    `paid_at` TIMESTAMP NULL DEFAULT NULL COMMENT '支付时间',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_order_no` (`order_no`),
    INDEX `idx_trade_no` (`trade_no`),
    INDEX `idx_status` (`status`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='充值订单表';

-- 添加支付配置到系统设置表
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `setting_name`, `setting_group`, `setting_type`) VALUES
('PAYMENT_POINTS_RATIO', '100', '充值积分比例（1元=多少积分）', 'payment', 'number'),
('PAYMENT_MERCHANT_ID', '1000', '商户ID', 'payment', 'text'),
('PAYMENT_PLATFORM_PUBLIC_KEY', 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAmiBtRqnkhw5G3Q4jkAZITDV9dDI71+u9fx9sfITtA3wXEnyv93XT8aCpGL68yZQ7HaNJ/Qhf8F7dsxPL+FarXwDQI1jLDjvn7jMLkL4mjBLvId0sXKr5mUnHMmfOUAnEtlKJyCfr8YvPOJJJ3877VpyxEtFtUlv9xfxm19PRqYJbEl5rx+WHxAbW2angKdDtCTjCBBnoz8MVNi2a6mFVnDpGntPQGEz9A9f7n91IhVNLVBwFkj8WLiuJspZ0c3haVJGQ4H05mOp5c9Z3+YY1/QTU1TGPZetSUbidBwz0/5cL7tFE4m5WE74GDeu/ze4yv6355b/kZS3DEUK+6FZ5CwIDAQAB', '平台公钥', 'payment', 'textarea'),
('PAYMENT_MERCHANT_PRIVATE_KEY', 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDFBYRQ+62TuZ8o5Ac7NxyKhHDsfRzmM5CjdZeynpLT+4IMJoZeHF0gZY8qwevP/g91gOpVBHFD11GC4d4oqrGnxZ497ess0w4f7ONot6VJvGrvL6EmgvC5eriIQfwuk1hxL+ZrNa7iWFQajYoS3zXEjUQ4dxj3VO6cRbNuUt+4/adThM2W7PqOfV4oyxFSARUHpPKEO1/LuaRxrN6WVELG55g5ZpSqM9A2b3NBcawdVFL9KGNX+e3nckqyM4frO3bLRDXY3z8NgIkaVBI3ZPW3TyUBA6dkQymuT8EEZHtXiWg1otEw+kbTwW2iC7zoGvXJ8/kTcyxv5CslP24a0wMbAgMBAAECggEAXVwK4hEQpFKuL8M2BgJMfPrbQ8TZf9/pZvufAZ4Qt3CTpExRGaFZI7PcToeLxYh/LNAEunqbbWlHj7yV+DFCc9y56mCmMxxjsg8fh4yWP0WQanzoYQZlKY8UiES0SiG6JBBtoFnU4B6448g0KFMq+FN0g0k0RGczlkuVBe8xYkfB2uD375Kr/EWQWqvKDlzkcyzDih87HObH9JADp5qQpov62brIVUV1rFQhNZfT0bkJTeBQUMZt0SJF1zuLDRlx8aMYs5jiTgQ/uyL+N92DMkeddl7fj+ANUmrkpKiGuPYrbe5wEN6RY2OUOOsK2bIb7iKE8jIBACVoJjEjhJGxgQKBgQD7ty5/n1uT86pVvmRpNlzH/jJPx5i9UhzoMqQ4t2ntw/vWhpWJZ2vH47I1oyRDSDDPi3w3s5Xr3Af9NlZP63RaU5TTbg5BKFa+92PJMiS2osXpdRiQcD7kkHcZ5YZjCyJdQMWcNLrp7iUJN3+lLYHEPxlFuENGsBkBv1x9jtTMWwKBgQDIYANaNEBMGCcvWw8MMtS9rLlNAYtJjU6Dt9d1IWWaNLbfJ5q6OjfY04UU+wH+wF/r0J+lqSpsNQs7NlPbIamXUZ3W8Nd0l+BeC5qM8HrOqokFOO/laYWXaC8Reyr+rH9GeSoE9N9mHQpBefXVTBQ2pKC5TVSN7jvY2srlWstgQQKBgFcm5neTkl6YmBpV8GgpRViNX5gV0IGEQ7P1jLyCbK/BEpoFQRMw9rVf1d0SXkTZYuUJM3oJuNfP+Ago3xuOt1tq4vWNfmv67oXyG9+Wd/WwR/v76gRgiLYUethBixURztUgzwq1ix3hsXsOdyiWp/5tpm9oTArWf+IGAp0Kbg1PAoGAfhH6yfxqH/ZqYR83voMU2yobhFneWy6vIay/wRB8LqPQE2OFtHoAvUmISAUN4k0DjQk8CS0AZgiRwnWSGSN64pwVZTEvPkp4fnNqkBaWDgW6JDEIrxzPUs3YH3WRPZ8mjR6a03eGP2cyFrQ3ejZd2WuHPE9tTceAnBY85kVUBIECgYAIXl3fOb1YQNhfec5w0ifJ/YHB2+as46nIGI7rBMxqWgebRUPSlMP5Vs37eAlhm6u/nzu5loRUswFQnGdZ8/FKS+acfgpzcjnlomKKpnt0KZ/acf12AEjj+q6tcgqX1nSv9MEOX8yKcM8FG+gfXrj0lquu54nPyBcCBS0nxP9ArQ==', '商户私钥', 'payment', 'textarea'),
('PAYMENT_METHODS', 'alipay,wxpay,qqpay,bank', '可用支付方式（用逗号分隔：alipay,wxpay,qqpay,bank）', 'payment', 'text'),
('PAYMENT_DEFAULT_METHOD', 'alipay', '默认支付方式（alipay/wxpay/qqpay/bank）', 'payment', 'text')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
