<?php
$pageTitle = 'My Listings';
require_once 'includes/header.php';
requireLogin();
require_once 'includes/navbar.php';

$uid     = $_SESSION['user_id'];
$deleted = isset($_GET['deleted']);

$listings = $conn->query("
    SELECT l.*, c.name AS category_name
    FROM listings l
    JOIN categories c ON l.category_id = c.category_id
    WHERE l.seller_id = $uid AND l.status != 'removed'
    ORDER BY l.created_at DESC
");
?>

<div class="page-header">
    <div class="container d-flex justify-content-between align-items-center">
        <div>
            <h1><i class="bi bi-grid me-2"></i>My Listings</h1>
            <p>Manage all items you have posted</p>
        </div>
        <a href="/create-listing.php" class="btn btn-accent-ll">
            <i class="bi bi-plus me-1"></i>Post New Item
        </a>
    </div>
</div>

<div class="container py-5">
    <?php if ($deleted): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i>Listing removed successfully.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($listings->num_rows > 0): ?>
    <div class="bg-white rounded-ll shadow-ll overflow-hidden">
        <table class="table table-ll mb-0">
            <thead>
                <tr>
                    <th style="width:60px;">Image</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Posted</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($l = $listings->fetch_assoc()): ?>
            <tr>
                <td>
                    <?php if ($l['image']): ?>
                        <img src="<?= htmlspecialchars($l['image']) ?>"
                             style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
                    <?php else: ?>
                        <div style="width:48px;height:48px;background:var(--primary-lt);border-radius:8px;display:flex;align-items:center;justify-content:center;">🏷️</div>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/listing.php?id=<?= $l['listing_id'] ?>" class="fw-600">
                        <?= htmlspecialchars(substr($l['title'], 0, 45)) ?>
                    </a>
                </td>
                <td><small class="text-muted"><?= htmlspecialchars($l['category_name']) ?></small></td>
                <td><strong><?= formatPrice($l['price']) ?></strong></td>
                <td>
                    <span class="status-badge badge-<?= $l['status'] ?>">
                        <?= ucfirst($l['status']) ?>
                    </span>
                </td>
                <td><small class="text-muted"><?= date('d M Y', strtotime($l['created_at'])) ?></small></td>
                <td>
                    <div class="d-flex gap-1">
                        <a href="/edit-listing.php?id=<?= $l['listing_id'] ?>"
                           class="btn btn-sm btn-outline-secondary" title="Edit">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <a href="/delete-listing.php?id=<?= $l['listing_id'] ?>"
                           class="btn btn-sm btn-outline-danger confirm-delete" title="Remove">
                            <i class="bi bi-trash"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <?php else: ?>
    <div class="text-center py-5">
        <div style="font-size:4rem;">📦</div>
        <h4 class="mt-3">No listings yet</h4>
        <p class="text-muted">Post your first item and start selling on LocalLoop.</p>
        <a href="/create-listing.php" class="btn btn-primary-ll">Post an Item</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
