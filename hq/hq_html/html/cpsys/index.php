<?php
/**
 * Toptea HQ - cpsys
 * Main Entry Point
 * Engineer: Gemini | Date: 2025-10-28 | Revision: 12.6 (Redemption Rules Route)
 */
require_once realpath(__DIR__ . '/../../core/auth_core.php');
header('Content-Type: text/html; charset=utf-8');
require_once realpath(__DIR__ . '/../../core/config.php');
require_once APP_PATH . '/helpers/kds_helper.php'; // Includes redemption rule functions now
require_once APP_PATH . '/helpers/auth_helper.php';

if (!isset($pdo)) {
    die("Critical Error: Core configuration could not be loaded.");
}

$page = $_GET['page'] ?? 'dashboard';
$page_js = null;

if (($_SESSION['role_id'] ?? null) !== ROLE_SUPER_ADMIN && !in_array($page, ['dashboard', 'profile', 'product_list'])) {
     $page = 'access_denied';
}

// Function to get redemption rules (moved here for route)
function getAllRedemptionRules(PDO $pdo): array {
    $sql = "SELECT r.*, p.promo_name 
            FROM pos_point_redemption_rules r
            LEFT JOIN pos_promotions p ON r.reward_promo_id = p.id
            WHERE r.deleted_at IS NULL
            ORDER BY r.points_required ASC, r.id ASC";
    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Handle case where table might not exist yet during initial setup
        if ($e->getCode() == '42S02') { // Base table or view not found
             error_log("Warning: pos_point_redemption_rules table not found when fetching rules for routing. " . $e->getMessage());
             return []; // Return empty array to avoid breaking the page load
        }
        throw $e; // Re-throw other errors
    }
}


switch ($page) {
    case 'dashboard':
        $page_title = '仪表盘';
        $content_view = APP_PATH . '/views/cpsys/dashboard_view.php';
        break;

    // ... [Other cases remain unchanged] ...
    case 'product_list':
        $page_title = '产品配方管理';
        $products = getAllProducts($pdo);
        $content_view = APP_PATH . '/views/cpsys/product_list_view.php';
        $page_js = 'product_list.js';
        break;

    case 'product_management':
        $page_title = '创建新配方';
        $cup_options = getAllCups($pdo);
        $status_options = getAllStatuses($pdo);
        $material_options = getAllMaterials($pdo);
        $unit_options = getAllUnits($pdo);
        $sweetness_options_all = getAllActiveSweetnessOptions($pdo);
        $ice_options_all = getAllActiveIceOptions($pdo);
        $next_sku = getNextAvailableCustomCode($pdo, 'kds_products', 'product_sku', 100);
        $content_view = APP_PATH . '/views/cpsys/product_management_view.php';
        $page_js = 'product_management.js';
        break;

    case 'product_edit':
        $page_title = '编辑配方';
        $product_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$product_id) { die("无效的配方ID。"); }
        $product_data = getProductById($pdo, $product_id);
        if (!$product_data) { die("未找到指定的配方。"); }
        $product_recipes = getRecipesByProductId($pdo, $product_id);
        $selected_options = getProductSelectedOptions($pdo, $product_id);
        $product_adjustments = getProductAdjustments($pdo, $product_id);
        $cup_options = getAllCups($pdo);
        $status_options = getAllStatuses($pdo);
        $material_options = getAllMaterials($pdo);
        $unit_options = getAllUnits($pdo);
        $sweetness_options_all = getAllActiveSweetnessOptions($pdo);
        $ice_options_all = getAllActiveIceOptions($pdo);
        $content_view = APP_PATH . '/views/cpsys/product_edit_view.php';
        $page_js = 'product_edit.js';
        break;

    case 'expiry_management':
        $page_title = '效期管理';
        $expiry_items = getAllExpiryItems($pdo);
        $content_view = APP_PATH . '/views/cpsys/expiry_management_view.php';
        break;

    case 'warehouse_stock_management':
        $page_title = '总仓库存';
        $stock_items = getWarehouseStock($pdo);
        $content_view = APP_PATH . '/views/cpsys/warehouse_stock_management_view.php';
        $page_js = 'warehouse_stock_management.js';
        break;
        
    case 'stock_allocation':
        $page_title = '库存调拨';
        $stores = getAllStores($pdo);
        $materials = getAllMaterials($pdo);
        $content_view = APP_PATH . '/views/cpsys/stock_allocation_view.php';
        $page_js = 'stock_allocation.js';
        break;

    case 'store_stock_view': 
        $page_title = '门店库存';
        $stock_data = getAllStoreStock($pdo);
        $content_view = APP_PATH . '/views/cpsys/store_stock_view.php';
        break;

    case 'pos_menu_management':
        $page_title = 'POS 管理 - 菜单管理';
        $menu_items = getAllMenuItems($pdo);
        $pos_categories = getAllPosCategories($pdo);
        $content_view = APP_PATH . '/views/cpsys/pos_menu_management_view.php';
        $page_js = 'pos_menu_management.js';
        break;
        
    case 'pos_variants_management':
        $item_id = filter_input(INPUT_GET, 'item_id', FILTER_VALIDATE_INT);
        if (!$item_id) { die("无效的商品ID。"); }
        $menu_item = getMenuItemById($pdo, $item_id);
        if (!$menu_item) { die("未找到指定的商品。"); }
        $page_title = 'POS 管理 - 管理规格';
        $variants = getAllVariantsByMenuItemId($pdo, $item_id);
        $recipes = getAllProductRecipesForSelect($pdo);
        $content_view = APP_PATH . '/views/cpsys/pos_variants_management_view.php';
        $page_js = 'pos_variants_management.js';
        break;

    case 'pos_category_management':
        $page_title = '字典管理 - POS分类';
        $pos_categories = getAllPosCategories($pdo);
        $content_view = APP_PATH . '/views/cpsys/pos_category_management_view.php';
        $page_js = 'pos_category_management.js';
        break;
        
    case 'pos_invoice_list':
        $page_title = 'POS 管理 - 票据查询';
        $invoices = getAllInvoices($pdo);
        $content_view = APP_PATH . '/views/cpsys/pos_invoice_list_view.php';
        break;
    
    case 'pos_invoice_detail':
        $invoice_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$invoice_id) { die("无效的票据ID。"); }
        $invoice_data = getInvoiceDetails($pdo, $invoice_id);
        if (!$invoice_data) { die("未找到指定的票据。"); }
        $page_title = 'POS 票据详情: ' . htmlspecialchars($invoice_data['series'] . '-' . $invoice_data['number']);
        $content_view = APP_PATH . '/views/cpsys/pos_invoice_detail_view.php';
        $page_js = 'pos_invoice_management.js';
        break;

    case 'pos_promotion_management':
        $page_title = 'POS 管理 - 营销活动管理';
        $promotions = getAllPromotions($pdo);
        $menu_items_for_select = getAllMenuItemsForSelect($pdo);
        echo "<script>window.menuItemsForSelect = " . json_encode($menu_items_for_select) . ";</script>";
        $content_view = APP_PATH . '/views/cpsys/pos_promotion_management_view.php';
        $page_js = 'pos_promotion_management.js';
        break;

    case 'pos_eod_reports':
        $page_title = 'POS 管理 - 日结报告';
        $eod_reports = getAllEodReports($pdo);
        $content_view = APP_PATH . '/views/cpsys/pos_eod_reports_view.php';
        break;

    case 'pos_member_level_management':
        $page_title = 'POS 管理 - 会员等级';
        $member_levels = getAllMemberLevels($pdo);
        $promotions_for_select = getAllPromotions($pdo); 
        $content_view = APP_PATH . '/views/cpsys/pos_member_level_management_view.php';
        $page_js = 'pos_member_level_management.js';
        break;

    case 'pos_member_management':
        $page_title = 'POS 管理 - 会员列表';
        $members = getAllMembers($pdo);
        $member_levels = getAllMemberLevels($pdo); 
        $content_view = APP_PATH . '/views/cpsys/pos_member_management_view.php';
        $page_js = 'pos_member_management.js';
        break;

    case 'pos_member_settings':
        $page_title = 'POS 管理 - 会员积分设置';
        $content_view = APP_PATH . '/views/cpsys/pos_member_settings_view.php';
        $page_js = 'pos_member_settings.js'; 
        break;

    // --- 核心修复：确保路由存在 ---
    case 'pos_point_redemption_rules':
        $page_title = 'POS 管理 - 积分兑换规则';
        $rules = getAllRedemptionRules($pdo); // Fetch rules for the view
        $promotions_for_select = getAllPromotions($pdo); // For the reward dropdown in the form
        $content_view = APP_PATH . '/views/cpsys/pos_point_redemption_rules_view.php';
        $page_js = 'pos_point_redemption_rules.js'; // Requires a new JS file
        break;
    // --- End Route Fix ---

    // ... [Dictionary and System Settings cases remain unchanged] ...
    case 'cup_management':
        $page_title = '字典管理 - 杯型';
        $cups = getAllCups($pdo);
        $content_view = APP_PATH . '/views/cpsys/cup_management_view.php';
        $page_js = 'cup_management.js';
        break;

    case 'material_management':
        $page_title = '字典管理 - 物料';
        $materials = getAllMaterials($pdo);
        $unit_options = getAllUnits($pdo);
        $content_view = APP_PATH . '/views/cpsys/material_management_view.php';
        $page_js = 'material_management.js';
        break;

    case 'unit_management':
        $page_title = '字典管理 - 单位';
        $units = getAllUnits($pdo);
        $content_view = APP_PATH . '/views/cpsys/unit_management_view.php';
        $page_js = 'unit_management.js';
        break;

    case 'ice_option_management':
        $page_title = '字典管理 - 冰量选项';
        $ice_options = getAllIceOptions($pdo);
        $content_view = APP_PATH . '/views/cpsys/ice_option_management_view.php';
        $page_js = 'ice_option_management.js';
        break;

    case 'sweetness_option_management':
        $page_title = '字典管理 - 甜度选项';
        $sweetness_options = getAllSweetnessOptions($pdo);
        $content_view = APP_PATH . '/views/cpsys/sweetness_option_management_view.php';
        $page_js = 'sweetness_option_management.js';
        break;

    case 'user_management':
        $page_title = '系统设置 - 用户管理';
        $users = getAllUsers($pdo);
        $roles = getAllRoles($pdo);
        $content_view = APP_PATH . '/views/cpsys/user_management_view.php';
        $page_js = 'user_management.js';
        break;

    case 'store_management':
        $page_title = '系统设置 - 门店管理';
        $stores = getAllStores($pdo);
        $content_view = APP_PATH . '/views/cpsys/store_management_view.php';
        $page_js = 'store_management.js';
        break;

    case 'kds_user_management':
        $page_title = 'KDS 账户管理';
        $store_id = filter_input(INPUT_GET, 'store_id', FILTER_VALIDATE_INT);
        if (!$store_id) { die("无效的门店ID。"); }
        $store_data = getStoreById($pdo, $store_id);
        if (!$store_data) { die("未找到指定的门店。"); }
        $kds_users = getAllKdsUsersByStoreId($pdo, $store_id);
        $content_view = APP_PATH . '/views/cpsys/kds_user_management_view.php';
        $page_js = 'kds_user_management.js';
        break;

    case 'profile':
        $page_title = '个人资料';
        $current_user = getUserById($pdo, $_SESSION['user_id']);
        $content_view = APP_PATH . '/views/cpsys/profile_view.php';
        $page_js = 'profile.js';
        break;
        
    case 'access_denied':
        $page_title = '访问被拒绝';
        $content_view = APP_PATH . '/views/cpsys/access_denied_view.php';
        break;

    default:
        http_response_code(404);
        $page_title = '页面未找到';
        $content_view = null; // Set to null to trigger error message below
        // Fall through intentional to use the layout's error handling
}

// --- Render Layout ---
if (isset($content_view) || $page === 'access_denied' || http_response_code() === 404) {
    if ($page !== 'access_denied' && http_response_code() !== 404 && (!isset($content_view) || !file_exists($content_view))) {
         // If a known page has a missing view file, set a specific error view
         $page_title = '错误';
         $content_view = APP_PATH . '/views/cpsys/error_view.php'; // Assuming you have/create an error view
         // You might want to pass the expected view path to the error view for debugging
         $error_details = 'Expected view file not found: ' . ($content_view ?? 'N/A');
    } elseif (http_response_code() === 404 && !isset($content_view)) {
         // Handle 404 specifically if no content view was set by the switch
         $page_title = '页面未找到';
         $content_view = APP_PATH . '/views/cpsys/404_view.php'; // Assuming you have/create a 404 view
    }
    // Now include the main layout which handles displaying the content_view or error view
    include APP_PATH . '/views/cpsys/layouts/main.php';

} else {
    // This case should ideally not be reached if default handles 404 correctly
    die("Critical Error: No view file determined and not a recognized error state.");
}

