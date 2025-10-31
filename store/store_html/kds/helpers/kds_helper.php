<?php
/**
 * Toptea Store - KDS
 * KDS Data Helper Functions
 * Engineer: Gemini | Date: 2025-10-31 | Revision: 3.0 (Dynamic Recipe Engine Integration)
 */

/**
 * Fetches a complete, structured recipe by parsing a dynamic code (P-A-M-T).
 * - If only P-Code is given, returns base product info, all available options, and the standard recipe.
 * - If a full code is given, returns the final recipe after applying adjustment rules.
 */
function getDynamicSopDataByCode(PDO $pdo, string $code): ?array
{
    $parts = explode('-', strtoupper($code));
    $p_code = $parts[0] ?? null;
    $a_code = $parts[1] ?? null;
    $m_code = $parts[2] ?? null;
    $t_code = $parts[3] ?? null;

    if (!$p_code) {
        return null;
    }

    // 1. Fetch the base product data using P-Code
    $product_sql = "
        SELECT
            p.id,
            p.product_code,
            pt_zh.product_name AS name_zh,
            pt_es.product_name AS name_es,
            ps.status_name AS status_name_zh
        FROM kds_products p
        LEFT JOIN kds_product_translations pt_zh ON p.id = pt_zh.product_id AND pt_zh.language_code = 'zh-CN'
        LEFT JOIN kds_product_translations pt_es ON p.id = pt_es.product_id AND pt_es.language_code = 'es-ES'
        LEFT JOIN kds_product_statuses ps ON p.status_id = ps.id
        WHERE p.product_code = ? AND p.deleted_at IS NULL AND p.is_active = 1
    ";
    $stmt = $pdo->prepare($product_sql);
    $stmt->execute([$p_code]);
    $product_data = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product_data) {
        return null;
    }
    $product_id = $product_data['id'];
    // Placeholder for Spanish status name
    $product_data['status_name_es'] = $product_data['status_name_zh'];

    // 2. Fetch Base Recipe (always needed)
    $base_recipe_sql = "
        SELECT r.material_id, r.quantity, r.unit_id, mt_zh.material_name AS material_zh, mt_es.material_name AS material_es, ut_zh.unit_name AS unit_zh, ut_es.unit_name AS unit_es
        FROM kds_product_recipes r
        JOIN kds_material_translations mt_zh ON r.material_id = mt_zh.material_id AND mt_zh.language_code = 'zh-CN'
        JOIN kds_material_translations mt_es ON r.material_id = mt_es.material_id AND mt_es.language_code = 'es-ES'
        JOIN kds_unit_translations ut_zh ON r.unit_id = ut_zh.unit_id AND ut_zh.language_code = 'zh-CN'
        JOIN kds_unit_translations ut_es ON r.unit_id = ut_es.unit_id AND ut_es.language_code = 'es-ES'
        WHERE r.product_id = ? ORDER BY r.id ASC
    ";
    $stmt_recipe = $pdo->prepare($base_recipe_sql);
    $stmt_recipe->execute([$product_id]);
    $base_recipe_rows = $stmt_recipe->fetchAll(PDO::FETCH_ASSOC | PDO::FETCH_UNIQUE);


    // 3. Determine response type based on input code
    if (!$a_code && !$m_code && !$t_code) {
        // --- P-Code ONLY: Return base info, options, and standard recipe ---
        $options = [
            'cups' => [],
            'ice_options' => [],
            'sweetness_options' => []
        ];

        // Fetch available cups via POS tables
        $cup_sql = "SELECT DISTINCT c.id, c.cup_code, c.cup_name FROM kds_cups c JOIN pos_item_variants piv ON c.id = piv.cup_id JOIN pos_menu_items pmi ON piv.menu_item_id = pmi.id WHERE pmi.product_code = ?";
        $stmt_cups = $pdo->prepare($cup_sql);
        $stmt_cups->execute([$p_code]);
        $options['cups'] = $stmt_cups->fetchAll(PDO::FETCH_ASSOC);

        // Fetch available ice options
        $ice_sql = "SELECT io.id, io.ice_code, iot.ice_option_name AS name_zh, iot.sop_description AS sop_zh FROM kds_product_ice_options pio JOIN kds_ice_options io ON pio.ice_option_id = io.id JOIN kds_ice_option_translations iot ON io.id = iot.ice_option_id WHERE pio.product_id = ? AND iot.language_code = 'zh-CN'";
        $stmt_ice = $pdo->prepare($ice_sql);
        $stmt_ice->execute([$product_id]);
        $options['ice_options'] = $stmt_ice->fetchAll(PDO::FETCH_ASSOC);
        
        // Fetch available sweetness options
        $sweet_sql = "SELECT so.id, so.sweetness_code, sot.sweetness_option_name AS name_zh, sot.sop_description AS sop_zh FROM kds_product_sweetness_options pso JOIN kds_sweetness_options so ON pso.sweetness_option_id = so.id JOIN kds_sweetness_option_translations sot ON so.id = sot.sweetness_option_id WHERE pso.product_id = ? AND sot.language_code = 'zh-CN'";
        $stmt_sweet = $pdo->prepare($sweet_sql);
        $stmt_sweet->execute([$product_id]);
        $options['sweetness_options'] = $stmt_sweet->fetchAll(PDO::FETCH_ASSOC);

        return [
            'type' => 'base_info',
            'product' => $product_data,
            'options' => $options,
            'recipe' => array_values($base_recipe_rows)
        ];

    } else {
        // --- Full P-A-M-T Code: Apply adjustments and return final recipe ---
        $cup_id = $a_code ? $pdo->query("SELECT id FROM kds_cups WHERE cup_code = '{$a_code}'")->fetchColumn() : null;
        $ice_id = $m_code ? $pdo->query("SELECT id FROM kds_ice_options WHERE ice_code = '{$m_code}'")->fetchColumn() : null;
        $sweetness_id = $t_code ? $pdo->query("SELECT id FROM kds_sweetness_options WHERE sweetness_code = '{$t_code}'")->fetchColumn() : null;
        
        $adj_sql = "SELECT material_id, quantity, unit_id FROM kds_recipe_adjustments WHERE product_id = ? AND (cup_id = ? OR cup_id IS NULL) AND (ice_option_id = ? OR ice_option_id IS NULL) AND (sweetness_option_id = ? OR sweetness_option_id IS NULL)";
        $stmt_adj = $pdo->prepare($adj_sql);
        $stmt_adj->execute([$product_id, $cup_id, $ice_id, $sweetness_id]);
        $adjustments = $stmt_adj->fetchAll(PDO::FETCH_ASSOC);

        // Apply adjustments - This is a simplified override. More complex logic could be added here.
        foreach ($adjustments as $adj) {
            if (isset($base_recipe_rows[$adj['material_id']])) {
                $base_recipe_rows[$adj['material_id']]['quantity'] = $adj['quantity'];
                // Optionally update unit as well if the rule specifies it
                 if ($adj['unit_id'] != $base_recipe_rows[$adj['material_id']]['unit_id']) {
                    $new_unit_stmt = $pdo->prepare("SELECT ut_zh.unit_name AS unit_zh, ut_es.unit_name AS unit_es FROM kds_unit_translations ut_zh JOIN kds_unit_translations ut_es ON ut_zh.unit_id = ut_es.unit_id WHERE ut_zh.unit_id = ? AND ut_zh.language_code = 'zh-CN' AND ut_es.language_code = 'es-ES'");
                    $new_unit_stmt->execute([$adj['unit_id']]);
                    $new_unit_names = $new_unit_stmt->fetch();
                    if($new_unit_names) {
                        $base_recipe_rows[$adj['material_id']]['unit_id'] = $adj['unit_id'];
                        $base_recipe_rows[$adj['material_id']]['unit_zh'] = $new_unit_names['unit_zh'];
                        $base_recipe_rows[$adj['material_id']]['unit_es'] = $new_unit_names['unit_es'];
                    }
                 }
            }
        }
        
        // Add the cup name to the product data for display
        if($cup_id) {
            $product_data['cup_name'] = $pdo->query("SELECT cup_name FROM kds_cups WHERE id = {$cup_id}")->fetchColumn();
        }


        return [
            'type' => 'adjusted_recipe',
            'product' => $product_data,
            'recipe' => array_values($base_recipe_rows)
        ];
    }
}