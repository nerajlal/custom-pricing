# cart-custom-price-script.blade.php


(function() {
  'use strict';
  
  console.log('🛒 Universal Cart Pricing Loaded');

  const customerMeta = document.querySelector('meta[name="customer-id"]');
  if (!customerMeta) {
    console.log('⚠️ No customer logged in');
    return;
  }

  const customerId = customerMeta.content;
  console.log('👤 Customer ID:', customerId);

  const isCartPage = window.location.pathname.includes('/cart') || 
                     document.querySelector('.cart, [data-cart], #cart, .cart-page');
  
  if (!isCartPage) {
    console.log('⚠️ Not on cart page');
    return;
  }

  const CONFIG = {
    apiUrl: 'https://customprice.metora.in/api/storefront/custom-price',
    shop: window.Shopify.shop,
    currency: window.Shopify.currency.active
  };

  console.log('⚙️ Config:', CONFIG);

  // Global storage for custom prices
  window.metoraCustomPrices = window.metoraCustomPrices || {};

  // Add aggressive styles
  const styles = document.createElement('style');
  styles.textContent = `
    .metora-hide-price { display: none !important; visibility: hidden !important; }
    .metora-custom-price-badge {
      background: #dcfce7 !important;
      border: 2px solid #10b981 !important;
      border-radius: 8px !important;
      padding: 8px 12px !important;
      display: inline-block !important;
      margin: 4px 0 !important;
    }
    .metora-custom-price-label {
      font-size: 11px !important;
      color: #059669 !important;
      font-weight: 600 !important;
      display: block !important;
      margin-bottom: 4px !important;
    }
    .metora-custom-price-value {
      font-size: 18px !important;
      color: #10b981 !important;
      font-weight: 700 !important;
    }
    .metora-original-price-strike {
      text-decoration: line-through !important;
      color: #9ca3af !important;
      font-size: 14px !important;
      margin-left: 8px !important;
    }
    .metora-savings-badge {
      background: #059669 !important;
      color: white !important;
      padding: 2px 8px !important;
      border-radius: 12px !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      margin-left: 8px !important;
      display: inline-block !important;
    }
  `;
  document.head.appendChild(styles);

  // Initialize
  setTimeout(function() {
    console.log('🚀 Starting cart price update...');
    processCart();
  }, 500);

  async function processCart() {
    // Get cart data from Shopify
    try {
      const response = await fetch('/cart.js');
      const cart = await response.json();
      
      console.log('📦 Cart data:', cart);
      console.log('📊 Items in cart:', cart.items.length);

      // Process each item
      for (let i = 0; i < cart.items.length; i++) {
        const item = cart.items[i];
        console.log('Processing item', i + 1, '- Variant:', item.variant_id);
        await fetchAndApplyCustomPrice(item.variant_id, item);
      }

      // After all prices are fetched, update the display
      setTimeout(function() {
        updateAllCartDisplays();
      }, 1000);

    } catch (error) {
      console.error('❌ Error getting cart:', error);
    }
  }

  async function fetchAndApplyCustomPrice(variantId, cartItem) {
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
        console.log('  ℹ️ No custom price for variant:', variantId);
        return;
      }

      const data = await response.json();

      if (data.has_custom_price) {
        console.log('  🎉 Custom price found for variant:', variantId);
        console.log('  💰 Original:', data.original_price, '→ Custom:', data.custom_price);
        
        // Store the custom price
        window.metoraCustomPrices[variantId] = {
          original: parseFloat(data.original_price),
          custom: parseFloat(data.custom_price),
          quantity: cartItem.quantity
        };
      }
    } catch (error) {
      console.error('  ❌ Error:', error);
    }
  }

  function updateAllCartDisplays() {
    console.log('🎨 Updating all cart displays...');
    console.log('📝 Custom prices stored:', window.metoraCustomPrices);

    // Get all variant IDs that have custom prices
    const variantIds = Object.keys(window.metoraCustomPrices);
    
    if (variantIds.length === 0) {
      console.log('⚠️ No custom prices to display');
      return;
    }

    variantIds.forEach(function(variantId) {
      const priceData = window.metoraCustomPrices[variantId];
      updateCartItemDisplay(variantId, priceData);
    });

    console.log('✅ All displays updated');
  }

  function updateCartItemDisplay(variantId, priceData) {
    console.log('🔧 Updating display for variant:', variantId);

    const symbol = getSymbol(CONFIG.currency);
    const originalPrice = priceData.original / 100;
    const customPrice = priceData.custom / 100;
    const quantity = priceData.quantity;
    const savings = originalPrice - customPrice;
    const lineTotal = customPrice * quantity;
    const originalLineTotal = originalPrice * quantity;

    // ONLY search within cart area (not the whole page)
    const cartContainers = document.querySelectorAll(
      '.cart, .cart-items, #cart, [data-cart], ' +
      'cart-items, cart-items-component, ' +
      '.cart-page, .cart__items, ' +
      'table.cart-items__table'
    );

    if (cartContainers.length === 0) {
      console.warn('  ⚠️ No cart container found');
      return;
    }

    console.log('  📦 Searching in', cartContainers.length, 'cart containers');

    cartContainers.forEach(function(cartContainer) {
      // Find all rows/items within this cart
      const cartItems = cartContainer.querySelectorAll(
        'tr, .cart-item, .cart__item, [class*="cart-item"], li'
      );

      cartItems.forEach(function(item) {
        // Check if this item is for our variant
        const html = item.outerHTML;
        const isThisVariant = html.includes('variant-id="' + variantId + '"') ||
                             html.includes('data-variant-id="' + variantId + '"') ||
                             html.includes('variant=' + variantId) ||
                             item.getAttribute('data-variant-id') === variantId ||
                             item.getAttribute('data-id') === variantId;

        if (!isThisVariant) return;

        console.log('  ✓ Found cart item for variant:', variantId);

        // Update prices within THIS cart item only
        updatePricesInElement(item, originalPrice, customPrice, lineTotal, originalLineTotal, symbol, quantity);
      });
    });
  }

  function updatePricesInElement(element, originalPrice, customPrice, lineTotal, originalLineTotal, symbol, quantity) {
    // Find all price displays in this element
    const priceElements = element.querySelectorAll(
      '.price:not(.metora-updated), ' +
      '.money:not(.metora-updated), ' +
      '[data-price]:not(.metora-updated), ' +
      '[class*="price"]:not(.metora-updated)'
    );

    console.log('  💰 Found', priceElements.length, 'price elements in cart item');

    let unitPriceUpdated = false;
    let lineTotalUpdated = false;

    priceElements.forEach(function(priceEl) {
      const priceText = priceEl.textContent.replace(/[^\d.]/g, '');
      const priceValue = parseFloat(priceText);

      // Check if this is the unit price
      if (Math.abs(priceValue - originalPrice) < 0.01 && !unitPriceUpdated) {
        console.log('  ✅ Updating unit price from', originalPrice, 'to', customPrice);
        priceEl.classList.add('metora-updated');
        priceEl.innerHTML = createCustomPriceBadge(customPrice, originalPrice, symbol, quantity);
        unitPriceUpdated = true;
      }
      // Check if this is the line total
      else if (Math.abs(priceValue - originalLineTotal) < 0.01 && !lineTotalUpdated) {
        console.log('  ✅ Updating line total from', originalLineTotal, 'to', lineTotal);
        priceEl.classList.add('metora-updated');
        priceEl.innerHTML = '<span class="metora-custom-price-value">' + symbol + lineTotal.toFixed(2) + '</span><span class="metora-original-price-strike">' + symbol + originalLineTotal.toFixed(2) + '</span>';
        lineTotalUpdated = true;
      }
    });

    if (!unitPriceUpdated) {
      console.warn('  ⚠️ Could not find unit price element');
    }
    if (!lineTotalUpdated) {
      console.warn('  ⚠️ Could not find line total element');
    }
  }

  function createCustomPriceBadge(customPrice, originalPrice, symbol, quantity) {
    const savings = originalPrice - customPrice;
    const discount = Math.round((savings / originalPrice) * 100);
    
    return '<div class="metora-custom-price-badge">' +
           '<span class="metora-custom-price-label">✨ Your Special Price</span>' +
           '<div>' +
           '<span class="metora-custom-price-value">' + symbol + customPrice.toFixed(2) + '</span>' +
           '<span class="metora-original-price-strike">' + symbol + originalPrice.toFixed(2) + '</span>' +
           '<span class="metora-savings-badge">Save ' + symbol + savings.toFixed(2) + '</span>' +
           '</div>' +
           '</div>';
  }

  function getSymbol(currency) {
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

  console.log('✨ Universal cart pricing initialized');

})();