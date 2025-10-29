<?php
/**
 * Toptea HQ - cpsys
 * KDS Data Helper Functions
 * Engineer: Gemini | Date: 2025-10-24 | Revision: 5.1 (Final Feature Completion)
 */

// --- Functions for Dynamic Adjustments (Product Create/Edit) ---
function getAllActiveIceOptions(PDO $pdo): array {
    $sql = "SELECT i.id, it_zh.ice_option_name AS name_zh FROM kds_ice_options i LEFT JOIN kds_ice_option_translations it_zh ON i.id = it_zh.ice_option_id AND it_zh.language_code = 'zh-CN' WHERE i.deleted_at IS NULL ORDER BY i.ice_code ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
function getAllActiveSweetnessOptions(PDO $pdo): array {
    $sql = "SELECT s.id, st_zh.sweetness_option_name AS name_zh FROM kds_sweetness_options s LEFT JOIN kds_sweetness_option_translations st_zh ON s.id = st_zh.sweetness_option_id AND st_zh.language_code = 'zh-CN' WHERE s.deleted_at IS NULL ORDER BY s.sweetness_code ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll();
}
function getProductSelectedOptions(PDO $pdo, int $product_id): array {
    $options = ['sweetness' => [], 'ice' => []];
    $stmt_sweetness = $pdo->prepare("SELECT sweetness_option_id FROM kds_product_sweetness_options WHERE product_id = ?");
    $stmt_sweetness->execute([$product_id]);
    $options['sweetness'] = $stmt_sweetness->fetchAll(PDO::FETCH_COLUMN, 0);

    $stmt_ice = $pdo->prepare("SELECT ice_option_id FROM kds_product_ice_options WHERE product_id = ?");
    $stmt_ice->execute([$product_id]);
    $options['ice'] = $stmt_ice->fetchAll(PDO::FETCH_COLUMN, 0);
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

// --- Sweetness Option Management Functions ---
function getAllSweetnessOptions(PDO $pdo): array {
    $sql = "SELECT s.id, s.sweetness_code, st_zh.sweetness_option_name AS name_zh, st_es.sweetness_option_name AS name_es FROM kds_sweetness_options s LEFT JOIN kds_sweetness_option_translations st_zh ON s.id = st_zh.sweetness_option_id AND st_zh.language_code = 'zh-CN' LEFT JOIN kds_sweetness_option_translations st_es ON s.id = st_es.sweetness_option_id AND st_es.language_code = 'es-ES' WHERE s.deleted_at IS NULL ORDER BY s.sweetness_code ASC";
    $stmt = $pdo->query($sql); return $stmt->fetchAll();
}
function getSweetnessOptionById(PDO $pdo, int $id) {
    $sql = "SELECT s.id, s.sweetness_code, st_zh.sweetness_option_name AS name_zh, st_es.sweetness_option_name AS name_es FROM kds_sweetness_options s LEFT JOIN kds_sweetness_option_translations st_zh ON s.id = st_zh.sweetness_option_id AND st_zh.language_code = 'zh-CN' LEFT JOIN kds_sweetness_option_translations st_es ON s.id = st_es.sweetness_option_id AND st_es.language_code = 'es-ES' WHERE s.id = ? AND s.deleted_at IS NULL";
    $stmt = $pdo->prepare($sql); $stmt->execute([$id]); return $stmt->fetch();
}

// --- Ice Option Management Functions ---
function getAllIceOptions(PDO $pdo): array {
    $sql = "SELECT i.id, i.ice_code, it_zh.ice_option_name AS name_zh, it_es.ice_option_name AS name_es FROM kds_ice_options i LEFT JOIN kds_ice_option_translations it_zh ON i.id = it_zh.ice_option_id AND it_zh.language_code = 'zh-CN' LEFT JOIN kds_ice_option_translations it_es ON i.id = it_es.ice_option_id AND it_es.language_code = 'es-ES' WHERE i.deleted_at IS NULL ORDER BY i.ice_code ASC";
    $stmt = $pdo->query($sql); return $stmt->fetchAll();
}
function getIceOptionById(PDO $pdo, int $id) {
    $sql = "SELECT i.id, i.ice_code, it_zh.ice_option_name AS name_zh, it_es.ice_option_name AS name_es FROM kds_ice_options i LEFT JOIN kds_ice_option_translations it_zh ON i.id = it_zh.ice_option_id AND it_zh.language_code = 'zh-CN' LEFT JOIN kds_ice_option_translations it_es ON i.id = it_es.ice_option_id AND it_es.language_code = 'es-ES' WHERE i.id = ? AND i.deleted_at IS NULL";
    $stmt = $pdo->prepare($sql); $stmt->execute([$id]); return $stmt->fetch();
}

// --- Unit Management Functions ---
function getAllUnits(PDO $pdo): array {
    $sql = "SELECT u.id, u.unit_code, ut_zh.unit_name AS name_zh, ut_es.unit_name AS name_es FROM kds_units u LEFT JOIN kds_unit_translations ut_zh ON u.id = ut_zh.unit_id AND ut_zh.language_code = 'zh-CN' LEFT JOIN kds_unit_translations ut_es ON u.id = ut_es.unit_id AND ut_es.language_code = 'es-ES' WHERE u.deleted_at IS NULL ORDER BY u.unit_code ASC";
    $stmt = $pdo->query($sql); return $stmt->fetchAll();
}
function getUnitById(PDO $pdo, int $id) {
    $sql = "SELECT u.id, u.unit_code, ut_zh.unit_name AS name_zh, ut_es.unit_name AS name_es FROM kds_units u LEFT JOIN kds_unit_translations ut_zh ON u.id = ut_zh.unit_id AND ut_zh.language_code = 'zh-CN' LEFT JOIN kds_unit_translations ut_es ON u.id = ut_es.unit_id AND ut_es.language_code = 'es-ES' WHERE u.id = ? AND u.deleted_at IS NULL";
    $stmt = $pdo->prepare($sql); $stmt->execute([$id]); return $stmt->fetch();
}

// --- Material Management Functions ---
function getAllMaterials(PDO $pdo): array {
    $sql = "SELECT m.id, m.material_code, mt_zh.material_name AS name_zh, mt_es.material_name AS name_es FROM kds_materials m LEFT JOIN kds_material_translations mt_zh ON m.id = mt_zh.material_id AND mt_zh.language_code = 'zh-CN' LEFT JOIN kds_material_translations mt_es ON m.id = mt_es.material_id AND mt_es.language_code = 'es-ES' WHERE m.deleted_at IS NULL ORDER BY m.material_code ASC";
    $stmt = $pdo->query($sql); return $stmt->fetchAll();
}
function getMaterialById(PDO $pdo, int $id) {
    $sql = "SELECT m.id, m.material_code, mt_zh.material_name AS name_zh, mt_es.material_name AS name_es FROM kds_materials m LEFT JOIN kds_material_translations mt_zh ON m.id = mt_zh.material_id AND mt_zh.language_code = 'zh-CN' LEFT JOIN kds_material_translations mt_es ON m.id = mt_es.material_id AND mt_es.language_code = 'es-ES' WHERE m.id = ? AND m.deleted_at IS NULL";
    $stmt = $pdo->prepare($sql); $stmt->execute([$id]); return $stmt->fetch();
}

// --- Cup Management Functions ---
function getAllCups(PDO $pdo): array {
    $stmt = $pdo->query("SELECT id, cup_code, cup_name FROM kds_cups WHERE deleted_at IS NULL ORDER BY cup_code ASC");
    return $stmt->fetchAll();
}
function getCupById(PDO $pdo, int $id) {
    $stmt = $pdo->prepare("SELECT id, cup_code, cup_name FROM kds_cups WHERE id = ? AND deleted_at IS NULL");
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
    $stmt = $pdo->query($sql); return $stmt->fetchAll();
}
function getProductById(PDO $pdo, int $id) {
    $sql = "SELECT p.*, pt_zh.product_name AS name_zh, pt_es.product_name AS name_es FROM kds_products p LEFT JOIN kds_product_translations pt_zh ON p.id = pt_zh.product_id AND pt_zh.language_code = 'zh-CN' LEFT JOIN kds_product_translations pt_es ON p.id = pt_es.product_id AND pt_es.language_code = 'es-ES' WHERE p.id = ? AND p.deleted_at IS NULL";
    $stmt = $pdo->prepare($sql); $stmt->execute([$id]); return $stmt->fetch();
}
function getRecipesByProductId(PDO $pdo, int $id): array {
    $stmt = $pdo->prepare("SELECT * FROM kds_product_recipes WHERE product_id = ? ORDER BY step_category, sort_order ASC");
    $stmt->execute([$id]); return $stmt->fetchAll();
}
function getNextAvailableCustomCode(PDO $pdo, string $tableName, string $codeColumnName, int $start_from = 1): int {
    $tableName = preg_replace('/[^a-zA-Z0-9_]/', '', $tableName); $codeColumnName = preg_replace('/[^a-zA-Z0-9_]/', '', $codeColumnName);
    $sql = "SELECT {$codeColumnName} FROM {$tableName} WHERE deleted_at IS NULL AND {$codeColumnName} >= :start_from ORDER BY {$codeColumnName} ASC";
    $stmt = $pdo->prepare($sql); $stmt->execute([':start_from' => $start_from]);
    $existing_codes = $stmt->fetchAll(PDO::FETCH_COLUMN, 0);
    $i = $start_from;
    while (in_array($i, $existing_codes)) { $i++; }
    return $i;
}

// --- Store Management Functions ---
function getAllStores(PDO $pdo): array { /* ... */ }
function getStoreById(PDO $pdo, int $id) { /* ... */ }

// --- KDS User Management Functions (for cpsys) ---
function getAllKdsUsersByStoreId(PDO $pdo, int $store_id): array { /* ... */ }
function getKdsUserById(PDO $pdo, int $id) { /* ... */ }