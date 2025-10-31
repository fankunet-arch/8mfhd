/**
 * Toptea KDS - kds_print_bridge.js
 * 核心打印模块 (v1.3 - 混合动力版 + 间隙修复)
 *
 * 自动检测环境：
 * 1. 如果在 APK 套壳中 (检测到 window.AndroidBridge): 
 * - 调用 convertToTSPL() 将模板转为 TSPL 打印语言。
 * - 调用 window.AndroidBridge.printRaw(tsplString) 发送给打印机。
 * 2. 如果在 PC 浏览器中 (如 WIN11):
 * - 调用 convertToHtml() 将模板转为 HTML。
 * - 调用 window.print() 打开系统打印对话框。
 *
 * Engineer: Gemini | Date: 2025-10-30
 */

var KDS_PRINT_BRIDGE = (function () {

    var printWindow = null;  // 用于 window.print 的弹出窗口

    // 辅助函数：替换 {variable_name} 占位符
    function replacePlaceholders(text, data) {
        if (!text || typeof text !== 'string') return '';
        return text.replace(/\{([\w_]+)\}/g, function (match, key) {
            // 安全地返回值，如果未定义则返回原始占位符
            var value = data[key];
            return (value !== undefined && value !== null) ? String(value) : match;
        });
    }

    /**
     * ===================================================================
     * 通道 1: 转换为 HTML (用于 PC / window.print)
     * ===================================================================
     */
    function convertToHtml(templateContent, data, templateSize) {
        var htmlLines = [];

        templateContent.forEach(function (command) {
            var type = command.type;
            var value = command.value || '';
            var align = command.align || 'left'; // left, center, right
            var size = command.size || 'normal'; // normal, double
            var key = command.key || '';
            var boldValue = command.bold_value || false;

            var line = '';
            var style = 'margin: 0; padding: 0; font-family: monospace; line-height: 1.4; word-wrap: break-word; white-space: pre-wrap;';
            
            // 根据模板尺寸调整基础字体大小
            var baseFontSize = '12px'; // 80mm
            if (templateSize.startsWith('40x') || templateSize.startsWith('50x') || templateSize.startsWith('30x') || templateSize.startsWith('25x')) {
                baseFontSize = '10px'; // 小标签
            }

            style += 'font-size: ' + baseFontSize + ';';
            if (align === 'center') style += 'text-align: center;';
            if (align === 'right') style += 'text-align: right;';
            
            // 模拟字体大小
            if (size === 'double') {
                style += 'font-size: 1.6em; font-weight: bold; line-height: 1.2;';
            }

            switch (type) {
                case 'text':
                    line = replacePlaceholders(value, data);
                    break;
                case 'kv':
                    var replacedKey = replacePlaceholders(key, data);
                    var replacedValue = replacePlaceholders(value, data);
                    line = replacedKey + ": " + (boldValue ? '<strong>' : '') + replacedValue + (boldValue ? '</strong>' : '');
                    break;
                case 'divider':
                    var char = command.char || '-';
                    line = char.repeat(32); // 32 字符宽度
                    break;
                case 'feed':
                    var lineCount = command.lines || 1;
                    line = '<br>'.repeat(lineCount);
                    break;
                case 'cut':
                    // 在 window.print() 中, 我们用一个 CSS page-break 来模拟切纸
                    htmlLines.push('<div style="page-break-after: always;"></div>');
                    return; // 继续下一个 command
                default:
                    line = "[未知命令: " + type + "]";
            }
            htmlLines.push('<div style="' + style + '">' + line + '</div>');
        });
        
        return htmlLines.join('\n');
    }

    /**
     * ===================================================================
     * 通道 2: 转换为 TSPL (用于 安卓APK / window.AndroidBridge)
     * ===================================================================
     */
    function convertToTSPL(templateContent, data, templateSize) {
        var tsplCommands = [];
        var yPos = 20; // Y轴起始位置 (点)
        var xStart = 20; // X轴起始位置 (点)

        // 1. 设置标签尺寸 (假设 1mm = 8 dots)
        var width, height;
        if (templateSize === '80mm') {
            width = 80;
            height = 60; // 假设80mm连续纸，本次打印高度60mm (需要动态计算)
        } else {
            var dimensions = templateSize.split('x');
            width = parseInt(dimensions[0] || '50', 10);
            height = parseInt(dimensions[1] || '30', 10);
        }
        
        tsplCommands.push('SIZE ' + width + ' mm, ' + height + ' mm');
        // --- 关键修复：定义 3mm 的标签间隙 ---
        tsplCommands.push('GAP 3 mm, 0 mm');
        // ---------------------------------
        tsplCommands.push('CLS'); // 清除缓冲区

        // 2. 遍历命令
        templateContent.forEach(function (command) {
            var type = command.type;
            var value = replacePlaceholders(command.value || '', data);
            var align = command.align || 'left';
            var size = command.size || 'normal';
            var key = replacePlaceholders(command.key || '', data);
            var boldValue = command.bold_value || false;
            
            var font = 'TSS24.BF2'; // 默认字体 (24x24)
            var multiplier = (size === 'double') ? 2 : 1;
            var lineHeight = (size === 'double') ? 48 : 24;
            
            var text = '';
            
            switch (type) {
                case 'text':
                    text = value;
                    break;
                case 'kv':
                    text = key + ": " + value; // TSPL 不支持混合样式，bold 暂忽略
                    break;
                case 'divider':
                    // TSPL 使用 BAR 命令画线
                    tsplCommands.push('BAR ' + xStart + ',' + yPos + ', ' + (width * 8 - 40) + ', 2');
                    yPos += 10;
                    return;
                case 'feed':
                    yPos += (command.lines || 1) * 20;
                    return;
                case 'cut':
                    // CUT 命令会在最后添加
                    return; 
                default:
                    text = "[? " + type + "]";
            }
            
            // TSPL TEXT 命令: X, Y, "字体", 旋转, X缩放, Y缩放, "内容"
            tsplCommands.push('TEXT ' + xStart + ',' + yPos + ',"' + font + '",0,' + multiplier + ',' + multiplier + ',"' + text + '"');
            yPos += lineHeight + 4; // 增加 Y 坐标
        });

        // 3. 结束命令
        tsplCommands.push('PRINT 1,1'); // 打印1张
        if (templateContent.some(c => c.type === 'cut')) {
             tsplCommands.push('CUT'); // 执行切刀
        }

        // 返回 TSPL 字符串，确保换行符正确
        return tsplCommands.join('\r\n');
    }

    /**
     * ===================================================================
     * 公开方法：执行打印 (混合逻辑)
     * ===================================================================
     */
    function executePrint(template, data) {
        if (!template || !template.content || !Array.isArray(template.content)) {
            console.error("KDS Print Error: 模板 (template) 无效。");
            alert("打印失败：模板格式不正确。");
            return;
        }
        if (!data) {
            console.error("KDS Print Error: 数据 (data) 未定义。");
            alert("打印失败：无打印数据。");
            return;
        }

        var templateSize = template.size || "80mm"; // e.g., "50x30" or "80mm"
        
        console.log("--- KDS 打印任务 ---");
        console.log("Template:", template);
        console.log("Data:", data);

        // **混合逻辑检测**
        if (window.AndroidBridge && typeof window.AndroidBridge.printRaw === 'function') {
            // --- 通道 1: APK 套壳环境 (平板) ---
            console.log("检测到 AndroidBridge，使用 TSPL 打印...");
            try {
                var tsplString = convertToTSPL(template.content, data, templateSize);
                console.log("TSPL:\n", tsplString);
                window.AndroidBridge.printRaw(tsplString);
            } catch (e) {
                console.error("TSPL 打印失败:", e);
                alert("平板打印失败: " + e.message);
            }

        } else {
            // --- 通道 2: PC 浏览器环境 (WIN11) ---
            console.log("未检测到 AndroidBridge，使用 window.print() (PC测试)...");
            
            var htmlContent = convertToHtml(template.content, data, templateSize);

            // 关闭旧的打印窗口（如果存在）
            if (printWindow) {
                try { printWindow.close(); } catch (e) {}
            }

            // 打开新窗口并写入内容
            printWindow = window.open('', '_blank', 'width=300,height=300,left=100,top=100');
            if (!printWindow) {
                alert("打开打印窗口失败，请检查您的浏览器是否阻止了弹出窗口。");
                return;
            }

            var width, height;
            if (templateSize === '80mm') {
                width = '80mm';
                height = 'auto'; // 连续纸，高度自动
            } else {
                var dimensions = templateSize.split('x');
                width = dimensions[0] ? dimensions[0] + 'mm' : '50mm';
                height = dimensions[1] ? dimensions[1] + 'mm' : '30mm';
            }

            printWindow.document.write('<html><head><title>TopTea Print</title>');
            printWindow.document.write('<style>');
            printWindow.document.write('body { margin: 0; padding: 0; }');
            printWindow.document.write('@media print {');
            printWindow.document.write('  @page {');
            // --- 修复：确保尺寸和间隙设置正确 ---
            printWindow.document.write('    size: ' + width + (height === 'auto' ? '' : ' ' + height) + ';');
            printWindow.document.write('    margin: 1mm;'); // 留 1mm 边距
            printWindow.document.write('  }');
            printWindow.document.write('  body { margin: 0; }'); 
            printWindow.document.write('}');
            printWindow.document.write('</style></head><body>');
            printWindow.document.write(htmlContent);
            printWindow.document.write('</body></html>');
            printWindow.document.close();

            // 调用打印
            setTimeout(function() {
                try {
                    printWindow.focus();
                    printWindow.print();
                    // printWindow.close(); // 打印后自动关闭（通常会被浏览器阻止）
                } catch (e) {
                    console.error("window.print() 失败:", e);
                    printWindow.close(); // 失败时尝试关闭
                }
            }, 250); // 等待内容渲染
        }
    }

    // 返回公开的 API
    return {
        executePrint: executePrint
    };

})();
