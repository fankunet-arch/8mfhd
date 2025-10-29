import { t, fmtEUR, toast } from '../utils.js';

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
        toast('请选择起始和截止日期');
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

    // --- 核心修复：监听 'shown.bs.offcanvas' 事件 ---
    // 这个事件确保在抽屉动画完全结束后，才执行加载内容的函数。
    txnQueryOffcanvasEl.addEventListener('shown.bs.offcanvas', () => {
        refreshTxnList();
    }, { once: true }); // { once: true } 表示这个监听器在触发一次后会自动移除，避免重复执行。

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
                const time = new Date(txn.issued_at).toLocaleString('zh-CN', { hour12: false });
                const statusClass = txn.status === 'CANCELLED' ? 'text-danger' : '';
                const statusText = txn.status === 'CANCELLED' ? `(${t('cancelled')})` : '';
                html += `<a href="#" class="list-group-item list-group-item-action txn-item" data-id="${txn.id}"><div class="d-flex w-100 justify-content-between"><h6 class="mb-1">${txn.series}-${txn.number} <small class="${statusClass}">${statusText}</small></h6><strong>${fmtEUR(txn.final_total)}</strong></div><small>${time}</small></a>`;
            });
            html += '</div>';
            listTarget.innerHTML = html;
        } else { throw new Error(result.message); }
    } catch (error) { listTarget.innerHTML = `<div class="alert alert-danger m-3">${error.message}</div>`; }
}

export async function showTxnDetails(id) {
    const detailModalEl = document.getElementById('txnDetailModal');
    const detailModal = new bootstrap.Modal(detailModalEl);
    
    document.getElementById('txn_detail_title').textContent = `票据 #${id}`;
    document.getElementById('txn_detail_body').innerHTML = '<div class="text-center p-4"><div class="spinner-border"></div></div>';
    detailModal.show();

    try {
        const response = await fetch(`api/pos_transaction_handler.php?action=get_details&id=${id}`);
        const result = await response.json();
        if (result.status === 'success') {
            const d = result.data;
            let itemsHtml = '';
            d.items.forEach(item => {
                let customs = {};
                try { customs = JSON.parse(item.customizations) || {}; } catch(e) {}
                const customText = `I:${customs.ice || 'N/A'} | S:${customs.sugar || 'N/A'}`;
                itemsHtml += `<tr><td>${d.item_name} <small class="text-muted">(${item.variant_name})</small><br><small class="text-muted">${customText}</small></td><td>${item.quantity}</td><td>${fmtEUR(item.unit_price)}</td><td>${fmtEUR(item.unit_price * item.quantity)}</td></tr>`;
            });

            const statusBadge = `<span class="badge text-bg-${d.status === 'CANCELLED' ? 'danger':'success'}">${t(d.status.toLowerCase())}</span>`;

            const html = `<p><strong>票号:</strong> ${d.series}-${d.number}</p><p><strong>时间:</strong> ${new Date(d.issued_at).toLocaleString('zh-CN', { hour12: false })}</p><p><strong>收银员:</strong> ${d.cashier_name || 'N/A'}</p><p><strong>状态:</strong> ${statusBadge}</p><hr><h5>商品列表</h5><table class="table table-sm"><thead><tr><th>商品</th><th>数量</th><th>单价</th><th>总价</th></tr></thead><tbody>${itemsHtml}</tbody></table><hr><div class="text-end"><div><small>税前:</small> ${fmtEUR(d.taxable_base)}</div><div><small>税额:</small> ${fmtEUR(d.vat_amount)}</div><div class="fs-5 fw-bold">总计: ${fmtEUR(d.final_total)}</div></div>`;
            
            document.getElementById('txn_detail_title').textContent = `${d.series}-${d.number}`;
            document.getElementById('txn_detail_body').innerHTML = html;

        } else { throw new Error(result.message); }
    } catch (error) { 
        document.getElementById('txn_detail_body').innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}