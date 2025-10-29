import { t, fmtEUR, toast } from '../utils.js';

function setupDatePickers() {
    const startDateInput = document.getElementById('txn_start_date');
    const endDateInput = document.getElementById('txn_end_date');
    const today = new Date().toISOString().split('T')[0];

    // 规则2：截止日期和起始日期都不能选择未来
    startDateInput.max = today;
    endDateInput.max = today;

    function updateDateLimits() {
        const startDate = new Date(startDateInput.value);
        const endDate = new Date(endDateInput.value);

        // --- 动态更新截止日期的限制 ---
        // 规则3：截止日期不可早于起始日期
        endDateInput.min = startDateInput.value;
        // 规则1：查询范围不能超过一个月
        const maxEndDate = new Date(startDate);
        maxEndDate.setMonth(maxEndDate.getMonth() + 1);
        // 同时要确保最大可选日期不能超过今天
        const finalMaxEndDate = new Date(Math.min(maxEndDate, new Date(today)));
        endDateInput.max = finalMaxEndDate.toISOString().split('T')[0];


        // --- 动态更新起始日期的限制 ---
         // 规则3：起始日期不能晚于截止日期 (反向)
        startDateInput.max = endDateInput.value;
        // 规则1：查询范围不能超过一个月 (反向)
        const minStartDate = new Date(endDate);
        minStartDate.setMonth(minStartDate.getMonth() - 1);
        startDateInput.min = minStartDate.toISOString().split('T')[0];
    }

    startDateInput.addEventListener('change', updateDateLimits);
    endDateInput.addEventListener('change', updateDateLimits);
    
    // 初始化限制
    updateDateLimits();
}

function validateDateRange() {
    const startDateInput = document.getElementById('txn_start_date');
    const endDateInput = document.getElementById('txn_end_date');
    const startDate = startDateInput.value;
    const endDate = endDateInput.value;
    
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const selectedStartDate = new Date(startDate);
    const selectedEndDate = new Date(endDate);

    // 校验3: 截止日期不能早于起始日期
    if (selectedEndDate < selectedStartDate) {
        toast(t('validation_end_date_before_start'));
        return false;
    }

    // 校验2: 截止日期不可是未来日期
    if (selectedEndDate > today) {
        toast(t('validation_end_date_in_future'));
        return false;
    }
    
    // 校验1: 只能查询最近一个月
    const oneMonthAgo = new Date(today);
    oneMonthAgo.setMonth(oneMonthAgo.getMonth() - 1);

    if (selectedStartDate < oneMonthAgo) {
        toast(t('validation_date_range_too_large'));
        return false;
    }
    
    return true; // 所有校验通过
}


export async function openTxnQueryPanel() {
    const opsOffcanvas = bootstrap.Offcanvas.getInstance('#opsOffcanvas');
    if (opsOffcanvas) opsOffcanvas.hide();
    
    const container = document.getElementById('txn_list_container');
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
        
        // --- 核心修复：查询按钮现在先校验，再刷新 ---
        document.getElementById('btn_filter_txn').addEventListener('click', () => {
            if (validateDateRange()) {
                refreshTxnList();
            }
        });
        
        document.getElementById('txn_start_date').addEventListener('click', function() { this.showPicker(); });
        document.getElementById('txn_end_date').addEventListener('click', function() { this.showPicker(); });
        
        setupDatePickers();
    }
    
    const txnQueryOffcanvas = new bootstrap.Offcanvas('#txnQueryOffcanvas');
    txnQueryOffcanvas.show();
    // 首次打开时，不校验，直接加载默认日期（今天）的数据
    await refreshTxnList();
}

async function refreshTxnList() {
    const listTarget = document.getElementById('txn_list_target');
    const startDate = document.getElementById('txn_start_date').value;
    const endDate = document.getElementById('txn_end_date').value;

    listTarget.innerHTML = '<div class="text-center p-4"><div class="spinner-border spinner-border-sm"></div></div>';
    
    let apiUrl = 'api/pos_transaction_handler.php?action=list';
    if (startDate && endDate) {
        apiUrl += `&start_date=${startDate}&end_date=${endDate}`;
    }

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
                const time = new Date(txn.issued_at).toLocaleString();
                const statusClass = txn.status === 'CANCELLED' ? 'text-danger' : '';
                html += `<a href="#" class="list-group-item list-group-item-action txn-item" data-id="${txn.id}"><div class="d-flex w-100 justify-content-between"><h6 class="mb-1">${txn.series}-${txn.number}</h6><strong class="${statusClass}">${fmtEUR(txn.final_total)}</strong></div><small>${time}</small></a>`;
            });
            html += '</div>';
            listTarget.innerHTML = html;
        } else { throw new Error(result.message); }
    } catch (error) { listTarget.innerHTML = `<div class="alert alert-danger m-3">${error.message}</div>`; }
}

export async function showTxnDetails(id) {
    const detailModal = new bootstrap.Modal('#txnDetailModal');
    $('#txn_detail_title').text(`票据 #${id}`);
    $('#txn_detail_body').html('<div class="text-center p-4"><div class="spinner-border"></div></div>');
    detailModal.show();
    try {
        const response = await fetch(`api/pos_transaction_handler.php?action=get_details&id=${id}`);
        const result = await response.json();
        if (result.status === 'success') {
            const d = result.data;
            let itemsHtml = '';
            d.items.forEach(item => {
                const customs = JSON.parse(item.customizations);
                const customText = `I:${customs.ice || 'N/A'} | S:${customs.sugar || 'N/A'}`;
                itemsHtml += `<tr><td>${item.item_name} <small class="text-muted">(${item.variant_name})</small><br><small class="text-muted">${customText}</small></td><td>${item.quantity}</td><td>${fmtEUR(item.unit_price)}</td><td>${fmtEUR(item.unit_price * item.quantity)}</td></tr>`;
            });
            const html = `<p><strong>票号:</strong> ${d.series}-${d.number}</p><p><strong>时间:</strong> ${new Date(d.issued_at).toLocaleString()}</p><p><strong>收银员:</strong> ${d.cashier_name || 'N/A'}</p><p><strong>状态:</strong> <span class="badge text-bg-${d.status === 'CANCELLED' ? 'danger':'success'}">${t(d.status.toLowerCase())}</span></p><hr><h5>商品列表</h5><table class="table table-sm"><thead><tr><th>商品</th><th>数量</th><th>单价</th><th>总价</th></tr></thead><tbody>${itemsHtml}</tbody></table><hr><div class="text-end"><div><small>税前:</small> ${fmtEUR(d.taxable_base)}</div><div><small>税额:</small> ${fmtEUR(d.vat_amount)}</div><div class="fs-5 fw-bold">总计: ${fmtEUR(d.final_total)}</div></div>`;
            $('#txn_detail_title').text(`${d.series}-${d.number}`);
            $('#txn_detail_body').html(html);
        } else { throw new Error(result.message); }
    } catch (error) { $('#txn_detail_body').html(`<div class="alert alert-danger">${error.message}</div>`); }
}