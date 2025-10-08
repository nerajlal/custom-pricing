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

  // Add minimal styles (most styling is inline now)
  const styles = document.createElement('style');
  styles.textContent = `
    .metora-original-price-strike {
      text-decoration: line-through !important;
      opacity: 0.4 !important;
      font-size: 0.85em !important;
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
    const variantId = getVariantIdFromCard(card);
    
    if (variantId) {
      console.log('  ✓ Variant ID:', variantId);
      checkAndDisplayCustomPrice(card, variantId);
    } else {
      console.log('  ✗ No variant ID found');
    }
  });

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

    // Method 2: Hidden input in add-to-cart form
    element = card.querySelector('input[name="id"]');
    if (element && element.value) {
      console.log('  Method 2 (input[name="id"]):', element.value);
      return element.value;
    }

    // Method 3: Select dropdown
    element = card.querySelector('select[name="id"]');
    if (element && element.value) {
      console.log('  Method 3 (select[name="id"]):', element.value);
      return element.value;
    }

    // Method 4: From product link with variant parameter
    element = card.querySelector('a[href*="variant="]');
    if (element) {
      const match = element.href.match(/variant=(\d+)/);
      if (match) {
        console.log('  Method 4 (URL variant param):', match[1]);
        return match[1];
      }
    }

    // Method 5: From data-product attribute (might contain variant info)
    element = card.querySelector('[data-product], [data-product-handle]');
    if (element) {
      const dataProduct = element.getAttribute('data-product');
      if (dataProduct) {
        try {
          const productData = JSON.parse(dataProduct);
          if (productData.variants && productData.variants[0]) {
            console.log('  Method 5 (data-product JSON):', productData.variants[0].id);
            return productData.variants[0].id;
          }
        } catch (e) {}
      }
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
    // Find the price element
    let priceElement = card.querySelector('.price, .product-price, [data-price], .price__regular, .price-item');
    
    if (!priceElement) {
      console.log('  ⚠️ Could not find price element in card');
      return;
    }

    console.log('  ✓ Found price element:', priceElement.className);

    const currencySymbol = getCurrencySymbol(CONFIG.currency);
    const discount = Math.round(((data.original_price - data.custom_price) / data.original_price) * 100);

    // Strike through original price
    priceElement.classList.add('metora-original-price-strike');

    // Create custom price container with inline styles (same as console script that worked)
    const customPriceContainer = document.createElement('div');
    customPriceContainer.className = 'metora-collection-custom-price';
    customPriceContainer.style.cssText = `
        display: block !important;
        position: relative !important;
        background: #10b981 !important;
        color: white !important;
        padding: 12px !important;
        margin: 0 0 10px 0 !important;
        border-radius: 8px !important;
        font-size: 16px !important;
        font-weight: 700 !important;
        z-index: 10 !important;
        width: 100% !important;
        box-sizing: border-box !important;
    `;
    customPriceContainer.innerHTML = '<div style="font-size: 12px; margin-bottom: 4px; opacity: 0.95;">Special Price for You</div><div style="font-size: 20px; font-weight: 800;">' + currencySymbol + parseFloat(data.custom_price).toFixed(2) + ' <span style="background: rgba(255,255,255,0.3); padding: 3px 8px; border-radius: 4px; font-size: 13px;">' + discount + '% OFF</span></div>';

    // Make card position relative
    card.style.position = 'relative';

    // Insert at the VERY TOP of the card (same as console script that worked)
    card.insertBefore(customPriceContainer, card.firstChild);
    
    console.log('  ✅ Custom price inserted at top of card');
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