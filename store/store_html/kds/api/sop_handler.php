<?php
/**
 * Toptea Store - KDS
 * API Handler for SOP Data (Dynamic Recipe Engine Version)
 * Engineer: Gemini | Date: 2025-10-31 | Revision: 3.0
 */
header('Content-Type: application/json; charset=utf-8');

require_once realpath(__DIR__ . '/../../../kds/core/config.php');
require_once KDS_HELPERS_PATH . '/kds_helper.php';

function send_json_response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    send_json_response('error', 'Invalid request method.');
}

// The parameter is now 'code' which can be 'P0003' or 'P0003-A001-M002-T001'
$code = filter_input(INPUT_GET, 'code', FILTER_SANITIZE_STRING);

if (!$code) {
    http_response_code(400);
    send_json_response('error', '无效或缺失的配方编码 (Code is missing)。');
}

try {
    $sop_data = getDynamicSopDataByCode($pdo, $code);

    if ($sop_data) {
        send_json_response('success', '配方数据获取成功。', $sop_data);
    } else {
        http_response_code(404);
        send_json_response('error', '找不到对应的产品配方，或该产品未上架。');
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log("KDS SOP Handler Error: " . $e->getMessage());
    send_json_response('error', '查询配方时发生数据库错误。', ['debug' => $e->getMessage()]);
}