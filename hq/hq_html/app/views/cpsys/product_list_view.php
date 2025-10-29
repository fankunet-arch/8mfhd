<div class="d-flex justify-content-end mb-3">
    <a href="index.php?page=product_management" class="btn btn-primary">
        <i class="bi bi-plus-circle me-2"></i>创建新产品
    </a>
</div>

<div class="card">
    <div class="card-header">
        产品列表
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>饮品编码</th>
                        <th>产品名 (中)</th>
                        <th>产品名 (西)</th>
                        <th>杯型</th>
                        <th>状态</th>
                        <th>上架状态</th>
                        <th>创建时间</th>
                        <th class="text-end">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($products)): ?>
                        <tr>
                            <td colspan="8" class="text-center">暂无产品数据。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($product['product_sku']); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['name_zh']); ?></td>
                                <td><?php echo htmlspecialchars($product['name_es']); ?></td>
                                <td><?php echo htmlspecialchars($product['cup_name']); ?></td>
                                <td><?php echo htmlspecialchars($product['status_name']); ?></td>
                                <td>
                                    <?php if ($product['is_active']): ?>
                                        <span class="badge text-bg-success">已上架</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">已下架</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($product['created_at'])); ?></td>
                                <td class="text-end">
                                    <a href="index.php?page=product_edit&id=<?php echo $product['id']; ?>" class="btn btn-sm btn-outline-primary">编辑</a>
                                    <button type="button" class="btn btn-sm btn-outline-danger delete-product-btn" 
											data-product-id="<?php echo $product['id']; ?>" 
											data-product-name="<?php echo htmlspecialchars($product['name_zh']); ?>">
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