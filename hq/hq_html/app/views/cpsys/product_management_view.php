<div class="d-flex justify-content-end mb-3">
    <a href="index.php?page=product_list" class="btn btn-secondary me-2">返回列表</a>
    <button class="btn btn-primary" id="save-product-btn">
        <i class="bi bi-save me-2"></i>保存配方
    </button>
</div>
<div class="card mb-4">
    <div class="card-header">配方主体信息</div>
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-4">
                <label for="product-sku" class="form-label">配方编码 (SKU)</label>
                <input type="text" class="form-control" id="product-sku" placeholder="例如: 101-M" value="<?php echo $next_sku; ?>">
            </div>
            <div class="col-md-4">
                <label for="product-name-zh" class="form-label">配方名称 (中文)</label>
                <input type="text" class="form-control" id="product-name-zh" placeholder="例如: 经典奶茶-中杯配方">
            </div>
            <div class="col-md-4">
                <label for="product-name-es" class="form-label">配方名称 (西语)</label>
                <input type="text" class="form-control" id="product-name-es" placeholder="Ej: Receta Clásico Mediano">
            </div>
            <div class="col-md-6">
                <label for="cup-type" class="form-label">杯型 (单选)</label>
                <select id="cup-type" class="form-select">
                    <option value="" selected disabled>请选择...</option>
                    <?php foreach ($cup_options as $cup): ?>
                        <option value="<?php echo $cup['id']; ?>">
                            <?php
                                echo htmlspecialchars($cup['cup_name']);
                                if (!empty($cup['sop_description_zh'])) {
                                    echo ' (' . htmlspecialchars($cup['sop_description_zh']) . ')';
                                }
                            ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label for="product-status" class="form-label">状态 (单选)</label>
                <select id="product-status" class="form-select">
                    <option value="" selected disabled>请选择...</option>
                    <?php foreach ($status_options as $status): ?>
                        <option value="<?php echo $status['id']; ?>"><?php echo htmlspecialchars($status['status_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">可选甜度 (多选)</label>
                <div class="form-control" style="height: auto;">
                    <?php foreach ($sweetness_options_all as $option): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input adjustment-checkbox" type="checkbox" data-type="sweetness" data-id="<?php echo $option['id']; ?>" data-name="<?php echo htmlspecialchars($option['name_zh']); ?>" id="sweetness_<?php echo $option['id']; ?>">
                            <label class="form-check-label" for="sweetness_<?php echo $option['id']; ?>">
                                <?php
                                    echo htmlspecialchars($option['name_zh']);
                                    if (!empty($option['sop_zh'])) {
                                        echo ' (' . htmlspecialchars($option['sop_zh']) . ')';
                                    }
                                ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">可选冰量 (多选)</label>
                <div class="form-control" style="height: auto;">
                    <?php foreach ($ice_options_all as $option): ?>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input adjustment-checkbox" type="checkbox" data-type="ice" data-id="<?php echo $option['id']; ?>" data-name="<?php echo htmlspecialchars($option['name_zh']); ?>" id="ice_<?php echo $option['id']; ?>">
                            <label class="form-check-label" for="ice_<?php echo $option['id']; ?>">
                                <?php
                                    echo htmlspecialchars($option['name_zh']);
                                    if (!empty($option['sop_zh'])) {
                                        echo ' (' . htmlspecialchars($option['sop_zh']) . ')';
                                    }
                                ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="card mb-4">
    <div class="card-header">动态用量调整</div>
    <div class="card-body">
        <div id="adjustments-container">
            <p class="text-muted">请先在上方选择“可选甜度”或“可选冰量”，此处将自动生成对应的用量设置行。</p>
        </div>
    </div>
</div>
<div class="card">
    <div class="card-header">结构化制作步骤 (从物料字典选择)</div>
    <div class="card-body">
        <div class="accordion" id="recipeAccordion">
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseBase" aria-expanded="true">第一步: 底料 (BASE)</button>
                </h2>
                <div id="collapseBase" class="accordion-collapse collapse show" data-bs-parent="#recipeAccordion">
                    <div class="accordion-body" id="base-ingredients">
                        <button class="btn btn-outline-primary btn-sm mt-2 add-recipe-row" data-target="base-ingredients">添加底料行</button>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseCup" aria-expanded="false">第二步: 调杯 (CUP)</button>
                </h2>
                <div id="collapseCup" class="accordion-collapse collapse" data-bs-parent="#recipeAccordion">
                    <div class="accordion-body" id="cup-ingredients">
                        <button class="btn btn-outline-primary btn-sm mt-2 add-recipe-row" data-target="cup-ingredients">添加调杯行</button>
                    </div>
                </div>
            </div>
            <div class="accordion-item">
                <h2 class="accordion-header">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTop" aria-expanded="false">第三步: 顶料 (TOP)</button>
                </h2>
                <div id="collapseTop" class="accordion-collapse collapse" data-bs-parent="#recipeAccordion">
                    <div class="accordion-body" id="top-ingredients">
                        <button class="btn btn-outline-primary btn-sm mt-2 add-recipe-row" data-target="top-ingredients">添加顶料行</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<template id="adjustment-row-template">
    <div class="row g-3 align-items-center mb-3 adjustment-row" data-type="{type}" data-id="{id}">
        <div class="col-md-2">
            <label class="form-label fw-bold">{name}</label>
        </div>
        <div class="col-md-4">
            <select class="form-select adjustment-material">
                <option value="" selected disabled>选择物料 (如: 果糖)</option>
                <?php foreach ($material_options as $material): ?>
                    <option value="<?php echo $material['id']; ?>"><?php echo htmlspecialchars($material['name_zh']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <input type="number" class="form-control adjustment-quantity" placeholder="数量">
        </div>
        <div class="col-md-3">
            <select class="form-select adjustment-unit">
                 <option value="" selected disabled>选择单位</option>
                <?php foreach ($unit_options as $unit): ?>
                    <option value="<?php echo $unit['id']; ?>"><?php echo htmlspecialchars($unit['name_zh']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</template>
<div id="recipe-row-template" style="display: none;">
    <div class="recipe-row mb-3">
        <div class="drag-handle me-2"><i class="bi bi-grip-vertical"></i></div>
        <select class="form-select material-select" name="material[]">
            <option value="" selected disabled>从字典选择物料...</option>
            <?php foreach ($material_options as $material): ?>
                <option value="<?php echo $material['id']; ?>"><?php echo htmlspecialchars($material['name_zh']); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="number" class="form-control quantity-input" name="quantity[]" placeholder="数量">
        <select class="form-select unit-select" name="unit_zh[]">
            <option value="" selected disabled>单位 (中)</option>
            <?php foreach ($unit_options as $unit): ?>
                <option value="<?php echo $unit['id']; ?>"><?php echo htmlspecialchars($unit['name_zh']); ?></option>
            <?php endforeach; ?>
        </select>
        <div class="btn-group">
            <button type="button" class="btn btn-outline-danger btn-sm delete-recipe-row"><i class="bi bi-trash"></i></button>
        </div>
    </div>
</div>