<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Custom Pricing & Loyalty Manager</title>
    <link rel="icon" type="image/png" href="{{ asset('logo.png') }}">
    <meta name="shopify-api-key" content="{{ config('shopify.api_key') }}">
    <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js"></script>

    <script>
        var AppBridge = window['app-bridge'];
        var actions = AppBridge.actions;
        var createApp = AppBridge.default;
        
        // Initialize the App if running in an iframe (embedded)
        if (window.top !== window.self) {
            var apiKey = document.querySelector('meta[name="shopify-api-key"]').content;
            var host = new URLSearchParams(window.location.search).get('host');
            
            if (apiKey && host) {
                var app = createApp({
                    apiKey: apiKey,
                    host: host,
                    forceRedirect: true
                });
                
                // Store app instance globally
                window.shopifyApp = app;

                // Helper to get session token
                const getSessionToken = () => {
                    return new Promise((resolve, reject) => {
                        const SessionToken = actions.SessionToken;
                        const unsubscribe = app.subscribe(SessionToken.Action.RESPOND, (data) => {
                            unsubscribe();
                            resolve(data.sessionToken);
                        });
                        app.dispatch(SessionToken.request());
                        
                        setTimeout(() => {
                           unsubscribe();
                           reject('Session Token Timeout');
                        }, 5000);
                    });
                };

                // Intercept Fetch Requests
                const originalFetch = window.fetch;
                window.fetch = async function(url, options = {}) {
                    // Convert URL to string to be safe
                    const urlString = url.toString();
                    
                    // Check if it's an API call (relative to current origin or explicit API)
                    if (urlString.includes('/api/') || urlString.startsWith(window.location.origin)) {
                         try {
                             const token = await getSessionToken();
                             // Ensure headers object exists
                             options.headers = options.headers || {};
                             // Add Authorization header
                             if (options.headers instanceof Headers) {
                                 options.headers.append('Authorization', `Bearer ${token}`);
                             } else {
                                 options.headers['Authorization'] = `Bearer ${token}`;
                             }
                         } catch (error) {
                             console.warn('Failed to retrieve session token for fetch:', error);
                         }
                    }
                    return originalFetch(url, options);
                };
            }
        }
    </script>
    <script src="https://cdn.tailwindcss.com"></script>

	<style>
        /* Shopify Polaris Design System Colors & Styles */
        :root {
            --p-surface: #ffffff;
            --p-surface-subdued: #f9fafb;
            --p-border: #e1e3e5;
            --p-border-subdued: #c9cccf;
            --p-text: #202223;
            --p-text-subdued: #6d7175;
            --p-interactive: #008060;
            --p-interactive-hovered: #006e52;
            --p-interactive-pressed: #005e46;
            --p-critical: #d72c0d;
            --p-critical-hovered: #bf2809;
            --p-success: #008060;
            --p-warning: #ffc453;
            --p-info: #0084c9;
        }
        
        * {
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'San Francisco', 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background-color: #f1f2f4;
            color: var(--p-text);
            font-size: 14px;
            line-height: 20px;
        }
        
        /* Polaris Button Styles */
        .polaris-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 36px;
            padding: 7px 16px;
            background: var(--p-surface);
            border: 1px solid var(--p-border-subdued);
            border-radius: 8px;
            font-size: 14px;
            font-weight: 500;
            line-height: 1;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.1s ease-in-out;
            outline: none;
        }
        
        .polaris-btn:hover {
            background: #f6f6f7;
            border-color: var(--p-border-subdued);
        }
        
        .polaris-btn:active {
            background: #f1f2f3;
        }
        
        .polaris-btn-primary {
            background: var(--p-interactive);
            border-color: var(--p-interactive);
            color: white;
            box-shadow: 0 0 0 0 transparent, 0 1px 1px 0 rgba(0,0,0,.05);
        }
        
        .polaris-btn-primary:hover {
            background: var(--p-interactive-hovered);
            border-color: var(--p-interactive-hovered);
        }
        
        .polaris-btn-primary:active {
            background: var(--p-interactive-pressed);
            border-color: var(--p-interactive-pressed);
        }
        
        .polaris-btn-destructive {
            background: var(--p-critical);
            border-color: var(--p-critical);
            color: white;
        }
        
        .polaris-btn-destructive:hover {
            background: var(--p-critical-hovered);
            border-color: var(--p-critical-hovered);
        }
        
        /* Polaris Card */
        .polaris-card {
            background: var(--p-surface);
            border-radius: 12px;
            box-shadow: 0 0 0 1px rgba(63,63,68,.05), 0 1px 3px 0 rgba(63,63,68,.15);
            overflow: hidden;
        }
        
        .polaris-card-section {
            padding: 20px;
        }
        
        .polaris-card-section + .polaris-card-section {
            border-top: 1px solid var(--p-border);
        }
        
        /* Polaris Input */
        .polaris-textfield {
            position: relative;
        }
        
        .polaris-textfield input,
        .polaris-textfield textarea {
            width: 100%;
            min-height: 36px;
            padding: 7px 12px;
            background: var(--p-surface);
            border: 1px solid var(--p-border-subdued);
            border-radius: 8px;
            font-size: 14px;
            line-height: 20px;
            color: var(--p-text);
            transition: border-color 0.1s ease-in-out;
        }
        
        .polaris-textfield input:focus,
        .polaris-textfield textarea:focus,
        input[type="text"]:focus,
        input[type="number"]:focus,
        input[type="email"]:focus,
        select:focus {
            outline: none;
            border-color: var(--p-interactive);
            box-shadow: 0 0 0 1px var(--p-interactive);
        }

        /* Generic Input/Select Styling for cases without specific wrappers */
        input[type="text"],
        input[type="number"],
        input[type="email"],
        select {
            width: 100%;
            min-height: 36px;
            padding: 7px 12px;
            background: var(--p-surface);
            border: 1px solid var(--p-border-subdued);
            border-radius: 8px;
            font-size: 14px;
            line-height: 20px;
            color: var(--p-text);
            transition: all 0.1s ease-in-out;
        }

        select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 20 20'%3E%3Cpath d='M10 12l-5-5h10l-5 5z' fill='%236d7175'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 8px center;
            background-size: 16px;
            padding-right: 32px;
        }
        
        .polaris-textfield input::placeholder {
            color: #8c9196;
        }
        
        /* Polaris Badge */
        .polaris-badge {
            display: inline-flex;
            align-items: center;
            padding: 2px 8px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            line-height: 16px;
        }
        
        .polaris-badge-success {
            background: #aee9d1;
            color: #004c3f;
        }
        
        .polaris-badge-info {
            background: #b4e5fa;
            color: #004c68;
        }
        
        .polaris-badge-warning {
            background: #ffea8a;
            color: #5c4700;
        }
        
        .polaris-badge-attention {
            background: #ffb887;
            color: #4a1504;
        }
        
        /* Polaris Tabs */
        .polaris-tabs {
            display: flex;
            border-bottom: 1px solid var(--p-border);
            margin: 0;
            padding: 0;
            list-style: none;
        }
        
        .polaris-tab {
            position: relative;
            padding: 12px 16px;
            background: transparent;
            border: none;
            border-bottom: 3px solid transparent;
            color: var(--p-text-subdued);
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.1s ease-in-out;
        }
        
        .polaris-tab:hover {
            color: var(--p-text);
        }
        
        .polaris-tab-active {
            color: var(--p-interactive);
            border-bottom-color: var(--p-interactive);
        }
        
        /* Polaris Toggle */
        .polaris-choice {
            display: flex;
            align-items: center;
        }
        
        .polaris-choice-control {
            position: relative;
            width: 44px;
            height: 24px;
            background: #c9cccf;
            border-radius: 24px;
            cursor: pointer;
            transition: background-color 0.2s ease;
        }
        
        .polaris-choice-control::before {
            content: '';
            position: absolute;
            width: 20px;
            height: 20px;
            top: 2px;
            left: 2px;
            background: white;
            border-radius: 50%;
            box-shadow: 0 2px 4px rgba(0,0,0,0.2);
            transition: transform 0.2s ease;
        }
        
        .polaris-choice-input:checked + .polaris-choice-control {
            background: var(--p-interactive);
        }
        
        .polaris-choice-input:checked + .polaris-choice-control::before {
            transform: translateX(20px);
        }
        
        .polaris-choice-input {
            position: absolute;
            opacity: 0;
            width: 0;
            height: 0;
        }
        
        /* Polaris Label */
        .polaris-label {
            display: block;
            margin-bottom: 4px;
            color: var(--p-text);
            font-size: 13px;
            font-weight: 500;
            line-height: 16px;
        }
        
        .polaris-label-secondary {
            margin-top: 4px;
            color: var(--p-text-subdued);
            font-size: 12px;
            font-weight: 400;
        }
        
        /* Polaris Stack */
        .polaris-stack {
            display: flex;
            flex-wrap: wrap;
            align-items: stretch;
        }
        
        .polaris-stack-item {
            flex: 0 0 auto;
            min-width: 0;
        }
        
        .polaris-stack-item-fill {
            flex: 1 1 auto;
        }
        
        /* Polaris Layout */
        .polaris-layout {
            display: grid;
            gap: 20px;
        }
        
        /* Polaris Modal */
        .polaris-modal-backdrop {
            position: fixed;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .polaris-modal {
            background: white;
            border-radius: 12px;
            box-shadow: 0 26px 80px rgba(0, 0, 0, 0.4);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow: auto;
        }
        
        .polaris-modal-header {
            padding: 20px;
            border-bottom: 1px solid var(--p-border);
        }
        
        .polaris-modal-body {
            padding: 20px;
        }
        
        .polaris-modal-footer {
            padding: 20px;
            border-top: 1px solid var(--p-border);
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }
        
        /* Polaris Heading */
        .polaris-heading {
            font-size: 17px;
            font-weight: 600;
            line-height: 24px;
            margin: 0;
        }
        
        .polaris-subheading {
            font-size: 13px;
            font-weight: 600;
            line-height: 16px;
            text-transform: uppercase;
            color: var(--p-text-subdued);
        }
        
        /* Polaris Resource Item */
        .polaris-resource-item {
            padding: 16px;
            border: 1px solid var(--p-border);
            border-radius: 8px;
            background: var(--p-surface);
            transition: all 0.1s ease;
        }
        
        .polaris-resource-item:hover {
            box-shadow: 0 0 0 1px rgba(0,0,0,.1), 0 2px 4px rgba(0,0,0,.1);
        }
        
        /* Stats Cards */
        .stat-card {
            padding: 20px;
            border-radius: 12px;
            color: white;
        }
        
        .stat-card-value {
            font-size: 28px;
            font-weight: 600;
            line-height: 32px;
            margin: 8px 0 4px;
        }
        
        .stat-card-label {
            font-size: 13px;
            font-weight: 500;
            opacity: 0.9;
        }
        
        .stat-card-unit {
            font-size: 12px;
            opacity: 0.75;
        }
        
        /* Hide/Show */
        .hidden {
            display: none !important;
        }
        
        /* Responsive Grid */
        @media (min-width: 768px) {
            .grid-md-2 {
                grid-template-columns: repeat(2, 1fr);
            }
            .grid-md-4 {
                grid-template-columns: repeat(4, 1fr);
            }
        }
        
        /* Utility Classes */
        .gap-3 { gap: 12px; }
        .gap-4 { gap: 16px; }
        .gap-6 { gap: 24px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-6 { margin-bottom: 24px; }
        .mt-2 { margin-top: 8px; }
        .mt-4 { margin-top: 16px; }
        .text-subdued { color: var(--p-text-subdued); }
    </style>

</head>
<body>
    <div class="min-h-screen" style="padding: 20px; background-color: var(--p-surface-subdued);">
        <div class="max-w-7xl mx-auto">
            
            <!-- Header with Tabs -->
            <div class="polaris-card mb-6">
                <div class="polaris-card-section">
                    <div class="polaris-stack mb-4" style="justify-content: space-between; align-items: center;">
                        <div class="polaris-stack-item">
                            <h1 class="polaris-heading" style="font-size: 24px; margin-bottom: 0;">Custom Pricing & Loyalty Manager</h1>
                        </div>
                        <div class="polaris-stack-item">
                            <a href="https://custompricing.task19.com/home" target="_blank" class="polaris-btn" style="text-decoration: none; display: flex; align-items: center; gap: 8px;">
                                <svg viewBox="0 0 20 20" width="16" height="16" fill="currentColor"><path d="M10 2a8 8 0 100 16 8 8 0 000-16zm1 11H9v-2h2v2zm0-4H9V7h2v2z"/></svg>
                                Installation Guide
                            </a>
                        </div>
                    </div>
                    
                    <ul class="polaris-tabs">
                        <li>
                            <button onclick="showTab('custom-pricing')" id="tab-custom-pricing" class="polaris-tab polaris-tab-active">
                                Custom Pricing
                            </button>
                        </li>
                        <li>
                            <button onclick="showTab('pricing-tiers')" id="tab-pricing-tiers" class="polaris-tab">
                                Pricing Tiers
                            </button>
                        </li>

                        <li>
                            <button onclick="showTab('loyalty')" id="tab-loyalty" class="polaris-tab">
                                Loyalty Points
                            </button>
                        </li>
                        <li>
                            <button onclick="showTab('settings')" id="tab-settings" class="polaris-tab">
                                Settings
                            </button>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Custom Pricing Tab -->
            <div id="content-custom-pricing" class="tab-content">
                <div class="polaris-card mb-6">
                    <div class="polaris-card-section">
                        <h2 class="polaris-heading mb-4">Search Customer for Custom Pricing</h2>
                        
                        <div class="polaris-stack gap-3">
                            <div class="polaris-stack-item-fill">
                                <div class="polaris-textfield">
                                    <input 
                                        type="email" 
                                        id="customerEmail" 
                                        placeholder="Enter customer email..."
                                    >
                                </div>
                            </div>
                            <div class="polaris-stack-item">
                                <button 
                                    onclick="searchCustomer()"
                                    class="polaris-btn polaris-btn-primary"
                                >
                                    Search
                                </button>
                            </div>
                        </div>

                        <div id="customerResult" class="hidden mt-4">
                            <div class="polaris-resource-item">
                                <div class="polaris-stack" style="justify-content: space-between; align-items: flex-start;">
                                    <div class="polaris-stack-item-fill">
                                        <h3 class="polaris-heading" id="customerName"></h3>
                                        <p class="text-subdued" id="customerEmailDisplay"></p>
                                        <p class="polaris-label-secondary" id="customerId"></p>
                                    </div>
                                    
                                    <div class="polaris-stack-item polaris-choice">
                                        <span class="polaris-label" style="margin-right: 12px; margin-bottom: 0;">Enable Custom Pricing</span>
                                        <label class="polaris-choice">
                                            <input type="checkbox" id="customPricingToggle" onchange="toggleCustomPricing()" class="polaris-choice-input">
                                            <span class="polaris-choice-control"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="pricingSection" class="hidden">
                    <div class="polaris-card mb-6">
                        <div class="polaris-card-section">
                            <h2 class="polaris-heading mb-4">Set Custom Prices</h2>
                            
                            <div class="polaris-stack gap-3 mb-4">
                                <div class="polaris-stack-item-fill">
                                    <div class="polaris-textfield">
                                        <input 
                                            type="text" 
                                            id="productSearch" 
                                            placeholder="Search products..."
                                        >
                                    </div>
                                </div>
                                <div class="polaris-stack-item">
                                    <button 
                                        onclick="searchProducts()"
                                        class="polaris-btn polaris-btn-primary"
                                    >
                                        Search Products
                                    </button>
                                </div>
                            </div>

                            <div id="productResults" class="polaris-layout gap-4"></div>
                        </div>
                    </div>

                    <div class="polaris-card">
                        <div class="polaris-card-section">
                            <h2 class="polaris-heading mb-4">Current Custom Prices</h2>
                            <div id="existingPrices" class="polaris-layout gap-3"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pricing Tiers Tab -->
            <div id="content-pricing-tiers" class="tab-content hidden">
                <div class="polaris-card mb-6">
                    <div class="polaris-card-section">
                        <div class="polaris-stack" style="justify-content: space-between; align-items: center; margin-bottom: 20px;">
                            <h2 class="polaris-heading">Pricing Tiers</h2>
                            <button onclick="openCreateTierModal()" class="polaris-btn polaris-btn-primary">
                                Create New Tier
                            </button>
                        </div>
                        
                        <div id="tiersListContainer">
                             <p class="text-subdued" style="text-align: center; padding: 20px;">Loading Tiers...</p>
                        </div>
                    </div>
                </div>

                <!-- Tier Detail View (Hidden by default) -->
                <div id="tierDetailView" class="hidden">
                    <div class="polaris-card mb-4" style="background: var(--p-surface-subdued);">
                        <button onclick="closeTierDetail()" class="polaris-btn" style="margin-bottom: 10px;">← Back to Tiers</button>
                        <h2 class="polaris-heading" id="detailTierName" style="font-size: 20px;"></h2>
                        <p id="detailTierDesc" class="text-subdued"></p>
                    </div>

                    <div class="grid-md-2" style="display: grid; gap: 20px;">
                        <!-- Tier Prices Column -->
                        <div class="polaris-card">
                            <div class="polaris-card-section">
                                <h3 class="polaris-heading mb-4">Tier Prices</h3>
                                <p class="text-subdued mb-4">Set prices that apply to all customers in this tier.</p>
                                
                                <div class="polaris-stack gap-3 mb-4">
                                    <div class="polaris-stack-item-fill">
                                        <div class="polaris-textfield">
                                            <input type="text" id="tierProductSearch" placeholder="Search products...">
                                        </div>
                                    </div>
                                    <button onclick="searchProductsForTier()" class="polaris-btn polaris-btn-primary">Search</button>
                                </div>
                                <div id="tierProductResults" class="mb-6"></div>

                                <h4 class="polaris-heading mb-4">Current Tier Prices</h4>
                                <div id="tierExistingPrices"></div>
                            </div>
                        </div>

                        <!-- Tier Members Column -->
                        <div class="polaris-card">
                            <div class="polaris-card-section">
                                <h3 class="polaris-heading mb-4">Tier Members</h3>
                                <div class="polaris-stack gap-3 mb-4">
                                    <div class="polaris-stack-item-fill">
                                        <div class="polaris-textfield">
                                            <input type="email" id="tierCustomerSearch" placeholder="Add customer by email...">
                                        </div>
                                    </div>
                                    <button onclick="searchCustomerForTier()" class="polaris-btn">Find</button>
                                </div>
                                <div id="tierCustomerSearchResults" class="mb-4"></div>

                                <h4 class="polaris-heading mb-4">Existing Members</h4>
                                <div id="tierMembersList"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Create Tier Modal -->
            <div id="createTierModal" class="polaris-modal-backdrop hidden">
                <div class="polaris-modal">
                    <div class="polaris-modal-header">
                        <h2 class="polaris-heading">Create New Pricing Tier</h2>
                    </div>
                    <div class="polaris-modal-body">
                        <div class="polaris-layout">
                            <label class="polaris-label">Tier Name</label>
                            <div class="polaris-textfield mb-4">
                                <input type="text" id="newTierName" placeholder="e.g. Gold Wholesale">
                            </div>
                            
                            <label class="polaris-label">Description (Optional)</label>
                            <div class="polaris-textfield">
                                <input type="text" id="newTierDesc" placeholder="e.g. VIP customers get $10 off">
                            </div>
                        </div>
                    </div>
                    <div class="polaris-modal-footer">
                        <button onclick="document.getElementById('createTierModal').classList.add('hidden')" class="polaris-btn">Cancel</button>
                        <button onclick="createTier()" class="polaris-btn polaris-btn-primary">Create Tier</button>
                    </div>
                </div>
            </div>

            <!-- Loyalty Points Tab -->
            <div id="content-loyalty" class="tab-content hidden">
                
                <div class="polaris-card mb-6">
                    <div class="polaris-card-section">
                        <h2 class="polaris-heading mb-4">Customer Loyalty Account</h2>
                        
                        <div class="polaris-stack gap-3">
                            <div class="polaris-stack-item-fill">
                                <div class="polaris-textfield">
                                    <input 
                                        type="email" 
                                        id="loyaltyCustomerEmail" 
                                        placeholder="Enter customer email..."
                                    >
                                </div>
                            </div>
                            <div class="polaris-stack-item">
                                <button 
                                    onclick="searchLoyaltyCustomer()"
                                    class="polaris-btn polaris-btn-primary"
                                >
                                    Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="loyaltyAccountSection" class="hidden polaris-layout gap-6">
                    
                    <div class="polaris-layout gap-4 grid-md-4">
                        <div class="stat-card" style="background: linear-gradient(135deg, #0084c9 0%, #006bb8 100%);">
                            <div class="stat-card-label">Current Balance</div>
                            <div class="stat-card-value" id="currentBalance">0</div>
                            <div class="stat-card-unit">points</div>
                        </div>
                        
                        <div class="stat-card" style="background: linear-gradient(135deg, #008060 0%, #005e46 100%);">
                            <div class="stat-card-label">Total Earned</div>
                            <div class="stat-card-value" id="totalEarned">0</div>
                            <div class="stat-card-unit">points</div>
                        </div>
                        
                        <div class="stat-card" style="background: linear-gradient(135deg, #f49342 0%, #e67e22 100%);">
                            <div class="stat-card-label">Total Redeemed</div>
                            <div class="stat-card-value" id="totalRedeemed">0</div>
                            <div class="stat-card-unit">points</div>
                        </div>
                        
                        <div class="stat-card" style="background: linear-gradient(135deg, #9c6ade 0%, #7c3aed 100%);">
                            <div class="stat-card-label">Current Tier</div>
                            <div class="stat-card-value" style="font-size: 22px;" id="currentTier">Bronze</div>
                            <div class="stat-card-unit" id="tierBenefit">1x points</div>
                        </div>
                    </div>

                    <div class="polaris-card">
                        <div class="polaris-card-section">
                            <h3 class="polaris-heading mb-4">Quick Actions</h3>
                            
                            <div class="polaris-layout gap-4 grid-md-2">
                                <div class="polaris-resource-item">
                                    <h4 class="polaris-label mb-3">Adjust Points</h4>
                                    <div class="polaris-layout gap-3">
                                        <div class="polaris-textfield">
                                            <input 
                                                type="number" 
                                                id="adjustPointsInput"
                                                placeholder="Enter points (+ or -)"
                                            >
                                        </div>
                                        <div class="polaris-textfield">
                                            <input 
                                                type="text" 
                                                id="adjustReasonInput"
                                                placeholder="Reason for adjustment"
                                            >
                                        </div>
                                        <button 
                                            onclick="adjustPoints()"
                                            class="polaris-btn polaris-btn-primary" style="width: 100%;"
                                        >
                                            Adjust Points
                                        </button>
                                    </div>
                                </div>

                                <div class="polaris-resource-item">
                                    <h4 class="polaris-label mb-3">Redeem Points</h4>
                                    <div class="polaris-layout gap-3">
                                        <div class="polaris-textfield">
                                            <input 
                                                type="number" 
                                                id="redeemPointsInput"
                                                placeholder="Points to redeem"
                                            >
                                        </div>
                                        <div class="polaris-label-secondary" id="redeemValue">= $0.00 discount</div>
                                        <button 
                                            onclick="redeemPoints()"
                                            class="polaris-btn polaris-btn-primary" style="width: 100%; background: var(--p-success);"
                                        >
                                            Create Discount Code
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="polaris-card">
                        <div class="polaris-card-section">
                            <h3 class="polaris-heading mb-4">Transaction History</h3>
                            <div id="transactionHistory" class="polaris-layout gap-2"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Tab -->
            <div id="content-settings" class="tab-content hidden">
                
                <div class="polaris-card mb-6">
                    <div class="polaris-card-section">
                        <h2 class="polaris-heading mb-4">Loyalty Program Settings</h2>
                        
                        <div class="polaris-layout gap-4">
                            <div class="polaris-stack" style="justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--p-border);">
                                <div class="polaris-stack-item-fill">
                                    <label class="polaris-label">Enable Loyalty Program</label>
                                    <p class="polaris-label-secondary">Allow customers to earn and redeem points</p>
                                </div>
                                <div class="polaris-stack-item">
                                    <label class="polaris-choice">
                                        <input type="checkbox" id="loyaltyEnabled" class="polaris-choice-input" checked>
                                        <span class="polaris-choice-control"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="polaris-stack" style="justify-content: space-between; align-items: center; padding: 16px 0; border-bottom: 1px solid var(--p-border);">
                                <div class="polaris-stack-item-fill">
                                    <label class="polaris-label">Enable for All Customers</label>
                                    <p class="polaris-label-secondary">Automatically enable loyalty program for every customer in your store</p>
                                </div>
                                <div class="polaris-stack-item">
                                    <label class="polaris-choice">
                                        <input type="checkbox" id="allowAllCustomers" class="polaris-choice-input">
                                        <span class="polaris-choice-control"></span>
                                    </label>
                                </div>
                            </div>

                            <div class="polaris-layout gap-4 grid-md-2" style="padding-top: 16px;">
                                <div>
                                    <label class="polaris-label">Points per Dollar</label>
                                    <div class="polaris-textfield mt-2">
                                        <input 
                                            type="number" 
                                            id="pointsPerDollar"
                                            value="10"
                                        >
                                    </div>
                                    <p class="polaris-label-secondary">How many points customers earn per $1 spent</p>
                                </div>

                                <div>
                                    <label class="polaris-label">Points Value (cents)</label>
                                    <div class="polaris-textfield mt-2">
                                        <input 
                                            type="number" 
                                            id="pointsValueCents"
                                            value="10"
                                        >
                                    </div>
                                    <p class="polaris-label-secondary">100 points = $0.10 discount</p>
                                </div>

                                <div>
                                    <label class="polaris-label">Minimum Redemption</label>
                                    <div class="polaris-textfield mt-2">
                                        <input 
                                            type="number" 
                                            id="minRedemption"
                                            value="100"
                                        >
                                    </div>
                                    <p class="polaris-label-secondary">Minimum points required to redeem</p>
                                </div>

                                <div>
                                    <label class="polaris-label">Signup Bonus Points</label>
                                    <div class="polaris-textfield mt-2">
                                        <input 
                                            type="number" 
                                            id="signupBonus"
                                            value="100"
                                        >
                                    </div>
                                    <p class="polaris-label-secondary">Points given when customer joins</p>
                                </div>
                            </div>

                            <button 
                                onclick="saveLoyaltySettings()"
                                class="polaris-btn polaris-btn-primary mt-4"
                            >
                                Save Settings
                            </button>
                        </div>
                    </div>
                </div>

                <div class="polaris-card">
                    <div class="polaris-card-section">
                        <h2 class="polaris-heading mb-4">Loyalty Tiers</h2>
                        <div id="tiersList" class="polaris-layout gap-4 grid-md-4"></div>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Price Modal -->
    <div id="priceModal" class="hidden polaris-modal-backdrop">
        <div class="polaris-modal">
            <div class="polaris-modal-header">
                <h3 class="polaris-heading">Set Custom Price</h3>
            </div>
            
            <div class="polaris-modal-body">
                <div class="polaris-layout gap-4">
                    <div>
                        <label class="polaris-label">Product</label>
                        <p id="modalProductName" class="polaris-label" style="margin-bottom: 0;"></p>
                        <p id="modalVariantName" class="polaris-label-secondary"></p>
                    </div>

                    <div>
                        <label class="polaris-label">Original Price</label>
                        <p id="modalOriginalPrice" class="polaris-heading"></p>
                    </div>

                    <div>
                        <label class="polaris-label">Custom Price</label>
                        <div class="polaris-textfield mt-2">
                            <input 
                                type="number" 
                                id="customPriceInput"
                                step="0.01"
                                min="0"
                                placeholder="0.00"
                            >
                        </div>
                    </div>
                </div>
            </div>

            <div class="polaris-modal-footer">
                <button 
                    onclick="closeModal()"
                    class="polaris-btn"
                >
                    Cancel
                </button>
                <button 
                    onclick="saveCustomPrice()"
                    class="polaris-btn polaris-btn-primary"
                >
                    Save Price
                </button>
            </div>
        </div>
    </div>

    <script>
        // IMPORTANT: Update these values
        //const API_URL = '{{ env("APP_URL") }}/api';
      const API_URL = '{{ rtrim(env("APP_URL"), "/") }}/api';
	 // const SHOP_DOMAIN = '{{ request()->query("shop") ?? "neraj-test-store.myshopify.com" }}';
        
	// Get shop from URL parameters
const urlParams = new URLSearchParams(window.location.search);
const SHOP_DOMAIN = urlParams.get('shop') || '{{ request()->query("shop") }}';
        let currentPricingSetting = null;
        let selectedProduct = null;
        let loyaltySettings = null;
        let currentLoyaltyAccount = null;
        let currentTierId = null; // Track active tier in Tier View

        // Tabs Logic
        function showTab(tabId) {
            // Hide all contents
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            // Remove active class from all tabs
            document.querySelectorAll('.polaris-tab').forEach(el => el.classList.remove('polaris-tab-active'));
            
            // Show selected
            document.getElementById(`content-${tabId}`).classList.remove('hidden');
            document.getElementById(`tab-${tabId}`).classList.add('polaris-tab-active');

            // Load data if needed
            if (tabId === 'loyalty') {
                loadLoyaltySettings();
                loadTiers(); // Load loyalty tiers
            } else if (tabId === 'pricing-tiers') {
                loadPricingTiers(); // Load pricing tiers function (renamed to avoid conflict with loyalty tiers)
            }
        }

        // Custom Pricing Functions
        async function searchCustomer() {
            const email = document.getElementById('customerEmail').value.trim();
            if (!email) {
                alert('Please enter a customer email');
                return;
            }

            console.log('Searching for:', email, 'in shop:', SHOP_DOMAIN);

            try {
                const response = await fetch(`${API_URL}/admin/customers/search`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ email, shop: SHOP_DOMAIN })
                });

                const contentType = response.headers.get("content-type");
                let data;
                if (contentType && contentType.indexOf("application/json") !== -1) {
                     data = await response.json();
                } else {
                     const text = await response.text();
                     console.error('Non-JSON response:', text);
                     throw new Error('Server returned non-JSON response: ' + response.status);
                }
                
                if (response.ok) {
                    currentCustomer = data.customer;
                    currentPricingSetting = data.pricing_setting;
                    displayCustomer(data.customer, data.pricing_setting);
                    loadExistingPrices();
                } else {
                    console.log('Search failed:', data);
                    alert(data.message || 'Customer not found');
                }
            } catch (error) {
                console.error('Error in searchCustomer:', error);
                alert('Failed to search customer. Check console for details.');
            }
        }

        function displayCustomer(customer, setting) {
            document.getElementById('customerName').textContent = `${customer.first_name} ${customer.last_name}`;
            document.getElementById('customerEmailDisplay').textContent = customer.email;
            document.getElementById('customerId').textContent = `Customer ID: ${customer.id}`;
            
            // Handle null setting gracefully
            const isEnabled = setting ? setting.is_custom_pricing_enabled : false;
            document.getElementById('customPricingToggle').checked = isEnabled;
            
            document.getElementById('customerResult').classList.remove('hidden');
            document.getElementById('pricingSection').classList.remove('hidden'); // This might need to be hidden if we don't have a specific mode selected yet, but for now show it.
        }

        async function toggleCustomPricing() {
            const enabled = document.getElementById('customPricingToggle').checked;
            
            try {
                const response = await fetch(`${API_URL}/admin/customers/toggle-pricing?shop=${SHOP_DOMAIN}`, {
            	method: 'POST',
            	headers: {
                	'Content-Type': 'application/json',
           	 },
            	body: JSON.stringify({
                customer_pricing_setting_id: currentPricingSetting.id,
                enabled,
                shop: SHOP_DOMAIN
            	})
        	});

                const data = await response.json();
                
                if (response.ok) {
                    currentPricingSetting = data.setting;
                    alert(`Custom pricing ${enabled ? 'enabled' : 'disabled'} successfully`);
                } else {
                    alert('Failed to update pricing settings');
                    document.getElementById('customPricingToggle').checked = !enabled;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to toggle custom pricing');
                document.getElementById('customPricingToggle').checked = !enabled;
            }
        }

        async function searchProducts() {
            const query = document.getElementById('productSearch').value.trim();
            if (!query) {
                alert('Please enter a product name');
                return;
            }

            try {
                //const response = await fetch(`${API_URL}/admin/products/search`, {
                const response = await fetch(`${API_URL}/admin/products/search?shop=${SHOP_DOMAIN}`, {
		    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ query, shop: SHOP_DOMAIN })
                });

                const products = await response.json();
                displayProducts(products);
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to search products');
            }
        }

        function displayProducts(products) {
            const container = document.getElementById('productResults');
            container.innerHTML = '';

                products.forEach(product => {
                product.variants.forEach(variant => {
                    const div = document.createElement('div');
                    div.className = 'polaris-resource-item';
                    
                    // Add data attributes for easy access
                    div.dataset.variantId = variant.id;
                    div.dataset.productTitle = product.title;
                    div.dataset.variantTitle = variant.title;
                    div.dataset.originalPrice = variant.price;
                    
                    div.innerHTML = `
                        <div class="polaris-stack" style="justify-content: space-between; align-items: center;">
                            <div class="polaris-stack-item-fill">
                                <h4 class="polaris-label">${product.title}</h4>
                                <p class="polaris-label-secondary">${variant.title}</p>
                                <p class="polaris-heading mt-2">$${variant.price}</p>
                            </div>
                            <div class="polaris-stack-item">
                                <div class="polaris-stack">
                                    <div class="polaris-stack-item">
                                        <div class="polaris-textfield">
                                            <input type="number" 
                                                placeholder="Custom Price" 
                                                step="0.01" 
                                                min="0"
                                                style="width: 120px;"
                                            >
                                        </div>
                                    </div>
                                    <div class="polaris-stack-item">
                                        <button 
                                            class="polaris-btn" 
                                            onclick="saveCustomPrice(${product.id}, ${variant.id})"
                                        >
                                            Save
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    container.appendChild(div);
                });
            });
        }

        function openPriceModal(product) {
            selectedProduct = product;
            document.getElementById('modalProductName').textContent = product.product_title;
            document.getElementById('modalVariantName').textContent = product.variant_title;
            document.getElementById('modalOriginalPrice').textContent = `$${product.price}`;
            document.getElementById('customPriceInput').value = '';
            document.getElementById('priceModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('priceModal').classList.add('hidden');
            selectedProduct = null;
        }

        async function saveCustomPrice(productId, variantId) {
            const row = document.querySelector(`div[data-variant-id="${variantId}"]`);
            const priceInput = row.querySelector('input[type="number"]');
            const customPrice = parseFloat(priceInput.value);
            
            // Validate input
            if (isNaN(customPrice) || customPrice < 0) {
                alert('Please enter a valid price');
                return;
            }

            // Determine context (Customer Specific vs Tier)
            // For now, we are in the "Customer Pricing" tab context
            // checking if currentPricingSetting is valid
            if (!currentPricingSetting || !currentPricingSetting.id) {
                alert('No active customer pricing setting found. Please search for a customer first.');
                return;
            }

            try {
                const response = await fetch(`${API_URL}/admin/custom-prices`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        shop: SHOP_DOMAIN,
                        customer_pricing_setting_id: currentPricingSetting.id, // Send this for customer-specific
                        shopify_product_id: productId,
                        shopify_variant_id: variantId,
                        product_title: row.dataset.productTitle,
                        variant_title: row.dataset.variantTitle,
                        original_price: row.dataset.originalPrice,
                        custom_price: customPrice
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    // Update UI to show saved state
                    priceInput.style.borderColor = 'var(--p-success)';
                    // Reload existing prices to show them in the "Current Custom Prices" list
                    loadExistingPrices();
                    
                    // Show brief success feedback
                    const btn = row.querySelector('button');
                    const originalText = btn.textContent;
                    btn.textContent = 'Saved!';
                    btn.classList.add('polaris-btn-primary');
                    setTimeout(() => {
                        btn.textContent = originalText;
                        btn.classList.remove('polaris-btn-primary');
                    }, 2000);
                } else {
                    alert(data.message || 'Failed to set custom price');
                }
            } catch (error) {
                console.error('Error saving price:', error);
                alert('Failed to save custom price');
            }
        }

        async function loadExistingPrices() {
            if (!currentPricingSetting || !currentPricingSetting.id) return;

            try {
                const response = await fetch(`${API_URL}/admin/custom-prices/customer/${currentPricingSetting.id}?shop=${SHOP_DOMAIN}`);
                const data = await response.json();
                
                if (response.ok) {
                    displayExistingPrices(data.prices);
                }
            } catch (error) {
                console.error('Error loading prices:', error);
            }
        }

        function displayExistingPrices(prices) {
            const container = document.getElementById('existingPrices');
            container.innerHTML = '';

            if (prices.length === 0) {
                container.innerHTML = '<p class="text-subdued" style="text-align: center; padding: 16px 0;">No custom prices set yet</p>';
                return;
            }

            prices.forEach(price => {
                const div = document.createElement('div');
                div.className = 'polaris-resource-item';
                const discount = ((price.original_price - price.custom_price) / price.original_price * 100).toFixed(2);
                
                div.innerHTML = `
                    <div class="polaris-stack" style="justify-content: space-between; align-items: flex-start;">
                        <div class="polaris-stack-item-fill">
                            <h4 class="polaris-label">${price.product_title}</h4>
                            <p class="polaris-label-secondary">${price.variant_title || 'Default'}</p>
                            <div class="polaris-stack gap-4 mt-2">
                                <span class="polaris-label-secondary">Original: <span style="text-decoration: line-through;">$${price.original_price}</span></span>
                                <span class="polaris-label" style="color: var(--p-success);">Custom: $${price.custom_price}</span>
                                <span class="polaris-badge polaris-badge-success">${discount}% off</span>
                            </div>
                        </div>
                        <div class="polaris-stack-item">
                            <button 
                                onclick="deletePrice(${price.id})"
                                class="polaris-btn polaris-btn-destructive"
                            >
                                Delete
                            </button>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        async function deletePrice(priceId, isTier = false) {
            if (!confirm('Are you sure you want to delete this custom price?')) return;

            try {
                const response = await fetch(`${API_URL}/admin/custom-prices/${priceId}?shop=${SHOP_DOMAIN}`, {
                    method: 'DELETE'
                });

                if (response.ok) {
                    // alert('Custom price deleted successfully');
                    if (isTier && currentTierId) {
                        loadTierPrices(currentTierId);
                    } else {
                        loadExistingPrices();
                    }
                } else {
                    alert('Failed to delete custom price');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to delete custom price');
            }
        }

        // Loyalty Functions
        async function searchLoyaltyCustomer() {
            const email = document.getElementById('loyaltyCustomerEmail').value.trim();
            if (!email) {
                alert('Please enter a customer email');
                return;
            }

            try {
                const response = await fetch(`${API_URL}/admin/loyalty/customers/search`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ email, shop: SHOP_DOMAIN })
                });

                const data = await response.json();
                
                if (response.ok) {
                    currentLoyaltyAccount = data.loyalty_account;
                    displayLoyaltyAccount(data.loyalty_account);
                } else {
                    alert(data.message || 'Customer not found');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to search customer');
            }
        }

        function displayLoyaltyAccount(account) {
            document.getElementById('currentBalance').textContent = account.current_points_balance || 0;
            document.getElementById('totalEarned').textContent = account.total_points_earned || 0;
            document.getElementById('totalRedeemed').textContent = account.points_redeemed || 0;
            
            // Update tier benefit
            if (account.tier) {
                document.getElementById('tierBenefit').textContent = `${account.tier.points_multiplier / 100}x points, ${account.tier.discount_percentage}% discount`;
            } else {
                document.getElementById('tierBenefit').textContent = '1x points, 0% discount';
            }
            
            // Add status toggle
            const statusBadge = account.is_enabled ? 
                '<span class="polaris-badge polaris-badge-success">Active</span>' : 
                '<span class="polaris-badge polaris-badge-critical">Disabled</span>';
            
            const toggleBtn = `
                <button onclick="toggleCustomerLoyalty(${account.id}, ${!account.is_enabled})" 
                        class="polaris-btn ${account.is_enabled ? 'polaris-btn-destructive' : 'polaris-btn-primary'}" 
                        style="margin-top: 10px; width: 100%;">
                    ${account.is_enabled ? 'Disable Loyalty for this Customer' : 'Enable Loyalty for this Customer'}
                </button>
            `;

            document.getElementById('currentTier').innerHTML = `${account.tier ? account.tier.name : 'Bronze'} ${statusBadge}${toggleBtn}`;

            displayTransactionHistory(account.transactions);
            document.getElementById('loyaltyAccountSection').classList.remove('hidden');
        }

        function displayTransactionHistory(transactions) {
            const container = document.getElementById('transactionHistory');
            container.innerHTML = '';

            if (!transactions || transactions.length === 0) {
                container.innerHTML = '<p class="text-subdued" style="text-align: center; padding: 16px 0;">No transactions yet</p>';
                return;
            }

            transactions.forEach(txn => {
                const typeColors = {
                    'earn': 'polaris-badge-success',
                    'redeem': 'polaris-badge-attention',
                    'bonus': 'polaris-badge-info',
                    'adjust': 'polaris-badge-warning'
                };

                const div = document.createElement('div');
                div.className = 'polaris-resource-item';
                div.innerHTML = `
                    <div class="polaris-stack" style="justify-content: space-between; align-items: center;">
                        <div class="polaris-stack-item-fill">
                            <p class="polaris-label">${txn.description}</p>
                            <p class="polaris-label-secondary">${new Date(txn.created_at).toLocaleDateString()}</p>
                        </div>
                        <div class="polaris-stack-item" style="text-align: right;">
                            <span class="polaris-badge ${typeColors[txn.type] || 'polaris-badge-info'}">
                                ${txn.points >= 0 ? '+' : ''}${txn.points}
                            </span>
                            <p class="polaris-label-secondary" style="margin-top: 4px;">Balance: ${txn.balance_after}</p>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        async function adjustPoints() {
            const points = parseInt(document.getElementById('adjustPointsInput').value);
            const reason = document.getElementById('adjustReasonInput').value.trim();

            if (!points || !reason) {
                alert('Please enter points and reason');
                return;
            }

            if (!currentLoyaltyAccount) {
                alert('Please search for a customer first');
                return;
            }

            try {
                const response = await fetch(`${API_URL}/admin/loyalty/points/adjust?shop=${SHOP_DOMAIN}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        account_id: currentLoyaltyAccount.id,
                        points: points,
                        reason: reason
                    })
                });

                const data = await response.json();
                
                if (response.ok) {
                    alert('Points adjusted successfully!');
                    document.getElementById('adjustPointsInput').value = '';
                    document.getElementById('adjustReasonInput').value = '';
                    searchLoyaltyCustomer();
                } else {
                    alert(data.message || 'Failed to adjust points');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to adjust points');
            }
        }

        async function redeemPoints() {
            const points = parseInt(document.getElementById('redeemPointsInput').value);

            if (!points || points <= 0) {
                alert('Please enter valid points amount');
                return;
            }

            if (!currentLoyaltyAccount) {
                alert('Please search for a customer first');
                return;
            }

            try {
                const response = await fetch(`${API_URL}/admin/loyalty/points/redeem?shop=${SHOP_DOMAIN}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        account_id: currentLoyaltyAccount.id,
                        points: points
                    })
                });

                const data = await response.json();
                
                if (response.ok) {
                    alert(`Success! Discount code: ${data.redemption.coupon_code}\nDiscount: ${data.redemption.discount_amount}`);
                    document.getElementById('redeemPointsInput').value = '';
                    searchLoyaltyCustomer();
                } else {
                    alert(data.message || 'Failed to redeem points');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to redeem points');
            }
        }

        document.getElementById('redeemPointsInput')?.addEventListener('input', function() {
            const points = parseInt(this.value) || 0;
            if (loyaltySettings) {
                const value = (points * loyaltySettings.points_value_cents / 100).toFixed(2);
                document.getElementById('redeemValue').textContent = `= $${value} discount`;
            }
        });

        async function toggleCustomerLoyalty(accountId, enabled) {
            if (!confirm(`Are you sure you want to ${enabled ? 'enable' : 'disable'} loyalty for this customer?`)) return;

            try {
                const response = await fetch(`${API_URL}/admin/loyalty/customers/toggle-status?shop=${SHOP_DOMAIN}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        account_id: accountId,
                        enabled: enabled
                    })
                });

                if (response.ok) {
                    alert('Status updated successfully!');
                    searchLoyaltyCustomer(); // Refresh display
                } else {
                    alert('Failed to update status');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to toggle loyalty status');
            }
        }

        async function loadLoyaltySettings() {
            try {
                const response = await fetch(`${API_URL}/admin/loyalty/settings?shop=${SHOP_DOMAIN}`);
                loyaltySettings = await response.json();
                
                document.getElementById('loyaltyEnabled').checked = loyaltySettings.is_enabled;
                document.getElementById('allowAllCustomers').checked = !!loyaltySettings.allow_all_customers;
                document.getElementById('pointsPerDollar').value = loyaltySettings.points_per_dollar;
                document.getElementById('pointsValueCents').value = loyaltySettings.points_value_cents;
                document.getElementById('minRedemption').value = loyaltySettings.min_points_redemption;
                document.getElementById('signupBonus').value = loyaltySettings.signup_bonus_points;
            } catch (error) {
                console.error('Error loading settings:', error);
            }
        }

        async function saveLoyaltySettings() {
            const settings = {
        	is_enabled: document.getElementById('loyaltyEnabled').checked,
                allow_all_customers: document.getElementById('allowAllCustomers').checked,
        	points_per_dollar: parseInt(document.getElementById('pointsPerDollar').value),
        	points_value_cents: parseInt(document.getElementById('pointsValueCents').value),
        	min_points_redemption: parseInt(document.getElementById('minRedemption').value),
       		signup_bonus_points: parseInt(document.getElementById('signupBonus').value)
    	     };

            try {
                const response = await fetch(`${API_URL}/admin/loyalty/settings?shop=${SHOP_DOMAIN}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ shop: SHOP_DOMAIN, settings })
                });

                if (response.ok) {
                    alert('Settings saved successfully!');
                    loadLoyaltySettings();
                } else {
                    alert('Failed to save settings');
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to save settings');
            }
        }

        async function loadTiers() {
            try {
                const response = await fetch(`${API_URL}/admin/loyalty/tiers?shop=${SHOP_DOMAIN}`);
                const tiers = await response.json();
                displayTiers(tiers);
            } catch (error) {
                console.error('Error loading tiers:', error);
            }
        }

        function displayTiers(tiers) {
            const container = document.getElementById('tiersList');
            container.innerHTML = '';

            tiers.forEach(tier => {
                const div = document.createElement('div');
                div.className = 'polaris-resource-item';
                div.style.borderWidth = '2px';
                div.style.borderColor = tier.color;
                div.innerHTML = `
                    <div style="text-align: center;">
                        <div style="width: 64px; height: 64px; border-radius: 50%; margin: 0 auto 12px; display: flex; align-items: center; justify-content: center; background-color: ${tier.color}20;">
                            <span style="font-size: 32px;">🏆</span>
                        </div>
                        <h3 class="polaris-heading" style="color: ${tier.color}; margin-bottom: 4px;">${tier.name}</h3>
                        <p class="polaris-label-secondary">${tier.min_points_required}+ points</p>
                        <div class="polaris-layout gap-1 mt-3">
                            <p class="polaris-label">${tier.points_multiplier / 100}x points</p>
                            <p class="polaris-label">${tier.discount_percentage}% discount</p>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
        }
        // --- Pricing Tiers Logic ---

        async function loadPricingTiers() {
            try {
                const response = await fetch(`${API_URL}/admin/pricing-tiers?shop=${SHOP_DOMAIN}`);
                const tiers = await response.json();
                displayPricingTiers(tiers);
            } catch (error) {
                console.error('Error loading tiers:', error);
                document.getElementById('tiersListContainer').innerHTML = '<p class="text-subdued">Failed to load tiers.</p>';
            }
        }

        function displayPricingTiers(tiers) {
            const container = document.getElementById('tiersListContainer');
            container.innerHTML = '';

            if (tiers.length === 0) {
                container.innerHTML = '<p class="text-subdued" style="text-align: center; padding: 20px;">No tiers found. Create one to get started.</p>';
                return;
            }

            tiers.forEach(tier => {
                const div = document.createElement('div');
                div.className = 'polaris-resource-item';
                div.style.cursor = 'pointer';
                div.onclick = (e) => {
                    // Prevent click if button was clicked
                    if (e.target.tagName === 'BUTTON') return;
                    openTierDetail(tier);
                };

                div.innerHTML = `
                    <div class="polaris-stack" style="justify-content: space-between; align-items: center;">
                        <div class="polaris-stack-item-fill">
                            <h3 class="polaris-heading">${tier.name}</h3>
                            <p class="text-subdued">${tier.description || 'No description'}</p>
                            <p class="polaris-label-secondary mt-2">ID: ${tier.id} • Click to manage</p>
                        </div>
                        <div class="polaris-stack-item">
                            <button onclick="deletePricingTier(${tier.id})" class="polaris-btn polaris-btn-destructive">Delete</button>
                        </div>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        function openCreateTierModal() {
            document.getElementById('newTierName').value = '';
            document.getElementById('newTierDesc').value = '';
            document.getElementById('createTierModal').classList.remove('hidden');
        }

        async function createTier() {
            const name = document.getElementById('newTierName').value;
            const desc = document.getElementById('newTierDesc').value;
            
            if (!name) { alert('Name is required'); return; }

            try {
                const response = await fetch(`${API_URL}/admin/pricing-tiers`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ shop: SHOP_DOMAIN, name, description: desc })
                });

                if (response.ok) {
                    document.getElementById('createTierModal').classList.add('hidden');
                    loadPricingTiers();
                } else {
                    alert('Failed to create tier');
                }
            } catch (error) { console.error(error); alert('Error creating tier'); }
        }

        async function deletePricingTier(id) {
            if (!confirm('Delete this tier? Prices and member assignments will be removed.')) return;
            try {
                await fetch(`${API_URL}/admin/pricing-tiers/${id}?shop=${SHOP_DOMAIN}`, { method: 'DELETE' });
                loadPricingTiers();
            } catch(e) { console.error(e); }
        }

        // --- Tier Detail Logic ---

        function openTierDetail(tier) {
            currentTierId = tier.id;
            document.getElementById('detailTierName').textContent = tier.name;
            document.getElementById('detailTierDesc').textContent = tier.description || '';
            
            document.getElementById('tiersListContainer').parentElement.classList.add('hidden'); // Hide list section
            document.getElementById('tierDetailView').classList.remove('hidden');

            loadTierPrices(tier.id);
            loadTierMembers(tier.id);
        }

        function closeTierDetail() {
            currentTierId = null;
            document.getElementById('tierDetailView').classList.add('hidden');
            document.getElementById('tiersListContainer').parentElement.classList.remove('hidden');
        }

        // Reuse product search logic but for tiers
        async function searchProductsForTier() {
            const query = document.getElementById('tierProductSearch').value;
             try {
                // Reuse existing search logic/endpoint but render differently
                const response = await fetch(`${API_URL}/admin/products/search?shop=${SHOP_DOMAIN}`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ shop: SHOP_DOMAIN, query: query })
                });

                const products = await response.json();
                displayTierProducts(products);
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to search products');
            }
        }

        function displayTierProducts(products) {
            const container = document.getElementById('tierProductResults');
            container.innerHTML = '';

            products.forEach(product => {
                product.variants.forEach(variant => {
                    const div = document.createElement('div');
                    div.className = 'polaris-resource-item';
                    
                    div.innerHTML = `
                         <div class="polaris-stack" style="justify-content: space-between; align-items: center;">
                            <div class="polaris-stack-item-fill">
                                <h4 class="polaris-label">${product.title}</h4>
                                <p class="polaris-label-secondary">${variant.title}</p>
                                <p class="polaris-heading mt-2">$${variant.price}</p>
                            </div>
                             <div class="polaris-stack-item">
                                <div class="polaris-stack">
                                    <div class="polaris-stack-item">
                                        <input type="number" id="tier-price-${variant.id}" class="polaris-text-field" placeholder="Tier Price" step="0.01" style="width: 100px;">
                                    </div>
                                    <div class="polaris-stack-item">
                                        <button class="polaris-btn" onclick="saveTierPrice(${product.id}, ${variant.id}, '${product.title.replace(/'/g, "\\'")}', '${variant.title.replace(/'/g, "\\'")}', ${variant.price})">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    container.appendChild(div);
                });
            });
        }

        async function saveTierPrice(prodId, varId, prodTitle, varTitle, origPrice) {
            if (!currentTierId) return;
            const price = document.getElementById(`tier-price-${varId}`).value;
            
            if (!price) { alert('Enter price'); return; }

            try {
                const response = await fetch(`${API_URL}/admin/custom-prices`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        shop: SHOP_DOMAIN,
                        pricing_tier_id: currentTierId, // Context: TIER
                        shopify_product_id: prodId,
                        shopify_variant_id: varId,
                        product_title: prodTitle,
                        variant_title: varTitle,
                        original_price: origPrice,
                        custom_price: parseFloat(price)
                    })
                });
                
                if (response.ok) {
                    alert('Saved!');
                    loadTierPrices(currentTierId);
                } else {
                    alert('Failed');
                }
            } catch(e) { console.error(e); }
        }

        async function loadTierPrices(tierId) {
             try {
                const response = await fetch(`${API_URL}/admin/custom-prices/tier/${tierId}?shop=${SHOP_DOMAIN}`);
                const data = await response.json();
                
                const container = document.getElementById('tierExistingPrices');
                container.innerHTML = '';
                
                 if (data.prices.length === 0) {
                    container.innerHTML = '<p class="text-subdued">No prices set for this tier.</p>';
                    return;
                }

                data.prices.forEach(price => {
                     const div = document.createElement('div');
                     div.className = 'polaris-resource-item';
                     div.innerHTML = `
                        <div class="polaris-stack" style="justify-content: space-between;">
                            <div>
                                <b>${price.product_title}</b> (${price.variant_title})<br>
                                <span class="text-subdued">Orig: $${price.original_price}</span> -> <span style="color:var(--p-success)">$${price.custom_price}</span>
                            </div>
                            <button onclick="deletePrice(${price.id}, true)" class="polaris-btn polaris-btn-destructive polaris-btn-sm">Remove</button>
                        </div>
                     `;
                     container.appendChild(div);
                });

            } catch (error) {
                console.error('Error loading tier prices:', error);
            }
        }
        // --- Tier Member Logic ---

        async function searchCustomerForTier() {
            const email = document.getElementById('tierCustomerSearch').value;
            if (!email) { alert('Enter email'); return; }

            try {
                const response = await fetch(`${API_URL}/admin/customers/search`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ email, shop: SHOP_DOMAIN })
                });
                
                const data = await response.json();
                
                // searchCustomer returns { customer: ..., pricing_setting: ... }
                // We wrap it in an array for display function
                const results = data.customer ? [data.customer] : [];
                displayTierCustomerSearchResults(results);
            } catch (error) {
                console.error('Error:', error);
                alert('Search failed');
            }
        }

        function displayTierCustomerSearchResults(customers) {
            const container = document.getElementById('tierCustomerSearchResults');
            container.innerHTML = '';

            if (!customers || customers.length === 0) {
                container.innerHTML = '<p class="text-subdued">No customers found.</p>';
                return;
            }

            customers.forEach(bg => {
                // Background customers usually don't have first/last name if not set? 
                // formattedCustomers has id, email, first_name, last_name
                const customer = bg; 
                
                const div = document.createElement('div');
                div.className = 'polaris-resource-item';
                div.style.marginBottom = '10px';
                div.innerHTML = `
                    <div class="polaris-stack" style="justify-content: space-between; align-items: center;">
                        <div>
                            <p class="polaris-heading">${customer.first_name} ${customer.last_name}</p>
                            <p class="text-subdued">${customer.email}</p>
                        </div>
                        <button onclick="addCustomerToTier('${customer.id}', '${customer.email}')" class="polaris-btn polaris-btn-primary">Add</button>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        async function addCustomerToTier(shopifyIdStr, email) {
            if (!currentTierId) return;
            
            // Extract numeric ID from GID if needed
            // shopifyIdStr ex: "gid://shopify/Customer/12345"
            let customerId = shopifyIdStr;
            if (typeof shopifyIdStr === 'string' && shopifyIdStr.includes('/')) {
                customerId = shopifyIdStr.split('/').pop();
            }

            try {
                const response = await fetch(`${API_URL}/admin/customers/assign-tier`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        shop: SHOP_DOMAIN,
                        pricing_tier_id: currentTierId,
                        shopify_customer_id: customerId,
                        email: email
                    })
                });

                if (response.ok) {
                    alert('Customer added to tier');
                    document.getElementById('tierCustomerSearch').value = '';
                    document.getElementById('tierCustomerSearchResults').innerHTML = '';
                    loadTierMembers(currentTierId);
                } else {
                    const d = await response.json();
                    alert(d.message || 'Failed to add customer');
                }
            } catch(e) { console.error(e); }
        }

        async function loadTierMembers(tierId) {
            try {
                // Endpoint to get members. We will implement this next.
                const response = await fetch(`${API_URL}/admin/pricing-tiers/${tierId}/members?shop=${SHOP_DOMAIN}`);
                const members = await response.json();
                displayTierMembers(members);
            } catch (e) {
                console.error('Error loading members:', e);
            }
        }

        function displayTierMembers(members) {
            const container = document.getElementById('tierMembersList');
            container.innerHTML = '';

            if (!members || members.length === 0) {
                container.innerHTML = '<p class="text-subdued">No members in this tier.</p>';
                return;
            }

            members.forEach(member => {
                const div = document.createElement('div');
                div.className = 'polaris-resource-item';
                div.innerHTML = `
                    <div class="polaris-stack" style="justify-content: space-between; align-items: center;">
                        <div>
                            <p class="polaris-heading">${member.customer_email}</p>
                            <p class="text-subdued">ID: ${member.shopify_customer_id}</p>
                        </div>
                        <button onclick="removeTierMember(${member.id})" class="polaris-btn polaris-btn-destructive polaris-btn-sm">Remove</button>
                    </div>
                `;
                container.appendChild(div);
            });
        }

        async function removeTierMember(settingId) {
            if (!confirm('Remove customer from this tier?')) return;
            try {
                const response = await fetch(`${API_URL}/admin/customers/remove-tier`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_pricing_setting_id: settingId,
                        shop: SHOP_DOMAIN // Although not strictly required by endpoint currently, good practice
                    })
                });
                
                if (response.ok) {
                    loadTierMembers(currentTierId);
                } else {
                    alert('Failed to remove member');
                }
            } catch(e) { console.error(e); }
        }
    </script>
</body>
</html>
