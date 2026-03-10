(function() {
    console.log('=== LOYALTY TEST SCRIPT START ===');
    
    // Test 1: Check if script loads
    console.log('✅ Step 1: Script loaded successfully');
    
    // Test 2: Check customer detection
    console.log('📋 Step 2: Testing customer detection...');
    console.log('  - window.__st:', window.__st);
    console.log('  - window.__st.cid:', window.__st?.cid);
    console.log('  - window.Shopify:', window.Shopify);
    
    const customerId = window.__st?.cid;
    if (!customerId) {
        console.log('❌ Step 2 FAILED: No customer ID found');
        return;
    }
    console.log('✅ Step 2: Customer ID found:', customerId);
    
    // Test 3: Check API connectivity
    console.log('📋 Step 3: Testing API...');
    const shopDomain = window.Shopify?.shop || 'my-custom-pricing-store.myshopify.com';
    
    fetch('{{ env("APP_URL") }}/api/storefront/loyalty', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            customer_id: customerId,
            shop: shopDomain
        })
    })
    .then(response => {
        console.log('  - API Response Status:', response.status);
        return response.json();
    })
    .then(data => {
        console.log('✅ Step 3: API working!', data);
        console.log('  - Points Balance:', data.points_balance);
        console.log('  - Tier:', data.tier?.name);
        
        // Test 4: Create simple widget
        console.log('📋 Step 4: Creating test widget...');
        createTestWidget(data);
    })
    .catch(error => {
        console.log('❌ Step 3 FAILED: API Error:', error);
    });
    
    // Test 4: Create a simple visible widget
    function createTestWidget(loyaltyData) {
        // Remove existing test widget
        const existing = document.getElementById('loyalty-test-widget');
        if (existing) existing.remove();
        
        // Create widget
        const widget = document.createElement('div');
        widget.id = 'loyalty-test-widget';
        widget.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.3);
            z-index: 999999;
            font-family: Arial, sans-serif;
            min-width: 200px;
        `;
        
        widget.innerHTML = `
            <div style="font-size: 18px; font-weight: bold; margin-bottom: 10px;">
                ⭐ Loyalty Points
            </div>
            <div style="font-size: 32px; font-weight: bold; margin-bottom: 5px;">
                ${loyaltyData.points_balance || 0}
            </div>
            <div style="font-size: 14px; opacity: 0.9; margin-bottom: 10px;">
                ${loyaltyData.tier?.name || 'Bronze'} Member
            </div>
            <div style="font-size: 12px; opacity: 0.8;">
                = $${((loyaltyData.points_balance || 0) * (loyaltyData.points_value || 10) / 100).toFixed(2)}
            </div>
        `;
        
        // Insert into page
        document.body.appendChild(widget);
        console.log('✅ Step 4: Test widget created and inserted!');
        console.log('  - Widget element:', widget);
        console.log('  - Widget visible:', widget.offsetHeight > 0);
    }
    
    console.log('=== LOYALTY TEST SCRIPT END ===');
})();
