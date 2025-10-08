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
    const currencySymbol = getCurrencySymbol(CONFIG.currency);
    const priceElement = productCard.querySelector('.price, .product-price, [data-price], .card__information .price');

    if (!priceElement) {
      console.log('⚠️ Price element not found on card.');
      return;
    }

    // Directly update the price element's content to be more resilient
    priceElement.style.display = 'block'; // Ensure the price element is visible
    priceElement.innerHTML = `
      <span style="font-weight: bold; color: #059669;">${currencySymbol}${parseFloat(data.custom_price).toFixed(2)}</span>
      <s style="opacity: 0.7; margin-left: 6px;">${currencySymbol}${parseFloat(data.original_price).toFixed(2)}</s>
    `;
    
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

  async function processProductCards() {
    const productCards = document.querySelectorAll('.product-card:not([data-custom-price-processed]), .card:not([data-custom-price-processed]), .grid__item:not([data-custom-price-processed]), .product-item:not([data-custom-price-processed]), [data-product-id]:not([data-custom-price-processed])');

    if (productCards.length === 0) return;

    console.log('🔍 Found', productCards.length, 'new product cards to process');

    for (const card of productCards) {
      card.setAttribute('data-custom-price-processed', 'true');

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
        await checkProductPrice(card, variantId);
      }
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

  // Initial run
  processProductCards();

  // Set up a poller to catch products that are loaded dynamically
  setInterval(processProductCards, 500);

  console.log('✨ Collection pricing initialized');

})();