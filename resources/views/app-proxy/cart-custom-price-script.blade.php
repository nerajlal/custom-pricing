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
      
      // **RE-ENABLED: Setup Mutation Observer to ensure persistence**
      interceptCartRender(); 
      
      // Mark initialization complete
      window.metoraInitComplete = true;
      
      // Trigger loyalty refresh after delay
      setTimeout(function() {
          if (window.metoraLoyalty) {
              console.log('🔄 Triggering one-time loyalty refresh...');
              triggerLoyaltyRefresh();
          }
      }, 2000);
      
      console.log('✅ All handlers initialized (MutationObserver enabled for persistence)');
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

  function applyAllUpdates() {
    if (window.metoraUpdateInProgress) {
      console.log('⏸️ Update already in progress, skipping...');
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
          // Only remove the badge itself, not properly nested ones
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
    if (initialHide) initialHide.remove();
    
    const cartHide = document.getElementById('metora-cart-hide');
    if (cartHide) cartHide.remove();
    
    setTimeout(function() {
      window.metoraUpdateInProgress = false;
    }, 1000);
  }
  
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
    const symbol = getCurrencySymbol(CONFIG.currency);
    
    // Find ALL matching cart item rows (common for mobile/desktop splits)
    const rows = findAllCartRows(variantId);
    
    if (rows.length === 0) {
      console.log('  ⚠️ Rows not found for variant:', variantId);
      return;
    }
    
    console.log('  ✅ Updating ' + rows.length + ' rows for variant:', variantId);
    
    rows.forEach(row => {
        console.log('    📦 Processing Row:', row.tagName + (row.className ? '.' + row.className.replace(/\s+/g, '.') : ''));
        processRow(row, priceData, symbol);
    });

    function findAllCartRows(vId) {
        const found = [];
        const selectors = [
            'tr.cart-items__table-row[data-key*="' + vId + '"]',
            'tr[data-key*="' + vId + '"]',
            'tr.cart-item[data-key*="' + vId + '"]',
            '[data-variant-id="' + vId + '"]',
            '[data-id="' + vId + '"]',
            '.cart-item[id*="' + vId + '"]',
            '.cart-drawer__item[data-id*="' + vId + '"]'
        ];
        
        selectors.forEach(selector => {
            const elements = document.querySelectorAll(selector);
            elements.forEach(el => {
                if (!found.includes(el)) found.push(el);
            });
        });
        
        // Method 2: Search for variant ID in links/inputs within the cart
        if (found.length === 0) {
            const cartContainers = document.querySelectorAll('.cart-drawer, cart-drawer, .cart, .cart-page, #CartDrawer');
            cartContainers.forEach(container => {
                const subElements = container.querySelectorAll('a[href*="' + vId + '"], input[value="' + vId + '"], [id*="' + vId + '"]');
                subElements.forEach(sub => {
                    const parentRow = sub.closest('tr, .cart-item, [role="row"], .cart-drawer__item, .cart-item__details');
                    if (parentRow && !found.includes(parentRow)) found.push(parentRow);
                });
            });
        }
        
        return found;
    }

    function processRow(row, pData, sym) {
        let unitPriceUpdated = false;
        let lineTotalUpdated = false;

        function findPriceElements(root) {
            if (!root) return;
            // Skip already processed
            if (root.classList && (
                root.classList.contains('metora-updated') || 
                root.classList.contains('metora-custom-price-badge') ||
                root.closest('.metora-custom-price-badge') ||
                root.classList.contains('metora-silent-price-badge')
            )) return;
            
            // Skip inputs/buttons
            if (root.tagName === 'INPUT' || root.tagName === 'BUTTON' || root.tagName === 'SELECT') return;

            const children = root.children;
            if (children.length === 0) {
                checkAndReplace(root);
            } else {
                Array.from(children).forEach(child => findPriceElements(child));
            }
        }
        
        function checkAndReplace(el) {
            if (unitPriceUpdated && lineTotalUpdated) return;
            
            const text = el.textContent.trim();
            if (!text) return;
            if (text.length > 25) return; // Skip long descriptions
            
            // Aggressive cleaning
            const numericText = text.replace(/[^\d.]/g, ''); 
            const value = parseFloat(numericText);
            
            if (isNaN(value)) return;
            
            const originalPrice = pData.original;
            const customPrice = pData.custom;
            const quantity = pData.quantity;
            
            // Unit price check
            if (!unitPriceUpdated && Math.abs(value - originalPrice) < 0.1) {
               console.log('      ✅ Updating unit price element:', text);
               el.classList.add('metora-updated');
               
               if (customPrice >= originalPrice) {
                    el.innerHTML = '<span class="metora-silent-price-badge" style="font-weight:700; color:inherit;">' + sym + customPrice.toFixed(2) + '</span>';
               } else {
                    el.innerHTML = '<div class="metora-custom-price-badge" style="display:inline-block; border:1px solid #10b981; border-radius:4px; padding:2px 6px; background:#f0fdf4; margin-bottom:4px;">' +
                        '<div style="font-size:10px; color:#15803d; font-weight:700; text-transform:uppercase; line-height:1;">✨ Special</div>' +
                        '<div style="color:#10b981; font-weight:800; font-size:1.1em;">' + sym + customPrice.toFixed(2) + '</div>' +
                        '<div style="text-decoration:line-through; color:#9ca3af; font-size:0.85em;">' + sym + originalPrice.toFixed(2) + '</div>' +
                        '</div>';
               }
               unitPriceUpdated = true;
            } 
            // Line total check
            else if (!lineTotalUpdated && (Math.abs(value - (originalPrice * quantity)) < 0.1 || Math.abs(value - originalPrice) < 0.1)) {
               // Note: Some themes repeat unit price in line total column if quantity is 1
               console.log('      ✅ Updating line total element:', text);
               el.classList.add('metora-updated');
               
               const totalCustom = customPrice * quantity;
               const totalOriginal = originalPrice * quantity;
               
               if (customPrice >= originalPrice) {
                    el.innerHTML = '<span class="metora-silent-price-badge" style="font-weight:700; color:inherit;">' + sym + totalCustom.toFixed(2) + '</span>';
               } else {
                    el.innerHTML = '<span class="metora-custom-price-value" style="color:#10b981; font-weight:700;">' + sym + totalCustom.toFixed(2) + '</span> ' +
                        '<span class="metora-original-price-strike" style="text-decoration:line-through; color:#9ca3af; font-size:0.85em; margin-left:4px;">' + sym + totalOriginal.toFixed(2) + '</span>';
               }
               lineTotalUpdated = true;
            }
        }
        
        // Start traversal for this specific row
        console.log('    🔍 Searching for price', pData.original, 'in row...');
        findPriceElements(row);

        if (!unitPriceUpdated && !lineTotalUpdated) {
             console.log('    ⚠️ Traversing failed, trying aggressive selection in row...');
             const possiblePrices = row.querySelectorAll('.price, .cart-item__price, [class*="price"], .money, .totals__subtotal-value');
             possiblePrices.forEach(el => {
                if (!unitPriceUpdated || !lineTotalUpdated) checkAndReplace(el);
             });
        }
    }
    
    function checkAndReplace(el) {
        if (unitPriceUpdated && lineTotalUpdated) return;
        
        const text = el.textContent.trim();
        if (!text) return;
        if (text.length > 20) return; // Skip long descriptions
        
        // Aggressive cleaning
        const numericText = text.replace(/[^\d.]/g, ''); 
        const value = parseFloat(numericText);
        
        if (isNaN(value)) return;
        
        // Unit price
        if (!unitPriceUpdated && Math.abs(value - priceData.original) < 0.1) {
           console.log('    ✅ Updating unit price element:', text);
           el.classList.add('metora-updated');
           el.innerHTML = createPriceBadge(priceData.custom, priceData.original, symbol);
           unitPriceUpdated = true;
        }
        // Line total
        else if (!lineTotalUpdated && Math.abs(value - (priceData.original * priceData.quantity)) < 0.1) {
           console.log('    ✅ Updating line total element:', text);
           el.classList.add('metora-updated');
           
           const totalCustom = priceData.custom * priceData.quantity;
           const totalOriginal = priceData.original * priceData.quantity;
           
           if (totalCustom >= totalOriginal) {
                // Silent Override
                el.innerHTML = '<span class="metora-silent-price-badge" style="font-weight:700; color:inherit;">' + 
                 symbol + totalCustom.toFixed(2) + 
                 '</span>';
           } else {
                // Discount Display
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
    console.log('    🔍 Searching for price', priceData.original, 'in row...');
    findPriceElements(row);

    if (!unitPriceUpdated && !lineTotalUpdated) {
         console.log('  ⚠️ Could not find price matching ' + symbol + priceData.original + ' in row via traversal');
         
         // Fallback: Aggressive search in row for any price element
         const possiblePrices = row.querySelectorAll('.price, .cart-item__price, [class*="price"], .money');
         possiblePrices.forEach(el => {
            if (unitPriceUpdated && lineTotalUpdated) return;
            checkAndReplace(el);
         });
    }
  }

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
                console.log('  🎫 Applying loyalty discount: ' + symbol + loyaltyDiscount);
            }
        }
    } catch (err) {
        console.log('  ℹ️ Loyalty not yet loaded or no active discount');
    }
    
    const finalTotal = hasLoyaltyDiscount ? Math.max(0, newTotal - loyaltyDiscount) : newTotal;
    
    const symbol = getCurrencySymbol(CONFIG.currency);
    
    console.log('  📊 Shopify original: ' + symbol + shopifyOriginalTotal);
    console.log('  📊 Custom price total: ' + symbol + newTotal);
    if (hasLoyaltyDiscount) {
        console.log('  🎫 Loyalty discount: -' + symbol + loyaltyDiscount);
    }
    console.log('  📊 Final total: ' + symbol + finalTotal);
    
    // **Update common total components**
    const totalSelectors = [
        'text-component[ref="cartTotal"]',
        'text-component[data-cart-subtotal]',
        '.totals__subtotal-value',
        '.cart__subtotal-value',
        '.cart-drawer__footer .totals__subtotal-value',
        '#cart-subtotal',
        '.cart__subtotal'
    ];
    
    let componentFound = false;
    totalSelectors.forEach(selector => {
        const el = document.querySelector(selector);
        if (el && !el.hasAttribute('data-metora-total-updated')) {
            console.log('  🎯 Found cart total component via selector:', selector);
            el.setAttribute('value', symbol + ' ' + finalTotal.toFixed(2));
            el.textContent = symbol + finalTotal.toFixed(2);
            el.style.color = '#10b981';
            el.style.fontWeight = '700';
            el.setAttribute('data-metora-total-updated', 'true');
            componentFound = true;
        }
    });
    
    // **AGGRESSIVE SEARCH: Find ANY element with the cart total**
    const allElements = document.querySelectorAll('.cart__footer *, .totals *, #cart-subtotal, .cart__subtotal, [class*="total"], .cart-item__totals');
    console.log('  🔍 Searching for cart total (' + symbol + shopifyOriginalTotal + ') in ' + allElements.length + ' elements...');
    
    let totalUpdatedCount = 0;
    
    for (let i = 0; i < allElements.length; i++) {
        const el = allElements[i];
        
        if (el.hasAttribute('data-metora-total-updated')) continue;
        if (el.children.length > 5) continue; // Slightly more relaxed for complex themes
        if (el.closest('#metora-loyalty-widget')) continue;
        
        const text = el.textContent.trim();
        if (!text) continue;

        // More robust numeric extraction: handles commas, multiple dots, currency symbols
        const cleanText = text.replace(/[^\d.,]/g, '').replace(',', '.');
        const value = parseFloat(cleanText);
        
        if (!isNaN(value) && Math.abs(value - shopifyOriginalTotal) < 0.1) {
            console.log('  🎯 Updating cart total element:', el.className || el.tagName, 'at position', i);
            
            // Mark as updated FIRST
            el.setAttribute('data-metora-total-updated', 'true');
            el.classList.add('metora-total-updated');
            
            // Clear and rebuild
            el.innerHTML = '';
            
            // Final total (in green)
            const finalSpan = document.createElement('span');
            finalSpan.className = 'metora-final-price';
            finalSpan.style.cssText = 'color: #10b981 !important; font-weight: 700 !important; font-size: inherit !important;';
            finalSpan.textContent = symbol + finalTotal.toFixed(2);
            el.appendChild(finalSpan);
            
            // If there's loyalty discount, show custom price struck through
            if (hasLoyaltyDiscount && newTotal !== finalTotal) {
                const customSpan = document.createElement('span');
                customSpan.style.cssText = 'text-decoration: line-through !important; color: #9ca3af !important; font-size: 0.85em !important; margin-left: 6px !important; opacity: 0.7;';
                customSpan.textContent = symbol + newTotal.toFixed(2);
                el.appendChild(document.createTextNode(' '));
                el.appendChild(customSpan);
            }
            
            // Original price (struck through)
            if (shopifyOriginalTotal !== finalTotal) {
                const originalSpan = document.createElement('span');
                originalSpan.style.cssText = 'text-decoration: line-through !important; color: #9ca3af !important; font-size: 0.85em !important; margin-left: 6px !important; opacity: 0.5;';
                originalSpan.textContent = symbol + shopifyOriginalTotal.toFixed(2);
                el.appendChild(document.createTextNode(' '));
                el.appendChild(originalSpan);
            }
            
            totalUpdatedCount++;
        }
    }
    
    if (totalUpdatedCount > 0) {
        console.log('  ✅ Updated ' + totalUpdatedCount + ' cart total elements successfully!');
    } else if (!componentFound) {
        console.error('  ❌ Could not find ANY cart total elements!');
        console.log('  💡 The element with value ' + symbol + shopifyOriginalTotal + ' was not found');
    }
        
        console.log('  ✅ Cart total updated successfully!');
    } else {
        console.error('  ❌ Could not find cart total element!');
        console.log('  💡 The element with value ' + symbol + shopifyOriginalTotal + ' was not found');
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

  // **Manual Refresh Handler (for debugging)**
  window.metoraManualRefreshPrice = function() {
      console.log('🔄 Manual cart price refresh triggered...');
      
      if (window.metoraRefreshInProgress) {
          console.warn('⚠️ Refresh already in progress');
          return;
      }
      
      window.metoraRefreshInProgress = true;
      
      // Fetch prices fresh and apply
      fetchAllCustomPrices().then(function() {
          applyAllUpdates();
          updateCartTotal();
          
          setTimeout(function() {
              window.metoraRefreshInProgress = false;
              console.log('✅ Manual refresh complete');
          }, 1000);
      }).catch(function(err) {
          console.error('❌ Manual refresh failed:', err);
          window.metoraRefreshInProgress = false;
      });
  };

  console.log('✨ Complete cart & checkout handler initialized');

})();
