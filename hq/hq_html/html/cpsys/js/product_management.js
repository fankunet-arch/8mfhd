/**
 * Toptea HQ - cpsys
 * JavaScript for Product Management Page
 * Engineer: Gemini | Date: 2025-10-25 | Revision: 6.8 (Drag Handle Fix)
 */
$(document).ready(function() {

    // --- SortableJS Initialization ---
    ['base-ingredients', 'cup-ingredients', 'top-ingredients'].forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            new Sortable(el, {
                animation: 150,
                ghostClass: 'sortable-ghost',
                handle: '.drag-handle', // --- CORE FIX: Use the dedicated drag handle ---
            });
        }
    });

    // --- Add/Delete Row Logic ---
    $('.add-recipe-row').on('click', function() {
        const newRow = $('#recipe-row-template .recipe-row').clone();
        $(this).before(newRow);
    });
    $('.accordion-body').on('click', '.delete-recipe-row', function() {
        $(this).closest('.recipe-row').remove();
    });
    
    // --- Save Product Logic (unchanged) ---
    $('#save-product-btn').on('click', function() {
        
        const productData = {
            sku: $('#product-sku').val(),
            name_zh: $('#product-name-zh').val(),
            name_es: $('#product-name-es').val(),
            cup_id: $('#cup-type').val(),
            status_id: $('#product-status').val(),
            recipes: { base: [], mixing: [], topping: [] }
        };

        if (!productData.sku || !productData.name_zh || !productData.cup_id || !productData.status_id) {
            alert('请填写所有必填的主体信息 (饮品编码, 中文名, 杯型, 状态)。');
            return;
        }

        $('#base-ingredients .recipe-row').each(function() {
            const row = $(this);
            productData.recipes.base.push({ material_id: row.find('.material-select').val(), quantity: row.find('.quantity-input').val(), unit_id: row.find('.unit-select').val() });
        });
        $('#cup-ingredients .recipe-row').each(function() {
            const row = $(this);
            productData.recipes.mixing.push({ material_id: row.find('.material-select').val(), quantity: row.find('.quantity-input').val(), unit_id: row.find('.unit-select').val() });
        });
        $('#top-ingredients .recipe-row').each(function() {
            const row = $(this);
            productData.recipes.topping.push({ material_id: row.find('.material-select').val(), quantity: row.find('.quantity-input').val(), unit_id: row.find('.unit-select').val() });
        });
        
        console.log("Data to be sent:", productData);

        $.ajax({
            url: 'api/product_handler.php',
            type: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ data: productData }),
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    alert(response.message);
                    window.location.href = 'index.php?page=product_list';
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
});