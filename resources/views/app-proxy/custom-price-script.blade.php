(async function() {
  'use strict';
  
  if (window.metoraPricingInitialized) {
      console.log('🚫 Metora Pricing Script already initialized, skipping...');
      return;
  }
  window.metoraPricingInitialized = true;

  console.log('🎨 Unified Custom Pricing Script Loaded');

  // Helper to fetch identity from Proxy
  async function fetchCustomerId() {
    // Try potential proxy paths
    const proxyPaths = ['/apps/custompicker', '/apps/custom-pricing', '/apps/pricing'];
    
    for (const path of proxyPaths) {
      try {
        const url = `${path}/app-proxy/identify-customer?shop=${window.Shopify.shop}`;
        const res = await fetch(url);
        if (res.ok) {
           const data = await res.json();
           if (data.customer_id) {
               console.log('✅ Identified customer via Proxy:', path);
               return data.customer_id;
           }
        }
      } catch(e) { /* ignore */ }
    }
    return null;
  }

  // Check if customer is logged in
  // Robust Customer ID Detection
  function detectCustomerId() {
    // Method 0: LocalStorage Cache (Fastest)
    try {
        const cachedId = localStorage.getItem('metora_customer_id');
        if (cachedId) {
             console.log('✅ Found customer ID in LocalStorage:', cachedId);
             return parseInt(cachedId);
        }
    } catch(e) {}

    // Method 1: window.__st.cid (Shopify Analytics - Most Reliable)
    if (window.__st && window.__st.cid) {
        console.log('✅ Found customer ID in __st.cid:', window.__st.cid);
        localStorage.setItem('metora_customer_id', window.__st.cid);
        return parseInt(window.__st.cid);
    }
    
    // Method 2: window.Shopify.customer
    if (window.Shopify && window.Shopify.customer && window.Shopify.customer.id) {
        console.log('✅ Found customer ID in Shopify.customer object');
        localStorage.setItem('metora_customer_id', window.Shopify.customer.id);
        return window.Shopify.customer.id;
    }

    // Method 3: Server-side injected ID (Blade)
    let injectedId = "{{ $customerId ?? '' }}";
    if (injectedId) {
        console.log('✅ Found server-side injected ID');
        localStorage.setItem('metora_customer_id', injectedId);
        return injectedId;
    }
    
    // Method 4: Meta tags
    const metaSelectors = ['meta[name="shopify-customer-id"]', 'meta[name="customer-id"]'];
    for (const selector of metaSelectors) {
        const meta = document.querySelector(selector);
        if (meta && meta.content) {
            console.log('✅ Found customer ID in meta tag:', selector);
            localStorage.setItem('metora_customer_id', meta.content);
            return meta.content;
        }
    }
    
    return null;
  }

  let customerId = detectCustomerId();
  // Check for logout link explicitly for the HIDE logic
  const hasLogoutLink = !!document.querySelector('a[href*="/account/logout"]');
  const shouldHidePrices = customerId || hasLogoutLink;

  // ⚡ IMMEDIATE HIDE: Prevents flash of original price (Moved Up)
  if (shouldHidePrices) {
      const hideStyle = document.createElement('style');
      hideStyle.id = 'metora-initial-hide';
      hideStyle.textContent = `
          .product__info-container .price, 
          .product-single__meta .price,
          .product__price, 
          .price, 
          [data-price], 
          .product-price, 
          .price__container,
          .price__regular,
          .price-item,
          .money,
          .cart-item .price, 
          .cart__price, 
          [data-cart-item-price],
          .cart__subtotal,
          .totals__subtotal-value,
          .line-item-price { 
              opacity: 0 !important; 
              visibility: hidden !important; 
          }
      `;
      document.head.appendChild(hideStyle);
      console.log('🙈 Prices temporarily hidden to prevent flash (Sync)');
  }

  // Priority 5: Async Proxy Identification (Last Resort)
  if (!customerId) {
       console.log('🕵️‍♀️ Attempting to identify customer via Proxy as last resort...');
       customerId = await fetchCustomerId();
       if (customerId) {
           localStorage.setItem('metora_customer_id', customerId);
           // Optional: Hide here too if we want to support late-detected users, 
           // but seeing the price for 3s then hiding it might be jarring.
           // Let's assume most use cases are covered by Sync.
       }
  }

  if (!customerId) {
    console.log('⚠️ No customer logged in (checked Proxy Injection, Meta, and Async Identification)');
    // Safety: If we optimistically hid prices but couldn't identify the user, unhide them now.
    const initialHide = document.getElementById('metora-initial-hide');
    if (initialHide) initialHide.remove();
    return;
  }
  
  console.log('👤 Customer ID:', customerId);
  
  window.SHOPIFY_CUSTOMER_ID = customerId;

  window.metoraConfig = {
    apiUrl: '{{ rtrim(env("APP_URL"), "/") }}/api/storefront/custom-price',
    shop: window.Shopify.shop,
    currency: window.Shopify.currency.active
  };
  
  const CONFIG = window.metoraConfig;

  console.log('⚙️ Config:', CONFIG);


// Detect page type FIRST
  const isProductPage = window.location.pathname.includes('/products/') && 
                        (document.querySelector('input[name="id"], select[name="id"]') || document.querySelector('.product__info-container'));
  const isCartPage = window.location.pathname.includes('/cart') || 
                     document.querySelector('.cart, [data-cart], #cart, .cart-page');
  
  const pageType = (
    isProductPage ? 'Product Detail Page' :
    isCartPage ? 'Cart Page' : 
    'Home/Collection Page'
  );
  
  console.log('📄 Page type:', pageType);

  // **EXIT EARLY ON CART PAGE - Let cart-custom-price-script.blade.php handle it**
  if (isCartPage) {
    console.log('⏭️ Skipping unified script on cart page (cart-specific script will handle it)');
    return;
  }

  // ============================================
  // SHARED STYLES (PDP & GRIDS)
  // ============================================
  const sharedStyles = document.createElement('style');
  sharedStyles.textContent = `
    /* Main Product Box (PDP) */
    .metora-custom-price-container {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%) !important;
      color: white !important;
      padding: 20px !important;
      border-radius: 12px !important;
      margin: 16px 0 !important;
      box-shadow: 0 4px 15px rgba(16,185,129,0.3) !important;
      display: none;
      visibility: visible !important;
      opacity: 1 !important;
      position: relative !important;
      z-index: 999999 !important;
      width: 100% !important;
      max-width: 600px !important;
      min-height: 50px !important;
      border: 2px solid #047857 !important;
    }
    .metora-custom-price-container.active {
      display: block !important;
    }
    .metora-custom-price-container .custom-price-header {
      font-size: 14px !important;
      font-weight: 600 !important;
      margin-bottom: 8px !important;
      opacity: 0.95 !important;
    }
    .metora-custom-price-container .custom-price-main {
      display: flex !important;
      align-items: center !important;
      gap: 16px !important;
      flex-wrap: wrap !important;
    }
    .metora-custom-price-container .custom-price-value {
      font-size: 32px !important;
      font-weight: bold !important;
    }
    .metora-custom-price-container .custom-price-original {
      text-decoration: line-through !important;
      opacity: 0.8 !important;
      font-size: 18px !important;
    }
    .metora-custom-price-container .custom-price-badge {
      background: rgba(255,255,255,0.25) !important;
      padding: 6px 12px !important;
      border-radius: 20px !important;
      font-size: 13px !important;
      font-weight: 700 !important;
    }

    /* Grid/Card Styling overrides */
    .product-card .metora-custom-price-container, 
    .grid__item .metora-custom-price-container,
    .product-item .metora-custom-price-container {
      border: 2px solid #10b981 !important;
      border-radius: 8px !important;
      padding: 8px 12px !important;
      background: #f0fdf4 !important; /* Lighter background for grids */
      color: #065f46 !important;
      box-shadow: none !important;
      margin: 4px 0 !important;
    }
    
    .product-card .metora-custom-price-container .custom-price-header,
    .grid__item .metora-custom-price-container .custom-price-header {
      font-size: 10px !important;
      color: #059669 !important;
    }
    
    .product-card .metora-custom-price-container .custom-price-value,
    .grid__item .metora-custom-price-container .custom-price-value {
      font-size: 18px !important;
      color: #10b981 !important;
    }
    
    .product-card .metora-custom-price-container .custom-price-original,
    .grid__item .metora-custom-price-container .custom-price-original {
      color: #9ca3af !important;
      font-size: 14px !important;
    }

    .product-card .metora-custom-price-container .custom-price-badge,
    .grid__item .metora-custom-price-container .custom-price-badge {
      background: #dcfce7 !important;
      color: #059669 !important;
      font-size: 11px !important;
    }

    /* Silent override style (Universal) */
    .metora-custom-price-container.silent-mode {
      background: transparent !important;
      padding: 0 !important;
      margin: 0 !important;
      box-shadow: none !important;
      color: inherit !important;
      border: none !important;
      width: auto !important;
      display: inline-block !important;
    }
    .metora-custom-price-container.silent-mode .custom-price-header,
    .metora-custom-price-container.silent-mode .custom-price-original,
    .metora-custom-price-container.silent-mode .custom-price-badge {
      display: none !important;
    }
    .metora-custom-price-container.silent-mode .custom-price-main {
      gap: 0 !important;
    }
    .metora-custom-price-container.silent-mode .custom-price-value {
      font-size: inherit !important;
      font-weight: bold !important;
      color: inherit !important;
    }

    /* Force hide theme elements even if re-rendered */
    .metora-temporarily-hidden, .metora-hidden-original {
      display: none !important;
      visibility: hidden !important;
      height: 0 !important;
      overflow: hidden !important;
      opacity: 0 !important;
    }
  `;
  document.head.appendChild(sharedStyles);
  // ============================================
  // 1. PRODUCT DETAIL PAGE
  // ============================================
  if (isProductPage) {
    console.log('🛍️ Initializing Product Page Pricing...');

    const variantInput = document.querySelector('input[name="id"], select[name="id"]');
    let currentVariantId = variantInput.value || 
                          (variantInput.options && variantInput.options[variantInput.selectedIndex] 
                            ? variantInput.options[variantInput.selectedIndex].value 
                            : null);

    console.log('🏷️ Initial variant ID:', currentVariantId);


    // Create container template
    const containerTemplate = document.createElement('div');
    containerTemplate.className = 'metora-custom-price-container silent-mode active'; // Start in silent/active mode
    containerTemplate.innerHTML = '';
    
    // Find ALL injection points
    // We filter to ensure we're finding the main price container usually
    const possibleSelectors = [
        '.product__info-container .price', 
        '.product-single__meta .price',
        '.product__price', 
        '.price', 
        '[data-price]', 
        '.product-price', 
        '.price__container'
    ];
    
    let allPossiblePoints = document.querySelectorAll(possibleSelectors.join(','));
    console.log('🔍 Total elements matching price selectors:', allPossiblePoints.length);
    
    // Filter out prices inside our own container or cart/recommendations if needed
    // And ensure we don't inject multiple times into the same parent
    let validPoints = [];
    allPossiblePoints.forEach((point, idx) => {
        let reason = '';
        if (point.closest('.metora-custom-price-container')) reason = 'inside-metora';
        else if (point.closest('.cart-drawer')) reason = 'cart-drawer-class';
        else if (point.closest('cart-drawer')) reason = 'cart-drawer-tag';
        else if (point.closest('.cart-notification') || point.closest('cart-notification')) reason = 'cart-notification';
        else if (point.closest('.cart-items')) reason = 'cart-items';
        else if (point.closest('.related-products')) reason = 'related-products';
        else if (point.closest('.product-recommendations')) reason = 'recommendations';
        
        const mainContainer = point.closest('.product__info-container, .product-single__meta, .main-product, #ProductSection');
        const form = point.closest('form[action*="/cart/add"]');
        if (!mainContainer && !form) reason = 'not-in-main-product';
        
        if (point.parentElement.querySelector('.metora-custom-price-container')) reason = 'duplicate-in-parent';

        if (reason) {
            // console.log(`  Filtered point ${idx}: ${reason}`, point);
            return;
        }
        
        validPoints.push(point);
    });

    // Fallback if no specific price points found
    if (validPoints.length === 0) {
        console.log('⚠️ No primary price points found, trying fallbacks...');
        const mainContainer = document.querySelector('.product__info-container, .product-single__meta, .main-product, #ProductSection');
        
        // Try to find the price inside the product form specifically
        const formPrice = (mainContainer || document).querySelector('form[action*="/cart/add"] .price, form[action*="/cart/add"] [data-price]');
        
        if (formPrice && !formPrice.closest('.metora-custom-price-container')) {
            validPoints.push(formPrice);
        } else {
             const addToCart = (mainContainer || document).querySelector('button[name="add"], .product-form__submit');
             if (addToCart && !addToCart.closest('.cart-drawer')) validPoints.push(addToCart);
        }
    }
    
    if (validPoints.length > 0) {
        console.log('📍 Found', validPoints.length, 'injection points for price');
        validPoints.forEach(point => {
            const container = containerTemplate.cloneNode(true);
            
            // Hide original immediately if it's a price element (not a button/form)
            // Hide original immediately if it's a price element (not a button/form)
            if (point.tagName !== 'BUTTON' && point.tagName !== 'FORM') {
                 point.style.display = 'none';
                 point.classList.add('metora-temporarily-hidden');
                 point.classList.add('metora-processed'); // STOP Collection Script from touching this
            }

            // Logic to insert after price or before button
            if (point.tagName === 'BUTTON' || point.tagName === 'FORM') {
                 point.insertBefore(container, point.firstChild);
            } else {
                 point.parentNode.insertBefore(container, point.nextSibling);
            }
        });

    } else {
        // Absolute fallback
        const container = containerTemplate.cloneNode(true);
        document.body.insertBefore(container, document.body.firstChild);
        container.style.position = 'sticky';
        container.style.top = '10px';
        container.style.zIndex = '1000';
        console.log('⚠️ No points found, injected at top of body');
    }

    // 🔓 UNLOCK: Always remove global hide style after setup attempt
    const initialHide = document.getElementById('metora-initial-hide');
    if (initialHide) initialHide.remove();
    console.log('🔓 Released global hide lock (PDP)');



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
          // Check if it's actually a discount
          if (parseFloat(data.custom_price) >= parseFloat(data.original_price)) {
             console.log('ℹ️ Custom price >= Original, applying silent override');
             displaySilentPrice(data);
          } else {
             console.log('🎉 Custom price found!');
             displayCustomPrice(data);
          }
        } else {
          console.log('ℹ️ No custom price');
          hideCustomPrice();
        }
      } catch (error) {
        console.error('❌ Error:', error);
        hideCustomPrice();
      }
    }

    function displaySilentPrice(data) {
        const currencySymbol = getCurrencySymbol(CONFIG.currency);
        const containers = document.querySelectorAll('.metora-custom-price-container');
        
        containers.forEach(container => {
            // Hide siblings (original price logic)
            let sibling = container.previousElementSibling || container.nextElementSibling;
            if (sibling && (sibling.classList.contains('price') || sibling.querySelector('.price') || sibling.tagName === 'SPAN')) {
                sibling.style.display = 'none'; 
                sibling.classList.add('metora-hidden-original');
            }

            // 🛡️ PDP Silent Mode: Force Visibility & Robust Styles (Same as Collection)
            container.classList.add('silent-mode');
            container.classList.add('active');
            
            container.style.cssText = 'display: inline-block !important; width: auto !important; height: auto !important; opacity: 1 !important; visibility: visible !important; background: transparent !important; padding: 0 !important; margin: 0 !important; border: none !important; color: inherit !important; font-size: inherit !important; font-weight: bold !important; line-height: normal !important;';
            
            // Direct Text Node
            container.textContent = currencySymbol + parseFloat(data.custom_price).toFixed(2);
        });
    }

    function displayCustomPrice(data) {
      const discount = Math.round(((data.original_price - data.custom_price) / data.original_price) * 100);
      const currencySymbol = getCurrencySymbol(CONFIG.currency);
      
      const containers = document.querySelectorAll('.metora-custom-price-container');
      
      containers.forEach(container => {
          // Restore non-silent mode and Original Price visibility if we hid it
          container.classList.remove('silent-mode');
          
          // Re-show sibling if we previously hid it (optional, depending on design preference)
          // For now, let's keep it consistent: If we hid it for silent mode, we might want to show it for Green Box mode
          // UNLESS Green Box is meant to replace it too. 
          // Current behavior: Green Box adds to it. So we should probably restore it.
          let sibling = container.previousElementSibling || container.nextElementSibling;
          if (sibling && sibling.classList.contains('metora-hidden-original')) {
              sibling.style.display = ''; // Restore default
              sibling.classList.remove('metora-hidden-original');
          }

          container.innerHTML = '<div class="custom-price-header">✨ Special Price for You</div><div class="custom-price-main"><span class="custom-price-value">' + currencySymbol + parseFloat(data.custom_price).toFixed(2) + '</span><span class="custom-price-original">' + currencySymbol + parseFloat(data.original_price).toFixed(2) + '</span><span class="custom-price-badge">' + discount + '% OFF</span></div>';
          
          container.classList.add('active');
          container.style.display = 'block';
          container.style.visibility = 'visible';
          container.style.opacity = '1';
      });
      
      console.log('✅ Price displayed in', containers.length, 'containers');
      containers.forEach((c, idx) => {
          const style = window.getComputedStyle(c);
          const isVisible = c.offsetParent !== null;
          console.log(`  Container ${idx}: visible=${isVisible}, height=${c.offsetHeight}, zIndex=${style.zIndex}, display=${style.display}`);
          
          if (!isVisible && style.display !== 'none') {
             console.log(`  🔍 Checking why Container ${idx} is hidden...`);
             let p = c.parentElement;
             while (p && p !== document.body) {
                 const ps = window.getComputedStyle(p);
                 if (ps.display === 'none' || ps.visibility === 'hidden' || ps.opacity === '0') {
                     console.log(`    ❌ Parent ${p.tagName}.${p.className || ''} is hiding us! (display: ${ps.display}, visibility: ${ps.visibility}, opacity: ${ps.opacity})`);
                     if (p.classList.contains('metora-temporarily-hidden')) {
                         console.log('      🛠️ Forcing parent visibility (Metora class detected)...');
                         p.style.setProperty('display', 'block', 'important');
                         p.style.setProperty('visibility', 'visible', 'important');
                         p.style.setProperty('opacity', '1', 'important');
                     }
                 }
                 p = p.parentElement;
             }
          }
      });
    }

    function hideCustomPrice() {
      const containers = document.querySelectorAll('.metora-custom-price-container');
      containers.forEach(c => c.style.display = 'none');
      
      // Restore original prices if they were hidden (either initially or by silent mode)
      const hiddenOriginals = document.querySelectorAll('.metora-temporarily-hidden, .metora-hidden-original');
      hiddenOriginals.forEach(el => {
          el.style.display = '';
          el.classList.remove('metora-temporarily-hidden');
          el.classList.remove('metora-hidden-original');
      });
    }

    // Listen for variant changes
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
            checkCustomPrice(currentVariantId);
          }
        }, 100);
      });
    });

    // Observer for theme script price overrides (Dawn, etc.)
    const pdpObserver = new MutationObserver((mutations) => {
        let needsRecheck = false;
        mutations.forEach(mutation => {
            if (mutation.type === 'childList') {
                mutation.removedNodes.forEach(node => {
                    if (node.classList && node.classList.contains('metora-custom-price-container')) {
                        needsRecheck = true;
                    }
                });
                mutation.addedNodes.forEach(node => {
                    if (node.nodeType === 1 && !node.classList.contains('metora-custom-price-container')) {
                        // If theme re-adds a price element, we need to hide it
                        if (node.matches('.price, [data-price], .product__price')) {
                            needsRecheck = true;
                        }
                    }
                });
            }
        });

        if (needsRecheck) {
            console.log('🔄 Theme DOM change detected, re-applying custom price...');
            // Avoid loops by disconnecting temporarily if needed, 
            // but checkCustomPrice has internal guards too.
            checkCustomPrice(currentVariantId);
        }
    });

    // Start observing the product info container or body
    const productInfo = document.querySelector('.product__info-container, .product-single__meta, .main-product');
    if (productInfo) {
        pdpObserver.observe(productInfo, { childList: true, subtree: true });
    } else {
        pdpObserver.observe(document.body, { childList: true, subtree: true });
    }

    // Expose manual refresh for user debugging
    window.metoraManualRefreshPrice = function() {
        console.log('🛠️ Manual refresh triggered');
        checkCustomPrice(currentVariantId);
        if (typeof initGridPricing === 'function') initGridPricing();
    };

    // Check initial variant
    checkCustomPrice(currentVariantId);
    console.log('✨ Product page pricing initialized');
  }

  // ============================================
  // 2. PRODUCT GRID PRICING (Universal)
  // ============================================
  
  // Throttle helper
  function debounce(func, wait) {
    let timeout;
    return function() {
      const context = this, args = arguments;
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(context, args), wait);
    };
  }

  const debouncedInitGrid = debounce(initGridPricing, 300);

  function initGridPricing() {
      console.log('🔍 Scanning for product cards...');
      const productCards = findProductCards();
      console.log('📦 Found ' + productCards.length + ' potential cards');
      
      productCards.forEach(function(card, index) {
        // Self-Healing & Skip logic
        if (card.classList.contains('metora-processed')) {
            if (!card.querySelector('.metora-custom-price-container')) {
                card.classList.remove('metora-processed');
            } else {
                return; 
            }
        }
        
        card.classList.add('metora-processed');
        processCard(card, index);
      });
  }

  async function processCard(card, index) {
    let variantId = getVariantIdFromCard(card);
    
    if (variantId) {
      console.log('  🏷️ Card ' + index + ': Found Variant ID ' + variantId);
      await checkAndDisplayCustomPriceOnCard(card, variantId);
    } else {
      const productId = card.getAttribute('data-product-id') || card.getAttribute('data-id');
      if (productId) {
        console.log('  🏷️ Card ' + index + ': Fetching variant for Product ID ' + productId);
        variantId = await getFirstVariantFromProduct(card, productId);
        if (variantId) {
          await checkAndDisplayCustomPriceOnCard(card, variantId);
        }
      }
    }
  }

  async function getFirstVariantFromProduct(card, productId) {
    const productLink = card.querySelector('a[href*="/products/"]');
    if (!productLink) return null;

    const match = productLink.href.match(/\/products\/([^?#/]+)/);
    if (!match) return null;

    const productHandle = match[1];

    try {
      const response = await fetch('/products/' + productHandle + '.js');
      if (response.ok) {
        const productData = await response.json();
        if (productData.variants && productData.variants.length > 0) {
          return productData.variants[0].id;
        }
      }
    } catch (error) {}
    return null;
  }

  function findProductCards() {
    // Aggressive list of common shopify grid item selectors
    const selectors = [
      '.product-card',
      '.product-item',
      '.grid__item',
      '.card-wrapper', // Dawn
      '.card', // Dawn/Generic
      '[data-product-id]',
      '.product-grid-item',
      '.product',
      'li[class*="product"]',
      'div[class*="product-card"]'
    ];

    let allFound = [];
    selectors.forEach(sel => {
        const items = document.querySelectorAll(sel);
        items.forEach(item => {
            // Filter: Ensure it contains a price or a product link
            if (item.querySelector('.price, [class*="price"], a[href*="/products/"]')) {
                if (!allFound.includes(item)) allFound.push(item);
            }
        });
    });

    return allFound;
  }

  function getVariantIdFromCard(card) {
    // 1. Precise data attributes
    let element = card.querySelector('[data-variant-id]') || card.querySelector('[data-id]');
    if (element && element.getAttribute('data-variant-id')) return element.getAttribute('data-variant-id');
    if (element && element.getAttribute('data-id') && !isNaN(element.getAttribute('data-id'))) return element.getAttribute('data-id');

    // 2. Select/Input (for variant switchers in grids)
    element = card.querySelector('input[name="id"], select[name="id"], input[type="hidden"][value]');
    if (element && element.name === 'id' && element.value) return element.value;

    // 3. URL parameters
    element = card.querySelector('a[href*="variant="]');
    if (element) {
      const match = element.href.match(/variant=(\d+)/);
      if (match) return match[1];
    }

    // 4. JSON metadata
    const scripts = card.querySelectorAll('script[type="application/json"]');
    for (let i = 0; i < scripts.length; i++) {
      try {
        const data = JSON.parse(scripts[i].textContent);
        if (data.variants && data.variants[0] && data.variants[0].id) return data.variants[0].id;
        if (data.id && !data.handle) return data.id; // Single variant object
      } catch (e) {}
    }

    return null;
  }

  async function checkAndDisplayCustomPriceOnCard(card, variantId) {
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

      if (!response.ok) return;
      const data = await response.json();

      if (data.has_custom_price) {
        const custom = parseFloat(data.custom_price);
        const original = parseFloat(data.original_price);
        if (custom >= original) {
            displaySilentPriceOnCard(card, data);
        } else {
            displayCustomPriceOnCard(card, data);
        }
      }
    } catch (error) {}
  }

  function displaySilentPriceOnCard(card, data) {
       const symbol = getCurrencySymbol(CONFIG.currency);
       // Find original price elements to hide
       const priceEls = card.querySelectorAll('.price, .money, [class*="price"]:not(.metora-custom-price-container)');
       
       priceEls.forEach(el => {
           if (el.closest('.metora-custom-price-container')) return;
           el.style.display = 'none';
           el.classList.add('metora-hidden-original');
       });

       // Create or update our container
       let container = card.querySelector('.metora-custom-price-container');
       if (!container) {
           container = document.createElement('div');
           container.className = 'metora-custom-price-container silent-mode active';
           const insertPoint = card.querySelector('.price, [class*="price"], .card__content') || card.firstChild;
           insertPoint.parentNode.insertBefore(container, insertPoint);
       }
       
       container.textContent = symbol + parseFloat(data.custom_price).toFixed(2);
  }

  function displayCustomPriceOnCard(card, data) {
    const symbol = getCurrencySymbol(CONFIG.currency);
    const discount = Math.round(((data.original_price - data.custom_price) / data.original_price) * 100);
    
    // Hide theme prices
    const priceEls = card.querySelectorAll('.price, .money, [class*="price"]:not(.metora-custom-price-container)');
    priceEls.forEach(el => {
        if (el.closest('.metora-custom-price-container')) return;
        el.style.display = 'none';
        el.classList.add('metora-hidden-original');
    });

    let container = card.querySelector('.metora-custom-price-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'metora-custom-price-container active';
        const insertPoint = card.querySelector('.price, [class*="price"], .card__content') || card.firstChild;
        insertPoint.parentNode.insertBefore(container, insertPoint);
    }

    container.innerHTML = '<div class="custom-price-header">✨ Your Special Price</div>' +
                          '<div class="custom-price-main">' +
                          '<span class="custom-price-value">' + symbol + parseFloat(data.custom_price).toFixed(2) + '</span>' +
                          '<span class="custom-price-original">' + symbol + parseFloat(data.original_price).toFixed(2) + '</span>' +
                          '<span class="custom-price-badge">' + discount + '% OFF</span>' +
                          '</div>';
  }

  // **Grid MutationObserver for AJAX content**
  const gridObserver = new MutationObserver((mutations) => {
      let shouldRefresh = false;
      mutations.forEach(m => {
          if (m.addedNodes.length > 0) shouldRefresh = true;
      });
      if (shouldRefresh) debouncedInitGrid();
  });

  const gridContainer = document.querySelector('.grid, #product-grid, .products, .collection-page, #CollectionContainer');
  if (gridContainer) {
      gridObserver.observe(gridContainer, { childList: true, subtree: true });
  } else {
      gridObserver.observe(document.body, { childList: true, subtree: false }); // Light check on body
  }

  // Initial Run
  setTimeout(initGridPricing, 200);
  
  // Backup interval for stubborn themes
  setInterval(initGridPricing, 5000);

  console.log('✨ Universal grid pricing initialized');


  // ============================================
  // SHARED UTILITIES
  // ============================================
  function getCurrencySymbol(currency) {
    const symbols = {
      'USD': '$', 'EUR': '€', 'GBP': '£', 'INR': '₹', 'CAD': '$', 'AUD': '$'
    };
    return symbols[currency] || currency + ' ';
  }

  console.log('✨ Unified custom pricing fully initialized');

})();
