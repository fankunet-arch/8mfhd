<?php
/**
 * 通过 Nager.Date API 获取指定国家/年份的公共假期
 * * 注意：此API只能保证国家级和部分州/区级的假期准确性。
 * 对于马德里市（地方性）独有的假期，仍需人工核对或使用更专业的API。
 */
function get_spain_holidays_from_api(int $year): array {
    $country = 'ES'; // 西班牙
    // 修正后的 API URL
    $api_url = "https://date.nager.at/api/v3/PublicHolidays/{$year}/{$country}";

    // 使用 file_get_contents 或 cURL 发起请求
    $options = [
        'http' => [
            'method' => "GET",
            'header' => "Accept: application/json\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $data = @file_get_contents($api_url, false, $context);

    if ($data === FALSE) {
        // 无法连接或请求失败
        error_log("Failed to fetch holidays from Nager.Date API.");
        return ['status' => 'error', 'message' => '无法连接到假期 API。'];
    }

    $holidays = json_decode($data, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        // JSON 解析错误
        return ['status' => 'error', 'message' => 'API 返回数据格式错误。'];
    }

    $madrid_holidays = [];
    foreach ($holidays as $holiday) {
        // 西班牙的假期可能包含 region 字段来表示地区。
        // 例如：马德里大区代码为 "M" (Madrid)。
        $regions = $holiday['regions'] ?? [];
        
        // 筛选出适用于所有地区或专门适用于马德里的假期
        if (empty($regions) || in_array('ES-M', $regions) || in_array('M', $regions)) {
            $madrid_holidays[] = [
                'date' => $holiday['date'],
                'name_zh' => $holiday['localName'] ?? $holiday['name'],
                // Nager.Date 的 holidayType 可以帮助区分通用和地方假期
                'type' => $holiday['type'] ?? 'Unknown', 
                'is_madrid_specific' => in_array('ES-M', $regions) 
            ];
        }
    }
    
    return ['status' => 'success', 'data' => $madrid_holidays];
}

// 示例调用：获取 2026 年假期
$result = get_spain_holidays_from_api(2026);

if ($result['status'] === 'success') {
    echo "== 马德里地区 2026 年假期列表 (包含国家/地区级) ==\n";
    foreach ($result['data'] as $h) {
        $region_note = $h['is_madrid_specific'] ? ' (马德里大区级)' : ' (通用/全国级)';
        echo "日期: {$h['date']} | 名称: {$h['name_zh']} {$region_note}\n";
    }
} else {
    echo "获取假期信息失败: {$result['message']}\n";
}
?>