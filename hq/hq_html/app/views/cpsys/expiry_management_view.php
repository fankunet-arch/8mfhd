<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" id="add-expiry-item-btn" disabled>
        <i class="bi bi-plus-circle me-2"></i>登记新开封物料 (仅限KDS端)
    </button>
</div>

<div class="card">
    <div class="card-header">
        效期追踪列表
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>所属门店</th>
                        <th>物料名称</th>
                        <th>开封时间</th>
                        <th>过期时间</th>
                        <th>状态</th>
                        <th class="text-end">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($expiry_items)): ?>
                        <tr>
                            <td colspan="6" class="text-center">暂无需要追踪的物料。</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($expiry_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['store_name']); ?></td>
                                <td><strong><?php echo htmlspecialchars($item['material_name']); ?></strong></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($item['opened_at'])); ?></td>
                                <td><?php echo date('Y-m-d H:i', strtotime($item['expires_at'])); ?></td>
                                <td>
                                    <?php 
                                        $status_map = [
                                            'ACTIVE' => ['class' => 'success', 'text' => '追踪中'],
                                            'USED' => ['class' => 'secondary', 'text' => '已用完'],
                                            'DISCARDED' => ['class' => 'danger', 'text' => '已废弃']
                                        ];
                                        $status_info = $status_map[$item['status']] ?? ['class' => 'info', 'text' => '未知'];
                                    ?>
                                    <span class="badge text-bg-<?php echo $status_info['class']; ?>"><?php echo $status_info['text']; ?></span>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary" disabled>查看详情</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>