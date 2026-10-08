<?php
$pageTitle = 'Frequently Asked Questions | Gloy Skincare';
require_once __DIR__ . '/includes/header.php';
?>

<div class="container section">
  <div class="section-header" style="max-width:700px;">
    <span class="section-subtitle">Help Center</span>
    <h1 class="section-title" style="color:var(--text-heading);">Frequently Asked Questions</h1>
    <p class="section-desc">Everything you need to know about our Gloy skincare products, ordering, shipping, and routine guidance.</p>
  </div>

  <div class="accordion-container" style="max-width:850px; margin:0 auto; display:flex; flex-direction:column; gap:16px;">
    
    <!-- CATEGORY 1: ORDERS -->
    <div style="margin-bottom:12px;">
      <h3 style="font-size:1.3rem; color:var(--text-heading);"><i class="bi bi-box-seam me-2" style="color:var(--primary-purple);"></i> Orders &amp; Tracking</h3>
    </div>

    <div class="accordion-item" style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0 24px;">
      <button type="button" class="accordion-header">
        <span style="color:var(--text-heading); font-weight:600;">How can I track my order status?</span>
        <span class="accordion-icon"><i class="bi bi-chevron-down"></i></span>
      </button>
      <div class="accordion-body">
        Once your order is placed, you can view live updates on your <a href="account.php" style="color:var(--primary-purple); text-decoration:underline;">My Account page</a> under Order History. You will also receive notifications when your order status updates to Shipped or Delivered.
      </div>
    </div>

    <div class="accordion-item" style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0 24px;">
      <button type="button" class="accordion-header">
        <span style="color:var(--text-heading); font-weight:600;">Can I modify or cancel an order after placing it?</span>
        <span class="accordion-icon"><i class="bi bi-chevron-down"></i></span>
      </button>
      <div class="accordion-body">
        Orders begin processing shortly after placement. If you need to make changes, please contact us promptly at <strong>gloyskincare@gmail.com</strong> or via WhatsApp at <strong>0316 8882788</strong>.
      </div>
    </div>

    <!-- CATEGORY 2: PAYMENTS -->
    <div style="margin-top:24px; margin-bottom:12px;">
      <h3 style="font-size:1.3rem; color:var(--text-heading);"><i class="bi bi-credit-card me-2" style="color:var(--primary-purple);"></i> Payments &amp; Checkout</h3>
    </div>

    <div class="accordion-item" style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0 24px;">
      <button type="button" class="accordion-header">
        <span style="color:var(--text-heading); font-weight:600;">What payment methods do you accept?</span>
        <span class="accordion-icon"><i class="bi bi-chevron-down"></i></span>
      </button>
      <div class="accordion-body">
        We support convenient payment methods:
        <ul style="list-style:disc; margin-left:20px; margin-top:8px;">
          <li><strong>Cash on Delivery (COD):</strong> Pay in cash directly to the courier upon delivery anywhere across Pakistan.</li>
          <li><strong>Credit / Debit Card:</strong> Secure payment gateway simulation for online card processing.</li>
        </ul>
      </div>
    </div>

    <!-- CATEGORY 3: SHIPPING -->
    <div style="margin-top:24px; margin-bottom:12px;">
      <h3 style="font-size:1.3rem; color:var(--text-heading);"><i class="bi bi-truck me-2" style="color:var(--primary-purple);"></i> Shipping &amp; Delivery</h3>
    </div>

    <div class="accordion-item" style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0 24px;">
      <button type="button" class="accordion-header">
        <span style="color:var(--text-heading); font-weight:600;">How long does shipping take?</span>
        <span class="accordion-icon"><i class="bi bi-chevron-down"></i></span>
      </button>
      <div class="accordion-body">
        Standard domestic shipping across Pakistan takes 2-4 business days.
      </div>
    </div>

    <div class="accordion-item" style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0 24px;">
      <button type="button" class="accordion-header">
        <span style="color:var(--text-heading); font-weight:600;">What ingredients are used in Gloy products?</span>
        <span class="accordion-icon"><i class="bi bi-chevron-down"></i></span>
      </button>
      <div class="accordion-body">
        Our flagship formulas include <strong>Ceramides, Shea Butter, Glycerine, Vitamin C, Kojic Acid, and Olive Oil</strong> for our Glass Skin Moisturizer, and <strong>Glutathione, Vitamin C, &amp; Niacinamide</strong> for our Brightening Serum. All products are manufactured at CosmeManix Labs, Korangi, Karachi.
      </div>
    </div>

    <!-- CATEGORY 4: SKINCARE ADVICE -->
    <div style="margin-top:24px; margin-bottom:12px;">
      <h3 style="font-size:1.3rem; color:var(--text-heading);"><i class="bi bi-flower1 me-2" style="color:var(--primary-purple);"></i> Ingredients &amp; Routine Advice</h3>
    </div>

    <div class="accordion-item" style="background:var(--bg-surface); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:0 24px;">
      <button type="button" class="accordion-header">
        <span style="color:var(--text-heading); font-weight:600;">Are Gloy products safe for sensitive skin?</span>
        <span class="accordion-icon"><i class="bi bi-chevron-down"></i></span>
      </button>
      <div class="accordion-body">
        Yes! Gloy products are formulated for everyday skin needs and have been tested by real community members on sensitive skin. They help nourish, hydrate, and maintain a healthy-looking skin barrier.
      </div>
    </div>

  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
