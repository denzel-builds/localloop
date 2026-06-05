<?php
$pageTitle = 'Manage Listings';
require_once __DIR__ . '/includes/header.php';
requireAdminLogin();
require_once __DIR__ . '/includes/navbar.php';

$msg = '';
$err = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $lid    = intval($_POST['listing_id'] ?? 0);
    $action = $_POST['action'];

    if ($action === 'update_status') {
        $status  = sanitise($conn, $_POST['new_status']);
        $allowed = ['active', 'sold', 'removed'];
        if (in_array($status, $allowed) && $lid) {
            $conn->query("UPDATE listings SET status = '$status' WHERE listing_id = $lid");
            $msg = "Listing status updated to $status.";
        }
    }

    if ($action === 'delete_listing' && hasPermission('all')) {
        if ($lid) {
            $conn->query("DELETE FROM listings WHERE listing_id = $lid");
            $msg = 'Listing permanently deleted.';
        }
    }
}

// Filters
$search = sanitise($conn, $_GET['q']      ?? '');
$status = sanitise($conn, $_GET['status'] ?? '');

$where = ['1=1'];
if ($search) $where[] = "(l.title LIKE '%$search%' OR u.name LIKE '%$search%')";
if ($status) $where[] = "l.status = '$status'";

$listings = $conn->query("
    SELECT l.*, c.name AS category_name, u.name AS seller_name
    FROM listings l
    JOIN categories c ON l.category_id = c.category_id
    JOIN users u ON l.seller_id = u.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY l.created_at DESC
");
?>

<?php if ($msg): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($err): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i><?= $err ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- FILTERS -->
<form method="GET" class="d-flex gap-2 mb-4 flex-wrap">
    <input type="text" name="q" class="form-control form-control-sm"
           placeholder="Search title or seller..." value="<?= htmlspecialchars($search) ?>" style="width:220px;">
    <select name="status" class="form-select form-select-sm" style="width:150px;">
        <option value="">All Statuses</option>
        <option value="active"  <?= $status=='active'  ? 'selected':'' ?>>Active</option>
        <option value="sold"    <?= $status=='sold'    ? 'selected':'' ?>>Sold</option>
        <option value="removed" <?= $status=='removed' ? 'selected':'' ?>>Removed</option>
    </select>
    <button class="btn btn-sm btn-primary-ll">Filter</button>
    <a href="/admin/listings.php" class="btn btn-sm btn-light">Clear</a>
</form>

<div class="admin-card">
    <div class="admin-card-header">
        <h5><i class="bi bi-grid me-2"></i>All Listings (<?= $listings->num_rows ?>)</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-ll mb-0">
            <thead>
                <tr>
                    <th style="width:56px;">Image</th>
                    <th>Title</th>
                    <th>Seller</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($l = $listings->fetch_assoc()): ?>
            <tr>
                <td>
                    <?php if ($l['image']): ?>
                        <img src="<?= htmlspecialchars($l['image']) ?>"
                             style="width:44px;height:44px;object-fit:cover;border-radius:8px;">
                    <?php else: ?>
                        <div style="width:44px;height:44px;background:var(--primary-lt);border-radius:8px;display:flex;align-items:center;justify-content:center;">🏷️</div>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="/listing.php?id=<?= $l['listing_id'] ?>" target="_blank" style="font-size:13px;">
                        <?= htmlspecialchars(substr($l['title'], 0, 40)) ?>...
                    </a>
                </td>
                <td style="font-size:13px;"><?= htmlspecialchars($l['seller_name']) ?></td>
                <td><small class="text-muted"><?= htmlspecialchars($l['category_name']) ?></small></td>
                <td><strong><?= formatPrice($l['price']) ?></strong></td>
                <td>
                    <span class="status-badge badge-<?= $l['status'] ?>">
                        <?= ucfirst($l['status']) ?>
                    </span>
                </td>
                <td style="font-size:12px;color:var(--muted);"><?= date('d M Y', strtotime($l['created_at'])) ?></td>
                <td>
                    <!-- Quick status change -->
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="listing_id" value="<?= $l['listing_id'] ?>">
                        <input type="hidden" name="action"     value="update_status">
                        <select name="new_status" class="form-select form-select-sm d-inline-block"
                                style="width:100px;" onchange="this.form.submit()">
                            <option value="active"  <?= $l['status']=='active'  ? 'selected':'' ?>>Active</option>
                            <option value="sold"    <?= $l['status']=='sold'    ? 'selected':'' ?>>Sold</option>
                            <option value="removed" <?= $l['status']=='removed' ? 'selected':'' ?>>Remove</option>
                        </select>
                    </form>
                    <?php if (hasPermission('all')): ?>
                    <form method="POST" class="d-inline ms-1">
                        <input type="hidden" name="listing_id" value="<?= $l['listing_id'] ?>">
                        <input type="hidden" name="action"     value="delete_listing">
                        <button class="btn btn-sm btn-outline-danger confirm-delete" title="Delete permanently">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
