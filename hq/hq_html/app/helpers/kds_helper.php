<?php
/**
 * Toptea HQ - cpsys
 * KDS Data Helper Functions
 * Engineer: Gemini | Date: 2025-10-28 | Revision: 11.5 (Member Management Functions)
 */

// --- Stock System Functions ---
function getWarehouseStock(PDO $pdo): array {
    $sql = "
        SELECT 
            m.id as material_id,
            mt.material_name,
            ut.unit_name AS base_unit_name,
            COALESCE(ws.quantity, 0) as quantity
        FROM kds_materials m
        JOIN kds_material_translations mt ON m.id = mt.material_id AND mt.language_code = 'zh-CN'
        JOIN kds_unit_translations ut ON m.base_unit_id = ut.unit_id AND ut.language_code = 'zh-CN'
        LEFT JOIN expsys_warehouse_stock ws ON m.id = ws.material_id
        WHERE m.deleted_at IS NULL
        ORDER BY m.material_code ASC
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function getAllStoreStock(PDO $pdo): array {
    $sql = "
        SELECT 
            s.store_name,
            mt.material_name,
            ut.unit_name AS base_unit_name,
            ss.quantity
        FROM expsys_store_stock ss
        JOIN kds_stores s ON ss.store_id = s.id
        JOIN kds_materials m ON ss.material_id = m.id
        JOIN kds_material_translations mt ON m.id = mt.material_id AND mt.language_code = 'zh-CN'
        JOIN kds_unit_translations ut ON m.base_unit_id = ut.unit_id AND ut.language_code = 'zh-CN'
        WHERE s.deleted_at IS NULL AND m.deleted_at IS NULL AND s.is_active = 1
        ORDER BY s.store_code ASC, mt.material_name ASC
    ";
    $stmt = $pdo->query($sql);
    $flat_results = $stmt->fetchAll();

    $grouped_results = [];
    foreach ($flat_results as $row) {
        $grouped_results[$row['store_name']][] = [
            'material_name' => $row['material_name'],
            'quantity' => $row['quantity'],
            'base_unit_name' => $row['base_unit_name']
        ];
    }
    return $grouped_results;
}

// --- Expiry System Functions ---
function getAllExpiryItems(PDO $pdo): array {
    $sql = "
        SELECT 
            e.id, e.batch_code, e.opened_at, e.expires_at, e.status,
            mt.material_name,
            s.store_name
        FROM kds_material_expiries e
        JOIN kds_material_translations mt ON e.material_id = mt.material_id AND mt.language_code = 'zh-CN'
        JOIN kds_stores s ON e.store_id = s.id
        ORDER BY e.expires_at ASC
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}


// --- Functions for Dynamic Adjustments (Newly Added) ---
function getAllActiveIceOptions(PDO $pdo): array {
    $sql = "SELECT i.id, it_zh.ice_option_name AS name_zh, it_zh.sop_description AS sop_zh FROM kds_ice_options i LEFT JOIN kds_ice_option_translations it_zh ON i.id = it_zh.ice_option_id AND it_zh.language_code = 'zh-CN' WHERE i.deleted_at IS NULL ORDER BY i.ice_code ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
function getAllActiveSweetnessOptions(PDO $pdo): array {
    $sql = "SELECT s.id, st_zh.sweetness_option_name AS name_zh, st_zh.sop_description AS sop_zh FROM kds_sweetness_options s LEFT JOIN kds_sweetness_option_translations st_zh ON s.id = st_zh.sweetness_option_id AND st_zh.language_code = 'zh-CN' WHERE s.deleted_at IS NULL ORDER BY s.sweetness_code ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}


// --- Sweetness Option Management Functions ---
function getAllSweetnessOptions(PDO $pdo): array {
    $sql = "
        SELECT 
            s.id, s.sweetness_code,
            st_zh.sweetness_option_name AS name_zh, 
            st_es.sweetness_option_name AS name_es,
            st_zh.sop_description AS sop_zh
        FROM kds_sweetness_options s 
        LEFT JOIN kds_sweetness_option_translations st_zh ON s.id = st_zh.sweetness_option_id AND st_zh.language_code = 'zh-CN' 
        LEFT JOIN kds_sweetness_option_translations st_es ON s.id = st_es.sweetness_option_id AND st_es.language_code = 'es-ES'
        WHERE s.deleted_at IS NULL 
        ORDER BY s.sweetness_code ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
function getSweetnessOptionById(PDO $pdo, int $id) {
    $sql = "
        SELECT 
            s.id, s.sweetness_code,
            st_zh.sweetness_option_name AS name_zh, 
            st_es.sweetness_option_name AS name_es,
            st_zh.sop_description AS sop_zh,
            st_es.sop_description AS sop_es
        FROM kds_sweetness_options s 
        LEFT JOIN kds_sweetness_option_translations st_zh ON s.id = st_zh.sweetness_option_id AND st_zh.language_code = 'zh-CN' 
        LEFT JOIN kds_sweetness_option_translations st_es ON s.id = st_es.sweetness_option_id AND st_es.language_code = 'es-ES' 
        WHERE s.id = ? AND s.deleted_at IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// --- Ice Option Management Functions ---
function getAllIceOptions(PDO $pdo): array {
    $sql = "
        SELECT 
            i.id, i.ice_code,
            it_zh.ice_option_name AS name_zh, 
            it_es.ice_option_name AS name_es,
            it_zh.sop_description AS sop_zh
        FROM kds_ice_options i 
        LEFT JOIN kds_ice_option_translations it_zh ON i.id = it_zh.ice_option_id AND it_zh.language_code = 'zh-CN' 
        LEFT JOIN kds_ice_option_translations it_es ON i.id = it_es.ice_option_id AND it_es.language_code = 'es-ES'
        WHERE i.deleted_at IS NULL 
        ORDER BY i.ice_code ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
function getIceOptionById(PDO $pdo, int $id) {
    $sql = "
        SELECT 
            i.id, i.ice_code,
            it_zh.ice_option_name AS name_zh, 
            it_es.ice_option_name AS name_es,
            it_zh.sop_description AS sop_zh,
            it_es.sop_description AS sop_es
        FROM kds_ice_options i 
        LEFT JOIN kds_ice_option_translations it_zh ON i.id = it_zh.ice_option_id AND it_zh.language_code = 'zh-CN' 
        LEFT JOIN kds_ice_option_translations it_es ON i.id = it_es.ice_option_id AND it_es.language_code = 'es-ES' 
        WHERE i.id = ? AND i.deleted_at IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// --- Unit Management Functions ---
function getAllUnits(PDO $pdo): array {
    $sql = "SELECT u.id, u.unit_code, ut_zh.unit_name AS name_zh, ut_es.unit_name AS name_es FROM kds_units u LEFT JOIN kds_unit_translations ut_zh ON u.id = ut_zh.unit_id AND ut_zh.language_code = 'zh-CN' LEFT JOIN kds_unit_translations ut_es ON u.id = ut_es.unit_id AND ut_es.language_code = 'es-ES' WHERE u.deleted_at IS NULL ORDER BY u.unit_code ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
function getUnitById(PDO $pdo, int $id) {
    $sql = "SELECT u.id, u.unit_code, ut_zh.unit_name AS name_zh, ut_es.unit_name AS name_es FROM kds_units u LEFT JOIN kds_unit_translations ut_zh ON u.id = ut_zh.unit_id AND ut_zh.language_code = 'zh-CN' LEFT JOIN kds_unit_translations ut_es ON u.id = ut_es.unit_id AND ut_es.language_code = 'es-ES' WHERE u.id = ? AND u.deleted_at IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// --- Material Management Functions ---
function getAllMaterials(PDO $pdo): array {
    $sql = "
        SELECT 
            m.id, m.material_code, m.material_type, m.conversion_rate,
            mt_zh.material_name AS name_zh, 
            mt_es.material_name AS name_es,
            ut_base_zh.unit_name AS base_unit_name,
            ut_large_zh.unit_name AS large_unit_name
        FROM kds_materials m
        LEFT JOIN kds_material_translations mt_zh ON m.id = mt_zh.material_id AND mt_zh.language_code = 'zh-CN'
        LEFT JOIN kds_material_translations mt_es ON m.id = mt_es.material_id AND mt_es.language_code = 'es-ES'
        LEFT JOIN kds_unit_translations ut_base_zh ON m.base_unit_id = ut_base_zh.unit_id AND ut_base_zh.language_code = 'zh-CN'
        LEFT JOIN kds_unit_translations ut_large_zh ON m.large_unit_id = ut_large_zh.unit_id AND ut_large_zh.language_code = 'zh-CN'
        WHERE m.deleted_at IS NULL 
        ORDER BY m.material_code ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
function getMaterialById(PDO $pdo, int $id) {
    $sql = "
        SELECT 
            m.id, m.material_code, m.material_type, m.base_unit_id, m.large_unit_id, m.conversion_rate,
            m.expiry_rule_type, m.expiry_duration,
            mt_zh.material_name AS name_zh, 
            mt_es.material_name AS name_es,
            ut_base.unit_name AS base_unit_name,
            ut_large.unit_name AS large_unit_name
        FROM kds_materials m 
        LEFT JOIN kds_material_translations mt_zh ON m.id = mt_zh.material_id AND mt_zh.language_code = 'zh-CN' 
        LEFT JOIN kds_material_translations mt_es ON m.id = mt_es.material_id AND mt_es.language_code = 'es-ES'
        LEFT JOIN kds_unit_translations ut_base ON m.base_unit_id = ut_base.unit_id AND ut_base.language_code = 'zh-CN'
        LEFT JOIN kds_unit_translations ut_large ON m.large_unit_id = ut_large.unit_id AND ut_large.language_code = 'zh-CN'
        WHERE m.id = ? AND m.deleted_at IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// --- Cup Management Functions ---
function getAllCups(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, cup_code, cup_name, sop_description_zh FROM kds_cups WHERE deleted_at IS NULL ORDER BY cup_code ASC");
    return $stmt->fetchAll();
}
function getCupById(PDO $pdo, int $id) {
    $stmt = $pdo->prepare("SELECT id, cup_code, cup_name, sop_description_zh, sop_description_es FROM kds_cups WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// --- Product Management & Other Stable Functions ---
function getAllStatuses(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, status_name FROM kds_product_statuses ORDER BY status_code ASC");
    return $stmt->fetchAll();
}
function getAllProducts(PDO $pdo): array {
    $sql = "SELECT p.id, p.product_sku, pt_zh.product_name AS name_zh, pt_es.product_name AS name_es, c.cup_name, ps.status_name, p.is_active, p.created_at FROM kds_products p LEFT JOIN kds_product_translations pt_zh ON p.id = pt_zh.product_id AND pt_zh.language_code = 'zh-CN' LEFT JOIN kds_product_translations pt_es ON p.id = pt_es.product_id AND pt_es.language_code = 'es-ES' LEFT JOIN kds_cups c ON p.cup_id = c.id LEFT JOIN kds_product_statuses ps ON p.status_id = ps.id WHERE p.deleted_at IS NULL ORDER BY p.product_sku ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
function getProductById(PDO $pdo, int $id) {
    $sql = "SELECT p.*, pt_zh.product_name AS name_zh, pt_es.product_name AS name_es FROM kds_products p LEFT JOIN kds_product_translations pt_zh ON p.id = pt_zh.product_id AND pt_zh.language_code = 'zh-CN' LEFT JOIN kds_product_translations pt_es ON p.id = pt_es.product_id AND pt_es.language_code = 'es-ES' WHERE p.id = ? AND p.deleted_at IS NULL";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$id]);
    return $stmt->fetch();
}
function getRecipesByProductId(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("SELECT * FROM kds_product_recipes WHERE product_id = ? ORDER BY step_category, sort_order ASC");
    $stmt->execute([$id]);
    return $stmt->fetchAll();
}
function getNextAvailableCustomCode(PDO $pdo, string $tableName, string $codeColumnName, int $start_from = 1): int {
    $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName);
    $codeColumnName = preg_replace('/[^a-zA-Z0-9_]/', '', $codeColumnName);
    $sql = "SELECT {$codeColumnName} FROM {$tableName} WHERE deleted_at IS NULL AND {$codeColumnName} >= :start_from ORDER BY {$codeColumnName} ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':start_from' => $start_from]);
    $existing_codes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $i = $start_from;
    while (in_array($i, $existing_codes)) {
        $i++;
    }
    return $i;
}
function getAllStores(PDO $pdo): array {
    $stmt = $pdo->query("SELECT * FROM kds_stores WHERE deleted_at IS NULL ORDER BY store_code ASC");
    return $stmt->fetchAll();
}
function getStoreById(PDO $pdo, int $id) {
    $stmt = $pdo->prepare("SELECT * FROM kds_stores WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    return $stmt->fetch();
}
function getAllKdsUsersByStoreId(PDO $pdo, int $store_id): array {
    $stmt = $pdo->prepare("SELECT id, username, display_name, role, is_active, last_login_at FROM kds_users WHERE store_id = ? AND deleted_at IS NULL ORDER BY id ASC");
    $stmt->execute([$store_id]);
    return $stmt->fetchAll();
}
function getKdsUserById(PDO $pdo, int $id) {
    $stmt = $pdo->prepare("SELECT id, username, display_name, role, is_active, store_id FROM kds_users WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    return $stmt->fetch();
}
function getProductSelectedOptions(PDO $pdo, int $product_id): array {
    $options = ['sweetness_ids' => [], 'ice_ids' => []];
    $stmt_sweetness = $pdo->prepare("SELECT sweetness_option_id FROM kds_product_sweetness_options WHERE product_id = ?");
    $stmt_sweetness->execute([$product_id]);
    $options['sweetness_ids'] = $stmt_sweetness->fetchAll(PDO::FETCH_COLUMN, 0);
    $stmt_ice = $pdo->prepare("SELECT ice_option_id FROM kds_product_ice_options WHERE product_id = ?");
    $stmt_ice->execute([$product_id]);
    $options['ice_ids'] = $stmt_ice->fetchAll(PDO::FETCH_COLUMN, 0);
    return $options;
}
function getProductAdjustments(PDO $pdo, int $product_id): array {
    $stmt = $pdo->prepare("SELECT * FROM kds_product_adjustments WHERE product_id = ?");
    $stmt->execute([$product_id]);
    $results = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $results[$row['option_type']][$row['option_id']] = $row;
    }
    return $results;
}

// --- NEW FUNCTION for POS ---
function getAllPosCategories(PDO $pdo): array {
    $stmt = $pdo->query("SELECT * FROM pos_categories WHERE deleted_at IS NULL ORDER BY sort_order ASC, id ASC");
    return $stmt->fetchAll();
}

// --- NEW FUNCTIONS for POS Menu Management ---
function getAllMenuItems(PDO $pdo): array {
    $sql = "
        SELECT 
            mi.id,
            mi.name_zh,
            mi.sort_order,
            mi.is_active,
            pc.name_zh AS category_name_zh,
            GROUP_CONCAT(pv.variant_name_zh SEPARATOR ', ') AS variants
        FROM pos_menu_items mi
        LEFT JOIN pos_categories pc ON mi.pos_category_id = pc.id
        LEFT JOIN pos_item_variants pv ON mi.id = pv.menu_item_id AND pv.deleted_at IS NULL
        WHERE mi.deleted_at IS NULL
        GROUP BY mi.id
        ORDER BY pc.sort_order ASC, mi.sort_order ASC, mi.id ASC
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function getMenuItemById(PDO $pdo, int $id) {
    $stmt = $pdo->prepare("SELECT id, name_zh FROM pos_menu_items WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getAllVariantsByMenuItemId(PDO $pdo, int $menu_item_id): array {
    $sql = "
        SELECT 
            pv.id,
            pv.variant_name_zh,
            pv.price_eur,
            pv.sort_order,
            pv.is_default,
            kp.product_sku,
            kpt.product_name AS recipe_name_zh
        FROM pos_item_variants pv
        JOIN kds_products kp ON pv.product_id = kp.id
        LEFT JOIN kds_product_translations kpt ON kp.id = kpt.product_id AND kpt.language_code = 'zh-CN'
        WHERE pv.menu_item_id = ? AND pv.deleted_at IS NULL
        ORDER BY pv.sort_order ASC, pv.id ASC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$menu_item_id]);
    return $stmt->fetchAll();
}

function getAllProductRecipesForSelect(PDO $pdo): array {
    $sql = "
        SELECT 
            p.id,
            p.product_sku,
            pt.product_name AS name_zh
        FROM kds_products p
        LEFT JOIN kds_product_translations pt ON p.id = pt.product_id AND pt.language_code = 'zh-CN'
        WHERE p.deleted_at IS NULL
        ORDER BY p.product_sku ASC
    ";
    return $pdo->query($sql)->fetchAll();
}

function getAllInvoices(PDO $pdo): array {
    $sql = "
        SELECT 
            pi.id,
            pi.series,
            pi.number,
            pi.issued_at,
            pi.final_total,
            pi.status,
            pi.compliance_system,
            ks.store_name
        FROM pos_invoices pi
        LEFT JOIN kds_stores ks ON pi.store_id = ks.id
        ORDER BY pi.issued_at DESC
    ";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}

function getInvoiceDetails(PDO $pdo, int $invoice_id): ?array {
    $sql = "
        SELECT 
            pi.*,
            ks.store_name,
            ks.tax_id AS issuer_tax_id_snapshot,
            cu.display_name AS cashier_name
        FROM pos_invoices pi
        LEFT JOIN kds_stores ks ON pi.store_id = ks.id
        LEFT JOIN cpsys_users cu ON pi.user_id = cu.id
        WHERE pi.id = ?
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$invoice_id]);
    $invoice = $stmt->fetch();

    if (!$invoice) {
        return null;
    }

    $sql_items = "SELECT * FROM pos_invoice_items WHERE invoice_id = ?";
    $stmt_items = $pdo->prepare($sql_items);
    $stmt_items->execute([$invoice_id]);
    $items = $stmt_items->fetchAll();

    $invoice['items'] = $items;
    
    $invoice['compliance_data_decoded'] = json_decode($invoice['compliance_data'] ?? '[]', true);
    $invoice['payment_summary_decoded'] = json_decode($invoice['payment_summary'] ?? '[]', true);

    return $invoice;
}

function getAllPromotions(PDO $pdo): array {
    $sql = "SELECT id, promo_name, promo_trigger_type, promo_start_date, promo_end_date, promo_is_active FROM pos_promotions ORDER BY promo_priority ASC, id DESC";
    return $pdo->query($sql)->fetchAll();
}

function getPromotionById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM pos_promotions WHERE id = ?");
    $stmt->execute([$id]);
    $promo = $stmt->fetch();
    if ($promo) {
        $promo['promo_conditions'] = json_decode($promo['promo_conditions'], true);
        $promo['promo_actions'] = json_decode($promo['promo_actions'], true);
    }
    return $promo;
}

function getAllMenuItemsForSelect(PDO $pdo): array {
    $sql = "SELECT id, name_zh FROM pos_menu_items WHERE deleted_at IS NULL AND is_active = 1 ORDER BY name_zh ASC";
    return $pdo->query($sql)->fetchAll();
}

function getAllEodReports(PDO $pdo): array {
    $sql = "
        SELECT 
            per.*,
            ks.store_name,
            cu.display_name AS user_name
        FROM pos_eod_reports per
        LEFT JOIN kds_stores ks ON per.store_id = ks.id
        LEFT JOIN cpsys_users cu ON per.user_id = cu.id
        ORDER BY per.report_date DESC, ks.store_code ASC
    ";
    return $pdo->query($sql)->fetchAll();
}

// --- NEW: Member Level & Member Management Functions ---
function getAllMemberLevels(PDO $pdo): array {
    $sql = "
        SELECT 
            pml.*, 
            pp.promo_name 
        FROM pos_member_levels pml
        LEFT JOIN pos_promotions pp ON pml.level_up_promo_id = pp.id
        ORDER BY pml.sort_order ASC, pml.points_threshold ASC
    ";
    return $pdo->query($sql)->fetchAll();
}

function getMemberLevelById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM pos_member_levels WHERE id = ?");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result ?: null;
}

function getAllMembers(PDO $pdo): array {
    $sql = "
        SELECT 
            m.*, 
            ml.level_name_zh 
        FROM pos_members m
        LEFT JOIN pos_member_levels ml ON m.member_level_id = ml.id
        WHERE m.deleted_at IS NULL
        ORDER BY m.id DESC
    ";
    return $pdo->query($sql)->fetchAll();
}

function getMemberById(PDO $pdo, int $id): ?array {
    $stmt = $pdo->prepare("SELECT * FROM pos_members WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$id]);
    $result = $stmt->fetch();
    return $result ?: null;
}