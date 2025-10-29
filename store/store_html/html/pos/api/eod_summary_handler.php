<?php
/**
 * POS · EOD（日结/补结算）处理器（稳健版）
 * - 允许 target_business_date=YYYY-MM-DD；不传则默认“昨日”(Europe/Madrid)
 * - 仅用 PHP 计算 UTC 区间；SQL 不用 CONVERT_TZ/时区表
 * - 不查 information_schema；逐表试探
 * - 防重复：同门店+同业务日 仅允许一份（优先 JSON 字段，其次 created_at ∈ 业务日UTC区间）
 * - 所有异常写 error_log，响应不暴露细节
 */
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../pos_backend/core/config.php'; // 需提供 $pdo
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('DB connection ($pdo) not initialized.');
    }

    // --- 基础参数与时区 ---
    $tzMadrid = new DateTimeZone('Europe/Madrid');
    $utc      = new DateTimeZone('UTC');

    $store_id = (int)($_POST['store_id'] ?? $_GET['store_id'] ?? 1);

    $target_business_date = null;
    if (!empty($_POST['target_business_date'])) {
        $d = DateTime::createFromFormat('Y-m-d', $_POST['target_business_date'], $tzMadrid);
        if ($d !== false) $target_business_date = $d->format('Y-m-d');
    }
    if ($target_business_date === null) {
        $target_business_date = (new DateTime('yesterday', $tzMadrid))->format('Y-m-d');
    }

    // 业务日的本地时间窗口 → UTC 字符串
    $bd_start_local = new DateTime($target_business_date . ' 00:00:00', $tzMadrid);
    $bd_end_local   = new DateTime($target_business_date . ' 23:59:59', $tzMadrid);
    $bd_start_utc   = (clone $bd_start_local)->setTimezone($utc)->format('Y-m-d H:i:s');
    $bd_end_utc     = (clone $bd_end_local)->setTimezone($utc)->format('Y-m-d H:i:s');

    $today_local = (new DateTime('now', $tzMadrid))->format('Y-m-d');
    $is_makeup   = ($target_business_date !== $today_local);

    // --- 工具：逐表试探，返回第一个可 SELECT 的表名 ---
    $pickTable = function(PDO $pdo, array $candidates) {
        foreach ($candidates as $t) {
            try { $pdo->query("SELECT 1 FROM `{$t}` LIMIT 1"); return $t; }
            catch (Throwable $e) { /* try next */ }
        }
        return null;
    };

    // JSON_EXTRACT 支持性探测（MariaDB/MySQL 老版本可能不支持）
    $hasJsonExtract = (function(PDO $pdo) {
        try { $pdo->query("SELECT JSON_EXTRACT('{\"a\":1}', '$.a')"); return true; }
        catch (Throwable $e) { return false; }
    })($pdo);

    // 表名选取（按常见命名猜测；与你库名不同也能继续，只是丢部分明细）
    $eod_table      = $pickTable($pdo, ['pos_eod_reports','eod_reports']);
    $invoices_table = $pickTable($pdo, ['pos_invoices','invoices']);
    $payments_table = $pickTable($pdo, ['pos_payments','payments']);

    // --- 防重复：同门店 + 同业务日 不可重复 ---
    if ($eod_table) {
        $dupCount = 0;
        try {
            if ($hasJsonExtract) {
                $sql = "SELECT COUNT(*) FROM `{$eod_table}`
                        WHERE store_id=:sid
                          AND (JSON_EXTRACT(report_json,'$.business_date')=:bd
                               OR (created_at BETWEEN :s AND :e))";
                $st = $pdo->prepare($sql);
                $st->execute([':sid'=>$store_id, ':bd'=>$target_business_date, ':s'=>$bd_start_utc, ':e'=>$bd_end_utc]);
            } else {
                $sql = "SELECT COUNT(*) FROM `{$eod_table}`
                        WHERE store_id=:sid
                          AND (report_json LIKE :needle
                               OR (created_at BETWEEN :s AND :e))";
                $st = $pdo->prepare($sql);
                $st->execute([
                    ':sid'=>$store_id,
                    ':needle'=>'%"business_date":"'.$target_business_date.'"%',
                    ':s'=>$bd_start_utc, ':e'=>$bd_end_utc
                ]);
            }
            $dupCount = (int)$st->fetchColumn();
        } catch (Throwable $e) {
            error_log('[POS][EOD dup-check] ' . $e->getMessage());
        }
        if ($dupCount > 0) {
            echo json_encode(['status'=>'error','message'=>'该业务日已完成日结','data'=>['business_date'=>$target_business_date]], JSON_UNESCAPED_UNICODE);
            return;
        }
    }

    // --- 发票汇总（若找不到发票表也不报错，返回 0） ---
    $inv = ['invoice_count'=>0,'subtotal_eur'=>0,'discount_eur'=>0,'total_eur'=>0];
    if ($invoices_table) {
        // 尝试带 status_key='completed'；若列不存在则退化为不带状态过滤
        $sqlInvA = "SELECT COUNT(*) AS c,
                           COALESCE(SUM(subtotal_eur),0) AS sub,
                           COALESCE(SUM(discount_eur),0) AS disc,
                           COALESCE(SUM(total_eur),0) AS tot
                      FROM `{$invoices_table}`
                     WHERE store_id=:sid
                       AND created_at BETWEEN :s AND :e
                       AND status_key='completed'";
        $sqlInvB = "SELECT COUNT(*) AS c,
                           COALESCE(SUM(subtotal_eur),0) AS sub,
                           COALESCE(SUM(discount_eur),0) AS disc,
                           COALESCE(SUM(total_eur),0) AS tot
                      FROM `{$invoices_table}`
                     WHERE store_id=:sid
                       AND created_at BETWEEN :s AND :e";
        try {
            $st = $pdo->prepare($sqlInvA);
            $st->execute([':sid'=>$store_id, ':s'=>$bd_start_utc, ':e'=>$bd_end_utc]);
        } catch (Throwable $e) {
            error_log('[POS][EOD inv-A] ' . $e->getMessage());
            $st = $pdo->prepare($sqlInvB);
            $st->execute([':sid'=>$store_id, ':s'=>$bd_start_utc, ':e'=>$bd_end_utc]);
        }
        if ($row = $st->fetch(PDO::FETCH_ASSOC)) {
            $inv = ['invoice_count'=>(int)$row['c'],
                    'subtotal_eur'  =>(float)$row['sub'],
                    'discount_eur'  =>(float)$row['disc'],
                    'total_eur'     =>(float)$row['tot']];
        }
    }

    // --- 支付分布（若无表则跳过） ---
    $payments = [];
    if ($payments_table && $invoices_table) {
        // 猜测列：pos_payments.order_id -> pos_invoices.id；若失败则忽略
        $sqlPay = "SELECT p.method_key, COALESCE(SUM(p.amount_eur),0) AS amount_eur
                     FROM `{$payments_table}` p
                     JOIN `{$invoices_table}` i ON i.id = p.order_id
                    WHERE i.store_id=:sid
                      AND i.created_at BETWEEN :s AND :e
                 GROUP BY p.method_key ORDER BY p.method_key";
        try {
            $st = $pdo->prepare($sqlPay);
            $st->execute([':sid'=>$store_id, ':s'=>$bd_start_utc, ':e'=>$bd_end_utc]);
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                $payments[] = ['method_key'=>$r['method_key'], 'amount_eur'=>(float)$r['amount_eur']];
            }
        } catch (Throwable $e) {
            error_log('[POS][EOD pay] ' . $e->getMessage());
            $payments = [];
        }
    }

    // --- 组装报表 ---
    $report = [
        'business_date'    => $target_business_date,
        'is_makeup'        => $is_makeup,
        'store_id'         => $store_id,
        'summary'          => $inv,
        'payments'         => $payments,
        'generated_at_utc' => (new DateTime('now', $utc))->format('Y-m-d\TH:i:s\Z')
    ];
    $report_json = json_encode($report, JSON_UNESCAPED_UNICODE);

    // --- 持久化（若有 EOD 表） ---
    if ($eod_table) {
        $ins = $pdo->prepare("INSERT INTO `{$eod_table}` (store_id, report_json, created_at) VALUES (:sid, :js, UTC_TIMESTAMP())");
        $ins->execute([':sid'=>$store_id, ':js'=>$report_json]);
    } else {
        error_log('[POS][EOD] table not found, skip persistence.');
    }

    echo json_encode(['status'=>'ok','data'=>['business_date'=>$target_business_date,'report'=>$report]], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('[POS][eod_summary_handler] ' . $e->getMessage() . ' | input=' . substr(json_encode($_POST,JSON_UNESCAPED_UNICODE),0,1000));
    echo json_encode(['status'=>'error','message'=>'EOD处理失败'], JSON_UNESCAPED_UNICODE);
}
