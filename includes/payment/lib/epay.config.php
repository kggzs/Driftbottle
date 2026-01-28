<?php
/* *
 * 支付配置文件
 * 从数据库读取配置
 */

require_once __DIR__ . '/../../config.php';

// 从数据库获取支付配置
function getPaymentConfig() {
    $apiurl = 'https://pay.kggzs.cn/'; // 支付接口地址写死
    
    $pid = getSetting('PAYMENT_MERCHANT_ID', '1000');
    $platform_public_key = getSetting('PAYMENT_PLATFORM_PUBLIC_KEY', '');
    $merchant_private_key = getSetting('PAYMENT_MERCHANT_PRIVATE_KEY', '');
    
    $epay_config = [
        //支付接口地址（写死）
        'apiurl' => $apiurl,
        
        //商户ID（从数据库读取）
        'pid' => $pid,
        
        //平台公钥（从数据库读取）
        'platform_public_key' => $platform_public_key,
        
        //商户私钥（从数据库读取）
        'merchant_private_key' => $merchant_private_key,
    ];
    
    return $epay_config;
}

// 获取积分比例
function getPointsRatio() {
    return (float)getSetting('PAYMENT_POINTS_RATIO', 100);
}

// 获取可用的支付方式配置
function getAvailablePaymentMethods() {
    $config = getSetting('PAYMENT_METHODS', 'alipay,wxpay,qqpay,bank');
    $methods = explode(',', $config);
    $methods = array_map('trim', $methods);
    $methods = array_filter($methods); // 移除空值
    
    // 如果配置为空，返回默认值
    if (empty($methods)) {
        return ['alipay', 'wxpay', 'qqpay', 'bank'];
    }
    
    return array_values($methods);
}

// 获取默认支付方式
function getDefaultPaymentMethod() {
    return getSetting('PAYMENT_DEFAULT_METHOD', 'alipay');
}
