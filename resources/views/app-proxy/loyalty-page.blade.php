<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loyalty Rewards - {{ config('app.name') }}</title>
    
    <!-- Load store's theme CSS -->
    <link rel="stylesheet" href="{{ request()->getSchemeAndHttpHost() }}/cdn/shop/t/*/assets/base.css">
    
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f9fafb;
            color: #202223;
        }
        
        .loyalty-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .page-header {
            text-align: center;
            margin-bottom: 48px;
        }
        
        .page-title {
            font-size: 32px;
            font-weight: 600;
            color: #202223;
            margin: 0 0 12px 0;
        }
        
        .page-subtitle {
            font-size: 16px;
            color: #6d7175;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            border: 1px solid #e1e3e5;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        
        .stat-label {
            font-size: 13px;
            font-weight: 500;
            color: #6d7175;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 36px;
            font-weight: 600;
            color: #202223;
            line-height: 1;
        }
        
        .stat-subtext {
            font-size: 13px;
            color: #6d7175;
            margin-top: 4px;
        }
        
        .card {
            background: white;
            border: 1px solid #e1e3e5;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        
        .card-title {
            font-size: 18px;
            font-weight: 600;
            color: #202223;
            margin: 0 0 20px 0;
        }
        
        .tier-badge {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 16px 24px;
            background: #f6f6f7;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .tier-icon {
            font-size: 32px;
        }
        
        .tier-name {
            font-size: 24px;
            font-weight: 600;
            color: #202223;
        }
        
        .tier-benefits {
            font-size: 14px;
            color: #6d7175;
        }
        
        .progress-section {
            margin-top: 24px;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            color: #6d7175;
            margin-bottom: 8px;
        }
        
        .progress-bar-container {
            width: 100%;
            height: 8px;
            background: #f6f6f7;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: #008060;
            border-radius: 4px;
            transition: width 0.5s ease;
        }
        
        .filter-tabs {
            display: flex;
            gap: 8px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 8px 16px;
            background: white;
            border: 1px solid #e1e3e5;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            color: #202223;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .filter-tab:hover {
            background: #f6f6f7;
        }
        
        .filter-tab.active {
            background: #008060;
            color: white;
            border-color: #008060;
        }
        
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            padding: 16px;
            border: 1px solid #e1e3e5;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        
        .transaction-icon {
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
        
        .transaction-details {
            flex: 1;
            margin-left: 16px;
        }
        
        .transaction-title {
            font-size: 14px;
            font-weight: 500;
            color: #202223;
            margin-bottom: 4px;
        }
        
        .transaction-date {
            font-size: 13px;
            color: #6d7175;
        }
        
        .transaction-points {
            text-align: right;
        }
        
        .points-value {
            font-size: 20px;
            font-weight: 600;
        }
        
        .points-value.positive {
            color: #008060;
        }
        
        .points-value.negative {
            color: #bf0711;
        }
        
        .balance-after {
            font-size: 12px;
            color: #6d7175;
            margin-top: 4px;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6d7175;
        }
        
        .empty-icon {
            font-size: 48px;
            margin-bottom: 16px;
            opacity: 0.5;
        }
        
        .sidebar-section {
            background: white;
            border: 1px solid #e1e3e5;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 24px;
        }
        
        .sidebar-title {
            font-size: 16px;
            font-weight: 600;
            color: #202223;
            margin: 0 0 16px 0;
        }
        
        .earn-item {
            display: flex;
            gap: 12px;
            margin-bottom: 16px;
        }
        
        .earn-icon {
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
        
        .earn-text {
            flex: 1;
        }
        
        .earn-title {
            font-size: 14px;
            font-weight: 500;
            color: #202223;
            margin-bottom: 4px;
        }
        
        .earn-description {
            font-size: 13px;
            color: #6d7175;
        }
        
        .tier-item {
            padding: 12px;
            border: 1px solid #e1e3e5;
            border-radius: 8px;
            margin-bottom: 12px;
        }
        
        .tier-item.current {
            border-color: #008060;
            background: #f6fff8;
        }
        
        .tier-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }
        
        .tier-info {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .tier-small-icon {
            font-size: 20px;
        }
        
        .tier-item-name {
            font-size: 14px;
            font-weight: 600;
            color: #202223;
        }
        
        .tier-points {
            font-size: 12px;
            color: #6d7175;
        }
        
        .tier-benefits-small {
            font-size: 12px;
            color: #6d7175;
        }
        
        .current-badge {
            font-size: 11px;
            font-weight: 600;
            color: #008060;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .button {
            display: inline-block;
            padding: 12px 24px;
            background: #008060;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-align: center;
            transition: background 0.2s;
        }
        
        .button:hover {
            background: #006e52;
        }
        
        .button-secondary {
            background: white;
            color: #202223;
            border: 1px solid #e1e3e5;
        }
        
        .button-secondary:hover {
            background: #f6f6f7;
        }
        
        @media (max-width: 768px) {
            .loyalty-container {
                padding: 24px 16px;
            }
            
            .page-title {
                font-size: 24px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr 1fr;
                gap: 12px;
            }
            
            .stat-value {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- This will load the store's header -->
    <div id="shopify-section-header"></div>
    
    <div class="loyalty-container">
        
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">🏆 Loyalty Rewards</h1>
            <p class="page-subtitle">Track your points and unlock exclusive benefits</p>
        </div>

        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-label">Current Balance</div>
                <div class="stat-value" id="current-balance">--</div>
                <div class="stat-subtext">points</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Total Earned</div>
                <div class="stat-value" id="total-earned">--</div>
                <div class="stat-subtext">lifetime</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Total Redeemed</div>
                <div class="stat-value" id="total-redeemed">--</div>
                <div class="stat-subtext">used</div>
            </div>

            <div class="stat-card">
                <div class="stat-label">Points Value</div>
                <div class="stat-value" id="points-value" style="color: #008060;">$0.00</div>
                <div class="stat-subtext">cash value</div>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 320px; gap: 24px;">
            
            <!-- Main Content -->
            <div>
                
                <!-- Tier Status -->
                <div class="card">
                    <h2 class="card-title">Your Tier Status</h2>
                    
                    <div class="tier-badge">
                        <span class="tier-icon" id="tier-icon">🏆</span>
                        <div>
                            <div class="tier-name" id="tier-name">Bronze</div>
                            <div class="tier-benefits" id="tier-benefits">1x points earned</div>
                        </div>
                    </div>

                    <div class="progress-section" id="tier-progress">
                        <div class="progress-label">
                            <span>Progress to <strong id="next-tier-name">Silver</strong></span>
                            <span id="progress-text">0 / 500 points</span>
                        </div>
                        <div class="progress-bar-container">
                            <div class="progress-bar" id="progress-bar" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <!-- Transaction History -->
                <div class="card">
                    <h2 class="card-title">Transaction History</h2>
                    
                    <div class="filter-tabs">
                        <button class="filter-tab active" onclick="filterTransactions('all')">All</button>
                        <button class="filter-tab" onclick="filterTransactions('earn')">Earned</button>
                        <button class="filter-tab" onclick="filterTransactions('redeem')">Redeemed</button>
                        <button class="filter-tab" onclick="filterTransactions('bonus')">Bonuses</button>
                    </div>

                    <div id="transactions-list">
                        <div class="empty-state">
                            <div class="empty-icon">📜</div>
                            <div>Loading transactions...</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar -->
            <div>
                
                <!-- How to Earn -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">How to Earn Points</h3>
                    
                    <div class="earn-item">
                        <div class="earn-icon">🛍️</div>
                        <div class="earn-text">
                            <div class="earn-title">Make a Purchase</div>
                            <div class="earn-description"><strong id="points-rate">10 points</strong> for every $1 spent</div>
                        </div>
                    </div>

                    <!--<div class="earn-item">-->
                    <!--    <div class="earn-icon">🎂</div>-->
                    <!--    <div class="earn-text">-->
                    <!--        <div class="earn-title">Birthday Bonus</div>-->
                    <!--        <div class="earn-description">Special reward on your birthday</div>-->
                    <!--    </div>-->
                    <!--</div>-->

                    <div class="earn-item">
                        <div class="earn-icon">⭐</div>
                        <div class="earn-text">
                            <div class="earn-title">Tier Multipliers</div>
                            <div class="earn-description">Earn more as you level up</div>
                        </div>
                    </div>
                </div>

                <!-- All Tiers -->
                <div class="sidebar-section">
                    <h3 class="sidebar-title">Loyalty Tiers</h3>
                    <div id="tiers-list"></div>
                </div>

                <!-- Redeem CTA -->
                <div class="sidebar-section" style="text-align: center;">
                    <h3 class="sidebar-title">Redeem Your Points</h3>
                    <p style="font-size: 14px; color: #6d7175; margin-bottom: 16px;">Contact us to use your points for discounts</p>
                    <a href="/pages/contact" class="button">Contact Store</a>
                </div>
            </div>
        </div>
    </div>

    <!-- This will load the store's footer -->
    <div id="shopify-section-footer"></div>

    <script>
        const API_URL = '{{ env("APP_URL") }}/api';
        const SHOP_DOMAIN = (function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.get('shop')) return urlParams.get('shop');
            if (window.Shopify && window.Shopify.shop) return window.Shopify.shop;
            return '{{ $shop_domain ?? "store.myshopify.com" }}';
        })();

        let customerId = null;
        let allTransactions = [];
        let loyaltyData = null;

        function getCustomerId() {
            if (window.SHOPIFY_CUSTOMER_ID) return window.SHOPIFY_CUSTOMER_ID;
            if (window.Shopify && window.Shopify.customer) return window.Shopify.customer.id;
            
            const urlParams = new URLSearchParams(window.location.search);
            const id = urlParams.get('customer_id');
            return id ? parseInt(id) : null;
        }

        customerId = getCustomerId();

        if (!customerId) {
            showLoginRequired();
        } else {
            loadAllData();
        }

        async function loadAllData() {
            try {
                await loadLoyaltyData();
                await loadTransactions();
                await loadTiers();
            } catch (error) {
                console.error('Error loading data:', error);
            }
        }

        async function loadLoyaltyData() {
            try {
                const response = await fetch(`${API_URL}/storefront/loyalty`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_id: customerId,
                        shop: SHOP_DOMAIN
                    })
                });

                const data = await response.json();

                if (!data.has_loyalty) {
                    showNoLoyalty();
                    return;
                }

                loyaltyData = data;
                updateDashboard(data);
            } catch (error) {
                console.error('Error:', error);
            }
        }

        async function loadTransactions() {
            try {
                const response = await fetch(`${API_URL}/storefront/loyalty/transactions`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        customer_id: customerId,
                        shop: SHOP_DOMAIN
                    })
                });

                const data = await response.json();
                allTransactions = data.transactions || [];
                displayTransactions(allTransactions);
            } catch (error) {
                console.error('Error loading transactions:', error);
                displayTransactions([]);
            }
        }

        async function loadTiers() {
            try {
                const response = await fetch(`${API_URL}/storefront/loyalty/tiers`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ shop: SHOP_DOMAIN })
                });

                const data = await response.json();
                const tiers = data.tiers || [];
                displayTiers(tiers);
                updateProgress(tiers);
            } catch (error) {
                console.error('Error loading tiers:', error);
            }
        }

        function updateDashboard(data) {
            document.getElementById('current-balance').textContent = (data.points_balance || 0).toLocaleString();
            document.getElementById('total-earned').textContent = (data.total_earned || data.points_balance || 0).toLocaleString();
            document.getElementById('total-redeemed').textContent = (data.points_redeemed || 0).toLocaleString();
            document.getElementById('points-value').textContent = '$' + (data.points_value || 0).toFixed(2);

            if (data.tier) {
                document.getElementById('tier-name').textContent = data.tier.name;
                const mult = (data.tier.points_multiplier / 100).toFixed(1);
                document.getElementById('tier-benefits').textContent = `${mult}x points • ${data.tier.discount_percentage}% discount`;
            }
        }

        function displayTransactions(transactions) {
            const container = document.getElementById('transactions-list');
            
            if (transactions.length === 0) {
                container.innerHTML = '<div class="empty-state"><div class="empty-icon">📭</div><div>No transactions yet</div><div style="font-size: 13px; margin-top: 8px;">Points will appear here as you earn them</div></div>';
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
                    <div class="transaction-item" data-type="${txn.type}">
                        <div class="transaction-icon">${icons[txn.type] || '💰'}</div>
                        <div class="transaction-details">
                            <div class="transaction-title">${txn.description}</div>
                            <div class="transaction-date">${date}</div>
                        </div>
                        <div class="transaction-points">
                            <div class="points-value ${isPositive ? 'positive' : 'negative'}">
                                ${isPositive ? '+' : ''}${txn.points}
                            </div>
                            <div class="balance-after">Balance: ${txn.balance_after}</div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function filterTransactions(type) {
            document.querySelectorAll('.filter-tab').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');

            const filtered = type === 'all' ? allTransactions : allTransactions.filter(t => t.type === type);
            displayTransactions(filtered);
        }

        function displayTiers(tiers) {
            const container = document.getElementById('tiers-list');
            
            container.innerHTML = tiers.map(tier => {
                const isCurrent = loyaltyData && loyaltyData.tier && loyaltyData.tier.name === tier.name;
                return `
                    <div class="tier-item ${isCurrent ? 'current' : ''}">
                        <div class="tier-header">
                            <div class="tier-info">
                                <span class="tier-small-icon">🏆</span>
                                <div>
                                    <div class="tier-item-name">${tier.name}</div>
                                    <div class="tier-points">${tier.min_points_required}+ points</div>
                                </div>
                            </div>
                            ${isCurrent ? '<div class="current-badge">Current</div>' : ''}
                        </div>
                        <div class="tier-benefits-small">
                            ${(tier.points_multiplier / 100).toFixed(1)}x points • ${tier.discount_percentage}% discount
                        </div>
                    </div>
                `;
            }).join('');
        }

        function updateProgress(tiers) {
            if (!loyaltyData || !loyaltyData.tier) return;

            const currentIndex = tiers.findIndex(t => t.name === loyaltyData.tier.name);
            if (currentIndex === -1 || currentIndex === tiers.length - 1) {
                document.getElementById('tier-progress').innerHTML = '<div style="text-align: center; padding: 16px; color: #008060; font-weight: 500;">🎉 You\'ve reached the highest tier!</div>';
                return;
            }

            const nextTier = tiers[currentIndex + 1];
            const current = loyaltyData.total_earned || loyaltyData.points_balance || 0;
            const progress = Math.min((current / nextTier.min_points_required) * 100, 100);

            document.getElementById('next-tier-name').textContent = nextTier.name;
            document.getElementById('progress-text').textContent = `${current} / ${nextTier.min_points_required} points`;
            document.getElementById('progress-bar').style.width = progress + '%';
        }

        function showLoginRequired() {
            document.body.innerHTML = '<div style="min-height: 60vh; display: flex; align-items: center; justify-content: center;"><div style="text-align: center;"><div style="font-size: 60px; margin-bottom: 16px;">🔒</div><h1 style="font-size: 24px; margin-bottom: 12px;">Please Log In</h1><p style="color: #6d7175; margin-bottom: 24px;">View your loyalty points after logging in</p><a href="/account/login" class="button">Log In</a></div></div>';
        }

        function showNoLoyalty() {
            document.body.innerHTML = '<div style="min-height: 60vh; display: flex; align-items: center; justify-content: center;"><div style="text-align: center;"><div style="font-size: 60px; margin-bottom: 16px;">⭐</div><h1 style="font-size: 24px; margin-bottom: 12px;">Join Loyalty Program</h1><p style="color: #6d7175; margin-bottom: 24px;">Start earning points with every purchase</p><a href="/" class="button">Continue Shopping</a></div></div>';
        }
    </script>
</body>
</html>