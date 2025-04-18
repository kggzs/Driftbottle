<?php
/**
 * IP地理位置查询功能
 */

class IPLocation {
    private $fp;
    private $firstip;
    private $lastip;
    private $totalip;
    
    public function __construct($filename = null) {
        // 使用绝对路径
        if ($filename === null) {
            $filename = dirname(__DIR__) . '/ip/qqwry.dat';
        }
        
        // 检查文件是否存在
        if (!file_exists($filename)) {
            error_log("IP数据库文件不存在: " . $filename);
            throw new Exception('IP数据库文件不存在，请确保已下载并放置正确位置');
        }
        
        // 检查文件是否可读
        if (!is_readable($filename)) {
            error_log("IP数据库文件不可读: " . $filename);
            throw new Exception('IP数据库文件不可读，请检查文件权限');
        }
        
        $this->fp = @fopen($filename, 'rb');
        if (!$this->fp) {
            error_log("无法打开IP数据库文件: " . $filename);
            throw new Exception('无法打开IP数据库文件，请检查文件权限');
        }
        
        // 检查文件大小
        $filesize = filesize($filename);
        if ($filesize < 100) { // 假设最小文件大小
            error_log("IP数据库文件大小异常: " . $filesize . " bytes");
            throw new Exception('IP数据库文件可能已损坏');
        }
        
        try {
            $this->firstip = $this->getlong();
            $this->lastip = $this->getlong();
            $this->totalip = ($this->lastip - $this->firstip) / 7;
        } catch (Exception $e) {
            error_log("IP数据库文件格式错误: " . $e->getMessage());
            throw new Exception('IP数据库文件格式错误，请重新下载');
        }
    }
    
    public function __destruct() {
        if ($this->fp) {
            fclose($this->fp);
        }
    }
    
    private function getlong() {
        $result = unpack('Vlong', fread($this->fp, 4));
        return $result['long'];
    }
    
    private function getstring() {
        $string = '';
        while (true) {
            $char = fread($this->fp, 1);
            if (ord($char) == 0) {
                break;
            }
            $string .= $char;
        }
        return $string;
    }
    
    private function getarea() {
        $byte = fread($this->fp, 1);
        switch (ord($byte)) {
            case 0:
                $area = '';
                break;
            case 1:
            case 2:
                fseek($this->fp, $this->getlong());
                $area = $this->getstring();
                break;
            default:
                $area = $this->getstring();
                break;
        }
        return $area;
    }
    
    public function getlocation($ip) {
        if (!$this->fp) return null;
        
        $location['ip'] = $ip;
        $ip = $this->ip2long($ip);
        
        $l = 0;
        $u = $this->totalip;
        $findip = $this->lastip;
        
        while ($l <= $u) {
            $i = floor(($l + $u) / 2);
            fseek($this->fp, $this->firstip + $i * 7);
            $startip = strrev(fread($this->fp, 4));
            if ($ip < strrev($startip)) {
                $u = $i - 1;
            } else {
                fseek($this->fp, $this->getlong3());
                $endip = strrev(fread($this->fp, 4));
                if ($ip > strrev($endip)) {
                    $l = $i + 1;
                } else {
                    $findip = $this->firstip + $i * 7;
                    break;
                }
            }
        }
        
        fseek($this->fp, $findip);
        $location['startip'] = long2ip($this->getlong());
        $offset = $this->getlong3();
        fseek($this->fp, $offset);
        $location['endip'] = long2ip($this->getlong());
        $byte = fread($this->fp, 1);
        switch (ord($byte)) {
            case 1:
                $countryOffset = $this->getlong3();
                fseek($this->fp, $countryOffset);
                $byte = fread($this->fp, 1);
                switch (ord($byte)) {
                    case 2:
                        fseek($this->fp, $this->getlong3());
                        $location['country'] = $this->getstring();
                        fseek($this->fp, $countryOffset + 4);
                        $location['area'] = $this->getarea();
                        break;
                    default:
                        $location['country'] = $this->getstring($byte);
                        $location['area'] = $this->getarea();
                        break;
                }
                break;
            case 2:
                fseek($this->fp, $this->getlong3());
                $location['country'] = $this->getstring();
                fseek($this->fp, $offset + 8);
                $location['area'] = $this->getarea();
                break;
            default:
                $location['country'] = $this->getstring($byte);
                $location['area'] = $this->getarea();
                break;
        }
        
        if ($location['country'] == ' CZ88.NET') {
            $location['country'] = '未知';
        }
        if ($location['area'] == ' CZ88.NET') {
            $location['area'] = '';
        }
        
        return $location;
    }
    
    private function getlong3() {
        $result = unpack('Vlong', fread($this->fp, 3).chr(0));
        return $result['long'];
    }
    
    private function ip2long($ip) {
        $ip = explode('.', $ip);
        return ($ip[0] << 24) | ($ip[1] << 16) | ($ip[2] << 8) | $ip[3];
    }
}

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
    
    try {
        // 使用ip9.com.cn API获取IP地址信息
        $url = "https://ip9.com.cn/get?ip=" . urlencode($ip);
        
        $context = stream_context_create([
            'http' => [
                'timeout' => 5, // 设置超时时间为5秒
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
            throw new Exception('无法连接到IP查询服务');
        }
        
        $data = json_decode($response, true);
        
        if (isset($data['ret']) && $data['ret'] == 200 && isset($data['data'])) {
            $locationData = $data['data'];
            $country = $locationData['country'] ?? '未知国家';
            $prov = $locationData['prov'] ?? '';
            $city = $locationData['city'] ?? '';
            $area = $locationData['area'] ?? '';
            
            $locationStr = $country;
            if (!empty($prov) && $prov != $country) {
                $locationStr .= ' ' . $prov;
            }
            if (!empty($city) && $city != $prov) {
                $locationStr .= ' ' . $city;
            }
            if (!empty($area) && $area != $city) {
                $locationStr .= ' ' . $area;
            }
            
            return $locationStr;
        } else {
            throw new Exception('IP查询服务返回无效数据');
        }
    } catch (Exception $e) {
        error_log('IP地理位置查询错误: ' . $e->getMessage());
    }
    
    // 如果所有方法都失败，回退到本地查询
    try {
        $iplocation = new IPLocation();
        $location = $iplocation->getlocation($ip);
        
        if ($location) {
            $locationStr = $location['country'];
            if (!empty($location['area'])) {
                $locationStr .= '，' . $location['area'];
            }
            return $locationStr;
        }
    } catch (Exception $e) {
        error_log('本地IP地理位置查询错误: ' . $e->getMessage());
    }
    
    return '未知位置';
} 