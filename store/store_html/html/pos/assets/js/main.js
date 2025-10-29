import { STATE, I18N } from './state.js';
import { applyI18N, renderCategories, renderProducts, renderAddons, openCustomize, updateCustomizePrice, refreshCartUI, updateMemberUI } from './ui.js';
import { fetchInitialData } from './api.js';
import { t, toast } from './utils.js';
import { addToCart, updateCartItem, calculatePromotions } from './modules/cart.js';
import { openPaymentModal, addPaymentPart, updatePaymentState, submitOrder } from './modules/payment.js';
import { openHoldOrdersPanel, createHoldOrder, restoreHeldOrder, refreshHeldOrdersList } from './modules/hold.js';
import { openEodModal, openEodConfirmationModal, submitEodReportFinal } from './modules/eod.js';
import { openTxnQueryPanel, showTxnDetails } from './modules/transactions.js';
import { handleSettingChange } from './modules/settings.js';
import { findMember, unlinkMember, showCreateMemberModal, createMember } from './modules/member.js';

// Add new I18N keys for points redemption (hardened)
const I18N_NS = (typeof I18N === 'object' && I18N) ? I18N : (window.I18N = window.I18N || {});
I18N_NS.zh = I18N_NS.zh || {};
I18N_NS.es = I18N_NS.es || {};

Object.assign(I18N_NS.zh, {
  points_redeem_placeholder: '使用积分',
  points_apply_btn: '应用',
  points_rule: '100积分 = 1€',
  points_feedback_applied: '已用 {points} 积分抵扣 €{amount}',
  points_feedback_not_enough: '积分不足或超出上限',
  // --- EOD Leak Texts ---
  unclosed_eod_title: '操作提醒',
  unclosed_eod_header: '上一营业日未日结',
  unclosed_eod_message: '系统检测到日期为 {date} 的营业日没有日结报告。',
  unclosed_eod_instruction: '为保证数据准确，请先完成该日期的日结，再开始新的营业日。',
  unclosed_eod_button: '立即完成上一日日结',
  unclosed_eod_force_button: '强制开启新一日 (需授权)', // Kept for future
});
Object.assign(I18N_NS.es, {
  points_redeem_placeholder: 'Usar puntos',
  points_apply_btn: 'Aplicar',
  points_rule: '100 puntos = 1€',
  points_feedback_applied: '{points} puntos aplicados, descuento de €{amount}',
  points_feedback_not_enough: 'Puntos insuficientes o excede el límite',
  // --- EOD Leak Texts ---
  unclosed_eod_title: 'Aviso de Operación',
  unclosed_eod_header: 'Día Anterior No Cerrado',
  unclosed_eod_message: 'El sistema detectó que el día hábil con fecha {date} no tiene informe de cierre.',
  unclosed_eod_instruction: 'Para garantizar la precisión de los datos, complete primero el cierre de ese día antes de comenzar un nuevo día hábil.',
  unclosed_eod_button: 'Completar Cierre Anterior Ahora',
  unclosed_eod_force_button: 'Forzar Inicio Nuevo Día (Requiere Autorización)', // Kept for future
});

// --- 核心修复：显示漏结提示的纯HTML覆盖层 ---
function showUnclosedEodOverlay(unclosedDate) {
    // 移除可能存在的旧覆盖层
    const existingOverlay = document.getElementById('eod-block-overlay');
    if (existingOverlay) {
        existingOverlay.remove();
    }

    const overlay = document.createElement('div');
    overlay.id = 'eod-block-overlay'; // Use ID for easier removal/styling
    overlay.innerHTML = `
        <div class="eod-block-content">
            <div class="eod-block-header">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <span>${t('unclosed_eod_title')}</span>
            </div>
            <div class="eod-block-body">
                <h4>${t('unclosed_eod_header')}</h4>
                <p>${t('unclosed_eod_message').replace('{date}', `<strong>${unclosedDate}</strong>`)}</p>
                <p class="text-muted small">${t('unclosed_eod_instruction')}</p>
            </div>
            <div class="eod-block-footer">
                <button type="button" class="btn btn-secondary" disabled>${t('unclosed_eod_force_button')}</button>
                <button type="button" class="btn btn-primary" id="btn_eod_now_overlay">${t('unclosed_eod_button')}</button>
            </div>
        </div>
    `;
    document.body.appendChild(overlay);

    // 为按钮绑定事件
    const btnEodNow = document.getElementById('btn_eod_now_overlay');
    if (btnEodNow) {
        btnEodNow.addEventListener('click', () => {
            // 点击后移除覆盖层，然后安全地打开 EOD Modal
            overlay.remove();
            openEodModal();
        });
    }
}


function bindEvents() {
  // --- Language & Sync ---
  $('.dropdown-menu [data-lang]').on('click', function(e) {
    e.preventDefault();
    STATE.lang = $(this).data('lang');
    localStorage.setItem('POS_LANG', STATE.lang);
    applyI18N();
    renderCategories();
    renderProducts();
    refreshCartUI();
    renderAddons();
    updateMemberUI();
  });
  $('#btn_sync').on('click', function() {
    $(this).prop('disabled', true).html('<span class="spinner-border spinner-border-sm"></span>');
    initApplication().finally(() => $(this).prop('disabled', false).html('<i class="bi bi-arrow-repeat"></i>'));
  });

  // --- Product & Customization ---
  $(document).on('click', '#category_scroller .nav-link', function() { STATE.active_category_key = $(this).data('cat'); renderCategories(); renderProducts(); });
  $('#search_input').on('input', renderProducts);
  $('#clear_search').on('click', () => $('#search_input').val('').trigger('input'));
  $(document).on('click', '.product-card', function() { openCustomize($(this).data('id')); });
  $(document).on('change', 'input[name="variant_selector"]', updateCustomizePrice);
  $(document).on('click', '#addon_list .addon-chip', function() { $(this).toggleClass('active'); updateCustomizePrice(); });
  $('input[name="ice"], input[name="sugar"]').on('change', updateCustomizePrice);
  $('#btn_add_to_cart').on('click', addToCart);

  // --- Cart ---
  $('#cartOffcanvas').on('show.bs.offcanvas', () => { calculatePromotions(); updateMemberUI(); });
  $(document).on('click', '#cart_items [data-act]', function() { updateCartItem($(this).data('id'), $(this).data('act')); });
  $(document).on('click', '#apply_coupon_btn', () => calculatePromotions(true));
  
  // --- Points Redemption Event ---
  $(document).on('click', '#apply_points_btn', () => calculatePromotions());

  // --- Payment ---
  $('#btn_cart_checkout').on('click', openPaymentModal);
  $('#btn_confirm_payment').on('click', submitOrder);
  $(document).on('click', '[data-pay-method]', function() { addPaymentPart($(this).data('pay-method')); });
  $(document).on('click', '.remove-part-btn', function() { $(this).closest('.payment-part').remove(); updatePaymentState(); });
  $(document).on('input', '.payment-part-input', updatePaymentState);
  
  // --- Ops Panel & Modals ---
  $('#btn_open_eod').on('click', openEodModal);
  $('#btn_open_holds').on('click', openHoldOrdersPanel);
  $('#btn_open_txn_query').on('click', openTxnQueryPanel);
  
  // --- Hold ---
  $('#btn_hold_current_cart').on('click', function() { if (STATE.cart.length === 0) { toast(t('tip_empty_cart')); return; } const cartOffcanvas = bootstrap.Offcanvas.getInstance('#cartOffcanvas'); if (cartOffcanvas) cartOffcanvas.hide(); setTimeout(() => $('#hold_order_note_input').focus(), 400); });
  $('#btn_create_new_hold').on('click', createHoldOrder);
  $(document).on('click', '.restore-hold-btn', function(e) { e.preventDefault(); restoreHeldOrder($(this).data('id')); });
  $('#holdOrdersOffcanvas .dropdown-item').on('click', function(e) { e.preventDefault(); STATE.holdSortBy = $(this).data('sort'); const sortKey = STATE.holdSortBy === 'time_desc' ? 'sort_by_time' : 'sort_by_amount'; $('#holdOrdersOffcanvas .dropdown-toggle').html(`<i class="bi bi-sort-down"></i> ${t(sortKey)}`); refreshHeldOrdersList(); });

  // --- EOD ---
  $(document).on('click', '#btn_submit_eod_start', openEodConfirmationModal);
  $(document).on('click', '#btn_confirm_eod_final', submitEodReportFinal);
  
  // EOD Leak Handler button is now added dynamically in showUnclosedEodOverlay
  
  // --- Txn Query ---
  $(document).on('click', '.txn-item', function(e) { e.preventDefault(); showTxnDetails($(this).data('id')); });

  // --- Member ---
  $(document).on('click', '#btn_find_member', findMember);
  $(document).on('click', '#btn_unlink_member', unlinkMember);
  $(document).on('click', '#btn_show_create_member', showCreateMemberModal);
  $(document).on('submit', '#form_create_member', createMember);
  
  // --- Settings ---
  $('#settingsOffcanvas input').on('change', handleSettingChange);
}

async function initApplication() {
    try {
        // --- 核心修复：检查移回开头 ---
        const eodStatusResponse = await fetch('./api/check_eod_status.php');
        const eodStatusResult = await eodStatusResponse.json();

        if (eodStatusResult.status === 'success' && eodStatusResult.data.previous_day_unclosed) {
            // --- 核心修复：显示纯HTML覆盖层，阻止后续加载 ---
            showUnclosedEodOverlay(eodStatusResult.data.unclosed_date);
            console.log("Previous EOD unclosed. Blocking UI.");
            return; // 阻止加载主界面
        }

        // --- 只有在没有漏结时才执行 ---
        await fetchInitialData();
        applyI18N();
        updateMemberUI();
        renderCategories();
        renderProducts();
        renderAddons();
        refreshCartUI();

    } catch (error) {
        console.error("Fatal Error during initialization:", error);
        // 使用更简单的错误显示，避免依赖可能未加载的 $()
        document.body.innerHTML = `<div class="alert alert-danger m-5"><strong>Fatal Error:</strong> Could not initialize POS. Error: ${error.message}</div>`;
    }
}

// --- Main Execution ---
$(() => {
    bindEvents();
    initApplication();
});