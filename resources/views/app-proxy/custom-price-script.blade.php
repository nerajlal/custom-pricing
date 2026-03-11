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
        const url = `${path}/identify-customer?shop=${window.Shopify.shop}`;
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

    // Expose manual refresh for user debugging
    window.metoraManualRefreshPrice = function() {
        console.log('🛠️ Manual refresh triggered');
        if (typeof checkCustomPrice === 'function' && typeof currentVariantId !== 'undefined') checkCustomPrice(currentVariantId);
        if (typeof initGridPricing === 'function') initGridPricing();
    };

    window.metoraDebugGrid = function() {
        if (typeof findProductCards !== 'function') {
            console.log('❌ Grid functions not available on this page');
            return;
        }
        const cards = findProductCards();
        console.log('🔍 Grid Debug:', {
            found_cards: cards.length,
            is_product_page: typeof isProductPage !== 'undefined' ? isProductPage : 'unknown'
        });
        cards.forEach((card, i) => {
            console.log(`Card[${i}]:`, {
                element: card,
                variant_id: typeof getVariantIdFromCard === 'function' ? getVariantIdFromCard(card) : 'unknown',
                product_id: card.getAttribute('data-product-id')
            });
        });
    };

  // **EXECUTION LOGIC**
  if (!window.location.pathname.includes('/cart')) {
      console.log('🚀 Initializing unified pricing for non-cart page...');
      // Start the actual logic
      setTimeout(startUnifiedPricing, 100); 
  } else {
      console.log('⏭️ Skipping unified script execution on cart page (debug functions remain available)');
  }

  function startUnifiedPricing() {
      // Check initial variant
      if (typeof checkCustomPrice === 'function') {
          checkCustomPrice(currentVariantId);
          console.log('✨ Product page pricing initialized');
      }
      
      // Grid pricing
      if (typeof initGridPricing === 'function') {
          initGridPricing();
      }
  }

  // Helper functions and definitions follow (accessible to metoraDebugGrid)

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

    // (Moved to top for global availability)

    // Check initial variant
    checkCustomPrice(currentVariantId);
    console.log('✨ Product page pricing initialized');
  }

  // ============================================
  // 2. PRODUCT GRID PRICING (Universal)
  // ============================================
  // Runs on ALL pages except Cart
  function initGridPricing() {
      const productCards = findProductCards();
      
      productCards.forEach(function(card, index) {
        // Self-Healing: If processed but container missing (Theme Wipe), reset.
        if (card.getAttribute('data-metora-processed')) {
            if (!card.querySelector('.metora-custom-price-container')) {
                card.removeAttribute('data-metora-processed');
            } else {
                return; // Truly processed and healthy
            }
        }
        
        card.setAttribute('data-metora-processed', 'true');
        processCard(card, index);
      });
      
      // Safety: Unhide original prices after a short delay
      const initialHide = document.getElementById('metora-initial-hide');
      if (initialHide) {
           setTimeout(function() {
              const hide = document.getElementById('metora-initial-hide');
              if (hide) {
                  console.log('🔓 Safe unhide triggered');
                  hide.remove();
              }
          }, 1000); 
      }
  }

  // Define Grid Helper Functions in shared scope
  async function processCard(card, index) {
    let variantId = getVariantIdFromCard(card);
    
    if (variantId) {
      await checkAndDisplayCustomPriceOnCard(card, variantId);
    } else {
      const productId = card.getAttribute('data-product-id');
      if (productId) {
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
      const urls = [
        '/products/' + productHandle + '.js',
        window.location.origin + '/products/' + productHandle + '.js'
      ];

      for (let url of urls) {
        try {
          const response = await fetch(url);
          if (response.ok) {
            const productData = await response.json();
            if (productData.variants && productData.variants.length > 0) {
              return productData.variants[0].id;
            }
          }
        } catch (e) {}
      }
    } catch (error) {}

    const cardHTML = card.outerHTML;
    const variantMatch = cardHTML.match(/variant['":\s]+(\d{10,})/i);
    if (variantMatch) return variantMatch[1];

    return null;
  }

  function findProductCards() {
    let allCards = new Set();
    
    const selectors = [
      '.product-card',
      '.product-item',
      '.grid__item',
      '[data-product-id]',
      '.product-grid-item',
      '.product',
      '.collection-product-card',
      'li[class*="product"]',
      'div[class*="product-card"]',
      '.card-wrapper', // Dawn specific
      '.card' // Broad but common
    ];

    selectors.forEach(selector => {
      document.querySelectorAll(selector).forEach(el => allCards.add(el));
    });

    if (allCards.size === 0) {
      const productLinks = document.querySelectorAll('a[href*="/products/"]');
      productLinks.forEach(function(link) {
        let parent = link.parentElement;
        let depth = 0;
        while (parent && depth < 5) {
          if (parent.querySelector('.price, [data-price]')) {
            allCards.add(parent);
            break;
          }
          parent = parent.parentElement;
          depth++;
        }
      });
    }

    // Filter out cards that are actually the PDP main info
    return Array.from(allCards).filter(card => {
        return !card.closest('.product__info-container, .product-single__meta');
    });
  }

  function getVariantIdFromCard(card) {
    let element = card.querySelector('[data-variant-id], [data-id], [data-variant]');
    if (element) return element.getAttribute('data-variant-id') || element.getAttribute('data-id') || element.getAttribute('data-variant');

    // Check card attributes itself
    if (card.hasAttribute('data-variant-id')) return card.getAttribute('data-variant-id');
    if (card.hasAttribute('data-id')) return card.getAttribute('data-id');

    element = card.querySelector('input[name="id"], select[name="id"]');
    if (element && element.value) return element.value;

    element = card.querySelector('a[href*="variant="]');
    if (element) {
      const match = element.href.match(/variant=(\d+)/);
      if (match) return match[1];
    }

    const scripts = card.querySelectorAll('script[type="application/json"]');
    for (let i = 0; i < scripts.length; i++) {
      try {
        const data = JSON.parse(scripts[i].textContent);
        if (data.variants && data.variants[0] && data.variants[0].id) {
          return data.variants[0].id;
        }
        if (data.id) return data.id; // Single variant product JSON
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
        const diff = original - custom;

        if (custom >= original || diff <= 0.01) {
            displaySilentPriceOnCard(card, data);
        } else {
            displayCustomPriceOnCard(card, data);
        }
      }
    } catch (error) {}
  }

  function displaySilentPriceOnCard(card, data) {
       const currencySymbol = getCurrencySymbol(CONFIG.currency);
       const priceElements = card.querySelectorAll('.price, .product-price, [data-price], .price__regular, .price-item, .money, [class*="price"]');
       
       priceElements.forEach(function(priceEl) {
          if (priceEl.classList.contains('metora-processed')) return;
          if (priceEl.closest('.metora-custom-price-container')) return;
          if (priceEl.previousElementSibling && priceEl.previousElementSibling.classList.contains('metora-custom-price-container')) return;

          priceEl.classList.add('metora-processed');
          priceEl.style.display = 'none';

          const container = document.createElement('div');
          container.className = 'metora-custom-price-container silent-mode active';
          container.textContent = currencySymbol + parseFloat(data.custom_price).toFixed(2);
          
          priceEl.parentElement.insertBefore(container, priceEl);
       });
  }

  function displayCustomPriceOnCard(card, data) {
    const currencySymbol = getCurrencySymbol(CONFIG.currency);
    const discount = Math.round(((data.original_price - data.custom_price) / data.original_price) * 100);
    const priceElements = card.querySelectorAll('.price, .product-price, [data-price], .price__regular, .price-item, .money, [class*="price"]');
    
    priceElements.forEach(function(priceEl) {
      if (priceEl.classList.contains('metora-processed')) return;
      if (priceEl.closest('.metora-custom-price-container')) return;

      priceEl.classList.add('metora-processed');
      priceEl.style.display = 'none';

      const container = document.createElement('div');
      container.className = 'metora-custom-price-container active';
      container.innerHTML = '<div class="custom-price-header">✨ Your Special Price</div><div class="custom-price-main"><span class="custom-price-value">' + currencySymbol + parseFloat(data.custom_price).toFixed(2) + '</span><span class="custom-price-original">' + currencySymbol + parseFloat(data.original_price).toFixed(2) + '</span><span class="custom-price-badge">' + discount + '% OFF</span></div>';

      priceEl.parentElement.insertBefore(container, priceEl);
    });
  }

  // Run Grid Pricing site-wide
  setTimeout(initGridPricing, 100);
  setInterval(initGridPricing, 3000); // Slower interval for grid to save battery
  console.log('✨ Universal grid pricing initialized');


  // ============================================
  // 3. CART PAGE
  // ============================================
  if (isCartPage) {
    console.log('🛒 Initializing Cart Page Pricing...');

    window.metoraCustomPrices = window.metoraCustomPrices || {};

    const cartStyles = document.createElement('style');
    cartStyles.textContent = `
      .metora-custom-price-badge {
        background: #dcfce7 !important;
        border: 2px solid #10b981 !important;
        border-radius: 8px !important;
        padding: 8px 12px !important;
        display: inline-block !important;
        margin: 4px 0 !important;
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
      }
    `;
    document.head.appendChild(cartStyles);

    setTimeout(function() {
      processCart();
    }, 500);

    async function processCart() {
      try {
        const response = await fetch('/cart.js');
        const cart = await response.json();
        
        console.log('📦 Cart items:', cart.items.length);

        for (let i = 0; i < cart.items.length; i++) {
          const item = cart.items[i];
          console.log('Processing item', i + 1, '- Variant:', item.variant_id);
          await fetchAndApplyCustomPrice(item.variant_id, item);
        }

        setTimeout(function() {
          updateAllCartDisplays();
        }, 1000);

      } catch (error) {
        console.error('❌ Error:', error);
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

        if (!response.ok) return;

        const data = await response.json();

        if (data.has_custom_price) {
          // Check discount
          if (parseFloat(data.custom_price) >= parseFloat(data.original_price)) {
              // Allow logic to proceed, update UI logic elsewhere if needed
          }

          console.log('  🎉 Custom price found');
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
      const variantIds = Object.keys(window.metoraCustomPrices);
      
      if (variantIds.length === 0) {
        console.log('⚠️ No custom prices');
        return;
      }

      console.log('🎨 Updating', variantIds.length, 'cart items');

      variantIds.forEach(function(variantId) {
        const priceData = window.metoraCustomPrices[variantId];
        updateCartItemDisplay(variantId, priceData);
      });

      console.log('✅ Cart updated');
    }

    function updateCartItemDisplay(variantId, priceData) {
      const symbol = getCurrencySymbol(CONFIG.currency);
      const originalPrice = priceData.original / 100;
      const customPrice = priceData.custom / 100;
      const quantity = priceData.quantity;
      const lineTotal = customPrice * quantity;
      const originalLineTotal = originalPrice * quantity;

      const cartContainers = document.querySelectorAll('.cart, .cart-items, #cart, [data-cart]');

      cartContainers.forEach(function(cartContainer) {
        const cartItems = cartContainer.querySelectorAll('tr, .cart-item, .cart__item, [class*="cart-item"]');

        cartItems.forEach(function(item) {
          const html = item.outerHTML;
          const isThisVariant = html.includes('variant-id="' + variantId + '"') ||
                               html.includes('data-variant-id="' + variantId + '"') ||
                               html.includes('variant=' + variantId) ||
                               item.getAttribute('data-variant-id') === variantId;

          if (!isThisVariant) return;

          console.log('  ✓ Found cart item for variant:', variantId);
          updatePricesInElement(item, originalPrice, customPrice, lineTotal, originalLineTotal, symbol);
        });
      });
    }

    function updatePricesInElement(element, originalPrice, customPrice, lineTotal, originalLineTotal, symbol) {
      const priceElements = element.querySelectorAll('.price:not(.metora-updated), .money:not(.metora-updated)');

      let unitPriceUpdated = false;
      let lineTotalUpdated = false;

      priceElements.forEach(function(priceEl) {
        const priceText = priceEl.textContent.replace(/[^\d.]/g, '');
        const priceValue = parseFloat(priceText);

        if (Math.abs(priceValue - originalPrice) < 0.01 && !unitPriceUpdated) {
          priceEl.classList.add('metora-updated');
          priceEl.innerHTML = createCustomPriceBadge(customPrice, originalPrice, symbol);
          unitPriceUpdated = true;
        }
        else if (Math.abs(priceValue - originalLineTotal) < 0.01 && !lineTotalUpdated) {
          priceEl.classList.add('metora-updated');
          
          if (lineTotal >= originalLineTotal) {
             priceEl.innerHTML = '<span class="metora-custom-price-value" style="color: inherit !important; font-size: inherit !important;">' + symbol + lineTotal.toFixed(2) + '</span>';
          } else {
             priceEl.innerHTML = '<span class="metora-custom-price-value">' + symbol + lineTotal.toFixed(2) + '</span><span class="metora-original-price-strike">' + symbol + originalLineTotal.toFixed(2) + '</span>';
          }
          
          lineTotalUpdated = true;
        }
      });
    }

    function createCustomPriceBadge(customPrice, originalPrice, symbol) {
      const savings = originalPrice - customPrice;
      
      // Silent Override if no savings
      if (savings <= 0.01) {
          return '<span class="metora-custom-price-value" style="color: inherit !important; font-size: inherit !important;">' + symbol + customPrice.toFixed(2) + '</span>';
      }

      return '<div class="metora-custom-price-badge">' +
             '<div style="font-size: 11px; color: #059669; font-weight: 600;">✨ Your Special Price</div>' +
             '<div>' +
             '<span class="metora-custom-price-value">' + symbol + customPrice.toFixed(2) + '</span>' +
             '<span class="metora-original-price-strike">' + symbol + originalPrice.toFixed(2) + '</span>' +
             '<span class="metora-savings-badge">Save ' + symbol + savings.toFixed(2) + '</span>' +
             '</div>' +
             '</div>';
    }

    console.log('✨ Cart pricing initialized');
  }

  // ============================================
  // SHARED UTILITIES
  // ============================================
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

  console.log('✨ Unified custom pricing fully initialized');

})();
