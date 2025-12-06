<?php
// 举报API接口

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
            // 提交举报
            $targetType = $input['target_type'] ?? '';
            $targetId = (int)($input['target_id'] ?? 0);
            $reason = trim($input['reason'] ?? '');
            
            if (!in_array($targetType, ['post', 'comment', 'user']) || $targetId <= 0 || empty($reason)) {
                throw new Exception('举报类型、目标ID和原因不能为空');
            }
            
            if ($db->reportContent($currentUserId, $targetType, $targetId, $reason)) {
                $response = [
                    'success' => true,
                    'message' => '举报提交成功，管理员会尽快处理'
                ];
            } else {
                throw new Exception('举报提交失败，请重试');
            }
            break;
            
        case 'GET':
            // 管理员获取举报列表
            if (!isAdmin()) {
                throw new Exception('权限不足');
            }
            
            $status = $_GET['status'] ?? null;
            $page = (int)($_GET['page'] ?? 1);
            $limit = 20;
            $offset = ($page - 1) * $limit;
            
            $reports = $db->getReports($status, $limit, $offset);
            
            $response = [
                'success' => true,
                'data' => $reports
            ];
            break;
            
        case 'PUT':
            // 管理员处理举报
            if (!isAdmin()) {
                throw new Exception('权限不足');
            }
            
            $reportId = (int)($input['report_id'] ?? 0);
            $status = $input['status'] ?? '';
            
            if ($reportId <= 0 || !in_array($status, ['approved', 'rejected', 'pending'])) {
                throw new Exception('举报ID和状态无效');
            }
            
            if ($db->updateReportStatus($reportId, $status)) {
                $response = [
                    'success' => true,
                    'message' => '举报处理成功'
                ];
            } else {
                throw new Exception('举报处理失败，请重试');
            }
            break;
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
}

echo json_encode($response);