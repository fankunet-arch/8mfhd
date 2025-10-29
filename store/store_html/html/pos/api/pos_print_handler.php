<?php
/**
 * TopTea POS - Print Handler API
 * Version: 2.2.0
 * Engineer: Gemini | Date: 2025-10-29
 * Implements 7.A.3 - Step 2.1: Template Synchronization Service for POS
 */

require_once realpath(__DIR__ . '/../../../pos_backend/core/config.php');

header('Content-Type: application/json; charset=utf-8');

function send_json_response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

// SIMULATION: In a real system, these would come from the logged-in user's session
$store_id = 1; 
$user_id = 1;

$action = $_GET['action'] ?? null;

try {
    switch ($action) {
        // action=get_templates: 获取所有当前门店可用的打印模板
        case 'get_templates':
            $stmt = $pdo->prepare(
                "SELECT template_type, template_content 
                 FROM pos_print_templates 
                 WHERE (store_id = :store_id OR store_id IS NULL) AND is_active = 1
                 ORDER BY store_id DESC" // 店铺专用模板优先
            );
            $stmt->execute([':store_id' => $store_id]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 去重，确保每个 template_type 只取一个最高优先级的模板 (店铺专用 > 全局)
            $templates = [];
            foreach ($results as $row) {
                if (!isset($templates[$row['template_type']])) {
                    $templates[$row['template_type']] = json_decode($row['template_content'], true);
                }
            }

            send_json_response('success', 'Templates loaded.', $templates);
            break;
        
        // action=get_eod_print_data: 获取指定日结报告的打印数据
        case 'get_eod_print_data':
            $report_id = (int)($_GET['report_id'] ?? 0);
            if (!$report_id) {
                http_response_code(400);
                send_json_response('error', '无效的报告ID。');
            }

            $stmt = $pdo->prepare(
                "SELECT r.*, s.store_name, u.display_name as user_name
                 FROM pos_eod_reports r
                 LEFT JOIN kds_stores s ON r.store_id = s.id
                 LEFT JOIN cpsys_users u ON r.user_id = u.id
                 WHERE r.id = ? AND r.store_id = ?"
            );
            $stmt->execute([$report_id, $store_id]);
            $report_data = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$report_data) {
                http_response_code(404);
                send_json_response('error', '未找到指定的日结报告。');
            }

            // 添加动态打印时间
            $report_data['print_time'] = (new DateTime('now', new DateTimeZone('Europe/Madrid')))->format('Y-m-d H:i:s');
            
            // 格式化数字
            foreach(['system_gross_sales', 'system_discounts', 'system_net_sales', 'system_tax', 'system_cash', 'system_card', 'system_platform', 'counted_cash', 'cash_discrepancy'] as $key) {
                if (isset($report_data[$key])) {
                    $report_data[$key] = number_format((float)$report_data[$key], 2, '.', '');
                }
            }

            send_json_response('success', 'EOD report data for printing retrieved.', $report_data);
            break;

        default:
            http_response_code(400);
            send_json_response('error', '无效的操作请求。');
    }
} catch (Exception $e) {
    error_log("Print Handler API Error: " . $e->getMessage());
    http_response_code(500);
    send_json_response('error', '服务器内部错误。');
}