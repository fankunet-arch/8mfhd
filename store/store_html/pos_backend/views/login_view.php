<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TopTea POS - 登录</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/pos_login.css?v=<?php echo time(); ?>">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h2 class="text-center mb-1 fw-bold"><span style="color: #ED7762;">TOPTEA</span> POS</h2>
            <h5 class="text-center text-muted mb-4">点餐收银系统</h5>
            
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger" role="alert">
                    无效的门店码、用户名或密码。
                </div>
            <?php endif; ?>

            <form action="api/pos_login_handler.php" method="POST">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="store_code" name="store_code" placeholder="门店码" required>
                    <label for="store_code">门店码</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="用户名" required>
                    <label for="username">用户名</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" placeholder="密码" required>
                    <label for="password">密码</label>
                </div>
                <div class="d-grid">
                    <button type="submit" class="btn btn-brand btn-lg">
                        <i class="bi bi-box-arrow-in-right me-2"></i> 登 录
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>