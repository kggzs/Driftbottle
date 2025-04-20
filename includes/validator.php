<?php
/**
 * 输入验证类
 * 提供高级的数据验证功能
 */
class Validator {
    /**
     * 验证用户名
     * @param string $username 用户名
     * @return array 验证结果
     */
    public static function validateUsername($username) {
        $result = [
            'valid' => true,
            'message' => ''
        ];
        
        // 检查长度
        if (mb_strlen($username, 'UTF-8') < 3 || mb_strlen($username, 'UTF-8') > 20) {
            $result['valid'] = false;
            $result['message'] = '用户名长度必须在3-20个字符之间';
            return $result;
        }
        
        // 检查格式：只允许字母、数字、下划线和中文
        if (!preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]+$/u', $username)) {
            $result['valid'] = false;
            $result['message'] = '用户名只能包含字母、数字、下划线和中文';
            return $result;
        }
        
        // 检查是否包含敏感词
        $sensitiveWords = ['admin', 'administrator', 'root', 'system', 'moderator'];
        foreach ($sensitiveWords as $word) {
            if (stripos($username, $word) !== false) {
                $result['valid'] = false;
                $result['message'] = '用户名包含系统保留词';
                return $result;
            }
        }
        
        return $result;
    }
    
    /**
     * 验证密码强度
     * @param string $password 密码
     * @return array 验证结果
     */
    public static function validatePassword($password) {
        $result = [
            'valid' => true,
            'message' => '',
            'strength' => 0 // 0-4 表示密码强度，0最弱，4最强
        ];
        
        // 检查长度
        if (strlen($password) < 6) {
            $result['valid'] = false;
            $result['message'] = '密码长度至少为6个字符';
            return $result;
        }
        
        // 计算强度
        $strength = 0;
        
        // 包含小写字母
        if (preg_match('/[a-z]/', $password)) {
            $strength++;
        }
        
        // 包含大写字母
        if (preg_match('/[A-Z]/', $password)) {
            $strength++;
        }
        
        // 包含数字
        if (preg_match('/[0-9]/', $password)) {
            $strength++;
        }
        
        // 包含特殊字符
        if (preg_match('/[^a-zA-Z0-9]/', $password)) {
            $strength++;
        }
        
        $result['strength'] = $strength;
        
        // 根据安全策略，可以要求最低强度
        if ($strength < 2) {
            $result['message'] = '密码强度较弱，建议使用字母、数字和特殊字符的组合';
        }
        
        return $result;
    }
    
    /**
     * 验证URL
     * @param string $url URL
     * @return array 验证结果
     */
    public static function validateUrl($url) {
        $result = [
            'valid' => true,
            'message' => ''
        ];
        
        // 首先进行清洁
        $url = sanitizeUrl($url);
        
        // 如果清洁后是空字符串，则表示URL是恶意的
        if (empty($url)) {
            $result['valid'] = false;
            $result['message'] = 'URL格式不正确或包含危险协议';
            return $result;
        }
        
        // 使用PHP内置函数验证URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            $result['valid'] = false;
            $result['message'] = 'URL格式不正确';
            return $result;
        }
        
        return $result;
    }
    
    /**
     * 验证电子邮件
     * @param string $email 电子邮件
     * @return array 验证结果
     */
    public static function validateEmail($email) {
        $result = [
            'valid' => true,
            'message' => ''
        ];
        
        // 使用PHP内置函数验证电子邮件
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result['valid'] = false;
            $result['message'] = '电子邮件格式不正确';
            return $result;
        }
        
        // 检查域名是否有效
        $domain = substr(strrchr($email, "@"), 1);
        if (!checkdnsrr($domain, "MX") && !checkdnsrr($domain, "A")) {
            $result['valid'] = false;
            $result['message'] = '电子邮件域名无效';
            return $result;
        }
        
        // 检查是否是临时邮箱域名
        $disposableDomains = ['10minutemail.com', 'mailinator.com', 'guerrillamail.com', 'temp-mail.org'];
        foreach ($disposableDomains as $disposableDomain) {
            if (stripos($domain, $disposableDomain) !== false) {
                $result['valid'] = false;
                $result['message'] = '不允许使用临时邮箱';
                return $result;
            }
        }
        
        return $result;
    }
    
    /**
     * 验证纯文本内容
     * @param string $text 文本内容
     * @param int $minLength 最小长度
     * @param int $maxLength 最大长度
     * @return array 验证结果
     */
    public static function validateText($text, $minLength = 0, $maxLength = 0) {
        $result = [
            'valid' => true,
            'message' => ''
        ];
        
        // 检查是否为字符串
        if (!is_string($text)) {
            $result['valid'] = false;
            $result['message'] = '输入必须是文本';
            return $result;
        }
        
        // 净化文本
        $text = sanitizeInput($text);
        
        // 检查最小长度
        if ($minLength > 0 && mb_strlen($text, 'UTF-8') < $minLength) {
            $result['valid'] = false;
            $result['message'] = "文本长度必须至少为 $minLength 个字符";
            return $result;
        }
        
        // 检查最大长度
        if ($maxLength > 0 && mb_strlen($text, 'UTF-8') > $maxLength) {
            $result['valid'] = false;
            $result['message'] = "文本长度不能超过 $maxLength 个字符";
            return $result;
        }
        
        return $result;
    }
    
    /**
     * 验证富文本内容
     * @param string $html HTML内容
     * @param int $maxLength 最大长度
     * @return array 验证结果
     */
    public static function validateHtml($html, $maxLength = 0) {
        $result = [
            'valid' => true,
            'message' => '',
            'sanitized' => ''
        ];
        
        // 检查是否为字符串
        if (!is_string($html)) {
            $result['valid'] = false;
            $result['message'] = '输入必须是文本';
            return $result;
        }
        
        // 使用高级净化
        if (class_exists('Security')) {
            $sanitized = Security::purifyHtml($html);
        } else {
            $sanitized = sanitizeHtml($html);
        }
        
        // 检查是否包含恶意内容
        if (class_exists('Security') && Security::detectMaliciousInput($html)) {
            $result['valid'] = false;
            $result['message'] = '内容包含潜在的危险代码';
            return $result;
        }
        
        // 检查最大长度（不含HTML标签）
        if ($maxLength > 0) {
            $plainText = strip_tags($sanitized);
            if (mb_strlen($plainText, 'UTF-8') > $maxLength) {
                $result['valid'] = false;
                $result['message'] = "内容长度不能超过 $maxLength 个字符";
                return $result;
            }
        }
        
        $result['sanitized'] = $sanitized;
        return $result;
    }
}
?> 