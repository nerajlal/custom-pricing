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

  const priceCache = {};

  async function checkProductPrice(productCard, variantId) {
    if (!variantId) return;

    // If we have a cached price, use it.
    if (priceCache[variantId]) {
      if (priceCache[variantId].has_custom_price) {
        displayCustomPriceOnCard(productCard, priceCache[variantId]);
      }
      return;
    }

    // Mark as fetching to prevent multiple requests for the same variant.
    priceCache[variantId] = { fetching: true };

    try {
      const response = await fetch(CONFIG.apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({
          customer_id: parseInt(customerId),
          variant_id: parseInt(variantId),
          shop: CONFIG.shop,
          currency: CONFIG.currency
        })
      });

      if (!response.ok) {
        priceCache[variantId] = { has_custom_price: false }; // Cache failure to prevent re-fetching
        return;
      }

      const data = await response.json();
      priceCache[variantId] = data; // Cache the successful response
      if (data.has_custom_price) {
        displayCustomPriceOnCard(productCard, data);
      }
    } catch (error) {
      console.error('❌ Error checking price for variant:', variantId, error);
      priceCache[variantId] = { has_custom_price: false }; // Cache failure
    }
  }

  function displayCustomPriceOnCard(productCard, data) {
    const currencySymbol = getCurrencySymbol(CONFIG.currency);
    const priceElement = productCard.querySelector('.price, .product-price, [data-price], .card__information .price');

    if (!priceElement) return;

    const newPriceHTML = `
      <span style="font-weight: bold; color: #059669;">${currencySymbol}${parseFloat(data.custom_price).toFixed(2)}</span>
      <s style="opacity: 0.7; margin-left: 6px;">${currencySymbol}${parseFloat(data.original_price).toFixed(2)}</s>
    `;

    // Only update if the content is different, to prevent flicker and unnecessary DOM manipulation
    if (priceElement.innerHTML.trim() !== newPriceHTML.trim()) {
        priceElement.style.display = 'block';
        priceElement.innerHTML = newPriceHTML;
        console.log('✅ Custom price updated for variant:', data.variant_id);
    }
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

  function processProductCards() {
    const productCards = document.querySelectorAll('.product-card, .card, .grid__item, .product-item, [data-product-id]');

    productCards.forEach(card => {
      let variantId = card.getAttribute('data-variant-id');
      if (!variantId) {
        variantId = extractVariantId(card);
      }
      if (!variantId) {
        const input = card.querySelector('input[name="id"]');
        if (input) variantId = input.value;
      }

      if (variantId && !priceCache[variantId]?.fetching) {
        checkProductPrice(card, variantId);
      } else if (variantId && priceCache[variantId]?.has_custom_price) {
        // If the price is cached, re-apply it in case the theme re-rendered
        displayCustomPriceOnCard(card, priceCache[variantId]);
      }
    });
  }

  // Set up a poller to constantly check and apply prices
  setInterval(processProductCards, 500);

  console.log('✨ Collection pricing initialized');

})();