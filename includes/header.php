<?php
require_once __DIR__ . '/security.php'; // session + security bootstrap
require_once __DIR__ . '/auth_middleware.php';
require_once __DIR__ . '/cart_helper.php';

$cartData = get_cart_items();
$cartCount = $cartData['cart_count'];
$currentUser = get_current_user_data();
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <title><?php echo isset($pageTitle) ? e($pageTitle) . ' | GLOY Skincare' : 'GLOY Skincare | Hydrated, Smooth & Radiant Glow'; ?></title>

  <!-- Performance Optimization Meta Tags -->
  <meta name="description" content="Gloy Skincare - Premium natural skincare products including Glass Skin Moisturizer and Glutathione Brightening Serum for hydrated, smooth, and radiant skin.">
  <meta name="keywords" content="skincare, moisturizer, serum, glutathione, glass skin, natural skincare, Pakistan skincare, dry skin, oily skin, brightening serum">
  <meta name="robots" content="index, follow">
  <meta name="googlebot" content="index, follow">

  <!-- Open Graph / Social Media -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://gloyskincare.com/">
  <meta property="og:title" content="Gloy Skincare | Hydrated, Smooth & Radiant Glow">
  <meta property="og:description" content="Premium natural skincare products for hydrated, smooth, and radiant skin.">
  <meta property="og:image" content="https://gloyskincare.com/assets/images/gloy_moisturizer_wood.jpg">

  <!-- Twitter Card -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="Gloy Skincare | Hydrated, Smooth & Radiant Glow">
  <meta name="twitter:description" content="Premium natural skincare products for hydrated, smooth, and radiant skin.">
  <meta name="twitter:image" content="https://gloyskincare.com/assets/images/gloy_moisturizer_wood.jpg">

  <!-- Schema.org JSON-LD for Organization -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "Gloy Skincare",
    "url": "https://gloyskincare.com/",
    "logo": "https://gloyskincare.com/assets/images/gloy_moisturizer_wood.jpg",
    "description": "Premium natural skincare products including Glass Skin Moisturizer and Glutathione Brightening Serum for hydrated, smooth, and radiant skin.",
    "contactPoint": {
      "@type": "ContactPoint",
      "telephone": "+92-316-888-2788",
      "contactType": "customer service",
      "areaServed": "PK",
      "availableLanguage": "English"
    },
    "sameAs": [
      "https://www.facebook.com/gloyskincare",
      "https://www.instagram.com/gloyskincare"
    ]
  }
  </script>

  <!-- Image Optimization Instructions -->
  <!--
  WEBP IMAGE OPTIMIZATION RECOMMENDATIONS:
  ==========================================
  To achieve lightning-fast page load times (under 1 second), convert images to WebP format:

  1. Install ImageMagick or use online tools like:
     - https://convertio.co/jpg-webp/
     - https://cloudconvert.com/jpg-to-webp

  2. Convert current images to WebP:
     - gloy_moisturizer_wood.jpg → gloy_moisturizer_wood.webp
     - gloy_serum_wood.jpg → gloy_serum_wood.webp
     - gloy_hero_natural_glow.jpg → gloy_hero_natural_glow.webp
     - gloy_model_skincare.jpg → gloy_model_skincare.webp

  3. Add WebP support to HTML:
     <picture>
       <source srcset="assets/images/gloy_moisturizer_wood.webp" type="image/webp">
       <img src="assets/images/gloy_moisturizer_wood.jpg" alt="Product" loading="lazy">
     </picture>

  4. Expected benefits:
     - 25-35% smaller file sizes
     - Faster page load times
     - Better Google PageSpeed scores
     - Improved SEO rankings

  5. Current estimated load time: 2-3 seconds
     After WebP optimization: 0.8-1.2 seconds
  -->
  
  <!-- Google Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,400..800;1,400..800&family=Plus+Jakarta+Sans:ital,wght@0,300..800;1,300..800&display=swap" rel="stylesheet">
  
  <!-- Bootstrap Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- CSS Stylesheets -->
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <link rel="stylesheet" href="assets/css/3d.css">

  <!-- Three.js & GSAP Animation Libraries via CDN -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/r128/three.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>

  <!-- Skin Quiz JavaScript -->
  <script src="assets/js/skin-quiz.js"></script>
</head>
<body>

<?php if (!isset($hideStandardHeader) || !$hideStandardHeader): ?>
  <!-- Sticky Navbar Header -->
  <header class="site-header">
    <div class="container navbar">
      <a href="index.php" class="brand-logo">
        GLOY<span>.</span>
      </a>

      <!-- Desktop Navigation Menu -->
      <nav class="desktop-nav">
        <ul class="nav-menu">
          <li><a href="index.php" class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">Home</a></li>
          <li><a href="products.php" class="nav-link <?php echo $currentPage === 'products.php' ? 'active' : ''; ?>">Shop Products</a></li>
          <li><a href="about.php" class="nav-link <?php echo $currentPage === 'about.php' ? 'active' : ''; ?>">About Us</a></li>
          <li><a href="contact.php" class="nav-link <?php echo $currentPage === 'contact.php' ? 'active' : ''; ?>">Contact</a></li>
        </ul>
      </nav>

      <!-- Desktop Product Search Bar with Live Suggestions -->
      <div class="nav-search" style="position:relative;">
        <form action="products.php" method="GET" class="search-form" id="header-search-form">
          <input type="text" name="search" id="header-search-input" class="search-input" placeholder="Search products..." value="<?php echo isset($_GET['search']) ? e($_GET['search']) : ''; ?>" autocomplete="off">
          <button type="submit" class="search-btn" aria-label="Search">
            <i class="bi bi-search"></i>
          </button>
        </form>
        <div id="search-suggestions-box" class="search-suggestions-dropdown" style="display:none;"></div>
      </div>

      <div class="nav-actions">
        <!-- Skin Quiz Button (Desktop) -->
        <button class="js-skin-quiz-trigger desktop-quiz-btn" style="background: var(--bg-accent-sage); color: var(--primary-purple); border: 2px solid var(--primary-purple); padding: 9px 18px; border-radius: 50px; font-weight: 600; cursor: pointer; transition: all 0.3s ease; font-family: var(--font-sans); letter-spacing: 0.05em; text-transform: uppercase; font-size: 0.8rem; white-space: nowrap;">
          <i class="bi bi-magic me-1"></i> Skin Quiz
        </button>

        <!-- Track Order Direct Link (Desktop) -->
        <a href="account.php" class="track-order-link">Track Order</a>

        <!-- User Account Dropdown -->
        <?php if ($currentUser): ?>
          <div class="user-dropdown">
            <button class="icon-btn" title="My Account" aria-label="User Menu">
              <i class="bi bi-person" style="font-size:1.35rem;"></i>
            </button>
            <div class="dropdown-menu">
              <div style="padding: 10px 20px; border-bottom: 1px solid var(--border-color); font-size: 0.85rem;">
                <strong style="color:var(--text-heading);"><?php echo e($currentUser['name']); ?></strong>
                <div style="color:var(--text-muted); font-size: 0.75rem;"><?php echo e($currentUser['email']); ?></div>
              </div>
              <a href="account.php" class="dropdown-item"><i class="bi bi-box-seam me-2"></i> My Account &amp; Orders</a>
              <?php if ($currentUser['role'] === 'admin'): ?>
                <a href="admin/dashboard.php" class="dropdown-item" style="color:var(--primary-purple); font-weight:600;"><i class="bi bi-speedometer2 me-2"></i> Admin Dashboard</a>
              <?php endif; ?>
              <a href="logout.php" class="dropdown-item" style="color:#EF4444;"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </div>
          </div>
        <?php else: ?>
          <a href="login.php" class="icon-btn" title="Login / Register" aria-label="Login">
            <i class="bi bi-person" style="font-size:1.35rem;"></i>
          </a>
        <?php endif; ?>

        <!-- Cart Button & Counter Badge -->
        <button class="icon-btn js-open-cart-drawer" title="Shopping Cart" aria-label="Shopping Cart">
          <i class="bi bi-bag" style="font-size:1.35rem;"></i>
          <span class="cart-badge"><?php echo $cartCount; ?></span>
        </button>

        <!-- Mobile Nav Hamburger Toggle -->
        <button class="mobile-nav-toggle" id="mobile-nav-toggle-btn" aria-label="Toggle Navigation Menu">
          <i class="bi bi-list"></i>
        </button>
      </div>
    </div>
  </header>

  <!-- Mobile Navigation Drawer Overlay & Panel -->
  <div class="mobile-nav-overlay" id="mobile-nav-overlay"></div>
  <aside class="mobile-nav-drawer" id="mobile-nav-drawer" aria-label="Mobile Navigation">
    <div class="mobile-nav-header">
      <a href="index.php" class="brand-logo mobile-drawer-logo">
        GLOY<span>.</span>
      </a>
      <button class="mobile-nav-close js-close-mobile-nav" aria-label="Close Menu">
        <i class="bi bi-x-lg"></i>
      </button>
    </div>

    <!-- Mobile Drawer Search Bar -->
    <div class="mobile-nav-search">
      <form action="products.php" method="GET" class="mobile-search-form">
        <i class="bi bi-search mobile-search-icon"></i>
        <input type="text" name="search" class="mobile-search-input" placeholder="Search products..." value="<?php echo isset($_GET['search']) ? e($_GET['search']) : ''; ?>">
        <button type="submit" class="mobile-search-submit" aria-label="Search">
          <i class="bi bi-arrow-right"></i>
        </button>
      </form>
    </div>

    <!-- Mobile Navigation Links -->
    <ul class="mobile-nav-links">
      <li>
        <a href="index.php" class="mobile-nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
          <i class="bi bi-house-door"></i>
          <span>Home</span>
        </a>
      </li>
      <li>
        <a href="products.php" class="mobile-nav-link <?php echo $currentPage === 'products.php' ? 'active' : ''; ?>">
          <i class="bi bi-grid"></i>
          <span>Shop All Products</span>
        </a>
      </li>
      <li>
        <a href="about.php" class="mobile-nav-link <?php echo $currentPage === 'about.php' ? 'active' : ''; ?>">
          <i class="bi bi-info-circle"></i>
          <span>About Gloy</span>
        </a>
      </li>
      <li>
        <a href="contact.php" class="mobile-nav-link <?php echo $currentPage === 'contact.php' ? 'active' : ''; ?>">
          <i class="bi bi-chat-dots"></i>
          <span>Contact Us</span>
        </a>
      </li>
      <li>
        <a href="account.php" class="mobile-nav-link">
          <i class="bi bi-truck"></i>
          <span>Track Order &amp; Account</span>
        </a>
      </li>
    </ul>

    <!-- Mobile Quiz Action Button -->
    <div class="mobile-nav-actions-box">
      <button class="mobile-quiz-btn js-skin-quiz-trigger js-close-mobile-nav">
        <i class="bi bi-magic"></i>
        <span>Find Your Skin Routine</span>
      </button>
    </div>

    <!-- Mobile User & Support Footer -->
    <div class="mobile-nav-footer">
      <?php if ($currentUser): ?>
        <div class="mobile-user-card">
          <div class="mobile-user-avatar">
            <i class="bi bi-person-fill"></i>
          </div>
          <div class="mobile-user-info">
            <strong><?php echo e($currentUser['name']); ?></strong>
            <span><?php echo e($currentUser['email']); ?></span>
          </div>
        </div>
        <div class="mobile-user-links">
          <a href="account.php" class="mobile-footer-link"><i class="bi bi-box-seam me-1"></i> Orders</a>
          <?php if ($currentUser['role'] === 'admin'): ?>
            <a href="admin/dashboard.php" class="mobile-footer-link admin-link"><i class="bi bi-speedometer2 me-1"></i> Admin</a>
          <?php endif; ?>
          <a href="logout.php" class="mobile-footer-link logout-link"><i class="bi bi-box-arrow-right me-1"></i> Logout</a>
        </div>
      <?php else: ?>
        <div class="mobile-auth-buttons">
          <a href="login.php" class="btn btn-primary btn-sm" style="flex:1; text-align:center; padding:10px;">Login</a>
          <a href="register.php" class="btn btn-secondary btn-sm" style="flex:1; text-align:center; padding:10px;">Register</a>
        </div>
      <?php endif; ?>

      <a href="https://wa.me/923168882788" target="_blank" rel="noopener" class="mobile-whatsapp-card">
        <i class="bi bi-whatsapp"></i>
        <div>
          <small>WhatsApp Skincare Support</small>
          <strong>0316 8882788</strong>
        </div>
      </a>
    </div>
  </aside>
<?php endif; ?>

<!-- Slide-Out Mini Cart Drawer -->
<div class="cart-drawer-overlay" id="cart-drawer-overlay"></div>
<div class="cart-drawer" id="cart-drawer">
  <div class="cart-drawer-header">
    <h3>Your Shopping Cart</h3>
    <button class="cart-drawer-close js-close-cart-drawer" aria-label="Close Cart">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>
  <div class="cart-drawer-body" id="cart-drawer-body">
    <div class="cart-drawer-loading">
      <i class="bi bi-hourglass-split"></i>
      <span>Loading your cart...</span>
    </div>
  </div>
  <div class="cart-drawer-footer" id="cart-drawer-footer">
    <div class="cart-drawer-totals">
      <div class="cart-drawer-total-row">
        <span>Subtotal</span>
        <strong id="cart-drawer-subtotal">Rs. 0</strong>
      </div>
    </div>
    <a href="cart.php" class="btn btn-primary" style="width: 100%;">View Full Cart</a>
    <a href="checkout.php" class="btn btn-accent" style="width: 100%; margin-top: 12px;">Proceed to Checkout</a>
  </div>
</div>

<!-- Floating WhatsApp Customer Support Widget -->
<div class="whatsapp-float-widget" id="whatsapp-widget">
  <a href="https://wa.me/923168882788?text=<?php echo urlencode('Hi Gloy, I have a question about my skin type. Can you help me?'); ?>" target="_blank" class="whatsapp-float-button">
    <i class="bi bi-whatsapp"></i>
  </a>
  <div class="whatsapp-float-tooltip">
    <div class="whatsapp-tooltip-content">
      <strong>Have questions about skin type?</strong>
      <p>Chat with Gloy skincare expert</p>
    </div>
  </div>
</div>

<!-- Interactive Skin Routine Quiz Modal -->
<div class="skin-quiz-overlay" id="skin-quiz-overlay"></div>
<div class="skin-quiz-modal" id="skin-quiz-modal">
  <button class="skin-quiz-close" id="skin-quiz-close" aria-label="Close Quiz">
    <i class="bi bi-x-lg"></i>
  </button>

  <div class="skin-quiz-content">
    <!-- Quiz Steps Container -->
    <div class="skin-quiz-step" id="quiz-step-1" data-step="1">
      <div class="quiz-header">
        <span class="quiz-step-indicator">Step 1 of 3</span>
        <h2 class="quiz-title">What's your skin type?</h2>
        <p class="quiz-subtitle">Help us understand your skin for personalized recommendations</p>
      </div>
      <div class="quiz-options">
        <button class="quiz-option" data-value="oily">
          <div class="quiz-option-icon"><i class="bi bi-droplet-fill"></i></div>
          <div class="quiz-option-text">
            <strong>Oily</strong>
            <span>Shiny, prone to breakouts</span>
          </div>
        </button>
        <button class="quiz-option" data-value="combination">
          <div class="quiz-option-icon"><i class="bi bi-layout-split"></i></div>
          <div class="quiz-option-text">
            <strong>Combination</strong>
            <span>Oily T-zone, dry cheeks</span>
          </div>
        </button>
        <button class="quiz-option" data-value="dry">
          <div class="quiz-option-icon"><i class="bi bi-water"></i></div>
          <div class="quiz-option-text">
            <strong>Dry</strong>
            <span>Tight, flaky, needs hydration</span>
          </div>
        </button>
        <button class="quiz-option" data-value="normal">
          <div class="quiz-option-icon"><i class="bi bi-check-circle-fill"></i></div>
          <div class="quiz-option-text">
            <strong>Normal</strong>
            <span>Balanced, no major issues</span>
          </div>
        </button>
      </div>
    </div>

    <div class="skin-quiz-step" id="quiz-step-2" data-step="2" style="display: none;">
      <div class="quiz-header">
        <span class="quiz-step-indicator">Step 2 of 3</span>
        <h2 class="quiz-title">What's your main skin concern?</h2>
        <p class="quiz-subtitle">Choose the one that bothers you most</p>
      </div>
      <div class="quiz-options">
        <button class="quiz-option" data-value="dark_spots">
          <div class="quiz-option-icon"><i class="bi bi-star-fill"></i></div>
          <div class="quiz-option-text">
            <strong>Dark Spots & Discoloration</strong>
            <span>Uneven skin tone, pigmentation</span>
          </div>
        </button>
        <button class="quiz-option" data-value="dryness">
          <div class="quiz-option-icon"><i class="bi bi-moisture"></i></div>
          <div class="quiz-option-text">
            <strong>Dryness & Dehydration</strong>
            <span>Tight, flaky, dull skin</span>
          </div>
        </button>
        <button class="quiz-option" data-value="uneven_tone">
          <div class="quiz-option-icon"><i class="bi bi-palette-fill"></i></div>
          <div class="quiz-option-text">
            <strong>Uneven Texture & Tone</strong>
            <span>Rough patches, dull complexion</span>
          </div>
        </button>
        <button class="quiz-option" data-value="acne">
          <div class="quiz-option-icon"><i class="bi bi-emoji-frown-fill"></i></div>
          <div class="quiz-option-text">
            <strong>Acne & Breakouts</strong>
            <span>Frequent pimples, congestion</span>
          </div>
        </button>
      </div>
    </div>

    <div class="skin-quiz-step" id="quiz-step-3" data-step="3" style="display: none;">
      <div class="quiz-header">
        <span class="quiz-step-indicator">Step 3 of 3</span>
        <h2 class="quiz-title">What's your skincare experience?</h2>
        <p class="quiz-subtitle">This helps us recommend the right routine complexity</p>
      </div>
      <div class="quiz-options">
        <button class="quiz-option" data-value="beginner">
          <div class="quiz-option-icon"><i class="bi bi-seedling-fill"></i></div>
          <div class="quiz-option-text">
            <strong>Beginner</strong>
            <span>Just starting my skincare journey</span>
          </div>
        </button>
        <button class="quiz-option" data-value="intermediate">
          <div class="quiz-option-icon"><i class="bi bi-flower1"></i></div>
          <div class="quiz-option-text">
            <strong>Intermediate</strong>
            <span>I have a basic routine</span>
          </div>
        </button>
        <button class="quiz-option" data-value="advanced">
          <div class="quiz-option-icon"><i class="bi bi-gem"></i></div>
          <div class="quiz-option-text">
            <strong>Advanced</strong>
            <span>Skincare enthusiast, multi-step routine</span>
          </div>
        </button>
      </div>
    </div>

    <!-- Quiz Results -->
    <div class="skin-quiz-results" id="quiz-results" style="display: none;">
      <div class="quiz-header">
        <span class="quiz-step-indicator">Your Personalized Routine</span>
        <h2 class="quiz-title">Find Your Glow Ritual ✨</h2>
        <p class="quiz-subtitle">Based on your skin type and concerns</p>
      </div>
      <div class="quiz-recommendations" id="quiz-recommendations">
        <!-- Dynamic content will be inserted here -->
      </div>
      <div class="quiz-cta">
        <button class="btn btn-primary js-add-entire-routine" id="add-entire-routine">
          <i class="bi bi-cart-plus me-1"></i> Add Entire Duo to Cart
        </button>
        <button class="btn btn-secondary js-retake-quiz" id="retake-quiz">
          <i class="bi bi-arrow-clockwise me-1"></i> Retake Quiz
        </button>
      </div>
    </div>
  </div>
</div>
