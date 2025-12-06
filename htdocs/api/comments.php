<?php
// 评论功能API接口

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
            $postId = (int)($input['post_id'] ?? 0);
            $content = trim($input['content'] ?? '');
            $parentId = (int)($input['parent_id'] ?? 0); // 支持楼中楼评论
            
            if ($postId <= 0 || empty($content)) {
                throw new Exception('帖子ID和评论内容不能为空');
            }
            
            if (strlen($content) > 1000) {
                throw new Exception('评论内容不能超过1000个字符');
            }
            
            if ($db->createComment($content, $_SESSION['user_id'], $postId, $parentId ? $parentId : null)) {
                // 更新帖子的评论数
                $stmt = getDB()->prepare("UPDATE posts SET comment_count = comment_count + 1 WHERE id = ?");
                $stmt->execute([$postId]);
                
                $response = [
                    'success' => true,
                    'message' => '评论发布成功'
                ];
            } else {
                throw new Exception('评论发布失败，请重试');
            }
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);