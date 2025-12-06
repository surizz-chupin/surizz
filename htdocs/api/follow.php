<?php
// 用户关注API接口

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
            $targetUserId = (int)($input['user_id'] ?? 0);
            
            if ($targetUserId <= 0) {
                throw new Exception('用户ID无效');
            }
            
            if ($currentUserId == $targetUserId) {
                throw new Exception('不能关注自己');
            }
            
            // 检查是否已关注
            $stmt = getDB()->prepare("SELECT id FROM follows WHERE follower_id = ? AND followed_id = ?");
            $stmt->execute([$currentUserId, $targetUserId]);
            if ($stmt->fetch()) {
                throw new Exception('已关注该用户');
            }
            
            // 添加关注
            $stmt = getDB()->prepare("INSERT INTO follows (follower_id, followed_id) VALUES (?, ?)");
            if ($stmt->execute([$currentUserId, $targetUserId])) {
                $response = [
                    'success' => true,
                    'message' => '关注成功'
                ];
            } else {
                throw new Exception('关注失败，请重试');
            }
            break;
            
        case 'DELETE':
            $targetUserId = (int)($_GET['user_id'] ?? 0);
            
            if ($targetUserId <= 0) {
                throw new Exception('用户ID无效');
            }
            
            // 取消关注
            $stmt = getDB()->prepare("DELETE FROM follows WHERE follower_id = ? AND followed_id = ?");
            if ($stmt->execute([$currentUserId, $targetUserId])) {
                $response = [
                    'success' => true,
                    'message' => '取消关注成功'
                ];
            } else {
                throw new Exception('取消关注失败，请重试');
            }
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);