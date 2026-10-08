/**
 * Gloy Skincare - Interactive Skin Routine Quiz
 */
document.addEventListener('DOMContentLoaded', () => {
  const quizOverlay = document.getElementById('skin-quiz-overlay');
  const quizModal = document.getElementById('skin-quiz-modal');
  const quizClose = document.getElementById('skin-quiz-close');
  const quizTrigger = document.querySelector('.js-skin-quiz-trigger');

  // Quiz state
  let quizState = {
    step: 1,
    answers: {
      skin_type: null,
      concern: null,
      experience: null
    }
  };

  // Product recommendations based on quiz answers
  const productRecommendations = {
    // Dry skin + dark spots -> Moisturizer + Serum
    'dry_dark_spots': {
      products: [
        {
          id: 1,
          name: 'Glass Skin Moisturizer',
          image: 'gloy_moisturizer_wood.jpg',
          price: 2150,
          benefit: 'Deep hydration & barrier support'
        },
        {
          id: 2,
          name: 'Glutathione Brightening Serum',
          image: 'gloy_serum_wood.jpg',
          price: 2250,
          benefit: 'Brightens & evens skin tone'
        }
      ],
      routine_name: 'Hydration & Brightening Duo'
    },
    // Dry skin + dryness -> Moisturizer + Serum
    'dry_dryness': {
      products: [
        {
          id: 1,
          name: 'Glass Skin Moisturizer',
          image: 'gloy_moisturizer_wood.jpg',
          price: 2150,
          benefit: 'Intense hydration & moisture lock'
        },
        {
          id: 2,
          name: 'Glutathione Brightening Serum',
          image: 'gloy_serum_wood.jpg',
          price: 2250,
          benefit: 'Nourishes & adds glow'
        }
      ],
      routine_name: 'Deep Hydration Ritual'
    },
    // Oily skin + acne -> Serum + Moisturizer
    'oily_acne': {
      products: [
        {
          id: 2,
          name: 'Glutathione Brightening Serum',
          image: 'gloy_serum_wood.jpg',
          price: 2250,
          benefit: 'Helps clear breakouts & brighten'
        },
        {
          id: 1,
          name: 'Glass Skin Moisturizer',
          image: 'gloy_moisturizer_wood.jpg',
          price: 2150,
          benefit: 'Lightweight hydration without clogging'
        }
      ],
      routine_name: 'Clear & Balanced Routine'
    },
    // Normal skin + uneven tone -> Serum + Moisturizer
    'normal_uneven_tone': {
      products: [
        {
          id: 2,
          name: 'Glutathione Brightening Serum',
          image: 'gloy_serum_wood.jpg',
          price: 2250,
          benefit: 'Evens out skin tone & texture'
        },
        {
          id: 1,
          name: 'Glass Skin Moisturizer',
          image: 'gloy_moisturizer_wood.jpg',
          price: 2150,
          benefit: 'Maintains healthy moisture balance'
        }
      ],
      routine_name: 'Glow & Balance Duo'
    },
    // Default recommendation for other combinations
    'default': {
      products: [
        {
          id: 1,
          name: 'Glass Skin Moisturizer',
          image: 'gloy_moisturizer_wood.jpg',
          price: 2150,
          benefit: 'Essential daily hydration'
        },
        {
          id: 2,
          name: 'Glutathione Brightening Serum',
          image: 'gloy_serum_wood.jpg',
          price: 2250,
          benefit: 'Brightens & improves texture'
        }
      ],
      routine_name: 'Essential Glow Duo'
    }
  };

  // Open quiz modal
  function openQuiz() {
    quizOverlay.classList.add('is-open');
    quizModal.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    resetQuiz();
  }

  // Close quiz modal
  function closeQuiz() {
    quizOverlay.classList.remove('is-open');
    quizModal.classList.remove('is-open');
    document.body.style.overflow = '';
  }

  // Reset quiz to initial state
  function resetQuiz() {
    quizState = {
      step: 1,
      answers: {
        skin_type: null,
        concern: null,
        experience: null
      }
    };

    // Reset step visibility
    document.querySelectorAll('.skin-quiz-step').forEach(step => {
      step.style.display = 'none';
    });
    document.getElementById('quiz-step-1').style.display = 'block';

    // Hide results
    document.getElementById('quiz-results').style.display = 'none';
  }

  // Navigate to next step
  function goToStep(step) {
    // Hide current step
    document.querySelectorAll('.skin-quiz-step').forEach(s => {
      s.style.display = 'none';
    });

    // Show next step
    const nextStep = document.getElementById(`quiz-step-${step}`);
    if (nextStep) {
      nextStep.style.display = 'block';
      quizState.step = step;
    }
  }

  // Show quiz results
  function showResults() {
    // Hide all steps
    document.querySelectorAll('.skin-quiz-step').forEach(step => {
      step.style.display = 'none';
    });

    // Get recommendation key
    const key = `${quizState.answers.skin_type}_${quizState.answers.concern}`;
    const recommendation = productRecommendations[key] || productRecommendations['default'];

    // Render recommendations
    renderRecommendations(recommendation);

    // Show results
    document.getElementById('quiz-results').style.display = 'block';
  }

  // Render product recommendations
  function renderRecommendations(recommendation) {
    const container = document.getElementById('quiz-recommendations');

    let html = `
      <div style="text-align: center; margin-bottom: 20px;">
        <h3 style="font-family: var(--font-serif); font-size: 1.3rem; color: var(--text-heading); margin-bottom: 8px;">
          ${recommendation.routine_name}
        </h3>
        <p style="color: var(--text-muted); font-size: 0.9rem;">
          Perfect for your ${quizState.answers.skin_type.replace('_', ' ')} skin with ${quizState.answers.concern.replace('_', ' ')} concerns
        </p>
      </div>
    `;

    recommendation.products.forEach(product => {
      html += `
        <div class="quiz-product-card" data-product-id="${product.id}">
          <div class="quiz-product-image">
            <img src="assets/images/${product.image}" alt="${product.name}" loading="lazy">
          </div>
          <div class="quiz-product-details">
            <div class="quiz-product-name">${product.name}</div>
            <div class="quiz-product-benefit">${product.benefit}</div>
            <div class="quiz-product-price">Rs. ${product.price.toLocaleString()}</div>
          </div>
        </div>
      `;
    });

    container.innerHTML = html;
  }

  // Add entire routine to cart
  function addEntireRoutine() {
    const recommendations = document.querySelectorAll('.quiz-product-card');
    const productIds = [];

    recommendations.forEach(card => {
      const productId = card.dataset.productId;
      if (productId) {
        productIds.push(parseInt(productId));
      }
    });

    // Add products to cart sequentially
    let addedCount = 0;
    productIds.forEach((productId, index) => {
      setTimeout(() => {
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
          addedCount++;
          if (res.success) {
            const badge = document.querySelector('.cart-badge');
            if (badge) badge.textContent = res.cart_count;

            if (addedCount === productIds.length) {
              window.showToast('Complete routine added to cart with 10% discount!', 'success');
              closeQuiz();

              // Open cart drawer
              const cartDrawer = document.getElementById('cart-drawer');
              const cartDrawerOverlay = document.getElementById('cart-drawer-overlay');
              if (cartDrawer && cartDrawerOverlay) {
                cartDrawer.classList.add('is-open');
                cartDrawerOverlay.classList.add('is-open');
                document.body.style.overflow = 'hidden';

                // Load cart items
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
                      // Render cart items manually
                      const cartBody = document.getElementById('cart-drawer-body');
                      const subtotalEl = document.getElementById('cart-drawer-subtotal');

                      if (res.items.length === 0) {
                        cartBody.innerHTML = `
                          <div class="cart-drawer-empty">
                            <i class="bi bi-bag-x"></i>
                            <p>Your shopping cart is currently empty</p>
                            <a href="products.php" class="btn btn-primary btn-sm" style="margin-top: 16px;">Start Shopping</a>
                          </div>
                        `;
                        subtotalEl.textContent = 'Rs. 0';
                      } else {
                        let html = '';
                        res.items.forEach(item => {
                          html += `
                            <div class="cart-drawer-item" data-cart-id="${item.cart_id}">
                              <div class="cart-drawer-item-image">
                                <img src="assets/images/${item.image}" alt="${item.name}" loading="lazy">
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
                        subtotalEl.textContent = `Rs. ${parseInt(res.subtotal).toLocaleString()}`;
                      }
                    }
                  });
                }
              }
            }
          }
        });
      }, index * 300); // Stagger requests
    });
  }

  // Event listeners
  if (quizTrigger) {
    quizTrigger.addEventListener('click', openQuiz);
  }

  if (quizClose) {
    quizClose.addEventListener('click', closeQuiz);
  }

  if (quizOverlay) {
    quizOverlay.addEventListener('click', closeQuiz);
  }

  // Quiz option clicks
  document.querySelectorAll('.quiz-option').forEach(option => {
    option.addEventListener('click', function() {
      const step = this.closest('.skin-quiz-step').dataset.step;
      const value = this.dataset.value;

      // Store answer
      if (step === '1') quizState.answers.skin_type = value;
      if (step === '2') quizState.answers.concern = value;
      if (step === '3') quizState.answers.experience = value;

      // Navigate to next step or show results
      if (parseInt(step) < 3) {
        goToStep(parseInt(step) + 1);
      } else {
        showResults();
      }
    });
  });

  // Retake quiz button
  const retakeBtn = document.getElementById('retake-quiz');
  if (retakeBtn) {
    retakeBtn.addEventListener('click', resetQuiz);
  }

  // Add entire routine button
  const addRoutineBtn = document.getElementById('add-entire-routine');
  if (addRoutineBtn) {
    addRoutineBtn.addEventListener('click', addEntireRoutine);
  }

  // Close on escape key
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && quizModal.classList.contains('is-open')) {
      closeQuiz();
    }
  });

  // Handle dynamic cart drawer events
  document.addEventListener('click', (e) => {
    const qtyBtn = e.target.closest('.js-drawer-qty-btn');
    const removeBtn = e.target.closest('.js-drawer-remove-item');

    if (qtyBtn) {
      const item = qtyBtn.closest('.cart-drawer-item');
      const cartId = item.dataset.cartId;
      const change = parseInt(qtyBtn.dataset.change);
      const qtyInput = item.querySelector('.cart-drawer-qty-val');
      let newQty = parseInt(qtyInput.value) + change;

      if (newQty < 1) return;

      fetch('api/cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ action: 'update', cart_id: cartId, qty: newQty })
      })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          // Reload cart drawer
          const cartBody = document.getElementById('cart-drawer-body');
          const subtotalEl = document.getElementById('cart-drawer-subtotal');

          if (res.items.length === 0) {
            cartBody.innerHTML = `
              <div class="cart-drawer-empty">
                <i class="bi bi-bag-x"></i>
                <p>Your shopping cart is currently empty</p>
                <a href="products.php" class="btn btn-primary btn-sm" style="margin-top: 16px;">Start Shopping</a>
              </div>
            `;
            subtotalEl.textContent = 'Rs. 0';
          } else {
            let html = '';
            res.items.forEach(item => {
              html += `
                <div class="cart-drawer-item" data-cart-id="${item.cart_id}">
                  <div class="cart-drawer-item-image">
                    <img src="assets/images/${item.image}" alt="${item.name}" loading="lazy">
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
            subtotalEl.textContent = `Rs. ${parseInt(res.subtotal).toLocaleString()}`;
          }

          const badge = document.querySelector('.cart-badge');
          if (badge) badge.textContent = res.cart_count;
        }
      });
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
          const cartBody = document.getElementById('cart-drawer-body');
          const subtotalEl = document.getElementById('cart-drawer-subtotal');

          if (res.items.length === 0) {
            cartBody.innerHTML = `
              <div class="cart-drawer-empty">
                <i class="bi bi-bag-x"></i>
                <p>Your shopping cart is currently empty</p>
                <a href="products.php" class="btn btn-primary btn-sm" style="margin-top: 16px;">Start Shopping</a>
              </div>
            `;
            subtotalEl.textContent = 'Rs. 0';
          } else {
            let html = '';
            res.items.forEach(item => {
              html += `
                <div class="cart-drawer-item" data-cart-id="${item.cart_id}">
                  <div class="cart-drawer-item-image">
                    <img src="assets/images/${item.image}" alt="${item.name}" loading="lazy">
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
            subtotalEl.textContent = `Rs. ${parseInt(res.subtotal).toLocaleString()}`;
          }

          const badge = document.querySelector('.cart-badge');
          if (badge) badge.textContent = res.cart_count;
          window.showToast('Item removed from cart', 'info');
        }
      });
    }
  });
});
