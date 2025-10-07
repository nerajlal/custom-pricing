

(function() {
  'use strict';
  
  // Check if we're on a product page
  if (!window.ShopifyAnalytics || !window.ShopifyAnalytics.meta.product) {
    return;
  }

  // Check if customer is logged in
  const isCustomerLoggedIn = document.body.classList.contains('customer-logged-in') || 
                            document.querySelector('[data-customer-id]');
  
  if (!isCustomerLoggedIn) {
    return;
  }

  const CONFIG = {
    apiUrl: 'https://customprice.metora.in/api/storefront/custom-price',
    shop: window.Shopify.shop,
    currency: window.Shopify.currency.active
  };

  // Get customer ID from Shopify's customer data
  function getCustomerId() {
    // Method 1: From Shopify Analytics
    if (window.ShopifyAnalytics && window.ShopifyAnalytics.meta && window.ShopifyAnalytics.meta.page) {
      const customerId = window.ShopifyAnalytics.meta.page.customerId;
      if (customerId) return customerId;
    }

    // Method 2: From meta tag
    const metaTag = document.querySelector('meta[name="customer-id"]');
    if (metaTag) return metaTag.content;
    
    // Method 3: From data attribute
    const dataAttr = document.querySelector('[data-customer-id]');
    if (dataAttr) return dataAttr.dataset.customerId;

    // Method 4: Try to extract from page HTML (last resort)
    const scripts = document.querySelectorAll('script');
    for (let script of scripts) {
      const match = script.textContent.match(/customer_id['":\s]+(\d+)/i);
      if (match) return match[1];
    }
    
    return null;
  }

  const customerId = getCustomerId();
  console.log('Custom Pricing: Customer ID =', customerId);
  
  if (!customerId) {
    console.log('Custom Pricing: No customer logged in');
    return;
  }

  // Create and inject styles
  const styles = `
    <style>
      .custom-price-container {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        padding: 16px;
        border-radius: 12px;
        margin: 16px 0;
        animation: customPriceSlideIn 0.4s ease-out;
        box-shadow: 0 4px 6px rgba(16, 185, 129, 0.2);
        display: none;
      }
      
      .custom-price-container.active {
        display: block;
      }
      
      .custom-price-header {
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 8px;
        opacity: 0.95;
      }
      
      .custom-price-main {
        display: flex;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
      }
      
      .custom-price-value {
        font-size: 32px;
        font-weight: bold;
        letter-spacing: -0.5px;
      }
      
      .custom-price-original {
        text-decoration: line-through;
        opacity: 0.8;
        font-size: 18px;
      }
      
      .custom-price-badge {
        background: rgba(255, 255, 255, 0.25);
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 13px;
        font-weight: 700;
      }
      
      @keyframes customPriceSlideIn {
        from {
          opacity: 0;
          transform: translateY(-10px);
        }
        to {
          opacity: 1;
          transform: translateY(0);
        }
      }
    </style>
  `;
  
  // Inject styles
  document.head.insertAdjacentHTML('beforeend', styles);

  // Create container and inject it
  function injectContainer() {
    const priceElement = document.querySelector('.product__price, .price, [data-price], .product-price');
    if (!priceElement) return null;

    const container = document.createElement('div');
    container.id = 'metora-custom-price-container';
    container.className = 'custom-price-container';
    container.innerHTML = '<div style="padding: 12px; text-align: center; opacity: 0.8;">Checking for your special price...</div>';
    
    priceElement.parentNode.insertBefore(container, priceElement.nextSibling);
    return container;
  }

  const container = injectContainer();
  if (!container) return;

  let currentVariantId = window.ShopifyAnalytics.meta.product.variants[0].id;

  async function checkCustomPrice(variantId) {
    try {
      const response = await fetch(CONFIG.apiUrl, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          customer_id: parseInt(customerId),
          variant_id: parseInt(variantId),
          shop: CONFIG.shop,
          currency: CONFIG.currency
        })
      });

      if (!response.ok) {
        hideCustomPrice();
        return;
      }

      const data = await response.json();

      if (data.has_custom_price) {
        displayCustomPrice(data);
      } else {
        hideCustomPrice();
      }
    } catch (error) {
      console.error('Custom pricing error:', error);
      hideCustomPrice();
    }
  }

  function displayCustomPrice(data) {
    const discount = Math.round(((data.original_price - data.custom_price) / data.original_price) * 100);
    const currencySymbol = window.Shopify.currency.active === 'USD' ? '$' : window.Shopify.currency.active;
    
    container.innerHTML = `
      <div class="custom-price-header">🎉 Your Exclusive Price</div>
      <div class="custom-price-main">
        <span class="custom-price-value">${formatMoney(data.custom_price, currencySymbol)}</span>
        <span class="custom-price-original">${formatMoney(data.original_price, currencySymbol)}</span>
        <span class="custom-price-badge">${discount}% OFF</span>
      </div>
    `;
    
    container.classList.add('active');
  }

  function hideCustomPrice() {
    container.classList.remove('active');
  }

  function formatMoney(cents, symbol) {
    const amount = (cents / 100).toFixed(2);
    return symbol + amount;
  }

  // Watch for variant changes
  document.addEventListener('variant:change', function(e) {
    if (e.detail && e.detail.variant) {
      currentVariantId = e.detail.variant.id;
      checkCustomPrice(currentVariantId);
    }
  });

  // Initialize
  checkCustomPrice(currentVariantId);

})();