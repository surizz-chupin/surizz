<?php
// 帖子相关API接口

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
        case 'GET':
            // 获取帖子列表
            $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : null;
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = POSTS_PER_PAGE;
            $offset = ($page - 1) * $limit;
            
            $posts = $db->getPosts($categoryId, $limit, $offset);
            $totalPosts = count($posts); // 实际上这里应该是查询总数，为了简化先这样
            
            $response = [
                'success' => true,
                'data' => $posts,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $limit,
                    'total' => $totalPosts
                ]
            ];
            break;
            
        case 'POST':
            if (!isLoggedIn()) {
                throw new Exception('请先登录');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $title = trim($input['title'] ?? '');
            $content = trim($input['content'] ?? '');
            $categoryId = (int)($input['category_id'] ?? 0);
            
            if (empty($title) || empty($content) || $categoryId <= 0) {
                throw new Exception('标题、内容和板块不能为空');
            }
            
            if ($db->createPost($title, $content, $_SESSION['user_id'], $categoryId)) {
                // 发帖成功后添加积分
                $db->addPoints($_SESSION['user_id'], POINTS_POST, '发布帖子');
                
                $response = [
                    'success' => true,
                    'message' => '帖子发布成功'
                ];
            } else {
                throw new Exception('发布失败，请重试');
            }
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);