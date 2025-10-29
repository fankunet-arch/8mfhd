import { STATE } from './state.js';
import { toast } from './utils.js';

async function apiCall(url, options = {}) {
    try {
        const response = await fetch(url, options);
        if (!response.ok) {
            let errorData;
            try {
                errorData = await response.json();
            } catch (e) {
                throw new Error(`Server returned error: ${response.status}`);
            }
            throw new Error(errorData.message || 'Unknown server error');
        }
        return response.json();
    } catch (error) {
        console.error(`API call to ${url} failed:`, error);
        toast(`Network Error: ${error.message}`);
        throw error;
    }
}

export async function fetchInitialData() {
    const result = await apiCall('./api/pos_data_loader.php');
    if (result.status === 'success') {
        STATE.products = result.data.products;
        STATE.categories = result.data.categories;
        STATE.addons = result.data.addons;
        STATE.redemptionRules = result.data.redemption_rules || [];
        if (!STATE.active_category_key && STATE.categories.length > 0) {
            STATE.active_category_key = STATE.categories[0].key;
        }
    }
}

/**
 * Version: 2.2.0
 * Fetches all available print templates from the backend.
 */
export async function fetchPrintTemplates() {
    const result = await apiCall('./api/pos_print_handler.php?action=get_templates');
    if (result.status === 'success') {
        STATE.printTemplates = result.data || {};
        console.log('Print templates loaded:', STATE.printTemplates);
    }
}


export async function calculatePromotionsAPI(payload) {
    const result = await apiCall('api/calculate_promotions.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
    return result.data;
}

export async function submitOrderAPI(paymentPayload) {
     const payload = {
        cart: STATE.cart,
        coupon_code: STATE.activeCouponCode,
        redemption_rule_id: STATE.activeRedemptionRuleId,
        member_id: STATE.activeMember ? STATE.activeMember.id : null,
        payment: paymentPayload,
        points_redeemed: STATE.calculatedCart.points_redemption ? STATE.calculatedCart.points_redemption.points_redeemed : 0,
        points_discount: STATE.calculatedCart.points_redemption ? STATE.calculatedCart.points_redemption.discount_amount : 0
    };
    return await apiCall('api/submit_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
}