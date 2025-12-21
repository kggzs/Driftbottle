<?php
require_once 'config.php';

/**
 * 安全中间件类
 * 提供输入验证、XSS防护、CSRF防护等安全功能
 */
class Security {
    /**
     * 初始化安全设置
     */
    public static function init() {
        // 设置适当的内容安全策略(CSP)
        self::setContentSecurityPolicy();
        
        // 设置其他安全相关的HTTP头
        self::setSecurityHeaders();
        
        // 防止会话固定攻击
        self::preventSessionFixation();
        
        // 验证CSRF令牌
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            self::validateCsrfToken();
        }
    }
    
    /**
     * 设置内容安全策略
     */
    private static function setContentSecurityPolicy() {
        $cspPolicies = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.bootcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://cdn.bootcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            "img-src 'self' data: https:",
            "font-src 'self' data: https://cdn.bootcss.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net",
            "connect-src 'self'",
            "frame-src 'self'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'"
        ];
        
        $cspHeader = implode('; ', $cspPolicies);
        header("Content-Security-Policy: $cspHeader");
    }
    
    /**
     * 设置其他安全相关的HTTP头
     */
    private static function setSecurityHeaders() {
        // 防止点击劫持
        header('X-Frame-Options: SAMEORIGIN');
        
        // 启用XSS过滤
        header('X-XSS-Protection: 1; mode=block');
        
        // 禁止MIME类型嗅探
        header('X-Content-Type-Options: nosniff');
        
        // 引用策略
        header('Referrer-Policy: same-origin');
        
        // 强制使用HTTPS (如果服务器支持)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }
    
    /**
     * 防止会话固定攻击
     */
    private static function preventSessionFixation() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // 每30分钟重新生成会话ID
        if (isset($_SESSION['last_regenerated'])) {
            $regenerated = $_SESSION['last_regenerated'];
            if (time() - $regenerated > 1800) {
                session_regenerate_id(true);
                $_SESSION['last_regenerated'] = time();
            }
        } else {
            $_SESSION['last_regenerated'] = time();
        }
    }
    
    /**
     * 生成CSRF令牌
     * @return string 生成的CSRF令牌
     */
    public static function generateCsrfToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * 验证CSRF令牌
     * @throws Exception 如果令牌验证失败
     */
    public static function validateCsrfToken() {
        // API请求通常从前端JavaScript发出，使用自定义头或JSON数据中的令牌
        $token = null;
        
        // 检查是否在HTTP头中
        $headers = getallheaders();
        if (isset($headers['X-CSRF-Token'])) {
            $token = $headers['X-CSRF-Token'];
        }
        
        // 检查是否在POST数据中
        if ($token === null && isset($_POST['csrf_token'])) {
            $token = $_POST['csrf_token'];
        }
        
        // 检查是否在JSON数据中
        if ($token === null) {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            if (is_array($data) && isset($data['csrf_token'])) {
                $token = $data['csrf_token'];
            }
        }
        
        // 如果不需要严格验证CSRF，可以在开发环境禁用
        if (DEBUG_MODE) {
            return true;
        }
        
        if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            // 可以记录潜在的CSRF攻击尝试
            error_log('潜在的CSRF攻击: 无效的令牌');
            
            // 安全起见，清除会话并重新生成CSRF令牌
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            
            // 返回错误而不是抛出异常，使API可以正常响应
            return false;
        }
        
        return true;
    }
    
    /**
     * 验证并过滤输入
     * @param array $data 要验证的数据
     * @param array $rules 验证规则
     * @return array 包含验证结果和过滤后的数据
     */
    public static function validateInput($data, $rules) {
        $filteredData = [];
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = isset($data[$field]) ? $data[$field] : null;
            
            // 检查是否必填
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = $rule['message'] ?? "字段 '$field' 是必需的";
                continue;
            }
            
            // 如果字段不存在且不是必需的，跳过后续验证
            if (!isset($data[$field]) && (!isset($rule['required']) || !$rule['required'])) {
                continue;
            }
            
            // 应用过滤器
            if (isset($rule['filter'])) {
                switch ($rule['filter']) {
                    case 'string':
                        $value = sanitizeInput($value);
                        break;
                    case 'html':
                        $value = sanitizeHtml($value);
                        break;
                    case 'url':
                        $value = sanitizeUrl($value);
                        break;
                    case 'email':
                        $value = filter_var($value, FILTER_SANITIZE_EMAIL);
                        break;
                    case 'int':
                        $value = (int)$value;
                        break;
                    case 'float':
                        $value = (float)$value;
                        break;
                    case 'bool':
                        $value = (bool)$value;
                        break;
                }
            }
            
            // 验证特定类型
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = $rule['message'] ?? "字段 '$field' 必须是有效的电子邮件地址";
                        }
                        break;
                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$field] = $rule['message'] ?? "字段 '$field' 必须是有效的URL";
                        }
                        break;
                    case 'numeric':
                        if (!is_numeric($value)) {
                            $errors[$field] = $rule['message'] ?? "字段 '$field' 必须是数字";
                        }
                        break;
                    case 'alpha':
                        if (!ctype_alpha($value)) {
                            $errors[$field] = $rule['message'] ?? "字段 '$field' 只能包含字母";
                        }
                        break;
                    case 'alphanumeric':
                        if (!ctype_alnum($value)) {
                            $errors[$field] = $rule['message'] ?? "字段 '$field' 只能包含字母和数字";
                        }
                        break;
                }
            }
            
            // 验证长度
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = $rule['message'] ?? "字段 '$field' 最小长度为 {$rule['min_length']}";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = $rule['message'] ?? "字段 '$field' 最大长度为 {$rule['max_length']}";
            }
            
            // 自定义正则表达式验证
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field] = $rule['message'] ?? "字段 '$field' 格式不正确";
            }
            
            // 将验证后的值添加到过滤后的数据中
            $filteredData[$field] = $value;
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'data' => $filteredData
        ];
    }
    
    /**
     * 检测并阻止常见的恶意输入
     * @param string $input 要检测的输入
     * @return bool 如果检测到恶意输入，返回true
     */
    public static function detectMaliciousInput($input) {
        if (!is_string($input)) {
            return false;
        }
        
        // 检测常见的XSS攻击模式
        $xssPatterns = [
            '/(<script[^>]*>.*?<\/script>)/is',
            '/(javascript\s*:)/is',
            '/(\bon\w+\s*=)/is',
            '/(document\.cookie)/is',
            '/(document\.location)/is',
            '/(document\.write)/is',
            '/(eval\s*\()/is',
            '/(alert\s*\()/is',
            '/(prompt\s*\()/is',
            '/(confirm\s*\()/is',
            '/(\bdata:text\/html)/is',
            '/(\bdata:application\/javascript)/is',
            '/(\bexpression\s*\()/is',
            '/(\<iframe)/is',
            '/(\<embed)/is',
            '/(\<object)/is',
            '/(\<meta)/is',
            '/(\<base)/is',
            '/(\<form)/is',
            '/(\<applet)/is',
            '/(\<link)/is'
        ];
        
        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                error_log('检测到潜在的XSS攻击: ' . substr($input, 0, 100));
                return true;
            }
        }
        
        // 检测常见的SQL注入模式
        $sqlInjectionPatterns = [
            '/(UNION\s+ALL\s+SELECT)/is',
            '/(SELECT\s+\*\s+FROM)/is',
            '/(OR\s+1=1)/is',
            '/(DROP\s+TABLE)/is',
            '/(DELETE\s+FROM)/is',
            '/(UPDATE\s+\w+\s+SET)/is',
            '/(INTO\s+OUTFILE)/is',
            '/(SLEEP\s*\()/is',
            '/(BENCHMARK\s*\()/is',
            '/(WAITFOR\s+DELAY)/is',
            '/(;\s*SELECT)/is',
            '/(;\s*INSERT)/is',
            '/(;\s*UPDATE)/is',
            '/(;\s*DELETE)/is'
        ];
        
        foreach ($sqlInjectionPatterns as $pattern) {
            if (preg_match($pattern, $input)) {
                error_log('检测到潜在的SQL注入攻击: ' . substr($input, 0, 100));
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 高级HTML净化，用于内容富文本
     * @param string $html HTML内容
     * @return string 过滤后的HTML
     */
    public static function purifyHtml($html) {
        if (!is_string($html)) {
            return '';
        }
        
        // 步骤1: 基本清理
        $html = trim($html);
        $html = stripslashes($html);
        
        // 步骤2: 只允许特定的HTML标签
        $allowedTags = '<p><br><b><i><u><em><strong><span><div><ul><ol><li><h1><h2><h3><h4><h5><h6><pre><code><blockquote><q><hr><table><thead><tbody><tr><th><td>';
        $html = strip_tags($html, $allowedTags);
        
        // 步骤3: 清除所有标签属性中的事件处理程序
        $html = preg_replace('/(<[^>]+?)on\w+\s*=\s*["\'][^"\']*["\']?/i', '$1', $html);
        
        // 步骤4: 清除所有标签中的style属性（或者仅保留安全的style）
        $html = preg_replace('/(<[^>]+?)style\s*=\s*["\'][^"\']*["\']?/i', '$1', $html);
        
        // 步骤5: 清除所有a标签的危险href值
        $html = preg_replace('/<a\s+[^>]*href\s*=\s*["\']?(javascript:|data:|vbscript:)[^"\']*["\']?[^>]*>/i', '', $html);
        
        // 步骤6: 清除其他可能的危险协议
        $html = preg_replace('/(javascript:|data:|vbscript:|expression:)/i', '', $html);
        
        // 步骤7: 删除注释
        $html = preg_replace('/<!--(.|\s)*?-->/', '', $html);
        
        // 步骤8: 删除XML处理指令
        $html = preg_replace('/<\?(.|\s)*?\?>/', '', $html);
        
        // 步骤9: 删除CDATA部分
        $html = preg_replace('/<!\[CDATA\[(.|\s)*?\]\]>/', '', $html);
        
        return $html;
    }
    
    /**
     * 过滤JSON数据，防止XSS攻击
     * @param mixed $data 需要过滤的数据
     * @return mixed 过滤后的数据
     */
    public static function sanitizeJsonData($data) {
        if (is_string($data)) {
            // 对字符串应用HTML实体转换
            return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        } elseif (is_array($data)) {
            // 递归处理数组
            foreach ($data as $key => $value) {
                $data[$key] = self::sanitizeJsonData($value);
            }
        } elseif (is_object($data)) {
            // 递归处理对象
            foreach ($data as $key => $value) {
                $data->$key = self::sanitizeJsonData($value);
            }
        }
        
        return $data;
    }
    
    /**
     * 安全的输出JSON数据
     * @param mixed $data 要输出的数据
     * @param bool $sanitize 是否对数据进行过滤
     */
    public static function outputJson($data, $sanitize = true) {
        // 确保没有输出缓冲干扰
        if (ob_get_level() > 0) {
            ob_clean();
        }
        
        header('Content-Type: application/json; charset=utf-8');
        
        if ($sanitize) {
            $data = self::sanitizeJsonData($data);
        }
        
        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        
        // 检查 JSON 编码是否成功
        if ($json === false) {
            $error = json_last_error_msg();
            error_log('JSON编码错误: ' . $error);
            $data = [
                'success' => false,
                'message' => '服务器响应格式错误',
                'error' => $error
            ];
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        
        echo $json;
        exit;
    }
} 