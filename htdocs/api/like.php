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
            
            // 获取目标内容的作者ID
            if ($targetType === 'post') {
                $stmt = getDB()->prepare("SELECT user_id FROM posts WHERE id = ?");
                $stmt->execute([$targetId]);
                $target = $stmt->fetch();
            } else {
                $stmt = getDB()->prepare("SELECT user_id FROM comments WHERE id = ?");
                $stmt->execute([$targetId]);
                $target = $stmt->fetch();
            }
            
            if (!$target) {
                throw new Exception('目标内容不存在');
            }
            
            $targetUserId = $target['user_id'];
            $wasLiked = $db->isLiked($_SESSION['user_id'], $targetType, $targetId);
            $result = $db->toggleLike($_SESSION['user_id'], $targetType, $targetId);
            
            // 如果是点赞操作，给被点赞用户添加积分；如果是取消点赞，扣除积分
            if ($result && !$wasLiked) {
                // 添加点赞
                $db->addPoints($targetUserId, POINTS_LIKE_RECEIVED, '内容被点赞');
            } elseif (!$result && $wasLiked) {
                // 取消点赞
                $db->addPoints($targetUserId, -POINTS_LIKE_RECEIVED, '内容被取消点赞');
            }
            
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