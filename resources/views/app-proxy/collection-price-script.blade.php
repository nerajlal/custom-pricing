# collection-price-script.blade.php

(function() {
  'use strict';
  
  console.log('🛍️ Collection Custom Pricing Script Loaded');

  // Check if customer is logged in
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

  console.log('⚙️ Config:', CONFIG);

  // Add styles for custom pricing
  const styles = document.createElement('style');
  styles.textContent = `
    .metora-custom-price-container {
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
      margin-bottom: 4px !important;
    }
    .metora-custom-price-value {
      font-size: 18px !important;
      color: #10b981 !important;
      font-weight: 700 !important;
    }
    .metora-original-price {
      text-decoration: line-through !important;
      color: #9ca3af !important;
      font-size: 14px !important;
      margin-left: 8px !important;
    }
    .metora-discount-badge {
      background: #dcfce7 !important;
      color: #059669 !important;
      padding: 2px 8px !important;
      border-radius: 12px !important;
      font-size: 11px !important;
      font-weight: 700 !important;
      margin-left: 8px !important;
      display: inline-block !important;
    }
  `;
  document.head.appendChild(styles);

  // Find all product cards on the page
  const productCards = findProductCards();
  console.log('📦 Found', productCards.length, 'product cards');

  if (productCards.length === 0) {
    console.warn('⚠️ No product cards found. Trying different selectors...');
  }

  // Process each product card
  productCards.forEach(function(card, index) {
    console.log('Processing card', index + 1);
    processCard(card, index);
  });

  async function processCard(card, index) {
    let variantId = getVariantIdFromCard(card);
    
    if (variantId) {
      console.log('  ✓ Variant ID:', variantId);
      await checkAndDisplayCustomPrice(card, variantId);
    } else {
      // Try to get variant ID from product ID
      const productId = card.getAttribute('data-product-id');
      if (productId) {
        console.log('  🔄 No variant found, fetching from product ID:', productId);
        variantId = await getFirstVariantFromProduct(card, productId);
        if (variantId) {
          console.log('  ✓ Got variant ID from product:', variantId);
          await checkAndDisplayCustomPrice(card, variantId);
        } else {
          console.log('  ✗ Could not get variant ID from product');
        }
      } else {
        console.log('  ✗ No variant ID or product ID found');
      }
    }
  }

  async function getFirstVariantFromProduct(card, productId) {
    // Try to get product handle from link
    const productLink = card.querySelector('a[href*="/products/"]');
    if (!productLink) {
      console.log('  ✗ No product link found');
      return null;
    }

    const match = productLink.href.match(/\/products\/([^?#/]+)/);
    if (!match) {
      console.log('  ✗ Could not extract product handle from URL');
      return null;
    }

    const productHandle = match[1];
    console.log('  📡 Fetching product data for:', productHandle);

    try {
      // Try multiple URL formats
      const urls = [
        '/products/' + productHandle + '.js',
        window.location.origin + '/products/' + productHandle + '.js',
        'https://' + CONFIG.shop + '/products/' + productHandle + '.js'
      ];

      for (let url of urls) {
        try {
          const response = await fetch(url);
          if (response.ok) {
            const productData = await response.json();
            console.log('  ✅ Got product data from', url);
            console.log('  📦 Variants:', productData.variants?.length);

            if (productData.variants && productData.variants.length > 0) {
              return productData.variants[0].id;
            }
          }
        } catch (e) {
          console.log('  ⚠️ Failed with URL:', url);
        }
      }
    } catch (error) {
      console.error('  ❌ Error fetching product:', error);
    }

    // Fallback: Try to find variant in the card's onclick or data attributes
    console.log('  🔍 Fallback: searching card for variant data...');
    const cardHTML = card.outerHTML;
    const variantMatch = cardHTML.match(/variant['":\s]+(\d{10,})/i);
    if (variantMatch) {
      console.log('  ✅ Found variant in HTML:', variantMatch[1]);
      return variantMatch[1];
    }

    return null;
  }

  function findProductCards() {
    // Try multiple selectors for different themes
    let cards = [];
    
    const selectors = [
      '.product-card',
      '.product-item',
      '.grid__item',
      '[data-product-id]',
      '.product-grid-item',
      '.product',
      '.collection-product-card',
      'li[class*="product"]',
      'div[class*="product-card"]'
    ];

    for (let i = 0; i < selectors.length; i++) {
      cards = document.querySelectorAll(selectors[i]);
      if (cards.length > 0) {
        console.log('✓ Found cards using selector:', selectors[i]);
        break;
      }
    }

    // If still no cards, look for any element with a product link
    if (cards.length === 0) {
      const productLinks = document.querySelectorAll('a[href*="/products/"]');
      const parentCards = [];
      productLinks.forEach(function(link) {
        let parent = link.parentElement;
        let depth = 0;
        // Go up max 5 levels to find the card container
        while (parent && depth < 5) {
          if (parent.querySelector('.price, [data-price]')) {
            if (parentCards.indexOf(parent) === -1) {
              parentCards.push(parent);
            }
            break;
          }
          parent = parent.parentElement;
          depth++;
        }
      });
      cards = parentCards;
      console.log('✓ Found', cards.length, 'cards by searching product links');
    }

    return Array.from(cards);
  }

  function getVariantIdFromCard(card) {
    console.log('  Looking for variant ID in card...');
    
    // Method 1: data-variant-id attribute
    let element = card.querySelector('[data-variant-id]');
    if (element) {
      const id = element.getAttribute('data-variant-id');
      console.log('  Method 1 (data-variant-id):', id);
      return id;
    }

    // Method 2: data-product-id on the card itself
    if (card.hasAttribute('data-product-id')) {
      const productId = card.getAttribute('data-product-id');
      console.log('  Method 2a (data-product-id on card):', productId);
      const variantInput = card.querySelector('input[name="id"], select[name="id"]');
      if (variantInput && variantInput.value) {
        console.log('  Method 2b (variant from form):', variantInput.value);
        return variantInput.value;
      }
    }

    // Method 3: Hidden input in add-to-cart form
    element = card.querySelector('input[name="id"]');
    if (element && element.value) {
      console.log('  Method 3 (input[name="id"]):', element.value);
      return element.value;
    }

    // Method 4: Select dropdown
    element = card.querySelector('select[name="id"]');
    if (element && element.value) {
      console.log('  Method 4 (select[name="id"]):', element.value);
      return element.value;
    }

    // Method 5: From product link with variant parameter
    element = card.querySelector('a[href*="variant="]');
    if (element) {
      const match = element.href.match(/variant=(\d+)/);
      if (match) {
        console.log('  Method 5 (URL variant param):', match[1]);
        return match[1];
      }
    }

    // Method 6: From product URL
    const productLink = card.querySelector('a[href*="/products/"]');
    if (productLink) {
      const productHandle = productLink.href.match(/\/products\/([^?#/]+)/);
      if (productHandle && productHandle[1]) {
        console.log('  Method 6 (product handle):', productHandle[1]);
        const form = card.querySelector('form[action*="/cart/add"]');
        if (form) {
          const variantInput = form.querySelector('input[name="id"]');
          if (variantInput) {
            console.log('  Method 6b (variant from cart form):', variantInput.value);
            return variantInput.value;
          }
        }
      }
    }

    // Method 7: From data-product attribute
    element = card.querySelector('[data-product], [data-product-handle]');
    if (element) {
      const dataProduct = element.getAttribute('data-product');
      if (dataProduct) {
        try {
          const productData = JSON.parse(dataProduct);
          if (productData.variants && productData.variants[0]) {
            console.log('  Method 7 (data-product JSON):', productData.variants[0].id);
            return productData.variants[0].id;
          }
        } catch (e) {}
      }
    }

    // Method 8: Look in script tags
    const scripts = card.querySelectorAll('script[type="application/json"]');
    for (let i = 0; i < scripts.length; i++) {
      try {
        const data = JSON.parse(scripts[i].textContent);
        if (data.variants && data.variants[0] && data.variants[0].id) {
          console.log('  Method 8 (JSON script tag):', data.variants[0].id);
          return data.variants[0].id;
        }
      } catch (e) {}
    }

    console.log('  ✗ Could not find variant ID');
    return null;
  }

  async function checkAndDisplayCustomPrice(card, variantId) {
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
        console.log('  ℹ️ No custom price (API returned', response.status + ')');
        return;
      }

      const data = await response.json();
      console.log('  📦 API Response:', data);

      if (data.has_custom_price) {
        console.log('  🎉 Custom price found! Displaying...');
        displayCustomPriceOnCard(card, data);
      } else {
        console.log('  ℹ️ No custom price for this product');
      }
    } catch (error) {
      console.error('  ❌ Error checking custom price:', error);
    }
  }

  function displayCustomPriceOnCard(card, data) {
    const currencySymbol = getCurrencySymbol(CONFIG.currency);
    const discount = Math.round(((data.original_price - data.custom_price) / data.original_price) * 100);

    // Find ALL price elements in this card
    const priceElements = card.querySelectorAll('.price, .product-price, [data-price], .price__regular, .price-item, .money, [class*="price"]');
    
    console.log('  ✓ Found', priceElements.length, 'price elements to replace');

    let replaced = 0;

    priceElements.forEach(function(priceEl) {
      // Skip if already processed
      if (priceEl.classList.contains('metora-processed')) return;
      
      // Skip if it's our custom price element
      if (priceEl.closest('.metora-custom-price-container')) return;

      // Mark as processed
      priceEl.classList.add('metora-processed');

      // Get the parent to inject our custom price
      const parent = priceEl.parentElement;
      if (!parent) return;

      // Hide original price
      priceEl.style.display = 'none';

      // Create custom price container with border
      const customPriceContainer = document.createElement('div');
      customPriceContainer.className = 'metora-custom-price-container';
      
      customPriceContainer.innerHTML = `
        <div class="metora-custom-price-label">✨ Your Special Price</div>
        <div>
          <span class="metora-custom-price-value">${currencySymbol}${parseFloat(data.custom_price).toFixed(2)}</span>
          <span class="metora-original-price">${currencySymbol}${parseFloat(data.original_price).toFixed(2)}</span>
          <span class="metora-discount-badge">${discount}% OFF</span>
        </div>
      `;

      // Insert the custom price in place of original
      parent.insertBefore(customPriceContainer, priceEl);
      
      replaced++;
    });

    console.log('  ✅ Replaced', replaced, 'price displays with custom price');
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

  console.log('✨ Collection pricing initialized');

})();