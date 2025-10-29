<?php
/**
 * Toptea Store - KDS API
 * API Endpoint for KDS to record a new expiry item
 * Engineer: Gemini | Date: 2025-10-26 | Revision: 8.2 (DB Column Fix)
 */

require_once realpath(__DIR__ . '/../../../kds/core/config.php');
require_once realpath(__DIR__ . '/../../../kds/helpers/kds_helper_shim.php');

header('Content-Type: application/json; charset=utf-8');
@session_start();

function send_json_response($status, $message, $data = null) { echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_json_response('error', 'Invalid request method.');
}

if (!isset($_SESSION['kds_store_id']) || !isset($_SESSION['kds_user_id'])) {
    http_response_code(401);
    send_json_response('error', 'Unauthorized: Missing KDS session data.');
}

$json_data = json_decode(file_get_contents('php://input'), true);
$material_id = (int)($json_data['material_id'] ?? 0);
$store_id = (int)$_SESSION['kds_store_id'];

if ($material_id <= 0) {
    http_response_code(400);
    send_json_response('error', '无效的物料ID。');
}

try {
    $material = getMaterialById($pdo, $material_id);
    if (!$material || !$material['expiry_rule_type']) {
        http_response_code(404);
        send_json_response('error', '找不到该物料或该物料未设置效期规则。');
    }

    $opened_at = new DateTime('now', new DateTimeZone('Europe/Madrid'));
    $expires_at = clone $opened_at;

    switch ($material['expiry_rule_type']) {
        case 'HOURS':
            $expires_at->add(new DateInterval('PT' . (int)$material['expiry_duration'] . 'H'));
            break;
        case 'DAYS':
            $expires_at->add(new DateInterval('P' . (int)$material['expiry_duration'] . 'D'));
            break;
        case 'END_OF_DAY':
            $expires_at->setTime(23, 59, 59);
            break;
    }

    $pdo->beginTransaction();

    // --- CORE FIX: Removed the non-existent 'opened_by_id' column from the INSERT statement ---
    $stmt_expiry = $pdo->prepare(
        "INSERT INTO kds_material_expiries (material_id, store_id, opened_at, expires_at, status) VALUES (?, ?, ?, ?, 'ACTIVE')"
    );
    $stmt_expiry->execute([
        $material_id,
        $store_id,
        $opened_at->format('Y-m-d H:i:s'),
        $expires_at->format('Y-m-d H:i:s')
    ]);
    
    $pdo->commit();

    send_json_response('success', '效期记录已生成。');

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) { $pdo->rollBack(); }
    http_response_code(500);
    // Provide a more detailed error message for debugging
    send_json_response('error', '服务器内部错误。', ['debug' => $e->getMessage()]);
}