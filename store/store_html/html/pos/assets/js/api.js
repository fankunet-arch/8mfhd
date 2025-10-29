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
        // --- 核心修复：存储加载到的兑换规则 ---
        STATE.redemptionRules = result.data.redemption_rules || [];
        // ----------------------------------
        if (!STATE.active_category_key && STATE.categories.length > 0) {
            STATE.active_category_key = STATE.categories[0].key;
        }
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
        // --- 核心修复：传递 coupon_code 和 redemption_rule_id ---
        coupon_code: STATE.activeCouponCode, // 优惠券码
        redemption_rule_id: STATE.activeRedemptionRuleId, // 积分兑换规则ID
        // ------------------------------------------------
        member_id: STATE.activeMember ? STATE.activeMember.id : null,
        payment: paymentPayload,
        // Include redeemed points in the final order submission
        points_redeemed: STATE.calculatedCart.points_redemption ? STATE.calculatedCart.points_redemption.points_redeemed : 0,
        points_discount: STATE.calculatedCart.points_redemption ? STATE.calculatedCart.points_redemption.discount_amount : 0
    };
    return await apiCall('api/submit_order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
    });
}
