<?php $current = basename($_SERVER['PHP_SELF']); ?>

<div class="admin-sidebar">
    <div class="brand">Local<span>Loop</span> <small style="font-size:11px;opacity:.5;font-weight:400;">Admin</small></div>

    <div class="nav-label">Overview</div>
    <a href="/admin/index.php" class="nav-item <?= $current=='index.php' ? 'active':'' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
    </a>

    <div class="nav-label">Manage</div>
    <a href="/admin/users.php" class="nav-item <?= $current=='users.php' ? 'active':'' ?>">
        <i class="bi bi-people"></i> Users
    </a>
    <a href="/admin/listings.php" class="nav-item <?= $current=='listings.php' ? 'active':'' ?>">
        <i class="bi bi-grid"></i> Listings
    </a>
    <a href="/admin/orders.php" class="nav-item <?= $current=='orders.php' ? 'active':'' ?>">
        <i class="bi bi-bag"></i> Orders
    </a>
    <a href="/admin/reviews.php" class="nav-item <?= $current=='reviews.php' ? 'active':'' ?>">
        <i class="bi bi-star"></i> Reviews
    </a>
    <a href="/admin/categories.php" class="nav-item <?= $current=='categories.php' ? 'active':'' ?>">
        <i class="bi bi-tags"></i> Categories
    </a>

    <div style="margin-top:auto;padding:16px 22px;border-top:1px solid rgba(255,255,255,.1);">
        <a href="/admin/logout.php" style="color:rgba(255,255,255,.55);font-size:13px;text-decoration:none;">
            <i class="bi bi-box-arrow-left me-2"></i>Logout
        </a>
    </div>
</div>

<!-- TOP BAR -->
<div class="admin-topbar">
    <div style="font-weight:600;font-size:15px;color:var(--dark);">
        <?= isset($pageTitle) ? htmlspecialchars($pageTitle) : 'Dashboard' ?>
    </div>
    <div class="d-flex align-items-center gap-3">
        <span style="font-size:13px;color:var(--muted);">
            <i class="bi bi-person-circle me-1"></i>
            <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
            <small class="ms-1 text-muted">(<?= htmlspecialchars($_SESSION['admin_role'] ?? '') ?>)</small>
        </span>
        <a href="/index.php" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-box-arrow-up-right me-1"></i>View Site
        </a>
    </div>
</div>

<div class="admin-content">