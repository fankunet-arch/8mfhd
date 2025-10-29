<?php
/**
 * POS · EOD 状态检测（稳健版）
 * - 按“昨日业务日”(Europe/Madrid) 判断是否已结
 * - 优先匹配 report_json.business_date，其次 created_at ∈ 昨日UTC区间
 * - 不依赖 information_schema/CONVERT_TZ
 */
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../../pos_backend/core/config.php';
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException('DB connection ($pdo) not initialized.');
    }

    $tzMadrid = new DateTimeZone('Europe/Madrid');
    $utc      = new DateTimeZone('UTC');

    $yesterday = (new DateTime('yesterday', $tzMadrid))->format('Y-m-d');

    // 昨日业务日窗口（UTC）
    $bd_start_utc = (new DateTime($yesterday.' 00:00:00', $tzMadrid))->setTimezone($utc)->format('Y-m-d H:i:s');
    $bd_end_utc   = (new DateTime($yesterday.' 23:59:59', $tzMadrid))->setTimezone($utc)->format('Y-m-d H:i:s');

    $store_id = (int)($_GET['store_id'] ?? $_POST['store_id'] ?? 1);

    // 逐表试探
    $pickTable = function(PDO $pdo, array $candidates) {
        foreach ($candidates as $t) {
            try { $pdo->query("SELECT 1 FROM `{$t}` LIMIT 1"); return $t; }
            catch (Throwable $e) { /* try next */ }
        }
        return null;
    };
    $eod_table = $pickTable($pdo, ['pos_eod_reports','eod_reports']);

    if (!$eod_table) {
        echo json_encode(['status'=>'ok','needs_makeup'=>true,'target_business_date'=>$yesterday,'message'=>'未找到EOD表，默认提示补结'], JSON_UNESCAPED_UNICODE);
        return;
    }

    // JSON_EXTRACT 支持性
    $hasJsonExtract = (function(PDO $pdo) {
        try { $pdo->query("SELECT JSON_EXTRACT('{\"a\":1}', '$.a')"); return true; }
        catch (Throwable $e) { return false; }
    })($pdo);

    // 先按 JSON business_date，失败则用 created_at 区间
    $count = 0;
    try {
        if ($hasJsonExtract) {
            $sql = "SELECT COUNT(*) FROM `{$eod_table}` WHERE store_id=:sid
                      AND (JSON_EXTRACT(report_json,'$.business_date')=:bd
                           OR (created_at BETWEEN :s AND :e))";
            $st = $pdo->prepare($sql);
            $st->execute([':sid'=>$store_id, ':bd'=>$yesterday, ':s'=>$bd_start_utc, ':e'=>$bd_end_utc]);
        } else {
            $sql = "SELECT COUNT(*) FROM `{$eod_table}` WHERE store_id=:sid
                      AND (report_json LIKE :needle
                           OR (created_at BETWEEN :s AND :e))";
            $st = $pdo->prepare($sql);
            $st->execute([
                ':sid'=>$store_id,
                ':needle'=>'%"business_date":"'.$yesterday.'"%',
                ':s'=>$bd_start_utc, ':e'=>$bd_end_utc
            ]);
        }
        $count = (int)$st->fetchColumn();
    } catch (Throwable $e) {
        error_log('[POS][check_eod_status] ' . $e->getMessage());
    }

    if ($count > 0) {
        echo json_encode(['status'=>'ok','needs_makeup'=>false,'message'=>'昨日已完成日结'], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['status'=>'ok','needs_makeup'=>true,'target_business_date'=>$yesterday,'message'=>'检测到昨日未完成日结'], JSON_UNESCAPED_UNICODE);
    }

} catch (Throwable $e) {
    error_log('[POS][check_eod_status fatal] ' . $e->getMessage());
    echo json_encode(['status'=>'error','message'=>'检查失败'], JSON_UNESCAPED_UNICODE);
}
