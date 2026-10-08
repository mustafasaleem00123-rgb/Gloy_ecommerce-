/**
 * Gloy Skincare - Cart & Checkout AJAX Actions
 */
document.addEventListener('DOMContentLoaded', () => {
  // 1. Grid Product Card "Add to Cart" Button Handler
  document.addEventListener('click', (e) => {
    const addBtn = e.target.closest('.js-add-to-cart');
    if (addBtn) {
      e.preventDefault();
      const productId = addBtn.dataset.id;
      if (!productId) return;

      const originalText = addBtn.innerHTML;
      addBtn.disabled = true;
      addBtn.innerHTML = '<span>Adding...</span>';

      fetch('api/cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'add',
          product_id: productId,
          qty: 1
        })
      })
      .then(res => res.json())
      .then(res => {
        addBtn.disabled = false;
        addBtn.innerHTML = originalText;

        if (res.success) {
          window.showToast('Item added to your shopping cart', 'success');
          const badge = document.querySelector('.cart-badge');
          if (badge) badge.textContent = res.cart_count;

          // Open cart drawer
          const cartDrawer = document.getElementById('cart-drawer');
          const cartDrawerOverlay = document.getElementById('cart-drawer-overlay');
          if (cartDrawer && cartDrawerOverlay) {
            cartDrawer.classList.add('is-open');
            cartDrawerOverlay.classList.add('is-open');
            document.body.style.overflow = 'hidden';

            // Load cart items in drawer
            const cartBody = document.getElementById('cart-drawer-body');
            if (cartBody) {
              cartBody.innerHTML = `
                <div class="cart-drawer-loading">
                  <i class="bi bi-hourglass-split"></i>
                  <span>Loading your cart...</span>
                </div>
              `;

              fetch('api/cart_action.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ action: 'get' })
              })
              .then(res => res.json())
              .then(res => {
                if (res.success) {
                  // Render cart items (reusing logic from main.js)
                  renderCartDrawerItems(res.items, res.subtotal);
                }
              });
            }
          }
        } else {
          window.showToast(res.message || 'Unable to add to cart', 'error');
        }
      })
      .catch(err => {
        console.error(err);
        addBtn.disabled = false;
        addBtn.innerHTML = originalText;
        window.showToast('Server error adding item to cart', 'error');
      });
    }

    // 1b. Grid Product Card "Buy Now" Button Handler -> Immediate Checkout
    const buyBtn = e.target.closest('.js-buy-now');
    if (buyBtn) {
      e.preventDefault();
      const productId = buyBtn.dataset.id;
      if (!productId) return;

      const originalText = buyBtn.innerHTML;
      buyBtn.disabled = true;
      buyBtn.innerHTML = '<span>Proceeding...</span>';

      fetch('api/cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'add',
          product_id: productId,
          qty: 1
        })
      })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          window.location.href = 'checkout.php';
        } else {
          buyBtn.disabled = false;
          buyBtn.innerHTML = originalText;
          window.showToast(res.message || 'Unable to proceed to checkout', 'error');
        }
      })
      .catch(err => {
        console.error(err);
        buyBtn.disabled = false;
        buyBtn.innerHTML = originalText;
        window.showToast('Server error processing purchase', 'error');
      });
    }
  });

  // 2. Cart Page Quantity Adjustment (+ / -) & Item Removal
  const cartTable = document.getElementById('cart-table-body');
  if (cartTable) {
    cartTable.addEventListener('click', (e) => {
      const qtyBtn = e.target.closest('.js-cart-qty-btn');
      const removeBtn = e.target.closest('.js-remove-cart-item');

      // Update Quantity
      if (qtyBtn) {
        const row = qtyBtn.closest('tr');
        const cartId = row.dataset.cartId;
        const change = parseInt(qtyBtn.dataset.change);
        const qtyInput = row.querySelector('.cart-qty-val');
        let newQty = parseInt(qtyInput.value) + change;

        if (newQty < 1) return;

        updateCartItem(cartId, newQty, row);
      }

      // Remove Item
      if (removeBtn) {
        const row = removeBtn.closest('tr');
        const cartId = row.dataset.cartId;

        fetch('api/cart_action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ action: 'remove', cart_id: cartId })
        })
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            row.remove();
            recalculateCartTotals(res);
            window.showToast('Item removed from cart', 'info');
            const badge = document.querySelector('.cart-badge');
            if (badge) badge.textContent = res.cart_count;
          } else {
            window.showToast(res.message || 'Error removing item', 'error');
          }
        });
      }
    });
  }

  function updateCartItem(cartId, newQty, row) {
    fetch('api/cart_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action: 'update', cart_id: cartId, qty: newQty })
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        const qtyInput = row.querySelector('.cart-qty-val');
        if (qtyInput) qtyInput.value = newQty;
        
        const lineTotal = row.querySelector('.cart-line-total');
        if (lineTotal) lineTotal.textContent = `Rs. ${parseInt(res.item_total).toLocaleString()}`;

        recalculateCartTotals(res);
        const badge = document.querySelector('.cart-badge');
        if (badge) badge.textContent = res.cart_count;
      } else {
        window.showToast(res.message || 'Could not update quantity', 'error');
      }
    });
  }

  function recalculateCartTotals(data) {
    const subtotalEl = document.getElementById('cart-subtotal');
    const grandTotalEl = document.getElementById('cart-grand-total');

    if (subtotalEl) subtotalEl.textContent = `Rs. ${parseInt(data.subtotal).toLocaleString()}`;
    if (grandTotalEl) grandTotalEl.textContent = `Rs. ${parseInt(data.grand_total).toLocaleString()}`;

    // Show empty cart message if subtotal is 0
    if (parseFloat(data.subtotal) <= 0) {
      const cartWrapper = document.getElementById('cart-wrapper');
      if (cartWrapper) {
        cartWrapper.innerHTML = `
          <div style="text-align:center; padding:60px 0; background:var(--bg-surface); border-radius:var(--radius-md); border:1px solid var(--border-color);">
            <div style="margin-bottom:16px;"><i class="bi bi-bag-x" style="font-size:3.5rem; color:var(--primary-purple);"></i></div>
            <h3 style="margin-bottom:12px; color:var(--text-heading);">Your shopping cart is currently empty</h3>
            <p style="color:var(--text-muted); margin-bottom:24px;">Discover our botanical skincare collection to add items.</p>
            <a href="products.php" class="btn btn-primary">Start Shopping</a>
          </div>
        `;
      }
    }
  }

  function renderCartDrawerItems(items, subtotal) {
    const cartBody = document.getElementById('cart-drawer-body');
    const subtotalEl = document.getElementById('cart-drawer-subtotal');

    if (items.length === 0) {
      cartBody.innerHTML = `
        <div class="cart-drawer-empty">
          <i class="bi bi-bag-x"></i>
          <p>Your shopping cart is currently empty</p>
          <a href="products.php" class="btn btn-primary btn-sm" style="margin-top: 16px;">Start Shopping</a>
        </div>
      `;
      subtotalEl.textContent = 'Rs. 0';
      return;
    }

    let html = '';
    items.forEach(item => {
      html += `
        <div class="cart-drawer-item" data-cart-id="${item.cart_id}">
          <div class="cart-drawer-item-image">
            <img src="assets/images/${item.image}" alt="${item.name}">
          </div>
          <div class="cart-drawer-item-details">
            <div class="cart-drawer-item-name">${item.name}</div>
            <div class="cart-drawer-item-price">Rs. ${parseInt(item.unit_price).toLocaleString()}</div>
            <div class="cart-drawer-item-qty">
              <button class="cart-drawer-qty-btn js-drawer-qty-btn" data-change="-1">-</button>
              <input type="number" class="cart-drawer-qty-input cart-drawer-qty-val" value="${item.qty}" readonly>
              <button class="cart-drawer-qty-btn js-drawer-qty-btn" data-change="1">+</button>
              <button class="cart-drawer-item-remove js-drawer-remove-item">
                <i class="bi bi-trash3"></i>
              </button>
            </div>
          </div>
        </div>
      `;
    });

    cartBody.innerHTML = html;
    subtotalEl.textContent = `Rs. ${parseInt(subtotal).toLocaleString()}`;
  }

  // Cart drawer event handlers
  const cartDrawerBodyElement = document.getElementById('cart-drawer-body');
  if (cartDrawerBodyElement) {
    cartDrawerBodyElement.addEventListener('click', (e) => {
      const qtyBtn = e.target.closest('.js-drawer-qty-btn');
      const removeBtn = e.target.closest('.js-drawer-remove-item');

      if (qtyBtn) {
        const item = qtyBtn.closest('.cart-drawer-item');
        const cartId = item.dataset.cartId;
        const change = parseInt(qtyBtn.dataset.change);
        const qtyInput = item.querySelector('.cart-drawer-qty-val');
        let newQty = parseInt(qtyInput.value) + change;

        if (newQty < 1) return;

        updateDrawerCartItem(cartId, newQty);
      }

      if (removeBtn) {
        const item = removeBtn.closest('.cart-drawer-item');
        const cartId = item.dataset.cartId;

        fetch('api/cart_action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ action: 'remove', cart_id: cartId })
        })
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            // Reload cart drawer
            fetch('api/cart_action.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
              body: new URLSearchParams({ action: 'get' })
            })
            .then(res => res.json())
            .then(res => {
              if (res.success) {
                renderCartDrawerItems(res.items, res.subtotal);
              }
            });

            const badge = document.querySelector('.cart-badge');
            if (badge) badge.textContent = res.cart_count;
            window.showToast('Item removed from cart', 'info');
          } else {
            window.showToast(res.message || 'Error removing item', 'error');
          }
        });
      }
    });
  }

  function updateDrawerCartItem(cartId, newQty) {
    fetch('api/cart_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({ action: 'update', cart_id: cartId, qty: newQty })
    })
    .then(res => res.json())
    .then(res => {
      if (res.success) {
        // Reload cart drawer
        fetch('api/cart_action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({ action: 'get' })
        })
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            renderCartDrawerItems(res.items, res.subtotal);
          }
        });

        const badge = document.querySelector('.cart-badge');
        if (badge) badge.textContent = res.cart_count;
      } else {
        window.showToast(res.message || 'Could not update quantity', 'error');
      }
    });
  }
});
