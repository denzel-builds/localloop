<?php
$pageTitle = 'Incoming Orders';
require_once 'includes/header.php';
requireLogin();
require_once 'includes/navbar.php';

$uid = $_SESSION['user_id'];
$msg = '';
$err = '';

// Handle seller response (approve or decline)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $order_id     = intval($_POST['order_id']);
    $new_status   = sanitise($conn, $_POST['order_status']);
    $seller_note  = sanitise($conn, $_POST['seller_note'] ?? '');
    $allowed      = ['approved', 'declined', 'completed'];

    if (!in_array($new_status, $allowed)) {
        $err = 'Invalid status.';
    } else {
        // Make sure this order belongs to one of the seller's listings
        $check = $conn->prepare("
            SELECT o.order_id FROM orders o
            JOIN listings l ON o.listing_id = l.listing_id
            WHERE o.order_id = ? AND l.seller_id = ?
        ");
        $check->bind_param('ii', $order_id, $uid);
        $check->execute();

        if ($check->get_result()->num_rows === 0) {
            $err = 'Order not found or does not belong to your listings.';
        } else {
            $upd = $conn->prepare("
                UPDATE orders SET order_status = ?, seller_note = ?
                WHERE order_id = ?
            ");
            $upd->bind_param('ssi', $new_status, $seller_note, $order_id);
            $upd->execute()
                ? $msg = 'Order updated. The buyer will see your response.'
                : $err = 'Could not update order.';
        }
    }
}

// Fetch all orders for this seller's listings
$orders = $conn->query("
    SELECT o.*,
           l.title       AS listing_title,
           l.image       AS listing_image,
           l.listing_id  AS listing_id,
           buyer.name    AS buyer_name,
           buyer.email   AS buyer_email
    FROM orders o
    JOIN listings l ON o.listing_id = l.listing_id
    JOIN users buyer ON o.buyer_id = buyer.user_id
    WHERE l.seller_id = $uid
    ORDER BY 
        FIELD(o.order_status, 'pending', 'approved', 'completed', 'declined'),
        o.created_at DESC
");
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-inbox me-2"></i>Incoming Orders</h1>
        <p>Manage orders placed on your listings</p>
    </div>
</div>

<div class="container py-5">

    <?php if ($msg): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?= $msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($err): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i><?= $err ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($orders->num_rows > 0): ?>
    <div class="row g-4">
        <?php while ($o = $orders->fetch_assoc()): ?>
        <div class="col-12">
            <div class="bg-white rounded-ll shadow-ll p-4">
                <div class="row align-items-start g-3">

                    <!-- Listing Image -->
                    <div class="col-auto">
                        <?php if ($o['listing_image']): ?>
                            <img src="<?= htmlspecialchars($o['listing_image']) ?>"
                                 style="width:80px;height:80px;object-fit:cover;border-radius:10px;">
                        <?php else: ?>
                            <div style="width:80px;height:80px;background:var(--primary-lt);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:2rem;">🏷️</div>
                        <?php endif; ?>
                    </div>

                    <!-- Order Info -->
                    <div class="col">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <h5 class="mb-1">
                                    <a href="/listing.php?id=<?= $o['listing_id'] ?>">
                                        <?= htmlspecialchars($o['listing_title']) ?>
                                    </a>
                                </h5>
                                <div style="font-size:13px;color:var(--muted);">
                                    <i class="bi bi-person me-1"></i>
                                    <strong><?= htmlspecialchars($o['buyer_name']) ?></strong>
                                    &nbsp;·&nbsp;
                                    <?= htmlspecialchars($o['buyer_email']) ?>
                                    &nbsp;·&nbsp;
                                    Ordered <?= date('d M Y \a\t H:i', strtotime($o['created_at'])) ?>
                                </div>
                                <div class="mt-2">
                                    <strong style="font-size:1.1rem;color:var(--primary);">
                                        <?= formatPrice($o['total_price']) ?>
                                    </strong>
                                </div>
                            </div>
                            <!-- Current status badge -->
                            <div>
                                <?php
                                $badge_map = [
                                    'pending'   => 'badge-pending',
                                    'approved'  => 'badge-paid',
                                    'declined'  => 'badge-removed',
                                    'completed' => 'badge-sold',
                                ];
                                $badge = $badge_map[$o['order_status']] ?? 'badge-pending';
                                ?>
                                <span class="status-badge <?= $badge ?>" style="font-size:13px;padding:6px 16px;">
                                    <?= ucfirst($o['order_status']) ?>
                                </span>
                            </div>
                        </div>

                        <?php if ($o['seller_note']): ?>
                        <div class="mt-2 p-2 rounded" style="background:var(--primary-lt);font-size:13px;">
                            <i class="bi bi-chat-left-text me-1"></i>
                            Your note: <?= htmlspecialchars($o['seller_note']) ?>
                        </div>
                        <?php endif; ?>

                        <!-- Response form — only show if not yet completed or declined -->
                        <?php if ($o['order_status'] !== 'completed'): ?>
                        <div class="mt-3 pt-3 border-top">
                            <form method="POST" class="row g-2 align-items-end">
                                <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">

                                <div class="col-md-3">
                                    <label class="form-label" style="font-size:13px;font-weight:600;">Update Status</label>
                                    <select name="order_status" class="form-select form-select-sm">
                                        <option value="pending"   <?= $o['order_status']=='pending'   ? 'selected':'' ?>>Pending</option>
                                        <option value="approved"  <?= $o['order_status']=='approved'  ? 'selected':'' ?>>Approved</option>
                                        <option value="declined"  <?= $o['order_status']=='declined'  ? 'selected':'' ?>>Declined</option>
                                        <option value="completed" <?= $o['order_status']=='completed' ? 'selected':'' ?>>Completed</option>
                                    </select>
                                </div>

                                <div class="col-md-7">
                                    <label class="form-label" style="font-size:13px;font-weight:600;">
                                        Message to buyer <span class="text-muted">(optional)</span>
                                    </label>
                                    <input type="text" name="seller_note" class="form-control form-control-sm"
                                           placeholder="e.g. Ready for collection in Soweto, call 082..."
                                           value="<?= htmlspecialchars($o['seller_note'] ?? '') ?>">
                                </div>

                                <div class="col-md-2">
                                    <button type="submit" class="btn btn-primary-ll btn-sm w-100">
                                        <i class="bi bi-check-lg me-1"></i>Respond
                                    </button>
                                </div>
                            </form>
                        </div>
                        <?php else: ?>
                        <div class="mt-2">
                            <small class="text-success"><i class="bi bi-check-circle me-1"></i>This order is completed.</small>
                        </div>
                        <?php endif; ?>

                    </div>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php else: ?>
    <div class="text-center py-5">
        <div style="font-size:4rem;">📭</div>
        <h4 class="mt-3">No orders yet</h4>
        <p class="text-muted">When someone places an order on one of your listings, it will appear here.</p>
        <a href="/browse.php" class="btn btn-primary-ll">Browse Listings</a>
    </div>
    <?php endif; ?>

</div>

<?php require_once 'includes/footer.php'; ?>