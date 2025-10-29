<?php
/**
 * Toptea HQ - cpsys
 * AJAX Logic for Saving Product (Processor)
 *
 * Date: 2025-10-23
 * Revision: 3.3 (Syntax Error Corrected)
 */

if (!isset($pdo)) {
    http_response_code(403);
    die(json_encode(['status' => 'error', 'message' => 'Forbidden Access']));
}

function send_json_response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    send_json_response('error', 'Invalid request method.');
}

$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    send_json_response('error', 'Invalid JSON data received.');
}

$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare("INSERT INTO kds_products (product_sku, cup_id, status_id, is_active) VALUES (?, ?, ?, 1)");
    $stmt->execute([$data['sku'], $data['cup_id'], $data['status_id']]);
    $product_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO kds_product_translations (product_id, language_code, product_name) VALUES (?, ?, ?)");
    $stmt->execute([$product_id, 'zh-CN', $data['name_zh']]);
    $stmt->execute([$product_id, 'es-ES', $data['name_es']]);

    $stmt = $pdo->prepare("INSERT INTO kds_product_recipes (product_id, material_id, unit_id, quantity, step_category, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($data['recipes'] as $category => $steps) {
        $sort_order = 0;
        foreach ($steps as $step) {
            $stmt->execute([$product_id, $step['material_id'], $step['unit_id'], $step['quantity'], $category, $sort_order]);
            $sort_order++;
        }
    }
    
    $pdo->commit();
    send_json_response('success', '产品已成功保存！');

} catch (PDOException $e) {
    $pdo->rollBack();
    if ($e->getCode() == '23000') {
        http_response_code(409); // Conflict
        send_json_response('error', '饮品编码 "' . htmlspecialchars($data['sku']) . '" 已被一个有效产品使用，请使用其他编码。');
    } else {
        http_response_code(500);
        send_json_response('error', '保存产品时发生数据库错误。', ['error_message' => $e->getMessage()]);
    }
}
// The stray '}' at the end of the file has been removed.