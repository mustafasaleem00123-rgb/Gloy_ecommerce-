/**
 * Gloy Skincare - Core JavaScript (Navbar, Toast System, Scroll Reveal)
 */
document.addEventListener('DOMContentLoaded', () => {
  // 1. Sticky Header Transparency Control
  const header = document.querySelector('.site-header');
  if (header) {
    window.addEventListener('scroll', () => {
      if (window.scrollY > 30) {
        header.classList.add('scrolled');
      } else {
        header.classList.remove('scrolled');
      }
    });
  }

  // 2. Mobile Navigation Drawer Controller
  const navToggle = document.getElementById('mobile-nav-toggle-btn') || document.querySelector('.mobile-nav-toggle');
  const navDrawer = document.getElementById('mobile-nav-drawer');
  const navOverlay = document.getElementById('mobile-nav-overlay');
  const closeNavBtns = document.querySelectorAll('.js-close-mobile-nav');
  const mobileNavLinks = document.querySelectorAll('.mobile-nav-link');

  function openMobileNav() {
    if (navDrawer) navDrawer.classList.add('is-active');
    if (navOverlay) navOverlay.classList.add('is-active');
    if (navToggle) navToggle.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
  }

  function closeMobileNav() {
    if (navDrawer) navDrawer.classList.remove('is-active');
    if (navOverlay) navOverlay.classList.remove('is-active');
    if (navToggle) navToggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  }

  if (navToggle) {
    navToggle.addEventListener('click', (e) => {
      e.stopPropagation();
      if (navDrawer && navDrawer.classList.contains('is-active')) {
        closeMobileNav();
      } else {
        openMobileNav();
      }
    });
  }

  if (navOverlay) {
    navOverlay.addEventListener('click', closeMobileNav);
  }

  closeNavBtns.forEach(btn => {
    btn.addEventListener('click', closeMobileNav);
  });

  mobileNavLinks.forEach(link => {
    link.addEventListener('click', closeMobileNav);
  });

  // Close with Escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && navDrawer && navDrawer.classList.contains('is-active')) {
      closeMobileNav();
    }
  });

  // 3. Scroll Reveal via IntersectionObserver
  const revealElements = document.querySelectorAll('.reveal-on-scroll');
  if (revealElements.length > 0) {
    const observerOptions = {
      root: null,
      rootMargin: '0px 0px -50px 0px',
      threshold: 0.15
    };

    const observer = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, observerOptions);

    revealElements.forEach(el => observer.observe(el));
  }

  // 4. Global Toast Notification System
  window.showToast = function(message, type = 'info') {
    let container = document.querySelector('.toast-container');
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    
    let icon = '<i class="bi bi-check-circle-fill me-1"></i>';
    if (type === 'error') icon = '<i class="bi bi-exclamation-circle-fill me-1"></i>';
    if (type === 'info') icon = '<i class="bi bi-info-circle-fill me-1"></i>';

    toast.innerHTML = `<span>${icon}</span> <span>${message}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(20px)';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  };

  // 5. Accordion Event Delegation
  document.addEventListener('click', (e) => {
    const accordionHeader = e.target.closest('.accordion-header');
    if (accordionHeader) {
      const item = accordionHeader.parentElement;
      const isActive = item.classList.contains('active');

      // Close sibling items if inside an accordion container
      const container = item.closest('.accordion-container');
      if (container) {
        container.querySelectorAll('.accordion-item').forEach(el => el.classList.remove('active'));
      }

      if (!isActive) {
        item.classList.add('active');
      }
    }
  });

  // 6. Slide-Out Mini Cart Drawer
  const cartDrawer = document.getElementById('cart-drawer');
  const cartDrawerOverlay = document.getElementById('cart-drawer-overlay');
  const openCartBtns = document.querySelectorAll('.js-open-cart-drawer');
  const closeCartBtns = document.querySelectorAll('.js-close-cart-drawer');

  function openCartDrawer() {
    cartDrawer.classList.add('is-open');
    cartDrawerOverlay.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    loadCartItems();
  }

  function closeCartDrawer() {
    cartDrawer.classList.remove('is-open');
    cartDrawerOverlay.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  openCartBtns.forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      openCartDrawer();
    });
  });

  closeCartBtns.forEach(btn => {
    btn.addEventListener('click', closeCartDrawer);
  });

  cartDrawerOverlay.addEventListener('click', closeCartDrawer);

  // Close cart drawer on escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && cartDrawer.classList.contains('is-open')) {
      closeCartDrawer();
    }
  });

  // 7. Live Product Search Suggestion Dropdowns
  const searchInputs = document.querySelectorAll('.search-input');
  searchInputs.forEach(input => {
    const form = input.closest('form');
    const container = input.closest('.nav-search') || form.parentElement;
    let dropdown = container.querySelector('.search-suggestions-dropdown');

    if (!dropdown) {
      dropdown = document.createElement('div');
      dropdown.className = 'search-suggestions-dropdown';
      container.appendChild(dropdown);
    }

    let debounceTimer = null;

    input.addEventListener('input', (e) => {
      const query = e.target.value.trim();
      clearTimeout(debounceTimer);

      if (query.length < 2) {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
        return;
      }

      debounceTimer = setTimeout(() => {
        fetch(`api/search_suggestions.php?q=${encodeURIComponent(query)}`)
          .then(res => res.json())
          .then(data => {
            if (data.success && data.results && data.results.length > 0) {
              let html = '<div class="suggestions-list">';
              data.results.forEach(item => {
                html += `
                  <a href="products.php?search=${encodeURIComponent(item.name)}" class="suggestion-item" style="display:flex; align-items:center; gap:12px; padding:10px 14px; text-decoration:none; border-bottom:1px solid var(--border-color); color:var(--text-heading); transition:background 0.2s;">
                    <img src="assets/images/${item.image}" alt="${item.name}" style="width:36px; height:36px; object-fit:contain; background:var(--bg-main); border-radius:4px; padding:2px;">
                    <div style="flex:1;">
                      <strong style="font-size:0.85rem; display:block; color:var(--text-heading);">${item.name}</strong>
                      <span style="font-size:0.75rem; color:var(--text-muted);">${item.category} • ${item.formatted_price}</span>
                    </div>
                    <i class="bi bi-chevron-right" style="font-size:0.75rem; color:var(--text-muted);"></i>
                  </a>
                `;
              });
              html += `
                <a href="products.php?search=${encodeURIComponent(query)}" style="display:block; padding:8px 14px; text-align:center; font-size:0.8rem; font-weight:600; color:var(--primary-purple); background:var(--bg-main); text-decoration:none;">
                  View all results for "${query}" <i class="bi bi-arrow-right ms-1"></i>
                </a>
              </div>`;
              dropdown.innerHTML = html;
              dropdown.style.display = 'block';
            } else {
              dropdown.innerHTML = `
                <div style="padding:14px; text-align:center; font-size:0.85rem; color:var(--text-muted);">
                  No products found for "${query}"
                </div>
              `;
              dropdown.style.display = 'block';
            }
          })
          .catch(err => {
            console.error(err);
            dropdown.style.display = 'none';
          });
      }, 250);
    });

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
      if (!container.contains(e.target)) {
        dropdown.style.display = 'none';
      }
    });
  });

  function loadCartItems() {
    const cartBody = document.getElementById('cart-drawer-body');
    const cartFooter = document.getElementById('cart-drawer-footer');

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
        renderCartDrawerItems(res.items, res.subtotal);
      } else {
        cartBody.innerHTML = `
          <div class="cart-drawer-empty">
            <i class="bi bi-bag-x"></i>
            <p>Error loading cart. Please try again.</p>
          </div>
        `;
      }
    })
    .catch(err => {
      console.error(err);
      cartBody.innerHTML = `
        <div class="cart-drawer-empty">
          <i class="bi bi-bag-x"></i>
          <p>Error loading cart. Please try again.</p>
        </div>
      `;
    });
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

  // Cart drawer quantity and remove handlers
  cartBody = document.getElementById('cart-drawer-body');
  if (cartBody) {
    cartBody.addEventListener('click', (e) => {
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
            loadCartItems();
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
        loadCartItems();
        const badge = document.querySelector('.cart-badge');
        if (badge) badge.textContent = res.cart_count;
      } else {
        window.showToast(res.message || 'Could not update quantity', 'error');
      }
    });
  }

  // 8. Global Password Visibility Toggle System
  document.addEventListener('click', (e) => {
    const toggleBtn = e.target.closest('.js-toggle-password');
    if (toggleBtn) {
      e.preventDefault();
      const targetId = toggleBtn.dataset.target;
      const input = targetId ? document.getElementById(targetId) : toggleBtn.closest('.password-toggle-wrap')?.querySelector('input');
      if (input) {
        const icon = toggleBtn.querySelector('i');
        if (input.type === 'password') {
          input.type = 'text';
          if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
          }
        } else {
          input.type = 'password';
          if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
          }
        }
      }
    }
  });
});
