<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth_middleware.php';

$pdo = getDBConnection();

// Read Filter Parameters
$category  = isset($_GET['category']) ? trim($_GET['category']) : '';
$skinType  = isset($_GET['skin_type']) ? trim($_GET['skin_type']) : '';
$maxPrice  = isset($_GET['max_price']) ? (float)$_GET['max_price'] : 5000;
$minRating = isset($_GET['min_rating']) ? (float)$_GET['min_rating'] : 0;
$sort      = isset($_GET['sort']) ? trim($_GET['sort']) : 'newest';
$page      = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit     = 6;
$offset    = ($page - 1) * $limit;
$search    = isset($_GET['search']) ? trim($_GET['search']) : (isset($_GET['q']) ? trim($_GET['q']) : '');

// Build WHERE Clause dynamically with PDO Prepared Statements
$whereClauses = ["price <= :max_price"];
$params = [':max_price' => $maxPrice];

if (!empty($category)) {
    $whereClauses[] = "category = :category";
    $params[':category'] = $category;
}

if (!empty($skinType)) {
    $whereClauses[] = "(skin_type = :skin_type OR skin_type = 'All')";
    $params[':skin_type'] = $skinType;
}

if ($minRating > 0) {
    $whereClauses[] = "rating_avg >= :min_rating";
    $params[':min_rating'] = $minRating;
}

if (!empty($search)) {
    $whereClauses[] = "(name LIKE :search OR description LIKE :search OR category LIKE :search OR ingredients LIKE :search OR subtitle LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

$whereSQL = "WHERE " . implode(" AND ", $whereClauses);

// Build Order By Clause
$orderBy = "created_at DESC";
switch ($sort) {
    case 'price_low':
        $orderBy = "price ASC";
        break;
    case 'price_high':
        $orderBy = "price DESC";
        break;
    case 'rating':
        $orderBy = "rating_avg DESC";
        break;
    case 'newest':
    default:
        $orderBy = "created_at DESC";
        break;
}

// Total Count for Pagination
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products $whereSQL");
foreach ($params as $key => $val) {
    $countStmt->bindValue($key, $val);
}
$countStmt->execute();
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $limit);

// Fetch Products with LIMIT & OFFSET
$sql = "SELECT * FROM products $whereSQL ORDER BY $orderBy LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll();

// Fetch all distinct categories dynamically from database + default categories
$defaultCategories = ['Cleansers', 'Serums', 'Moisturizers', 'Sunscreen', 'Masks', 'Bundles'];
$dbCatStmt = $pdo->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != ''");
$dbCategories = $dbCatStmt ? $dbCatStmt->fetchAll(PDO::FETCH_COLUMN) : [];
$allCategories = array_values(array_unique(array_merge($defaultCategories, $dbCategories)));
sort($allCategories);

// Helper function to render star icons
function renderStars($rating) {
    $rInt = (int)round($rating);
    $html = '<div style="display:inline-flex; gap:2px; color:#F59E0B; font-size:0.85rem;">';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rInt) {
            $html .= '<i class="bi bi-star-fill"></i>';
        } else {
            $html .= '<i class="bi bi-star" style="color:#CBD5E1;"></i>';
        }
    }
    $html .= '</div>';
    return $html;
}

// If AJAX Partial Request, output HTML cards only
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    if (empty($products)) {
        echo '<div style="grid-column: 1/-1; text-align:center; padding:60px 0; color:var(--text-muted);">';
        echo '<i class="bi bi-search" style="font-size:3rem; color:var(--border-color); display:block; margin-bottom:12px;"></i>';
        echo '<h3 style="color:var(--text-heading); font-size:1.4rem; margin-bottom:8px;">No products match your search or filter.</h3>';
        echo '<p style="color:var(--text-muted);">Try adjusting your keywords or clearing selected filters.</p>';
        echo '</div>';
        exit;
    }

    foreach ($products as $p) {
        ?>
        <div class="product-card">
          <?php if ($p['is_bestseller']): ?>
            <span class="product-badge"><i class="bi bi-award-fill me-1"></i> Bestseller</span>
          <?php endif; ?>
          <div class="product-image-wrap">
            <img src="assets/images/<?php echo e($p['image']); ?>" alt="<?php echo e($p['name']); ?>" loading="lazy">
          </div>
          <div class="product-details">
            <span class="product-category"><?php echo e($p['category']); ?> • <?php echo e($p['skin_type']); ?> Skin</span>
            <h3 class="product-title"><?php echo e($p['name']); ?></h3>
            <div class="product-rating">
              <?php echo renderStars($p['rating_avg']); ?>
              <span class="rating-count">(<?php echo $p['rating_avg']; ?>)</span>
            </div>
            <div class="product-price-row">
              <span class="product-price"><?php echo formatPrice($p['price']); ?></span>
            </div>
            <div class="card-actions">
              <button class="btn btn-secondary btn-sm js-view-details" data-id="<?php echo $p['id']; ?>"><i class="bi bi-eye me-1"></i> Details</button>
              <button class="btn btn-secondary btn-sm js-add-to-cart" data-id="<?php echo $p['id']; ?>"><i class="bi bi-cart-plus me-1"></i> Add</button>
              <button class="btn btn-primary btn-sm js-buy-now" data-id="<?php echo $p['id']; ?>" style="font-weight:700;"><i class="bi bi-lightning-charge-fill me-1"></i> Buy Now</button>
            </div>
          </div>
        </div>
        <?php
    }
    exit;
}

$pageTitle = !empty($search) ? 'Search: ' . htmlspecialchars($search) . ' | Gloy Skincare' : 'Shop Gloy Skincare Formulas';
require_once __DIR__ . '/includes/header.php';

// Schema.org JSON-LD for Products Page
$productsSchema = [
  "@context" => "https://schema.org",
  "@type" => "ItemList",
  "itemListElement" => []
];

foreach ($products as $index => $p) {
  $productsSchema["itemListElement"][] = [
    "@type" => "ListItem",
    "position" => $index + 1,
    "item" => [
      "@type" => "Product",
      "name" => $p['name'],
      "description" => $p['description'],
      "image" => "https://gloyskincare.com/assets/images/" . $p['image'],
      "url" => "https://gloyskincare.com/products.php",
      "brand" => [
        "@type" => "Brand",
        "name" => "Gloy Skincare"
      ],
      "offers" => [
        "@type" => "Offer",
        "price" => $p['price'],
        "priceCurrency" => "PKR",
        "availability" => $p['stock'] > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock",
        "url" => "https://gloyskincare.com/products.php"
      ],
      "aggregateRating" => [
        "@type" => "AggregateRating",
        "ratingValue" => $p['rating_avg'],
        "reviewCount" => $p['review_count'] ?? 10,
        "bestRating" => "5",
        "worstRating" => "1"
      ]
    ]
  ];
}
?>

<!-- Schema.org JSON-LD for Products -->
<script type="application/ld+json">
<?php echo json_encode($productsSchema, JSON_PRETTY_PRINT); ?>
</script>

<div class="container section">
  <div style="margin-bottom: 36px;">
    <h1 style="font-size:2.8rem; margin-bottom:8px; color:var(--text-heading);">
      <?php if (!empty($search)): ?>
        Search Results for "<span style="color:var(--primary-purple);"><?php echo e($search); ?></span>"
      <?php else: ?>
        All Skincare Products
      <?php endif; ?>
    </h1>
    <p style="color:var(--text-body);">Explore clean, high-performance skincare formulas crafted for every skin concern.</p>

    <?php if (!empty($search)): ?>
      <div style="margin-top:16px; display:inline-flex; align-items:center; gap:12px; background:var(--bg-surface); border:1px solid var(--border-color); padding:8px 16px; border-radius:30px;">
        <span style="font-size:0.85rem; color:var(--text-muted);"><i class="bi bi-search text-primary me-1"></i> Filtered by keyword: <strong><?php echo e($search); ?></strong> (<?php echo $totalProducts; ?> found)</span>
        <a href="products.php" style="color:var(--accent-terracotta); font-size:0.8rem; font-weight:600; text-decoration:none; display:flex; align-items:center; gap:4px;">
          <i class="bi bi-x-circle-fill"></i> Clear Search
        </a>
      </div>
    <?php endif; ?>
  </div>

  <div class="shop-layout">
    <!-- Filter Sidebar -->
    <aside class="filter-sidebar">
      <form id="product-filter-form" action="products.php" method="GET">
        <div class="filter-group">
          <h4 class="filter-title"><i class="bi bi-search me-1"></i> Search Products</h4>
          <input type="text" name="search" class="search-input-sidebar" placeholder="Name, ingredient, category..." value="<?php echo e($search); ?>">
        </div>

        <div class="filter-group">
          <h4 class="filter-title"><i class="bi bi-grid me-1"></i> Category</h4>
          <ul class="filter-list">
            <li>
              <label class="checkbox-label">
                <input type="radio" name="category" value="" <?php echo empty($category) ? 'checked' : ''; ?>>
                <span>All Categories</span>
              </label>
            </li>
            <?php foreach ($allCategories as $cat): ?>
              <li>
                <label class="checkbox-label">
                  <input type="radio" name="category" value="<?php echo e($cat); ?>" <?php echo $category === $cat ? 'checked' : ''; ?>>
                  <span><?php echo e($cat); ?></span>
                </label>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="filter-group">
          <h4 class="filter-title"><i class="bi bi-droplet me-1"></i> Skin Type</h4>
          <ul class="filter-list">
            <li>
              <label class="checkbox-label">
                <input type="radio" name="skin_type" value="" <?php echo empty($skinType) ? 'checked' : ''; ?>>
                <span>All Skin Types</span>
              </label>
            </li>
            <?php foreach (['Sensitive', 'Dry', 'Oily', 'Combination'] as $st): ?>
              <li>
                <label class="checkbox-label">
                  <input type="radio" name="skin_type" value="<?php echo $st; ?>" <?php echo $skinType === $st ? 'checked' : ''; ?>>
                  <span><?php echo $st; ?> Skin</span>
                </label>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>

        <div class="filter-group">
          <h4 class="filter-title"><i class="bi bi-cash me-1"></i> Max Price (Rs.)</h4>
          <input type="range" name="max_price" min="1500" max="5000" step="50" value="<?php echo $maxPrice; ?>" class="input-field" oninput="this.nextElementSibling.value = 'Rs. ' + parseInt(this.value).toLocaleString()">
          <output style="font-weight:600; color:var(--primary-purple); display:block; margin-top:6px;">Rs. <?php echo number_format($maxPrice); ?></output>
        </div>

        <div class="filter-group">
          <h4 class="filter-title"><i class="bi bi-star me-1"></i> Minimum Rating</h4>
          <select name="min_rating" class="input-field">
            <option value="0" <?php echo $minRating == 0 ? 'selected' : ''; ?>>All Ratings</option>
            <option value="4.5" <?php echo $minRating == 4.5 ? 'selected' : ''; ?>>4.5+ Stars</option>
            <option value="4.0" <?php echo $minRating == 4.0 ? 'selected' : ''; ?>>4.0+ Stars</option>
          </select>
        </div>

        <?php if (!empty($search) || !empty($category) || !empty($skinType) || $minRating > 0): ?>
          <div style="margin-top:16px;">
            <a href="products.php" class="btn btn-secondary btn-sm" style="width:100%; text-align:center; display:block;">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Reset All Filters
            </a>
          </div>
        <?php endif; ?>
      </form>
    </aside>

    <!-- Main Grid Content Area -->
    <main>
      <!-- Sort Bar -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; padding-bottom:16px; border-bottom:1px solid var(--border-color);">
        <span style="color:var(--text-muted); font-size:0.9rem;">
          Showing <strong><?php echo count($products); ?></strong> of <strong><?php echo $totalProducts; ?></strong> items
        </span>

        <div style="display:flex; align-items:center; gap:10px;">
          <label style="font-size:0.85rem; font-weight:600; color:var(--text-heading);">Sort By:</label>
          <select id="sort-by-select" class="input-field" style="width:auto; padding:8px 16px;">
            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest Arrivals</option>
            <option value="price_low" <?php echo $sort === 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
            <option value="price_high" <?php echo $sort === 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
            <option value="rating" <?php echo $sort === 'rating' ? 'selected' : ''; ?>>Top Rated</option>
          </select>
        </div>
      </div>

      <!-- Products Grid -->
      <div class="product-grid" id="shop-product-grid">
        <?php if (empty($products)): ?>
          <div style="grid-column: 1/-1; text-align:center; padding:60px 0; color:var(--text-muted);">
            <i class="bi bi-search" style="font-size:3rem; color:var(--border-color); display:block; margin-bottom:12px;"></i>
            <h3 style="color:var(--text-heading); font-size:1.4rem; margin-bottom:8px;">No products match your search.</h3>
            <p style="color:var(--text-muted); margin-bottom:20px;">Try searching for "moisturizer", "serum", or "glutathione".</p>
            <a href="products.php" class="btn btn-primary btn-sm">View All Products</a>
          </div>
        <?php else: ?>
          <?php foreach ($products as $p): ?>
            <div class="product-card">
              <?php if ($p['is_bestseller']): ?>
                <span class="product-badge"><i class="bi bi-award-fill me-1"></i> Bestseller</span>
              <?php endif; ?>
              <div class="product-image-wrap">
                <img src="assets/images/<?php echo e($p['image']); ?>" alt="<?php echo e($p['name']); ?>" loading="lazy">
              </div>
              <div class="product-details">
                <span class="product-category"><?php echo e($p['category']); ?> • <?php echo e($p['skin_type']); ?> Skin</span>
                <h3 class="product-title"><?php echo e($p['name']); ?></h3>
                <div class="product-rating">
                  <?php echo renderStars($p['rating_avg']); ?>
                  <span class="rating-count">(<?php echo $p['rating_avg']; ?>)</span>
                </div>
                <div class="product-price-row">
                  <span class="product-price"><?php echo formatPrice($p['price']); ?></span>
                </div>
                <div class="card-actions">
                  <button class="btn btn-secondary btn-sm js-view-details" data-id="<?php echo $p['id']; ?>"><i class="bi bi-eye me-1"></i> Details</button>
                  <button class="btn btn-secondary btn-sm js-add-to-cart" data-id="<?php echo $p['id']; ?>"><i class="bi bi-cart-plus me-1"></i> Add</button>
                  <button class="btn btn-primary btn-sm js-buy-now" data-id="<?php echo $p['id']; ?>" style="font-weight:700;"><i class="bi bi-lightning-charge-fill me-1"></i> Buy Now</button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <div style="display:flex; justify-content:center; gap:8px; margin-top:48px;">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="products.php?page=<?php echo $i; ?>&category=<?php echo urlencode($category); ?>&skin_type=<?php echo urlencode($skinType); ?>&sort=<?php echo urlencode($sort); ?>&search=<?php echo urlencode($search); ?>" 
               class="btn btn-sm <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>" 
               style="width:40px; height:40px; padding:0; display:inline-flex; align-items:center; justify-content:center;">
              <?php echo $i; ?>
            </a>
          <?php endfor; ?>
        </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<!-- INLINE DETAIL VIEW MODAL CONTAINER -->
<div class="modal-overlay" id="product-detail-modal">
  <div class="modal-container"></div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
