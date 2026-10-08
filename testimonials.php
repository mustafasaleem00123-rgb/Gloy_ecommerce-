<?php
/**
 * Gloy Skincare - Real Customer Results, Before & After Gallery & Photo Reviews
 */
$pageTitle = 'Verified Before & After Results & Customer Reviews | Gloy Skincare';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

$ratingFilter = isset($_GET['rating']) ? (int)$_GET['rating'] : 0;

$pdo = getDBConnection();

// Fetch Photo Reviews for Before & After Showcase
$photoSql = "
    SELECT r.*, u.name as user_name, p.name as product_name, p.image as product_image, p.price as product_price
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    WHERE r.approved = 1 AND r.before_image IS NOT NULL AND r.before_image != ''
    ORDER BY r.created_at DESC
";
$photoReviews = $pdo->query($photoSql)->fetchAll();

// Fetch All Reviews with Filter
$sql = "
    SELECT r.*, u.name as user_name, p.name as product_name, p.image as product_image
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    WHERE r.approved = 1
";
if ($ratingFilter > 0) {
    $sql .= " AND r.rating = " . (int)$ratingFilter;
}
$sql .= " ORDER BY r.created_at DESC";

$reviews = $pdo->query($sql)->fetchAll();

// Fetch products list for review form dropdown
$prodStmt = $pdo->query("SELECT id, name FROM products ORDER BY name ASC");
$allProducts = $prodStmt->fetchAll();
?>

<div class="container section" style="padding-top:40px;">
  
  <!-- Page Header -->
  <div class="section-header" style="max-width:780px; margin:0 auto 48px; text-align:center;">
    <span class="bento-tag"><i class="bi bi-patch-check-fill text-primary me-1"></i> Real Results &amp; Authentic Reviews</span>
    <h1 class="section-title" style="font-size:2.8rem; color:var(--text-heading); margin:10px 0;">Gloy Skin Transformations</h1>
    <p class="section-desc" style="color:var(--text-body); font-size:1.05rem;">
      See unfiltered before &amp; after results and genuine customer feedback from people with sensitive, dry, and uneven skin.
    </p>
  </div>

  <!-- 1. BEFORE & AFTER PHOTO TRANSFORMATION GALLERY -->
  <div style="margin-bottom:70px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; border-bottom:1px solid var(--border-color); padding-bottom:14px; flex-wrap:wrap; gap:12px;">
      <h2 style="font-size:1.6rem; color:var(--text-heading); margin:0;">
        <i class="bi bi-camera-fill text-primary me-2"></i> Before &amp; After Visual Gallery
      </h2>
      <a href="#submit-photo-review" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-circle me-1"></i> Submit Your Photos &amp; Review
      </a>
    </div>

    <div class="testimonials-gallery-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(min(100%, 320px), 1fr)); gap:32px;">
      <?php foreach ($photoReviews as $pr): ?>
        <div class="product-card" style="background:var(--bg-surface); border:1.5px solid var(--border-color); border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-sm); display:flex; flex-direction:column;">
          
          <!-- Image Container -->
          <div style="position:relative; width:100%; height:320px; overflow:hidden; background:var(--bg-main);">
            <img src="assets/images/<?php echo e($pr['before_image']); ?>" alt="Customer Skincare Results" style="width:100%; height:100%; object-fit:cover; cursor:pointer;" onclick="openPhotoModal('assets/images/<?php echo e($pr['before_image']); ?>', '<?php echo addslashes($pr['user_name']); ?> - <?php echo addslashes($pr['product_name']); ?>')" loading="lazy">
            
            <div style="position:absolute; bottom:12px; left:12px; background:rgba(0,0,0,0.8); color:#fff; font-size:0.75rem; padding:4px 12px; border-radius:20px; font-weight:600; backdrop-filter:blur(6px);">
              <i class="bi bi-patch-check-fill text-primary me-1"></i> Verified Transformation
            </div>
          </div>

          <div style="padding:24px; display:flex; flex-direction:column; flex:1;">
            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
              <span class="product-badge" style="position:static; margin:0; font-size:0.75rem; background:var(--bg-accent-sage); color:var(--primary-purple); font-weight:bold;">
                <?php echo e($pr['product_name']); ?>
              </span>
              <div style="color:#F59E0B; font-size:0.9rem; display:flex; gap:2px;">
                <?php for ($s = 1; $s <= 5; $s++): ?>
                  <i class="bi bi-star-fill<?php echo $s <= $pr['rating'] ? '' : '-empty'; ?>" style="<?php echo $s > $pr['rating'] ? 'color:#CBD5E1;' : ''; ?>"></i>
                <?php endfor; ?>
              </div>
            </div>

            <p style="font-size:0.95rem; color:var(--text-body); font-style:italic; line-height:1.6; margin-bottom:20px; flex:1;">
              "<?php echo e($pr['comment']); ?>"
            </p>

            <div style="display:flex; justify-content:space-between; align-items:center; padding-top:14px; border-top:1px dashed var(--border-color); font-size:0.85rem;">
              <div style="display:flex; align-items:center; gap:10px;">
                <div style="width:34px; height:34px; border-radius:50%; background:var(--primary-purple); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:0.85rem;">
                  <?php echo strtoupper(substr($pr['user_name'], 0, 1)); ?>
                </div>
                <div>
                  <strong style="color:var(--text-heading); display:block; font-size:0.9rem;"><?php echo e($pr['user_name']); ?></strong>
                  <span style="font-size:0.75rem; color:var(--text-muted);"><?php echo date('M d, Y', strtotime($pr['created_at'])); ?></span>
                </div>
              </div>
              <div style="display:flex; gap:6px;">
                <button class="btn btn-secondary btn-sm js-add-to-cart" data-id="<?php echo $pr['product_id']; ?>" style="padding:6px 12px; font-size:0.75rem;" title="Add to Cart">
                  <i class="bi bi-cart-plus me-1"></i> Add
                </button>
                <button class="btn btn-primary btn-sm js-buy-now" data-id="<?php echo $pr['product_id']; ?>" style="padding:6px 14px; font-size:0.75rem; font-weight:700;">
                  <i class="bi bi-lightning-charge-fill me-1"></i> Buy Now
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- 2. ALL CUSTOMER REVIEWS WITH FILTER -->
  <div style="margin-bottom:60px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; padding-bottom:16px; border-bottom:1px solid var(--border-color); flex-wrap:wrap; gap:12px;">
      <div>
        <h2 style="font-size:1.6rem; color:var(--text-heading); margin:0 0 4px 0;">Community Customer Reviews</h2>
        <span style="font-size:0.85rem; color:var(--text-muted);">Showing <?php echo count($reviews); ?> approved verified customer reviews</span>
      </div>

      <div style="display:flex; align-items:center; gap:12px;">
        <label style="font-size:0.85rem; font-weight:600; color:var(--text-heading);">Filter Rating:</label>
        <select onchange="location = this.value;" class="input-field" style="width:auto; padding:6px 14px; font-size:0.85rem; background:var(--bg-surface);">
          <option value="testimonials.php" <?php echo $ratingFilter == 0 ? 'selected' : ''; ?>>All Star Ratings</option>
          <option value="testimonials.php?rating=5" <?php echo $ratingFilter == 5 ? 'selected' : ''; ?>>5 Stars Only</option>
          <option value="testimonials.php?rating=4" <?php echo $ratingFilter == 4 ? 'selected' : ''; ?>>4 Stars Only</option>
        </select>
      </div>
    </div>

    <div class="testimonials-reviews-grid" style="display:grid; grid-template-columns:repeat(auto-fill, minmax(min(100%, 280px), 1fr)); gap:24px;">
      <?php if (empty($reviews)): ?>
        <div style="grid-column:1/-1; text-align:center; padding:50px 0; color:var(--text-muted);">
          <h3 style="color:var(--text-heading);">No reviews found matching this filter.</h3>
        </div>
      <?php else: ?>
        <?php foreach ($reviews as $r): ?>
          <div class="product-card" style="padding:24px; background:var(--bg-surface); border:1px solid var(--border-color); display:flex; flex-direction:column;">
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:14px;">
              <img src="assets/images/<?php echo e($r['product_image']); ?>" alt="<?php echo e($r['product_name']); ?>" style="width:40px; height:40px; object-fit:contain; background:var(--bg-main); border-radius:var(--radius-sm); padding:4px;">
              <div>
                <strong style="font-size:0.88rem; color:var(--text-heading); display:block;"><?php echo e($r['product_name']); ?></strong>
                <div style="color:#F59E0B; font-size:0.8rem; display:flex; gap:2px;">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                    <i class="bi bi-star-fill<?php echo $s <= $r['rating'] ? '' : '-empty'; ?>" style="<?php echo $s > $r['rating'] ? 'color:#CBD5E1;' : ''; ?>"></i>
                  <?php endfor; ?>
                </div>
              </div>
            </div>

            <p style="font-size:0.9rem; color:var(--text-body); font-style:italic; margin-bottom:18px; line-height:1.6; flex:1;">
              "<?php echo e($r['comment']); ?>"
            </p>

            <?php if (!empty($r['before_image'])): ?>
              <div style="margin-bottom:14px;">
                <img src="assets/images/<?php echo e($r['before_image']); ?>" alt="Review photo" style="width:60px; height:60px; object-fit:cover; border-radius:var(--radius-sm); cursor:pointer; border:1px solid var(--border-color);" onclick="openPhotoModal('assets/images/<?php echo e($r['before_image']); ?>', '<?php echo addslashes($r['user_name']); ?>')">
              </div>
            <?php endif; ?>

            <div style="display:flex; justify-content:space-between; align-items:center; padding-top:12px; border-top:1px dashed var(--border-color); font-size:0.78rem; color:var(--text-muted); margin-top:auto;">
              <strong style="color:var(--primary-purple);"><?php echo e($r['user_name']); ?></strong>
              <span><?php echo date('M d, Y', strtotime($r['created_at'])); ?></span>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- 3. SUBMIT A VERIFIED PHOTO REVIEW FORM -->
  <div id="submit-photo-review" class="review-form-card" style="max-width:700px; margin:0 auto; background:var(--bg-surface); border:1.5px solid var(--border-color); border-radius:var(--radius-lg); padding:36px 28px; box-shadow:var(--shadow-md);">
    <div style="text-align:center; margin-bottom:28px;">
      <span class="bento-tag"><i class="bi bi-camera me-1"></i> Community Voice</span>
      <h3 style="font-size:1.8rem; color:var(--text-heading); margin:8px 0 6px 0;">Share Your Skin Journey</h3>
      <p style="color:var(--text-muted); font-size:0.9rem;">Upload your before/after skin photos and help others discover their glow.</p>
    </div>

    <form id="photo-review-form" enctype="multipart/form-data">
      
      <div class="form-row-2col" style="margin-bottom:18px;">
        <div>
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Your Name *</label>
          <input type="text" name="name" class="input-field" placeholder="e.g. Ayesha K." value="<?php echo e($_SESSION['user_name'] ?? ''); ?>" required>
        </div>
        <div>
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Email Address (Optional)</label>
          <input type="email" name="email" class="input-field" placeholder="ayesha@example.com" value="<?php echo e($_SESSION['user_email'] ?? ''); ?>">
        </div>
      </div>

      <div class="form-row-2col" style="margin-bottom:18px;">
        <div>
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Gloy Product Tested *</label>
          <select name="product_id" class="input-field" required>
            <option value="">-- Choose Product --</option>
            <?php foreach ($allProducts as $prod): ?>
              <option value="<?php echo $prod['id']; ?>"><?php echo e($prod['name']); ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div>
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Star Rating *</label>
          <select name="rating" class="input-field">
            <option value="5">⭐⭐⭐⭐⭐ (5 Stars - Outstanding)</option>
            <option value="4">⭐⭐⭐⭐ (4 Stars - Very Good)</option>
            <option value="3">⭐⭐⭐ (3 Stars - Average)</option>
            <option value="2">⭐⭐ (2 Stars - Below Average)</option>
            <option value="1">⭐ (1 Star - Poor)</option>
          </select>
        </div>
      </div>

      <div style="margin-bottom:20px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px; color:var(--text-heading);">Your Experience &amp; Review *</label>
        <textarea name="comment" class="input-field" rows="4" placeholder="How did the formula feel on your skin? When did you notice results?" required></textarea>
      </div>

      <!-- Photo Uploads (Before & After) -->
      <div style="background:var(--bg-main); border:1px dashed var(--border-color); border-radius:var(--radius-md); padding:20px; margin-bottom:24px;">
        <h4 style="font-size:0.95rem; color:var(--text-heading); margin:0 0 12px 0;">
          <i class="bi bi-image text-primary me-1"></i> Upload Before &amp; After Photo (Optional)
        </h4>
        
        <div class="form-row-2col">
          <div>
            <label style="font-size:0.8rem; font-weight:600; display:block; margin-bottom:4px; color:var(--text-heading);">Before Photo / Split Image</label>
            <input type="file" name="before_image" accept="image/jpeg,image/png,image/webp" class="input-field" style="padding:6px; font-size:0.8rem;">
            <span style="font-size:0.72rem; color:var(--text-muted);">Max 5MB (JPG, PNG, WebP)</span>
          </div>

          <div>
            <label style="font-size:0.8rem; font-weight:600; display:block; margin-bottom:4px; color:var(--text-heading);">After Photo (Optional)</label>
            <input type="file" name="after_image" accept="image/jpeg,image/png,image/webp" class="input-field" style="padding:6px; font-size:0.8rem;">
            <span style="font-size:0.72rem; color:var(--text-muted);">Max 5MB (JPG, PNG, WebP)</span>
          </div>
        </div>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%; padding:14px; font-size:1rem;">
        <i class="bi bi-send-fill me-2"></i> Submit Verified Review
      </button>
    </form>
  </div>

</div>

<!-- Photo Lightbox Modal -->
<div class="modal-overlay" id="photo-modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; padding:20px;">
  <div style="position:relative; max-width:700px; width:100%; background:var(--bg-surface); border-radius:var(--radius-lg); overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,0.4);">
    <button onclick="closePhotoModal()" style="position:absolute; top:12px; right:12px; background:rgba(0,0,0,0.6); color:#fff; border:none; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:10;">
      <i class="bi bi-x-lg"></i>
    </button>
    <img id="photo-modal-img" src="" alt="Transformation" style="width:100%; max-height:80vh; object-fit:contain; background:#000;">
    <div id="photo-modal-caption" style="padding:14px 20px; font-size:0.9rem; color:var(--text-heading); font-weight:600; background:var(--bg-surface); border-top:1px solid var(--border-color);"></div>
  </div>
</div>

<script>
function openPhotoModal(src, caption) {
  const modal = document.getElementById('photo-modal-overlay');
  const img = document.getElementById('photo-modal-img');
  const cap = document.getElementById('photo-modal-caption');
  if (modal && img) {
    img.src = src;
    if (cap) cap.textContent = caption || 'Customer Transformation';
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
}

function closePhotoModal() {
  const modal = document.getElementById('photo-modal-overlay');
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = '';
  }
}

document.getElementById('photo-modal-overlay')?.addEventListener('click', (e) => {
  if (e.target.id === 'photo-modal-overlay') closePhotoModal();
});

// Photo Review Form AJAX Submission
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('photo-review-form');
  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const btn = form.querySelector('button[type="submit"]');
      const originalText = btn.innerHTML;
      btn.disabled = true;
      btn.innerHTML = 'Submitting Review...';

      const formData = new FormData(form);
      formData.append('csrf_token', (document.querySelector('meta[name="csrf-token"]') || {}).content || '');

      fetch('api/submit_review.php', {
        method: 'POST',
        body: formData
      })
      .then(res => res.json())
      .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (data.success) {
          window.showToast(data.message, 'success');
          form.reset();
          setTimeout(() => location.reload(), 1500);
        } else {
          window.showToast(data.message || 'Error submitting review', 'error');
        }
      })
      .catch(err => {
        console.error(err);
        btn.disabled = false;
        btn.innerHTML = originalText;
        window.showToast('Failed to submit review. Please try again.', 'error');
      });
    });
  }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
