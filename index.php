<?php
$pageTitle = 'Gloy Skincare | Glass Skin Moisturizer & Glutathione Serum';
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/config/database.php';

// Schema.org JSON-LD for Homepage
$homepageSchema = [
  "@context" => "https://schema.org",
  "@type" => "WebSite",
  "name" => "Gloy Skincare",
  "url" => "https://gloyskincare.com/",
  "description" => "Premium natural skincare products including Glass Skin Moisturizer and Glutathione Brightening Serum for hydrated, smooth, and radiant skin.",
  "potentialAction" => [
    "@type" => "SearchAction",
    "target" => "https://gloyskincare.com/products.php?search={search_term_string}",
    "query-input" => "required name=search_term_string"
  ]
];
?>

<!-- Schema.org JSON-LD for Homepage -->
<script type="application/ld+json">
<?php echo json_encode($homepageSchema, JSON_PRETTY_PRINT); ?>
</script>

<!-- 1. GLOY NATURAL GLOW HERO SECTION WITH INTEGRATED NAVBAR -->
<?php
$pdo = getDBConnection();

// Fetch Bestseller Products
$bsStmt = $pdo->query("SELECT * FROM products ORDER BY price ASC LIMIT 6");
$bestsellers = $bsStmt->fetchAll();

// Fetch Customer Reviews with Before/After Images
$revStmt = $pdo->query("
    SELECT r.*, u.name as user_name, p.name as product_name, p.image as product_image
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    WHERE r.approved = 1 AND r.before_image IS NOT NULL AND r.before_image != ''
    ORDER BY r.created_at DESC
    LIMIT 3
");
$testimonials = $revStmt->fetchAll();
?>

<!-- 1. GLOY NATURAL GLOW HERO SECTION WITH INTEGRATED NAVBAR -->
<section class="hero-luxury-section hero-integrated-container" id="hero-parallax-section">
  <!-- Hero Main Card Canvas Full Width -->
  <div class="hero-banner-card hero-fullwidth-card">
    <div class="hero-image-wrapper">
      
      <img src="assets/images/gloy_hero_natural_glow.jpg" alt="GLOY Natural Glow Hero" class="hero-bg-banner" loading="eager">
      <div class="hero-overlay-shade"></div>

      <!-- Top-Right Floating Glass Story Card -->
      <div class="hero-glass-card story-card-float">
        <div class="story-thumb">
          <img src="assets/images/gloy_serum_wood.jpg" alt="Gloy Pure Care" loading="lazy">
          <div class="play-badge"><i class="bi bi-play-fill"></i></div>
        </div>
        <div class="story-details">
          <span class="story-tag"><i class="bi bi-sparkles me-1"></i> Our Story</span>
          <h4 class="story-title">Pure Care &amp; Organic Ingredients</h4>
        </div>
      </div>

      <!-- Hero Content Overlay Left (Subtitle + Shop CTA) -->
      <div class="hero-left-overlay-box">
        <p class="hero-overlay-tagline">Pure ingredients. Instant results.<br>A more confident you.</p>
        <a href="products.php" class="btn btn-hero-cta">
          <span>Shop Now</span>
          <i class="bi bi-arrow-right ms-2"></i>
        </a>
      </div>

      <!-- Bottom-Left Floating Glass Bestseller Card with Close button -->
      <div class="hero-glass-card bestseller-card-float" id="hero-bestseller-card">
        <button class="glass-card-close" onclick="document.getElementById('hero-bestseller-card').style.display='none'" aria-label="Close">&times;</button>
        <div class="bestseller-thumb">
          <img src="assets/images/gloy_moisturizer_wood.jpg" alt="Gloy Bestseller" loading="lazy">
        </div>
        <div class="bestseller-details">
          <span class="bestseller-badge">Bestseller</span>
          <h4 class="bestseller-title">Shine Naturally Every Single Day</h4>
          <div class="bestseller-rating">
            <i class="bi bi-star-fill"></i>
            <i class="bi bi-star-fill"></i>
            <i class="bi bi-star-fill"></i>
            <i class="bi bi-star-fill"></i>
            <i class="bi bi-star-fill"></i>
            <span>4.98/5</span>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- 2. GLOY FLAGSHIP ESSENTIALS SECTION (SUNLIGHT WOODEN AESTHETIC PRODUCT PHOTOS) -->
<section id="gloy-flagship" class="section" style="background: var(--bg-main); border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color);">
  <div class="container">
    <div class="section-header" style="text-align:center; max-width:700px; margin:0 auto 48px;">
      <span class="bento-tag">Daily Skincare Ritual</span>
      <h2 class="section-title" style="font-family:var(--font-serif); font-size:2.6rem; color:var(--text-heading);">The Gloy Essential Flagship Duo</h2>
      <p class="section-desc" style="color:var(--text-body);">Formulated to deliver deep hydration, smooth texture, and an effortless radiant complexion every day.</p>
    </div>

    <div class="flagship-duo-grid" style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:32px;">
      
      <!-- Product 1 Card: Gloy Glass Skin Moisturizer -->
      <div class="bento-card reveal-on-scroll" style="background: var(--bg-surface); border: 1.5px solid var(--border-color); border-radius: var(--radius-lg); padding:36px; display:flex; flex-direction:column; position:relative; box-shadow:var(--shadow-sm);">
        <span class="product-badge" style="background: var(--primary-purple); color:#FFF; font-weight:bold; position:absolute; top:20px; right:20px; padding:6px 14px; border-radius:50px; font-size:0.75rem; text-transform:uppercase; z-index:5;">Flagship Moisturizer</span>
        
        <!-- Sunlight Wooden Photo Container -->
        <div style="height:260px; border-radius:var(--radius-md); overflow:hidden; margin-bottom:24px; position:relative; border:1px solid var(--border-color);">
          <img src="assets/images/gloy_moisturizer_wood.jpg" alt="Gloy Glass Skin Moisturizer Sunlit Wooden Photo" style="width:100%; height:100%; object-fit:cover;" loading="lazy">
          
          <button onclick="window.open('assets/images/gloy_box_ingredients.png', '_blank')" style="position:absolute; bottom:12px; right:12px; background:rgba(255,255,255,0.92); color:var(--primary-purple); border:1px solid var(--border-color); padding:6px 14px; border-radius:30px; font-size:0.75rem; font-weight:600; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,0.05);">
            <i class="bi bi-box me-1"></i> View Box Ingredients
          </button>
        </div>

        <span style="color:var(--primary-purple); font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em;">Deep Hydration &amp; Barrier Support</span>
        <h3 style="font-family:var(--font-serif); font-size:1.8rem; margin:8px 0 12px; color:var(--text-heading);">Gloy Glass Skin Moisturizer</h3>
        
        <p style="font-size:0.95rem; color:var(--text-body); line-height:1.6; margin-bottom:20px;">
          Give your skin the hydration it needs with Gloy Glass Skin Moisturizer. Its nourishing formula deeply hydrates, smooths, and softens the skin while helping improve the appearance of an uneven skin tone for a fresh, healthy-looking glow.
        </p>

        <div style="margin-bottom:24px; background: var(--bg-secondary); border-radius:var(--radius-md); padding:18px; border:1px solid var(--border-color);">
          <strong style="color:var(--text-heading); font-size:0.85rem; text-transform:uppercase; display:block; margin-bottom:10px;"><i class="bi bi-patch-check-fill text-primary me-1"></i> Key Ingredients (From Official Box):</strong>
          <ul style="font-size:0.85rem; color:var(--text-body); display:flex; flex-direction:column; gap:6px;">
            <li><strong>Ceramides:</strong> Helps support skin barrier &amp; lock in moisture</li>
            <li><strong>Kojic Acid &amp; Vitamin C:</strong> Helps brighten &amp; even skin tone</li>
            <li><strong>Shea Butter &amp; Olive Oil:</strong> Nourishes &amp; softens dry skin</li>
            <li><strong>Glycerine + Water:</strong> Draws moisture for lasting hydration</li>
          </ul>
        </div>

        <div style="margin-top:auto; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding-top:20px; border-top:1px solid var(--border-color);">
          <div>
            <span style="font-size:0.75rem; color:var(--text-muted); display:block; text-transform:uppercase;">Price (M.R.P.)</span>
            <strong style="font-size:1.6rem; color:var(--text-heading); font-family:var(--font-serif);">Rs. 2,150</strong>
          </div>
          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn btn-secondary js-add-to-cart" data-id="1" style="padding:12px 20px; font-weight:600; border-radius:50px; font-size:0.88rem;">
              <i class="bi bi-cart-plus me-1"></i> Add to Cart
            </button>
            <button class="btn btn-primary js-buy-now" data-id="1" style="padding:12px 24px; font-weight:700; border-radius:50px; font-size:0.88rem;">
              <i class="bi bi-lightning-charge-fill me-1"></i> Buy Now
            </button>
          </div>
        </div>
      </div>

      <!-- Product 2 Card: Glutathione Brightening Serum -->
      <div class="bento-card reveal-on-scroll" style="background: var(--bg-surface); border: 1.5px solid var(--border-color); border-radius: var(--radius-lg); padding:36px; display:flex; flex-direction:column; position:relative; box-shadow:var(--shadow-sm);">
        <span class="product-badge" style="background: var(--accent-terracotta); color:#FFF; font-weight:bold; position:absolute; top:20px; right:20px; padding:6px 14px; border-radius:50px; font-size:0.75rem; text-transform:uppercase; z-index:5;">Brightening Serum</span>
        
        <!-- Sunlight Wooden Photo Container -->
        <div style="height:260px; border-radius:var(--radius-md); overflow:hidden; margin-bottom:24px; position:relative; border:1px solid var(--border-color);">
          <img src="assets/images/gloy_serum_wood.jpg" alt="Gloy Glutathione Serum Sunlit Wooden Photo" style="width:100%; height:100%; object-fit:cover;" loading="lazy">
          
          <button onclick="window.open('assets/images/gloy_serum_box.png', '_blank')" style="position:absolute; bottom:12px; right:12px; background:rgba(255,255,255,0.92); color:var(--accent-terracotta); border:1px solid var(--border-color); padding:6px 14px; border-radius:30px; font-size:0.75rem; font-weight:600; cursor:pointer; box-shadow:0 4px 10px rgba(0,0,0,0.05);">
            <i class="bi bi-box me-1"></i> View Serum Box
          </button>
        </div>

        <span style="color:var(--accent-terracotta); font-size:0.8rem; font-weight:700; text-transform:uppercase; letter-spacing:0.1em;">Tone Perfecting &amp; Radiance</span>
        <h3 style="font-family:var(--font-serif); font-size:1.8rem; margin:8px 0 12px; color:var(--text-heading);">Glutathione Brightening Serum</h3>
        
        <p style="font-size:0.95rem; color:var(--text-body); line-height:1.6; margin-bottom:20px;">
          A lightweight brightening serum formulated with Glutathione, Vitamin C, and Niacinamide to help improve the appearance of dull skin and promote a more bright, fresh, and even-looking skin tone.
        </p>

        <div style="margin-bottom:24px; background: var(--bg-secondary); border-radius:var(--radius-md); padding:18px; border:1px solid var(--border-color);">
          <strong style="color:var(--text-heading); font-size:0.85rem; text-transform:uppercase; display:block; margin-bottom:10px;"><i class="bi bi-sparkles me-1" style="color:var(--accent-terracotta);"></i> Key Benefits (Daily Glow in Every Drop):</strong>
          <ul style="font-size:0.85rem; color:var(--text-body); display:flex; flex-direction:column; gap:6px;">
            <li><i class="bi bi-check-circle-fill text-primary me-1"></i> Evens out skin tone &amp; hydrates skin</li>
            <li><i class="bi bi-check-circle-fill text-primary me-1"></i> Reduces appearance of dark wrinkles &amp; spots</li>
            <li><i class="bi bi-check-circle-fill text-primary me-1"></i> Supports a fresh, radiant complexion</li>
            <li><i class="bi bi-check-circle-fill text-primary me-1"></i> Lightweight formula easy to layer daily</li>
          </ul>
        </div>

        <div style="margin-top:auto; display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px; padding-top:20px; border-top:1px solid var(--border-color);">
          <div>
            <span style="font-size:0.75rem; color:var(--text-muted); display:block; text-transform:uppercase;">Price</span>
            <strong style="font-size:1.6rem; color:var(--text-heading); font-family:var(--font-serif);">Rs. 2,250</strong>
          </div>
          <div style="display:flex; gap:10px; flex-wrap:wrap;">
            <button class="btn btn-secondary js-add-to-cart" data-id="2" style="padding:12px 20px; font-weight:600; border-radius:50px; font-size:0.88rem;">
              <i class="bi bi-cart-plus me-1"></i> Add to Cart
            </button>
            <button class="btn btn-primary js-buy-now" data-id="2" style="padding:12px 24px; font-weight:700; border-radius:50px; background:var(--accent-terracotta); font-size:0.88rem;">
              <i class="bi bi-lightning-charge-fill me-1"></i> Buy Now
            </button>
          </div>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- 3. ABOUT GLOY PHILOSOPHY SECTION (WITH HD MODEL PHOTO) -->
<section class="section" style="background: var(--bg-surface); padding:80px 0;">
  <div class="container">
    <div class="about-philosophy-grid" style="display:grid; grid-template-columns: 1fr 1fr; gap:50px; align-items:center;">
      <div>
        <span class="bento-tag">Our Philosophy</span>
        <h2 style="font-family:var(--font-serif); font-size:2.6rem; color:var(--text-heading); margin-bottom:20px; line-height:1.2;">
          Care, Hydration &amp; a Natural Glow For Every Skin Type
        </h2>
        <p style="font-size:1.02rem; color:var(--text-body); line-height:1.75; margin-bottom:16px;">
          At <strong>Gloy</strong>, we believe every skin type deserves care, hydration, and a glow.
        </p>
        <p style="font-size:1rem; color:var(--text-body); line-height:1.75; margin-bottom:16px;">
          Created for everyday skin needs, Gloy brings together carefully selected ingredients and thoughtful formulas to help your skin look hydrated, smooth, fresh, and naturally radiant.
        </p>
        <p style="font-size:1rem; color:var(--text-body); line-height:1.75; margin-bottom:28px;">
          From our <strong>Glutathione Serum with Vitamin C &amp; Niacinamide</strong> to our <strong>Glass Skin Moisturizer</strong>, every product is made with the goal of giving your skin the care it deserves while keeping your routine effortless.
        </p>

        <div style="display:flex; gap:24px;">
          <div>
            <strong style="font-size:2rem; color:var(--primary-purple); font-family:var(--font-serif);">100%</strong>
            <span style="font-size:0.8rem; color:var(--text-muted); display:block;">Gentle &amp; Thoughtful</span>
          </div>
          <div>
            <strong style="font-size:2rem; color:var(--primary-purple); font-family:var(--font-serif);">Effortless</strong>
            <span style="font-size:0.8rem; color:var(--text-muted); display:block;">Daily Routine</span>
          </div>
        </div>
      </div>

      <div style="position:relative;">
        <div style="border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-md); border:1.5px solid var(--border-color);">
          <img src="assets/images/gloy_model_skincare.jpg" alt="Glowing Skin Routine with Gloy Serum" style="width:100%; height:auto; max-height:440px; object-fit:cover;" loading="lazy">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- 4. BEFORE & AFTER VISUAL TRANSFORMATION GALLERY SECTION -->
<?php
$photoSql = "
    SELECT r.*, u.name as user_name, p.name as product_name, p.image as product_image, p.price as product_price
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    JOIN products p ON r.product_id = p.id
    WHERE r.approved = 1 AND r.before_image IS NOT NULL AND r.before_image != ''
    ORDER BY r.created_at DESC
";
$photoReviews = $pdo->query($photoSql)->fetchAll();
?>
<section class="section" style="background: var(--bg-main); border-top:1px solid var(--border-color); padding:80px 0;">
  <div class="container">
    <div class="section-header" style="max-width:780px; margin:0 auto 40px; text-align:center;">
      <span class="bento-tag"><i class="bi bi-camera-fill text-primary me-1"></i> Real Customer Results</span>
      <h2 class="section-title" style="font-family:var(--font-serif); font-size:2.6rem; color:var(--text-heading); margin:10px 0;">Before &amp; After Visual Gallery</h2>
      <p class="section-desc" style="color:var(--text-body); font-size:1.02rem;">
        See unfiltered before &amp; after results and genuine customer feedback from real people after using Gloy skincare routines.
      </p>
    </div>

    <!-- Visual Transformation Cards Grid -->
    <div class="transformation-gallery-grid" style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:28px; margin-bottom:48px;">
      <?php foreach ($photoReviews as $pr): ?>
        <div class="product-card" style="background:var(--bg-surface); border:1.5px solid var(--border-color); border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-sm); display:flex; flex-direction:column;">
          
          <!-- Image Container -->
          <div style="position:relative; width:100%; height:320px; overflow:hidden; background:var(--bg-main);">
            <img src="assets/images/<?php echo e($pr['before_image']); ?>" alt="Customer Skincare Results" style="width:100%; height:100%; object-fit:cover; cursor:pointer;" onclick="openHomePhotoModal('assets/images/<?php echo e($pr['before_image']); ?>', '<?php echo addslashes($pr['user_name']); ?> - <?php echo addslashes($pr['product_name']); ?>')" loading="lazy">
            
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
</section>

<!-- 5. SKINCARE TIPS & BENEFITS SECTION -->
<section class="section" style="background: var(--bg-surface); border-top:1px solid var(--border-color); padding:95px 0;" id="skincare-advice-section">
  <div class="container">
    <div class="section-header" style="text-align:center; max-width:780px; margin:0 auto 36px;">
      <span class="bento-tag"><i class="bi bi-patch-check-fill me-1"></i> Science-Backed Advice</span>
      <h2 class="section-title" style="font-family:var(--font-serif); font-size:2.8rem; color:var(--text-heading); margin-bottom:12px;">Expert Skincare Advice</h2>
      <p class="section-desc" style="color:var(--text-body); font-size:1.05rem;">Dermatologist-approved rituals and active ingredient pairing tips to help you build an effective, effortless daily routine.</p>
    </div>

    <!-- Interactive Category Filter Tabs -->
    <div class="advice-filter-bar">
      <button type="button" class="advice-filter-btn active" data-filter="all">
        <i class="bi bi-grid-fill"></i> All Advice (6)
      </button>
      <button type="button" class="advice-filter-btn" data-filter="morning">
        <i class="bi bi-sun-fill"></i> Morning (AM)
      </button>
      <button type="button" class="advice-filter-btn" data-filter="night">
        <i class="bi bi-moon-stars-fill"></i> Night (PM)
      </button>
      <button type="button" class="advice-filter-btn" data-filter="barrier">
        <i class="bi bi-shield-check"></i> Barrier &amp; Actives
      </button>
    </div>

    <!-- Enhanced Interactive Advice Cards Grid -->
    <div class="advice-card-grid" id="advice-cards-container">
      
      <!-- Card 1: Hydration -->
      <div class="skincare-advice-card reveal-on-scroll" data-category="morning night barrier">
        <div class="advice-card-top">
          <span class="advice-step-badge">Step 01 • Hydration</span>
          <span class="advice-time-pill"><i class="bi bi-clock-history"></i> AM &amp; PM</span>
        </div>
        <div class="advice-icon-wrap advice-icon-sage">
          <i class="bi bi-droplet-fill"></i>
        </div>
        <span class="advice-target-tag"><i class="bi bi-bullseye"></i> Target: Dehydration &amp; Dullness</span>
        <h3 class="advice-card-title">Deep Cellular Hydration</h3>
        <p class="advice-card-desc">Hydrate both internally and topically. Multi-molecular weight hyaluronic acid binds up to 1000x its weight in water to keep barrier plump.</p>
        <div class="advice-pro-tip">
          <i class="bi bi-lightbulb-fill text-warning me-1"></i> <strong>Pro Tip:</strong> Apply moisturizer while face is still slightly damp for 3x deeper moisture lock.
        </div>
        <div class="advice-card-footer">
          <a href="products.php" class="advice-product-link">
            <span>Pair with: <strong>Glass Skin Moisturizer</strong></span> <i class="bi bi-arrow-right"></i>
          </a>
          <button type="button" class="advice-copy-btn" title="Copy Skincare Tip" onclick="copyAdviceTip('Deep Cellular Hydration: Apply moisturizer on damp skin to lock in 3x moisture.')">
            <i class="bi bi-clipboard"></i>
          </button>
        </div>
      </div>

      <!-- Card 2: Sun Protection -->
      <div class="skincare-advice-card reveal-on-scroll" data-category="morning">
        <div class="advice-card-top">
          <span class="advice-step-badge" style="background:var(--bg-accent-blush); color:var(--accent-terracotta);">Step 02 • UV Shield</span>
          <span class="advice-time-pill"><i class="bi bi-sun"></i> Morning Only</span>
        </div>
        <div class="advice-icon-wrap advice-icon-terracotta">
          <i class="bi bi-sun-fill"></i>
        </div>
        <span class="advice-target-tag"><i class="bi bi-bullseye"></i> Target: Sun Spots &amp; Photo-Aging</span>
        <h3 class="advice-card-title">Broad-Spectrum UV Defense</h3>
        <p class="advice-card-desc">UVA rays penetrate glass and clouds year-round. Daily broad-spectrum SPF prevents 80% of premature fine lines and persistent hyperpigmentation.</p>
        <div class="advice-pro-tip">
          <i class="bi bi-lightbulb-fill text-warning me-1"></i> <strong>Pro Tip:</strong> Use the 2-finger sunscreen rule for face and neck. Reapply every 3-4 hours outdoors.
        </div>
        <div class="advice-card-footer">
          <a href="products.php" class="advice-product-link">
            <span>Pair with: <strong>Daily Glow Shield</strong></span> <i class="bi bi-arrow-right"></i>
          </a>
          <button type="button" class="advice-copy-btn" title="Copy Skincare Tip" onclick="copyAdviceTip('Broad-Spectrum UV Defense: Apply SPF every single day rain or shine to prevent hyperpigmentation.')">
            <i class="bi bi-clipboard"></i>
          </button>
        </div>
      </div>

      <!-- Card 3: Antioxidant Brightening -->
      <div class="skincare-advice-card reveal-on-scroll" data-category="morning night">
        <div class="advice-card-top">
          <span class="advice-step-badge">Step 03 • Actives</span>
          <span class="advice-time-pill"><i class="bi bi-stars"></i> AM / PM</span>
        </div>
        <div class="advice-icon-wrap advice-icon-sage">
          <i class="bi bi-stars"></i>
        </div>
        <span class="advice-target-tag"><i class="bi bi-bullseye"></i> Target: Melanin &amp; Acne Scars</span>
        <h3 class="advice-card-title">Antioxidant Brightening Synergy</h3>
        <p class="advice-card-desc">Glutathione combined with Vitamin C and Niacinamide neutralizes oxidative stress and halts excess melanin production at the source.</p>
        <div class="advice-pro-tip">
          <i class="bi bi-lightbulb-fill text-warning me-1"></i> <strong>Pro Tip:</strong> Pat 3-4 drops gently into clean skin before thicker creams for maximum active absorption.
        </div>
        <div class="advice-card-footer">
          <a href="products.php" class="advice-product-link">
            <span>Pair with: <strong>Glutathione Serum</strong></span> <i class="bi bi-arrow-right"></i>
          </a>
          <button type="button" class="advice-copy-btn" title="Copy Skincare Tip" onclick="copyAdviceTip('Antioxidant Brightening: Pat 3-4 drops of Glutathione Serum into skin before moisturizer for glass-like radiance.')">
            <i class="bi bi-clipboard"></i>
          </button>
        </div>
      </div>

      <!-- Card 4: Gentle Cleansing -->
      <div class="skincare-advice-card reveal-on-scroll" data-category="morning night">
        <div class="advice-card-top">
          <span class="advice-step-badge" style="background:var(--bg-accent-blush); color:var(--accent-terracotta);">Step 04 • Cleanse</span>
          <span class="advice-time-pill"><i class="bi bi-water"></i> Twice Daily</span>
        </div>
        <div class="advice-icon-wrap advice-icon-terracotta">
          <i class="bi bi-heart-fill"></i>
        </div>
        <span class="advice-target-tag"><i class="bi bi-bullseye"></i> Target: Pore Congestion &amp; Oil</span>
        <h3 class="advice-card-title">Barrier-Safe Gentle Cleansing</h3>
        <p class="advice-card-desc">Avoid harsh sulfated soaps that leave skin squeaky and tight. A pH 5.5 balanced cleanser removes sebum without disturbing the microbiome.</p>
        <div class="advice-pro-tip">
          <i class="bi bi-lightbulb-fill text-warning me-1"></i> <strong>Pro Tip:</strong> Wash with lukewarm water for at least 45-60 seconds to effectively dissolve impurities.
        </div>
        <div class="advice-card-footer">
          <a href="products.php" class="advice-product-link">
            <span>Pair with: <strong>Gentle Cleansing Wash</strong></span> <i class="bi bi-arrow-right"></i>
          </a>
          <button type="button" class="advice-copy-btn" title="Copy Skincare Tip" onclick="copyAdviceTip('Gentle Cleansing: Always use pH-balanced lukewarm water and avoid squeaky-clean harsh cleansers.')">
            <i class="bi bi-clipboard"></i>
          </button>
        </div>
      </div>

      <!-- Card 5: Night Barrier Repair -->
      <div class="skincare-advice-card reveal-on-scroll" data-category="night barrier">
        <div class="advice-card-top">
          <span class="advice-step-badge">Step 05 • Recovery</span>
          <span class="advice-time-pill"><i class="bi bi-moon-stars"></i> Night (PM)</span>
        </div>
        <div class="advice-icon-wrap advice-icon-sage">
          <i class="bi bi-moon-stars-fill"></i>
        </div>
        <span class="advice-target-tag"><i class="bi bi-bullseye"></i> Target: Redness &amp; Micro-Tears</span>
        <h3 class="advice-card-title">Overnight Lipid Barrier Repair</h3>
        <p class="advice-card-desc">Between 10 PM and 4 AM, skin cellular turnover peaks. Seal in ceramides and fatty acids overnight to rebuild compromised lipid matrix.</p>
        <div class="advice-pro-tip">
          <i class="bi bi-lightbulb-fill text-warning me-1"></i> <strong>Pro Tip:</strong> Layer a slightly thicker coat on dry patches before bed to wake up with bouncy, recovered skin.
        </div>
        <div class="advice-card-footer">
          <a href="products.php" class="advice-product-link">
            <span>Pair with: <strong>Flagship Barrier Cream</strong></span> <i class="bi bi-arrow-right"></i>
          </a>
          <button type="button" class="advice-copy-btn" title="Copy Skincare Tip" onclick="copyAdviceTip('Overnight Barrier Repair: Ceramides applied before sleep accelerate cellular renewal and seal moisture.')">
            <i class="bi bi-clipboard"></i>
          </button>
        </div>
      </div>

      <!-- Card 6: Consistency & Microbiome -->
      <div class="skincare-advice-card reveal-on-scroll" data-category="barrier">
        <div class="advice-card-top">
          <span class="advice-step-badge" style="background:var(--bg-accent-blush); color:var(--accent-terracotta);">Step 06 • Consistency</span>
          <span class="advice-time-pill"><i class="bi bi-check-all"></i> Daily Ritual</span>
        </div>
        <div class="advice-icon-wrap advice-icon-terracotta">
          <i class="bi bi-shield-check"></i>
        </div>
        <span class="advice-target-tag"><i class="bi bi-bullseye"></i> Target: Long-Term Radiance</span>
        <h3 class="advice-card-title">Consistency Over Complexity</h3>
        <p class="advice-card-desc">A simple 2-step ritual used faithfully outperforms a 10-step chaotic routine. Allow skin 28-35 days to complete its natural renewal cycle.</p>
        <div class="advice-pro-tip">
          <i class="bi bi-lightbulb-fill text-warning me-1"></i> <strong>Pro Tip:</strong> Take monthly progress photos under the same natural morning lighting to track genuine skin improvements.
        </div>
        <div class="advice-card-footer">
          <a href="products.php" class="advice-product-link">
            <span>Pair with: <strong>Complete 2-Step Duo</strong></span> <i class="bi bi-arrow-right"></i>
          </a>
          <button type="button" class="advice-copy-btn" title="Copy Skincare Tip" onclick="copyAdviceTip('Consistency Over Complexity: Stick to your Gloy 2-step routine for 30 days for maximum radiant skin.')">
            <i class="bi bi-clipboard"></i>
          </button>
        </div>
      </div>

    </div>
  </div>
</section>

<!-- Interactive Filter & Copy Script for Skincare Advice Section -->
<script>
document.addEventListener('DOMContentLoaded', () => {
  const filterBtns = document.querySelectorAll('.advice-filter-btn');
  const cards = document.querySelectorAll('.skincare-advice-card');

  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active'));
      btn.classList.add('active');

      const filterValue = btn.getAttribute('data-filter');

      cards.forEach(card => {
        const categories = card.getAttribute('data-category') || '';
        if (filterValue === 'all' || categories.includes(filterValue)) {
          card.style.display = 'flex';
          setTimeout(() => {
            card.style.opacity = '1';
            card.style.transform = 'translateY(0) scale(1)';
          }, 30);
        } else {
          card.style.opacity = '0';
          card.style.transform = 'scale(0.95)';
          setTimeout(() => {
            card.style.display = 'none';
          }, 250);
        }
      });
    });
  });
});

function copyAdviceTip(text) {
  if (navigator.clipboard) {
    navigator.clipboard.writeText(text).then(() => {
      showAdviceToast('✨ Skincare Tip copied to clipboard!');
    }).catch(() => {
      showAdviceToast('✨ Tip saved to your routine!');
    });
  } else {
    showAdviceToast('✨ Tip saved to your routine!');
  }
}

function showAdviceToast(msg) {
  let toast = document.getElementById('advice-toast-msg');
  if (!toast) {
    toast = document.createElement('div');
    toast.id = 'advice-toast-msg';
    toast.style.cssText = 'position:fixed; bottom:30px; right:30px; background:var(--text-heading, #2B2420); color:#fff; padding:12px 24px; border-radius:50px; font-size:0.9rem; font-weight:600; box-shadow:0 10px 30px rgba(0,0,0,0.2); z-index:99999; transition:all 0.3s ease; transform:translateY(100px); opacity:0; display:flex; align-items:center; gap:8px;';
    document.body.appendChild(toast);
  }
  toast.innerHTML = `<i class="bi bi-check-circle-fill text-success"></i> ${msg}`;
  toast.style.transform = 'translateY(0)';
  toast.style.opacity = '1';
  setTimeout(() => {
    toast.style.transform = 'translateY(100px)';
    toast.style.opacity = '0';
  }, 2500);
}
</script>

<!-- 6. INGREDIENTS HIGHLIGHT SECTION -->
<section class="section" style="background: var(--bg-accent-sage); border-top:1px solid var(--border-color); padding:90px 0;">
  <div class="container">
    <div class="section-header" style="text-align:center; max-width:750px; margin:0 auto 48px;">
      <span class="bento-tag"><i class="bi bi-flower1 me-1"></i> Natural Ingredients</span>
      <h2 class="section-title" style="font-family:var(--font-serif); font-size:2.6rem; color:var(--text-heading);">Power of Nature</h2>
      <p class="section-desc" style="color:var(--text-body);">Discover the natural ingredients that make Gloy products effective and gentle on your skin.</p>
    </div>

    <div class="ingredients-highlight-grid" style="display:grid; grid-template-columns: 1.15fr 1fr; gap:40px; align-items:center;">
      <div>
        <div class="ingredients-2col-boxes" style="display:grid; grid-template-columns: 1fr 1fr; gap:16px;">
          <div style="background:var(--bg-surface); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color);">
            <i class="bi bi-water" style="font-size:1.8rem; color:var(--primary-purple); margin-bottom:10px; display:inline-block;"></i>
            <h4 style="font-family:var(--font-serif); font-size:1.05rem; color:var(--text-heading); margin-bottom:6px;">Hyaluronic Acid</h4>
            <p style="font-size:0.82rem; color:var(--text-body); line-height:1.5;">Deeply hydrates and plumps skin for a youthful appearance.</p>
          </div>
          <div style="background:var(--bg-surface); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color);">
            <i class="bi bi-flower2" style="font-size:1.8rem; color:var(--accent-terracotta); margin-bottom:10px; display:inline-block;"></i>
            <h4 style="font-family:var(--font-serif); font-size:1.05rem; color:var(--text-heading); margin-bottom:6px;">Vitamin C</h4>
            <p style="font-size:0.82rem; color:var(--text-body); line-height:1.5;">Brightens skin and helps fade dark spots for even tone.</p>
          </div>
          <div style="background:var(--bg-surface); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color);">
            <i class="bi bi-stars" style="font-size:1.8rem; color:var(--primary-purple); margin-bottom:10px; display:inline-block;"></i>
            <h4 style="font-family:var(--font-serif); font-size:1.05rem; color:var(--text-heading); margin-bottom:6px;">Niacinamide</h4>
            <p style="font-size:0.82rem; color:var(--text-body); line-height:1.5;">Strengthens skin barrier and reduces appearance of pores.</p>
          </div>
          <div style="background:var(--bg-surface); padding:20px; border-radius:var(--radius-md); border:1px solid var(--border-color);">
            <i class="bi bi-tree" style="font-size:1.8rem; color:var(--accent-terracotta); margin-bottom:10px; display:inline-block;"></i>
            <h4 style="font-family:var(--font-serif); font-size:1.05rem; color:var(--text-heading); margin-bottom:6px;">Shea Butter</h4>
            <p style="font-size:0.82rem; color:var(--text-body); line-height:1.5;">Nourishes and moisturizes dry, sensitive skin.</p>
          </div>
        </div>
      </div>
      <div style="position:relative;">
        <div style="border-radius:var(--radius-lg); overflow:hidden; box-shadow:var(--shadow-md); border:1.5px solid var(--border-color);">
          <img src="assets/images/gloy_power_of_nature_hd.jpg" alt="Natural Skincare Ingredients" style="width:100%; height:auto; max-height:400px; object-fit:cover;" loading="lazy">
        </div>
      </div>
    </div>
  </div>
</section>

<!-- INLINE DETAIL VIEW MODAL CONTAINER -->
<div class="modal-overlay" id="product-detail-modal">
  <div class="modal-container"></div>
</div>

<!-- Photo Lightbox Modal for Homepage -->
<div class="modal-overlay" id="home-photo-modal-overlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.85); z-index:9999; align-items:center; justify-content:center; padding:20px;">
  <div style="position:relative; max-width:700px; width:100%; background:var(--bg-surface); border-radius:var(--radius-lg); overflow:hidden; box-shadow:0 20px 40px rgba(0,0,0,0.4);">
    <button onclick="closeHomePhotoModal()" style="position:absolute; top:12px; right:12px; background:rgba(0,0,0,0.6); color:#fff; border:none; width:36px; height:36px; border-radius:50%; display:flex; align-items:center; justify-content:center; cursor:pointer; z-index:10;">
      <i class="bi bi-x-lg"></i>
    </button>
    <img id="home-photo-modal-img" src="" alt="Transformation" style="width:100%; max-height:80vh; object-fit:contain; background:#000;">
    <div id="home-photo-modal-caption" style="padding:14px 20px; font-size:0.9rem; color:var(--text-heading); font-weight:600; background:var(--bg-surface); border-top:1px solid var(--border-color);"></div>
  </div>
</div>

<script>
function openHomePhotoModal(src, caption) {
  const modal = document.getElementById('home-photo-modal-overlay');
  const img = document.getElementById('home-photo-modal-img');
  const cap = document.getElementById('home-photo-modal-caption');
  if (modal && img) {
    img.src = src;
    if (cap) cap.textContent = caption || 'Customer Transformation';
    modal.style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }
}

function closeHomePhotoModal() {
  const modal = document.getElementById('home-photo-modal-overlay');
  if (modal) {
    modal.style.display = 'none';
    document.body.style.overflow = '';
  }
}

document.getElementById('home-photo-modal-overlay')?.addEventListener('click', (e) => {
  if (e.target.id === 'home-photo-modal-overlay') closeHomePhotoModal();
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

