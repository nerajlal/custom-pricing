(function() {
    'use strict';
    
    if (window.metoraLoyaltyWidgetInitialized) return;
    window.metoraLoyaltyWidgetInitialized = true;
    
    console.log('🎯 Loyalty Widget Script Loaded');
    
    function initLoyaltyWidget() {
        console.log('🎯 Initializing Loyalty Widget...');
    
    // ============================================
    // CONFIGURATION
    // ============================================
    const API_URL = '{{ rtrim(env("APP_URL"), "/") }}/api';
    const SHOP_DOMAIN = window.Shopify && window.Shopify.shop ? window.Shopify.shop : '{{ $shop_domain ?? "store.myshopify.com" }}';
    
    let customerId = null;
    let loyaltyData = null;
    let widgetVisible = false;
    let allTransactions = [];
    let allTiers = [];

    // ============================================
    // DETECT CUSTOMER ID
    // ============================================
    function detectCustomerId() {
    // Method 1: Check window.__st.cid (most reliable for Shopify)
    if (window.__st && window.__st.cid) {
        console.log('✅ Found customer ID in __st.cid:', window.__st.cid);
        return parseInt(window.__st.cid);
    }
    
    // Method 2: Check window.Shopify.customer
    if (window.Shopify && window.Shopify.customer && window.Shopify.customer.id) {
        console.log('✅ Found customer ID in Shopify.customer:', window.Shopify.customer.id);
        return window.Shopify.customer.id;
    }
    
    // Method 3: Check meta tags
    const metaSelectors = [
        'meta[name="shopify-customer-id"]',
        'meta[name="customer-id"]',
        'meta[property="customer:id"]'
    ];
    
    for (const selector of metaSelectors) {
        const meta = document.querySelector(selector);
        if (meta && meta.content) {
            console.log('✅ Found customer ID in meta tag:', meta.content);
            return parseInt(meta.content);
        }
    }
    
    console.log('❌ Could not detect customer ID');
    return null;
}

    // ============================================
    // CREATE SMALL WIDGET
    // ============================================
    function createWidget() {
        if (document.getElementById('loyalty-widget-container')) return;
        
        const widgetHTML = `
            <div id="loyalty-widget-container" class="lw-container">
                <div id="loyalty-collapsed" class="lw-collapsed">
                    <span class="lw-icon">⭐</span>
                    <div>
                        <div class="lw-label">Your Points</div>
                        <div class="lw-points" id="widget-points-collapsed">Loading...</div>
                    </div>
                </div>

                <div id="loyalty-expanded" class="lw-expanded">
                    <div class="lw-expanded-header">
                        <button onclick="toggleLoyaltyWidget()" class="lw-close-small">×</button>
                        <div class="lw-expanded-content">
                            <div class="lw-expanded-label">Loyalty Points</div>
                            <div class="lw-expanded-points" id="widget-points-expanded">0</div>
                            <div class="lw-expanded-value" id="widget-points-value">= $0.00</div>
                        </div>
                    </div>

                    <div class="lw-expanded-body">
                        <div class="lw-tier-section">
                            <div class="lw-tier-badge">
                                <span class="lw-tier-emoji">🏆</span>
                                <span class="lw-tier-name" id="widget-tier-name">Bronze</span>
                            </div>
                            <div class="lw-tier-benefit" id="widget-tier-benefit">1x points earned</div>
                        </div>

                        <button onclick="openLoyaltyPopup()" class="lw-view-btn">
                            <div class="lw-view-btn-title">📊 View Full Details</div>
                            <div class="lw-view-btn-sub">See all transactions & rewards</div>
                        </button>

                        <div class="lw-redeem-note">Contact us to redeem your points</div>
                    </div>
                </div>
            </div>

            <style>
                .lw-container { position: fixed; bottom: 20px; left: 20px; z-index: 999999; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; }
                .lw-collapsed { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 18px; border-radius: 50px; cursor: pointer; box-shadow: 0 4px 16px rgba(0,0,0,0.2); display: flex; align-items: center; gap: 10px; transition: all 0.3s ease; }
                .lw-collapsed:hover { transform: translateY(-3px); box-shadow: 0 6px 20px rgba(0,0,0,0.3); }
                .lw-icon { font-size: 22px; }
                .lw-label { font-size: 11px; opacity: 0.9; font-weight: 500; }
                .lw-points { font-size: 18px; font-weight: bold; }
                .lw-expanded { display: none; background: white; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.25); width: 340px; max-width: calc(100vw - 40px); overflow: hidden; animation: slideUp 0.3s ease; }
                .lw-expanded-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 24px 20px; position: relative; }
                .lw-close-small { position: absolute; top: 12px; right: 12px; background: rgba(255,255,255,0.2); border: none; color: white; width: 32px; height: 32px; border-radius: 50%; cursor: pointer; font-size: 20px; line-height: 1; display: flex; align-items: center; justify-content: center; transition: background 0.2s; font-family: inherit; }
                .lw-close-small:hover { background: rgba(255,255,255,0.3); }
                .lw-expanded-content { text-align: center; }
                .lw-expanded-label { font-size: 13px; opacity: 0.9; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px; }
                .lw-expanded-points { font-size: 42px; font-weight: bold; line-height: 1; }
                .lw-expanded-value { font-size: 13px; opacity: 0.85; margin-top: 6px; }
                .lw-expanded-body { padding: 20px; }
                .lw-tier-section { text-align: center; margin-bottom: 20px; }
                .lw-tier-badge { display: inline-flex; align-items: center; gap: 8px; background: #f3f4f6; padding: 10px 18px; border-radius: 24px; border: 2px solid #e5e7eb; }
                .lw-tier-emoji { font-size: 18px; }
                .lw-tier-name { font-weight: 700; color: #1f2937; font-size: 15px; }
                .lw-tier-benefit { font-size: 12px; color: #6b7280; margin-top: 8px; font-weight: 500; }
                .lw-view-btn { display: block; width: 100%; background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; padding: 14px; border-radius: 10px; text-align: center; border: none; cursor: pointer; box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3); transition: all 0.2s; margin-bottom: 12px; font-family: inherit; }
                .lw-view-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4); }
                .lw-view-btn-title { font-size: 14px; font-weight: 700; margin-bottom: 4px; }
                .lw-view-btn-sub { font-size: 11px; opacity: 0.9; }
                .lw-redeem-note { text-align: center; padding: 8px; font-size: 11px; color: #6b7280; }
                
                @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
                
                @media (max-width: 480px) {
                    .lw-container { bottom: 10px; left: 10px; }
                    .lw-expanded { width: calc(100vw - 20px); }
                    .lw-collapsed > div { display: none; }      /* Hides the text */
                    .lw-collapsed { padding: 12px; }            /* Reduces padding */
                    .lw-icon { font-size: 24px; } 
                }
            </style>
        `;

        document.body.insertAdjacentHTML('beforeend', widgetHTML);
        document.getElementById('loyalty-collapsed').addEventListener('click', toggleLoyaltyWidget);
    }

    // ============================================
    // CREATE POPUP MODAL
    // ============================================
    function createPopup() {
        if (document.getElementById('loyalty-popup-modal')) return;

        const popupHTML = `
            <div id="loyalty-popup-modal" class="lp-modal">
                <div class="lp-overlay" onclick="closeLoyaltyPopup()"></div>
                <div class="lp-container">
                    <div class="lp-content">
                        <button onclick="closeLoyaltyPopup()" class="lp-close">×</button>
                        <div class="lp-inner">
                            
                            <div class="lp-header">
                                <h1 class="lp-title">🏆 Loyalty Rewards</h1>
                                <p class="lp-subtitle">Track your points and unlock exclusive benefits</p>
                            </div>

                            <div class="lp-stats-grid">
                                <div class="lp-stat-card">
                                    <div class="lp-stat-label">Current Balance</div>
                                    <div class="lp-stat-value" id="popup-current-balance">0</div>
                                    <div class="lp-stat-unit">points</div>
                                </div>
                                <div class="lp-stat-card">
                                    <div class="lp-stat-label">Total Earned</div>
                                    <div class="lp-stat-value" id="popup-total-earned">0</div>
                                    <div class="lp-stat-unit">lifetime</div>
                                </div>
                                <div class="lp-stat-card">
                                    <div class="lp-stat-label">Total Redeemed</div>
                                    <div class="lp-stat-value" id="popup-total-redeemed">0</div>
                                    <div class="lp-stat-unit">used</div>
                                </div>
                                <div class="lp-stat-card">
                                    <div class="lp-stat-label">Points Value</div>
                                    <div class="lp-stat-value lp-stat-value-money" id="popup-points-value">$0.00</div>
                                    <div class="lp-stat-unit">cash value</div>
                                </div>
                            </div>

                            <div class="lp-main-grid">
                                
                                <div class="lp-left-col">
                                    
                                    <div class="lp-card">
                                        <h2 class="lp-card-title">Your Tier Status</h2>
                                        <div class="lp-tier-badge">
                                            <span class="lp-tier-icon">🏆</span>
                                            <div>
                                                <div class="lp-tier-name" id="popup-tier-name">Bronze</div>
                                                <div class="lp-tier-desc" id="popup-tier-benefits">1x points earned</div>
                                            </div>
                                        </div>
                                        <div class="lp-progress" id="popup-tier-progress">
                                            <div class="lp-progress-label">
                                                <span>Progress to <strong id="popup-next-tier-name">Silver</strong></span>
                                                <span id="popup-progress-text">0 / 500 points</span>
                                            </div>
                                            <div class="lp-progress-bar-bg">
                                                <div class="lp-progress-bar" id="popup-progress-bar"></div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="lp-card">
                                        <h2 class="lp-card-title">Transaction History</h2>
                                        <div class="lp-filter-tabs">
                                            <button class="lp-filter-tab lp-filter-active" data-type="all">All</button>
                                            <button class="lp-filter-tab" data-type="earn">Earned</button>
                                            <button class="lp-filter-tab" data-type="redeem">Redeemed</button>
                                            <button class="lp-filter-tab" data-type="bonus">Bonuses</button>
                                        </div>
                                        <div id="popup-transactions-list" class="lp-transactions">
                                            <div class="lp-empty">
                                                <div class="lp-empty-icon">📜</div>
                                                <div>Loading transactions...</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                </div>

                                <div class="lp-sidebar">
                                    
                                    <div class="lp-card">
                                        <h3 class="lp-card-subtitle">How to Earn Points</h3>
                                        <div class="lp-earn-item">
                                            <div class="lp-earn-icon">🛍️</div>
                                            <div class="lp-earn-text">
                                                <div class="lp-earn-title">Make a Purchase</div>
                                                <div class="lp-earn-desc"><strong>10 points</strong> for every $1 spent</div>
                                            </div>
                                        </div>
                                        <div class="lp-earn-item">
                                            <div class="lp-earn-icon">⭐</div>
                                            <div class="lp-earn-text">
                                                <div class="lp-earn-title">Tier Multipliers</div>
                                                <div class="lp-earn-desc">Earn more as you level up</div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="lp-card">
                                        <h3 class="lp-card-subtitle">Loyalty Tiers</h3>
                                        <div id="popup-tiers-list" class="lp-tiers"></div>
                                    </div>

                                    <div class="lp-card lp-card-centered">
                                        <h3 class="lp-card-subtitle">Redeem Your Points</h3>
                                        <p class="lp-redeem-text">Contact us to use your points for discounts</p>
                                        <a href="/pages/contact" class="lp-btn">Contact Store</a>
                                    </div>
                                    
                                </div>
                                
                            </div>
                            
                        </div>
                    </div>
                </div>
            </div>

            <style>
                @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
                
                .lp-modal { 
                    display: none; 
                    position: fixed; 
                    top: 0; 
                    left: 0; 
                    width: 100%; 
                    height: 100%; 
                    z-index: 9999999; 
                    animation: fadeIn 0.3s ease; 
                }
                
                .lp-overlay { 
                    position: fixed; 
                    top: 0; 
                    left: 0; 
                    width: 100%; 
                    height: 100%; 
                    background: rgba(0,0,0,0.6); 
                }
                
                .lp-container { 
                    position: fixed; 
                    top: 50%; 
                    left: 50%; 
                    transform: translate(-50%, -50%); 
                    width: calc(100% - 40px); 
                    max-width: 1200px; 
                    height: calc(100vh - 80px); 
                    max-height: 900px; 
                }
                
                .lp-content { 
                    width: 100%; 
                    height: 100%; 
                    background: #f9fafb; 
                    border-radius: 16px; 
                    box-shadow: 0 20px 60px rgba(0,0,0,0.3); 
                    position: relative; 
                    animation: slideUp 0.4s ease; 
                    display: flex; 
                    flex-direction: column; 
                    overflow: hidden; 
                }
                
                .lp-close { 
                    position: absolute; 
                    top: 20px; 
                    right: 20px; 
                    background: white; 
                    border: 2px solid #e1e3e5; 
                    color: #202223; 
                    width: 44px; 
                    height: 44px; 
                    border-radius: 50%; 
                    cursor: pointer; 
                    font-size: 24px; 
                    line-height: 1; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    transition: all 0.2s; 
                    z-index: 10; 
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1); 
                    font-family: inherit; 
                }
                
                .lp-close:hover { 
                    background: #f6f6f7; 
                    transform: rotate(90deg); 
                }
                
                .lp-inner { 
                    padding: 40px; 
                    overflow-y: auto; 
                    overflow-x: hidden; 
                    height: 100%; 
                    -webkit-overflow-scrolling: touch; 
                    -ms-overflow-style: none; 
                    scrollbar-width: none; 
                }
                
                .lp-inner::-webkit-scrollbar { 
                    display: none; 
                }
                
                .lp-header { 
                    text-align: center; 
                    margin-bottom: 40px; 
                }
                
                .lp-title { 
                    font-size: 32px; 
                    font-weight: 600; 
                    color: #202223; 
                    margin: 0 0 12px 0; 
                }
                
                .lp-subtitle { 
                    font-size: 16px; 
                    color: #6d7175; 
                    margin: 0; 
                }
                
                .lp-stats-grid { 
                    display: grid; 
                    grid-template-columns: repeat(4, 1fr); 
                    gap: 20px; 
                    margin-bottom: 32px; 
                }
                
                .lp-stat-card { 
                    background: white; 
                    border: 1px solid #e1e3e5; 
                    border-radius: 8px; 
                    padding: 20px; 
                    text-align: center; 
                }
                
                .lp-stat-label { 
                    font-size: 13px; 
                    font-weight: 500; 
                    color: #6d7175; 
                    text-transform: uppercase; 
                    letter-spacing: 0.5px; 
                    margin-bottom: 8px; 
                }
                
                .lp-stat-value { 
                    font-size: 36px; 
                    font-weight: 600; 
                    color: #202223; 
                    line-height: 1; 
                }
                
                .lp-stat-value-money { 
                    color: #008060; 
                }
                
                .lp-stat-unit { 
                    font-size: 13px; 
                    color: #6d7175; 
                    margin-top: 4px; 
                }
                
                .lp-main-grid { 
                    display: grid; 
                    grid-template-columns: 1fr 320px; 
                    gap: 24px; 
                }
                
                .lp-card { 
                    background: white; 
                    border: 1px solid #e1e3e5; 
                    border-radius: 8px; 
                    padding: 24px; 
                    margin-bottom: 24px; 
                }
                
                .lp-card:last-child { 
                    margin-bottom: 0; 
                }
                
                .lp-card-title { 
                    font-size: 18px; 
                    font-weight: 600; 
                    color: #202223; 
                    margin: 0 0 20px 0; 
                }
                
                .lp-card-subtitle { 
                    font-size: 16px; 
                    font-weight: 600; 
                    color: #202223; 
                    margin: 0 0 16px 0; 
                }
                
                .lp-card-centered { 
                    text-align: center; 
                }
                
                .lp-tier-badge { 
                    display: inline-flex; 
                    align-items: center; 
                    gap: 12px; 
                    padding: 16px 24px; 
                    background: #f6f6f7; 
                    border-radius: 8px; 
                    margin-bottom: 20px; 
                }
                
                .lp-tier-icon { 
                    font-size: 32px; 
                }
                
                .lp-tier-name { 
                    font-size: 24px; 
                    font-weight: 600; 
                    color: #202223; 
                }
                
                .lp-tier-desc { 
                    font-size: 14px; 
                    color: #6d7175; 
                }
                
                .lp-progress { 
                    margin-top: 24px; 
                }
                
                .lp-progress-label { 
                    display: flex; 
                    justify-content: space-between; 
                    font-size: 13px; 
                    color: #6d7175; 
                    margin-bottom: 8px; 
                }
                
                .lp-progress-bar-bg { 
                    width: 100%; 
                    height: 8px; 
                    background: #f6f6f7; 
                    border-radius: 4px; 
                    overflow: hidden; 
                }
                
                .lp-progress-bar { 
                    height: 100%; 
                    background: #008060; 
                    border-radius: 4px; 
                    transition: width 0.5s ease; 
                    width: 0%; 
                }
                
                .lp-filter-tabs { 
                    display: flex; 
                    gap: 8px; 
                    margin-bottom: 20px; 
                    flex-wrap: wrap; 
                }
                
                .lp-filter-tab { 
                    padding: 8px 16px; 
                    background: white; 
                    color: #202223; 
                    border: 1px solid #e1e3e5; 
                    border-radius: 6px; 
                    font-size: 14px; 
                    font-weight: 500; 
                    cursor: pointer; 
                    transition: all 0.2s; 
                    font-family: inherit; 
                }
                
                .lp-filter-tab:hover { 
                    background: #f6f6f7; 
                }
                
                .lp-filter-active { 
                    background: #008060 !important; 
                    color: white !important; 
                    border-color: #008060 !important; 
                }
                
                .lp-transactions { 
                    min-height: 200px; 
                }
                
                .lp-transaction-item { 
                    display: flex; 
                    justify-content: space-between; 
                    align-items: flex-start; 
                    padding: 16px; 
                    border: 1px solid #e1e3e5; 
                    border-radius: 8px; 
                    margin-bottom: 12px; 
                }
                
                .lp-txn-icon { 
                    width: 40px; 
                    height: 40px; 
                    background: #f6f6f7; 
                    border-radius: 8px; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    font-size: 20px; 
                    flex-shrink: 0; 
                }
                
                .lp-txn-details { 
                    flex: 1; 
                    margin-left: 16px; 
                }
                
                .lp-txn-title { 
                    font-size: 14px; 
                    font-weight: 500; 
                    color: #202223; 
                    margin-bottom: 4px; 
                }
                
                .lp-txn-date { 
                    font-size: 13px; 
                    color: #6d7175; 
                }
                
                .lp-txn-points { 
                    text-align: right; 
                }
                
                .lp-txn-value { 
                    font-size: 20px; 
                    font-weight: 600; 
                }
                
                .lp-txn-positive { 
                    color: #008060; 
                }
                
                .lp-txn-negative { 
                    color: #bf0711; 
                }
                
                .lp-txn-balance { 
                    font-size: 12px; 
                    color: #6d7175; 
                    margin-top: 4px; 
                }
                
                .lp-empty { 
                    text-align: center; 
                    padding: 40px 20px; 
                    color: #6d7175; 
                }
                
                .lp-empty-icon { 
                    font-size: 48px; 
                    margin-bottom: 16px; 
                    opacity: 0.5; 
                }
                
                .lp-earn-item { 
                    display: flex; 
                    gap: 12px; 
                    margin-bottom: 16px; 
                }
                
                .lp-earn-item:last-child { 
                    margin-bottom: 0; 
                }
                
                .lp-earn-icon { 
                    width: 36px; 
                    height: 36px; 
                    background: #f6f6f7; 
                    border-radius: 8px; 
                    display: flex; 
                    align-items: center; 
                    justify-content: center; 
                    font-size: 18px; 
                    flex-shrink: 0; 
                }
                
                .lp-earn-text { 
                    flex: 1; 
                }
                
                .lp-earn-title { 
                    font-size: 14px; 
                    font-weight: 500; 
                    color: #202223; 
                    margin-bottom: 4px; 
                }
                
                .lp-earn-desc { 
                    font-size: 13px; 
                    color: #6d7175; 
                }
                
                .lp-tier-item { 
                    padding: 12px; 
                    border: 1px solid #e1e3e5; 
                    border-radius: 8px; 
                    margin-bottom: 12px; 
                    background: white; 
                }
                
                .lp-tier-item:last-child { 
                    margin-bottom: 0; 
                }
                
                .lp-tier-current { 
                    border-color: #008060; 
                    background: #f6fff8; 
                }
                
                .lp-tier-header { 
                    display: flex; 
                    justify-content: space-between; 
                    align-items: center; 
                    margin-bottom: 8px; 
                }
                
                .lp-tier-info { 
                    display: flex; 
                    align-items: center; 
                    gap: 8px; 
                }
                
                .lp-tier-small-icon { 
                    font-size: 20px; 
                }
                
                .lp-tier-item-name { 
                    font-size: 14px; 
                    font-weight: 600; 
                    color: #202223; 
                }
                
                .lp-tier-points { 
                    font-size: 12px; 
                    color: #6d7175; 
                }
                
                .lp-tier-benefits { 
                    font-size: 12px; 
                    color: #6d7175; 
                }
                
                .lp-tier-badge-small { 
                    font-size: 11px; 
                    font-weight: 600; 
                    color: #008060; 
                    text-transform: uppercase; 
                    letter-spacing: 0.5px; 
                }
                
                .lp-btn { 
                    display: inline-block; 
                    padding: 12px 24px; 
                    background: #008060; 
                    color: white; 
                    text-decoration: none; 
                    border-radius: 6px; 
                    font-size: 14px; 
                    font-weight: 500; 
                    transition: background 0.2s; 
                }
                
                .lp-btn:hover { 
                    background: #006e52; 
                }
                
                .lp-redeem-text { 
                    font-size: 14px; 
                    color: #6d7175; 
                    margin: 0 0 16px 0; 
                }
                
                /* Tablet Responsive */
                @media (max-width: 1024px) {
                    .lp-container { 
                        width: calc(100% - 20px); 
                        height: calc(100vh - 40px); 
                    }
                    
                    .lp-content { 
                        border-radius: 12px; 
                    }
                    
                    .lp-inner { 
                        padding: 30px 24px; 
                    }
                    
                    .lp-main-grid { 
                        grid-template-columns: 1fr; 
                        gap: 20px; 
                    }
                    
                    .lp-sidebar { 
                        order: 2; 
                    }
                }
                
                /* Mobile Responsive */
                @media (max-width: 768px) {
                    .lp-container { 
                        width: 100%; 
                        height: 100%; 
                        max-height: 100%; 
                        top: 0; 
                        left: 0; 
                        transform: none; 
                    }
                    
                    .lp-content { 
                        border-radius: 0; 
                        height: 100%; 
                    }
                    
                    .lp-inner { 
                        padding: 20px 16px 80px 16px; 
                    }
                    
                    .lp-close { 
                        top: 12px; 
                        right: 12px; 
                        width: 40px; 
                        height: 40px; 
                        font-size: 22px; 
                    }
                    
                    .lp-header { 
                        margin-bottom: 24px; 
                    }
                    
                    .lp-title { 
                        font-size: 24px; 
                        padding-right: 50px; 
                    }
                    
                    .lp-subtitle { 
                        font-size: 14px; 
                    }
                    
                    .lp-stats-grid { 
                        grid-template-columns: repeat(2, 1fr); 
                        gap: 12px; 
                        margin-bottom: 24px; 
                    }
                    
                    .lp-stat-card { 
                        padding: 16px 12px; 
                    }
                    
                    .lp-stat-label { 
                        font-size: 11px; 
                    }
                    
                    .lp-stat-value { 
                        font-size: 28px; 
                    }
                    
                    .lp-stat-unit { 
                        font-size: 12px; 
                    }
                    
                    .lp-card { 
                        padding: 16px; 
                        margin-bottom: 16px; 
                    }
                    
                    .lp-card-title { 
                        font-size: 16px; 
                        margin-bottom: 16px; 
                    }
                    
                    .lp-card-subtitle { 
                        font-size: 15px; 
                    }
                    
                    .lp-tier-badge { 
                        padding: 12px 16px; 
                        width: 100%; 
                        box-sizing: border-box; 
                    }
                    
                    .lp-tier-icon { 
                        font-size: 28px; 
                    }
                    
                    .lp-tier-name { 
                        font-size: 20px; 
                    }
                    
                    .lp-filter-tabs { 
                        overflow-x: auto; 
                        -webkit-overflow-scrolling: touch; 
                        scrollbar-width: none; 
                        -ms-overflow-style: none; 
                    }
                    
                    .lp-filter-tabs::-webkit-scrollbar { 
                        display: none; 
                    }
                    
                    .lp-filter-tab { 
                        white-space: nowrap; 
                        flex-shrink: 0; 
                        padding: 8px 14px; 
                        font-size: 13px; 
                    }
                    
                    .lp-transaction-item { 
                        padding: 12px; 
                    }
                    
                    .lp-txn-icon { 
                        width: 36px; 
                        height: 36px; 
                        font-size: 18px; 
                    }
                    
                    .lp-txn-details { 
                        margin-left: 12px; 
                    }
                    
                    .lp-txn-title { 
                        font-size: 13px; 
                    }
                    
                    .lp-txn-date { 
                        font-size: 12px; 
                    }
                    
                    .lp-txn-value { 
                        font-size: 18px; 
                    }
                    
                    .lp-txn-balance { 
                        font-size: 11px; 
                    }
                }
                
                /* Small Mobile */
                @media (max-width: 480px) {
                    .lp-stats-grid { 
                        grid-template-columns: 1fr; 
                        gap: 10px; 
                    }
                    
                    .lp-inner { 
                        padding: 16px 12px 60px 12px; 
                    }
                    
                    .lp-title { 
                        font-size: 20px; 
                    }
                    
                    .lp-subtitle { 
                        font-size: 13px; 
                    }
                    
                    .lp-stat-value { 
                        font-size: 32px; 
                    }
                    
                    .lp-tier-name { 
                        font-size: 18px; 
                    }
                    
                    .lp-tier-desc { 
                        font-size: 13px; 
                    }
                    
                    .lp-card { 
                        padding: 14px; 
                    }
                    
                    .lp-transaction-item { 
                        flex-wrap: wrap; 
                    }
                    
                    .lp-txn-points { 
                        width: 100%; 
                        text-align: left; 
                        margin-top: 8px; 
                        padding-top: 8px; 
                        border-top: 1px solid #e1e3e5; 
                        display: flex; 
                        justify-content: space-between; 
                        align-items: center; 
                    }
                }
                
                /* Short Screens */
                @media (max-height: 600px) and (max-width: 768px) {
                    .lp-inner { 
                        padding: 16px 12px 40px 12px; 
                    }
                    
                    .lp-header { 
                        margin-bottom: 16px; 
                    }
                    
                    .lp-stats-grid { 
                        margin-bottom: 16px; 
                    }
                    
                    .lp-card { 
                        margin-bottom: 12px; 
                        padding: 12px; 
                    }
                }
            </style>
        `;

        document.body.insertAdjacentHTML('beforeend', popupHTML);

        document.querySelectorAll('.lp-filter-tab').forEach(btn => {
            btn.addEventListener('click', function() {
                filterPopupTransactions(this.dataset.type);
            });
        });
    }

    // ============================================
    // TOGGLE SMALL WIDGET
    // ============================================
    window.toggleLoyaltyWidget = function() {
        const collapsed = document.getElementById('loyalty-collapsed');
        const expanded = document.getElementById('loyalty-expanded');
        
        if (!collapsed || !expanded) return;
        
        widgetVisible = !widgetVisible;
        
        if (widgetVisible) {
            collapsed.style.display = 'none';
            expanded.style.display = 'block';
        } else {
            collapsed.style.display = 'flex';
            expanded.style.display = 'none';
        }
    };

    // ============================================
    // OPEN/CLOSE POPUP
    // ============================================
    window.openLoyaltyPopup = function() {
        createPopup();
        const modal = document.getElementById('loyalty-popup-modal');
        if (modal) {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            updatePopupContent();
        }
    };

    window.closeLoyaltyPopup = function() {
        const modal = document.getElementById('loyalty-popup-modal');
        if (modal) {
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }
    };

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('loyalty-popup-modal');
            if (modal && modal.style.display === 'block') {
                closeLoyaltyPopup();
            }
        }
    });

    // ============================================
    // API FUNCTIONS
    // ============================================
    async function loadLoyaltyData() {
        try {
            const response = await fetch(`${API_URL}/storefront/loyalty`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    customer_id: customerId,
                    shop: SHOP_DOMAIN
                })
            });

            if (!response.ok) {
                console.error('Loyalty Widget: API error', response.status);
                hideWidget();
                return;
            }

            const data = await response.json();

            if (data.has_loyalty) {
                loyaltyData = data;
                updateWidget(data);
                showWidget();
            } else {
                hideWidget();
            }
        } catch (error) {
            console.error('Loyalty Widget: Failed to load data', error);
            hideWidget();
        }
    }

    async function loadTransactions() {
        try {
            const response = await fetch(`${API_URL}/storefront/loyalty/transactions`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    customer_id: customerId,
                    shop: SHOP_DOMAIN
                })
            });

            const data = await response.json();
            allTransactions = data.transactions || [];
        } catch (error) {
            console.error('Loyalty Widget: Error loading transactions:', error);
            allTransactions = [];
        }
    }

    async function loadTiers() {
        try {
            const response = await fetch(`${API_URL}/storefront/loyalty/tiers`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    shop: SHOP_DOMAIN
                })
            });

            const data = await response.json();
            allTiers = data.tiers || [];
        } catch (error) {
            console.error('Loyalty Widget: Error loading tiers:', error);
            allTiers = [];
        }
    }

    // ============================================
    // UPDATE WIDGET
    // ============================================
    function updateWidget(data) {
        const pointsBalance = data.points_balance || 0;
        const pointsValue = data.points_value || 0;
        
        const collapsedPoints = document.getElementById('widget-points-collapsed');
        const expandedPoints = document.getElementById('widget-points-expanded');
        const valueEl = document.getElementById('widget-points-value');
        
        if (collapsedPoints) collapsedPoints.textContent = pointsBalance;
        if (expandedPoints) expandedPoints.textContent = pointsBalance.toLocaleString();
        if (valueEl) valueEl.textContent = `= $${pointsValue.toFixed(2)}`;

        if (data.tier) {
            const tierName = document.getElementById('widget-tier-name');
            const tierBenefit = document.getElementById('widget-tier-benefit');
            
            if (tierName) tierName.textContent = data.tier.name;
            if (tierBenefit) {
                const multiplier = (data.tier.points_multiplier / 100).toFixed(1);
                const discount = data.tier.discount_percentage;
                tierBenefit.textContent = `${multiplier}x points, ${discount}% discount`;
            }
        }
    }

    // ============================================
    // UPDATE POPUP CONTENT
    // ============================================
    function updatePopupContent() {
        if (!loyaltyData) return;

        const currentBalance = document.getElementById('popup-current-balance');
        const totalEarned = document.getElementById('popup-total-earned');
        const totalRedeemed = document.getElementById('popup-total-redeemed');
        const pointsValue = document.getElementById('popup-points-value');

        if (currentBalance) currentBalance.textContent = (loyaltyData.points_balance || 0).toLocaleString();
        if (totalEarned) totalEarned.textContent = (loyaltyData.total_earned || loyaltyData.points_balance || 0).toLocaleString();
        if (totalRedeemed) totalRedeemed.textContent = (loyaltyData.points_redeemed || 0).toLocaleString();
        if (pointsValue) pointsValue.textContent = '$' + (loyaltyData.points_value || 0).toFixed(2);

        if (loyaltyData.tier) {
            const tierName = document.getElementById('popup-tier-name');
            const tierBenefits = document.getElementById('popup-tier-benefits');
            
            if (tierName) tierName.textContent = loyaltyData.tier.name;
            if (tierBenefits) {
                const mult = (loyaltyData.tier.points_multiplier / 100).toFixed(1);
                tierBenefits.textContent = `${mult}x points • ${loyaltyData.tier.discount_percentage}% discount`;
            }
        }

        displayPopupTransactions(allTransactions);
        displayPopupTiers(allTiers);
        updatePopupProgress(allTiers);
    }

    // ============================================
    // DISPLAY TRANSACTIONS
    // ============================================
    function displayPopupTransactions(transactions) {
        const container = document.getElementById('popup-transactions-list');
        if (!container) return;
        
        if (transactions.length === 0) {
            container.innerHTML = '<div class="lp-empty"><div class="lp-empty-icon">📭</div><div>No transactions yet</div><div style="font-size: 13px; margin-top: 8px;">Points will appear here as you earn them</div></div>';
            return;
        }

        const icons = {
            earn: '🛍️',
            redeem: '🎁',
            bonus: '⭐',
            adjust: '✏️'
        };

        container.innerHTML = transactions.map(txn => {
            const date = new Date(txn.created_at).toLocaleDateString();
            const isPositive = txn.points >= 0;
            
            return `
                <div class="lp-transaction-item" data-type="${txn.type}">
                    <div class="lp-txn-icon">${icons[txn.type] || '💰'}</div>
                    <div class="lp-txn-details">
                        <div class="lp-txn-title">${txn.description}</div>
                        <div class="lp-txn-date">${date}</div>
                    </div>
                    <div class="lp-txn-points">
                        <div class="lp-txn-value ${isPositive ? 'lp-txn-positive' : 'lp-txn-negative'}">
                            ${isPositive ? '+' : ''}${txn.points}
                        </div>
                        <div class="lp-txn-balance">Balance: ${txn.balance_after}</div>
                    </div>
                </div>
            `;
        }).join('');
    }

    function filterPopupTransactions(type) {
        document.querySelectorAll('.lp-filter-tab').forEach(btn => {
            if (btn.dataset.type === type) {
                btn.classList.add('lp-filter-active');
            } else {
                btn.classList.remove('lp-filter-active');
            }
        });

        const filtered = type === 'all' ? allTransactions : allTransactions.filter(t => t.type === type);
        displayPopupTransactions(filtered);
    }

    // ============================================
    // DISPLAY TIERS
    // ============================================
    function displayPopupTiers(tiers) {
        const container = document.getElementById('popup-tiers-list');
        if (!container) return;
        
        container.innerHTML = tiers.map(tier => {
            const isCurrent = loyaltyData && loyaltyData.tier && loyaltyData.tier.name === tier.name;
            return `
                <div class="lp-tier-item ${isCurrent ? 'lp-tier-current' : ''}">
                    <div class="lp-tier-header">
                        <div class="lp-tier-info">
                            <span class="lp-tier-small-icon">🏆</span>
                            <div>
                                <div class="lp-tier-item-name">${tier.name}</div>
                                <div class="lp-tier-points">${tier.min_points_required}+ points</div>
                            </div>
                        </div>
                        ${isCurrent ? '<div class="lp-tier-badge-small">CURRENT</div>' : ''}
                    </div>
                    <div class="lp-tier-benefits">
                        ${(tier.points_multiplier / 100).toFixed(1)}x points • ${tier.discount_percentage}% discount
                    </div>
                </div>
            `;
        }).join('');
    }

    // ============================================
    // UPDATE PROGRESS
    // ============================================
    function updatePopupProgress(tiers) {
        const progressContainer = document.getElementById('popup-tier-progress');
        if (!progressContainer || !loyaltyData || !loyaltyData.tier) return;

        const currentIndex = tiers.findIndex(t => t.name === loyaltyData.tier.name);
        if (currentIndex === -1 || currentIndex === tiers.length - 1) {
            progressContainer.innerHTML = '<div style="text-align: center; padding: 16px; color: #008060; font-weight: 500;">🎉 You\'ve reached the highest tier!</div>';
            return;
        }

        const nextTier = tiers[currentIndex + 1];
        const current = loyaltyData.total_earned || loyaltyData.points_balance || 0;
        const progress = Math.min((current / nextTier.min_points_required) * 100, 100);

        const nextTierName = document.getElementById('popup-next-tier-name');
        const progressText = document.getElementById('popup-progress-text');
        const progressBar = document.getElementById('popup-progress-bar');

        if (nextTierName) nextTierName.textContent = nextTier.name;
        if (progressText) progressText.textContent = `${current} / ${nextTier.min_points_required} points`;
        if (progressBar) progressBar.style.width = progress + '%';
    }

    // ============================================
    // SHOW/HIDE WIDGET
    // ============================================
    function showWidget() {
        const container = document.getElementById('loyalty-widget-container');
        if (container) {
            container.style.display = 'block';
        }
    }

    function hideWidget() {
        const container = document.getElementById('loyalty-widget-container');
        if (container) {
            container.style.display = 'none';
        }
    }

    // ============================================
    // LOAD ALL DATA
    // ============================================
    async function loadAllData() {
    try {
        await loadLoyaltyData();
        await loadTransactions();
        await loadTiers();
        console.log('✅ All loyalty data loaded');
    } catch (error) {
        console.error('❌ Error loading loyalty data:', error);
    }
	}

    // ============================================
    // INITIALIZE
    // ============================================
    function init() {
        customerId = detectCustomerId();
        
        if (!customerId) {
            setTimeout(function() {
                customerId = detectCustomerId();
                if (customerId) {
                    init();
                }
            }, 1000);
            return;
        }

        console.log('Loyalty Widget: Initializing for customer:', customerId);
        createWidget();
        loadAllData();
        
        setInterval(loadAllData, 30000);
    }

    // Call init when ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
} // End of initLoyaltyWidget function
    
// Initialize when ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoyaltyWidget);
} else {
    initLoyaltyWidget();
}

})();
