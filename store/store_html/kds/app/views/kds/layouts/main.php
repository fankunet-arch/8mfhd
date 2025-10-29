<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <title><?php echo $page_title ?? 'TopTea KDS'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet" />
    <link rel="stylesheet" href="css/kds_style.css?v=<?php echo time(); ?>">
</head>
<body>
    
    <?php
        // This is where the actual page content will be injected.
        if (isset($content_view) && file_exists($content_view)) {
            include $content_view;
        } else {
            echo '<div class="alert alert-danger m-5">Error: Content view file not found.</div>';
        }
    ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($page_js)): ?>
        <script src="js/<?php echo $page_js; ?>?v=<?php echo time(); ?>"></script>
    <?php endif; ?>

    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmationModalLabel" data-i18n-key="modal_title_confirm">请确认操作</h5>
                </div>
                <div class="modal-body" id="confirmationModalBody">
                    您确定要执行此操作吗？
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" data-i18n-key="modal_btn_cancel">取消</button>
                    <button type="button" class="btn btn-primary" id="confirm-action-btn" data-i18n-key="modal_btn_confirm">确认</button>
                </div>
            </div>
        </div>
    </div>
    </body>
</html>