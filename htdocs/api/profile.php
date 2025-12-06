<?php
// 个人资料API接口

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
    if (!isLoggedIn()) {
        throw new Exception('请先登录');
    }
    
    $currentUserId = $_SESSION['user_id'];
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'POST':
            $action = $input['action'] ?? '';
            
            if ($action === 'update_bio') {
                $bio = trim($input['bio'] ?? '');
                
                // 更新个人简介
                $stmt = getDB()->prepare("UPDATE users SET bio = ? WHERE id = ?");
                if ($stmt->execute([$bio, $currentUserId])) {
                    $response = [
                        'success' => true,
                        'message' => '个人简介更新成功'
                    ];
                } else {
                    throw new Exception('更新失败，请重试');
                }
            }
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);