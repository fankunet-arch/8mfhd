/**
 * TopTea BMS - JavaScript for POS Category Management
 * Engineer: Gemini | Date: 2025-10-26
 */
$(document).ready(function() {
    const dataDrawer = new bootstrap.Offcanvas(document.getElementById('data-drawer'));
    const form = $('#data-form');
    const drawerLabel = $('#drawer-label');
    const dataIdInput = $('#data-id');
    const codeInput = $('#category_code');

    $('#create-btn').on('click', function() {
        drawerLabel.text('创建新POS分类');
        form[0].reset();
        dataIdInput.val('');
        codeInput.prop('readonly', false);
    });

    $('.table').on('click', '.edit-btn', function() {
        const dataId = $(this).data('id');
        drawerLabel.text('编辑POS分类');
        form[0].reset();
        dataIdInput.val(dataId);
        codeInput.prop('readonly', true); // Prevent changing the key

        $.ajax({
            url: 'api/pos_category_handler.php',
            type: 'GET',
            data: { action: 'get', id: dataId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    const cat = response.data;
                    codeInput.val(cat.category_code);
                    $('#name_zh').val(cat.name_zh);
                    $('#name_es').val(cat.name_es);
                    $('#sort_order').val(cat.sort_order);
                } else {
                    alert('获取数据失败: ' + response.message);
                    dataDrawer.hide();
                }
            },
            error: function() {
                alert('获取数据时发生网络错误。');
                dataDrawer.hide();
            }
        });
    });

    form.on('submit', function(e) {
        e.preventDefault();
        const formData = {
            id: dataIdInput.val(),
            category_code: codeInput.val(),
            name_zh: $('#name_zh').val(),
            name_es: $('#name_es').val(),
            sort_order: $('#sort_order').val()
        };
        $.ajax({
            url: 'api/pos_category_handler.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: 'save', data: formData }),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    window.location.reload();
                } else {
                    alert('保存失败: ' + (response.message || '未知错误'));
                }
            },
            error: function(jqXHR) {
                if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                    alert('操作失败: ' + jqXHR.responseJSON.message);
                } else {
                    alert('保存过程中发生网络或服务器错误。');
                }
            }
        });
    });

    $('.table').on('click', '.delete-btn', function() {
        const dataId = $(this).data('id');
        const dataName = $(this).data('name');
        if (confirm(`您确定要删除分类 "${dataName}" 吗？`)) {
            $.ajax({
                url: 'api/pos_category_handler.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ action: 'delete', id: dataId }),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        alert(response.message);
                        window.location.reload();
                    } else {
                        alert('删除失败: ' + response.message);
                    }
                },
                error: function() {
                    alert('删除过程中发生网络或服务器错误。');
                }
            });
        }
    });
});