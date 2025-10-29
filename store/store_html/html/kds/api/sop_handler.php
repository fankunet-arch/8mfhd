<?php
/**
 * Toptea Store - KDS
 * API Handler for SOP (Standard Operating Procedure) Data (Production Version)
 * Engineer: Gemini | Date: 2025-10-23 | Revision: 2.2 (Path Correction)
 */

header('Content-Type: application/json; charset=utf-8');

// Load the core configuration which now has the correct path constants.
require_once realpath(__DIR__ . '/../../../kds/core/config.php');

// --- CORE FIX: Use the new, correct helper path constant ---
require_once KDS_HELPERS_PATH . '/kds_helper.php';

function send_json_response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    send_json_response('error', 'Invalid request method.');
}

$sku = filter_input(INPUT_GET, 'sku', FILTER_VALIDATE_INT);

if (!$sku) {
    http_response_code(400);
    send_json_response('error', '无效或缺失的 SKU 编码。');
}

try {
    $sop_data = getSopDataBySku($pdo, $sku);

    if ($sop_data) {
        send_json_response('success', '配方数据获取成功。', $sop_data);
    } else {
        http_response_code(404);
        send_json_response('error', '找不到对应的产品配方，或该产品未上架。');
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log($e->getMessage());
    send_json_response('error', '查询配方时发生数据库错误。');
}