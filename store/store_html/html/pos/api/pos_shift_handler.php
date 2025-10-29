<?php
/**
 * TopTea POS - Shift Management API
 * Handles Start, End, and Status checks for user shifts.
 * Engineer: Gemini | Date: 2025-10-29
 */
require_once realpath(__DIR__ . '/../../../pos_backend/core/config.php');
require_once realpath(__DIR__ . '/../../../pos_backend/core/pos_auth_core.php');

header('Content-Type: application/json; charset=utf-8');

function send_json_response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data]);
    exit;
}

// Get user and store context directly from the session for security
$user_id = (int)($_SESSION['pos_user_id'] ?? 0);
$store_id = (int)($_SESSION['pos_store_id'] ?? 0);

if ($user_id === 0 || $store_id === 0) {
    http_response_code(401);
    send_json_response('error', 'Unauthorized: Invalid session data.');
}

$action = $_GET['action'] ?? null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_data = json_decode(file_get_contents('php://input'), true);
    $action = $json_data['action'] ?? $action;
}

try {
    switch ($action) {
        case 'status':
            $stmt = $pdo->prepare("SELECT id, start_time, starting_float FROM pos_shifts WHERE user_id = ? AND store_id = ? AND status = 'ACTIVE' LIMIT 1");
            $stmt->execute([$user_id, $store_id]);
            $active_shift = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($active_shift) {
                $_SESSION['pos_shift_id'] = (int)$active_shift['id'];
                send_json_response('success', 'Active shift found.', ['has_active_shift' => true, 'shift' => $active_shift]);
            } else {
                unset($_SESSION['pos_shift_id']);
                send_json_response('success', 'No active shift.', ['has_active_shift' => false]);
            }
            break;

        case 'start':
            $starting_float = (float)($json_data['starting_float'] ?? -1.0);
            if ($starting_float < 0) {
                http_response_code(400);
                send_json_response('error', '初始备用金不能为空或为负数。');
            }
            
            $pdo->beginTransaction();
            $stmt_check = $pdo->prepare("SELECT id FROM pos_shifts WHERE user_id = ? AND status = 'ACTIVE' FOR UPDATE");
            $stmt_check->execute([$user_id]);
            if ($stmt_check->fetch()) {
                $pdo->rollBack();
                http_response_code(409); // Conflict
                send_json_response('error', '该用户已存在一个进行中的班次。');
            }

            $stmt_insert = $pdo->prepare(
                "INSERT INTO pos_shifts (shift_uuid, store_id, user_id, start_time, status, starting_float) VALUES (?, ?, ?, UTC_TIMESTAMP(), 'ACTIVE', ?)"
            );
            $stmt_insert->execute([bin2hex(random_bytes(16)), $store_id, $user_id, $starting_float]);
            $shift_id = $pdo->lastInsertId();
            $pdo->commit();

            $_SESSION['pos_shift_id'] = (int)$shift_id;
            send_json_response('success', '班次已成功开始！', ['shift_id' => $shift_id]);
            break;

        case 'summary':
            $shift_id = (int)($_SESSION['pos_shift_id'] ?? 0);
            if ($shift_id === 0) {
                http_response_code(404);
                send_json_response('error', '找不到当前用户的进行中班次。');
            }

            $sql_sales = "SELECT 
                COUNT(*) as transactions_count,
                COALESCE(SUM(final_total), 0) as net_sales
                FROM pos_invoices 
                WHERE shift_id = ? AND status = 'ISSUED'";
            $stmt_sales = $pdo->prepare($sql_sales);
            $stmt_sales->execute([$shift_id]);
            $sales_summary = $stmt_sales->fetch(PDO::FETCH_ASSOC);

            $sql_payments = "SELECT payment_summary FROM pos_invoices WHERE shift_id = ? AND status = 'ISSUED'";
            $stmt_payments = $pdo->prepare($sql_payments);
            $stmt_payments->execute([$shift_id]);

            $payments = ['Cash' => 0.0, 'Card' => 0.0, 'Platform' => 0.0];
            while($row = $stmt_payments->fetch(PDO::FETCH_ASSOC)) {
                if(empty($row['payment_summary'])) continue;
                $summary_data = json_decode($row['payment_summary'], true);
                if(isset($summary_data['summary']) && is_array($summary_data['summary'])){
                    foreach($summary_data['summary'] as $part){
                        if(isset($payments[$part['method']])) {
                            $payments[$part['method']] += (float)$part['amount'];
                        }
                    }
                    if(isset($summary_data['change']) && (float)$summary_data['change'] > 0){
                        $payments['Cash'] -= (float)$summary_data['change'];
                    }
                }
            }
             foreach($payments as &$value) { $value = max(0, round($value, 2)); }

            send_json_response('success', 'Shift summary loaded.', [
                'sales_summary' => $sales_summary,
                'payment_summary' => $payments
            ]);
            break;

        case 'end':
            $shift_id = (int)($_SESSION['pos_shift_id'] ?? 0);
            $counted_cash = (float)($json_data['counted_cash'] ?? -1.0);

            if ($shift_id === 0) { http_response_code(404); send_json_response('error', '找不到有效的班次。'); }
            if ($counted_cash < 0) { http_response_code(400); send_json_response('error', '清点的现金金额无效。'); }

            $pdo->beginTransaction();

            $stmt_shift = $pdo->prepare("SELECT starting_float FROM pos_shifts WHERE id = ? AND status = 'ACTIVE' FOR UPDATE");
            $stmt_shift->execute([$shift_id]);
            $shift = $stmt_shift->fetch();

            if (!$shift) { $pdo->rollBack(); http_response_code(404); send_json_response('error', '班次不存在或已结束。'); }
            
            // Re-calculate summary on the backend for security
            $sql_sales = "SELECT COUNT(*) as transactions_count, COALESCE(SUM(final_total), 0) as net_sales FROM pos_invoices WHERE shift_id = ? AND status = 'ISSUED'";
            $stmt_sales = $pdo->prepare($sql_sales);
            $stmt_sales->execute([$shift_id]);
            $sales_summary = $stmt_sales->fetch(PDO::FETCH_ASSOC);

            $sql_payments = "SELECT payment_summary FROM pos_invoices WHERE shift_id = ? AND status = 'ISSUED'";
            $stmt_payments = $pdo->prepare($sql_payments);
            $stmt_payments->execute([$shift_id]);
            $payments = ['Cash' => 0.0, 'Card' => 0.0, 'Platform' => 0.0];
             while($row = $stmt_payments->fetch(PDO::FETCH_ASSOC)) {
                if(empty($row['payment_summary'])) continue;
                $summary_data = json_decode($row['payment_summary'], true);
                if(isset($summary_data['summary']) && is_array($summary_data['summary'])){
                    foreach($summary_data['summary'] as $part){
                        if(isset($payments[$part['method']])) { $payments[$part['method']] += (float)$part['amount']; }
                    }
                    if(isset($summary_data['change']) && (float)$summary_data['change'] > 0){
                        $payments['Cash'] -= (float)$summary_data['change'];
                    }
                }
            }
            foreach($payments as &$value) { $value = max(0, round($value, 2)); }

            $starting_float = (float)$shift['starting_float'];
            $expected_cash = $starting_float + $payments['Cash'];
            $cash_variance = $counted_cash - $expected_cash;

            $stmt_update = $pdo->prepare(
                "UPDATE pos_shifts SET 
                    end_time = UTC_TIMESTAMP(), 
                    status = 'ENDED', 
                    counted_cash = ?, 
                    expected_cash = ?, 
                    cash_variance = ?, 
                    payment_summary = ?, 
                    sales_summary = ? 
                WHERE id = ?"
            );
            $stmt_update->execute([
                $counted_cash, 
                $expected_cash, 
                $cash_variance, 
                json_encode($payments), 
                json_encode($sales_summary), 
                $shift_id
            ]);

            $pdo->commit();
            
            // Unset session variables to force logout
            unset($_SESSION['pos_shift_id']);
            unset($_SESSION['pos_logged_in']);

            send_json_response('success', 'Shift ended successfully. User logged out.');
            break;

        default:
            http_response_code(400);
            send_json_response('error', 'Invalid action specified.');
            break;
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    error_log("Shift Handler Error: " . $e->getMessage());
    send_json_response('error', 'An internal server error occurred.', ['debug' => $e->getMessage()]);
}