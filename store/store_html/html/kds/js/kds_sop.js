/**
 * Toptea Store - KDS
 * SOP (Standard Operating Procedure) Page Logic
 * Engineer: Gemini | Date: 2025-10-31 | Revision: 7.8 (FIX: Replace alert())
 */
$(function() {

    const I18N = {
        'zh-CN': {
            btn_mode_quantity: '用量版',
            btn_mode_sop: 'SOP版',
            info_enter_sku: '请先输入编码',
            btn_action_complete: '制茶完成',
            btn_action_report: '缺料申报',
            nav_prep: '物料制备', // Changed key
            nav_expiry: '效期追踪', // New key
            nav_guide: '制杯指引',
            step_base: '底料',
            step_mixing: '调杯',
            step_topping: '顶料',
            tip_waiting: '请输入饮品编码开始查询',
            cards_waiting: '等待查询...',
            placeholder_sku: '输入饮品编码...',
            btn_logout: '退出',
            // 【新增】
            sop_query_failed: '查询失败'
        },
        'es-ES': {
            btn_mode_quantity: 'Dosis',
            btn_mode_sop: 'SOP',
            info_enter_sku: 'Introduzca el código',
            btn_action_complete: 'Completado',
            btn_action_report: 'Reportar Falta',
            nav_prep: 'Preparación', // Changed key
            nav_expiry: 'Seguimiento', // New key
            nav_guide: 'Guía de Preparación',
            step_base: 'Base',
            step_mixing: 'Mezcla',
            step_topping: 'Topping',
            tip_waiting: 'Introduzca el código del producto para buscar',
            cards_waiting: 'Esperando búsqueda...',
            placeholder_sku: 'Introduzca el código...',
            btn_logout: 'Salir',
            // 【新增】
            sop_query_failed: 'Error de Búsqueda'
        }
    };

    let currentRecipeData = null;
    let currentLang = localStorage.getItem("kds_lang") || "zh-CN";
    let currentStepKey = "base";

    const $productInfoArea = $('#product-info-area');
    const $stepTip = $('#kds-step-tip');
    const $cardsContainer = $('#kds-cards');
    const $skuInput = $('#sku-input');
    const $searchForm = $('#sku-search-form');

    function renderProductInfo() { /* ... unchanged ... */ }
    function renderStepCards() { /* ... unchanged ... */ }
    
    // **【关键修复 v1.6】**
    function fetchSopData(sku) {
        $searchForm.find('button').prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>');
        $.ajax({
            url: 'api/sop_handler.php',
            type: 'GET',
            data: { sku: sku },
            dataType: 'json',
            success: function(response) {
                if (response.status === 'success') {
                    currentRecipeData = response.data;
                    currentStepKey = "base";
                    $('.kds-step-tab').removeClass('active').first().addClass('active');
                    renderAll();
                }
            },
            error: function(jqXHR) {
                currentRecipeData = null;
                renderAll();
                const errorMsg = jqXHR.responseJSON?.message || '查询失败，请检查网络或SKU是否正确。';
                // (使用自定义 Alert)
                const translations = I18N[currentLang] || I18N['zh-CN'];
                showKdsAlert(`${translations.sop_query_failed}: ${errorMsg}`, true);
            },
            complete: function() {
                $searchForm.find('button').prop('disabled', false).html('<i class="bi bi-search"></i>');
                $skuInput.focus().select();
            }
        });
    }
    // **【修复结束】**

    function renderAll() { renderProductInfo(); renderStepCards(); }
    $searchForm.on('submit', function(e) { e.preventDefault(); const sku = $skuInput.val(); if (sku) { fetchSopData(sku); } });
    $('.kds-step-tab').on('click', function() { currentStepKey = $(this).data('step'); $('.kds-step-tab').removeClass('active'); $(this).addClass('active'); renderStepCards(); });
    
    function applyLang(lang) {
      currentLang = lang;
      document.documentElement.setAttribute("lang", lang);
      $(".lang-flag").removeClass("active").filter(`[data-lang="${lang}"]`).addClass("active");
      localStorage.setItem("kds_lang", lang);
      const translations = I18N[currentLang] || I18N['zh-CN'];
      $('[data-i18n-key]').each(function() {
          const key = $(this).data('i18n-key');
          if (translations[key]) {
              if ($(this).is('input[placeholder]')) { $(this).attr('placeholder', translations[key]); } 
              else { $(this).text(translations[key]); }
          }
      });
      renderAll();
    }

    $('.lang-switch').on('click', '.lang-flag', function() { applyLang($(this).data('lang')); });

    function startClock() { function tick() { $('#kds-clock').text(new Date().toLocaleTimeString('zh-CN', { hour12: false })); } tick(); setInterval(tick, 1000); }
    
    startClock();
    applyLang(currentLang);
    const yearElement = document.getElementById('cp-year');
    if(yearElement){ yearElement.textContent = new Date().getFullYear(); }
    $skuInput.focus();

    // --- Unchanged function bodies ---
    function renderProductInfo() { if (!currentRecipeData) { $productInfoArea.html(`<div class="kds-cup-number mb-2 text-muted">---</div><h3 class="fw-bold mb-3 text-muted" data-i18n-key="info_enter_sku">${I18N[currentLang].info_enter_sku}</h3><div class="kds-info-display text-muted">--</div><ul class="kds-order-tags"></ul>`); $('.btn-touch-action').prop('disabled', true); return; } const product = currentRecipeData.product; const langKey = currentLang.substring(0, 2); const productName = product[`name_${langKey}`] || product.name_zh; const statusName = product[`status_name_${langKey}`] || product.status_name_zh; $productInfoArea.html(`<div class="kds-cup-number mb-2">${product.product_sku}</div><h3 class="fw-bold mb-3">${productName}</h3><div class="kds-info-display">${product.cup_name}</div><ul class="kds-order-tags"><li class="kds-tag">${statusName}</li></ul>`); $('.btn-touch-action').prop('disabled', false); }
    function renderStepCards() { if (!currentRecipeData) { $stepTip.text(I18N[currentLang].tip_waiting); $cardsContainer.html(`<div class="col-12 text-center text-muted pt-5"><h4 data-i18n-key="cards_waiting">${I18N[currentLang].cards_waiting}</h4></div>`); return; } const stepData = currentRecipeData.recipe[currentStepKey]; $stepTip.text(stepData.tip[currentLang] || stepData.tip['zh-CN']); $cardsContainer.empty(); if (!stepData.items.length) { $cardsContainer.html('<div class="col-12 text-muted">此步骤无配料</div>'); return; } stepData.items.forEach(item => { const title = item.title[currentLang] || item.title['zh-CN']; const unit = item.unit[currentLang] || item.unit['zh-CN']; $cardsContainer.append(`<div class="col-12 col-md-6 col-xl-4"><div class="kds-ingredient-card"><span class="badge bg-success position-absolute top-0 start-0 m-3">${item.order}</span><div class="bg-secondary rounded mx-auto mb-2" style="width:80px;height:80px; background-image: url(images/placeholder.png); background-size: cover;"></div><h5 class="fw-bold mb-0">${title}</h5><div class="kds-quantity">${item.qty}</div><div class="kds-unit-measure">${unit}</div></div></div>`); }); }
});
