<?php
require_once __DIR__ . '/../../includes/security.php'; // session + security bootstrap
require_once __DIR__ . '/../../includes/auth_middleware.php';

// Strict Admin protection
require_admin('login.php');

$currentAdmin = get_current_user_data();
$currentAdminPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
  <title><?php echo isset($adminTitle) ? e($adminTitle) . ' | Gloy Admin' : 'Admin Dashboard | Gloy Skincare'; ?></title>
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  
  <!-- Bootstrap Icons CDN -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

  <!-- CSS -->
  <link rel="stylesheet" href="../assets/css/style.css">
  <link rel="stylesheet" href="../assets/css/components.css">
  <link rel="stylesheet" href="../assets/css/admin.css">

  <!-- Chart.js CDN for Admin Analytics -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-body">

  <!-- Admin Sidebar -->
  <aside class="admin-sidebar">
    <div class="admin-brand">
      <a href="../index.php" class="brand-logo" style="font-size:1.4rem;">
        GLOY<span style="color:var(--primary-purple);">.</span>
      </a>
      <span style="font-size:0.7rem; font-weight:700; text-transform:uppercase; background:rgba(99,102,241,0.12); color:var(--primary-purple); padding:2px 8px; border-radius:var(--radius-pill);">Admin</span>
    </div>

    <nav class="admin-nav">
      <a href="dashboard.php" class="admin-nav-item <?php echo $currentAdminPage === 'dashboard.php' ? 'active' : ''; ?>">
        <i class="bi bi-speedometer2 me-2"></i> Dashboard
      </a>
      <a href="products.php" class="admin-nav-item <?php echo $currentAdminPage === 'products.php' ? 'active' : ''; ?>">
        <i class="bi bi-box-seam me-2"></i> Products &amp; Inventory
      </a>
      <a href="orders.php" class="admin-nav-item <?php echo $currentAdminPage === 'orders.php' ? 'active' : ''; ?>">
        <i class="bi bi-bag-check me-2"></i> Orders &amp; Payments
      </a>
      <a href="customers.php" class="admin-nav-item <?php echo $currentAdminPage === 'customers.php' ? 'active' : ''; ?>">
        <i class="bi bi-people me-2"></i> Customers
      </a>
      <a href="reviews.php" class="admin-nav-item <?php echo $currentAdminPage === 'reviews.php' ? 'active' : ''; ?>">
        <i class="bi bi-star me-2"></i> Review Moderation
      </a>
      <a href="messages.php" class="admin-nav-item <?php echo $currentAdminPage === 'messages.php' ? 'active' : ''; ?>">
        <i class="bi bi-chat-left-text me-2"></i> Customer Messages
      </a>
    </nav>

    <div style="padding:20px 24px; border-top:1px solid var(--border-color);">
      <div style="font-size:0.85rem; font-weight:600; color:var(--text-heading); margin-bottom:4px;"><?php echo e($currentAdmin['name']); ?></div>
      <a href="../logout.php" style="font-size:0.8rem; color:var(--accent-terracotta);"><i class="bi bi-box-arrow-right me-1"></i> Log out of Admin</a>
    </div>
  </aside>

  <!-- Sidebar Mobile Backdrop -->
  <div class="admin-sidebar-backdrop" id="adminSidebarBackdrop" onclick="toggleAdminSidebar()"></div>

  <!-- Admin Main Content Wrapper -->
  <main class="admin-main">
    <!-- Mobile Header Bar -->
    <div class="admin-mobile-header">
      <button type="button" class="btn btn-secondary btn-sm" onclick="toggleAdminSidebar()" style="padding:6px 12px; font-size:1rem; display:inline-flex; align-items:center; gap:6px;">
        <i class="bi bi-list" style="font-size:1.2rem;"></i> <span>Menu</span>
      </button>
      <a href="dashboard.php" class="brand-logo" style="font-size:1.2rem;">
        GLOY<span style="color:var(--primary-purple);">.</span>
      </a>
      <a href="../logout.php" class="btn btn-secondary btn-sm" style="padding:6px 10px; color:var(--accent-terracotta);" title="Log Out">
        <i class="bi bi-box-arrow-right"></i>
      </a>
    </div>

    <script>
      function toggleAdminSidebar() {
        const sidebar = document.querySelector('.admin-sidebar');
        const backdrop = document.getElementById('adminSidebarBackdrop');
        if (sidebar) sidebar.classList.toggle('is-active');
        if (backdrop) backdrop.classList.toggle('is-active');
      }
    </script>
