(function() {
  'use strict';
  
  console.log('🎨 Custom Pricing Script Loaded');

  const customerMeta = document.querySelector('meta[name="customer-id"]');
  if (!customerMeta) {
    console.log('⚠️ No customer logged in');
    return;
  }

  const customerId = customerMeta.content;
  console.log('👤 Customer ID:', customerId);

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

  let currentVariantId = variantInput.value || (variantInput.options && variantInput.options[variantInput.selectedIndex] ? variantInput.options[variantInput.selectedIndex].value : null);
  console.log('🏷️ Initial variant ID:', currentVariantId);

  const styles = document.createElement('style');
  styles.textContent = '#metora-custom-price-container{background:linear-gradient(135deg,#10b981 0%,#059669 100%)!important;color:white!important;padding:20px!important;border-radius:12px!important;margin:16px 0!important;box-shadow:0 4px 6px rgba(16,185,129,0.2)!important;display:none!important;visibility:visible!important;opacity:1!important;position:relative!important;z-index:100!important;width:100%!important;max-width:600px!important}#metora-custom-price-container.active{display:block!important}#metora-custom-price-container .custom-price-header{font-size:14px!important;font-weight:600!important;margin-bottom:8px!important;opacity:0.95!important}#metora-custom-price-container .custom-price-main{display:flex!important;align-items:center!important;gap:16px!important;flex-wrap:wrap!important}#metora-custom-price-container .custom-price-value{font-size:32px!important;font-weight:bold!important}#metora-custom-price-container .custom-price-original{text-decoration:line-through!important;opacity:0.8!important;font-size:18px!important}#metora-custom-price-container .custom-price-badge{background:rgba(255,255,255,0.25)!important;padding:6px 12px!important;border-radius:20px!important;font-size:13px!important;font-weight:700!important}';
  document.head.appendChild(styles);

  const container = document.createElement('div');
  container.id = 'metora-custom-price-container';
  container.innerHTML = '<div style="padding:12px;text-align:center;opacity:0.8">Checking for your special price...</div>';
  
  let injectionPoint = document.querySelector('.product__price, .price, [data-price], .product-price, .price__container');
  
  if (!injectionPoint) {
    injectionPoint = document.querySelector('button[name="add"], .product-form__submit, [type="submit"]');
  }
  
  if (!injectionPoint) {
    injectionPoint = document.querySelector('form[action*="/cart/add"]');
  }
  
  if (injectionPoint) {
    if (injectionPoint.tagName === 'FORM') {
      injectionPoint.insertBefore(container, injectionPoint.firstChild);
    } else {
      injectionPoint.parentNode.insertBefore(container, injectionPoint.nextSibling);
    }
    console.log('✅ Container injected');
  } else {
    document.body.insertBefore(container, document.body.firstChild);
    container.style.position = 'sticky';
    container.style.top = '10px';
    console.log('⚠️ Injected at top');
  }

  async function checkCustomPrice(variantId) {
    if (!variantId) {
      console.warn('⚠️ No variant ID');
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

      console.log('📡 API status:', response.status);

      if (!response.ok) {
        hideCustomPrice();
        return;
      }

      const data = await response.json();
      console.log('📦 Response:', data);

      if (data.has_custom_price) {
        console.log('🎉 Custom price found!');
        displayCustomPrice(data);
      } else {
        console.log('ℹ️ No custom price');
        hideCustomPrice();
      }
    } catch (error) {
      console.error('❌ Error:', error);
      hideCustomPrice();
    }
  }

  function displayCustomPrice(data) {
    const discount = Math.round(((data.original_price - data.custom_price) / data.original_price) * 100);
    const currencySymbol = getCurrencySymbol(CONFIG.currency);
    
    container.innerHTML = '<div class="custom-price-header">🎉 Your Exclusive Price</div><div class="custom-price-main"><span class="custom-price-value">' + currencySymbol + parseFloat(data.custom_price).toFixed(2) + '</span><span class="custom-price-original">' + currencySymbol + parseFloat(data.original_price).toFixed(2) + '</span><span class="custom-price-badge">' + discount + '% OFF</span></div>';
    
    container.classList.add('active');
    container.style.display = 'block';
    container.style.visibility = 'visible';
    container.style.opacity = '1';
    
    console.log('✅ Price displayed');
  }

  function getCurrencySymbol(currency) {
    const symbols = {
      'USD': '$',
      'EUR': '€',
      'GBP': '£',
      'INR': '₹',
      'CAD': '$',
      'AUD': '$'
    };
    return symbols[currency] || currency + ' ';
  }

  function hideCustomPrice() {
    container.style.display = 'none';
  }

  variantInput.addEventListener('change', function() {
    currentVariantId = this.value || (this.options && this.options[this.selectedIndex] ? this.options[this.selectedIndex].value : null);
    console.log('🔄 Variant changed:', currentVariantId);
    checkCustomPrice(currentVariantId);
  });

  const optionSelectors = document.querySelectorAll('select[data-index^="option"], input[type="radio"][name*="option"]');
  optionSelectors.forEach(function(selector) {
    selector.addEventListener('change', function() {
      setTimeout(function() {
        const newVariantId = variantInput.value || (variantInput.options && variantInput.options[variantInput.selectedIndex] ? variantInput.options[variantInput.selectedIndex].value : null);
        if (newVariantId && newVariantId !== currentVariantId) {
          currentVariantId = newVariantId;
          console.log('🔄 Variant changed:', currentVariantId);
          checkCustomPrice(currentVariantId);
        }
      }, 100);
    });
  });

  checkCustomPrice(currentVariantId);

  console.log('✨ Initialized');

})();