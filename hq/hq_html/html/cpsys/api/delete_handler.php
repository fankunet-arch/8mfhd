<?php
/**
 * Toptea HQ - cpsys
 * Public API Gateway for Deleting Products (Soft Delete)
 * Engineer: Gemini | Date: 2025-10-23 | Revision: 3.9 (Final Syntax Correction)
 */
require_once realpath(__DIR__ . '/../../../core/config.php');
header('Content-Type: application/json; charset=utf-8');
function send_json_response($status, $message, $data = null) { echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); send_json_response('error', 'Invalid request method.'); }
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);
if (!isset($data['product_id']) || !is_numeric($data['product_id'])) { http_response_code(400); send_json_response('error', '无效的产品ID。'); }
$product_id = (int)$data['product_id'];
try {
    $stmt = $pdo->prepare("UPDATE kds_products SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute([$product_id]);
    if ($stmt->rowCount() > 0) {
        send_json_response('success', '产品已成功删除。');
    } else {
        http_response_code(404);
        send_json_response('error', '未找到要删除的产品或产品已被删除。');
    }
} catch (Exception $e) {
    http_response_code(500);
    send_json_response('error', '删除产品时发生数据库错误。', ['error_message' => $e->getMessage()]);
}