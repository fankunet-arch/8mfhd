<?php
/**
 * Toptea HQ - cpsys
 * Public API Gateway for Updating Products
 * Engineer: Gemini | Date: 2025-10-26 | Revision: 8.8 (Add POS Category)
 */
require_once realpath(__DIR__ . '/../../../core/config.php');
header('Content-Type: application/json; charset=utf-8');

function send_json_response($status, $message, $data = null) { 
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]); 
    exit; 
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { 
    http_response_code(405); 
    send_json_response('error', 'Invalid request method.'); 
}

$json_data = json_decode(file_get_contents('php://input'), true);
$data = $json_data['data'] ?? null;

if (json_last_error() !== JSON_ERROR_NONE || !$data || !isset($data['id']) || !is_numeric($data['id'])) { 
    http_response_code(400); 
    send_json_response('error', '无效的请求数据或产品ID。'); 
}

$product_id = (int)$data['id'];

$pdo->beginTransaction();
try {
    // 1. 检查 SKU 唯一性
    $stmt_check = $pdo->prepare("SELECT id FROM kds_products WHERE product_sku = ? AND id != ? AND deleted_at IS NULL");
    $stmt_check->execute([$data['sku'], $product_id]);
    if ($stmt_check->fetch()) { 
        $pdo->rollBack();
        http_response_code(409); 
        send_json_response('error', '饮品编码 "' . htmlspecialchars($data['sku']) . '" 已被另一个产品使用。'); 
    }

    // 2. 更新产品主表
    // --- START: CORE FIX ---
    $stmt_update_product = $pdo->prepare("UPDATE kds_products SET product_sku = ?, cup_id = ?, pos_category_id = ?, status_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt_update_product->execute([$data['sku'], $data['cup_id'], (int)($data['pos_category_id'] ?? 0), $data['status_id'], $product_id]);
    // --- END: CORE FIX ---

    // 3. 更新产品翻译表
    $stmt_update_trans_zh = $pdo->prepare("UPDATE kds_product_translations SET product_name = ? WHERE product_id = ? AND language_code = 'zh-CN'");
    $stmt_update_trans_zh->execute([$data['name_zh'], $product_id]);
    $stmt_update_trans_es = $pdo->prepare("UPDATE kds_product_translations SET product_name = ? WHERE product_id = ? AND language_code = 'es-ES'");
    $stmt_update_trans_es->execute([$data['name_es'], $product_id]);

    // 4. 更新制作步骤 (先删后插)
    $stmt_delete_recipes = $pdo->prepare("DELETE FROM kds_product_recipes WHERE product_id = ?");
    $stmt_delete_recipes->execute([$product_id]);
    
    $stmt_insert_recipe = $pdo->prepare("INSERT INTO kds_product_recipes (product_id, material_id, unit_id, quantity, step_category, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
    if (!empty($data['recipes'])) {
        foreach ($data['recipes'] as $category => $steps) {
            $sort_order = 0;
            foreach ($steps as $step) {
                if (!empty($step['material_id']) && !empty($step['unit_id'])) {
                    $stmt_insert_recipe->execute([$product_id, $step['material_id'], $step['unit_id'], $step['quantity'], $category, $sort_order]);
                    $sort_order++;
                }
            }
        }
    }
    
    // 5. 更新甜度/冰度选项 (先删后插)
    $pdo->prepare("DELETE FROM kds_product_sweetness_options WHERE product_id = ?")->execute([$product_id]);
    if (!empty($data['sweetness_options'])) {
        $stmt_sweetness = $pdo->prepare("INSERT INTO kds_product_sweetness_options (product_id, sweetness_option_id) VALUES (?, ?)");
        foreach ($data['sweetness_options'] as $option_id) {
            $stmt_sweetness->execute([$product_id, (int)$option_id]);
        }
    }
    
    $pdo->prepare("DELETE FROM kds_product_ice_options WHERE product_id = ?")->execute([$product_id]);
    if (!empty($data['ice_options'])) {
        $stmt_ice = $pdo->prepare("INSERT INTO kds_product_ice_options (product_id, ice_option_id) VALUES (?, ?)");
        foreach ($data['ice_options'] as $option_id) {
            $stmt_ice->execute([$product_id, (int)$option_id]);
        }
    }

    $pdo->commit();
    send_json_response('success', '产品已成功更新！');

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    error_log($e->getMessage()); 
    send_json_response('error', '更新产品时发生了一个未知的服务器内部错误。');
}