<?php
/**
 * TopTea POS - Submit Order API
 * Revision: 5.3 (Shift ID Integration)
 * Patched by Gemini · 2025-10-29
 */

require_once realpath(__DIR__ . '/../../../pos_backend/core/config.php');
// SECURE: Enforce session validation, which also starts the session.
require_once realpath(__DIR__ . '/../../../pos_backend/core/pos_auth_core.php');
require_once realpath(__DIR__ . '/../../../pos_backend/services/PromotionEngine.php');

header('Content-Type: application/json; charset=utf-8');

/**
 * Sends a JSON response and terminates the script.
 */
function send_json_response($status, $message, $data = null) {
    echo json_encode(['status' => $status, 'message' => $message, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * Diagnostic helper function.
 */
function diag($msg, $e = null) {
    $base = 'DIAG_V5.3 :: File: submit_order.php';
    if ($e instanceof Throwable) return $base . ' :: Line: ' . $e->getLine() . ' :: ' . $e->getMessage();
    return $base . ' :: ' . $msg;
}


if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); send_json_response('error', 'Invalid request method.'); }
$raw = file_get_contents('php://input');
$json_data = json_decode($raw, true);
if (!$json_data || !isset($json_data['cart']) || !is_array($json_data['cart']) || count($json_data['cart']) === 0) { http_response_code(400); send_json_response('error', 'Cart data is missing or empty.'); }

// SECURITY: shift_id MUST come from the trusted session, not the payload.
$shift_id = (int)($_SESSION['pos_shift_id'] ?? 0);
if ($shift_id === 0) {
    http_response_code(403); // Forbidden
    send_json_response('error', 'No active shift found for the current user. Cannot process order.');
}


try {
    // Inputs from session and payload
    $store_id    = (int)($_SESSION['pos_store_id']);
    $user_id     = (int)($_SESSION['pos_user_id']);
    $member_id   = isset($json_data['member_id']) ? (int)$json_data['member_id'] : null;
    $points_redeemed_from_payload = isset($json_data['points_redeemed']) ? (int)$json_data['points_redeemed'] : 0;
    $payments    = $json_data['payment']['summary'] ?? [];
    $couponCode  = null;
    foreach (['coupon_code','coupon','code','promo_code','discount_code'] as $k) { if (!empty($json_data[$k])) { $couponCode = trim((string)$json_data[$k]); break; } }

    // Fetch store config
    $stmt_store = $pdo->prepare("SELECT * FROM kds_stores WHERE id = :store_id LIMIT 1");
    $stmt_store->execute([':store_id' => $store_id]);
    $store_config = $stmt_store->fetch(PDO::FETCH_ASSOC) ?: [];
    if (empty($store_config)) { throw new Exception("Store configuration for store_id #{$store_id} not found."); }

    $vat_rate = isset($store_config['default_vat_rate']) ? (float)$store_config['default_vat_rate'] : 21.0;

    // Fetch points earning rule
    $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM pos_settings WHERE setting_key = 'points_euros_per_point'");
    $settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
    $euros_per_point = isset($settings['points_euros_per_point']) ? (float)$settings['points_euros_per_point'] : 1.0;
    if ($euros_per_point <= 0) $euros_per_point = 1.0; // Safety fallback

    // Recalculate server-side to ensure integrity
    $engine = new PromotionEngine($pdo);
    $promoResult  = $engine->applyPromotions($json_data['cart'], $couponCode);
    $final_total_after_promo = (float)$promoResult['final_total'];
    
    // Server-side points validation & calculation
    $points_discount_final = 0.0;
    $points_to_deduct = 0;
    if ($member_id && $points_redeemed_from_payload > 0) {
        $stmt_member = $pdo->prepare("SELECT points_balance FROM pos_members WHERE id = ? AND is_active = 1 FOR UPDATE");
        $stmt_member->execute([$member_id]);
        $member = $stmt_member->fetch(PDO::FETCH_ASSOC);

        if ($member) {
            $current_points = (float)$member['points_balance'];
            $max_possible_discount = $final_total_after_promo;
            $max_points_for_discount = floor($max_possible_discount * 100);
            
            $points_to_deduct = min($points_redeemed_from_payload, $current_points, $max_points_for_discount);

            if ($points_to_deduct > 0) {
                $points_discount_final = floor($points_to_deduct) / 100.0;
            } else {
                $points_to_deduct = 0;
            }
        } else {
            $points_to_deduct = 0;
        }
    }
    
    $cart = $promoResult['cart'];
    $final_total = $final_total_after_promo - $points_discount_final;
    $discount_amount = (float)$promoResult['discount_amount'] + $points_discount_final;
    $payment_summary = $json_data['payment'];

    // Compliance logic
    $compliance_system = $store_config['billing_system'] ?? null;
    $compliance_data   = null;
    $qr_payload        = null;
    $invoice_number    = null;
    $series            = null;
    
    if ($compliance_system) {
        $handler_path = realpath(__DIR__ . "/../../../pos_backend/compliance/{$compliance_system}Handler.php");
        if ($handler_path && file_exists($handler_path)) {
            require_once $handler_path;
            $class = $compliance_system . 'Handler';
            if (class_exists($class)) {
                $handler = new $class();
                $series = "A" . date('Y');
                $issuer_nif = $store_config['tax_id'];
                $stmt_max_num = $pdo->prepare("SELECT MAX(number) FROM pos_invoices WHERE compliance_system = :system AND series = :series AND issuer_nif = :nif");
                $stmt_max_num->execute([':system' => $compliance_system, ':series' => $series, ':nif' => $issuer_nif]);
                $max_number = $stmt_max_num->fetchColumn();
                $invoice_number = ($max_number === null || $max_number < ($store_config['invoice_number_offset'] ?? 0)) ? ($store_config['invoice_number_offset'] ?? 10000) + 1 : $max_number + 1;
                $stmt_prev = $pdo->prepare("SELECT compliance_data FROM pos_invoices WHERE compliance_system = :system AND series = :series AND issuer_nif = :nif ORDER BY `number` DESC LIMIT 1");
                $stmt_prev->execute([':system' => $compliance_system, ':series' => $series, ':nif' => $issuer_nif]);
                $prev_invoice = $stmt_prev->fetch();
                $previous_hash = $prev_invoice ? (json_decode($prev_invoice['compliance_data'], true)['hash'] ?? null) : null;
                $invoiceData = ['series' => $series, 'number' => $invoice_number, 'issued_at' => (new DateTime('now', new DateTimeZone('Europe/Madrid')))->format('Y-m-d H:i:s.u'), 'final_total' => $final_total];
                $compliance_data = $handler->generateComplianceData($pdo, $invoiceData, $previous_hash);
                $qr_payload      = is_array($compliance_data) ? ($compliance_data['qr_content'] ?? null) : null;
            }
        }
    }

    $pdo->beginTransaction();

    // 1. Create Invoice with shift_id
    $issued_at = date('Y-m-d H:i:s');
    $taxable_base = round($final_total / (1 + ($vat_rate / 100)), 2);
    $vat_amount = $final_total - $taxable_base;

    $stmt_invoice = $pdo->prepare("
        INSERT INTO pos_invoices (invoice_uuid, store_id, user_id, shift_id, issuer_nif, series, `number`, issued_at, invoice_type, taxable_base, vat_amount, discount_amount, final_total, status, compliance_system, compliance_data, payment_summary)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt_invoice->execute([
        bin2hex(random_bytes(16)), $store_id, $user_id, $shift_id, $store_config['tax_id'], $series, $invoice_number, $issued_at, 'F2', $taxable_base, $vat_amount, $discount_amount, $final_total, 'ISSUED', $compliance_system, json_encode($compliance_data), json_encode($payment_summary)
    ]);
    $invoice_id = (int)$pdo->lastInsertId();

    // 2. Create Invoice Items
    $sql_item = "INSERT INTO pos_invoice_items (invoice_id, item_name, variant_name, quantity, unit_price, unit_taxable_base, vat_rate, vat_amount, customizations) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_item = $pdo->prepare($sql_item);

    foreach ($cart as $item) {
        $final_unit_price = (float)($item['final_price'] ?? $item['unit_price_eur'] ?? 0);
        $item_total = $final_unit_price * ($item['qty'] ?? 1);
        $item_taxable_base_total = round($item_total / (1 + ($vat_rate / 100)), 2);
        $item_vat_amount = $item_total - $item_taxable_base_total;
        $customizations_for_db = ['ice' => $item['ice'] ?? null, 'sugar' => $item['sugar'] ?? null, 'addons' => $item['addons'] ?? [], 'remark' => $item['remark'] ?? ''];
        
        $stmt_item->execute([
            $invoice_id,
            (string)($item['title'] ?? ($item['name'] ?? '')),
            (string)($item['variant_name'] ?? ''),
            (int)($item['qty'] ?? 1),
            $final_unit_price,
            round($item_taxable_base_total / ($item['qty'] ?? 1), 4),
            $vat_rate,
            $item_vat_amount,
            json_encode($customizations_for_db, JSON_UNESCAPED_UNICODE)
        ]);
    }

    // 3. Member Points Logic (Accumulation & Redemption)
    if ($member_id) {
        if ($points_to_deduct > 0 && $points_discount_final > 0) {
            $stmt_deduct_points = $pdo->prepare("UPDATE pos_members SET points_balance = points_balance - ? WHERE id = ?");
            $stmt_deduct_points->execute([$points_to_deduct, $member_id]);

            $stmt_log_deduction = $pdo->prepare("
                INSERT INTO pos_member_points_log (member_id, invoice_id, points_change, reason_code, notes, user_id)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt_log_deduction->execute([$member_id, $invoice_id, -$points_to_deduct, 'REDEEM_DISCOUNT', "兑换抵扣 {$points_discount_final} EUR", $user_id]);
        }
        
        if ($final_total > 0) {
            $points_to_add = floor($final_total / $euros_per_point); 
            if ($points_to_add > 0) {
                $stmt_add_points = $pdo->prepare("UPDATE pos_members SET points_balance = points_balance + ? WHERE id = ?");
                $stmt_add_points->execute([$points_to_add, $member_id]);

                $stmt_log_addition = $pdo->prepare("
                    INSERT INTO pos_member_points_log (member_id, invoice_id, points_change, reason_code, user_id)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt_log_addition->execute([$member_id, $invoice_id, $points_to_add, 'PURCHASE', $user_id]);
            }
        }
    }
    
    $pdo->commit();

    $resp = [
        'invoice_id' => $invoice_id,
        'invoice_number' => ($series ?? '') . '-' . ($invoice_number ?? $invoice_id),
        'qr_content' => $qr_payload,
    ];

    send_json_response('success', 'Order created.', $resp);

} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    send_json_response('error', 'Failed to create order.', ['debug' => diag('', $e)]);
}