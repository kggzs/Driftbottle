<?php
/**
 * IP地理位置查询功能 - 使用高德地图IP定位API
 */

/**
 * 根据IP获取地理位置信息
 * @param string $ip IP地址
 * @return string 地理位置信息
 */
function getLocationByIp($ip) {
    // 如果是本地IP，返回本地位置
    if ($ip === '127.0.0.1' || $ip === '::1') {
        return '本地开发环境';
    }
    
    // 确保config.php已加载
    if (!defined('AMAP_API_KEY')) {
        error_log('高德地图API Key未配置，请在includes/config.php中设置AMAP_API_KEY');
        return '未知位置';
    }
    
    // 高德IP定位API地址
    $url = 'https://restapi.amap.com/v3/ip?ip=' . urlencode($ip) . '&key=' . urlencode(AMAP_API_KEY);
    
    try {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5, // 5秒超时
                'method' => 'GET',
                'header' => [
                    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ]
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new Exception('无法连接到高德IP定位服务');
        }
        
        $data = json_decode($response, true);
        
        // 检查API返回状态
        if (!isset($data['status']) || $data['status'] != '1') {
            $errorMsg = isset($data['info']) ? $data['info'] : '未知错误';
            throw new Exception('高德IP定位服务返回错误: ' . $errorMsg);
        }
        
        // 解析返回的地理位置信息
        // 处理可能返回数组的情况，转换为字符串
        $province = '';
        if (isset($data['province'])) {
            $province = is_array($data['province']) ? (string)($data['province'][0] ?? '') : (string)$data['province'];
            $province = trim($province);
        }
        
        $city = '';
        if (isset($data['city'])) {
            $city = is_array($data['city']) ? (string)($data['city'][0] ?? '') : (string)$data['city'];
            $city = trim($city);
        }
        
        // 构建位置字符串
        $locationStr = '';
        
        // 如果返回"局域网"，直接返回
        if ($province === '局域网') {
            return '局域网';
        }
        
        // 如果省份和城市都为空，可能是非法IP或国外IP
        if (empty($province) && empty($city)) {
            return '未知位置';
        }
        
        // 组合省份和城市信息
        if (!empty($province)) {
            $locationStr = $province;
        }
        
        if (!empty($city) && $city !== $province) {
            // 如果城市不是直辖市（直辖市时province和city相同），则添加城市信息
            $locationStr .= (!empty($locationStr) ? ' ' : '') . $city;
        }
        
        return !empty($locationStr) ? $locationStr : '未知位置';
        
    } catch (Exception $e) {
        error_log('IP地理位置查询错误: ' . $e->getMessage());
        return '未知位置';
    }
}
