<?php
/**
 * TopTea HQ - POS Print Template Variables View
 * Version: 2.1.1
 * Engineer: Gemini | Date: 2025-10-29
 * Description: Displays available variables and default templates.
 */

// Helper to format and display JSON
function render_json_template($title, $json_content) {
    $formatted_json = '';
    if (!empty($json_content)) {
        $json_decoded = json_decode($json_content, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $formatted_json = htmlspecialchars(json_encode($json_decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $formatted_json = htmlspecialchars($json_content);
        }
    } else {
        $formatted_json = " (暂无默认模板)";
    }

    echo '<div class="card mb-4">';
    echo '<div class="card-header">' . htmlspecialchars($title) . '</div>';
    echo '<div class="card-body">';
    echo '<pre><code class="language-json">' . $formatted_json . '</code></pre>';
    echo '</div>';
    echo '</div>';
}
?>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                顾客小票 (RECEIPT) & 厨房出品单 (KITCHEN_ORDER)
            </div>
            <div class="card-body">
                <p class="card-text">
                    这两类模板共享大部分订单相关的变量。厨房单通常只关注商品信息，而顾客小票则包含所有信息。
                </p>
                
                <h6 class="mt-4">全局变量</h6>
                <table class="table table-sm table-bordered">
                    <thead><tr><th>变量</th><th>说明</th></tr></thead>
                    <tbody>
                        <tr><td><code>{store_name}</code></td><td>门店名称</td></tr>
                        <tr><td><code>{store_address}</code></td><td>门店地址</td></tr>
                        <tr><td><code>{store_tax_id}</code></td><td>门店税号 (NIF)</td></tr>
                        <tr><td><code>{invoice_number}</code></td><td>完整票号 (系列-编号)</td></tr>
                        <tr><td><code>{issued_at}</code></td><td>开票时间 (YYYY-MM-DD HH:MM:SS)</td></tr>
                        <tr><td><code>{cashier_name}</code></td><td>收银员姓名</td></tr>
                        <tr><td><code>{qr_code}</code></td><td>合规二维码内容 (TicketBAI/Veri*Factu)</td></tr>
                    </tbody>
                </table>

                <h6 class="mt-4">财务变量 (仅顾客小票适用)</h6>
                <table class="table table-sm table-bordered">
                     <thead><tr><th>变量</th><th>说明</th></tr></thead>
                    <tbody>
                        <tr><td><code>{subtotal}</code></td><td>小计金额 (折扣前)</td></tr>
                        <tr><td><code>{discount_amount}</code></td><td>总折扣金额</td></tr>
                        <tr><td><code>{final_total}</code></td><td>最终应付总额</td></tr>
                        <tr><td><code>{taxable_base}</code></td><td>税前基数</td></tr>
                        <tr><td><code>{vat_amount}</code></td><td>总税额</td></tr>
                        <tr><td><code>{payment_methods}</code></td><td>支付方式明细 (自动格式化)</td></tr>
                        <tr><td><code>{change}</code></td><td>找零金额</td></tr>
                    </tbody>
                </table>

                <h6 class="mt-4">商品循环变量 (在 "items_loop" 中使用)</h6>
                 <table class="table table-sm table-bordered">
                    <thead><tr><th>变量</th><th>说明</th></tr></thead>
                    <tbody>
                        <tr><td><code>{item_name}</code></td><td>商品名称</td></tr>
                        <tr><td><code>{item_variant}</code></td><td>商品规格</td></tr>
                        <tr><td><code>{item_qty}</code></td><td>商品数量</td></tr>
                        <tr><td><code>{item_unit_price}</code></td><td>商品单价 (含税)</td></tr>
                        <tr><td><code>{item_total_price}</code></td><td>商品行总价 (含税)</td></tr>
                        <tr><td><code>{item_customizations}</code></td><td>商品自定义选项 (如: 少冰, 少糖)</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                日结报告 (EOD_REPORT) 变量
            </div>
            <div class="card-body">
                <p class="card-text">用于 `EOD_REPORT` 类型的模板。</p>
                <table class="table table-sm table-bordered">
                    <thead><tr><th>变量</th><th>说明</th></tr></thead>
                    <tbody>
                        <tr><td><code>{report_date}</code></td><td>报告所属日期 (YYYY-MM-DD)</td></tr>
                        <tr><td><code>{store_name}</code></td><td>门店名称</td></tr>
                        <tr><td><code>{user_name}</code></td><td>执行日结的收银员姓名</td></tr>
                        <tr><td><code>{print_time}</code></td><td>小票打印的当前时间</td></tr>
                        <tr><td><code>{transactions_count}</code></td><td>总交易笔数</td></tr>
                        <tr><td><code>{system_gross_sales}</code></td><td>总销售额 (含税)</td></tr>
                        <tr><td><code>{system_discounts}</code></td><td>总折扣额</td></tr>
                        <tr><td><code>{system_net_sales}</code></td><td>净销售额 (总销售 - 总折扣)</td></tr>
                        <tr><td><code>{system_tax}</code></td><td>总税额</td></tr>
                        <tr><td><code>{system_cash}</code></td><td>系统记录的现金收款总额</td></tr>
                        <tr><td><code>{system_card}</code></td><td>系统记录的刷卡收款总额</td></tr>
                        <tr><td><code>{system_platform}</code></td><td>系统记录的平台收款总额</td></tr>
                        <tr><td><code>{counted_cash}</code></td><td>收银员清点的现金金额</td></tr>
                        <tr><td><code>{cash_discrepancy}</code></td><td>现金差异 (清点 - 系统)</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<hr class="my-4">

<h3 class="mb-3">默认模板示例</h3>
<div class="row">
    <div class="col-12">
        <?php 
        render_json_template('默认顾客小票 (RECEIPT)', $default_templates['RECEIPT'] ?? '');
        render_json_template('默认厨房出品单 (KITCHEN_ORDER)', $default_templates['KITCHEN_ORDER'] ?? '');
        render_json_template('默认日结报告 (EOD_REPORT)', $default_templates['EOD_REPORT'] ?? '');
        ?>
    </div>
</div>