<?php
/**
 * Toptea HQ - cpsys
 * API Handler for Product Creation
 * Engineer: Gemini | Date: 2025-10-24 | Revision: 4.6 (API Data Structure Alignment)
 */
require_once realpath(__DIR__ . '/../../../core/config.php');
require_once APP_PATH . '/helpers/kds_helper.php';

header('Content-Type: application/json; charset=utf-8');

function send_json_response($status, $message, $data = null) { echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); send_json_response('error', 'Invalid request method.'); }

$json_data = json_decode(file_get_contents('php://input'), true);
$data = $json_data['data'] ?? null; // <-- CORE FIX: Expect wrapped data

if (!$data) { http_response_code(400); send_json_response('error', '无效的请求数据。'); }

// The rest of the logic is from our proven save_product.php
$pdo->beginTransaction();
try {
    $stmt_check = $pdo->prepare("SELECT id FROM kds_products WHERE product_sku = ? AND deleted_at IS NULL");
    $stmt_check->execute([$data['sku']]);
    if ($stmt_check->fetch()) {
        http_response_code(409); send_json_response('error', '饮品编码 "' . htmlspecialchars($data['sku']) . '" 已被一个有效产品使用。');
    }
    
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
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500); error_log($e->getMessage()); 
    send_json_response('error', '保存产品时发生了一个未知的服务器内部错误。');
}