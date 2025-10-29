import { STATE } from '../state.js';
import { t, fmtEUR, toast } from '../utils.js';
import { submitOrderAPI } from '../api.js';
import { calculatePromotions } from './cart.js';
import { unlinkMember } from './member.js'; // This import will now work correctly

export function openPaymentModal() {
    if (STATE.cart.length === 0) {
        toast(t('tip_empty_cart'));
        return;
    }
    const finalTotal = parseFloat(STATE.calculatedCart.final_total) || 0;
    STATE.payment = { total: finalTotal, parts: [] };
    $('#payment_parts_container').empty();
    addPaymentPart('Cash');
    bootstrap.Offcanvas.getInstance('#cartOffcanvas')?.hide();
    new bootstrap.Modal('#paymentModal').show();
}

export function updatePaymentState() {
    let totalPaid = 0;
    $('#payment_parts_container .payment-part-input').each(function () {
        totalPaid += parseFloat($(this).val()) || 0;
    });
    const totalReceivable = STATE.payment.total;
    const remaining = totalReceivable - totalPaid;
    const change = remaining < 0 ? -remaining : 0;
    $('#payment_total_display').text(fmtEUR(totalReceivable));
    $('#payment_paid_display').text(fmtEUR(totalPaid));
    $('#payment_remaining_display').text(fmtEUR(remaining > 0 ? remaining : 0));
    $('#payment_change_display').text(fmtEUR(change));
    $('#btn_confirm_payment').prop('disabled', remaining > 0.005);
}

export function addPaymentPart(method) {
    const remaining = STATE.payment.total - Array.from($('#payment_parts_container .payment-part-input')).reduce((sum, el) => sum + (parseFloat(el.value) || 0), 0);
    const $newPart = $(`#payment_templates .payment-part[data-method="${method}"]`).clone();
    $newPart.find('.payment-part-input').val((remaining > 0 ? remaining : 0).toFixed(2));
    $('#payment_parts_container').append($newPart);
    $newPart.find('.payment-part-input').focus().select();
    updatePaymentState();
}

export async function submitOrder() {
    const checkoutBtn = $('#btn_confirm_payment');
    checkoutBtn.prop('disabled', true).html(`<span class="spinner-border spinner-border-sm me-2"></span>${t('submitting_order')}`);

    const paymentParts = [];
    let totalPaid = 0;
    $('#payment_parts_container .payment-part').each(function () {
        const $part = $(this);
        const method = $part.data('method');
        const amount = parseFloat($part.find('.payment-part-input').val()) || 0;
        if (amount > 0) {
            const partData = { method: method, amount: amount };
            if (method === 'Platform') {
                partData.reference = $part.find('.payment-part-ref').val().trim();
            }
            paymentParts.push(partData);
            totalPaid += amount;
        }
    });

    const paymentPayload = {
        total: STATE.payment.total,
        paid: totalPaid,
        change: totalPaid - STATE.payment.total > 0 ? totalPaid - STATE.payment.total : 0,
        summary: paymentParts
    };

    try {
        const result = await submitOrderAPI(paymentPayload);
        if (result.status === 'success') {
            bootstrap.Modal.getInstance('#paymentModal').hide();
            $('#success_invoice_number').text(result.data.invoice_number);
            $('#success_qr_content').text(result.data.qr_content);
            new bootstrap.Modal('#orderSuccessModal').show();

            // Reset state after successful order
            STATE.cart = [];
            STATE.activeCouponCode = '';
            $('#coupon_code_input').val('');
            unlinkMember();
            calculatePromotions();
        } else {
            throw new Error(result.message || 'Unknown server error.');
        }
    } catch (error) {
        console.error('Failed to submit order:', error);
        toast(t('order_submit_failed') + ': ' + error.message);
    } finally {
        checkoutBtn.prop('disabled', false).html(t('confirm_payment'));
    }
}