import { t, fmtEUR, toast } from '../utils.js';
// --- CORE ADDITION: Import STATE for potential future use (e.g., permissions check) ---
import { STATE } from '../state.js';

// --- CORE ADDITION: Define confirmation modal instance (to be initialized in main.js) ---
let refundConfirmModal = null;
let currentActionContext = null; // To store { actionType: 'cancel'/'correct', invoiceId: xxx }

// --- CORE ADDITION: Initialize confirmation modal ---
export function initializeRefundModal(modalInstance) {
    refundConfirmModal = modalInstance;
    const confirmButton = document.getElementById('btn_confirm_refund_action');
    if (confirmButton) {
        confirmButton.addEventListener('click', () => {
            if (currentActionContext) {
                // In a real scenario, call the appropriate backend API here
                console.log(`Confirmed action: ${currentActionContext.actionType} for invoice ID: ${currentActionContext.invoiceId}`);
                toast(`模拟操作：${currentActionContext.actionType === 'cancel' ? t('cancel_invoice') : t('correct_invoice')} (ID: ${currentActionContext.invoiceId})`); // Simulate action
                refundConfirmModal.hide();
                // Optionally refresh the list or close the detail modal
                bootstrap.Modal.getInstance(document.getElementById('txnDetailModal'))?.hide();
                refreshTxnList(); // Refresh list after action
            }
        });
    }
}

// --- CORE ADDITION: Function to open confirmation modal ---
function requestRefundActionConfirmation(actionType, invoiceId, invoiceNumber) {
    if (!refundConfirmModal) {
        toast('错误：确认模态框未初始化');
        return;
    }
    currentActionContext = { actionType, invoiceId };
    const modalTitle = document.getElementById('refundConfirmModalLabel');
    const modalBody = document.getElementById('refundConfirmModalBody');
    const confirmButton = document.getElementById('btn_confirm_refund_action');

    if (actionType === 'cancel') {
        modalTitle.textContent = t('confirm_cancel_invoice_title');
        modalBody.textContent = t('confirm_cancel_invoice_body').replace('{invoiceNumber}', invoiceNumber);
        confirmButton.textContent = t('confirm_cancel_invoice_confirm');
        confirmButton.classList.remove('btn-warning');
        confirmButton.classList.add('btn-danger');
    } else if (actionType === 'correct') {
        modalTitle.textContent = t('confirm_correct_invoice_title');
        modalBody.textContent = t('confirm_correct_invoice_body').replace('{invoiceNumber}', invoiceNumber);
        confirmButton.textContent = t('confirm_correct_invoice_confirm');
        confirmButton.classList.remove('btn-danger');
        confirmButton.classList.add('btn-warning');
    }
    refundConfirmModal.show();
}


function setupDatePickers() {
    const startDateInput = document.getElementById('txn_start_date');
    const endDateInput = document.getElementById('txn_end_date');
    const today = new Date().toISOString().split('T')[0];

    startDateInput.max = today;
    endDateInput.max = today;

    function updateDateLimits() {
        const startDateValue = startDateInput.value;
        if (!startDateValue) return;

        const startDate = new Date(startDateValue);

        endDateInput.min = startDateValue;

        const maxEndDate = new Date(startDate);
        maxEndDate.setMonth(maxEndDate.getMonth() + 1);

        const finalMaxEndDate = new Date(Math.min(maxEndDate, new Date(today)));
        endDateInput.max = finalMaxEndDate.toISOString().split('T')[0];
    }

    function updateStartDateLimits() {
        const endDateValue = endDateInput.value;
        if(!endDateValue) return;

        const endDate = new Date(endDateValue);

        startDateInput.max = endDateValue;

        const minStartDate = new Date(endDate);
        minStartDate.setMonth(minStartDate.getMonth() - 1);
        startDateInput.min = minStartDate.toISOString().split('T')[0];
    }

    startDateInput.addEventListener('change', updateDateLimits);
    endDateInput.addEventListener('change', updateStartDateLimits);

    // Initial setup
    updateDateLimits();
    updateStartDateLimits();
}

function validateDateRange() {
    const startDateInput = document.getElementById('txn_start_date');
    const endDateInput = document.getElementById('txn_end_date');
    const startDate = startDateInput.value;
    const endDate = endDateInput.value;

    if (!startDate || !endDate) {
        toast(t('validation_select_dates')); // Use translation key
        return false;
    }

    const today = new Date();
    today.setHours(23, 59, 59, 999);

    const selectedStartDate = new Date(startDate);
    const selectedEndDate = new Date(endDate);

    if (selectedEndDate < selectedStartDate) {
        toast(t('validation_end_date_before_start'));
        return false;
    }
    if (selectedEndDate > today) {
        toast(t('validation_end_date_in_future'));
        return false;
    }

    const diffTime = Math.abs(selectedEndDate - selectedStartDate);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

    if (diffDays > 31) { // Loosen to 31 days to be safe
        toast(t('validation_date_range_too_large'));
        return false;
    }

    return true;
}

export async function openTxnQueryPanel() {
    const opsOffcanvasEl = document.getElementById('opsOffcanvas');
    if (opsOffcanvasEl) {
        const opsOffcanvas = bootstrap.Offcanvas.getInstance(opsOffcanvasEl);
        if (opsOffcanvas) opsOffcanvas.hide();
    }

    const container = document.getElementById('txn_list_container');
    const txnQueryOffcanvasEl = document.getElementById('txnQueryOffcanvas');

    if (!container.querySelector('#txn_filter_form')) {
        const today = new Date().toISOString().split('T')[0];
        const filterHtml = `
            <div id="txn_filter_form" class="p-3 border-bottom">
                <div class="row g-2 align-items-end">
                    <div class="col">
                        <label for="txn_start_date" class="form-label small">${t('start_date')}</label>
                        <input type="date" class="form-control" id="txn_start_date" value="${today}">
                    </div>
                    <div class="col">
                        <label for="txn_end_date" class="form-label small">${t('end_date')}</label>
                        <input type="date" class="form-control" id="txn_end_date" value="${today}">
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-primary" id="btn_filter_txn">${t('query')}</button>
                    </div>
                </div>
            </div>
            <div id="txn_list_target"></div>
        `;
        container.innerHTML = filterHtml;

        document.getElementById('btn_filter_txn').addEventListener('click', () => {
            if (validateDateRange()) {
                refreshTxnList();
            }
        });

        setupDatePickers();
    }

    const txnQueryOffcanvas = new bootstrap.Offcanvas(txnQueryOffcanvasEl);

    txnQueryOffcanvasEl.addEventListener('shown.bs.offcanvas', () => {
        refreshTxnList();
    }, { once: true });

    txnQueryOffcanvas.show();
}

async function refreshTxnList() {
    const listTarget = document.getElementById('txn_list_target');
    const startDate = document.getElementById('txn_start_date').value;
    const endDate = document.getElementById('txn_end_date').value;

    listTarget.innerHTML = '<div class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></div>';

    let apiUrl = `api/pos_transaction_handler.php?action=list&start_date=${startDate}&end_date=${endDate}`;

    try {
        const response = await fetch(apiUrl);
        const result = await response.json();
        if (result.status === 'success') {
            if (!result.data || result.data.length === 0) {
                listTarget.innerHTML = `<div class="alert alert-sheet m-3">${t('no_transactions')}</div>`;
                return;
            }
            let html = '<div class="list-group list-group-flush">';
            result.data.forEach(txn => {
                // Ensure issued_at is treated as local time for display
                const localTime = txn.issued_at.replace(' ', 'T'); // Make it ISO-like for Date constructor robustness
                const time = new Date(localTime).toLocaleString(STATE.lang === 'zh' ? 'zh-CN' : 'es-ES', { hour12: false, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });
                const statusClass = txn.status === 'CANCELLED' ? 'text-danger' : '';
                const statusText = txn.status === 'CANCELLED' ? `(${t('cancelled')})` : '';
                const invoiceNumber = `${txn.series || ''}-${txn.number || txn.id}`;
                html += `<a href="#" class="list-group-item list-group-item-action txn-item" data-id="${txn.id}"><div class="d-flex w-100 justify-content-between"><h6 class="mb-1">${invoiceNumber} <small class="${statusClass}">${statusText}</small></h6><strong>${fmtEUR(txn.final_total)}</strong></div><small>${time}</small></a>`;
            });
            html += '</div>';
            listTarget.innerHTML = html;
        } else { throw new Error(result.message); }
    } catch (error) { listTarget.innerHTML = `<div class="alert alert-danger m-3">${error.message}</div>`; }
}

export async function showTxnDetails(id) {
    const detailModalEl = document.getElementById('txnDetailModal');
    const detailModal = new bootstrap.Modal(detailModalEl);
    const modalTitleEl = document.getElementById('txn_detail_title');
    const modalBodyEl = document.getElementById('txn_detail_body');
    // --- CORE ADDITION: Get footer element ---
    const modalFooterEl = document.getElementById('txn_detail_footer');

    modalTitleEl.textContent = `${t('loading')}...`; // Use translation key
    modalBodyEl.innerHTML = '<div class="text-center p-4"><div class="spinner-border"></div></div>';
    modalFooterEl.innerHTML = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${t('close')}</button>`; // Default close button
    detailModal.show();

    try {
        const response = await fetch(`api/pos_transaction_handler.php?action=get_details&id=${id}`);
        const result = await response.json();
        if (result.status === 'success') {
            const d = result.data;
            const invoiceNumber = `${d.series || ''}-${d.number || d.id}`;
            let itemsHtml = '';
            (d.items || []).forEach(item => {
                let customs = {};
                try { customs = JSON.parse(item.customizations) || {}; } catch(e) {}
                const customText = `I:${customs.ice || 'N/A'} S:${customs.sugar || 'N/A'} +:${(customs.addons || []).join(',')}`;
                itemsHtml += `<tr><td>${item.item_name || '?'} <small class="text-muted">(${item.variant_name || '?'})</small><br><small class="text-muted">${customText}</small></td><td>${item.quantity || 0}</td><td>${fmtEUR(item.unit_price)}</td><td>${fmtEUR((item.unit_price || 0) * (item.quantity || 0))}</td></tr>`;
            });

            const statusBadge = `<span class="badge text-bg-${d.status === 'CANCELLED' ? 'danger':'success'}">${t(d.status.toLowerCase())}</span>`;
            // Ensure issued_at is treated as local time
            const localTime = d.issued_at.replace(' ', 'T');
            const timeDisplay = new Date(localTime).toLocaleString(STATE.lang === 'zh' ? 'zh-CN' : 'es-ES', { hour12: false, year: 'numeric', month: '2-digit', day: '2-digit', hour: '2-digit', minute: '2-digit' });

            const bodyHtml = `
                <p><strong>${t('invoice_number')}:</strong> ${invoiceNumber}</p>
                <p><strong>${t('time')}:</strong> ${timeDisplay}</p>
                <p><strong>${t('cashier')}:</strong> ${d.cashier_name || 'N/A'}</p>
                <p><strong>${t('status')}:</strong> ${statusBadge}</p>
                <hr>
                <h5>${t('item_list')}</h5>
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead><tr><th>${t('item')}</th><th>${t('qty')}</th><th>${t('unit_price')}</th><th>${t('total_price')}</th></tr></thead>
                        <tbody>${itemsHtml || `<tr><td colspan="4" class="text-center text-muted">${t('no_items')}</td></tr>`}</tbody>
                    </table>
                </div>
                <hr>
                <div class="text-end">
                    <div><small>${t('subtotal')}:</small> ${fmtEUR(d.taxable_base)}</div>
                    <div><small>${t('vat')}:</small> ${fmtEUR(d.vat_amount)}</div>
                    <div class="fs-5 fw-bold">${t('total')}: ${fmtEUR(d.final_total)}</div>
                </div>`;

            modalTitleEl.textContent = `${t('invoice_details')}: ${invoiceNumber}`;
            modalBodyEl.innerHTML = bodyHtml;

            // --- CORE ADDITION: Add action buttons based on status ---
            let footerHtml = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${t('close')}</button>`;
            if (d.status === 'ISSUED') {
                footerHtml += `
                    <button type="button" class="btn btn-warning btn-correct-invoice" data-id="${d.id}" data-number="${invoiceNumber}">
                        <i class="bi bi-pencil-square"></i> ${t('correct_invoice')}
                    </button>
                    <button type="button" class="btn btn-danger btn-cancel-invoice" data-id="${d.id}" data-number="${invoiceNumber}">
                        <i class="bi bi-trash"></i> ${t('cancel_invoice')}
                    </button>
                `;
            }
            modalFooterEl.innerHTML = footerHtml;


        } else { throw new Error(result.message); }
    } catch (error) {
        modalBodyEl.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
        modalFooterEl.innerHTML = `<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">${t('close')}</button>`;
    }
}

// Add necessary I18N strings (should be done in state.js ideally)
// Example additions:
/*
I18N.zh = {
    ...I18N.zh,
    cancel_invoice: '作废此单',
    correct_invoice: '开具更正票据',
    confirm_cancel_invoice_title: '确认作废票据',
    confirm_cancel_invoice_body: '您确定要作废票据 {invoiceNumber} 吗？此操作不可逆。',
    confirm_cancel_invoice_confirm: '确认作废',
    confirm_correct_invoice_title: '确认开具更正票据',
    confirm_correct_invoice_body: '为票据 {invoiceNumber} 开具更正票据？请在 HQ 后台完成后续操作。',
    confirm_correct_invoice_confirm: '确认开具',
    loading: '加载中',
    time: '时间',
    cashier: '收银员',
    status: '状态',
    item_list: '商品列表',
    item: '商品',
    qty: '数量',
    unit_price: '单价',
    total_price: '总价',
    no_items: '无商品',
    subtotal: '税前',
    vat: '税额',
    total: '总计',
    invoice_details: '票据详情',
    close: '关闭',
    validation_select_dates: '请选择起始和截止日期'
};
I18N.es = {
     ...I18N.es,
    cancel_invoice: 'Anular Ticket',
    correct_invoice: 'Factura Rectificativa',
    confirm_cancel_invoice_title: 'Confirmar Anulación',
    confirm_cancel_invoice_body: '¿Seguro que desea anular el ticket {invoiceNumber}? Esta acción es irreversible.',
    confirm_cancel_invoice_confirm: 'Confirmar Anulación',
    confirm_correct_invoice_title: 'Confirmar Factura Rectificativa',
    confirm_correct_invoice_body: '¿Emitir factura rectificativa para el ticket {invoiceNumber}? Complete la operación en el HQ.',
    confirm_correct_invoice_confirm: 'Confirmar Emisión',
    loading: 'Cargando',
    time: 'Hora',
    cashier: 'Cajero',
    status: 'Estado',
    item_list: 'Lista de artículos',
    item: 'Artículo',
    qty: 'Cant.',
    unit_price: 'P. Unit.',
    total_price: 'Total',
    no_items: 'Sin artículos',
    subtotal: 'Base Imp.',
    vat: 'IVA',
    total: 'Total',
    invoice_details: 'Detalles del Ticket',
    close: 'Cerrar',
    validation_select_dates: 'Por favor, seleccione las fechas de inicio y fin'
};
*/
