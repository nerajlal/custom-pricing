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

  const CONFIG = {
    apiUrl: 'https://customprice.metora.in/api/storefront/custom-price',
    shop: window.Shopify.shop,
    currency: window.Shopify.currency.active
  };

  // Check if we're on a product page or collection page
  const isProductPage = document.querySelector('input[name="id"], select[name="id"]');
  const isCollectionPage = document.querySelector('.product-grid, .collection, [data-product-grid]');

  if (isProductPage) {
    console.log('📄 Product page detected');
    initProductPage();
  } else if (isCollectionPage) {
    console.log('📋 Collection page detected');
    initCollectionPage();
  } else {
    console.log('⚠️ Unknown page type');
  }

  // ========== PRODUCT PAGE ==========
  function initProductPage() {
    const variantInput = document.querySelector('input[name="id"], select[name="id"]');
    let currentVariantId = variantInput.value || (variantInput.options && variantInput.options[variantInput.selectedIndex] ? variantInput.options[variantInput.selectedIndex].value : null);

    const styles = document.createElement('style');
    styles.textContent = '#metora-custom-price-container{background:linear-gradient(135deg,#10b981 0%,#059669 100%)!important;color:white!important;padding:20px!important;border-radius:12px!important;margin:16px 0!important;box-shadow:0 4px 6px rgba(16,185,129,0.2)!important;display:none!important;visibility:visible!important;opacity:1!important;position:relative!important;z-index:100!important;width:100%!important;max-width:600px!important}#metora-custom-price-container.active{display:block!important}#metora-custom-price-container .custom-price-header{font-size:14px!important;font-weight:600!important;margin-bottom:8px!important}#metora-custom-price-container .custom-price-main{display:flex!important;align-items:center!important;gap:16px!important;flex-wrap:wrap!important}#metora-custom-price-container .custom-price-value{font-size:32px!important;font-weight:bold!important}#metora-custom-price-container .custom-price-original{text-decoration:line-through!important;opacity:0.8!important;font-size:18px!important}#metora-custom-price-container .custom-price-badge{background:rgba(255,255,255,0.25)!important;padding:6px 12px!important;border-radius:20px!important;font-size:13px!important;font-weight:700!important}.metora-collection-price{display:inline-block!important;background:#10b981!important;color:white!important;padding:4px 8px!important;border-radius:6px!important;font-size:13px!important;font-weight:600!important;margin-left:8px!important}';
    document.head.appendChild(styles);

    const container = document.createElement('div');
    container.id = 'metora-custom-price-container';
    
    let injectionPoint = document.querySelector('.product__price, .price, [data-price], .product-price, .price__container');
    if (!injectionPoint) injectionPoint = document.querySelector('button[name="add"], .product-form__submit');
    if (!injectionPoint) injectionPoint = document.querySelector('form[action*="/cart/add"]');
    
    if (injectionPoint) {
      if (injectionPoint.tagName === 'FORM') {
        injectionPoint.insertBefore(container, injectionPoint.firstChild);
      } else {
        injectionPoint.parentNode.insertBefore(container, injectionPoint.nextSibling);
      }
    }

    checkCustomPrice(currentVariantId, container);

    variantInput.addEventListener('change', function() {
      currentVariantId = this.value || (this.options && this.options[this.selectedIndex] ? this.options[this.selectedIndex].value : null);
      checkCustomPrice(currentVariantId, container);
    });
  }

  // ========== COLLECTION PAGE ==========
  function initCollectionPage() {
    const styles = document.createElement('style');
    styles.textContent = '.metora-collection-price{display:inline-block!important;background:#10b981!important;color:white!important;padding:4px 8px!important;border-radius:6px!important;font-size:13px!important;font-weight:600!important;margin-left:8px!important}.metora-original-price{text-decoration:line-through!important;opacity:0.6!important}';
    document.head.appendChild(styles);

    // Find all product cards
    const productCards = document.querySelectorAll('.product-card, .product-item, [data-product-id], .grid__item');
    console.log('📦 Found', productCards.length, 'products');

    productCards.forEach(function(card) {
      const variantId = getVariantIdFromCard(card);
      if (variantId) {
        checkCustomPriceForCard(card, variantId);
      }
    });
  }

  function getVariantIdFromCard(card) {
    // Try different methods to get variant ID
    const dataAttr = card.querySelector('[data-variant-id]');
    if (dataAttr) return dataAttr.getAttribute('data-variant-id');

    const addToCartForm = card.querySelector('form[action*="/cart/add"]');
    if (addToCartForm) {
      const input = addToCartForm.querySelector('input[name="id"]');
      if (input) return input.value;
    }

    const link = card.querySelector('a[href*="/products/"]');
    if (link) {
      const match = link.href.match(/variant=(\d+)/);
      if (match) return match[1];
    }

    return null;
  }

  async function checkCustomPriceForCard(card, variantId) {
    try {
      const response = await fetch(CONFIG.apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          customer_id: parseInt(customerId),
          variant_id: parseInt(variantId),
          shop: CONFIG.shop,
          currency: CONFIG.currency
        })
      });

      if (!response.ok) return;

      const data = await response.json();

      if (data.has_custom_price) {
        displayCustomPriceOnCard(card, data);
      }
    } catch (error) {
      console.error('Error for variant', variantId, error);
    }
  }

  function displayCustomPriceOnCard(card, data) {
    const priceElement = card.querySelector('.price, .product-price, [data-price]');
    if (!priceElement) return;

    const currencySymbol = getCurrencySymbol(CONFIG.currency);
    const discount = Math.round(((data.original_price - data.custom_price) / data.original_price) * 100);

    // Mark original price
    priceElement.classList.add('metora-original-price');

    // Add custom price badge
    const badge = document.createElement('span');
    badge.className = 'metora-collection-price';
    badge.textContent = currencySymbol + parseFloat(data.custom_price).toFixed(2) + ' (-' + discount + '%)';
    
    priceElement.parentNode.insertBefore(badge, priceElement.nextSibling);
  }

  // ========== SHARED FUNCTIONS ==========
  async function checkCustomPrice(variantId, container) {
    if (!variantId) return;

    try {
      const response = await fetch(CONFIG.apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          customer_id: parseInt(customerId),
          variant_id: parseInt(variantId),
          shop: CONFIG.shop,
          currency: CONFIG.currency
        })
      });

      if (!response.ok) {
        container.style.display = 'none';
        return;
      }

      const data = await response.json();

      if (data.has_custom_price) {
        displayCustomPrice(container, data);
      } else {
        container.style.display = 'none';
      }
    } catch (error) {
      console.error('Error:', error);
      container.style.display = 'none';
    }
  }

  function displayCustomPrice(container, data) {
    const discount = Math.round(((data.original_price - data.custom_price) / data.original_price) * 100);
    const currencySymbol = getCurrencySymbol(CONFIG.currency);
    
    container.innerHTML = '<div class="custom-price-header">🎉 Your Exclusive Price</div><div class="custom-price-main"><span class="custom-price-value">' + currencySymbol + parseFloat(data.custom_price).toFixed(2) + '</span><span class="custom-price-original">' + currencySymbol + parseFloat(data.original_price).toFixed(2) + '</span><span class="custom-price-badge">' + discount + '% OFF</span></div>';
    
    container.classList.add('active');
    container.style.display = 'block';
  }

  function getCurrencySymbol(currency) {
    const symbols = { 'USD': '$', 'EUR': '€', 'GBP': '£', 'INR': '₹', 'CAD': '$', 'AUD': '$' };
    return symbols[currency] || currency + ' ';
  }

  console.log('✨ Initialized');

})();