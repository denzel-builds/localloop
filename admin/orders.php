<?php
$pageTitle = 'Manage Orders';
require_once __DIR__ . '/includes/header.php';
requireAdminLogin();
require_once __DIR__ . '/includes/navbar.php';

$msg = '';

// Update payment status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'])) {
    $oid    = intval($_POST['order_id']);
    $status = sanitise($conn, $_POST['payment_status']);
    $allowed = ['pending', 'paid', 'failed', 'refunded'];

    if (in_array($status, $allowed) && $oid) {
        $conn->query("UPDATE orders SET payment_status = '$status' WHERE order_id = $oid");
        $msg = "Order #$oid status updated to $status.";
    }
}

// Filters
$filter_status = sanitise($conn, $_GET['status'] ?? '');
$where = $filter_status ? "WHERE o.payment_status = '$filter_status'" : '';

$orders = $conn->query("
    SELECT o.*,
           l.title        AS listing_title,
           l.price        AS listing_price,
           buyer.name     AS buyer_name,
           buyer.email    AS buyer_email,
           seller.name    AS seller_name
    FROM orders o
    JOIN listings l  ON o.listing_id = l.listing_id
    JOIN users buyer  ON o.buyer_id   = buyer.user_id
    JOIN users seller ON l.seller_id  = seller.user_id
    $where
    ORDER BY o.created_at DESC
");
?>

<?php if ($msg): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- FILTER BAR -->
<form method="GET" class="d-flex gap-2 mb-4 flex-wrap align-items-center">
    <select name="status" class="form-select form-select-sm" style="width:170px;" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="pending"  <?= $filter_status=='pending'  ? 'selected':'' ?>>Pending</option>
        <option value="paid"     <?= $filter_status=='paid'     ? 'selected':'' ?>>Paid</option>
        <option value="failed"   <?= $filter_status=='failed'   ? 'selected':'' ?>>Failed</option>
        <option value="refunded" <?= $filter_status=='refunded' ? 'selected':'' ?>>Refunded</option>
    </select>
    <a href="/admin/orders.php" class="btn btn-sm btn-light">Clear</a>
    <span class="text-muted ms-2" style="font-size:13px;"><?= $orders->num_rows ?> order(s) found</span>
</form>

<div class="admin-card">
    <div class="admin-card-header">
        <h5><i class="bi bi-bag me-2"></i>All Orders</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-ll mb-0">
            <thead>
                <tr>
                    <th>#ID</th>
                    <th>Item</th>
                    <th>Buyer</th>
                    <th>Seller</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th>Update</th>
                </tr>
            </thead>
            <tbody>
            <?php while ($o = $orders->fetch_assoc()): ?>
            <tr>
                <td style="color:var(--muted);font-size:12px;">#<?= $o['order_id'] ?></td>
                <td>
                    <a href="/listing.php?id=<?= $o['listing_id'] ?>" target="_blank" style="font-size:13px;">
                        <?= htmlspecialchars(substr($o['listing_title'], 0, 30)) ?>...
                    </a>
                </td>
                <td>
                    <div style="font-size:13px;"><?= htmlspecialchars($o['buyer_name']) ?></div>
                    <div style="font-size:11px;color:var(--muted);"><?= htmlspecialchars($o['buyer_email']) ?></div>
                </td>
                <td style="font-size:13px;"><?= htmlspecialchars($o['seller_name']) ?></td>
                <td><strong><?= formatPrice($o['total_price']) ?></strong></td>
                <td>
                    <span class="status-badge badge-<?= $o['payment_status'] ?>">
                        <?= ucfirst($o['payment_status']) ?>
                    </span>
                </td>
                <td style="font-size:12px;color:var(--muted);"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                <td>
                    <form method="POST" class="d-flex gap-1">
                        <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                        <select name="payment_status" class="form-select form-select-sm" style="width:105px;">
                            <option value="pending"  <?= $o['payment_status']=='pending'  ? 'selected':'' ?>>Pending</option>
                            <option value="paid"     <?= $o['payment_status']=='paid'     ? 'selected':'' ?>>Paid</option>
                            <option value="failed"   <?= $o['payment_status']=='failed'   ? 'selected':'' ?>>Failed</option>
                            <option value="refunded" <?= $o['payment_status']=='refunded' ? 'selected':'' ?>>Refunded</option>
                        </select>
                        <button class="btn btn-sm btn-primary-ll" title="Save">
                            <i class="bi bi-check-lg"></i>
                        </button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
