import { STATE } from '../state.js';
import { t, fmtEUR, toast } from '../utils.js';
import { fetchPrintTemplates } from '../api.js';

// Module-level state
let startShiftModal = null;
let endShiftModal = null;
let currentShiftSummary = null;

/**
 * Initializes the shift module, gets modal instances.
 * Event binding is now handled by main.js for robustness.
 */
export function initializeShiftModals() {
    const startModalEl = document.getElementById('startShiftModal');
    const endModalEl = document.getElementById('endShiftModal');

    if (startModalEl) {
        startShiftModal = new bootstrap.Modal(startModalEl, {
            backdrop: 'static', // Prevents closing by clicking outside
            keyboard: false // Prevents closing with Esc key
        });
    }

    if (endModalEl) {
        endShiftModal = new bootstrap.Modal(endModalEl);
    }
    
    // Refresh summary when end shift modal is shown
    if (endModalEl) {
        endModalEl.addEventListener('show.bs.modal', fetchShiftSummary);
    }
}

/**
 * Checks the user's shift status with the backend.
 * This is the entry point for the shift logic.
 */
export async function checkShiftStatus() {
    try {
        const response = await fetch('api/pos_shift_handler.php?action=status');
        const result = await response.json();

        if (result.status === 'success') {
            if (!result.data.has_active_shift) {
                // If no active shift, force the user to start one.
                if (startShiftModal) {
                    startShiftModal.show();
                } else {
                    console.error('Start Shift Modal not found!');
                }
            }
        } else {
            toast(`Error checking shift status: ${result.message}`);
        }
    } catch (error) {
        toast(`Network error checking shift status: ${error.message}`);
    }
}

/**
 * Handles the submission of the "Start Shift" form.
 * Exported to be called from main.js event handler.
 */
export async function handleStartShift(event) {
    event.preventDefault();
    const startingFloatInput = document.getElementById('starting_float');
    const startingFloat = parseFloat(startingFloatInput.value);

    if (isNaN(startingFloat) || startingFloat < 0) {
        toast('请输入有效的初始备用金金额。');
        return;
    }

    const submitBtn = event.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    try {
        const response = await fetch('api/pos_shift_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'start', starting_float: startingFloat })
        });
        const result = await response.json();

        if (result.status === 'success') {
            toast('开班成功！');
            startShiftModal.hide();
            // Reload the page to ensure all states are correct
            window.location.reload();
        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        toast(`开班失败: ${error.message}`);
        submitBtn.disabled = false;
    }
}

/**
 * Fetches the summary for the current active shift to display in the "End Shift" modal.
 */
async function fetchShiftSummary() {
    const summaryBody = document.getElementById('end_shift_summary_body');
    summaryBody.innerHTML = '<div class="text-center"><div class="spinner-border"></div></div>';

    try {
        const response = await fetch('api/pos_shift_handler.php?action=summary');
        const result = await response.json();

        if (result.status === 'success') {
            currentShiftSummary = result.data;
            const startingFloat = parseFloat(document.getElementById('startShiftModal') ? // A bit of a hack to get starting float
                (document.getElementById('starting_float')?.dataset?.initialValue || 0) : 
                (currentShiftSummary.starting_float || 0)
            );

            const expectedCash = startingFloat + (currentShiftSummary.payment_summary.Cash || 0);

            const summaryHtml = `
                <p><strong>总交易笔数:</strong> ${currentShiftSummary.sales_summary.transactions_count}</p>
                <p><strong>净销售额:</strong> ${fmtEUR(currentShiftSummary.sales_summary.net_sales)}</p>
                <hr>
                <h6>收款方式汇总</h6>
                <p>现金收款: ${fmtEUR(currentShiftSummary.payment_summary.Cash)}</p>
                <p>刷卡收款: ${fmtEUR(currentShiftSummary.payment_summary.Card)}</p>
                <p>平台收款: ${fmtEUR(currentShiftSummary.payment_summary.Platform)}</p>
                <hr>
                <h6>现金核对</h6>
                <p>初始备用金: ${fmtEUR(startingFloat)}</p>
                <p class="fw-bold">系统应有现金: ${fmtEUR(expectedCash)}</p>
            `;
            summaryBody.innerHTML = summaryHtml;
            
            const countedCashInput = document.getElementById('counted_cash');
            countedCashInput.addEventListener('input', () => {
                const counted = parseFloat(countedCashInput.value) || 0;
                const variance = counted - expectedCash;
                const varianceEl = document.getElementById('cash_variance_display');
                varianceEl.textContent = fmtEUR(variance);
                varianceEl.className = variance < 0 ? 'text-danger fw-bold' : 'text-success fw-bold';
            });

        } else {
            throw new Error(result.message);
        }
    } catch (error) {
        summaryBody.innerHTML = `<div class="alert alert-danger">${error.message}</div>`;
    }
}

/**
 * Handles the submission of the "End Shift" form.
 */
async function handleEndShift(event) {
    event.preventDefault();
    const countedCashInput = document.getElementById('counted_cash');
    const countedCash = parseFloat(countedCashInput.value);

    if (isNaN(countedCash) || countedCash < 0) {
        toast('请输入有效的清点现金金额。');
        return;
    }

    const submitBtn = event.target.querySelector('button[type="submit"]');
    submitBtn.disabled = true;

    try {
        const response = await fetch('api/pos_shift_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'end', counted_cash: countedCash })
        });
        const result = await response.json();

        if (result.status === 'success') {
            toast('交班成功，系统将自动退出。');
            // Here you would typically trigger the printing of the shift report
            // For now, we just log out.
            setTimeout(() => {
                window.location.href = 'logout.php';
            }, 2000);
        } else {
            throw new Error(result.message);
        }

    } catch (error) {
        toast(`交班失败: ${error.message}`);
        submitBtn.disabled = false;
    }
}