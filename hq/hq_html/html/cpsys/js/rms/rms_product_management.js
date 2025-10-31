/**
 * Toptea HQ - RMS (Recipe Management System) JavaScript
 * Engineer: Gemini | Date: 2025-10-31
 */
$(document).ready(function() {

    const productListContainer = $('#product-list-container');
    const editorContainer = $('#product-editor-container');
    const templatesContainer = $('#rms-templates');

    // Event Delegation for clicking on a product in the list
    productListContainer.on('click', '.list-group-item-action', function(e) {
        e.preventDefault();
        
        productListContainer.find('.list-group-item-action').removeClass('active');
        $(this).addClass('active');

        const productId = $(this).data('productId');
        loadProductEditor(productId);
    });

    function loadProductEditor(productId) {
        editorContainer.html('<div class="card-body text-center"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>');

        $.ajax({
            url: 'api/rms/product_handler.php',
            type: 'GET',
            data: { action: 'get_product_details', id: productId },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    renderProductEditor(response.data);
                } else {
                    editorContainer.html(`<div class="alert alert-danger">${response.message}</div>`);
                }
            },
            error: function() {
                editorContainer.html('<div class="alert alert-danger">加载产品数据时发生网络错误。</div>');
            }
        });
    }

    function renderProductEditor(data) {
        // In the next step, we will build a comprehensive HTML template here
        // to display the base recipe and adjustment rules.
        let editorHtml = `
            <div class="card">
                <div class="card-header">
                    编辑产品: <strong>${data.product_code} - ${data.name_zh}</strong>
                </div>
                <div class="card-body">
                    <p>产品ID: ${data.id}</p>
                    <h5 class="mt-4">基础配方</h5>
                    <pre>${JSON.stringify(data.base_recipes, null, 2)}</pre>
                    <h5 class="mt-4">动态调整规则</h5>
                    <pre>${JSON.stringify(data.adjustments, null, 2)}</pre>
                    <p class="mt-4 text-muted"><em>（完整编辑器将在下一步实现）</em></p>
                </div>
            </div>
        `;
        editorContainer.html(editorHtml);
    }

});