(function() {
  'use strict';
  
  // Prevent multiple executions
  if (window.metoraCollectionPricingLoaded) {
    console.log('⚠️ Collection script already loaded, skipping');
    return;
  }
  window.metoraCollectionPricingLoaded = true;
  
  console.log('🛍️ Collection Custom Pricing Script Loaded');

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

  const styles = document.createElement('style');
  styles.textContent = '.custom-price-badge{display:inline-block;background:linear-gradient(135deg,#10b981,#059669)!important;color:white!important;padding:4px 10px!important;border-radius:6px!important;font-size:12px!important;font-weight:700!important;margin-top:4px!important;box-shadow:0 2px 4px rgba(16,185,129,0.3)!important}.custom-price-badge .price-new{font-size:16px!important;font-weight:bold!important;margin-right:6px!important}.custom-price-badge .price-old{text-decoration:line-through!important;opacity:0.8!important;font-size:13px!important;margin-right:6px!important}.custom-price-badge .discount{background:rgba(255,255,255,0.25)!important;padding:2px 6px!important;border-radius:4px!important;font-size:11px!important}';
  document.head.appendChild(styles);

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

  async function checkProductPrice(productCard, variantId) {
    if (!variantId) return;

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

      if (!response.ok) return;

      const data = await response.json();

      if (data.has_custom_price) {
        displayCustomPriceOnCard(productCard, data);
      }
    } catch (error) {
      console.error('❌ Error checking price for variant:', variantId, error);
    }
  }

  function displayCustomPriceOnCard(productCard, data) {
    const discount = Math.round(((data.original_price - data.custom_price) / data.original_price) * 100);
    const currencySymbol = getCurrencySymbol(CONFIG.currency);
    
    const priceElement = productCard.querySelector('.price, .product-price, [data-price], .card__information .price');
    if (!priceElement) return;

    const existingBadge = productCard.querySelector('.custom-price-badge');
    if (existingBadge) {
      existingBadge.remove();
    }

    const badge = document.createElement('div');
    badge.className = 'custom-price-badge';
    badge.innerHTML = '<span class="price-new">🎉 ' + currencySymbol + parseFloat(data.custom_price).toFixed(2) + '</span><span class="price-old">' + currencySymbol + parseFloat(data.original_price).toFixed(2) + '</span><span class="discount">' + discount + '% OFF</span>';
    
    priceElement.parentNode.insertBefore(badge, priceElement);
    priceElement.style.display = 'none';
    
    console.log('✅ Custom price displayed on card for variant:', data);
  }

  function extractVariantId(productCard) {
    const linkElement = productCard.querySelector('a[href*="/products/"]');
    if (!linkElement) return null;

    const href = linkElement.getAttribute('href');
    const match = href.match(/variant=(\d+)/);
    if (match) {
      return match[1];
    }

    const productId = href.match(/\/products\/([^?\/]+)/);
    if (productId) {
      const formData = productCard.querySelector('form input[name="id"]');
      if (formData) {
        return formData.value;
      }
    }

    return null;
  }

  let observer;

  async function processProductCards() {
    if (observer) {
      observer.disconnect();
    }

    const productCards = document.querySelectorAll('.product-card, .card, .grid__item, .product-item, [data-product-id]');
    const priceCheckPromises = [];

    console.log('🔍 Found', productCards.length, 'product cards');

    productCards.forEach(function(card, index) {
      let variantId = card.getAttribute('data-variant-id');
      
      if (!variantId) {
        variantId = extractVariantId(card);
      }

      if (!variantId) {
        const input = card.querySelector('input[name="id"]');
        if (input) {
          variantId = input.value;
        }
      }

      if (variantId) {
        console.log('📦 Processing card', index, 'with variant:', variantId);
        priceCheckPromises.push(checkProductPrice(card, variantId));
      } else {
        console.log('⚠️ No variant ID found for card', index);
      }
    });

    await Promise.all(priceCheckPromises);

    if (observer) {
      observer.observe(document.body, {
        childList: true,
        subtree: true,
      });
    }
  }

  function debounce(func, wait) {
    let timeout;
    return function(...args) {
      const context = this;
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(context, args), wait);
    };
  }

  const debouncedProcessProductCards = debounce(processProductCards, 300);

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', debouncedProcessProductCards);
  } else {
    debouncedProcessProductCards();
  }

  observer = new MutationObserver(() => {
    debouncedProcessProductCards();
  });

  observer.observe(document.body, {
    childList: true,
    subtree: true,
  });

  console.log('✨ Collection pricing initialized');

})();