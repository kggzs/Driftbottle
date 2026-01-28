<?php
// 开启输出缓冲，防止任何意外输出
ob_start();

// 禁用错误显示，但保留错误日志
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'includes/config.php';
require_once 'includes/user.php';
require_once 'includes/bottle.php';
require_once 'includes/ip_location.php';
require_once 'includes/security.php';
require_once 'includes/validator.php';
require_once 'includes/report.php';

// 初始化安全中间件
Security::init();

// 清除输出缓冲区中的任何内容
ob_clean();

// 设置头信息为JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// 获取请求路径
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// 获取endpoint
$endpoint = basename($requestUri);

// 检查是否是 api.php/endpoint 格式的请求
if (strpos($requestUri, 'api.php/') !== false) {
    // 提取 api.php/ 后面的部分作为 endpoint
    $pathParts = explode('api.php/', $requestUri);
    if (count($pathParts) > 1) {
        $endpoint = trim($pathParts[1], '/');
        // 如果有多个斜杠，只取第一个部分作为 endpoint
        if (strpos($endpoint, '/') !== false) {
            $endpoint = explode('/', $endpoint)[0];
        }
    }
}

// 如果是通过 action 参数传递的请求，使用 action 参数作为 endpoint
if (isset($_GET['action'])) {
    $endpoint = $_GET['action'];
}

// 获取请求参数
$data = json_decode(file_get_contents('php://input'), true);
if ($data === null) {
    $data = array_merge($_GET, $_POST);
}

// 对所有输入参数进行基本的恶意检测
$maliciousInput = false;
foreach ($data as $key => $value) {
    if (is_string($value) && Security::detectMaliciousInput($value)) {
        $maliciousInput = true;
        // 记录攻击尝试
        $ipAddress = getClientIpAddress();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $requestData = json_encode($data);
        error_log("检测到可能的攻击尝试 - IP: $ipAddress, UA: $userAgent, 数据: $requestData");
        break;
    }
}

if ($maliciousInput) {
    http_response_code(403);
    Security::outputJson(['success' => false, 'message' => '检测到可能的恶意输入']);
    exit();
}

// 跨域预检请求处理
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

$response = [];

// 处理不同的API端点
switch ($endpoint) {
    case 'register':
        if ($requestMethod === 'POST') {
            // 定义验证规则
            $rules = [
                'username' => [
                    'required' => true,
                    'filter' => 'string',
                    'min_length' => 3,
                    'max_length' => 20,
                    'pattern' => '/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]+$/u',
                    'message' => '用户名必须是3-20个字符，只能包含字母、数字、下划线和中文'
                ],
                'password' => [
                    'required' => true,
                    'filter' => 'string',
                    'min_length' => 6,
                    'message' => '密码长度至少为6个字符'
                ],
                'gender' => [
                    'required' => true,
                    'filter' => 'string',
                    'message' => '性别必须是男或女'
                ]
            ];
            
            // 验证输入
            $validation = Security::validateInput($data, $rules);
            
            if (!$validation['valid']) {
                $response = ['success' => false, 'message' => array_values($validation['errors'])[0]];
            } else {
                $username = $validation['data']['username'];
                $password = $validation['data']['password'];
                $gender = $validation['data']['gender'];
                
                if ($gender !== '男' && $gender !== '女') {
                    $response = ['success' => false, 'message' => '性别必须是男或女'];
                } else {
                    $response = registerUser($username, $password, $gender);
                }
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'login':
        if ($requestMethod === 'POST') {
            // 定义验证规则
            $rules = [
                'username' => [
                    'required' => true,
                    'filter' => 'string',
                    'message' => '用户名不能为空'
                ],
                'password' => [
                    'required' => true,
                    'filter' => 'string',
                    'message' => '密码不能为空'
                ]
            ];
            
            // 验证输入
            $validation = Security::validateInput($data, $rules);
            
            if (!$validation['valid']) {
                $response = ['success' => false, 'message' => array_values($validation['errors'])[0]];
            } else {
                $username = $validation['data']['username'];
                $password = $validation['data']['password'];
                $response = loginUser($username, $password);
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'logout':
        if ($requestMethod === 'POST' || $requestMethod === 'GET') {
            logoutUser();
            $response = ['success' => true];
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'check_auth':
        $response = ['success' => true, 'loggedIn' => isLoggedIn()];
        if (isLoggedIn()) {
            $response['user_id'] = $_SESSION['user_id'];
            $response['username'] = $_SESSION['username'];
            $response['gender'] = $_SESSION['gender'];
            $response['points'] = $_SESSION['points'] ?? 0;
            $response['experience'] = $_SESSION['experience'] ?? 0;
            $response['level'] = $_SESSION['level'] ?? 1;
            $response['signature'] = $_SESSION['signature'] ?? '';
            $response['is_vip'] = $_SESSION['is_vip'] ?? 0;
            $response['vip_level'] = $_SESSION['vip_level'] ?? 0;
            
            // 获取用户今日限制
            $limits = getUserDailyLimits(getCurrentUserId());
            $response['daily_limits'] = $limits;
            
            // 获取未读消息数
            $unreadMessages = getUnreadMessageCount(getCurrentUserId());
            $response['unread_messages'] = $unreadMessages['count'];
        }
        break;
        
    case 'upload_audio':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else if ($requestMethod === 'POST') {
            // 处理音频文件上传
            if (!isset($_FILES['audio']) || $_FILES['audio']['error'] !== UPLOAD_ERR_OK) {
                $response = ['success' => false, 'message' => '音频文件上传失败'];
            } else {
                // 检查文件类型
                $allowedTypes = ['audio/webm', 'audio/mpeg', 'audio/mp3', 'audio/wav', 'audio/ogg'];
                $fileType = $_FILES['audio']['type'];
                if (!in_array($fileType, $allowedTypes)) {
                    $response = ['success' => false, 'message' => '不支持的音频格式，请使用webm、mp3、wav或ogg格式'];
                } else {
                    // 检查文件大小（限制为5MB）
                    $maxSize = 5 * 1024 * 1024; // 5MB
                    if ($_FILES['audio']['size'] > $maxSize) {
                        $response = ['success' => false, 'message' => '音频文件大小不能超过5MB'];
                    } else {
                        // 创建上传目录
                        $uploadDir = __DIR__ . '/uploads/audio/';
                        if (!file_exists($uploadDir)) {
                            mkdir($uploadDir, 0755, true);
                        }
                        
                        // 生成唯一文件名
                        $fileName = 'voice_' . getCurrentUserId() . '_' . time() . '_' . uniqid() . '.webm';
                        $filePath = $uploadDir . $fileName;
                        
                        // 移动文件
                        if (move_uploaded_file($_FILES['audio']['tmp_name'], $filePath)) {
                            // 获取音频时长（简单估算，实际可能需要使用音频处理库）
                            $audioDuration = 0;
                            // 这里可以添加音频时长检测逻辑
                            // 注意：实际项目中可以使用getid3库或其他音频处理库来获取准确的时长
                            
                            // 返回相对路径，前端可以直接使用
                            $response = [
                                'success' => true,
                                'audio_file' => 'uploads/audio/' . $fileName,
                                'audio_duration' => $audioDuration
                            ];
                        } else {
                            $response = ['success' => false, 'message' => '文件保存失败'];
                        }
                    }
                }
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'create_bottle':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else if ($requestMethod === 'POST') {
            $bottleType = isset($data['bottle_type']) ? $data['bottle_type'] : 'text';
            
            // 根据漂流瓶类型定义不同的验证规则
            if ($bottleType === 'voice') {
                // 语音漂流瓶验证规则
                $rules = [
                    'audio_file' => [
                        'required' => true,
                        'filter' => 'string',
                        'message' => '音频文件不能为空'
                    ],
                    'is_anonymous' => [
                        'filter' => 'int'
                    ],
                    'mood' => [
                        'filter' => 'string'
                    ]
                ];
            } else {
                // 文字漂流瓶验证规则
                $rules = [
                    'content' => [
                        'required' => true,
                        'filter' => 'html',
                        'max_length' => MAX_BOTTLE_LENGTH,
                        'message' => '漂流瓶内容不能为空且不能超过' . MAX_BOTTLE_LENGTH . '字符'
                    ],
                    'is_anonymous' => [
                        'filter' => 'int'
                    ],
                    'mood' => [
                        'filter' => 'string'
                    ]
                ];
            }
            
            // 验证输入
            $validation = Security::validateInput($data, $rules);
            
            if (!$validation['valid']) {
                $response = ['success' => false, 'message' => array_values($validation['errors'])[0]];
            } else {
                $content = isset($validation['data']['content']) ? $validation['data']['content'] : '';
                $isAnonymous = isset($validation['data']['is_anonymous']) ? (int)$validation['data']['is_anonymous'] : 0;
                $mood = $validation['data']['mood'] ?? '其他';
                $audioFile = isset($validation['data']['audio_file']) ? $validation['data']['audio_file'] : null;
                $audioDuration = isset($data['audio_duration']) ? (int)$data['audio_duration'] : 0;
                
                $response = createBottle(getCurrentUserId(), $content, $isAnonymous, $mood, $bottleType, $audioFile, $audioDuration);
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'pick_bottle':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else if ($requestMethod === 'POST' || $requestMethod === 'GET') {
            $response = pickRandomBottle(getCurrentUserId());
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'comment_bottle':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else if ($requestMethod === 'POST') {
            // 定义验证规则
            $rules = [
                'bottle_id' => [
                    'required' => true,
                    'filter' => 'int',
                    'message' => '漂流瓶ID不能为空'
                ],
                'content' => [
                    'required' => true,
                    'filter' => 'html',
                    'max_length' => MAX_COMMENT_LENGTH,
                    'message' => '评论内容不能为空且不能超过' . MAX_COMMENT_LENGTH . '字符'
                ]
            ];
            
            // 验证输入
            $validation = Security::validateInput($data, $rules);
            
            if (!$validation['valid']) {
                $response = ['success' => false, 'message' => array_values($validation['errors'])[0]];
            } else {
                $bottleId = $validation['data']['bottle_id'];
                $content = $validation['data']['content'];
                $parentId = isset($data['parent_id']) && $data['parent_id'] ? (int)$data['parent_id'] : null;
                $replyToUserId = isset($data['reply_to_user_id']) && $data['reply_to_user_id'] ? (int)$data['reply_to_user_id'] : null;
                $throwBack = isset($data['throw_back']) ? (bool)$data['throw_back'] : true; // 默认为true，保持向后兼容
                
                $response = commentAndThrowBottle($bottleId, getCurrentUserId(), $content, $parentId, $replyToUserId, $throwBack);
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'like_bottle':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else if ($requestMethod === 'POST') {
            $bottleId = (int)($data['bottle_id'] ?? 0);
            
            if ($bottleId <= 0) {
                $response = ['success' => false, 'message' => '漂流瓶ID不能为空'];
            } else {
                $response = likeBottle($bottleId, getCurrentUserId());
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'user_info':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else {
            $userId = (int)($data['user_id'] ?? getCurrentUserId());
            $response = getUserInfo($userId);
        }
        break;
        
    case 'user_bottles':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else {
            $userId = (int)($data['user_id'] ?? getCurrentUserId());
            $response = getUserBottles($userId);
        }
        break;
        
    case 'user_picked_bottles':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else {
            $response = getUserPickedBottles(getCurrentUserId());
        }
        break;
        
    case 'user_checkin':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else if ($requestMethod === 'POST') {
            try {
                // 添加额外的响应头防止缓存
                header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
                header('Pragma: no-cache');
                header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
                
                // 记录签到开始和基本信息
                error_log('------- 签到请求开始 -------');
                error_log('用户ID: ' . getCurrentUserId());
                error_log('请求时间: ' . date('Y-m-d H:i:s'));
                error_log('客户端IP: ' . $_SERVER['REMOTE_ADDR']);
                error_log('用户代理: ' . ($_SERVER['HTTP_USER_AGENT'] ?? '未知'));
                
                // 设置执行时间限制以防止超时
                set_time_limit(30);
                
                // 处理签到请求
                error_log('调用userCheckin函数...');
                $response = userCheckin(getCurrentUserId());
                error_log('userCheckin函数调用完成，结果: ' . ($response['success'] ? '成功' : '失败'));
                
                // 记录签到结果详情
                error_log('响应内容: ' . json_encode($response));
                error_log('------- 签到请求结束 -------');
            } catch (Exception $e) {
                // 记录异常详细信息
                error_log('------- 签到异常 -------');
                error_log('异常信息: ' . $e->getMessage());
                error_log('异常文件: ' . $e->getFile() . ' 行: ' . $e->getLine());
                error_log('异常堆栈: ' . $e->getTraceAsString());
                
                $response = [
                    'success' => false, 
                    'message' => '签到处理异常，请稍后再试',
                    'error' => DEBUG_MODE ? $e->getMessage() : null
                ];
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'get_checkin_status':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else {
            $response = getUserCheckinStatus(getCurrentUserId());
        }
        break;
        
    case 'update_signature':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else if ($requestMethod === 'POST') {
            // 定义验证规则
            $rules = [
                'signature' => [
                    'required' => true,
                    'filter' => 'string',
                    'max_length' => MAX_SIGNATURE_LENGTH,
                    'message' => '个性签名不能为空且不能超过' . MAX_SIGNATURE_LENGTH . '字符'
                ]
            ];
            
            // 验证输入
            $validation = Security::validateInput($data, $rules);
            
            if (!$validation['valid']) {
                $response = ['success' => false, 'message' => array_values($validation['errors'])[0]];
            } else {
                $signature = $validation['data']['signature'];
                $response = updateUserSignature(getCurrentUserId(), $signature);
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'get_messages':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else {
            $response = getUserMessages(getCurrentUserId());
        }
        break;
        
    case 'mark_message_read':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else if ($requestMethod === 'POST') {
            $messageId = (int)($data['message_id'] ?? 0);
            
            if ($messageId <= 0) {
                $response = ['success' => false, 'message' => '消息ID不能为空'];
            } else {
                $response = markMessageAsRead($messageId, getCurrentUserId());
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'delete_message':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else if ($requestMethod === 'POST') {
            $messageId = (int)($data['message_id'] ?? 0);
            
            if ($messageId <= 0) {
                $response = ['success' => false, 'message' => '消息ID不能为空'];
            } else {
                $response = deleteMessage($messageId, getCurrentUserId());
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'get_unread_message_count':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else {
            $response = getUnreadMessageCount(getCurrentUserId());
        }
        break;
        
    case 'get_daily_limits':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else {
            $limits = getUserDailyLimits(getCurrentUserId());
        $isVip = $limits['is_vip'];
        $pickLimit = $isVip ? VIP_DAILY_PICK_LIMIT : DAILY_PICK_LIMIT; // 使用配置常量
        $throwLimit = $isVip ? VIP_DAILY_BOTTLE_LIMIT : DAILY_BOTTLE_LIMIT;
        
        $response = [
            'success' => true, 
            'limits' => $limits,
            'throw_remaining' => $throwLimit - $limits['throw_count'],
            'pick_remaining' => $pickLimit - $limits['pick_count'],
            'free_throws_remaining' => $limits['standard_free_throws_remaining'],
            'is_vip' => $limits['is_vip'],
            'vip_free_throws_remaining' => $limits['vip_free_throws_remaining']
        ];
        }
        break;
        
    case 'check_vip_status':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else {
            $vipStatus = isUserVip(getCurrentUserId());
            $response = [
                'success' => true,
                'is_vip' => $vipStatus['is_vip'],
                'vip_expire_date' => $vipStatus['vip_expire_date'],
                'vip_level' => $_SESSION['vip_level'] ?? 0
            ];
        }
        break;
        
    case 'upgrade_vip':
        // 升级VIP状态
        if (!isset($_SESSION['user_id'])) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else if ($requestMethod === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $months = isset($data['months']) ? intval($data['months']) : 0;
            $level = isset($data['level']) ? intval($data['level']) : 1;
            
            if ($months <= 0) {
                $response = ['success' => false, 'message' => '请选择有效的VIP时长'];
            } else {
                // 计算所需积分
                $pointsNeeded = 0;
                switch ($months) {
                    case 1:
                        $pointsNeeded = VIP_POINTS_1_MONTH;
                        break;
                    case 3:
                        $pointsNeeded = VIP_POINTS_3_MONTHS;
                        break;
                    case 6:
                        $pointsNeeded = VIP_POINTS_6_MONTHS;
                        break;
                    case 12:
                        $pointsNeeded = VIP_POINTS_12_MONTHS;
                        break;
                    default:
                        $pointsNeeded = $months * VIP_POINTS_1_MONTH;
                }
                
                // 检查用户积分是否足够
                $userPoints = getUserPoints($_SESSION['user_id']);
                
                if ($userPoints < $pointsNeeded) {
                    $response = [
                        'success' => false, 
                        'message' => "积分不足，开通{$months}个月VIP需要{$pointsNeeded}积分，您当前积分为{$userPoints}"
                    ];
                } else {
                    // 扣除积分
                    updateUserPoints($_SESSION['user_id'], -$pointsNeeded, "开通{$months}个月VIP");
                    
                    // 设置用户VIP状态
                    $vipResult = setUserVip($_SESSION['user_id'], $months, $level);
                    
                    // 获取VIP到期时间
                    $userInfo = getUserInfo($_SESSION['user_id']);
                    
                    // 更新会话中的VIP信息
                    $_SESSION['is_vip'] = 1;
                    $_SESSION['vip_level'] = $level;
                    $_SESSION['vip_expire_date'] = $userInfo['user']['vip_expire_date'];
                    $_SESSION['points'] = $userInfo['user']['points'];
                    
                    $response = [
                        'success' => true, 
                        'message' => "恭喜，您已成功开通{$months}个月VIP会员！已扣除{$pointsNeeded}积分",
                        'vip_expire_date' => $userInfo['user']['vip_expire_date'],
                        'points_deducted' => $pointsNeeded,
                        'remaining_points' => $_SESSION['points']
                    ];
                }
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'get_location_preview':
        if ($requestMethod === 'GET') {
            try {
                // 获取客户端IP
                $ip = $_SERVER['REMOTE_ADDR'];
                
                // 如果是本地开发环境，使用测试IP
                if ($ip === '127.0.0.1' || $ip === '::1') {
                    $ip = '8.8.8.8'; // 使用Google DNS作为测试IP
                }
                
                // 获取位置信息
                $location = getLocationByIp($ip);
                
                if ($location === null) {
                    throw new Exception('无法获取位置信息');
                }
                
                $response = [
                    'success' => true,
                    'ip_address' => $ip,
                    'location' => $location
                ];
            } catch (Exception $e) {
                error_log('获取位置信息失败: ' . $e->getMessage());
                $response = [
                    'success' => false,
                    'message' => '获取位置信息失败：' . $e->getMessage()
                ];
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'public_profile':
        // 获取用户公开资料（用于在个人主页查看其他用户信息）
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else {
            $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
            
            if ($userId <= 0) {
                $response = ['success' => false, 'message' => '无效的用户ID'];
            } else {
                // 获取用户公开信息
                $userData = getUserInfo($userId);
                
                if ($userData['success']) {
                    // 只返回公开信息
                    $publicInfo = [
                        'id' => $userData['user']['id'],
                        'username' => $userData['user']['username'],
                        'gender' => $userData['user']['gender'],
                        'signature' => $userData['user']['signature'],
                        'is_vip' => $userData['user']['is_vip'],
                        'bottle_count' => $userData['user']['bottle_count'],
                        'like_count' => $userData['user']['like_count'],
                        'created_at' => $userData['user']['created_at'],
                        'experience' => $userData['user']['experience'] ?? 0,
                        'level' => $userData['user']['level'] ?? 1,
                        'next_level_exp' => $userData['user']['next_level_exp'] ?? 0,
                        'current_level_exp' => $userData['user']['current_level_exp'] ?? 0,
                        'exp_progress' => $userData['user']['exp_progress'] ?? 0
                    ];
                    
                    $response = ['success' => true, 'user' => $publicInfo];
                } else {
                    $response = $userData; // 直接传递错误信息
                }
            }
        }
        break;
        
    case 'public_bottles':
        // 获取用户公开漂流瓶（仅显示非匿名的）
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else {
            $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
            
            if ($userId <= 0) {
                $response = ['success' => false, 'message' => '无效的用户ID'];
            } else {
                $conn = getDbConnection();
                
                // 只查询非匿名的漂流瓶
                $stmt = $conn->prepare("
                    SELECT b.id, b.content, b.mood, b.likes, b.throw_time, b.status, 
                           u.signature
                    FROM bottles b
                    JOIN users u ON b.user_id = u.id
                    WHERE b.user_id = ? AND b.is_anonymous = 0
                    ORDER BY b.throw_time DESC
                    LIMIT 20
                ");
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $bottles = [];
                while ($row = $result->fetch_assoc()) {
                    $bottles[] = $row;
                }
                
                $stmt->close();
                $conn->close();
                
                $response = ['success' => true, 'bottles' => $bottles];
            }
        }
        break;
        
    case 'get_system_config':
        // 获取系统配置常量
        $response = [
            'success' => true,
            'POINTS_PER_BOTTLE' => POINTS_PER_BOTTLE,
            'DAILY_BOTTLE_LIMIT' => DAILY_BOTTLE_LIMIT,
            'VIP_DAILY_BOTTLE_LIMIT' => VIP_DAILY_BOTTLE_LIMIT,
            'DAILY_PICK_LIMIT' => DAILY_PICK_LIMIT,
            'VIP_DAILY_PICK_LIMIT' => VIP_DAILY_PICK_LIMIT,
            'MAX_BOTTLE_LENGTH' => MAX_BOTTLE_LENGTH,
            'MAX_COMMENT_LENGTH' => MAX_COMMENT_LENGTH
        ];
        break;
        
    case 'get_debug_mode':
        $response = [
            'success' => true,
            'debug_mode' => DEBUG_MODE
        ];
        break;
        
    case 'get_points_config':
        // 返回系统中的积分配置信息
        $response = [
            'success' => true,
            'config' => [
                'POINTS_PER_CHECKIN' => POINTS_PER_CHECKIN,
                'POINTS_PER_WEEKLY_CHECKIN' => POINTS_PER_WEEKLY_CHECKIN,
                'POINTS_PER_VIP_CHECKIN' => POINTS_PER_VIP_CHECKIN,
                'POINTS_PER_BOTTLE' => POINTS_PER_BOTTLE,
                'POINTS_PER_LIKE' => POINTS_PER_LIKE
            ]
        ];
        break;
        
    case 'get_experience_config':
        // 返回系统中的经验值配置信息
        $response = [
            'success' => true,
            'config' => [
                'EXP_PER_BOTTLE' => EXP_PER_BOTTLE,
                'EXP_PER_PICK' => EXP_PER_PICK,
                'EXP_PER_COMMENT' => EXP_PER_COMMENT
            ]
        ];
        break;
        
    case 'get_user_level_info':
        // 获取用户等级信息
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else {
            require_once 'includes/user.php';
            $userInfo = getUserInfo(getCurrentUserId());
            if ($userInfo['success']) {
                $user = $userInfo['user'];
                $response = [
                    'success' => true,
                    'experience' => $user['experience'] ?? 0,
                    'level' => $user['level'] ?? 1,
                    'next_level_exp' => $user['next_level_exp'] ?? 0,
                    'current_level_exp' => $user['current_level_exp'] ?? 0,
                    'exp_progress' => $user['exp_progress'] ?? 0
                ];
            } else {
                $response = $userInfo;
            }
        }
        break;
        
    case 'get_limits_config':
        // 返回系统中的次数限制配置信息
        $response = [
            'success' => true,
            'config' => [
                'DAILY_BOTTLE_LIMIT' => DAILY_BOTTLE_LIMIT,
                'DAILY_PICK_LIMIT' => DAILY_PICK_LIMIT,
                'VIP_DAILY_BOTTLE_LIMIT' => VIP_DAILY_BOTTLE_LIMIT,
                'VIP_DAILY_PICK_LIMIT' => VIP_DAILY_PICK_LIMIT
            ]
        ];
        break;
        
    case 'get_vip_points_config':
        // 返回VIP会员开通积分配置
        $response = [
            'success' => true,
            'config' => [
                'VIP_POINTS_1_MONTH' => VIP_POINTS_1_MONTH,
                'VIP_POINTS_3_MONTHS' => VIP_POINTS_3_MONTHS,
                'VIP_POINTS_6_MONTHS' => VIP_POINTS_6_MONTHS,
                'VIP_POINTS_12_MONTHS' => VIP_POINTS_12_MONTHS
            ]
        ];
        break;
        
    case 'create_recharge_order':
        // 创建充值订单
        if ($requestMethod === 'POST' && isLoggedIn()) {
            try {
                // 清除任何可能的输出
                if (ob_get_level() > 0) {
                    ob_clean();
                }
                
                // 检查必要的文件是否存在
                $epayConfigFile = __DIR__ . '/includes/payment/lib/epay.config.php';
                $epayCoreFile = __DIR__ . '/includes/payment/lib/EpayCore.class.php';
                
                if (!file_exists($epayConfigFile)) {
                    throw new Exception('支付配置文件不存在');
                }
                if (!file_exists($epayCoreFile)) {
                    throw new Exception('支付核心类文件不存在');
                }
                
                require_once $epayConfigFile;
                require_once $epayCoreFile;
                $amount = isset($data['amount']) ? (float)$data['amount'] : 0;
                $payment_type = isset($data['payment_type']) ? sanitizeInput($data['payment_type']) : 'alipay';
                
                // 验证金额
                if ($amount <= 0 || $amount > 10000) {
                    throw new Exception('充值金额必须在0.01-10000元之间');
                }
                
                // 验证支付方式
                $allowed_types = ['alipay', 'wxpay', 'qqpay', 'bank'];
                if (!in_array($payment_type, $allowed_types)) {
                    throw new Exception('不支持的支付方式');
                }
                
                // 获取积分比例
                if (!function_exists('getPointsRatio')) {
                    throw new Exception('getPointsRatio函数未定义，请检查支付配置文件');
                }
                $points_ratio = getPointsRatio();
                if ($points_ratio <= 0) {
                    throw new Exception('积分比例配置错误，请检查后台设置');
                }
                $points = (int)($amount * $points_ratio);
                
                if ($points <= 0) {
                    throw new Exception('积分计算错误，请检查积分比例配置');
                }
                
                $user_id = getCurrentUserId();
                if (!$user_id) {
                    throw new Exception('用户未登录');
                }
                
                $conn = getDbConnection();
                if (!$conn) {
                    throw new Exception('数据库连接失败');
                }
                
                // 检查表是否存在
                $tableCheck = $conn->query("SHOW TABLES LIKE 'recharge_orders'");
                if (!$tableCheck || $tableCheck->num_rows == 0) {
                    $conn->close();
                    $conn = null; // 标记连接已关闭
                    throw new Exception('充值订单表不存在，请先执行sql/add_recharge_system.sql文件');
                }
                
                $conn->begin_transaction();
                $transactionStarted = true;
                
                // 生成订单号
                $order_no = 'R' . date('YmdHis') . mt_rand(1000, 9999) . $user_id;
                
                // 创建订单
                $stmt = $conn->prepare("INSERT INTO recharge_orders (order_no, user_id, amount, points, points_ratio, payment_type, status) VALUES (?, ?, ?, ?, ?, ?, 0)");
                if (!$stmt) {
                    throw new Exception('准备SQL语句失败: ' . $conn->error);
                }
                $stmt->bind_param("sidiis", $order_no, $user_id, $amount, $points, $points_ratio, $payment_type);
                if (!$stmt->execute()) {
                    throw new Exception('创建订单失败: ' . $stmt->error);
                }
                $stmt->close();
                
                // 获取支付配置
                if (!function_exists('getPaymentConfig')) {
                    throw new Exception('getPaymentConfig函数未定义，请检查支付配置文件');
                }
                $epay_config = getPaymentConfig();
                if (empty($epay_config['pid']) || empty($epay_config['platform_public_key']) || empty($epay_config['merchant_private_key'])) {
                    throw new Exception('支付配置不完整，请在后台系统设置中配置支付参数');
                }
                $epay = new EpayCore($epay_config);
                
                // 获取网站URL（自动检测，避免配置错误）
                $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
                $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
                $scriptPath = dirname($_SERVER['SCRIPT_NAME']);
                $baseUrl = $protocol . $host . ($scriptPath !== '/' ? $scriptPath : '');
                
                // 如果SITE_URL配置了且不是默认值，优先使用配置的URL
                $site_url = SITE_URL;
                if ($site_url === 'http://localhost' || empty($site_url)) {
                    $site_url = $baseUrl;
                }
                
                $notify_url = rtrim($site_url, '/') . '/payment_notify.php';
                $return_url = rtrim($site_url, '/') . '/payment_return.php';
                
                // 构建支付参数
                $pay_params = [
                    'type' => $payment_type,
                    'notify_url' => $notify_url,
                    'return_url' => $return_url,
                    'out_trade_no' => $order_no,
                    'name' => '积分充值',
                    'money' => number_format($amount, 2, '.', '')
                ];
                
                // 生成支付表单
                $pay_html = $epay->pagePay($pay_params);
                
                $conn->commit();
                $conn->close();
                
                // 构建响应数据
                $response = [
                    'success' => true,
                    'order_no' => $order_no,
                    'amount' => $amount,
                    'points' => $points,
                    'pay_html' => $pay_html  // pay_html包含HTML，需要特殊处理
                ];
                
                // 直接输出JSON，不对pay_html进行HTML转义
                if (ob_get_level() > 0) {
                    ob_clean();
                }
                header('Content-Type: application/json; charset=utf-8');
                
                // 手动构建JSON，确保pay_html不被转义
                $jsonData = [
                    'success' => $response['success'],
                    'order_no' => htmlspecialchars($response['order_no'], ENT_QUOTES, 'UTF-8'),
                    'amount' => $response['amount'],
                    'points' => $response['points'],
                    'pay_html' => $response['pay_html']  // 不转义HTML
                ];
                
                echo json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                exit;
                
            } catch (Exception $e) {
                // 只有在连接存在且事务已开始时才回滚
                if (isset($conn) && $conn !== null && isset($transactionStarted) && $transactionStarted) {
                    try {
                        if ($conn instanceof mysqli && $conn->ping()) {
                            $conn->rollback();
                        }
                    } catch (Exception $rollbackError) {
                        error_log("回滚事务失败: " . $rollbackError->getMessage());
                    }
                }
                
                // 关闭连接（如果还存在且未关闭）
                if (isset($conn) && $conn !== null) {
                    try {
                        if ($conn instanceof mysqli && $conn->ping()) {
                            $conn->close();
                        }
                    } catch (Exception $closeError) {
                        // 忽略关闭错误
                    }
                }
                
                error_log("创建充值订单失败: " . $e->getMessage());
                // 清除任何可能的输出
                if (ob_get_level() > 0) {
                    ob_clean();
                }
                $response = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            } catch (Error $e) {
                // 捕获PHP致命错误
                error_log("创建充值订单PHP错误: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
                if (ob_get_level() > 0) {
                    ob_clean();
                }
                $response = [
                    'success' => false,
                    'message' => '系统错误：' . $e->getMessage()
                ];
            }
        } else {
            $response = ['success' => false, 'message' => '请先登录'];
        }
        break;
        
    case 'get_recharge_orders':
        // 获取充值订单列表（用户自己的订单）
        if ($requestMethod === 'GET' && isLoggedIn()) {
            try {
                $user_id = getCurrentUserId();
                $conn = getDbConnection();
                
                $sql = "SELECT * FROM recharge_orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 20";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $orders = [];
                while ($row = $result->fetch_assoc()) {
                    $orders[] = $row;
                }
                
                $stmt->close();
                $conn->close();
                
                $response = [
                    'success' => true,
                    'orders' => $orders
                ];
            } catch (Exception $e) {
                error_log("获取充值订单失败: " . $e->getMessage());
                $response = [
                    'success' => false,
                    'message' => '获取订单失败'
                ];
            }
        } else {
            $response = ['success' => false, 'message' => '请先登录'];
        }
        break;
        
    case 'get_recharge_order_detail':
        // 获取充值订单详情（后台管理员使用）
        if ($requestMethod === 'GET') {
            require_once 'includes/admin.php';
            $admin = new Admin();
            
            if (!$admin->isLoggedIn()) {
                $response = ['success' => false, 'message' => '请先登录'];
                break;
            }
            
            try {
                $order_id = isset($data['order_id']) ? (int)$data['order_id'] : 0;
                
                if ($order_id <= 0) {
                    throw new Exception('订单ID无效');
                }
                
                $conn = getDbConnection();
                $sql = "SELECT ro.*, u.username 
                        FROM recharge_orders ro 
                        LEFT JOIN users u ON ro.user_id = u.id 
                        WHERE ro.id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $order_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $order = $result->fetch_assoc();
                $stmt->close();
                $conn->close();
                
                if ($order) {
                    $response = [
                        'success' => true,
                        'order' => $order
                    ];
                } else {
                    $response = [
                        'success' => false,
                        'message' => '订单不存在'
                    ];
                }
            } catch (Exception $e) {
                error_log("获取订单详情失败: " . $e->getMessage());
                $response = [
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'get_payment_config':
        // 获取支付配置（积分比例、支付方式等）
        require_once 'includes/payment/lib/epay.config.php';
        $points_ratio = getPointsRatio();
        $available_methods = getAvailablePaymentMethods();
        $default_method = getDefaultPaymentMethod();
        $response = [
            'success' => true,
            'points_ratio' => $points_ratio,
            'available_methods' => $available_methods,
            'default_method' => $default_method
        ];
        break;
        
    case 'get_announcements':
        // 获取公告列表，不需要登录即可访问
        if ($requestMethod === 'GET') {
            try {
                $conn = getDbConnection();
                
                $sql = "SELECT a.*, u.username as creator_name 
                       FROM announcements a 
                       LEFT JOIN users u ON a.created_by = u.id 
                       WHERE a.status = 1 
                       AND (a.start_time IS NULL OR a.start_time <= NOW()) 
                       AND (a.end_time IS NULL OR a.end_time >= NOW()) 
                       ORDER BY a.type DESC, a.created_at DESC 
                       LIMIT 10";
                
                $result = $conn->query($sql);
                
                if ($result === false) {
                    throw new Exception("查询失败: " . $conn->error);
                }
                
                $announcements = [];
                while ($row = $result->fetch_assoc()) {
                    // 处理公告类型的显示
                    switch ($row['type']) {
                        case '紧急':
                            $row['type_class'] = 'danger';
                            break;
                        case '重要':
                            $row['type_class'] = 'warning';
                            break;
                        default:
                            $row['type_class'] = 'info';
                    }
                    $announcements[] = $row;
                }
                
                $response = [
                    'success' => true,
                    'announcements' => $announcements
                ];
                
                $result->free();
                $conn->close();
                
            } catch (Exception $e) {
                error_log("获取公告失败: " . $e->getMessage());
                $response = [
                    'success' => false,
                    'message' => '获取公告失败：' . $e->getMessage()
                ];
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'update_profile':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else if ($requestMethod === 'POST') {
            $fields = [
                'nickname' => sanitizeInput($data['nickname'] ?? ''),
                'birthday' => sanitizeInput($data['birthday'] ?? ''),
                'location' => sanitizeInput($data['location'] ?? ''),
                'bio' => sanitizeHtml($data['bio'] ?? ''),
                'avatar' => sanitizeInput($data['avatar'] ?? ''),
                'website' => sanitizeUrl($data['website'] ?? ''),
                'social_media' => sanitizeUrl($data['social_media'] ?? '')
            ];
            $response = updateUserProfile(getCurrentUserId(), $fields);
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    case 'get_csrf_token':
        // 生成并返回CSRF令牌
        $token = Security::generateCsrfToken();
        $response = ['success' => true, 'token' => $token];
        break;
        
    case 'get_basic_settings':
        // 返回网站基本设置
        $response = [
            'success' => true,
            'settings' => [
                'SITE_NAME' => SITE_NAME,
                'SITE_URL' => SITE_URL,
                'ICP_LICENSE' => ICP_LICENSE,
                'POLICE_LICENSE' => POLICE_LICENSE,
                'COPYRIGHT_INFO' => COPYRIGHT_INFO,
                'WEBMASTER_EMAIL' => WEBMASTER_EMAIL
            ]
        ];
        break;
        
    case 'submit_report':
        // 提交举报
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else if ($requestMethod === 'POST') {
            $rules = [
                'target_type' => [
                    'required' => true,
                    'filter' => 'string',
                    'message' => '举报类型不能为空'
                ],
                'target_id' => [
                    'required' => true,
                    'filter' => 'int',
                    'message' => '被举报内容ID不能为空'
                ],
                'reason' => [
                    'required' => true,
                    'filter' => 'string',
                    'max_length' => 500,
                    'message' => '举报理由不能为空且不能超过500字符'
                ]
            ];
            
            $validation = Security::validateInput($data, $rules);
            
            if (!$validation['valid']) {
                $response = ['success' => false, 'message' => array_values($validation['errors'])[0]];
            } else {
                $targetType = $validation['data']['target_type'];
                $targetId = $validation['data']['target_id'];
                $reason = $validation['data']['reason'];
                
                // 验证target_type
                if (!in_array($targetType, ['bottle', 'comment'])) {
                    $response = ['success' => false, 'message' => '无效的举报类型'];
                } else {
                    $response = submitReport(getCurrentUserId(), $targetType, $targetId, $reason);
                }
            }
        } else {
            $response = ['success' => false, 'message' => '请求方法不支持'];
        }
        break;
        
    default:
        $response = ['success' => false, 'message' => '未找到API端点'];
        break;
}

// 在函数结束前，安全地输出JSON响应
if (!empty($response)) {
    Security::outputJson($response);
} else {
    Security::outputJson(['success' => false, 'message' => '未知错误']);
}
exit();
?> 