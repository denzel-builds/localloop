<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';
requireLogin();
require_once 'includes/navbar.php';

$uid = $_SESSION['user_id'];
$welcome = isset($_GET['welcome']);

// Stats
$my_listings = $conn->query("SELECT COUNT(*) AS c FROM listings WHERE seller_id = $uid AND status != 'removed'")->fetch_assoc()['c'];
$my_orders   = $conn->query("SELECT COUNT(*) AS c FROM orders WHERE buyer_id = $uid")->fetch_assoc()['c'];
$my_sales    = $conn->query("SELECT COUNT(*) AS c FROM orders o JOIN listings l ON o.listing_id = l.listing_id WHERE l.seller_id = $uid")->fetch_assoc()['c'];
$my_reviews  = $conn->query("SELECT COUNT(*) AS c FROM reviews WHERE seller_id = $uid")->fetch_assoc()['c'];

$pending_incoming = $conn->query("
    SELECT COUNT(*) AS c FROM orders o
    JOIN listings l ON o.listing_id = l.listing_id
    WHERE l.seller_id = $uid AND o.order_status = 'pending'
")->fetch_assoc()['c'];

// Recent listings
$recent_listings = $conn->query("
    SELECT l.*, c.name AS category_name FROM listings l
    JOIN categories c ON l.category_id = c.category_id
    WHERE l.seller_id = $uid AND l.status != 'removed'
    ORDER BY l.created_at DESC LIMIT 5
");

// Recent orders (as buyer)
$recent_orders = $conn->query("
    SELECT o.*, l.title, l.price AS listing_price, u.name AS seller_name
    FROM orders o
    JOIN listings l ON o.listing_id = l.listing_id
    JOIN users u ON l.seller_id = u.user_id
    WHERE o.buyer_id = $uid
    ORDER BY o.created_at DESC LIMIT 5
");
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-speedometer2 me-2"></i>My Dashboard</h1>
        <p>Welcome back, <?= htmlspecialchars($_SESSION['user_name']) ?></p>
    </div>
</div>

<div class="container py-5">

    <?php if ($welcome): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4">
        <i class="bi bi-emoji-smile me-2"></i>
        <strong>Welcome to LocalLoop!</strong> Your account is ready. Start by posting your first item or browsing listings.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- STAT CARDS -->
    <div class="row g-3 mb-5">
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon green"><i class="bi bi-grid"></i></div>
                <div>
                    <h3><?= $my_listings ?></h3>
                    <p>My Listings</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon amber"><i class="bi bi-bag"></i></div>
                <div>
                    <h3><?= $my_orders ?></h3>
                    <p>Orders Placed</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon blue"><i class="bi bi-cash-stack"></i></div>
                <div>
                    <h3><?= $my_sales ?></h3>
                    <p>Items Sold</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card">
                <div class="stat-icon red"><i class="bi bi-star"></i></div>
                <div>
                    <h3><?= $my_reviews ?></h3>
                    <p>Reviews</p>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <a href="/seller-orders.php" style="text-decoration:none;">
                <div class="stat-card">
                    <div class="stat-icon amber">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <div>
                        <h3><?= $pending_incoming ?></h3>
                        <p>Pending Orders</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- MY LISTINGS -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="section-title">My Listings</h4>
                <a href="/create-listing.php" class="btn btn-primary-ll btn-sm">
                    <i class="bi bi-plus me-1"></i>Post Item
                </a>
            </div>
            <div class="bg-white rounded-ll shadow-ll overflow-hidden">
                <?php if ($recent_listings->num_rows > 0): ?>
                <table class="table table-ll mb-0">
                    <thead>
                        <tr>
                            <th>Item</th>
                            <th>Category</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($l = $recent_listings->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <a href="/listing.php?id=<?= $l['listing_id'] ?>" class="fw-500">
                                <?= htmlspecialchars(substr($l['title'],0,35)) ?>...
                            </a>
                        </td>
                        <td><small><?= htmlspecialchars($l['category_name']) ?></small></td>
                        <td><strong><?= formatPrice($l['price']) ?></strong></td>
                        <td>
                            <span class="status-badge badge-<?= $l['status'] ?>">
                                <?= ucfirst($l['status']) ?>
                            </span>
                        </td>
                        <td>
                            <a href="/edit-listing.php?id=<?= $l['listing_id'] ?>" class="btn btn-sm btn-outline-secondary me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="/delete-listing.php?id=<?= $l['listing_id'] ?>" class="btn btn-sm btn-outline-danger confirm-delete">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <div class="p-3">
                    <a href="/my-listings.php" class="text-primary-ll" style="font-size:14px;">View all listings →</a>
                </div>
                <?php else: ?>
                <div class="text-center py-5">
                    <div style="font-size:3rem;">📦</div>
                    <p class="text-muted mt-2">You have no listings yet.</p>
                    <a href="/create-listing.php" class="btn btn-primary-ll btn-sm">Post Your First Item</a>
                </div>
                <?php endif; ?>
            </div>

            <!-- RECENT PURCHASES -->
            <div class="d-flex justify-content-between align-items-center mt-5 mb-3">
                <h4 class="section-title">My Orders</h4>
                <a href="/my-orders.php" style="font-size:14px;" class="text-primary-ll">View all →</a>
            </div>
            <div class="bg-white rounded-ll shadow-ll overflow-hidden">
                <?php if ($recent_orders->num_rows > 0): ?>
                <table class="table table-ll mb-0">
                    <thead>
                        <tr><th>Item</th><th>Seller</th><th>Total</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                    <?php while ($o = $recent_orders->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars(substr($o['title'],0,30)) ?>...</td>
                        <td><?= htmlspecialchars($o['seller_name']) ?></td>
                        <td><strong><?= formatPrice($o['total_price']) ?></strong></td>
                        <td>
                            <span class="status-badge badge-<?= $o['payment_status'] ?>">
                                <?= ucfirst($o['payment_status']) ?>
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="text-center py-4">
                    <p class="text-muted mb-0">No orders yet. <a href="/browse.php">Browse listings</a></p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- QUICK LINKS SIDEBAR -->
        <div class="col-lg-4">
            <div class="sidebar-card">
                <div class="sidebar-header"><i class="bi bi-lightning me-2"></i>Quick Actions</div>
                <a href="/create-listing.php" class="sidebar-link">
                    <i class="bi bi-plus-circle me-2 text-primary-ll"></i>Post a New Item
                </a>
                <a href="/browse.php" class="sidebar-link">
                    <i class="bi bi-search me-2 text-primary-ll"></i>Browse Listings
                </a>
                <a href="/my-listings.php" class="sidebar-link">
                    <i class="bi bi-grid me-2 text-primary-ll"></i>Manage My Listings
                </a>
                <a href="/my-orders.php" class="sidebar-link">
                    <i class="bi bi-bag me-2 text-primary-ll"></i>View My Orders
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>