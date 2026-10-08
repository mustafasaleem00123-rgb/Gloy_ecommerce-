/**
 * Gloy Skincare - Products Page AJAX Filter, Sort & Inline Detail Modal Handler
 */
document.addEventListener('DOMContentLoaded', () => {
  const shopGrid = document.getElementById('shop-product-grid');
  const filterForm = document.getElementById('product-filter-form');
  const sortSelect = document.getElementById('sort-by-select');
  const modalOverlay = document.getElementById('product-detail-modal');

  let savedScrollPosition = 0;

  // Helper function to generate star HTML string
  function getStarRatingHTML(rating) {
    const rInt = Math.round(rating);
    let stars = '<div style="display:inline-flex; gap:2px; color:#F59E0B; font-size:0.85rem;">';
    for (let i = 1; i <= 5; i++) {
      if (i <= rInt) {
        stars += '<i class="bi bi-star-fill"></i>';
      } else {
        stars += '<i class="bi bi-star" style="color:#CBD5E1;"></i>';
      }
    }
    stars += '</div>';
    return stars;
  }

  // 1. AJAX Grid Filtering & Sorting
  if (filterForm || sortSelect) {
    const updateGrid = () => {
      if (!shopGrid) return;
      shopGrid.style.opacity = '0.5';

      const formData = new FormData(filterForm || undefined);
      if (sortSelect) {
        formData.append('sort', sortSelect.value);
      }

      const params = new URLSearchParams(formData);

      fetch(`products.php?ajax=1&${params.toString()}`)
        .then(res => res.text())
        .then(html => {
          shopGrid.innerHTML = html;
          shopGrid.style.opacity = '1';
          // Update URL without reloading page
          history.pushState(null, '', `products.php?${params.toString()}`);
        })
        .catch(err => {
          console.error('Failed to update product grid:', err);
          shopGrid.style.opacity = '1';
        });
    };

    if (filterForm) {
      filterForm.addEventListener('change', updateGrid);
    }
    if (sortSelect) {
      sortSelect.addEventListener('change', updateGrid);
    }
  }

  // 2. Open Product Detail Modal (AJAX)
  document.addEventListener('click', (e) => {
    const detailsBtn = e.target.closest('.js-view-details');
    if (detailsBtn) {
      e.preventDefault();
      const productId = detailsBtn.dataset.id;
      if (!productId) return;

      savedScrollPosition = window.scrollY;

      // Show loader in modal
      if (modalOverlay) {
        const modalContainer = modalOverlay.querySelector('.modal-container');
        modalContainer.innerHTML = `
          <button class="modal-close-btn">&times;</button>
          <div style="text-align:center; padding:60px 0;">
            <div class="spinner" style="border:3px solid #E0E7FF; border-top:3px solid #6366F1; border-radius:50%; width:40px; height:40px; animation:spin 0.8s linear infinite; margin:0 auto 16px;"></div>
            <p style="color:var(--text-muted);">Loading details...</p>
          </div>
        `;
        modalOverlay.classList.add('is-active');
      }

      // Fetch details from backend
      fetch(`api/get_product.php?id=${productId}`)
        .then(res => res.json())
        .then(data => {
          if (!data.success) {
            window.showToast(data.message || 'Product not found', 'error');
            closeModal();
            return;
          }
          renderModalContent(data.product);
        })
        .catch(err => {
          console.error(err);
          window.showToast('Failed to load product details', 'error');
          closeModal();
        });
    }
  });

  // 3. Modal Close Handler
  const closeModal = () => {
    if (modalOverlay) {
      modalOverlay.classList.remove('is-active');
      window.scrollTo({ top: savedScrollPosition, behavior: 'instant' });
    }
  };

  document.addEventListener('click', (e) => {
    if (e.target.closest('.modal-close-btn') || e.target === modalOverlay) {
      closeModal();
    }
  });

  // 4. Render Dynamic Modal Content
  function renderModalContent(product) {
    const modalContainer = modalOverlay.querySelector('.modal-container');
    
    // Format Variants
    let variantsHTML = '';
    if (product.variants && product.variants.length > 0) {
      variantsHTML = `
        <div class="variant-picker">
          <span class="variant-label">Select Size / Volume:</span>
          <div class="variant-options">
            ${product.variants.map((v, i) => `
              <button type="button" class="variant-chip ${i === 0 ? 'selected' : ''}" 
                      data-variant-id="${v.id}" data-price="${v.price}">
                ${v.size_label} - Rs. ${parseInt(v.price).toLocaleString()}
              </button>
            `).join('')}
          </div>
        </div>
      `;
    }

    // Format Reviews List
    let reviewsHTML = '';
    if (product.reviews && product.reviews.length > 0) {
      reviewsHTML = product.reviews.map(r => `
        <div style="border-bottom:1px solid var(--border-color); padding:14px 0;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
            <strong style="color:var(--text-heading); font-size:0.95rem;">${r.user_name}</strong>
            ${getStarRatingHTML(r.rating)}
          </div>
          <p style="color:var(--text-body); font-size:0.9rem; margin-bottom:8px; line-height:1.5;">${r.comment}</p>
          ${r.before_image ? `
            <div style="margin:8px 0;">
              <img src="assets/images/${r.before_image}" alt="Review result photo" style="width:75px; height:75px; object-fit:cover; border-radius:6px; border:1px solid var(--border-color); cursor:pointer;" onclick="window.open('assets/images/${r.before_image}','_blank')" title="Click to view full image">
              <span style="display:block; font-size:0.7rem; color:var(--text-muted); margin-top:2px;"><i class="bi bi-patch-check-fill text-primary"></i> Verified Photo</span>
            </div>
          ` : ''}
          <span style="font-size:0.75rem; color:var(--text-muted);">${r.created_at_formatted}</span>
        </div>
      `).join('');
    } else {
      reviewsHTML = `<p style="color:var(--text-muted);">No reviews yet. Be the first to share your experience!</p>`;
    }

    // Modal Inner Layout HTML
    modalContainer.innerHTML = `
      <button class="modal-close-btn">&times;</button>
      <div class="modal-product-layout">
        <div class="modal-product-gallery">
          <img src="assets/images/${product.image}" alt="${product.name}" class="main-gallery-img">
        </div>

        <div class="modal-product-info">
          <span class="product-category">${product.category} • ${product.skin_type} Skin</span>
          <h2 class="product-title" style="font-size: 2rem; margin-top:6px; color:var(--text-heading);">${product.name}</h2>
          <p style="color:var(--text-body); margin-bottom:16px;">${product.subtitle || ''}</p>
          
          <div class="product-rating" style="margin-bottom: 20px;">
            ${getStarRatingHTML(product.rating_avg)}
            <span class="rating-count">(${product.rating_avg} / 5.0 • ${product.review_count} reviews)</span>
          </div>

          <div style="font-family: var(--font-serif); font-size: 1.75rem; font-weight: 600; color: var(--primary-purple); margin-bottom: 20px;" id="modal-product-price">
            Rs. ${parseInt(product.price).toLocaleString()}
          </div>

          ${variantsHTML}

          <div style="display:flex; gap:12px; align-items:center; margin-top:24px; flex-wrap:wrap;">
            <div class="qty-stepper">
              <button class="qty-btn js-qty-minus">-</button>
              <input type="number" class="qty-input" value="1" min="1" max="${product.stock}" id="modal-qty-input">
              <button class="qty-btn js-qty-plus">+</button>
            </div>
            <button class="btn btn-secondary js-modal-add-cart" data-product-id="${product.id}" style="flex:1; min-width:130px;">
              <i class="bi bi-cart-plus me-1"></i> Add to Cart
            </button>
            <button class="btn btn-primary js-modal-buy-now" data-product-id="${product.id}" style="flex:1; min-width:130px; font-weight:700;">
              <i class="bi bi-lightning-charge-fill me-1"></i> Buy Now
            </button>
          </div>

          <!-- Product Tabs -->
          <div class="product-tabs">
            <button type="button" class="tab-btn active" data-tab="desc">Description</button>
            <button type="button" class="tab-btn" data-tab="ingredients">Ingredients</button>
            <button type="button" class="tab-btn" data-tab="howtouse">How to Use</button>
            <button type="button" class="tab-btn" data-tab="reviews">Reviews (${product.review_count})</button>
          </div>

          <div class="tab-content active" id="tab-desc">
            <p style="color:var(--text-body); line-height:1.6;">${product.description}</p>
          </div>
          <div class="tab-content" id="tab-ingredients">
            <p style="color:var(--text-body); line-height:1.6;"><strong>Key Ingredients:</strong> ${product.ingredients}</p>
          </div>
          <div class="tab-content" id="tab-howtouse">
            <p style="color:var(--text-body); line-height:1.6;">${product.how_to_use}</p>
          </div>
          <div class="tab-content" id="tab-reviews">
            <div style="max-height:240px; overflow-y:auto; margin-bottom:20px;">
              ${reviewsHTML}
            </div>

            <!-- Review Form -->
            <form id="modal-review-form" enctype="multipart/form-data" style="border-top:1px solid var(--border-color); padding-top:16px;">
              <input type="hidden" name="product_id" value="${product.id}">
              <h4 style="font-size:1rem; margin-bottom:10px; color:var(--text-heading);">Leave a Review</h4>
              
              <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:10px;">
                <input type="text" name="name" class="input-field" placeholder="Your Name" style="padding:6px 12px; font-size:0.85rem;" required>
                <select name="rating" class="input-field" style="padding:6px 12px; font-size:0.85rem;">
                  <option value="5">⭐⭐⭐⭐⭐ (5 Stars)</option>
                  <option value="4">⭐⭐⭐⭐ (4 Stars)</option>
                  <option value="3">⭐⭐⭐ (3 Stars)</option>
                  <option value="2">⭐⭐ (2 Stars)</option>
                  <option value="1">⭐ (1 Star)</option>
                </select>
              </div>

              <textarea name="comment" class="input-field" rows="2" placeholder="Write your review experience..." required style="margin-bottom:10px; font-size:0.85rem;"></textarea>
              
              <div style="margin-bottom:12px; font-size:0.8rem; color:var(--text-muted);">
                <label style="display:block; margin-bottom:4px; font-weight:600; color:var(--text-heading);">Attach Before/After Photo (Optional):</label>
                <input type="file" name="before_image" accept="image/*" class="input-field" style="padding:4px 8px; font-size:0.75rem;">
              </div>

              <button type="submit" class="btn btn-secondary btn-sm"><i class="bi bi-send-fill me-1"></i> Submit Review</button>
            </form>
          </div>
        </div>
      </div>
    `;

    // 5. Variant Switcher Click Event
    const chipBtns = modalContainer.querySelectorAll('.variant-chip');
    let selectedVariantId = chipBtns.length > 0 ? chipBtns[0].dataset.variantId : null;

    chipBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        chipBtns.forEach(b => b.classList.remove('selected'));
        btn.classList.add('selected');
        selectedVariantId = btn.dataset.variantId;
        const newPrice = parseInt(btn.dataset.price).toLocaleString();
        modalContainer.querySelector('#modal-product-price').textContent = `Rs. ${newPrice}`;
      });
    });

    // 6. Quantity Stepper
    const qtyInput = modalContainer.querySelector('#modal-qty-input');
    modalContainer.querySelector('.js-qty-minus').addEventListener('click', () => {
      if (parseInt(qtyInput.value) > 1) qtyInput.value = parseInt(qtyInput.value) - 1;
    });
    modalContainer.querySelector('.js-qty-plus').addEventListener('click', () => {
      if (parseInt(qtyInput.value) < parseInt(qtyInput.max)) qtyInput.value = parseInt(qtyInput.value) + 1;
    });

    // 7. Modal Tabs Handler
    const tabBtns = modalContainer.querySelectorAll('.tab-btn');
    const tabContents = modalContainer.querySelectorAll('.tab-content');

    tabBtns.forEach(btn => {
      btn.addEventListener('click', () => {
        const targetTab = btn.dataset.tab;
        tabBtns.forEach(b => b.classList.remove('active'));
        tabContents.forEach(c => c.classList.remove('active'));

        btn.classList.add('active');
        modalContainer.querySelector(`#tab-${targetTab}`).classList.add('active');
      });
    });

    // 8. Add to Cart inside Modal
    const addCartBtn = modalContainer.querySelector('.js-modal-add-cart');
    addCartBtn.addEventListener('click', () => {
      const qty = parseInt(qtyInput.value) || 1;
      addCartBtn.disabled = true;
      addCartBtn.textContent = 'Adding...';

      fetch('api/cart_action.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'add',
          product_id: product.id,
          variant_id: selectedVariantId || '',
          qty: qty
        })
      })
      .then(res => res.json())
      .then(res => {
        addCartBtn.disabled = false;
        addCartBtn.innerHTML = '<i class="bi bi-bag-plus me-1"></i> Add to Cart';
        if (res.success) {
          window.showToast(`${product.name} added to your cart!`, 'success');
          // Update cart badge in header
          const badge = document.querySelector('.cart-badge');
          if (badge) badge.textContent = res.cart_count;
        } else {
          window.showToast(res.message || 'Error adding to cart', 'error');
        }
      })
      .catch(err => {
        console.error(err);
        addCartBtn.disabled = false;
        addCartBtn.innerHTML = '<i class="bi bi-bag-plus me-1"></i> Add to Cart';
        window.showToast('Network error adding to cart', 'error');
      });
    });

    // 8b. Buy Now inside Modal
    const modalBuyBtn = modalContainer.querySelector('.js-modal-buy-now');
    if (modalBuyBtn) {
      modalBuyBtn.addEventListener('click', () => {
        const qty = parseInt(qtyInput.value) || 1;
        modalBuyBtn.disabled = true;
        modalBuyBtn.textContent = 'Proceeding...';

        fetch('api/cart_action.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: new URLSearchParams({
            action: 'add',
            product_id: product.id,
            variant_id: selectedVariantId || '',
            qty: qty
          })
        })
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            window.location.href = 'checkout.php';
          } else {
            modalBuyBtn.disabled = false;
            modalBuyBtn.innerHTML = '<i class="bi bi-lightning-charge-fill me-1"></i> Buy Now';
            window.showToast(res.message || 'Error processing purchase', 'error');
          }
        })
        .catch(err => {
          console.error(err);
          modalBuyBtn.disabled = false;
          modalBuyBtn.innerHTML = '<i class="bi bi-lightning-charge-fill me-1"></i> Buy Now';
          window.showToast('Network error processing purchase', 'error');
        });
      });
    }

    // 9. Review Submission inside Modal
    const reviewForm = modalContainer.querySelector('#modal-review-form');
    if (reviewForm) {
      reviewForm.addEventListener('submit', (e) => {
        e.preventDefault();
        const submitBtn = reviewForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;

        const formData = new FormData(reviewForm);
        formData.append('csrf_token', (document.querySelector('meta[name="csrf-token"]') || {}).content || '');

        fetch('api/submit_review.php', {
          method: 'POST',
          body: formData
        })
        .then(res => res.json())
        .then(res => {
          submitBtn.disabled = false;
          if (res.success) {
            window.showToast(res.message, 'success');
            reviewForm.reset();
          } else {
            window.showToast(res.message, 'error');
          }
        })
        .catch(err => {
          console.error(err);
          submitBtn.disabled = false;
          window.showToast('Failed to submit review', 'error');
        });
      });
    }
  }
});
