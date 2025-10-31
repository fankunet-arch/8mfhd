<?php
/**
 * Toptea HQ - RMS API Handler
 * Handles all CRUD for the new dynamic recipe engine.
 * Engineer: Gemini | Date: 2025-10-31
 */
require_once realpath(__DIR__ . '/../../../../../core/config.php');
require_once APP_PATH . '/helpers/kds_helper.php';
require_once APP_PATH . '/helpers/auth_helper.php';

header('Content-Type: application/json; charset=utf-8');
function send_json_response($status, $message, $data = null, $http = 200) { 
    http_response_code($http);
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]); 
    exit; 
}

@session_start();
if (($_SESSION['role_id'] ?? null) !== ROLE_SUPER_ADMIN) {
    send_json_response('error', '权限不足。', null, 403);
}

global $pdo;
$action = $_GET['action'] ?? null;
$json_data = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' || $_SERVER['REQUEST_METHOD'] === 'PUT') {
    $json_data = json_decode(file_get_contents('php://input'), true);
    $action = $json_data['action'] ?? $action;
}

try {
    switch($action) {
        case 'get_product_details':
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if (!$id) send_json_response('error', '无效的产品ID。', null, 400);
            
            $data = getProductDetailsForRMS($pdo, $id);

            if ($data) {
                send_json_response('success', '产品详情加载成功。', $data);
            } else {
                send_json_response('error', '未找到产品。', null, 404);
            }
            break;
        
        case 'save_product':
            $productData = $json_data['product'];
            if (empty($productData)) send_json_response('error', '无效的产品数据。', null, 400);

            $pdo->beginTransaction();

            $productId = (int)($productData['id'] ?? 0);
            
            // Step 1: Save or Update Base Product Info
            if ($productId > 0) { // Update
                $stmt = $pdo->prepare("UPDATE kds_products SET product_code = ?, status_id = ? WHERE id = ?");
                $stmt->execute([$productData['product_code'], $productData['status_id'], $productId]);

                $stmt_trans = $pdo->prepare("UPDATE kds_product_translations SET product_name = ? WHERE product_id = ? AND language_code = ?");
                $stmt_trans->execute([$productData['name_zh'], $productId, 'zh-CN']);
                $stmt_trans->execute([$productData['name_es'], $productId, 'es-ES']);
            } else { // Create
                $stmt = $pdo->prepare("INSERT INTO kds_products (product_code, status_id, is_active) VALUES (?, ?, 1)");
                $stmt->execute([$productData['product_code'], $productData['status_id']]);
                $productId = $pdo->lastInsertId();

                $stmt_trans = $pdo->prepare("INSERT INTO kds_product_translations (product_id, language_code, product_name) VALUES (?, ?, ?)");
                $stmt_trans->execute([$productId, 'zh-CN', $productData['name_zh']]);
                $stmt_trans->execute([$productId, 'es-ES', $productData['name_es']]);
            }

            // Step 2: Clear old recipes and adjustments
            $pdo->prepare("DELETE FROM kds_product_recipes WHERE product_id = ?")->execute([$productId]);
            $pdo->prepare("DELETE FROM kds_recipe_adjustments WHERE product_id = ?")->execute([$productId]);

            // Step 3: Insert new base recipes
            $stmt_recipe = $pdo->prepare("INSERT INTO kds_product_recipes (product_id, material_id, quantity, unit_id, step_category, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
            foreach ($productData['base_recipes'] as $index => $recipe) {
                $stmt_recipe->execute([$productId, $recipe['material_id'], $recipe['quantity'], $recipe['unit_id'], 'base', $index]);
            }
            
            // Step 4: Insert new adjustment rules
            $stmt_adj = $pdo->prepare("INSERT INTO kds_recipe_adjustments (product_id, material_id, cup_id, sweetness_option_id, ice_option_id, quantity, unit_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($productData['adjustments'] as $adj) {
                 $stmt_adj->execute([
                    $productId, 
                    $adj['material_id'],
                    empty($adj['cup_id']) ? null : $adj['cup_id'],
                    empty($adj['sweetness_option_id']) ? null : $adj['sweetness_option_id'],
                    empty($adj['ice_option_id']) ? null : $adj['ice_option_id'],
                    $adj['quantity'],
                    $adj['unit_id']
                ]);
            }
            
            $pdo->commit();
            send_json_response('success', '产品配方已成功保存！', ['new_id' => $productId]);
            break;

        case 'delete_product':
             $id = (int)($json_data['id'] ?? 0);
             if (!$id) send_json_response('error', '无效的产品ID。', null, 400);

             $stmt = $pdo->prepare("UPDATE kds_products SET deleted_at = CURRENT_TIMESTAMP WHERE id = ?");
             $stmt->execute([$id]);
             send_json_response('success', '产品已成功删除。');
             break;

        default:
            send_json_response('error', '无效的操作请求。', null, 400);
    }
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("RMS API Error: " . $e->getMessage());
    send_json_response('error', '服务器内部错误: ' . $e->getMessage());
}