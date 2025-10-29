// store_html/html/pos/assets/js/modules/eod.js
// 版本：EOD-Overlay-Confirm v2（最小改动 / 不改DB / 不改接口）
// 改动要点：safeT 防止显示 raw key；确认页标题处加入红色警示；金额解析更强韧。

import { t, fmtEUR, toast } from '../utils.js';

let pendingEodPayload = null; // 覆盖式确认使用

/* ========== 文案安全取值（t(key) == key 视为缺失） ========== */
function safeT(key, fallback){
  try{
    const v = t(key);
    if(!v || v === key) return fallback;
    return v;
  }catch(_){
    return fallback;
  }
}

/* ========== 基础工具 ========== */
function getEl(id){ return document.getElementById(id); }
function getOrCreateModal(id, opts={backdrop:'static',keyboard:false}){
  const el = getEl(id);
  if(!el){ console.error('[EOD] missing #' + id); return null; }
  return bootstrap.Modal.getOrCreateInstance(el, opts);
}
function getOrCreateOffcanvas(id){
  const el = getEl(id);
  return el ? bootstrap.Offcanvas.getOrCreateInstance(el) : null;
}

/* ========== 若无节点则注入骨架 ========== */
function ensureModalsExist(){
  if(!getEl('eodSummaryModal')){
    document.body.insertAdjacentHTML('beforeend', `
<div class="modal fade" id="eodSummaryModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content modal-sheet">
      <div class="modal-header">
        <h5 class="modal-title">${safeT('eod_summary','今日日结报告')}</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="${safeT('close','关闭')}"></button>
      </div>
      <div class="modal-body" id="eod_summary_body"></div>
      <div class="modal-footer" id="eod_summary_footer"></div>
    </div>
  </div>
</div>`);
  }
}

/* ========== 可见性兜底（上一版已验证稳定） ========== */
function detachToBody(id){
  const el = getEl(id);
  if(el && el.parentElement !== document.body){
    document.body.appendChild(el);
  }
}
function forceVisibleModal(id){
  const modal = getEl(id); if(!modal) return;
  modal.classList.remove('fade');
  Object.assign(modal.style, {
    position:'fixed', inset:'0', display:'block', visibility:'visible', opacity:'1', zIndex:'1060'
  });
  const dlg = modal.querySelector('.modal-dialog');
  const content = modal.querySelector('.modal-content');
  if(dlg){
    Object.assign(dlg.style, {
      position:'fixed', left:'50%', top:'50%', transform:'translate(-50%, -50%)',
      width:'min(900px, calc(100vw - 32px))', maxWidth:'min(900px, calc(100vw - 32px))',
      maxHeight:'min(90vh, 720px)', display:'block', opacity:'1', zIndex:'1062'
    });
    dlg.classList.add('modal-dialog-centered');
  }
  if(content){
    Object.assign(content.style, {
      display:'block', width:'100%', maxHeight:'inherit', overflow:'auto', visibility:'visible', opacity:'1', zIndex:'1063'
    });
  }
  document.querySelectorAll('.offcanvas-backdrop').forEach(n=>n.remove());
  document.body.classList.remove('offcanvas-backdrop');
}

/* ========== 欧式小数解析（更强韧） ========== */
function parseEuroNumber(input){
  if(input == null) return NaN;
  let s = String(input).trim();

  // 1) 去货币符号与空白
  s = s.replace(/[€\s]/g, '');

  // 2) 保留数字与分隔符（.,，，-），其余全部丢弃（字母/中文/符号）
  s = s.replace(/[^0-9.,-]/g, '');

  // 3) 如果存在逗号且没有点，按“逗号为小数点”处理；如果两者都有，按“最后出现者为小数点”
  if(s.includes(',') && !s.includes('.')){
    // 纯逗号小数：1.234,56 或 1234,56
    s = s.replace(/\./g, '');   // 清掉可能的千分位点
    s = s.replace(',', '.');    // 逗号变小数点
  }else if(s.includes(',') && s.includes('.')){
    // 两者都在：保留最后一个作为小数点，其余全部视为千分位去掉
    const lastComma = s.lastIndexOf(',');
    const lastDot   = s.lastIndexOf('.');
    const decIsComma = lastComma > lastDot;
    if(decIsComma){
      s = s.replace(/\./g, '');   // 点仅作千分位
      const i = s.lastIndexOf(',');
      s = s.slice(0,i).replace(/,/g,'') + '.' + s.slice(i+1);
    }else{
      s = s.replace(/,/g,''); // 逗号仅作千分位
    }
  }else{
    // 只有点：去掉千分位逗号（如果有）
    s = s.replace(/,/g,'');
  }

  // 4) 处理多个负号；保留前导负号
  s = s.replace(/(?!^)-/g, '');

  const n = Number(s);
  return isFinite(n) ? n : NaN;
}

/* ========== 字段兼容（预览） ========== */
function normalizePreviewData(raw={}){
  const d = {...raw};
  const txn  = d.transactions_count ?? d.transaction_count ?? 0;
  const gross= d.system_gross_sales ?? d.gross_sales ?? 0;
  const disc = d.system_discounts ?? d.total_discounts ?? 0;
  const net  = d.system_net_sales ?? d.net_sales ?? (gross-disc);
  const tax  = d.system_tax ?? d.total_tax ?? 0;
  const pb = d.payments || d.payment_breakdown || {};
  const pick = (k)=> pb[k] ?? pb[k?.toLowerCase?.()] ?? pb[k?.toUpperCase?.()] ?? 0;
  return {
    report_date: d.report_date || '-',
    is_submitted: !!d.is_submitted,
    transactions_count: txn,
    system_gross_sales: gross,
    system_discounts: disc,
    system_net_sales: net,
    system_tax: tax,
    payments: {
      cash: pick('cash') ?? pick('Cash') ?? 0,
      card: pick('card') ?? pick('Card') ?? 0,
      platform: pick('platform') ?? pick('Platform') ?? 0,
    }
  };
}

/* ========== 覆盖式确认页（全屏） ========== */
function ensureConfirmOverlayStyle(){
  if(document.getElementById('eodConfirmStyle')) return;
  const css = `
#eodConfirmScreen{
  position:fixed; inset:0; z-index:2000; background:rgba(0,0,0,.6); backdrop-filter:blur(2px);
  display:flex; align-items:center; justify-content:center; padding:16px;
}
.eod-confirm-card{
  width:min(720px, 96vw); max-height:90vh; overflow:auto;
  background:#111827; color:#f9fafb; border-radius:18px; box-shadow:0 10px 30px rgba(0,0,0,.4);
}
.eod-confirm-header{
  display:flex; align-items:center; justify-content:space-between; gap:12px;
  padding:14px 18px; border-bottom:1px solid rgba(255,255,255,.08); background:#1f2937;
}
.eod-confirm-title{ font-size:18px; font-weight:700; display:flex; gap:8px; align-items:center; }
.eod-confirm-title .badge{
  background:#ef4444; color:#fff; font-size:12px; border-radius:8px; padding:2px 8px;
}
.eod-confirm-headnote{ color:#fca5a5; font-weight:700; font-size:12px; white-space:nowrap; }
.eod-confirm-body{ padding:16px 18px; }
.eod-grid{ display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-top:8px; }
.eod-stat{ background:#0b1220; border:1px solid rgba(255,255,255,.06); border-radius:12px; padding:10px 12px; }
.eod-stat .label{ font-size:12px; color:#9ca3af; }
.eod-stat .value{ font-size:20px; font-weight:700; margin-top:4px; }
.eod-actions{ display:flex; gap:12px; justify-content:flex-end; padding:14px 18px; border-top:1px solid rgba(255,255,255,.08); }
.eod-btn{ padding:10px 16px; border-radius:10px; font-weight:700; }
.eod-btn-cancel{ background:transparent; color:#e5e7eb; border:1px solid rgba(255,255,255,.2); }
.eod-btn-danger{ background:#ef4444; color:#fff; border:none; }
@media (max-width: 520px){
  .eod-grid{ grid-template-columns:1fr; }
  .eod-confirm-headnote{ display:none; }
}
  `.trim();
  const style = document.createElement('style');
  style.id = 'eodConfirmStyle';
  style.textContent = css;
  document.head.appendChild(style);
}
function openConfirmScreen(previewData){
  ensureConfirmOverlayStyle();
  const exist = document.getElementById('eodConfirmScreen');
  if(exist) exist.remove();

  const headNote = safeT('eod_confirm_headnote', '提交后无法再结报');

  const cash = fmtEUR(previewData?.payments?.cash ?? 0);
  const card = fmtEUR(previewData?.payments?.card ?? 0);
  const plat = fmtEUR(previewData?.payments?.platform ?? 0);
  const counted = fmtEUR(pendingEodPayload?.counted_cash ?? 0);
  const notes = pendingEodPayload?.notes || '';
  const repDate = previewData?.report_date || '-';

  const confirmText = safeT(
    'eod_confirm_text',
    '提交后，今日日结数据将被存档且无法修改。请确认所有款项已清点完毕。'
  );

  const html = `
<div id="eodConfirmScreen" role="dialog" aria-modal="true">
  <div class="eod-confirm-card">
    <div class="eod-confirm-header">
      <div class="eod-confirm-title">
        <span class="badge">FINAL</span>
        <span>${safeT('eod_confirm_submit','确认提交')}</span>
        <span style="opacity:.6; font-weight:500; font-size:12px;">（归属日期：${repDate}）</span>
      </div>
      <div class="eod-confirm-headnote">${headNote}</div>
      <button id="eodCancelConfirm" class="eod-btn eod-btn-cancel" aria-label="${safeT('cancel','取消')}"> ${safeT('cancel','取消')} </button>
    </div>
    <div class="eod-confirm-body">
      <div style="margin-bottom:8px; color:#d1d5db;">${confirmText}</div>
      <div class="eod-grid">
        <div class="eod-stat"><div class="label">${safeT('eod_cash','现金收款')}</div><div class="value">${cash}</div></div>
        <div class="eod-stat"><div class="label">${safeT('eod_card','刷卡收款')}</div><div class="value">${card}</div></div>
        <div class="eod-stat"><div class="label">${safeT('eod_platform','平台收款')}</div><div class="value">${plat}</div></div>
        <div class="eod-stat"><div class="label">${safeT('eod_counted_cash','清点现金金额')}</div><div class="value">${counted}</div></div>
      </div>
      ${notes ? `<div style="margin-top:10px; font-size:12px; color:#9ca3af;">notes: ${notes.replace(/[<>]/g,'')}</div>` : ''}
    </div>
    <div class="eod-actions">
      <button id="eodDoSubmit" class="eod-btn eod-btn-danger">${safeT('eod_confirm_submit','确认提交日结')}</button>
    </div>
  </div>
</div>`;
  document.body.insertAdjacentHTML('beforeend', html);
  document.getElementById('eodConfirmScreen').focus();
}

/* ========== 打开日结预览 ========== */
export async function openEodModal(){
  ensureModalsExist();

  const ops = getOrCreateOffcanvas('opsOffcanvas');
  if(ops){
    const el = getEl('opsOffcanvas');
    if(el && el.classList.contains('show')){
      await new Promise((resolve)=>{
        const onHidden=()=>{ el.removeEventListener('hidden.bs.offcanvas', onHidden); resolve(); };
        el.addEventListener('hidden.bs.offcanvas', onHidden, {once:true});
        ops.hide();
      });
      document.querySelectorAll('.offcanvas-backdrop').forEach(n=>n.remove());
      document.body.classList.remove('offcanvas-backdrop');
    }
  }

  detachToBody('eodSummaryModal');
  const summary = getOrCreateModal('eodSummaryModal');
  if(!summary) return;

  $('#eod_summary_body').html('<div class="text-center p-4"><div class="spinner-border"></div></div>');
  $('#eod_summary_footer').empty();

  summary.show();
  forceVisibleModal('eodSummaryModal');

  try{
    const resp = await fetch('api/eod_summary_handler.php?action=get_preview', { cache:'no-store' });
    const result = await resp.json();
    if(result.status !== 'success') throw new Error(result.message || 'Load failed');

    const data = normalizePreviewData(result.data);

    // --- 核心修复：根据 is_submitted 标志渲染不同视图 ---
    if (data.is_submitted) {
        const report = result.data.existing_report;
        const discrepancy = parseFloat(report.cash_discrepancy);
        const discrepancyClass = discrepancy === 0 ? 'text-success' : 'text-danger';
        const discrepancyText = discrepancy > 0 ? `+${fmtEUR(discrepancy)}` : fmtEUR(discrepancy);

        const body = `
            <div class="alert alert-info" role="alert">
              <h4 class="alert-heading">${safeT('eod_submitted_already', '今日已日结')}</h4>
              <p>${safeT('eod_submitted_desc', '今日报告已存档，以下为存档数据。')}</p>
            </div>
            <div class="row g-3">
                <div class="col-6 col-md-3"><div class="stat"><div class="label">${safeT('eod_date','报告日期')}</div><div class="value">${report.report_date}</div></div></div>
                <div class="col-6 col-md-3"><div class="stat"><div class="label">${safeT('eod_txn_count','交易笔数')}</div><div class="value">${report.transactions_count}</div></div></div>
                <div class="col-6 col-md-3"><div class="stat"><div class="label">${safeT('eod_net_sales','净销售额')}</div><div class="value">${fmtEUR(report.system_net_sales)}</div></div></div>
                <div class="col-6 col-md-3"><div class="stat"><div class="label">${safeT('eod_cash_discrepancy','现金差异')}</div><div class="value ${discrepancyClass}">${discrepancyText}</div></div></div>
            </div>
            <hr>
            <h6 class="fw-bold mb-2">${safeT('eod_payments','收款方式汇总')}</h6>
            <div class="row g-2">
                <div class="col-4"><div class="card card-sheet p-2"><div class="small text-muted">${safeT('eod_cash','现金收款')}</div><div class="fs-5">${fmtEUR(report.system_cash)}</div></div></div>
                <div class="col-4"><div class="card card-sheet p-2"><div class="small text-muted">${safeT('eod_card','刷卡收款')}</div><div class="fs-5">${fmtEUR(report.system_card)}</div></div></div>
                <div class="col-4"><div class="card card-sheet p-2"><div class="small text-muted">${safeT('eod_platform','平台收款')}</div><div class="fs-5">${fmtEUR(report.system_platform)}</div></div></div>
            </div>
             ${report.notes ? `<hr><p class="text-muted mb-0"><strong>备注:</strong> ${report.notes.replace(/</g, "&lt;")}</p>` : ''}
        `;
        $('#eod_summary_body').html(body);
        $('#eod_summary_footer').html(`<button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">${safeT('close','关闭')}</button>`);

    } else {
        const body = `
          <div class="row g-3">
            <div class="col-6 col-md-3"><div class="stat"><div class="label">${safeT('eod_date','报告日期')}</div><div class="value">${data.report_date}</div></div></div>
            <div class="col-6 col-md-3"><div class="stat"><div class="label">${safeT('eod_txn_count','交易笔数')}</div><div class="value">${data.transactions_count}</div></div></div>
            <div class="col-6 col-md-3"><div class="stat"><div class="label">${safeT('eod_gross_sales','总销售额')}</div><div class="value">${fmtEUR(data.system_gross_sales)}</div></div></div>
            <div class="col-6 col-md-3"><div class="stat"><div class="label">${safeT('eod_discounts','折扣总额')}</div><div class="value">${fmtEUR(data.system_discounts)}</div></div></div>
            <div class="col-6 col-md-3"><div class="stat"><div class="label">${safeT('eod_net_sales','净销售额')}</div><div class="value">${fmtEUR(data.system_net_sales)}</div></div></div>
            <div class="col-6 col-md-3"><div class="stat"><div class="label">${safeT('eod_tax','税额')}</div><div class="value">${fmtEUR(data.system_tax)}</div></div></div>

            <div class="col-12"><hr></div>

            <div class="col-12">
              <h6 class="fw-bold mb-2">${safeT('eod_payments','收款方式汇总')}</h6>
              <div class="row g-2">
                <div class="col-4"><div class="card card-sheet p-2"><div class="small text-muted">${safeT('eod_cash','现金收款')}</div><div class="fs-5">${fmtEUR(data.payments.cash)}</div></div></div>
                <div class="col-4"><div class="card card-sheet p-2"><div class="small text-muted">${safeT('eod_card','刷卡收款')}</div><div class="fs-5">${fmtEUR(data.payments.card)}</div></div></div>
                <div class="col-4"><div class="card card-sheet p-2"><div class="small text-muted">${safeT('eod_platform','平台收款')}</div><div class="fs-5">${fmtEUR(data.payments.platform)}</div></div></div>
              </div>
            </div>

            <div class="col-12"><hr></div>
            <div class="col-12 col-md-6">
              <label class="form-label">${safeT('eod_counted_cash','清点现金金额')}</label>
              <input type="text" inputmode="decimal" class="form-control" id="eod_counted_cash" placeholder="0,00 / 0.00">
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label">${safeT('eod_notes', '备注')}</label>
              <textarea class="form-control" id="eod_notes" rows="2" placeholder="${safeT('eod_notes', '备注')}"></textarea>
            </div>
          </div>
        `;
        $('#eod_summary_body').html(body);
        $('#eod_summary_footer').html(`<button type="button" class="btn btn-dark w-100" id="btn_submit_eod_start">${safeT('eod_submit','确认并提交日结')}</button>`);
    }

    // 给覆盖页展示使用
    $('#eodSummaryModal').data('previewData', data);

    forceVisibleModal('eodSummaryModal');

  }catch(err){
    console.error('[EOD] Preview error:', err);
    $('#eod_summary_body').html(`<div class="alert alert-danger">${err.message||'加载失败'}</div>`);
    $('#eod_summary_footer').html(`<button type="button" class="btn btn-secondary w-100" data-bs-dismiss="modal">${safeT('close','关闭')}</button>`);
    forceVisibleModal('eodSummaryModal');
  }
}

/* ========== 打开覆盖式确认页（全屏） ========== */
export function openEodConfirmationModal(){
  const raw = $('#eod_counted_cash').val();
  const countedNum = parseEuroNumber(raw);
  if(!isFinite(countedNum)){
    toast(`${safeT('eod_counted_cash','清点现金金额')} ${safeT('cannot_be_empty','不能为空')}`);
    return;
  }
  pendingEodPayload = { counted_cash: countedNum, notes: $('#eod_notes').val() ?? '' };

  const previewData = $('#eodSummaryModal').data('previewData') || null;
  openConfirmScreen(previewData);
}

/* ========== 最终提交 ========== */
export async function submitEodReportFinal(){
  const payload = pendingEodPayload || $('#eodConfirmModal').data('payload');
  if(!payload){ toast('发生未知错误，请重试'); return; }
  payload.action = 'submit_report';

  const btn = document.getElementById('eodDoSubmit');
  if(btn){
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
  }

  try{
    const resp = await fetch('api/eod_summary_handler.php', {
      method:'POST',
      headers:{ 'Content-Type':'application/json' },
      body: JSON.stringify(payload)
    });
    const result = await resp.json();
    if(!resp.ok || result.status!=='success') throw new Error(result.message||'提交失败');

    toast(safeT('eod_success_submit','提交成功'));

    const scr = document.getElementById('eodConfirmScreen');
    if(scr) scr.remove();
    const s = getOrCreateModal('eodSummaryModal');
    if(s) s.hide();
  }catch(err){
    console.error('[EOD] Submit error:', err);
    toast('提交失败: ' + (err.message||'网络错误'));
  }finally{
    if(btn){
      btn.disabled = false;
      btn.textContent = safeT('eod_confirm_submit','确认提交日结');
    }
  }
}

/* ========== 事件委托兜底（按钮一定触发） ========== */
$(document)
  .off('click.eod', '#btn_submit_eod_start')
  .on('click.eod',  '#btn_submit_eod_start', openEodConfirmationModal);

$(document)
  .off('click.eod', '#eodDoSubmit')
  .on('click.eod',  '#eodDoSubmit', submitEodReportFinal);

$(document)
  .off('click.eod', '#eodCancelConfirm')
  .on('click.eod',  '#eodCancelConfirm', () => {
    const scr = document.getElementById('eodConfirmScreen');
    if(scr) scr.remove();
  });

document.addEventListener('keydown', (e)=>{
  if(e.key === 'Escape'){
    const scr = document.getElementById('eodConfirmScreen');
    if(scr) scr.remove();
  }
});