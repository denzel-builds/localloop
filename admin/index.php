<?php
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
requireAdminLogin();
require_once __DIR__ . '/includes/navbar.php';

// Platform stats
$total_users    = $conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$total_listings = $conn->query("SELECT COUNT(*) AS c FROM listings WHERE status = 'active'")->fetch_assoc()['c'];
$total_orders   = $conn->query("SELECT COUNT(*) AS c FROM orders")->fetch_assoc()['c'];
$total_reviews  = $conn->query("SELECT COUNT(*) AS c FROM reviews")->fetch_assoc()['c'];
$suspended      = $conn->query("SELECT COUNT(*) AS c FROM users WHERE status = 'suspended'")->fetch_assoc()['c'];
$revenue        = $conn->query("SELECT SUM(total_price) AS s FROM orders WHERE payment_status = 'paid'")->fetch_assoc()['s'] ?? 0;

// Recent users
$recent_users = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// Recent orders
$recent_orders = $conn->query("
    SELECT o.*, l.title, u.name AS buyer_name
    FROM orders o
    JOIN listings l ON o.listing_id = l.listing_id
    JOIN users u ON o.buyer_id = u.user_id
    ORDER BY o.created_at DESC LIMIT 5
");
?>

<!-- STAT CARDS -->
<div class="row g-3 mb-4">
    <div class="col-6 col-xl-3">
        <div class="admin-stat d-flex align-items-center gap-3">
            <div class="stat-icon blue"><i class="bi bi-people"></i></div>
            <div><h3><?= $total_users ?></h3><p>Total Users</p></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat d-flex align-items-center gap-3">
            <div class="stat-icon green"><i class="bi bi-grid"></i></div>
            <div><h3><?= $total_listings ?></h3><p>Active Listings</p></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat d-flex align-items-center gap-3">
            <div class="stat-icon amber"><i class="bi bi-bag"></i></div>
            <div><h3><?= $total_orders ?></h3><p>Total Orders</p></div>
        </div>
    </div>
    <div class="col-6 col-xl-3">
        <div class="admin-stat d-flex align-items-center gap-3">
            <div class="stat-icon red"><i class="bi bi-star"></i></div>
            <div><h3><?= $total_reviews ?></h3><p>Reviews</p></div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- RECENT USERS -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5><i class="bi bi-people me-2"></i>Recent Users</h5>
                <a href="/admin/users.php" style="font-size:13px;">View all →</a>
            </div>
            <table class="table table-ll mb-0">
                <thead><tr><th>Name</th><th>Email</th><th>Status</th><th>Joined</th></tr></thead>
                <tbody>
                <?php while ($u = $recent_users->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($u['name']) ?></td>
                    <td style="font-size:13px;"><?= htmlspecialchars($u['email']) ?></td>
                    <td><span class="status-badge badge-<?= $u['status'] ?>"><?= ucfirst($u['status']) ?></span></td>
                    <td style="font-size:12px;color:var(--muted);"><?= date('d M', strtotime($u['created_at'])) ?></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- RECENT ORDERS -->
    <div class="col-lg-6">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5><i class="bi bi-bag me-2"></i>Recent Orders</h5>
                <a href="/admin/orders.php" style="font-size:13px;">View all →</a>
            </div>
            <table class="table table-ll mb-0">
                <thead><tr><th>Item</th><th>Buyer</th><th>Total</th><th>Status</th></tr></thead>
                <tbody>
                <?php while ($o = $recent_orders->fetch_assoc()): ?>
                <tr>
                    <td style="font-size:13px;"><?= htmlspecialchars(substr($o['title'],0,25)) ?>...</td>
                    <td style="font-size:13px;"><?= htmlspecialchars($o['buyer_name']) ?></td>
                    <td><strong><?= formatPrice($o['total_price']) ?></strong></td>
                    <td><span class="status-badge badge-<?= $o['payment_status'] ?>"><?= ucfirst($o['payment_status']) ?></span></td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- QUICK STATS ROW -->
    <div class="col-12">
        <div class="admin-card p-4">
            <div class="row g-4 text-center">
                <div class="col-4">
                    <h4 style="color:var(--primary);"><?= formatPrice($revenue) ?></h4>
                    <p class="text-muted mb-0" style="font-size:13px;">Paid Orders Revenue</p>
                </div>
                <div class="col-4">
                    <h4 style="color:#e55353;"><?= $suspended ?></h4>
                    <p class="text-muted mb-0" style="font-size:13px;">Suspended Users</p>
                </div>
                <div class="col-4">
                    <h4 style="color:var(--accent);"><?= $total_reviews ?></h4>
                    <p class="text-muted mb-0" style="font-size:13px;">Total Reviews</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>