// /pos/assets/js/payment_confirm.js
// v1.2 — 统一收款确认弹窗 + 金额读取更鲁棒（显式选择器优先，读不到再智能抓取）

(function () {
  // ========== 小工具 ==========
  function eur(x) {
    return new Intl.NumberFormat('zh-CN', { style: 'currency', currency: 'EUR' })
      .format(Number(x || 0));
  }
  function toNum(v) {
    if (typeof v === 'number') return v;
    if (!v) return 0;
    let s = String(v).trim();
    // 只保留数字 . , - € 等可视字符
    s = s.replace(/[^\d.,-]/g, '');
    // 兼容 1.234,56 / 4,50
    if (s.includes(',') && !s.includes('.')) {
      s = s.replace(/\./g, '');
      s = s.replace(',', '.');
    } else {
      s = s.replace(/,/g, '');
    }
    const n = parseFloat(s);
    return isNaN(n) ? 0 : n;
  }
  function first(elList) {
    for (const el of elList) if (el) return el;
    return null;
  }
  function q(sel, root)  { return (root || document).querySelector(sel); }
  function qa(sel, root) { return Array.from((root || document).querySelectorAll(sel)); }

  // 在某个容器里，按“中文/英文标签文本”定位金额：从同级/父级内找样式较粗的数字或带 € 的文本
  function findAmountByLabel(container, labels) {
    if (!container) return 0;
    const all = qa('*', container);
    for (const node of all) {
      const txt = (node.textContent || '').trim();
      if (!txt) continue;
      // 命中标签
      if (labels.some(lb => txt.includes(lb))) {
        // 在同级/父级区域内，搜寻金额样式节点
        const scope = node.parentElement || node;
        // 1) 常见粗体/大号数字
        const strongs = qa('.fw-bold,.fs-5,strong,b,.amount,.price', scope);
        for (const s of strongs) {
          const v = toNum(s.textContent);
          if (v) return v;
        }
        // 2) 任意带货币样式的文本
        const leafs = qa('*', scope).filter(n => !n.children?.length);
        for (const lf of leafs) {
          const v = toNum(lf.textContent);
          if (v) return v;
        }
      }
    }
    return 0;
  }

  // 在某个容器里，按“中文/英文标签文本”定位输入框并取值
  function findInputByLabel(container, labels) {
    if (!container) return 0;
    const inputs = qa('input[type="number"],input[type="text"],input', container);
    for (const inp of inputs) {
      // 往上找 3 层，看看是否包含这些标签文字
      let p = inp;
      let hit = false;
      for (let i = 0; i < 3 && p; i++) {
        const txt = (p.textContent || '').trim();
        if (labels.some(lb => txt.includes(lb))) { hit = true; break; }
        p = p.parentElement;
      }
      if (hit) {
        const v = toNum(inp.value);
        if (!isNaN(v) && v >= 0) return v;
      }
    }
    return 0;
  }

  // 获取“当前可见的结账弹窗”
  function getActiveCheckoutModal() {
    // 1) 已知 id
    let modal = q('#checkoutModal');
    // 2) 当前显示的任何 modal
    if (!modal) modal = q('.modal.show');
    return modal || document;
  }

  // ========== 从页面抓取金额（显式选择器优先，失败则智能抓取） ==========
  function getCtxFromUI() {
    const modal = getActiveCheckoutModal();

    // —— 应收（优先显式节点/数据 → 否则智能抓）
    let due =
      toNum(q('#checkout_due', modal)?.dataset?.value) ||
      toNum(q('#checkout_due', modal)?.textContent)     ||
      toNum(q('[data-amount="due"]', modal)?.textContent) ||
      toNum(q('.amount-due', modal)?.textContent)       ||
      toNum(q('#amount_due', modal)?.textContent)       ||
      0;

    if (!due) {
      due = findAmountByLabel(modal, ['应收', '应付', 'Due', 'Total', 'To Pay']);
    }

    // —— 现金 / 刷卡 / 平台码（优先显式输入 → 否则智能抓）
    let cash =
      toNum(q('.js-paid-cash', modal)?.value) ||
      toNum(q('#pay_cash', modal)?.value)     ||
      toNum(q('[data-input="cash"]', modal)?.value) ||
      0;

    if (!cash) cash = findInputByLabel(modal, ['现金', 'Cash', 'Efectivo']);

    let card =
      toNum(q('.js-paid-card', modal)?.value) ||
      toNum(q('#pay_card', modal)?.value)     ||
      toNum(q('[data-input="card"]', modal)?.value) ||
      0;

    if (!card) card = findInputByLabel(modal, ['刷卡', 'Card', 'Tarjeta', 'POS']);

    let platform =
      toNum(q('.js-paid-platform', modal)?.value) ||
      toNum(q('#pay_platform', modal)?.value)     ||
      toNum(q('[data-input="platform"]', modal)?.value) ||
      0;

    if (!platform) {
      // 平台码：Bizum/二维码/第三方等都归到 platform
      platform = findInputByLabel(modal, ['平台码', 'Bizum', '二维码', 'QR', 'WeChat', 'Alipay', 'Pay', 'Pago']);
    }

    // —— 兜底：有时候只录了一个输入，且只有一个 input 显示
    if (!cash && !card && !platform) {
      const visInputs = qa('input[type="number"],input[type="text"]', modal).filter(inp => inp.offsetParent !== null);
      if (visInputs.length === 1) cash = toNum(visInputs[0].value) || 0;
    }

    // 组装
    return {
      due: Number((due || 0).toFixed(2)),
      cash: Number((cash || 0).toFixed(2)),
      card: Number((card || 0).toFixed(2)),
      platform: Number((platform || 0).toFixed(2)),
    };
  }

  // ========== 渲染“收款方式”行 ==========
  function line(label, amt) {
    return `<div class="d-flex justify-content-between py-1 border-bottom small">
              <span>${label}</span><span class="fw-semibold">${eur(amt)}</span>
            </div>`;
  }

  // ========== 打开确认窗 ==========
  function openPaymentConfirm(ctx, onConfirm) {
    const modalEl = document.getElementById('paymentConfirmModal');
    const pcm = bootstrap.Modal.getOrCreateInstance(modalEl);

    const due = Number((ctx.due || 0).toFixed(2));
    const cash = Number((ctx.cash || 0).toFixed(2));
    const card = Number((ctx.card || 0).toFixed(2));
    const plat = Number((ctx.platform || 0).toFixed(2));

    const paid = Number((cash + card + plat).toFixed(2));
    const change = Math.max(0, Number((paid - due).toFixed(2)));
    const lack = Math.max(0, Number((due - paid).toFixed(2)));

    // 金额三栏
    const $ = (id) => document.getElementById(id);
    if (!$('pc-due') || !$('pc-paid') || !$('pc-change') || !$('pc-methods')) {
      console.error('[payment_confirm] 必要节点缺失');
      return;
    }
    $('pc-due').textContent = eur(due);
    $('pc-paid').textContent = eur(paid);
    $('pc-change').textContent = eur(change);

    // 方式明细
    const rows = [];
    if (cash > 0) rows.push(line('现金', cash));
    if (card > 0) rows.push(line('刷卡', card));
    if (plat > 0) rows.push(line('平台码', plat));
    $('pc-methods').innerHTML = rows.join('') || '<div class="small text-muted">—</div>';

    // 提示 & 按钮
    const warn = $('pc-warning');
    const lackEl = $('pc-lack');
    const note = $('pc-note');
    const noteC = $('pc-note-change');
    const btn = $('pc-confirm');

    if (lack > 0) {
      warn.classList.remove('d-none');
      lackEl.textContent = eur(lack);
      note.classList.add('d-none');
      btn.disabled = true;
    } else {
      warn.classList.add('d-none');
      if (change > 0) {
        note.classList.remove('d-none');
        noteC.textContent = eur(change);
      } else {
        note.classList.add('d-none');
      }
      btn.disabled = false;
    }

    // 确认 → 组装 payment 并回调
    btn.onclick = function () {
      const payment = {
        total: due,
        paid: paid,
        change: change,
        summary: [
          ...(cash > 0 ? [{ method: 'Cash', amount: cash }] : []),
          ...(card > 0 ? [{ method: 'Card', amount: card }] : []),
          ...(plat > 0 ? [{ method: 'Platform', amount: plat }] : []),
        ],
      };
      pcm.hide();
      onConfirm && onConfirm(payment);
    };

    pcm.show();
  }

  // ========== 统一入口：拦截“确认收款”按钮 ==========
  function bindConfirmButton() {
    const btn = first([
      document.getElementById('btnConfirmPay'),
      document.querySelector('[data-action="confirm-pay"]'),
    ]);
    if (!btn) return;

    btn.addEventListener('click', function (ev) {
      ev.preventDefault();

      // 1) 抓取 UI 金额
      const ctx = getCtxFromUI();

      // 2) 打开确认窗；确认后真正提交
      openPaymentConfirm(ctx, function (payment) {
        try {
          if (typeof window.buildOrderPayload === 'function') {
            const payload = window.buildOrderPayload();
            payload.payment = payment;

            fetch('/pos/api/submit_order.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json' },
              body: JSON.stringify(payload),
            })
              .then(r => r.json())
              .then(resp => {
                if (resp.status === 'success') {
                  // 成功 → 打开原有“下单成功”弹窗
                  const successModalEl = document.getElementById('orderSuccessModal');
                  if (successModalEl) {
                    const sm = bootstrap.Modal.getOrCreateInstance(successModalEl);
                    const inv = resp.data?.invoice_number || '--';
                    const qr  = resp.data?.qr_content || '';
                    const invEl = document.getElementById('success_invoice_number');
                    const qrEl  = document.getElementById('success_qr_content');
                    if (invEl) invEl.textContent = inv;
                    if (qrEl)  qrEl.textContent  = qr;
                    sm.show();
                  }
                  // 可选：自动打印（若存在）
                  if (window.POS?.print?.invoice && resp.data?.invoice_id) {
                    window.POS.print.invoice(resp.data.invoice_id);
                  }
                } else {
                  window.POS?.toast?.error?.(resp.message || '提交失败');
                }
              })
              .catch(err => window.POS?.toast?.error?.('网络错误：' + err.message));
            return;
          }
        } catch (e) {
          console.error(e);
        }

        // 兜底提示：没有你的构造/提交函数时
        alert('未找到提交函数，请把 payment_confirm.js 里的提交处改成你现有的提交流程。');
      });
    });
  }

  // ========== 启动 ==========
  document.addEventListener('DOMContentLoaded', bindConfirmButton);
})();
