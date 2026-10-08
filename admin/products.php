<?php
$adminTitle = 'Product Catalog Management';
require_once __DIR__ . '/includes/admin_header.php';

$pdo = getDBConnection();
$msg = '';
$error = '';

// Handle Delete Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
    csrf_require();
    $deleteId = (int)$_POST['delete'];
    $delStmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $delStmt->execute([$deleteId]);
    $msg = 'Product deleted successfully.';
}

// Handle Add / Edit Product Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_product'])) {
    csrf_require();
    $id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $name        = trim($_POST['name'] ?? '');
    $category    = trim($_POST['category'] ?? 'Serums');
    $skin_type   = trim($_POST['skin_type'] ?? 'All');
    $price       = (float)($_POST['price'] ?? 0);
    $stock       = (int)($_POST['stock'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $ingredients = trim($_POST['ingredients'] ?? '');
    $how_to_use  = trim($_POST['how_to_use'] ?? '');
    $existingImg = trim($_POST['existing_image'] ?? '');
    $bestseller  = isset($_POST['is_bestseller']) ? 1 : 0;

    $image = $existingImg;

    // Local PC Image File Upload Handler
    if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['product_image'];
        if ($file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            $error = 'Failed to upload product image. Please try again.';
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = 'Image file size exceeds 5MB limit.';
        } else {
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'webp', 'svg'];

            if (!in_array($ext, $allowedExts)) {
                $error = 'Invalid image format. Allowed file types: JPG, PNG, WEBP, SVG.';
            } else {
                $uploadDir = __DIR__ . '/../assets/images/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $newFilename = 'prod_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $targetPath = $uploadDir . $newFilename;

                if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                    @chmod($targetPath, 0644);
                    $image = $newFilename;
                } else {
                    $error = 'Could not save uploaded image to assets/images directory.';
                }
            }
        }
    }

    if (empty($error)) {
        if (empty($name) || $price <= 0) {
            $error = 'Product name and valid price are required.';
        } elseif (empty($image)) {
            $error = 'Please select a product image to upload from your computer.';
        } else {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $name)));

            if ($id > 0) {
                // Update Existing Product
                $upd = $pdo->prepare("
                    UPDATE products 
                    SET name = ?, slug = ?, category = ?, skin_type = ?, price = ?, stock = ?, description = ?, ingredients = ?, how_to_use = ?, image = ?, is_bestseller = ?
                    WHERE id = ?
                ");
                $upd->execute([$name, $slug, $category, $skin_type, $price, $stock, $description, $ingredients, $how_to_use, $image, $bestseller, $id]);
                $msg = 'Product updated successfully.';
            } else {
                // Insert New Product
                $ins = $pdo->prepare("
                    INSERT INTO products (name, slug, category, skin_type, price, stock, description, ingredients, how_to_use, image, is_bestseller)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $ins->execute([$name, $slug, $category, $skin_type, $price, $stock, $description, $ingredients, $how_to_use, $image, $bestseller]);
                $msg = 'New product created successfully.';
            }
        }
    }
}

// Fetch All Products
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$sql = "SELECT * FROM products";
if (!empty($search)) {
    $sql .= " WHERE name LIKE " . $pdo->quote("%$search%") . " OR category LIKE " . $pdo->quote("%$search%");
}
$sql .= " ORDER BY created_at DESC";
$products = $pdo->query($sql)->fetchAll();
?>

<div class="admin-topbar">
  <div>
    <h1 class="admin-title">Products Inventory</h1>
    <p style="color:var(--text-muted); font-size:0.9rem;">Manage product listings, pricing, stock levels, and formulas.</p>
  </div>
  <button class="btn btn-primary btn-sm" onclick="openProductForm()">+ Add New Product</button>
</div>

<?php if ($msg): ?>
  <div style="background:var(--bg-accent-sage); color:var(--primary-sage); padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px;">
    <?php echo e($msg); ?>
  </div>
<?php endif; ?>

<?php if ($error): ?>
  <div style="background:var(--accent-blush); color:var(--accent-terracotta); padding:14px 20px; border-radius:var(--radius-md); margin-bottom:24px;">
    <?php echo e($error); ?>
  </div>
<?php endif; ?>

<div class="admin-card">
  <div class="card-header-flex">
    <h3 style="font-size:1.2rem;">All Products (<?php echo count($products); ?>)</h3>
    <form method="GET" action="products.php" style="display:flex; gap:10px;">
      <input type="text" name="q" class="input-field" placeholder="Search product name..." value="<?php echo e($search); ?>" style="padding:6px 14px; font-size:0.85rem;">
      <button type="submit" class="btn btn-secondary btn-sm">Search</button>
    </form>
  </div>

  <div class="table-responsive">
    <table class="data-table">
      <thead>
        <tr>
          <th style="width:45px;">Image</th>
          <th>Product Name</th>
          <th>Category</th>
          <th>Skin Type</th>
          <th>Price</th>
          <th>Stock</th>
          <th>Rating</th>
          <th style="text-align:right;">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($products as $p): ?>
          <tr>
            <td>
              <img src="../assets/images/<?php echo e($p['image']); ?>" alt="" style="width:34px; height:34px; object-fit:contain; background:var(--bg-main); border-radius:var(--radius-sm); padding:2px; border:1px solid var(--border-color);">
            </td>
            <td>
              <strong><?php echo e($p['name']); ?></strong>
              <?php if ($p['is_bestseller']): ?>
                <span class="status-badge status-paid" style="font-size:0.62rem; margin-left:4px;">Bestseller</span>
              <?php endif; ?>
            </td>
            <td><span style="font-size:0.82rem; color:var(--text-muted);"><?php echo e($p['category']); ?></span></td>
            <td><span style="font-size:0.82rem;"><?php echo e($p['skin_type']); ?></span></td>
            <td><strong style="color:var(--primary-purple); font-family:var(--font-serif);"><?php echo formatPrice($p['price']); ?></strong></td>
            <td>
              <span style="font-weight:700; color:<?php echo $p['stock'] < 30 ? 'var(--accent-terracotta)' : 'var(--primary-purple)'; ?>;">
                <?php echo $p['stock']; ?>
              </span>
            </td>
            <td><span style="font-size:0.82rem;"><i class="bi bi-star-fill" style="color:#F59E0B;"></i> <?php echo $p['rating_avg']; ?> <small style="color:var(--text-muted);">(<?php echo $p['review_count']; ?>)</small></span></td>
            <td style="text-align:right;">
              <div style="display:inline-flex; gap:6px;">
                <button class="btn-table-action btn-enable" style="padding:4px 10px; font-size:0.75rem;" onclick='editProduct(<?php echo json_encode($p, JSON_HEX_APOS); ?>)'>
                  <i class="bi bi-pencil"></i> Edit
                </button>
                <a href="#" data-post-name="delete" data-post-value="<?php echo (int)$p['id']; ?>" class="btn-table-action btn-disable" style="padding:4px 8px; font-size:0.75rem;" data-confirm="Delete this product permanently?" title="Delete Product">
                  <i class="bi bi-trash"></i>
                </a>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Product Add / Edit Modal Overlay -->
<div class="modal-overlay" id="admin-product-modal">
  <div class="modal-container" style="max-width:700px;">
    <button class="modal-close-btn" onclick="closeProductForm()">&times;</button>
    <h3 id="form-modal-title" style="font-size:1.6rem; margin-bottom:20px;">Add New Product</h3>

    <form method="POST" action="products.php" enctype="multipart/form-data">
      <?php echo csrf_field(); ?>
      <input type="hidden" name="save_product" value="1">
      <input type="hidden" name="id" id="prod-id" value="0">
      <input type="hidden" name="existing_image" id="prod-existing-image" value="">

      <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:16px;">
        <div>
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Product Name *</label>
          <input type="text" name="name" id="prod-name" class="input-field" required>
        </div>

        <div>
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Category *</label>
          <select name="category" id="prod-category" class="input-field">
            <option value="Cleansers">Cleansers</option>
            <option value="Serums">Serums</option>
            <option value="Moisturizers">Moisturizers</option>
            <option value="Sunscreen">Sunscreen</option>
            <option value="Masks">Masks</option>
            <option value="Bundles">Bundles</option>
          </select>
        </div>
      </div>

      <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px; margin-bottom:16px;">
        <div>
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Skin Type</label>
          <select name="skin_type" id="prod-skin-type" class="input-field">
            <option value="All">All Skin Types</option>
            <option value="Sensitive">Sensitive</option>
            <option value="Dry">Dry</option>
            <option value="Oily">Oily</option>
            <option value="Combination">Combination</option>
          </select>
        </div>

        <div>
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Price (Rs.) *</label>
          <input type="number" step="0.01" name="price" id="prod-price" class="input-field" required>
        </div>

        <div>
          <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Stock Qty *</label>
          <input type="number" name="stock" id="prod-stock" class="input-field" required>
        </div>
      </div>

      <!-- Local Image File Upload Input -->
      <div style="margin-bottom:16px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">
          Product Image (Upload from Computer) *
        </label>
        <input type="file" name="product_image" id="prod-image-file" class="input-field" accept="image/jpeg,image/png,image/webp,image/svg+xml" onchange="previewProductImage(this)">
        <small style="color:var(--text-muted); font-size:0.75rem; display:block; margin-top:4px;">Supported Formats: JPG, PNG, WEBP, SVG (Max 5MB)</small>

        <!-- Dynamic Live Image Preview -->
        <div id="image-preview-wrap" style="margin-top:10px; display:none; align-items:center; gap:12px; background:var(--bg-main); padding:10px; border-radius:var(--radius-sm); border:1px solid var(--border-color);">
          <img id="image-preview-img" src="" alt="Preview" style="width:50px; height:50px; object-fit:contain; background:#fff; border-radius:4px; padding:2px; border:1px solid var(--border-color);">
          <div>
            <strong id="image-preview-name" style="font-size:0.82rem; color:var(--text-heading); display:block;"></strong>
            <span style="font-size:0.75rem; color:#10B981;"><i class="bi bi-check-circle-fill"></i> Image Selected</span>
          </div>
        </div>
      </div>

      <div style="margin-bottom:16px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Description</label>
        <textarea name="description" id="prod-desc" class="input-field" rows="3"></textarea>
      </div>

      <div style="margin-bottom:16px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">Ingredients</label>
        <textarea name="ingredients" id="prod-ingredients" class="input-field" rows="2"></textarea>
      </div>

      <div style="margin-bottom:20px;">
        <label style="font-size:0.85rem; font-weight:600; display:block; margin-bottom:6px;">How to Use</label>
        <textarea name="how_to_use" id="prod-howtouse" class="input-field" rows="2"></textarea>
      </div>

      <div style="margin-bottom:24px;">
        <label class="checkbox-label">
          <input type="checkbox" name="is_bestseller" id="prod-bestseller" value="1">
          <span>Mark as Cult Bestseller</span>
        </label>
      </div>

      <button type="submit" class="btn btn-primary" style="width:100%;">Save Product</button>
    </form>
  </div>
</div>

<script>
  function openProductForm() {
    document.getElementById('form-modal-title').textContent = 'Add New Product';
    document.getElementById('prod-id').value = '0';
    document.getElementById('prod-existing-image').value = '';
    document.getElementById('prod-image-file').value = '';
    document.getElementById('prod-name').value = '';
    document.getElementById('prod-price').value = '';
    document.getElementById('prod-stock').value = '50';
    document.getElementById('prod-desc').value = '';
    document.getElementById('prod-ingredients').value = '';
    document.getElementById('prod-howtouse').value = '';
    document.getElementById('prod-bestseller').checked = false;
    document.getElementById('image-preview-wrap').style.display = 'none';
    document.getElementById('admin-product-modal').classList.add('is-active');
  }

  function closeProductForm() {
    document.getElementById('admin-product-modal').classList.remove('is-active');
  }

  function editProduct(p) {
    document.getElementById('form-modal-title').textContent = 'Edit Product #' + p.id;
    document.getElementById('prod-id').value = p.id;
    document.getElementById('prod-name').value = p.name;
    document.getElementById('prod-category').value = p.category;
    document.getElementById('prod-skin-type').value = p.skin_type;
    document.getElementById('prod-price').value = p.price;
    document.getElementById('prod-stock').value = p.stock;
    document.getElementById('prod-existing-image').value = p.image || '';
    document.getElementById('prod-image-file').value = '';
    document.getElementById('prod-desc').value = p.description || '';
    document.getElementById('prod-ingredients').value = p.ingredients || '';
    document.getElementById('prod-howtouse').value = p.how_to_use || '';
    document.getElementById('prod-bestseller').checked = p.is_bestseller == 1;

    const wrap = document.getElementById('image-preview-wrap');
    const img = document.getElementById('image-preview-img');
    const label = document.getElementById('image-preview-name');
    if (p.image) {
      img.src = '../assets/images/' + p.image;
      label.textContent = 'Current: ' + p.image;
      wrap.style.display = 'flex';
    } else {
      wrap.style.display = 'none';
    }

    document.getElementById('admin-product-modal').classList.add('is-active');
  }

  function previewProductImage(input) {
    const wrap = document.getElementById('image-preview-wrap');
    const img = document.getElementById('image-preview-img');
    const label = document.getElementById('image-preview-name');
    if (input.files && input.files[0]) {
      const file = input.files[0];
      const reader = new FileReader();
      reader.onload = function(e) {
        img.src = e.target.result;
        label.textContent = 'Selected: ' + file.name;
        wrap.style.display = 'flex';
      };
      reader.readAsDataURL(file);
    }
  }
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>
