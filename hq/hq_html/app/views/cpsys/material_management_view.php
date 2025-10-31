<?php
// Define a helper map for material types
$material_type_map = [
    'RAW' => '原料',
    'SEMI_FINISHED' => '半成品',
    'PRODUCT' => '成品/直销品',
    'CONSUMABLE' => '耗材'
];
$expiry_rule_map = [
    'HOURS' => '小时',
    'DAYS' => '天',
    'END_OF_DAY' => '当日有效'
];
?>
<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" id="create-material-btn" data-bs-toggle="offcanvas" data-bs-target="#material-drawer">
        <i class="bi bi-plus-circle me-2"></i>创建新物料
    </button>
</div>

<div class="card">
    <div class="card-header">
        物料列表
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>自定义编号</th>
                        <th>物料名称 (中)</th>
                        <th>类型</th>
                        <th>换算关系</th>
                        <th class="text-end">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($materials)): ?>
                        <tr>
                            <td colspan="5" class="text-center">暂无物料数据。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($materials as $material): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($material['material_code']); ?></strong></td>
                                <td><?php echo htmlspecialchars($material['name_zh']); ?></td>
                                <td>
                                    <span class="badge text-bg-info"><?php echo $material_type_map[$material['material_type']] ?? '未知'; ?></span>
                                </td>
                                <td>
                                    <?php 
                                        if (!empty($material['large_unit_name']) && !empty($material['base_unit_name'])) {
                                            echo '1 ' . htmlspecialchars($material['large_unit_name']) . ' = ' . htmlspecialchars($material['conversion_rate'] ?? 'N/A') . ' ' . htmlspecialchars($material['base_unit_name']);
                                        } elseif (!empty($material['base_unit_name'])) {
                                            echo htmlspecialchars($material['base_unit_name']);
                                        } else {
                                            echo '<span class="text-muted">未设置</span>';
                                        }
                                    ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary edit-material-btn" 
                                            data-material-id="<?php echo $material['id']; ?>"
                                            data-bs-toggle="offcanvas" data-bs-target="#material-drawer">
                                        编辑
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger delete-material-btn"
											data-material-id="<?php echo $material['id']; ?>"
											data-material-name="<?php echo htmlspecialchars($material['name_zh']); ?>">
										删除
									</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="material-drawer" aria-labelledby="drawer-label">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="drawer-label">创建/编辑物料</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="material-form">
            <input type="hidden" id="material-id" name="material_id">

            <div class="mb-3">
                <label for="material-code" class="form-label">自定义编号 <span class="text-danger">*</span></label>
                <input type="number" class="form-control" id="material-code" name="material_code" required>
            </div>

            <div class="mb-3">
                <label for="material-type" class="form-label">物料类型 <span class="text-danger">*</span></label>
                <select class="form-select" id="material-type" name="material_type" required>
                    <option value="" disabled>-- 请选择 --</option>
                    <?php foreach ($material_type_map as $key => $value): ?>
                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text" id="material-type-description">
                    选择最符合厨房工作流的类型。
                    <ul>
                        <li><b>原料:</b> 需加工才能使用 (如: 茶叶, 干珍珠)。</li>
                        <li><b>半成品:</b> 原料加工后的产物 (如: 茶汤, 熟珍珠)。</li>
                        <li><b>成品/直销品:</b> 开封即用 (如: 牛奶, 罐头, 果浆)。</li>
                        <li><b>耗材:</b> 非食品 (如: 杯子, 吸管)。</li>
                    </ul>
                </div>
                </div>

            <div class="mb-3">
                <label for="material-name-zh" class="form-label">物料名称 (中) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="material-name-zh" name="material_name_zh" required>
            </div>
            
            <div class="mb-3">
                <label for="material-name-es" class="form-label">物料名称 (西) <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="material-name-es" name="material_name_es" required>
            </div>
            <hr>
            <h6 class="text-white-50">库存单位</h6>
            <div class="mb-3">
                <label for="base-unit-id" class="form-label">基础单位 <span class="text-danger">*</span></label>
                <select class="form-select" id="base-unit-id" name="base_unit_id" required>
                    <option value="" selected disabled>-- 请选择 --</option>
                    <?php foreach ($unit_options as $unit): ?>
                        <option value="<?php echo $unit['id']; ?>"><?php echo htmlspecialchars($unit['name_zh']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">库存和消耗的最小计算单位 (例如: 瓶, 克, 毫升)。</div>
            </div>
            <div class="mb-3">
                <label for="large-unit-id" class="form-label">大单位 (可选)</label>
                <select class="form-select" id="large-unit-id" name="large_unit_id">
                    <option value="">-- 不使用 --</option>
                     <?php foreach ($unit_options as $unit): ?>
                        <option value="<?php echo $unit['id']; ?>"><?php echo htmlspecialchars($unit['name_zh']); ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">方便入库和调拨的单位 (例如: 箱, 袋)。</div>
            </div>
             <div class="mb-3">
                <label for="conversion-rate" class="form-label">换算率</label>
                <input type="number" class="form-control" id="conversion-rate" name="conversion_rate" step="0.01">
                <div class="form-text">1个大单位等于多少个基础单位 (例如: 12)。</div>
            </div>
            <hr>
            <h6 class="text-white-50">效期规则</h6>
            <div class="mb-3">
                <label for="expiry-rule-type" class="form-label">规则类型</label>
                <select class="form-select" id="expiry-rule-type" name="expiry_rule_type">
                    <option value="">-- 不设置效期 --</option>
                    <?php foreach ($expiry_rule_map as $key => $value): ?>
                        <option value="<?php echo $key; ?>"><?php echo $value; ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">
                    定义此物料在KDS上操作后的有效期限。<br>
                    <b>重要:</b> 只有设置了效期规则的物料才会出现在KDS的“物料制备与开封”页面。
                </div>
            </div>
            <div class="mb-3" id="expiry-duration-wrapper" style="display: none;">
                <label for="expiry-duration" class="form-label">时长</label>
                <input type="number" class="form-control" id="expiry-duration" name="expiry_duration">
                <div class="form-text" id="expiry-duration-text"></div>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="offcanvas">取消</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>