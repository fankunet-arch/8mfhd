<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-primary" id="create-btn" data-bs-toggle="offcanvas" data-bs-target="#data-drawer">
        <i class="bi bi-plus-circle me-2"></i>创建新用户
    </button>
</div>

<div class="card">
    <div class="card-header">用户列表</div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>用户名</th>
                        <th>显示名称</th>
                        <th>邮箱</th>
                        <th>角色</th>
                        <th>状态</th>
                        <th>最后登录时间</th>
                        <th class="text-end">操作</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($users)): ?>
                        <tr><td colspan="7" class="text-center">暂无用户数据。</td></tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
                                <td><?php echo htmlspecialchars($user['display_name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td><span class="badge text-bg-info"><?php echo htmlspecialchars($user['role_name']); ?></span></td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge text-bg-success">已激活</span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">已禁用</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $user['last_login_at'] ? date('Y-m-d H:i', strtotime($user['last_login_at'])) : '从未'; ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary edit-btn" data-id="<?php echo $user['id']; ?>" data-bs-toggle="offcanvas" data-bs-target="#data-drawer">编辑</button>
                                    <?php if ($user['id'] != $_SESSION['user_id']): // Prevent deleting self ?>
                                        <button class="btn btn-sm btn-outline-danger delete-btn" data-id="<?php echo $user['id']; ?>" data-name="<?php echo htmlspecialchars($user['username']); ?>">删除</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="data-drawer" aria-labelledby="drawer-label">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="drawer-label">创建/编辑用户</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <form id="data-form">
            <input type="hidden" id="data-id" name="id">
            <div class="mb-3">
                <label for="username" class="form-label">用户名 <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="mb-3">
                <label for="display_name" class="form-label">显示名称 <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="display_name" name="display_name" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">邮箱</label>
                <input type="email" class="form-control" id="email" name="email">
            </div>
            <hr>
            <div class="mb-3">
                <label for="password" class="form-label">新密码</label>
                <input type="password" class="form-control" id="password" name="password">
                <div class="form-text">仅在需要设置或重置密码时填写。留空则不修改。</div>
            </div>
             <div class="mb-3">
                <label for="password_confirm" class="form-label">确认新密码</label>
                <input type="password" class="form-control" id="password_confirm" name="password_confirm">
            </div>
            <hr>
            <div class="mb-3">
                <label for="role_id" class="form-label">角色 <span class="text-danger">*</span></label>
                <select class="form-select" id="role_id" name="role_id" required>
                    <option value="" selected disabled>请选择角色...</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
             <div class="form-check form-switch mb-4">
                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" checked>
                <label class="form-check-label" for="is_active">账户是否激活</label>
            </div>
            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="offcanvas">取消</button>
                <button type="submit" class="btn btn-primary">保存</button>
            </div>
        </form>
    </div>
</div>