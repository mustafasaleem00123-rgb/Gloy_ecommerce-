  <!-- Footer Section -->
  <footer class="site-footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-brand">
          <a href="index.php" class="brand-logo">GLOY<span>.</span></a>
          <p class="footer-desc">
            At Gloy, we believe every skin type deserves care, hydration, and a glow. Created for everyday skin needs with carefully selected ingredients for effortless beauty.
          </p>
          <div style="display:flex; flex-wrap:wrap; gap:12px 18px; font-size:0.9rem; align-items:center; margin-top:18px;">
            <a href="https://www.facebook.com/profile.php?id=61566772826261" target="_blank" rel="noopener" aria-label="Facebook" style="display:inline-flex; align-items:center; gap:6px; color:var(--text-heading); font-weight:600;">
              <i class="bi bi-facebook" style="font-size:1.15rem; color:#1877F2;"></i>
              <span>Facebook</span>
            </a>
            <a href="https://www.instagram.com/" target="_blank" rel="noopener" aria-label="Instagram" style="display:inline-flex; align-items:center; gap:6px; color:var(--text-heading); font-weight:600;">
              <i class="bi bi-instagram" style="font-size:1.15rem; color:#E4405F;"></i>
              <span>Instagram</span>
            </a>
            <a href="https://wa.me/923168882788" target="_blank" rel="noopener" style="display:inline-flex; align-items:center; gap:6px; color:var(--text-heading); font-weight:600;">
              <i class="bi bi-whatsapp" style="font-size:1.15rem; color:#25D366;"></i>
              <span>0316 8882788</span>
            </a>
            <a href="mailto:gloyskincare@gmail.com" style="display:inline-flex; align-items:center; gap:6px; color:var(--text-heading); font-weight:600;">
              <i class="bi bi-envelope-fill" style="font-size:1.15rem; color:#EA4335;"></i>
              <span>gloyskincare@gmail.com</span>
            </a>
          </div>
        </div>

        <div>
          <h4 class="footer-title">Explore</h4>
          <ul class="footer-links">
            <li><a href="products.php">Shop All Products</a></li>
            <li><a href="products.php?category=Serums">Glutathione Serum</a></li>
            <li><a href="products.php?category=Moisturizers">Glass Skin Moisturizer</a></li>
            <li><a href="about.php">About Gloy</a></li>
            <li><a href="testimonials.php">Verified Customer Reviews</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-title">Customer Care</h4>
          <ul class="footer-links">
            <li><a href="about.php">Our Story &amp; Philosophy</a></li>
            <li><a href="testimonials.php">Verified Customer Reviews</a></li>
            <li><a href="contact.php">Contact Support Page</a></li>
            <li><a href="account.php">Track Order &amp; Account</a></li>
          </ul>
        </div>

        <div>
          <h4 class="footer-title">Join The Glow Club</h4>
          <p style="font-size:0.9rem; color:var(--text-body); margin-bottom:14px;">
            Subscribe to receive exclusive offers, hydration tips, and VIP skincare drops.
          </p>
          <form id="footer-newsletter-form" class="newsletter-form" novalidate>
            <div class="form-group">
              <input type="email" name="email" class="input-field" placeholder="e.g. yourname@gmail.com" pattern="[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" required>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Subscribe</button>
          </form>
        </div>
      </div>
    </div>
  </footer>

  <!-- Toast Notification Container -->
  <div class="toast-container"></div>

  <!-- Common Core JavaScript Files -->
  <script src="assets/js/main.js"></script>
  <script src="assets/js/cart.js"></script>
  <script src="assets/js/products.js"></script>

  <!-- Newsletter AJAX Form Script -->
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const newsletterForm = document.getElementById('footer-newsletter-form');
      if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
          e.preventDefault();
          const emailInput = newsletterForm.querySelector('input[name="email"]');
          const email = emailInput ? emailInput.value.trim() : '';

          // Strict Client-Side Email Validation (Letters in username required, valid domain)
          const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
          const emailParts = email.split('@');
          if (!email || !emailPattern.test(email) || emailParts.length !== 2 || !/[a-zA-Z]/.test(emailParts[0]) || !/[a-zA-Z]/.test(emailParts[1])) {
            window.showToast('Email username cannot be only numbers. Please include letters (e.g. ayesha@gmail.com or ayesha123@gmail.com).', 'error');
            if (emailInput) emailInput.focus();
            return;
          }

          const btn = newsletterForm.querySelector('button[type="submit"]');
          const originalBtnText = btn.innerHTML;
          btn.disabled = true;
          btn.innerHTML = '<i class="bi bi-hourglass-split"></i>';
          
          const formData = new FormData(newsletterForm);
          fetch('api/newsletter_signup.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
            if (data.success) {
              window.showToast(data.message, 'success');
              newsletterForm.reset();
            } else {
              window.showToast(data.message || 'Subscription failed', 'error');
            }
          })
          .catch(err => {
            btn.disabled = false;
            btn.innerHTML = originalBtnText;
            window.showToast('Network error, please try again.', 'error');
          });
        });
      }
    });
  </script>
</body>
</html>
