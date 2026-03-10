@if(isset($customer))
<div id="loyalty-widget" style="position: fixed; bottom: 20px; right: 20px; z-index: 9999;">
  <div id="loyalty-collapsed" onclick="toggleLoyaltyWidget()" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 16px 20px; border-radius: 50px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.15); display: flex; align-items: center; gap: 10px;">
    <span style="font-size: 24px;">⭐</span>
    <div>
      <div style="font-size: 12px; opacity: 0.9;">Your Points</div>
      <div style="font-size: 20px; font-weight: bold;" id="widget-points">--</div>
    </div>
  </div>

  <div id="loyalty-expanded" style="display: none; background: white; border-radius: 16px; box-shadow: 0 8px 32px rgba(0,0,0,0.2); width: 320px; overflow: hidden;">
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; position: relative;">
      <button onclick="toggleLoyaltyWidget()" style="position: absolute; top: 10px; right: 10px; background: rgba(255,255,255,0.2); border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 18px;">×</button>
      <div style="text-align: center;">
        <div style="font-size: 14px; opacity: 0.9; margin-bottom: 8px;">Loyalty Points</div>
        <div style="font-size: 36px; font-weight: bold;" id="expanded-points">0</div>
        <div style="font-size: 12px; opacity: 0.8; margin-top: 4px;" id="points-value">= $0.00</div>
      </div>
    </div>

    <div style="padding: 20px;">
      <div id="tier-badge" style="text-align: center; margin-bottom: 20px;">
        <div style="display: inline-block; background: #f3f4f6; padding: 8px 16px; border-radius: 20px;">
          <span style="font-size: 16px; margin-right: 6px;">🏆</span>
          <span style="font-weight: 600; color: #1f2937;" id="tier-name">Bronze</span>
        </div>
        <div style="font-size: 12px; color: #6b7280; margin-top: 4px;" id="tier-benefit">1x points earned</div>
      </div>

      <div style="background: #f9fafb; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
        <div style="display: flex; justify-content: space-between; align-items: center;">
          <span style="font-size: 13px; color: #6b7280;">Earn per $1 spent</span>
          <span style="font-weight: 600; color: #1f2937;" id="earn-rate">10 pts</span>
        </div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
          <span style="font-size: 13px; color: #6b7280;">100 points value</span>
          <span style="font-weight: 600; color: #10b981;">$0.10</span>
        </div>
      </div>

      <div style="text-align: center; padding: 12px; background: #eff6ff; border-radius: 8px;">
        <div style="font-size: 12px; color: #1e40af; margin-bottom: 4px;">💡 Keep shopping to earn more points!</div>
        <div style="font-size: 11px; color: #3b82f6;">Contact us to redeem your points</div>
      </div>
    </div>
  </div>
</div>

<script>
(function() {
  const API_URL = '{{ env("APP_URL") }}/api/storefront/loyalty';
  const SHOP_DOMAIN = '{{ $shop_domain ?? "neraj-test-store.myshopify.com" }}';
  let loyaltyData = null;

  async function loadLoyaltyData() {
    const customerId = {{ $customer->id ?? 'null' }};
    
    if (!customerId) return;

    try {
      const response = await fetch(API_URL, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          customer_id: customerId,
          shop: SHOP_DOMAIN
        })
      });

      const data = await response.json();

      if (data.has_loyalty) {
        loyaltyData = data;
        updateWidget(data);
      } else {
        document.getElementById('loyalty-widget').style.display = 'none';
      }
    } catch (error) {
      console.error('Error loading loyalty data:', error);
      document.getElementById('loyalty-widget').style.display = 'none';
    }
  }

  function updateWidget(data) {
    document.getElementById('widget-points').textContent = data.points_balance;
    document.getElementById('expanded-points').textContent = data.points_balance;
    document.getElementById('points-value').textContent = '= $' + data.points_value.toFixed(2);

    if (data.tier) {
      document.getElementById('tier-name').textContent = data.tier.name;
      document.getElementById('tier-benefit').textContent = (data.tier.points_multiplier / 100) + 'x points, ' + data.tier.discount_percentage + '% discount';
    }
  }

  window.toggleLoyaltyWidget = function() {
    const collapsed = document.getElementById('loyalty-collapsed');
    const expanded = document.getElementById('loyalty-expanded');

    if (collapsed.style.display === 'none') {
      collapsed.style.display = 'flex';
      expanded.style.display = 'none';
    } else {
      collapsed.style.display = 'none';
      expanded.style.display = 'block';
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', loadLoyaltyData);
  } else {
    loadLoyaltyData();
  }
})();
</script>

<style>
#loyalty-widget * {
  box-sizing: border-box;
}

#loyalty-collapsed:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0,0,0,0.2);
  transition: all 0.2s ease;
}
</style>
@endif