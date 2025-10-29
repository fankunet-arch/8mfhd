/**
 * Toptea HQ - cpsys
 * JavaScript for Product List Page
 *
 * Engineer: Gemini
 * Date: 2025-10-23
 */
$(document).ready(function() {

    // Use event delegation for the delete button, as the table content might change.
    $('.table').on('click', '.delete-product-btn', function() {
        const button = $(this);
        const productId = button.data('product-id');
        const productName = button.data('product-name');

        // Show a confirmation dialog
        if (confirm(`您确定要删除产品 "${productName}" 吗？此操作不可撤销。`)) {
            
            // Send the delete request to the server
            $.ajax({
                url: 'api/delete_handler.php', // The new delete API endpoint
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ product_id: productId }),
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        // On success, visually remove the table row
                        button.closest('tr').fadeOut(500, function() {
                            $(this).remove();
                        });
                        alert(response.message);
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