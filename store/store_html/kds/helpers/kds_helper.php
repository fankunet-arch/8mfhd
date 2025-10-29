<?php
/**
 * Toptea Store - KDS
 * KDS Data Helper Functions
 * Engineer: Gemini | Date: 2025-10-23 | Revision: 2.3 (Final Polish)
 */

/**
 * Fetches a complete, structured, and bilingual recipe for a given product SKU.
 */
function getSopDataBySku(PDO $pdo, int $sku): ?array
{
    // 1. Fetch the main product data, now including status
    $product_sql = "
        SELECT
            p.id,
            p.product_sku,
            pt_zh.product_name AS name_zh,
            pt_es.product_name AS name_es,
            c.cup_name,
            ps.status_name AS status_name_zh -- Assuming status name in DB is Chinese
        FROM kds_products p
        LEFT JOIN kds_product_translations pt_zh ON p.id = pt_zh.product_id AND pt_zh.language_code = 'zh-CN'
        LEFT JOIN kds_product_translations pt_es ON p.id = pt_es.product_id AND pt_es.language_code = 'es-ES'
        LEFT JOIN kds_cups c ON p.cup_id = c.id
        LEFT JOIN kds_product_statuses ps ON p.status_id = ps.id
        WHERE p.product_sku = ? AND p.deleted_at IS NULL AND p.is_active = 1
    ";
    $stmt = $pdo->prepare($product_sql);
    $stmt->execute([$sku]);
    $product_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product_data) {
        return null;
    }
    // For now, we'll manually add a placeholder for Spanish status name
    $product_data['status_name_es'] = $product_data['status_name_zh'];


    // 2. Fetch all recipe steps for this product
    $recipes_sql = "
        SELECT 
            r.quantity,
            r.step_category,
            r.sort_order,
            mt_zh.material_name AS material_zh,
            mt_es.material_name AS material_es,
            ut_zh.unit_name AS unit_zh,
            ut_es.unit_name AS unit_es
        FROM kds_product_recipes r
        LEFT JOIN kds_material_translations mt_zh ON r.material_id = mt_zh.material_id AND mt_zh.language_code = 'zh-CN'
        LEFT JOIN kds_material_translations mt_es ON r.material_id = mt_es.material_id AND mt_es.language_code = 'es-ES'
        LEFT JOIN kds_unit_translations ut_zh ON r.unit_id = ut_zh.unit_id AND ut_zh.language_code = 'zh-CN'
        LEFT JOIN kds_unit_translations ut_es ON r.unit_id = ut_es.unit_id AND ut_es.language_code = 'es-ES'
        WHERE r.product_id = ?
        ORDER BY r.step_category, r.sort_order ASC
    ";
    $stmt = $pdo->prepare($recipes_sql);
    $stmt->execute([$product_data['id']]);
    $recipe_steps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Structure the data into the final format required by the frontend
    $structured_recipe = [
        'base' => ['tip' => ['zh-CN' => '先放底料，确保量具干净、准确。', 'es-ES' => 'Primero, los ingredientes base. Asegúrese de que las herramientas estén limpias.'], 'items' => []],
        'mixing' => ['tip' => ['zh-CN' => '茶汤/糖度/冰量一次到位，轻摇 6–8 下。', 'es-ES' => 'Agregue té, dulzor y hielo. Agite suavemente 6-8 veces.'], 'items' => []],
        'topping' => ['tip' => ['zh-CN' => '顶料摆放居中，杯口擦干净。', 'es-ES' => 'Centre los toppings y limpie el borde del vaso.'], 'items' => []],
    ];

    foreach ($recipe_steps as $step) {
        $category = $step['step_category']; // 'base', 'mixing', or 'topping'
        if (isset($structured_recipe[$category])) {
            $structured_recipe[$category]['items'][] = [
                'order' => count($structured_recipe[$category]['items']) + 1,
                'title' => ['zh-CN' => $step['material_zh'], 'es-ES' => $step['material_es']],
                'qty'   => rtrim(rtrim(number_format($step['quantity'], 2), '0'), '.'),
                'unit'  => ['zh-CN' => $step['unit_zh'], 'es-ES' => $step['unit_es']]
            ];
        }
    }

    return [
        'product' => $product_data,
        'recipe'  => $structured_recipe
    ];
}