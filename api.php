<?php
require_once 'includes/config.php';
require_once 'includes/user.php';
require_once 'includes/bottle.php';
require_once 'includes/ip_location.php';
require_once 'includes/security.php';
require_once 'includes/validator.php';

// 初始化安全中间件
Security::init();

// 设置头信息为JSON
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');

// 获取请求路径
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

// 获取endpoint
$endpoint = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

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
        
    case 'create_bottle':
        if (!isLoggedIn()) {
            $response = ['success' => false, 'message' => '请先登录'];
        } else if ($requestMethod === 'POST') {
            // 定义验证规则
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
            
            // 验证输入
            $validation = Security::validateInput($data, $rules);
            
            if (!$validation['valid']) {
                $response = ['success' => false, 'message' => array_values($validation['errors'])[0]];
            } else {
                $content = $validation['data']['content'];
                $isAnonymous = isset($validation['data']['is_anonymous']) ? (int)$validation['data']['is_anonymous'] : 0;
                $mood = $validation['data']['mood'] ?? '其他';
                
                $response = createBottle(getCurrentUserId(), $content, $isAnonymous, $mood);
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
                
                $response = commentAndThrowBottle($bottleId, getCurrentUserId(), $content);
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
            
            $response = [
                'success' => true, 
                'limits' => $limits,
                'throw_remaining' => DAILY_BOTTLE_LIMIT - $limits['throw_count'],
                'pick_remaining' => $pickLimit - $limits['pick_count'],
                'free_throws_remaining' => $limits['free_throws_remaining'],
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
                        'created_at' => $userData['user']['created_at']
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
                'SITE_URL' => SITE_URL
            ]
        ];
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