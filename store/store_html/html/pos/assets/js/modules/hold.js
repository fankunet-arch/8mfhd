import { STATE } from '../state.js';
import { t, fmtEUR, toast } from '../utils.js';
import { calculatePromotions } from './cart.js';

export async function openHoldOrdersPanel() {
    const opsOffcanvas = bootstrap.Offcanvas.getInstance('#opsOffcanvas');
    if (opsOffcanvas) opsOffcanvas.hide();
    const holdOffcanvas = new bootstrap.Offcanvas('#holdOrdersOffcanvas');
    holdOffcanvas.show();
    await refreshHeldOrdersList();
}

export async function refreshHeldOrdersList() {
    const $list = $('#held_orders_list').html('<div class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></div>');
    try {
        const response = await fetch(`api/pos_hold_handler.php?action=list&sort=${STATE.holdSortBy}`);
        const result = await response.json();
        if (result.status === 'success') {
            if (!result.data || result.data.length === 0) {
                $list.html(`<div class="alert alert-sheet">${t('no_held_orders')}</div>`);
                return;
            }
            let html = '<div class="list-group list-group-flush">';
            result.data.forEach(order => {
                const noteDisplay = order.note ? order.note.replace(/</g, "&lt;").replace(/>/g, "&gt;") : `<em class="text-muted">(No Note)</em>`;
                const timeDisplay = new Date(order.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                html += `<div class="list-group-item"><div class="d-flex w-100 justify-content-between"><div><h6 class="mb-1">${noteDisplay}</h6><small class="text-muted">${timeDisplay}</small></div><div class="text-end"><strong class="d-block">${fmtEUR(order.total_amount)}</strong><button class="btn btn-sm btn-brand mt-1 restore-hold-btn" data-id="${order.id}">${t('restore')}</button></div></div></div>`;
            });
            html += '</div>';
            $list.html(html);
        } else { throw new Error(result.message); }
    } catch (error) { $list.html(`<div class="alert alert-danger">${error.message}</div>`); }
}

export async function createHoldOrder() {
    const note = $('#hold_order_note_input').val().trim();
    if (!note) {
        toast(t('note_is_required'));
        $('#hold_order_note_input').focus();
        return;
    }
    if (STATE.cart.length === 0) {
        toast(t('tip_empty_cart'));
        const holdOffcanvas = bootstrap.Offcanvas.getInstance('#holdOrdersOffcanvas');
        if (holdOffcanvas) holdOffcanvas.hide();
        return;
    }
    try {
        const response = await fetch('api/pos_hold_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'save', note: note, cart: STATE.cart })
        });
        const result = await response.json();
        if (result.status === 'success') {
            toast('当前订单已挂起');
            STATE.cart = [];
            calculatePromotions();
            const holdOffcanvas = bootstrap.Offcanvas.getInstance('#holdOrdersOffcanvas');
            if (holdOffcanvas) holdOffcanvas.hide();
            $('#hold_order_note_input').val('');
        } else { throw new Error(result.message); }
    } catch (error) { toast(t('hold_failed') + ': ' + error.message); }
}

export async function restoreHeldOrder(id) {
    if (STATE.cart.length > 0) {
        if (!confirm('当前购物车非空，恢复挂起单将覆盖当前内容，确定吗？')) {
            return;
        }
    }
    try {
        const response = await fetch(`api/pos_hold_handler.php?action=restore&id=${id}`);
        if (!response.ok) {
            const errorText = await response.text();
            let errorJson;
            try { errorJson = JSON.parse(errorText); } catch (e) { throw new Error(errorText || `服务器错误: ${response.status}`); }
            throw new Error(errorJson.message || '恢复订单时发生未知错误。');
        }
        const result = await response.json();
        if (result.status === 'success') {
            STATE.cart = result.data;
            calculatePromotions();
            bootstrap.Offcanvas.getInstance('#holdOrdersOffcanvas').hide();
            toast('订单已恢复');
        } else {
            throw new Error(result.message || '恢复订单失败。');
        }
    } catch (error) { toast(t('restore_failed') + ': ' + error.message); }
}