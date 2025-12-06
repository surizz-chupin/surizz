<?php
// 私信API接口

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
        case 'GET':
            // 获取私信列表
            $targetUserId = (int)($_GET['user_id'] ?? 0);
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            if ($targetUserId > 0) {
                // 获取与特定用户的私信
                $sql = "SELECT m.*, u1.username as sender_name, u2.username as receiver_name 
                        FROM messages m
                        JOIN users u1 ON m.sender_id = u1.id
                        JOIN users u2 ON m.receiver_id = u2.id
                        WHERE (m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)
                        ORDER BY m.created_at ASC
                        LIMIT ? OFFSET ?";
                $stmt = getDB()->prepare($sql);
                $stmt->execute([$currentUserId, $targetUserId, $targetUserId, $currentUserId, $limit, $offset]);
                $messages = $stmt->fetchAll();
                
                $response = [
                    'success' => true,
                    'data' => $messages
                ];
            } else {
                // 获取私信联系人列表
                $sql = "SELECT u.id, u.username, u.avatar, 
                               (SELECT COUNT(*) FROM messages WHERE (sender_id = u.id AND receiver_id = ?) AND is_read = 0) as unread_count,
                               (SELECT content FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_message,
                               (SELECT created_at FROM messages WHERE (sender_id = u.id AND receiver_id = ?) OR (sender_id = ? AND receiver_id = u.id) ORDER BY created_at DESC LIMIT 1) as last_time
                        FROM users u
                        WHERE u.id IN (
                            SELECT DISTINCT sender_id FROM messages WHERE receiver_id = ?
                            UNION
                            SELECT DISTINCT receiver_id FROM messages WHERE sender_id = ?
                        )
                        ORDER BY last_time DESC";
                $stmt = getDB()->prepare($sql);
                $stmt->execute([$currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId, $currentUserId]);
                $contacts = $stmt->fetchAll();
                
                $response = [
                    'success' => true,
                    'data' => $contacts
                ];
            }
            break;
            
        case 'POST':
            // 发送私信
            $receiverId = (int)($input['receiver_id'] ?? 0);
            $content = trim($input['content'] ?? '');
            
            if ($receiverId <= 0 || empty($content)) {
                throw new Exception('接收用户和消息内容不能为空');
            }
            
            if ($receiverId == $currentUserId) {
                throw new Exception('不能给自己发送私信');
            }
            
            // 检查接收用户是否存在
            $stmt = getDB()->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$receiverId]);
            if (!$stmt->fetch()) {
                throw new Exception('接收用户不存在');
            }
            
            // 发送私信
            $stmt = getDB()->prepare("INSERT INTO messages (sender_id, receiver_id, content) VALUES (?, ?, ?)");
            if ($stmt->execute([$currentUserId, $receiverId, $content])) {
                $response = [
                    'success' => true,
                    'message' => '私信发送成功'
                ];
            } else {
                throw new Exception('私信发送失败，请重试');
            }
            break;
            
        case 'PUT':
            // 标记消息为已读
            $receiverId = (int)($input['receiver_id'] ?? 0);
            
            if ($receiverId <= 0) {
                throw new Exception('接收用户ID无效');
            }
            
            $stmt = getDB()->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
            $stmt->execute([$receiverId, $currentUserId]);
            
            $response = [
                'success' => true,
                'message' => '消息已标记为已读'
            ];
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);