(function() {
  'use strict';
  
  console.log('🎨 Custom Pricing Script Loaded');

  // Get customer ID from meta tag
  const customerMeta = document.querySelector('meta[name="customer-id"]');
  if (!customerMeta) {
    console.log('⚠️ No customer logged in');
    return;
  }

  const customerId = customerMeta.content;
  console.log('👤 Customer ID:', customerId);

  // Check if we have a variant ID on the page
  const variantInput = document.querySelector('input[name="id"], select[name="id"]');
  if (!variantInput) {
    console.log('⚠️ Not a product page - no variant selector found');
    return;
  }

  const CONFIG = {
    apiUrl: 'https://customprice.metora.in/api/storefront/custom-price',
    shop: window.Shopify.shop,
    currency: window.Shopify.currency.active
  };

  console.log('⚙️ Config:', CONFIG);

  let currentVariantId = variantInput.value || variantInput.options?.[variantInput.selectedIndex]?.value;
  console.log('🏷️ Initial variant ID:', currentVariantId);

  // Create and inject styles
  const styles = document.createElement('style');
  styles.textContent = `
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
  `;
  document.head.appendChild(styles);

  // Create container
  const container = document.createElement('div');
  container.id = 'metora-custom-price-container';
  container.className = 'custom-price-container';
  container.innerHTML = '<div style="padding: 12px; text-align: center; opacity: 0.8;">Checking for your special price...</div>';
  
  // Find where to inject (look for price elements)
  const priceElement = document.querySelector('.product__price, .price, [data-price], .product-price, .price__container');
  if (priceElement) {
    priceElement.parentNode.insertBefore(container, priceElement.nextSibling);
    console.log('✅ Container injected successfully');
  } else {
    console.error('❌ Could not find price element to inject container');
    return;
  }

  async function checkCustomPrice(variantId) {
    if (!variantId) {
      console.warn('⚠️ No variant ID provided');
      return;
    }

    console.log('🔍 Checking custom price for variant:', variantId);
    
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

      console.log('📡 API Response status:', response.status);

      if (!response.ok) {
        console.warn('⚠️ API request failed');
        hideCustomPrice();
        return;
      }

      const data = await response.json();
      console.log('📦 API Response:', data);

      if (data.has_custom_price) {
        console.log('🎉 Custom price found! Displaying...');
        displayCustomPrice(data);
      } else {
        console.log('ℹ️ No custom price for this product');
        hideCustomPrice();
      }
    } catch (error) {
      console.error('❌ Error:', error);
      hideCustomPrice();
    }
  }

  function displayCustomPrice(data) {
    const discount = Math.round(((data.original_price - data.custom_price) / data.original_price) * 100);
    const currencySymbol = CONFIG.currency === 'USD' ? '$' : CONFIG.currency;
    
    container.innerHTML = `
      <div class="custom-price-header">🎉 Your Exclusive Price</div>
      <div class="custom-price-main">
        <span class="custom-price-value">${formatMoney(data.custom_price, currencySymbol)}</span>
        <span class="custom-price-original">${formatMoney(data.original_price, currencySymbol)}</span>
        <span class="custom-price-badge">${discount}% OFF</span>
      </div>
    `;
    
    container.classList.add('active');
    console.log('✅ Custom price displayed');
  }

  function hideCustomPrice() {
    container.classList.remove('active');
  }

  function formatMoney(cents, symbol) {
    const amount = (cents / 100).toFixed(2);
    return symbol + amount;
  }

  // Watch for variant changes
  variantInput.addEventListener('change', function() {
    currentVariantId = this.value || this.options?.[this.selectedIndex]?.value;
    console.log('🔄 Variant changed to:', currentVariantId);
    checkCustomPrice(currentVariantId);
  });

  // Also watch for option selectors
  const optionSelectors = document.querySelectorAll('select[data-index^="option"], input[type="radio"][name*="option"]');
  optionSelectors.forEach(selector => {
    selector.addEventListener('change', function() {
      setTimeout(() => {
        const newVariantId = variantInput.value || variantInput.options?.[variantInput.selectedIndex]?.value;
        if (newVariantId && newVariantId !== currentVariantId) {
          currentVariantId = newVariantId;
          console.log('🔄 Variant changed to:', currentVariantId);
          checkCustomPrice(currentVariantId);
        }
      }, 100);
    });
  });

  // Initial check
  checkCustomPrice(currentVariantId);

  console.log('✨ Custom Pricing initialized successfully');

})();