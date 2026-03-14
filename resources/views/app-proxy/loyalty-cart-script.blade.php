
(function() {
    'use strict';
    
    console.log('⭐ Loyalty Redemption Widget Loaded');
function initLoyaltyCart() {
    console.log('🎯 Initializing Loyalty Cart Widget...');
    // Check if customer is logged in
    // Detect customer ID using multiple methods
// Detect customer ID using multiple methods
function detectCustomerId() {
    // Method 1: window.__st.cid (most reliable)
    if (window.__st && window.__st.cid) {
        console.log('✅ Found customer ID in __st.cid:', window.__st.cid);
        return parseInt(window.__st.cid);
    }
    
    // Method 2: window.Shopify.customer
    if (window.Shopify && window.Shopify.customer && window.Shopify.customer.id) {
        return window.Shopify.customer.id;
    }
    
    // Method 3: meta tags
    const metaSelectors = ['meta[name="shopify-customer-id"]', 'meta[name="customer-id"]'];
    for (const selector of metaSelectors) {
        const meta = document.querySelector(selector);
        if (meta && meta.content) return parseInt(meta.content);
    }
    
    return null;
}

const customerId = detectCustomerId();
if (!customerId) {
    console.log('⚠️ No customer logged in - loyalty widget hidden');
    return;
}
console.log('👤 Loyalty Cart - Customer ID:', customerId);
    console.log('👤 Loyalty Cart - Customer ID:', customerId);
    
    // Page Type Detection
    const isCartPage = window.location.pathname.includes('/cart');
    
    // CONFIG
    const CONFIG = {
        apiUrl: '{{ rtrim(env("APP_URL"), "/") }}/api',
        shop: window.Shopify ? window.Shopify.shop : '',
        customerId: parseInt(customerId)
    };

    console.log('⚙️ Loyalty Config:', CONFIG);

    let loyaltyData = null;
    let activeRedemption = null;
    let settings = null;

    // ============================================
    // CREATE WIDGETS (Floating & Embedded)
    // ============================================
    function createWidget() {
        // 1. Floating Launcher (All Pages)
        createFloatingLauncher();
        
        // 2. Embedded Widget (Cart Page Only)
        if (isCartPage) {
            createEmbeddedWidget();
        }
        
        // 3. Modal (Hidden by default)
        createLoyaltyModal();
    }

    function createFloatingLauncher() {
        if (document.getElementById('metora-loyalty-launcher')) return;

        const launcher = document.createElement('div');
        launcher.id = 'metora-loyalty-launcher';
        launcher.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 20px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 2147483647;
            transition: transform 0.2s;
        `;
        launcher.innerHTML = '<div style="font-size: 30px;">⭐</div>';
        
        launcher.onclick = function() {
            const modal = document.getElementById('metora-loyalty-modal');
            if (modal) {
                modal.style.display = 'flex';
                // Default to Redeem tab
                switchTab('redeem');
            }
        };
        
        launcher.onmouseover = function() { this.style.transform = 'scale(1.1)'; };
        launcher.onmouseout = function() { this.style.transform = 'scale(1)'; };

        document.body.appendChild(launcher);
    }

    function createLoyaltyModal() {
        if (document.getElementById('metora-loyalty-modal')) return;

        const modal = document.createElement('div');
        modal.id = 'metora-loyalty-modal';
        modal.style.cssText = `
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 2147483648;
            align-items: center;
            justify-content: center;
        `;
        
        modal.innerHTML = `
            <div style="background: white; width: 90%; max-width: 400px; border-radius: 12px; overflow: hidden; position: relative; max-height: 90vh; display: flex; flex-direction: column;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 16px; color: white; display: flex; justify-content: space-between; align-items: center; flex-shrink: 0;">
                    <span style="font-weight: 600; font-size: 16px;">Rewards Program</span>
                    <button onclick="document.getElementById('metora-loyalty-modal').style.display='none'" style="background: none; border: none; color: white; font-size: 24px; cursor: pointer;">&times;</button>
                </div>
                
                <!-- TABS -->
                <div style="display: flex; border-bottom: 1px solid #eee; background: #f9fafb; flex-shrink: 0;">
                    <button onclick="window.metoraLoyalty.switchTab('redeem')" id="metora-tab-redeem" style="flex: 1; padding: 12px; border: none; background: none; font-weight: 600; color: #4b5563; cursor: pointer; border-bottom: 2px solid transparent;">Redeem</button>
                    <button onclick="window.metoraLoyalty.switchTab('history')" id="metora-tab-history" style="flex: 1; padding: 12px; border: none; background: none; font-weight: 600; color: #4b5563; cursor: pointer; border-bottom: 2px solid transparent;">History</button>
                </div>

                <!-- CONTENT AREA -->
                <div style="flex: 1; overflow-y: auto; padding: 20px; min-height: 300px;">
                    <div id="metora-loyalty-modal-content">
                        <!-- Redeem Content Goes Here -->
                        <div style="text-align: center; padding: 20px;">Loading...</div>
                    </div>
                    
                    <div id="metora-loyalty-history-content" style="display: none;">
                        <div style="text-align: center; padding: 20px;">Loading history...</div>
                    </div>
                </div>
            </div>
        `;
        
        // Close on background click
        modal.onclick = function(e) {
            if (e.target === modal) modal.style.display = 'none';
        };

        document.body.appendChild(modal);
    }

    // ... createEmbeddedWidget ...
    // Note: I am NOT replacing createEmbeddedWidget here, just ensuring the logic above is correct.
    // However, I need to make sure I don't delete createEmbeddedWidget.
    // The previous tool call output shows createEmbeddedWidget follows createLoyaltyModal.
    // I will include its definition to be safe if "StartLine" context requires it, 
    // but better to just use strict replacement of the two functions above.
    
    // START REPLACEMENT FROM LINE 70 (createFloatingLauncher) to end of createLoyaltyModal
    
    // NEW METHODS FOR HISTORY
    
    async function loadHistory() {
        const container = document.getElementById('metora-loyalty-history-content');
        container.innerHTML = '<div style="text-align: center; padding: 20px; opacity: 0.6;">Loading transactions...</div>';
        
        try {
            const response = await fetch(`${CONFIG.apiUrl}/storefront/loyalty/transactions`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    customer_id: CONFIG.customerId,
                    shop: CONFIG.shop
                })
            });
            
            if (response.ok) {
                const data = await response.json();
                renderHistory(data.transactions || []);
            } else {
                container.innerHTML = '<div style="text-align: center; color: red;">Failed to load history</div>';
            }
        } catch (error) {
            console.error('History error:', error);
            container.innerHTML = '<div style="text-align: center; color: red;">Error loading history</div>';
        }
    }
    
    function renderHistory(transactions) {
        const container = document.getElementById('metora-loyalty-history-content');
        
        if (transactions.length === 0) {
            container.innerHTML = '<div style="text-align: center; padding: 40px 20px; opacity: 0.6;">No transactions yet</div>';
            return;
        }
        
        let html = '<div style="display: flex; flex-direction: column; gap: 12px;">';
        
        transactions.forEach(tx => {
            const isPositive = tx.points > 0;
            const color = isPositive ? '#10b981' : '#ef4444';
            const sign = isPositive ? '+' : '';
            const date = new Date(tx.created_at).toLocaleDateString();
            
            html += `
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px; background: #f9fafb; border-radius: 8px;">
                    <div>
                        <div style="font-weight: 500; font-size: 14px; margin-bottom: 2px;">${tx.description || (isPositive ? 'Earned' : 'Redeemed')}</div>
                        <div style="font-size: 11px; opacity: 0.6;">${date}</div>
                    </div>
                    <div style="font-weight: 700; color: ${color}; font-size: 15px;">
                        ${sign}${tx.points} pts
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }

    function switchTab(tab) {
        const redeemBtn = document.getElementById('metora-tab-redeem');
        const historyBtn = document.getElementById('metora-tab-history');
        const redeemContent = document.getElementById('metora-loyalty-modal-content');
        const historyContent = document.getElementById('metora-loyalty-history-content');
        
        if (!redeemBtn || !historyBtn) return;
        
        // Reset styles
        redeemBtn.style.color = '#4b5563';
        redeemBtn.style.borderBottomColor = 'transparent';
        historyBtn.style.color = '#4b5563';
        historyBtn.style.borderBottomColor = 'transparent';
        
        redeemContent.style.display = 'none';
        historyContent.style.display = 'none';
        
        if (tab === 'redeem') {
            redeemBtn.style.color = '#764ba2';
            redeemBtn.style.borderBottomColor = '#764ba2';
            redeemContent.style.display = 'block';
            renderWidgetContent('metora-loyalty-modal-content');
        } else {
            historyBtn.style.color = '#764ba2';
            historyBtn.style.borderBottomColor = '#764ba2';
            historyContent.style.display = 'block';
            loadHistory();
        }
    }

    // switchTab attached to window.metoraLoyalty later

    function createEmbeddedWidget() {
        // Remove existing to avoid duplicates on refresh
        document.querySelectorAll('.metora-loyalty-widget-embedded').forEach(w => w.remove());

        // Find all cart forms/containers
        let cartForms = Array.from(document.querySelectorAll('form[action="/cart"], form[action^="/cart?"], .cart-drawer, cart-drawer, #cart-drawer-form'));
        
        if (cartForms.length === 0) {
            // Fallback
            const fallback = document.querySelector('.cart, #cart, main');
            if (fallback) cartForms = [fallback];
            else return null;
        }

        cartForms.forEach(cartForm => {
            // Avoid duplicate injection in nested forms
            if (cartForm.querySelector('.metora-loyalty-widget-embedded')) return;

            const widget = document.createElement('div');
            widget.className = 'metora-loyalty-widget-embedded';
            widget.style.cssText = `
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                border-radius: 12px;
                padding: 20px;
                margin: 20px 0;
                color: white;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            `;
            
            widget.innerHTML = `
                <div class="metora-loyalty-content-dynamic">
                    <div style="text-align: center; padding: 20px;">
                        <div style="font-size: 16px; font-weight: 600;">Loading loyalty points...</div>
                    </div>
                </div>
            `;

            const checkoutButton = cartForm.querySelector('button[name="checkout"], .cart__checkout-button');
            
            if (checkoutButton) {
                const checkoutContainer = checkoutButton.closest('.cart__footer, .cart__ctas, .cart-footer, .drawer__footer');
                if (checkoutContainer && checkoutContainer.parentNode) {
                    checkoutContainer.parentNode.insertBefore(widget, checkoutContainer);
                } else if (checkoutButton.parentNode) {
                    checkoutButton.parentNode.insertBefore(widget, checkoutButton);
                }
            } else {
                cartForm.appendChild(widget);
            }
        });

        console.log('✅ Embedded Widget containers created');
        return true;
    }

    // ============================================
    // LOAD DATA
    // ============================================
    async function loadLoyaltyData() {
        try {
            const response = await fetch(`${CONFIG.apiUrl}/storefront/loyalty`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    customer_id: CONFIG.customerId,
                    shop: CONFIG.shop
                })
            });

            if (!response.ok) {
                console.error('❌ Loyalty API error:', response.status);
                removeWidget();
                return;
            }

            const data = await response.json();
            
            if (data.has_loyalty) {
                loyaltyData = data;
                console.log('✅ Loyalty data loaded:', data);
            } else {
                console.log('ℹ️ Customer has no loyalty account');
                removeWidget();
            }
        } catch (error) {
            console.error('❌ Error loading loyalty data:', error);
            removeWidget();
        }
    }

    async function loadSettings() {
        try {
            const response = await fetch(`${CONFIG.apiUrl}/storefront/loyalty/settings?shop=${CONFIG.shop}`);
            
            if (response.ok) {
                settings = await response.json();
                console.log('✅ Settings loaded:', settings);
            } else {
                // Use defaults
                settings = {
                    points_per_dollar: 10,
                    points_value_cents: 10,
                    min_points_redemption: 100
                };
            }
        } catch (error) {
            console.error('⚠️ Error loading settings, using defaults:', error);
            settings = {
                points_per_dollar: 10,
                points_value_cents: 10,
                min_points_redemption: 100
            };
        }
    }

    async function loadActiveRedemptions() {
        try {
            const response = await fetch(`${CONFIG.apiUrl}/storefront/redemptions/active`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    customer_id: CONFIG.customerId,
                    shop: CONFIG.shop
                })
            });

            if (response.ok) {
                const data = await response.json();
                
                if (data.has_active && data.redemptions && data.redemptions.length > 0) {
                    activeRedemption = data.redemptions[0];
                    console.log('✅ Active redemption found:', activeRedemption);
                    
                    // Auto-apply discount to checkout
                    autoApplyDiscount();
                }
            }
        } catch (error) {
            console.error('⚠️ Error loading redemptions:', error);
        }
    }

    // ============================================
    // RENDER WIDGET CONTENT
    // ============================================
    function renderWidgetContent(targetIdentifier) {
        let contents = [];
        if (targetIdentifier.startsWith('.')) {
            contents = Array.from(document.querySelectorAll(targetIdentifier));
        } else {
            const el = document.getElementById(targetIdentifier);
            if (el) contents.push(el);
        }

        if (contents.length === 0) return;

        const pointValue = (loyaltyData.points_balance * settings.points_value_cents / 100).toFixed(2);
        
        let htmlString = '';

        if (activeRedemption) {
            // Show active redemption
            htmlString = `
                <div style="background: rgba(255,255,255,0.15); padding: 16px; border-radius: 8px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                        <div>
                            <div style="font-size: 14px; font-weight: 600;">🎉 Active Discount</div>
                            <div style="font-size: 12px; opacity: 0.9; margin-top: 2px;">Code: ${activeRedemption.coupon_code}</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 20px; font-weight: bold; color: #10b981;">₹${activeRedemption.discount_amount} OFF</div>
                        </div>
                    </div>
                    <button 
                        onclick="window.metoraLoyalty.cancelRedemption()" 
                        style="width: 100%; background: rgba(239, 68, 68, 0.8); color: white; border: none; padding: 8px; border-radius: 6px; font-size: 12px; cursor: pointer; font-weight: 500;"
                    >
                        Cancel Redemption
                    </button>
                </div>
                <div style="background: rgba(16, 185, 129, 0.2); padding: 12px; border-radius: 8px; border: 1px solid rgba(16, 185, 129, 0.3);">
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <span style="font-size: 20px;">✓</span>
                        <div style="flex: 1;">
                            <div style="font-size: 13px; font-weight: 600;">Discount Applied!</div>
                            <div style="font-size: 11px; opacity: 0.9; margin-top: 2px;">This discount will be automatically applied at checkout</div>
                        </div>
                    </div>
                </div>
                <div style="margin-top: 12px; font-size: 12px; opacity: 0.8; text-align: center;">
                    Remaining Balance: ${loyaltyData.points_balance} points (₹${pointValue})
                </div>
            `;
        } else if (loyaltyData.points_balance < settings.min_points_redemption) {
            // Not enough points
            htmlString = `
                <div style="text-align: center;">
                    <div style="font-size: 40px; margin-bottom: 8px;">⭐</div>
                    <div style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Loyalty Points</div>
                    <div style="font-size: 32px; font-weight: bold; margin: 8px 0;">${loyaltyData.points_balance}</div>
                    <div style="font-size: 14px; opacity: 0.9; margin-bottom: 12px;">Worth ₹${pointValue}</div>
                    <div style="background: rgba(255,255,255,0.2); padding: 12px; border-radius: 8px; font-size: 13px;">
                        💡 You need ${settings.min_points_redemption} points to redeem.<br>
                        Keep shopping to earn more!
                    </div>
                </div>
            `;
        } else {
            // Show redemption form
            htmlString = `
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
                    <div>
                        <div style="font-size: 14px; opacity: 0.9;">Available Points</div>
                        <div style="font-size: 28px; font-weight: bold;">${loyaltyData.points_balance}</div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 14px; opacity: 0.9;">Value</div>
                        <div style="font-size: 24px; font-weight: bold;">₹${pointValue}</div>
                    </div>
                </div>

                <div style="background: rgba(255,255,255,0.15); padding: 16px; border-radius: 8px; margin-bottom: 12px;">
                    <label style="display: block; font-size: 14px; margin-bottom: 8px; font-weight: 500;">Redeem Points for Discount</label>
                    
                    <div style="display: flex; gap: 8px; margin-bottom: 8px;">
                        <input 
                            type="number" 
                            id="metora-loyalty-points-input" 
                            placeholder="Enter points"
                            min="${settings.min_points_redemption}"
                            max="${loyaltyData.points_balance}"
                            style="flex: 1; padding: 10px; border: none; border-radius: 6px; font-size: 14px; color: #000;"
                            oninput="window.metoraLoyalty.updateValue(this.value)"
                        >
                        <button 
                            onclick="window.metoraLoyalty.redeemPoints()" 
                            id="metora-loyalty-redeem-btn"
                            style="background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; font-size: 14px; white-space: nowrap;"
                        >
                            Redeem
                        </button>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between; font-size: 12px; opacity: 0.9;">
                        <span>Min: ${settings.min_points_redemption} pts</span>
                        <span id="metora-loyalty-value-display">= ₹0.00</span>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px;">
                    ${loyaltyData.points_balance >= 100 ? `
                    <button onclick="window.metoraLoyalty.quickRedeem(100)" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 8px 4px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 500;">
                        100 pts<br><span style="font-size: 9px; opacity: 0.8;">₹1 off</span>
                    </button>` : ''}
                    ${loyaltyData.points_balance >= 250 ? `
                    <button onclick="window.metoraLoyalty.quickRedeem(250)" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 8px 4px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 500;">
                        250 pts<br><span style="font-size: 9px; opacity: 0.8;">₹2.5 off</span>
                    </button>` : ''}
                    ${loyaltyData.points_balance >= 500 ? `
                    <button onclick="window.metoraLoyalty.quickRedeem(500)" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 8px 4px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 500;">
                        500 pts<br><span style="font-size: 9px; opacity: 0.8;">₹5 off</span>
                    </button>` : ''}
                    <button onclick="window.metoraLoyalty.redeemAll()" style="background: rgba(255,255,255,0.2); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 8px 4px; border-radius: 6px; font-size: 11px; cursor: pointer; font-weight: 500;">
                        All<br><span style="font-size: 9px; opacity: 0.8;">${loyaltyData.points_balance} pts</span>
                    </button>
                </div>
            `;
        }

        contents.forEach(content => {
            content.innerHTML = htmlString;
        });

        console.log('✅ Widget rendered in', contents.length, 'containers');
    }

    function removeWidget() {
        // Remove ALL possible widgets
        document.querySelectorAll('.metora-loyalty-widget-embedded').forEach(w => w.remove());
        const modal = document.getElementById('metora-loyalty-modal');
        if (modal) modal.remove();
        const launcher = document.getElementById('metora-loyalty-launcher');
        if (launcher) launcher.remove();
    }

    // ============================================
    // AUTO-APPLY DISCOUNT
    // ============================================
    function autoApplyDiscount() {
        if (!activeRedemption) return;
    
        console.log('🎫 Auto-applying discount:', activeRedemption.coupon_code);
    
        // Method 1: Update checkout URL in links and buttons
        const checkoutButtons = document.querySelectorAll(
            'button[name="checkout"], ' +
            'input[name="checkout"], ' +
            'a[href*="/checkout"], ' +
            '.cart__checkout-button, ' +
            '[data-shopify="checkout-button"]'
        );
    
        checkoutButtons.forEach(btn => {
            // For <a> tags, update href
            if (btn.tagName === 'A') {
                const separator = btn.href.includes('?') ? '&' : '?';
                btn.href = btn.href.split('?')[0] + `?discount=${activeRedemption.coupon_code}`;
                console.log('✅ Updated link href:', btn.href);
            } 
            // For buttons and inputs
            else if (btn.tagName === 'BUTTON' || btn.tagName === 'INPUT') {
                // Find parent form
                const form = btn.closest('form');
                
                if (form) {
                    // Store original action
                    if (!form.dataset.originalAction) {
                        form.dataset.originalAction = form.action;
                    }
                    
                    // Update form action
                    const baseAction = form.dataset.originalAction || form.action;
                    const separator = baseAction.includes('?') ? '&' : '?';
                    form.action = baseAction.split('?')[0] + `?discount=${activeRedemption.coupon_code}`;
                    console.log('✅ Updated form action:', form.action);
                } else {
                    // No form - add onclick handler
                    btn.onclick = function(e) {
                        e.preventDefault();
                        window.location.href = `/checkout?discount=${activeRedemption.coupon_code}`;
                    };
                    console.log('✅ Added onclick handler');
                }
            }
        });
    
        // Method 2: Intercept Shopify checkout redirect
        if (window.Shopify && window.Shopify.routes) {
            const originalCheckout = window.Shopify.routes.root || '/checkout';
            window.Shopify.routes.root = `${originalCheckout}?discount=${activeRedemption.coupon_code}`;
        }
    
        // **REMOVED: Method 3 - DO NOT call /cart/update.js as it triggers the loop**
        // The discount will be applied via URL parameter at checkout instead
    
        console.log('✅ Discount auto-apply setup complete');
    }

    // ============================================
    // PUBLIC API
    // ============================================
    window.metoraLoyalty = {
        switchTab: switchTab,
        
        updateValue: function(points) {
            const value = (points * settings.points_value_cents / 100).toFixed(2);
            const display = document.getElementById('metora-loyalty-value-display');
            if (display) {
                display.textContent = `= ₹${value}`;
            }
        },

    quickRedeem: function(points) {
        if (points > loyaltyData.points_balance) {
            alert('Insufficient points balance');
            return;
        }
        const input = document.getElementById('metora-loyalty-points-input');
        if (input) {
            input.value = points;
            this.updateValue(points);
        }
        this.redeemPoints();
    },

    redeemAll: function() {
        this.quickRedeem(loyaltyData.points_balance);
    },

    redeemPoints: async function() {
        const input = document.getElementById('metora-loyalty-points-input');
        const points = parseInt(input?.value || 0);
        
        if (!points || points < settings.min_points_redemption) {
            alert(`Please enter at least ${settings.min_points_redemption} points`);
            return;
        }

        if (points > loyaltyData.points_balance) {
            alert('Insufficient points balance');
            return;
        }

        const btn = document.getElementById('metora-loyalty-redeem-btn');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Redeeming...';
        }

        try {
            const response = await fetch(`${CONFIG.apiUrl}/storefront/redemptions/create`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    customer_id: CONFIG.customerId,
                    shop: CONFIG.shop,
                    points: points
                })
            });

            const data = await response.json();

            if (data.success) {
                activeRedemption = data.redemption;
                loyaltyData.points_balance = data.remaining_balance;
                
                renderWidgetContent('.metora-loyalty-content-dynamic');
                renderWidgetContent('metora-loyalty-modal-content');
                autoApplyDiscount();
                
                alert(`Success! Your ₹${data.discount_amount} discount has been applied!`);
            } else {
                alert(data.error || 'Failed to redeem points');
            }
        } catch (error) {
            console.error('❌ Redemption error:', error);
            alert('Failed to redeem points. Please try again.');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.textContent = 'Redeem';
            }
        }
    },

    cancelRedemption: async function() {
        if (!confirm('Cancel this redemption? Your points will be refunded.')) return;

        try {
            const response = await fetch(`${CONFIG.apiUrl}/storefront/redemptions/${activeRedemption.id}/cancel`, {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json' }
            });

            const data = await response.json();

            if (data.success) {
                activeRedemption = null;
                await init();
                alert('Redemption cancelled and points refunded!');
            } else {
                alert('Failed to cancel redemption');
            }
        } catch (error) {
            console.error('❌ Cancel error:', error);
            alert('Failed to cancel redemption');
        }
    },

    getActiveDiscount: function() {
        if (activeRedemption && activeRedemption.coupon_code) {
            console.log('🎁 Loyalty: Returning active discount code:', activeRedemption.coupon_code);
            return activeRedemption.coupon_code;
        }
        console.log('ℹ️ Loyalty: No active discount');
        return null;
    },
    
    getActiveRedemption: function() {
        return activeRedemption;
    },
    
    // **NEW METHOD: Refresh widget when cart updates**
    refreshWidget: function() {
        console.log('🔄 Loyalty: Refreshing widget...');
        
        // ✅ PREVENT LOOP: Don't refresh if cart is updating
        if (window.metoraUpdateInProgress || window.metoraRefreshInProgress) {
            console.log('⏸️ Loyalty: Skipping refresh (cart updating)');
            return;
        }
        
        // Check if ANY embedded widget exists
        const widgets = document.querySelectorAll('.metora-loyalty-widget-embedded');
        
        if (widgets.length === 0 && isCartPage) {
            console.log('⚠️ Loyalty widget missing, recreating...');
            createEmbeddedWidget();
        }
        
        // Re-render content (without fetching new data)
        if (loyaltyData) {
            renderWidgetContent('.metora-loyalty-content-dynamic');
            renderWidgetContent('metora-loyalty-modal-content');
            
            // Reapply discount if active
            if (activeRedemption) {
                autoApplyDiscount();
            }
        }
        
        console.log('✅ Loyalty widget refreshed');
    }
};

    // **LISTEN FOR CART REFRESH EVENTS**
    // **LISTEN FOR CART REFRESH EVENTS**
    window.addEventListener('metoraCartRefreshed', function() {
        console.log('📢 Loyalty: Received cart refresh event');
        
        if (window.metoraLoyalty && window.metoraLoyalty.refreshWidget) {
            setTimeout(function() {
                window.metoraLoyalty.refreshWidget();
            }, 100);
        }
    });

    // ============================================
    // INITIALIZE
    // ============================================

    // Helper to update all visible instances
    function renderAllWidgets() {
        renderWidgetContent('.metora-loyalty-content-dynamic');
        renderWidgetContent('metora-loyalty-modal-content');
    }

    async function init() {
        console.log('🚀 Initializing loyalty widget...');
        createWidget();
        await loadSettings();
        await loadLoyaltyData();
        if (loyaltyData) {
            await loadActiveRedemptions();
            renderAllWidgets();
        }
        console.log('✅ Loyalty widget initialized');
    }
    
    // Start initialization
    init();
    
} // End of initLoyaltyCart function
// Call the initialization function
initLoyaltyCart();

})();
