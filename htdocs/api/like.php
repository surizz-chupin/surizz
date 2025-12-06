<?php
// 点赞功能API接口

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
            if (!isLoggedIn()) {
                throw new Exception('请先登录');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $targetType = $input['target_type'] ?? '';
            $targetId = (int)($input['target_id'] ?? 0);
            
            if (!in_array($targetType, ['post', 'comment']) || $targetId <= 0) {
                throw new Exception('参数错误');
            }
            
            $result = $db->toggleLike($_SESSION['user_id'], $targetType, $targetId);
            
            if ($result !== false) {
                // 获取更新后的点赞数
                if ($targetType === 'post') {
                    $stmt = getDB()->prepare("SELECT like_count FROM posts WHERE id = ?");
                    $stmt->execute([$targetId]);
                    $likeCount = $stmt->fetch()['like_count'];
                } else {
                    $stmt = getDB()->prepare("SELECT like_count FROM comments WHERE id = ?");
                    $stmt->execute([$targetId]);
                    $likeCount = $stmt->fetch()['like_count'];
                }
                
                $response = [
                    'success' => true,
                    'message' => $result ? '点赞成功' : '取消点赞',
                    'like_count' => $likeCount
                ];
            } else {
                throw new Exception('操作失败');
            }
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);