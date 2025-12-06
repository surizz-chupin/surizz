<?php
// 用户认证API接口

require_once '../includes/config.php';
require_once '../includes/database.php';

session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

$db = new Database();
$response = ['success' => false, 'message' => ''];

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $action = $_GET['action'] ?? '';
            $input = json_decode(file_get_contents('php://input'), true);
            
            if ($action === 'login') {
                $username = trim($input['username'] ?? '');
                $password = $input['password'] ?? '';
                
                if (empty($username) || empty($password)) {
                    throw new Exception('用户名和密码不能为空');
                }
                
                $user = $db->authenticateUser($username, $password);
                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    $response = [
                        'success' => true,
                        'message' => '登录成功',
                        'user' => [
                            'id' => $user['id'],
                            'username' => $user['username'],
                            'role' => $user['role']
                        ]
                    ];
                } else {
                    throw new Exception('用户名或密码错误');
                }
            } elseif ($action === 'register') {
                $username = trim($input['username'] ?? '');
                $email = trim($input['email'] ?? '');
                $password = $input['password'] ?? '';
                $confirmPassword = $input['confirm_password'] ?? '';
                
                if (empty($username) || empty($email) || empty($password)) {
                    throw new Exception('用户名、邮箱和密码不能为空');
                }
                
                if ($password !== $confirmPassword) {
                    throw new Exception('两次输入的密码不一致');
                }
                
                if (strlen($password) < 6) {
                    throw new Exception('密码长度至少6位');
                }
                
                // 检查用户名或邮箱是否已存在
                $stmt = getDB()->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    throw new Exception('用户名或邮箱已被注册');
                }
                
                if ($db->registerUser($username, $email, $password)) {
                    $response = [
                        'success' => true,
                        'message' => '注册成功'
                    ];
                } else {
                    throw new Exception('注册失败，请重试');
                }
            } elseif ($action === 'logout') {
                session_destroy();
                $response = [
                    'success' => true,
                    'message' => '退出登录成功'
                ];
            }
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);