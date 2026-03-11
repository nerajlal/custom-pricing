(function() {
  'use strict';
  
  console.log('🛒 Complete Cart & Checkout Handler Loaded');

  // Robust Customer ID Detection
  function detectCustomerId() {
    // Method 1: window.__st.cid (Shopify Analytics - Most Reliable)
    if (window.__st && window.__st.cid) {
        console.log('✅ Found customer ID in __st.cid:', window.__st.cid);
        return parseInt(window.__st.cid);
    }
    
    // Method 2: window.Shopify.customer
    if (window.Shopify && window.Shopify.customer && window.Shopify.customer.id) {
        console.log('✅ Found customer ID in Shopify.customer object');
        return window.Shopify.customer.id;
    }
    
    // Method 3: Meta tags
    const metaSelectors = ['meta[name="shopify-customer-id"]', 'meta[name="customer-id"]'];
    for (const selector of metaSelectors) {
        const meta = document.querySelector(selector);
        if (meta && meta.content) {
            console.log('✅ Found customer ID in meta tag:', selector);
            return meta.content;
        }
    }
    
  // Method 4: Check for Logout link (implies logged in)
    if (document.querySelector('a[href*="/account/logout"]')) {
       console.log('✅ Found Logout link (User is logged in)');
       // Return a placeholder or just rely on the 'shouldHide' check if we separate it.
       // For consistency with main script, let's keep it null here but handle below.
    }
    
    return null;
  }

  const customerId = detectCustomerId();
  const hasLogoutLink = !!document.querySelector('a[href*="/account/logout"]');
  const shouldHidePrices = customerId || hasLogoutLink;

  // ⚡ IMMEDIATE HIDE (Cart Script): Prevents flash
  if (shouldHidePrices) {
      if (!document.getElementById('metora-cart-hide')) {
          const hideStyle = document.createElement('style');
          hideStyle.id = 'metora-cart-hide'; // Different ID to avoid conflict/double removal issues
          hideStyle.textContent = `
              .cart-item .price, 
              .cart__price, 
              .price, 
              .money, 
              .product-price,
              [data-cart-item-price],
              .cart__subtotal,
              .totals__subtotal-value,
              .line-item-price { 
                  opacity: 0 !important; 
                  visibility: hidden !important; 
              }
          `;
          document.head.appendChild(hideStyle);
          console.log('🙈 Cart prices temporarily hidden');
      }
  }
  
  if (!customerId) {
    console.log('⚠️ No customer logged in');
    // Safety unhide if we hid it but no ID
    const cartHide = document.getElementById('metora-cart-hide');
    if (cartHide) cartHide.remove();
    return;
  }

  console.log('👤 Customer ID:', customerId);

  // Check if we are on cart page OR if a cart drawer is present
  const isCartPage = window.location.pathname.includes('/cart');
  let isDrawerOpen = false;
  
  // Broad detection for drawers
  const drawerSelectors = [
    '.cart-drawer', 
    '#CartDrawer', 
    '#cart-drawer', 
    '[data-role="cart-drawer"]',
    '.cart-notification',
    'cart-drawer'
  ];

  /*
  if (!isCartPage) {
     // Don't return yet! We need to watch for drawers.
     // console.log('⚠️ Not on cart page');
     // return;
  }
  */

  // Observer for Drawer Opening
  const bodyObserver = new MutationObserver((mutations) => {
    let shouldCheck = false;
    
    mutations.forEach(mutation => {
      if (mutation.type === 'childList') {
        mutation.addedNodes.forEach(node => {
           if (node.nodeType === 1) {
              if (drawerSelectors.some(sel => node.matches(sel) || node.querySelector(sel))) {
                  shouldCheck = true;
              }
           }
        });
      }
      if (mutation.type === 'attributes' && (mutation.attributeName === 'class' || mutation.attributeName === 'aria-hidden' || mutation.attributeName === 'open')) {
          if (drawerSelectors.some(sel => mutation.target.matches(sel) || mutation.target.closest(sel))) {
              shouldCheck = true;
          }
           // Check body classes for "cart-open" style classes
           if (document.body.classList.contains('cart-open') || document.body.classList.contains('overflow-hidden')) {
               shouldCheck = true;
           }
      }
    });

    if (shouldCheck) {
        console.log('🛒 Cart Drawer interaction detected!');
        if (!window.metoraInitComplete || !window.metoraUpdateInProgress) {
            refreshCartAndPrices();
        }
    }
  });

  bodyObserver.observe(document.body, { childList: true, verbose: false, subtree: true, attributes: true });

  const CONFIG = {
    apiUrl: '{{ rtrim(env("APP_URL"), "/") }}/api/storefront/custom-price',
    checkoutApiUrl: '{{ rtrim(env("APP_URL"), "/") }}/api/checkout/create',
    shop: window.Shopify.shop,
    currency: window.Shopify.currency.active
  };

  console.log('⚙️ Config:', CONFIG);

  // Global variables
  let symbol = window.Shopify && window.Shopify.currency ? (window.Shopify.currency.active === 'INR' ? '₹' : (window.Shopify.currency.active === 'USD' ? '$' : '')) : '$';
  if (!symbol) symbol = '$';
  
  // Store custom prices
  window.metoraCustomPrices = window.metoraCustomPrices || {};
  window.metoraUpdateInProgress = false;
  window.metoraInitComplete = false; // **NEW: Track if initialization is complete**

  const styles = document.createElement('style');
  styles.textContent = `
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
  document.head.appendChild(styles);

  // Initialize
  init();

  function init() {
    console.log('🚀 Initializing cart pricing and checkout...');
    
    // Initialize global symbol
    symbol = getCurrencySymbol(CONFIG.currency);
    
    // **CRITICAL: Initialize all flags**
    window.metoraUpdateInProgress = false;
    window.metoraRefreshInProgress = false;
    window.metoraCheckoutInProgress = false;
    window.metoraLoyaltyRefreshing = false;
    
    // Fetch custom prices
    fetchAllCustomPrices().then(function() {
      console.log('✅ All custom prices fetched, applying updates ONCE...');
      
      // Apply updates ONCE
      applyAllUpdates();
      
      // Setup handlers
      setupCheckoutInterceptor();
      setupQuantityWatchers();
      listenForCartUpdates();
      
      // Setup MutationObserver for persistent updates (detect AJAX cart changes)
      if (cartMutationObserver) {
          const cartContainer = document.querySelector('.cart, #cart, .cart-page, cart-items, .drawer__cart-items-wrapper');
          if (cartContainer) {
              cartMutationObserver.observe(cartContainer, {
                  childList: true,
                  subtree: true,
                  characterData: false
              });
              console.log('✅ Persistent MutationObserver active');
          }
      }
      
      console.log('✅ All handlers initialized');
      // Final absolute update to catch anything missed during initialization
      setTimeout(() => applyAllUpdates(true), 500);
    });
  }

  // ============================================
  // QUANTITY CHANGE DETECTION
  // ============================================
  function setupQuantityWatchers() {
    console.log('👀 Setting up quantity change watchers...');
    
    const quantityInputs = document.querySelectorAll('input[type="number"][name*="updates"], input[name*="quantity"]');
    
    console.log('  Found', quantityInputs.length, 'quantity inputs');
    
    quantityInputs.forEach(function(input) {
      input.addEventListener('change', handleQuantityChange);
      input.addEventListener('input', handleQuantityChange);
    });
    
    const quantityButtons = document.querySelectorAll('button[name*="plus"], button[name*="minus"], .quantity__button');
    
    console.log('  Found', quantityButtons.length, 'quantity buttons');
    
    quantityButtons.forEach(function(button) {
      button.addEventListener('click', function() {
        setTimeout(handleQuantityChange, 300);
      });
    });
  }

  let quantityChangeTimeout;
  function handleQuantityChange() {
    console.log('🔄 Quantity changed detected...');
    
    clearTimeout(quantityChangeTimeout);
    
    quantityChangeTimeout = setTimeout(function() {
      refreshCartAndPrices();
    }, 500);
  }

  async function refreshCartAndPrices() {
    console.log('🔄 Refreshing cart data and prices...');
    
    if (window.metoraRefreshInProgress) {
      console.log('⏸️ Refresh already in progress, skipping...');
      return;
    }
    
    window.metoraRefreshInProgress = true;
    
    try {
      const response = await fetch('/cart.js');
      const cart = await response.json();
      
      console.log('📦 Fresh cart items:', cart.items.length);
      
      cart.items.forEach(function(item) {
        const variantId = item.variant_id;
        
        if (window.metoraCustomPrices[variantId]) {
          console.log('  Updating quantity for variant', variantId, ':', window.metoraCustomPrices[variantId].quantity, '→', item.quantity);
          window.metoraCustomPrices[variantId].quantity = item.quantity;
        }
      });
      
      console.log('🧹 Cleaning up old price displays...');
      
      const cartContainers = '.cart-drawer, cart-drawer, .cart, .cart-notification, cart-notification, .drawer__inner, #CartDrawer';
      
      document.querySelectorAll(cartContainers).forEach(container => {
        container.querySelectorAll('.metora-custom-price-badge').forEach(badge => {
            if (!badge.closest('#metora-loyalty-widget')) badge.remove();
        });
        
        container.querySelectorAll('.metora-custom-price-value, .metora-original-price-strike, .metora-savings-badge').forEach(el => {
            if (!el.closest('#metora-loyalty-widget')) el.remove();
        });
      });
      
      document.querySelectorAll(cartContainers).forEach(container => {
        container.querySelectorAll('.metora-updated, .metora-total-updated').forEach(el => {
            if (!el.closest('#metora-loyalty-widget')) {
              el.classList.remove('metora-updated', 'metora-total-updated');
              el.removeAttribute('data-metora-updated');
              el.removeAttribute('data-metora-total-updated');
            }
        });
      });
      
      console.log('✅ Cleanup complete (loyalty widget preserved)');
      
      await new Promise(resolve => setTimeout(resolve, 100));
      
      applyAllUpdates();
      
      <!--setTimeout(function() {-->
      <!--    triggerLoyaltyRefresh();-->
      <!--}, 300);-->
      if (!window.metoraLoyaltyRefreshing && window.metoraInitComplete) {
            setTimeout(function() {
                triggerLoyaltyRefresh();
            }, 300);
        }

    } catch (error) {
      console.error('❌ Error refreshing cart:', error);
    } finally {
      setTimeout(function() {
        window.metoraRefreshInProgress = false;
      }, 1000);
    }
  }

  // ============================================
  // SHOPIFY CART UPDATE LISTENER
  // ============================================
  function listenForCartUpdates() {
    console.log('👂 Listening for Shopify cart updates...');
    
    document.addEventListener('cart:updated', function() {
      console.log('🛒 Shopify cart:updated event detected');
      refreshCartAndPrices();
    });
    
    const originalFetch = window.fetch;
    window.fetch = function() {
      const url = arguments[0];
      
      if (typeof url === 'string' && (url.includes('/cart/update') || url.includes('/cart/change') || url.includes('/cart/add'))) {
        console.log('🔍 Detected cart API call:', url);
        
        return originalFetch.apply(this, arguments).then(function(response) {
          if (response.ok) {
            setTimeout(function() {
              refreshCartAndPrices();
            }, 500);
          }
          return response;
        });
      }
      
      return originalFetch.apply(this, arguments);
    };
  }
  

  // ============================================
  // FETCH CUSTOM PRICES
  // ============================================
  async function fetchAllCustomPrices() {
    try {
      const response = await fetch('/cart.js');
      const cart = await response.json();
      
      console.log('📦 Cart items:', cart.items.length);

      for (let i = 0; i < cart.items.length; i++) {
        const item = cart.items[i];
        await fetchCustomPrice(item.variant_id, item);
      }

    } catch (error) {
      console.error('❌ Error:', error);
    }
  }

  async function fetchCustomPrice(variantId, cartItem) {
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
        console.log('  🎉 Custom price found for variant:', variantId);
        
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

  // ============================================
  // DISPLAY UPDATES
  // ============================================
  // Store observer globally so we can disconnect it
  let cartMutationObserver = null;

  function interceptCartRender() {
    console.log('🔧 Setting up cart render interceptor...');
    
    // Override Shopify.formatMoney if it exists
    if (window.Shopify && window.Shopify.formatMoney) {
      const originalFormatMoney = window.Shopify.formatMoney;
      
      window.Shopify.formatMoney = function(cents, format) {
        const price = cents / 100;
        
        // Check if this price matches any of our custom prices
        for (const variantId in window.metoraCustomPrices) {
          const priceData = window.metoraCustomPrices[variantId];
          
          if (Math.abs(price - priceData.original) < 1) {
            console.log('  🎯 Intercepted price format:', price, '→', priceData.custom);
            return originalFormatMoney.call(this, priceData.custom * 100, format);
          }
        }
        
        return originalFormatMoney.call(this, cents, format);
      };
      
      console.log('✅ formatMoney intercepted');
    }
    
    // **SIMPLIFIED: Only watch for MAJOR cart changes, not every mutation**
    let updateTimeout;
    let lastUpdateTime = 0;
    const UPDATE_THROTTLE = 2000; // Don't update more than once per 2 seconds
    
    cartMutationObserver = new MutationObserver(function(mutations) {
      // **CRITICAL: Don't trigger if we're already updating**
      if (window.metoraUpdateInProgress || 
          window.metoraRefreshInProgress || 
          window.metoraCheckoutInProgress ||
          window.metoraLoyaltyRefreshing) {
        return;
      }
      
      // **THROTTLE: Don't update too frequently**
      const now = Date.now();
      if (now - lastUpdateTime < UPDATE_THROTTLE) {
        console.log('⏸️ Throttling mutation observer (too soon)');
        return;
      }
      
      // **CRITICAL: Ignore mutations from our own updates OR from loyalty widget**
      let shouldUpdate = false;
      
      for (let i = 0; i < mutations.length; i++) {
        const mutation = mutations[i];
        
        // Skip if mutation is inside loyalty widget
        if (mutation.target.closest('#metora-loyalty-widget')) {
          continue;
        }
        
        // Skip if mutation is on our updated elements
        if (mutation.target.classList && 
            (mutation.target.classList.contains('metora-custom-price-badge') ||
             mutation.target.classList.contains('metora-custom-price-value') ||
             mutation.target.classList.contains('metora-updated') ||
             mutation.target.classList.contains('metora-total-updated') ||
             mutation.target.hasAttribute('data-metora-total-updated'))) {
          continue;
        }
        
        // Skip if added nodes are our custom elements
        if (mutation.addedNodes.length > 0) {
          let hasOurElements = false;
          mutation.addedNodes.forEach(function(node) {
            if (node.nodeType === 1 && node.classList && 
                (node.classList.contains('metora-custom-price-badge') ||
                 node.classList.contains('metora-custom-price-value') ||
                 node.id === 'metora-loyalty-widget')) {
              hasOurElements = true;
            }
          });
          if (hasOurElements) continue;
        }
        
        // **ONLY UPDATE FOR SIGNIFICANT CHANGES**
        // Check if this is a quantity change or cart structure change
        if (mutation.type === 'childList' && mutation.addedNodes.length > 0) {
          shouldUpdate = true;
          break;
        }
      }
      
      if (!shouldUpdate) {
        return;
      }
      
      console.log('🔔 Significant cart change detected, scheduling update...');
      
      clearTimeout(updateTimeout);
      updateTimeout = setTimeout(function() {
        lastUpdateTime = Date.now();
        applyAllUpdates();
      }, 1000); // Increased delay
    });
    
    const cartContainer = document.querySelector('.cart, #cart, .cart-page');
    if (cartContainer) {
      cartMutationObserver.observe(cartContainer, {
        childList: true,
        subtree: true,
        characterData: false // **CHANGED: Don't watch text changes**
      });
      console.log('✅ MutationObserver active on cart container');
    }
}

  function applyAllUpdates(force = false) {
    if (window.metoraUpdateInProgress && !force) {
      console.log('⏸️ Update already in progress, skipping (use force=true to override)...');
      return;
    }
    
    window.metoraUpdateInProgress = true;
    console.log('🎨 Applying all updates...');
    
    // **SAFETY CHECK: Remove any orphaned custom elements before updating**
    const orphanedBadges = document.querySelectorAll('.metora-custom-price-badge, .metora-custom-price-value, .metora-savings-badge');
    if (orphanedBadges.length > 0) {
      console.log('⚠️ Found', orphanedBadges.length, 'orphaned elements, removing...');
      orphanedBadges.forEach(function(el) {
        // Don't remove if it's inside a properly marked parent
        const parent = el.closest('.metora-updated');
        if (!parent || el.classList.contains('metora-custom-price-badge')) {
          if (el.parentElement && !el.parentElement.classList.contains('metora-updated')) {
            el.remove();
          }
        }
      });
    }
    
    Object.keys(window.metoraCustomPrices).forEach(function(variantId) {
      updateCartItem(variantId);
    });
    
    updateCartTotal();
    
    // 🔓 UNLOCK: Show prices now that custom prices are applied
    const initialHide = document.getElementById('metora-initial-hide');
    if (initialHide) {
        initialHide.remove();
        console.log('🔓 Released initial price hide');
    }
    
    const cartHide = document.getElementById('metora-cart-hide');
    if (cartHide) {
        cartHide.remove();
        console.log('🔓 Released cart price hide');
    }
    
    // Safety: Force all elements to be visible if they were hidden
    const selectors = '.price, .money, .cart__price, .product-option, .cart-item__price, [data-cart-item-price], .cart__subtotal, .totals__subtotal-value';
    const forceVisible = document.querySelectorAll(selectors);
    forceVisible.forEach(el => {
        if (el.closest('.cart, #cart, cart-drawer, .drawer__inner, .cart-items, .cart-drawer__items')) {
            el.style.setProperty('visibility', 'visible', 'important');
            el.style.setProperty('opacity', '1', 'important');
            el.style.setProperty('display', 'inline-block', 'important');
            el.classList.remove('hidden', 'hide', 'visually-hidden');
        }
    });

    // Special: Unhide even parents if they are hidden
    document.querySelectorAll('.metora-updated, .metora-total-updated').forEach(el => {
        let p = el.parentElement;
        while (p && !p.classList.contains('cart') && !p.classList.contains('cart-drawer')) {
            if (window.getComputedStyle(p).display === 'none') {
                p.style.setProperty('display', 'block', 'important');
            }
            p = p.parentElement;
        }
    });

    // Release the lock sooner or if forced
    const delay = force ? 100 : 1000;
    setTimeout(function() {
      window.metoraUpdateInProgress = false;
    }, delay);

    // **REINFORCEMENT LOOP**: Ensure changes stick for the next 2 seconds
    // This handles themes that re-render late or based on other script loads
    if (!window.metoraSticking) {
        window.metoraSticking = true;
        let count = 0;
        const stickLoop = setInterval(() => {
            count++;
            // Re-run unhider and total check
            document.querySelectorAll(selectors).forEach(el => {
                if (el.closest('.cart, #cart, cart-drawer, .drawer__inner, .cart-items')) {
                    el.style.setProperty('visibility', 'visible', 'important');
                    el.style.setProperty('opacity', '1', 'important');
                }
            });
            if (count > 5) {
                clearInterval(stickLoop);
                window.metoraSticking = false;
            }
        }, 500);
    }
  }

  // Expose manual refresh for cart page
  window.metoraManualRefreshPrice = function() {
      console.log('🛠️ Manual cart refresh triggered');
      window.metoraUpdateInProgress = false; // Emergency reset
      applyAllUpdates(true);
  };
  
  // ============================================
    // TRIGGER LOYALTY WIDGET REFRESH
    // ============================================
    function triggerLoyaltyRefresh() {
        console.log('📢 Triggering loyalty widget refresh...');
        
        window.metoraLoyaltyRefreshing = true;
        
        if (window.dispatchEvent) {
            window.dispatchEvent(new CustomEvent('metoraCartRefreshed'));
        }
        
        if (window.metoraLoyalty && typeof window.metoraLoyalty.refreshWidget === 'function') {
            setTimeout(function() {
                window.metoraLoyalty.refreshWidget();
                
                setTimeout(function() {
                    window.metoraLoyaltyRefreshing = false;
                    console.log('✅ Loyalty refresh complete');
                }, 500);
            }, 200);
        } else {
            setTimeout(function() {
                window.metoraLoyaltyRefreshing = false;
            }, 500);
        }
    }

  function updateCartItem(variantId) {
    const priceData = window.metoraCustomPrices[variantId];
    
    // Find the cart item row - try multiple selectors
    let row = document.querySelector(
      'tr.cart-items__table-row[data-key*="' + variantId + '"], ' +
      'tr[data-key*="' + variantId + '"], ' +
      '[data-variant-id="' + variantId + '"], ' +
      '[data-id="' + variantId + '"], ' +
      '.cart-item[data-variant-id="' + variantId + '"]'
    );
    
    // Fallback: search for any element that looks like a row and contains the variant ID in a link or data attribute
    if (!row) {
        const potentialRows = document.querySelectorAll('tr, .cart-item, [class*="cart-item"], .cart__item');
        for (let r of potentialRows) {
            if (r.innerHTML.includes('variant=' + variantId) || 
                r.innerHTML.includes('/' + variantId) ||
                r.getAttribute('data-id') == variantId ||
                r.getAttribute('data-variant-id') == variantId) {
                row = r;
                break;
            }
        }
    }
    
    if (!row) {
      console.log('  ⚠️ Row not found for variant:', variantId);
      return;
    }
    
    console.log('  ✅ Updating row for variant:', variantId);
    
    // **NEW: Safer Element Search**
    // Traverses down to find specific price elements instead of raw text replacement
    
    let unitPriceUpdated = false;
    let lineTotalUpdated = false;

    function findPriceElements(root) {
        if (!root) return;
        
        // Skip if already processed
        if (root.classList && (
            root.classList.contains('metora-updated') || 
            root.classList.contains('metora-custom-price-badge') ||
            root.closest('.metora-custom-price-badge') ||
            root.classList.contains('metora-silent-price-badge') ||
            root.closest('.metora-silent-price-badge')
        )) return;

        // Skip input fields
        if (root.tagName === 'INPUT' || root.tagName === 'BUTTON' || root.tagName === 'SELECT') return;

        // Check if this is a "leaf" or "near-leaf" node (likely to be a price container)
        // i.e., has no element children OR has very simple structure
        const children = root.children;
        const isLeaf = children.length === 0;
        
        if (isLeaf) {
            checkAndReplace(root);
        } else {
            // Recurse deeper
            Array.from(children).forEach(child => findPriceElements(child));
        }
    }
    
    function checkAndReplace(el) {
        // Remove the guards to update ALL matches in the row (e.g. mobile/desktop)
        // if (unitPriceUpdated && lineTotalUpdated) return; 
        
        const text = el.textContent.trim();
        if (!text) return;
        if (text.length > 20) return; // Skip long descriptions
        
        // Aggressive cleaning
        const numericMatch = text.match(/(\d[\d,.]*)/);
        if (!numericMatch) return;
        
        const numericValue = parseFloat(numericMatch[0].replace(/,/g, ''));
        if (isNaN(numericValue)) return;
        
        // Unit price match
        if (Math.abs(numericValue - priceData.original) < 0.1) {
           console.log('    ✅ Updating price element:', text, '->', priceData.custom);
           el.classList.add('metora-updated');
           el.innerHTML = createPriceBadge(priceData.custom, priceData.original, symbol);
           unitPriceUpdated = true;
        }
        // Line total match
        else if (Math.abs(numericValue - (priceData.original * priceData.quantity)) < 0.1) {
           console.log('    ✅ Updating total element:', text, '->', (priceData.custom * priceData.quantity));
           el.classList.add('metora-updated');
           
           const totalCustom = priceData.custom * priceData.quantity;
           const totalOriginal = priceData.original * priceData.quantity;
           
           if (totalCustom >= totalOriginal) {
                el.innerHTML = '<span class="metora-silent-price-badge" style="font-weight:700; color:inherit;">' + 
                 symbol + totalCustom.toFixed(2) + 
                 '</span>';
           } else {
                el.innerHTML = '<span class="metora-custom-price-value">' + 
                 symbol + totalCustom.toFixed(2) + 
                 '</span> <span class="metora-original-price-strike">' + 
                 symbol + totalOriginal.toFixed(2) + 
                 '</span>';
           }
           lineTotalUpdated = true;
        }
    }
    
    // Start traversal
    findPriceElements(row);

    if (!unitPriceUpdated && !lineTotalUpdated) {
         console.log('  ⚠️ Could not find price matching', priceData.original , 'in row via traversal. Trying broad search...');
         // Broad fallback: search all descendants
         row.querySelectorAll('*').forEach(checkAndReplace);
    }
  }

  window.metoraDebugCart = function() {
      console.log('🔍 Cart Debug:', {
          customerId: window.SHOPIFY_CUSTOMER_ID || customerId,
          pricesCount: Object.keys(window.metoraCustomPrices).length,
          updateInProgress: window.metoraUpdateInProgress
      });
      // Find all rows
      const rows = document.querySelectorAll('tr, .cart-item, [class*="cart-item"]');
      console.log(`Found ${rows.length} potential cart rows`);
      
      rows.forEach((row, i) => {
          const isItemRow = row.innerHTML.match(/\d{10,}/); // Simple regex for potential IDs
          if (isItemRow) {
              const updated = row.querySelectorAll('.metora-updated').length;
              const hidden = row.querySelectorAll('[style*="visibility: hidden"]').length;
              console.log(`Row[${i}]:`, {
                  element: row,
                  has_id: isItemRow[0],
                  updated_elements: updated,
                  hidden_elements: hidden
              });
          }
      });
  };

  // **NEW: Global set to track updated elements permanently**
  window.metoraUpdatedElements = window.metoraUpdatedElements || new Set();

  function updateCartTotal() {
    console.log('💰 Updating cart total...');
    
    // **CRITICAL: Temporarily disconnect observer while we update**
    if (cartMutationObserver) {
        cartMutationObserver.disconnect();
        console.log('⏸️ Temporarily disconnected observer for total update');
    }
    
    // Fetch Shopify's actual cart total
    fetch('/cart.js')
      .then(response => response.json())
      .then(cart => {
        const shopifyOriginalTotal = cart.total_price / 100;
        console.log('  💵 Shopify cart total:', shopifyOriginalTotal);
        
        // **NEW: Correct Calculation Logic**
        let newTotal = 0;
        let oldTotal = 0;
        
        cart.items.forEach(item => {
             const variantId = item.variant_id;
             const priceData = window.metoraCustomPrices[variantId];
             
             if (priceData) {
                 // Use custom price
                 newTotal += priceData.custom * item.quantity;
                 oldTotal += priceData.original * item.quantity;
                 console.log(`  Row ${item.title}: Custom Price ${priceData.custom} * ${item.quantity} = ${priceData.custom * item.quantity}`);
             } else {
                 // Use standard Shopify price (convert cents to dollars)
                 const standardPrice = item.final_price / 100; 
                 const originalStandard = item.original_price / 100;
                 
                 newTotal += standardPrice * item.quantity;
                 oldTotal += originalStandard * item.quantity;
                 console.log(`  Row ${item.title}: Standard Price ${standardPrice} * ${item.quantity} = ${standardPrice * item.quantity}`);
             }
        });
        
        console.log('  🧮 Calculated Total:', newTotal);

        // Now update with all the info
        updateCartTotalDisplay(newTotal, oldTotal, shopifyOriginalTotal);
        
        // **RECONNECT observer after update is complete**
        setTimeout(function() {
            if (cartMutationObserver) {
                const cartContainer = document.querySelector('.cart, #cart, .cart-page');
                if (cartContainer) {
                    cartMutationObserver.observe(cartContainer, {
                        childList: true,
                        subtree: true,
                        characterData: true
                    });
                    console.log('▶️ Reconnected mutation observer');
                }
            }
        }, 500);
      })
      .catch(err => {
        console.error('⚠️ Could not fetch cart.js for total calculation:', err);
        
        // Reconnect observer even on error
        setTimeout(function() {
            if (cartMutationObserver) {
                const cartContainer = document.querySelector('.cart, #cart, .cart-page');
                if (cartContainer) {
                    cartMutationObserver.observe(cartContainer, {
                        childList: true,
                        subtree: true,
                        characterData: true
                    });
                }
            }
        }, 500);
      });
}

function updateCartTotalDisplay(newTotal, oldTotal, shopifyOriginalTotal) {
    if (oldTotal === 0) return;
    
    // Check for active loyalty discount
    let loyaltyDiscount = 0;
    let hasLoyaltyDiscount = false;
    
    try {
        if (window.metoraLoyalty && typeof window.metoraLoyalty.getActiveRedemption === 'function') {
            const redemption = window.metoraLoyalty.getActiveRedemption();
            if (redemption && redemption.discount_amount) {
                loyaltyDiscount = parseFloat(redemption.discount_amount);
                hasLoyaltyDiscount = true;
                console.log('  🎫 Applying loyalty discount: ₹' + loyaltyDiscount);
            }
        }
    } catch (err) {
        console.log('  ℹ️ Loyalty not yet loaded or no active discount');
    }
    
    const finalTotal = hasLoyaltyDiscount ? Math.max(0, newTotal - loyaltyDiscount) : newTotal;
    
    console.log('  📊 Shopify original: ' + symbol + shopifyOriginalTotal);
    console.log('  📊 Custom price total: ' + symbol + newTotal);
    if (hasLoyaltyDiscount) {
        console.log('  🎫 Loyalty discount: -' + symbol + loyaltyDiscount);
    }
    console.log('  📊 Final total: ' + symbol + finalTotal);
    
    // **Update the text-component cart total**
    const cartTotalComponent = document.querySelector('text-component[ref="cartTotal"], text-component[data-cart-subtotal]');
    if (cartTotalComponent) {
        console.log('  🎯 Found cart total component');
        
        // Update the value attribute
        cartTotalComponent.setAttribute('value', symbol + ' ' + finalTotal.toFixed(2));
        
        // Update the text content
        cartTotalComponent.textContent = symbol + ' ' + finalTotal.toFixed(2);
        
        // Add styling
        cartTotalComponent.style.color = '#10b981';
        cartTotalComponent.style.fontWeight = '700';
        
        console.log('  ✅ Cart total component updated to:', symbol + finalTotal.toFixed(2));
    } else {
        console.log('  ⚠️ Cart total component not found');
    }
    
    // **AGGRESSIVE SEARCH: Find ANY element with the cart total**

    const allElements = document.querySelectorAll('*:not([data-metora-total-updated])');
    let targetElement = null;
    
    for (let i = 0; i < allElements.length; i++) {
        const el = allElements[i];
        
        // Skip if already updated
        if (el.hasAttribute('data-metora-total-updated')) continue;
        
        // Skip if it has many children (we want leaf nodes)
        if (el.children.length > 2) continue;
        
        // Skip loyalty widget
        if (el.closest('#metora-loyalty-widget')) continue;
        
        const text = el.textContent.trim();
        // More robust numeric extraction: find the first sequence of digits and a decimal point
        const numericMatch = text.match(/(\d[\d,.]*)/);
        if (!numericMatch) continue;
        
        const numericText = numericMatch[0].replace(/,/g, '');
        const value = parseFloat(numericText);
        
        // Check if this matches the original total (with some tolerance)
        if (!isNaN(value) && Math.abs(value - shopifyOriginalTotal) < 1) {
            // Extra check: is this in a footer/total section?
            const inFooter = el.closest('.cart__footer, .cart__ctas, .totals, [class*="total"], [class*="cart"]');
            if (inFooter) {
                targetElement = el;
                console.log('  ✅ Found cart total element:', el.className, 'value:', value);
                break;
            }
        }
    }
    
    if (targetElement) {
        console.log('  🎯 Updating cart total element...');
        
        // Mark as updated FIRST
        targetElement.setAttribute('data-metora-total-updated', 'true');
        targetElement.classList.add('metora-total-updated');
        
        // Clear and rebuild
        targetElement.innerHTML = '';
        
        // Final total (in green)
        const finalSpan = document.createElement('span');
        finalSpan.style.cssText = 'color: #10b981 !important; font-weight: 700 !important; font-size: inherit !important;';
        finalSpan.textContent = symbol + finalTotal.toFixed(2);
        targetElement.appendChild(finalSpan);
        
        // If there's loyalty discount, show custom price struck through
        if (hasLoyaltyDiscount && newTotal !== finalTotal) {
            const customSpan = document.createElement('span');
            customSpan.style.cssText = 'text-decoration: line-through !important; color: #9ca3af !important; font-size: 0.85em !important; margin-left: 6px !important;';
            customSpan.textContent = symbol + newTotal.toFixed(2);
            targetElement.appendChild(document.createTextNode(' '));
            targetElement.appendChild(customSpan);
        }
        
        // Original price (struck through)
        if (oldTotal !== finalTotal) {
            const originalSpan = document.createElement('span');
            originalSpan.style.cssText = 'text-decoration: line-through !important; color: #9ca3af !important; font-size: 0.85em !important; margin-left: 6px !important;';
            originalSpan.textContent = symbol + shopifyOriginalTotal.toFixed(2);
            targetElement.appendChild(document.createTextNode(' '));
            targetElement.appendChild(originalSpan);
        }
        
        console.log('  ✅ Cart total updated successfully!');
    } else {
        console.error('  ❌ Could not find cart total element!');
        console.log('  💡 The element with value ₹' + shopifyOriginalTotal + ' was not found');
    }
}

  // ============================================
  // CHECKOUT INTERCEPTOR
  // ============================================
  function setupCheckoutInterceptor() {
    if (Object.keys(window.metoraCustomPrices).length === 0) {
      console.log('ℹ️ No custom prices, normal checkout will proceed');
      return;
    }

    console.log('🔧 Setting up checkout interceptor...');

    window.metoraCheckoutInProgress = false;

    const checkoutButtons = document.querySelectorAll(
      'button[name="checkout"]:not(.metora-intercepted), ' +
      'input[name="checkout"]:not(.metora-intercepted), ' +
      '.cart__checkout-button:not(.metora-intercepted), ' +
      'button[type="submit"]:not(.metora-intercepted)'
    );

    console.log('✅ Found', checkoutButtons.length, 'checkout buttons to intercept');

    checkoutButtons.forEach(function(button) {
      button.classList.add('metora-intercepted');
      
      const form = button.closest('form');
      if (form) {
        form.addEventListener('submit', function(e) {
          e.preventDefault();
          e.stopPropagation();
        }, true);
      }

      button.addEventListener('click', handleCheckoutClick, true);
    });
  }

  async function handleCheckoutClick(e) {
    if (window.metoraCheckoutInProgress) {
      console.log('⏸️ Checkout already in progress, ignoring click...');
      e.preventDefault();
      e.stopPropagation();
      e.stopImmediatePropagation();
      return false;
    }
    
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();
    
    const button = e.currentTarget;
    
    // **CRITICAL: Stop ALL updates immediately**
    window.metoraCheckoutInProgress = true;
    window.metoraUpdateInProgress = true;
    window.metoraRefreshInProgress = true;
    
    // **CRITICAL: Disconnect MutationObserver to prevent any further updates**
    if (cartMutationObserver) {
      cartMutationObserver.disconnect();
      console.log('🛑 MutationObserver disconnected');
    }
    
    console.log('🛑 Checkout clicked - FREEZING all price updates...');
    console.log('🛑 Creating custom checkout with special prices...');
    
    const originalText = button.textContent || button.value;
    const originalDisabled = button.disabled;
    
    if (button.tagName === 'BUTTON') {
      button.textContent = '⏳ Creating your checkout...';
    } else {
      button.value = '⏳ Creating your checkout...';
    }
    button.disabled = true;
    button.style.opacity = '0.6';

    try {
      const cartResponse = await fetch('/cart.js');
      const cart = await cartResponse.json();

      console.log('📦 Cart items:', cart.items.length);
      console.log('💰 Custom prices:', Object.keys(window.metoraCustomPrices).length, 'items');

      // Check if there's an active loyalty discount
        let loyaltyDiscountCode = null;
        if (window.metoraLoyalty && window.metoraLoyalty.getActiveDiscount) {
            loyaltyDiscountCode = window.metoraLoyalty.getActiveDiscount();
            console.log('🎫 Found loyalty discount:', loyaltyDiscountCode);
        }
        
        const payload = {
            customer_id: parseInt(customerId),
            shop: CONFIG.shop,
            cart_items: cart.items.map(item => ({
                variant_id: item.variant_id,
                quantity: item.quantity,
                price: item.price
            })),
            custom_prices: window.metoraCustomPrices,
            loyalty_discount_code: loyaltyDiscountCode 
        };

      console.log('📤 Sending payload with loyalty discount:', loyaltyDiscountCode);

      const response = await fetch(CONFIG.checkoutApiUrl, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        console.error('❌ API Error Response:', errorData);
        throw new Error('API returned status: ' + response.status + ' - ' + (errorData.message || 'Unknown error'));
      }

      const data = await response.json();

      if (data.success && data.checkout_url) {
        console.log('✅ Custom checkout created successfully!');
        console.log('🔗 Redirecting to:', data.checkout_url);
        
        if (button.tagName === 'BUTTON') {
          button.textContent = '✓ Redirecting...';
        } else {
          button.value = '✓ Redirecting...';
        }
        
        setTimeout(function() {
          window.location.href = data.checkout_url;
        }, 500);
      } else {
        throw new Error(data.message || 'Failed to create checkout');
      }

    } catch (error) {
      console.error('❌ Checkout creation failed:', error);
      
      // **FIXED: Reset flags on error and reconnect observer**
      window.metoraCheckoutInProgress = false;
      window.metoraUpdateInProgress = false;
      window.metoraRefreshInProgress = false;
      
      // Reconnect observer
      if (cartMutationObserver) {
        const cartContainer = document.querySelector('.cart, #cart, .cart-page');
        if (cartContainer) {
          cartMutationObserver.observe(cartContainer, {
            childList: true,
            subtree: true,
            characterData: true
          });
        }
      }
      
      if (button.tagName === 'BUTTON') {
        button.textContent = originalText;
      } else {
        button.value = originalText;
      }
      button.disabled = originalDisabled;
      button.style.opacity = '1';
      
      alert('Unable to create custom checkout. Error: ' + error.message + '\n\nPlease try again or contact support.');
    }
    
    return false;
  }

  // ============================================
  // UTILITIES
  // ============================================
  function createPriceBadge(customPrice, originalPrice, symbol) {
    const savings = originalPrice - customPrice;
    
    // Silent Override for higher/equal prices
    if (savings <= 0.01) {
        // Return simple price span with a unique class we can skip later
        return '<span class="metora-silent-price-badge" style="font-weight:700; color:inherit;">' + symbol + customPrice.toFixed(2) + '</span>';
    }
    
    return '<div class="metora-custom-price-badge">' +
           '<div style="font-size: 11px; color: #059669; font-weight: 600; margin-bottom: 4px;">✨ Your Special Price</div>' +
           '<div>' +
           '<span class="metora-custom-price-value">' + symbol + customPrice.toFixed(2) + '</span>' +
           '<span class="metora-original-price-strike">' + symbol + originalPrice.toFixed(2) + '</span>' +
           '<span class="metora-savings-badge">Save ' + symbol + savings.toFixed(2) + '</span>' +
           '</div>' +
           '</div>';
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

  console.log('✨ Complete cart & checkout handler initialized');

})();
