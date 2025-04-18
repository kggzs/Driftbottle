<?php
/**
 * Admin.php - 管理员相关功能类
 * 
 * 负责管理员认证、权限管理等功能
 */

class Admin {
    private $conn;
    private $adminData = null;
    
    /**
     * 构造函数
     */
    public function __construct() {
        $this->conn = getDbConnection();
        $this->initSession();
        $this->loadAdminData();
    }
    
    /**
     * 初始化会话
     */
    private function initSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * 加载管理员数据
     */
    private function loadAdminData() {
        if ($this->isLoggedIn()) {
            $stmt = $this->conn->prepare("SELECT a.*, r.name as role_name, r.permissions 
                                         FROM admins a 
                                         LEFT JOIN admin_roles r ON a.role_id = r.id 
                                         WHERE a.id = ? AND a.status = 1");
            if ($stmt === false) {
                error_log("准备加载管理员数据查询失败: " . $this->conn->error);
                return;
            }
            $adminId = $_SESSION['admin_id'];
            $stmt->bind_param("i", $adminId);
            if (!$stmt->execute()) {
                error_log("执行加载管理员数据查询失败: " . $stmt->error);
                return;
            }
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $this->adminData = $result->fetch_assoc();
            } else {
                $this->logout(); // 管理员不存在或已禁用，强制登出
            }
            $stmt->close();
        }
    }
    
    /**
     * 管理员登录
     * 
     * @param string $username 用户名
     * @param string $password 密码
     * @return bool 是否登录成功
     */
    public function login($username, $password) {
        $username = sanitizeInput($username);
        
        // 查询管理员信息
        $stmt = $this->conn->prepare("SELECT id, username, password, status FROM admins WHERE username = ?");
        if ($stmt === false) {
            error_log("准备管理员登录查询失败: " . $this->conn->error);
            return [
                'success' => false,
                'message' => '系统错误，请稍后重试'
            ];
        }
        $stmt->bind_param("s", $username);
        if (!$stmt->execute()) {
            error_log("执行管理员登录查询失败: " . $stmt->error);
            return [
                'success' => false,
                'message' => '系统错误，请稍后重试'
            ];
        }
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            // 验证密码
            if (password_verify($password, $admin['password'])) {
                // 验证账号状态
                if ($admin['status'] != 1) {
                    return [
                        'success' => false,
                        'message' => '账号已被禁用，请联系超级管理员'
                    ];
                }
                
                // 记录登录信息
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_login_time'] = time();
                
                // 更新最后登录时间和IP
                $ip = $_SERVER['REMOTE_ADDR'];
                $updateStmt = $this->conn->prepare("UPDATE admins SET last_login_time = NOW(), last_login_ip = ? WHERE id = ?");
                if ($updateStmt === false) {
                    error_log("准备更新登录信息查询失败: " . $this->conn->error);
                } else {
                    $updateStmt->bind_param("si", $ip, $admin['id']);
                    if (!$updateStmt->execute()) {
                        error_log("执行更新登录信息查询失败: " . $updateStmt->error);
                    }
                    $updateStmt->close();
                }
                
                // 记录登录日志
                $this->logLogin($admin['id'], 1);
                
                return [
                    'success' => true,
                    'message' => '登录成功'
                ];
            }
        }
        
        // 记录失败登录
        $this->logLogin(0, 0, $username);
        
        return [
            'success' => false,
            'message' => '用户名或密码错误'
        ];
    }
    
    /**
     * 记录登录日志
     */
    private function logLogin($adminId, $status, $username = '') {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        try {
            if ($status == 1 && $adminId > 0) {
                $stmt = $this->conn->prepare("INSERT INTO admin_login_logs (admin_id, login_ip, user_agent, status) VALUES (?, ?, ?, ?)");
                if ($stmt === false) {
                    throw new Exception("准备记录登录日志查询失败: " . $this->conn->error);
                }
                $stmt->bind_param("issi", $adminId, $ip, $userAgent, $status);
            } else {
                // 记录失败登录，用描述字段记录尝试的用户名
                $stmt = $this->conn->prepare("INSERT INTO admin_login_logs (admin_id, login_ip, user_agent, status) VALUES (0, ?, ?, 0)");
                if ($stmt === false) {
                    throw new Exception("准备记录登录日志查询失败: " . $this->conn->error);
                }
                $stmt->bind_param("ss", $ip, $userAgent);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("执行记录登录日志查询失败: " . $stmt->error);
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log("记录登录日志失败: " . $e->getMessage());
        }
    }
    
    /**
     * 记录操作日志
     */
    public function logOperation($module, $action, $description = '', $data = null) {
        if (!$this->isLoggedIn()) return false;
        
        try {
            $adminId = $_SESSION['admin_id'];
            $ip = $_SERVER['REMOTE_ADDR'];
            $jsonData = $data ? json_encode($data, JSON_UNESCAPED_UNICODE) : null;
            
            $stmt = $this->conn->prepare("INSERT INTO admin_operation_logs (admin_id, module, action, ip, description, data) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt === false) {
                throw new Exception("准备记录操作日志查询失败: " . $this->conn->error);
            }
            $stmt->bind_param("isssss", $adminId, $module, $action, $ip, $description, $jsonData);
            $result = $stmt->execute();
            if (!$result) {
                throw new Exception("执行记录操作日志查询失败: " . $stmt->error);
            }
            $stmt->close();
            
            return $result;
        } catch (Exception $e) {
            error_log("记录操作日志失败: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 管理员登出
     */
    public function logout() {
        // 记录操作日志
        if ($this->isLoggedIn()) {
            $this->logOperation('系统', '登出', '管理员登出系统');
        }
        
        // 清除会话
        unset($_SESSION['admin_id']);
        unset($_SESSION['admin_username']);
        unset($_SESSION['admin_login_time']);
        
        // 销毁会话
        session_destroy();
        
        return true;
    }
    
    /**
     * 检查管理员是否已登录
     * 
     * @return bool 是否已登录
     */
    public function isLoggedIn() {
        return isset($_SESSION['admin_id']);
    }
    
    /**
     * 获取当前登录的管理员ID
     * 
     * @return int 管理员ID
     */
    public function getCurrentAdminId() {
        return $this->isLoggedIn() ? $_SESSION['admin_id'] : 0;
    }
    
    /**
     * 获取当前登录的管理员用户名
     * 
     * @return string 管理员用户名
     */
    public function getCurrentAdminUsername() {
        return $this->isLoggedIn() ? $_SESSION['admin_username'] : '';
    }
    
    /**
     * 获取当前管理员信息
     * 
     * @return array 管理员信息
     */
    public function getCurrentAdmin() {
        // 强制重新加载管理员数据，确保数据是最新的
        $this->loadAdminData();
        return $this->adminData;
    }
    
    /**
     * 检查是否有指定权限
     * 
     * @param string $permission 权限名称
     * @return bool 是否有权限
     */
    public function hasPermission($permission) {
        if (!$this->isLoggedIn() || !$this->adminData) {
            return false;
        }
        
        // 解析权限JSON
        $permissions = json_decode($this->adminData['permissions'], true);
        
        // 超级管理员拥有所有权限
        if (isset($permissions['all']) && $permissions['all'] === true) {
            return true;
        }
        
        // 检查具体权限
        return isset($permissions[$permission]) && $permissions[$permission] === true;
    }
    
    /**
     * 修改管理员密码
     * 
     * @param string $oldPassword 旧密码
     * @param string $newPassword 新密码
     * @return array 修改结果
     */
    public function changePassword($oldPassword, $newPassword) {
        if (!$this->isLoggedIn()) {
            return [
                'success' => false,
                'message' => '请先登录'
            ];
        }
        
        // 验证旧密码
        $stmt = $this->conn->prepare("SELECT password FROM admins WHERE id = ?");
        $adminId = $this->getCurrentAdminId();
        $stmt->bind_param("i", $adminId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            
            if (!password_verify($oldPassword, $admin['password'])) {
                return [
                    'success' => false,
                    'message' => '旧密码不正确'
                ];
            }
            
            // 更新密码
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $this->conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $updateStmt->bind_param("si", $hashedPassword, $adminId);
            $updateResult = $updateStmt->execute();
            $updateStmt->close();
            
            if ($updateResult) {
                // 记录操作日志
                $this->logOperation('系统', '修改密码', '管理员修改密码');
                
                return [
                    'success' => true,
                    'message' => '密码修改成功'
                ];
            }
        }
        
        return [
            'success' => false,
            'message' => '密码修改失败'
        ];
    }
}
?> 