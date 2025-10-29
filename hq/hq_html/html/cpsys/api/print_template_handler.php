<?php
/**
 * TopTea HQ - Print Template Management API
 * Version: 2.1.0
 * Engineer: Gemini | Date: 2025-10-29
 * Implements A.2 / 7.A.3 - Step 1.2: Template Management Backend Service
 */

require_once realpath(__DIR__ . '/../../../core/config.php');
require_once APP_PATH . '/helpers/auth_helper.php';

header('Content-Type: application/json; charset=utf-8');

function send_json_response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

@session_start();

// Security: Only Super Admins can manage templates
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] !== ROLE_SUPER_ADMIN) {
    http_response_code(403);
    send_json_response('error', '权限不足。');
}

global $pdo;
$action = $_GET['action'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_data = json_decode(file_get_contents('php://input'), true);
    $action = $json_data['action'] ?? $action;
}

try {
    switch($action) {
        case 'get':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                http_response_code(400);
                send_json_response('error', '无效的模板ID。');
            }
            $stmt = $pdo->prepare("SELECT * FROM pos_print_templates WHERE id = ?");
            $stmt->execute([$id]);
            $template = $stmt->fetch();
            if ($template) {
                send_json_response('success', '模板加载成功。', $template);
            } else {
                http_response_code(404);
                send_json_response('error', '未找到指定的模板。');
            }
            break;

        case 'save':
            $data = $json_data['data'] ?? [];
            $id = !empty($data['id']) ? (int)$data['id'] : null;

            $content_json = $data['template_content'] ?? '[]';
            // Validate JSON
            json_decode($content_json);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                send_json_response('error', '模板内容不是有效的JSON格式。');
            }

            $params = [
                ':template_name' => trim($data['template_name'] ?? ''),
                ':template_type' => trim($data['template_type'] ?? ''),
                ':template_content' => $content_json,
                ':is_active' => (int)($data['is_active'] ?? 0)
            ];

            if (empty($params[':template_name']) || empty($params[':template_type'])) {
                http_response_code(400);
                send_json_response('error', '模板名称和类型为必填项。');
            }

            if ($id) {
                $params[':id'] = $id;
                $sql = "UPDATE pos_print_templates SET template_name = :template_name, template_type = :template_type, template_content = :template_content, is_active = :is_active WHERE id = :id";
            } else {
                $sql = "INSERT INTO pos_print_templates (template_name, template_type, template_content, is_active) VALUES (:template_name, :template_type, :template_content, :is_active)";
            }
            
            $pdo->prepare($sql)->execute($params);
            send_json_response('success', $id ? '模板已成功更新！' : '新模板已成功创建！');
            break;

        case 'delete':
            $id = (int)($json_data['id'] ?? 0);
            if (!$id) {
                http_response_code(400);
                send_json_response('error', '无效的模板ID。');
            }
            $stmt = $pdo->prepare("DELETE FROM pos_print_templates WHERE id = ?");
            $stmt->execute([$id]);
            send_json_response('success', '模板已成功删除。');
            break;

        default:
            http_response_code(400);
            send_json_response('error', '无效的操作请求。');
            break;
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("Print Template API Error: " . $e->getMessage());
    send_json_response('error', '服务器内部错误。');
}