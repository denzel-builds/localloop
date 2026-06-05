<?php
require_once 'includes/header.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) redirect('/browse.php');

$stmt = $conn->prepare("
    SELECT l.*, c.name AS category_name, u.name AS seller_name, u.user_id AS seller_id, u.created_at AS seller_joined
    FROM listings l
    JOIN categories c ON l.category_id = c.category_id
    JOIN users u ON l.seller_id = u.user_id
    WHERE l.listing_id = ?
");
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    redirect('/browse.php');
}
$listing = $result->fetch_assoc();
$pageTitle = $listing['title'];

// Reviews for this listing/seller
$reviews_stmt = $conn->prepare("
    SELECT r.*, u.name AS reviewer_name
    FROM reviews r
    JOIN users u ON r.reviewer_id = u.user_id
    WHERE r.listing_id = ?
    ORDER BY r.created_at DESC
    LIMIT 5
");
$reviews_stmt->bind_param('i', $id);
$reviews_stmt->execute();
$reviews = $reviews_stmt->get_result();

// Average rating for seller
$avg_stmt = $conn->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM reviews WHERE seller_id = ?");
$avg_stmt->bind_param('i', $listing['seller_id']);
$avg_stmt->execute();
$avg_data = $avg_stmt->get_result()->fetch_assoc();

// Has user already ordered this?
$already_ordered = false;
if (isLoggedIn()) {
    $ao = $conn->prepare("SELECT order_id FROM orders WHERE buyer_id = ? AND listing_id = ?");
    $ao->bind_param('ii', $_SESSION['user_id'], $id);
    $ao->execute();
    $already_ordered = $ao->get_result()->num_rows > 0;
}

require_once 'includes/navbar.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="/browse.php">Browse</a></li>
            <li class="breadcrumb-item">
                <a href="/browse.php?category=<?= $listing['category_id'] ?>"><?= htmlspecialchars($listing['category_name']) ?></a>
            </li>
            <li class="breadcrumb-item active"><?= htmlspecialchars(substr($listing['title'],0,30)) ?>...</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- IMAGE -->
        <div class="col-md-6">
            <?php if ($listing['image']): ?>
                <img src="<?= htmlspecialchars($listing['image']) ?>"
                     class="listing-detail-img" alt="<?= htmlspecialchars($listing['title']) ?>">
            <?php else: ?>
                <div class="listing-detail-img-placeholder">🏷️</div>
            <?php endif; ?>
        </div>

        <!-- DETAILS -->
        <div class="col-md-6">
            <span class="category-badge mb-2 d-inline-block"><?= htmlspecialchars($listing['category_name']) ?></span>

            <?php if ($listing['status'] !== 'active'): ?>
                <div class="alert alert-warning"><i class="bi bi-exclamation-triangle me-2"></i>This item is no longer available.</div>
            <?php endif; ?>

            <h1 style="font-size:1.7rem;"><?= htmlspecialchars($listing['title']) ?></h1>
            <div class="price-tag"><?= formatPrice($listing['price']) ?></div>

            <p class="text-muted mt-3" style="font-size:14px;">
                <i class="bi bi-clock me-1"></i>Posted <?= date('d M Y', strtotime($listing['created_at'])) ?>
            </p>

            <hr>

            <h5>About this item</h5>
            <p><?= nl2br(htmlspecialchars($listing['description'])) ?></p>

            <hr>

            <?php if ($order_msg): ?>
                <div class="alert alert-success"><i class="bi bi-check-circle me-2"></i><?= $order_msg ?></div>
            <?php endif; ?>
            <?php if ($order_err): ?>
                <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $order_err ?></div>
            <?php endif; ?>

            <?php if ($listing['status'] === 'active'): ?>
                <?php if (!isLoggedIn()): ?>
                    <a href="/login.php" class="btn btn-primary-ll w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login to Buy
                    </a>
                <?php elseif ($_SESSION['user_id'] == $listing['seller_id']): ?>
                    <div class="d-flex gap-2">
                        <a href="/edit-listing.php?id=<?= $id ?>" class="btn btn-outline-ll flex-fill">
                            <i class="bi bi-pencil me-1"></i>Edit
                        </a>
                        <a href="/delete-listing.php?id=<?= $id ?>" class="btn btn-outline-danger flex-fill confirm-delete">
                            <i class="bi bi-trash me-1"></i>Delete
                        </a>
                    </div>
                <?php elseif ($already_ordered): ?>
                    <button class="btn btn-secondary w-100" disabled>
                        <i class="bi bi-check-circle me-2"></i>Order Already Placed
                    </button>
                <?php else: ?>
                    <a href="/checkout.php?listing_id=<?= $listing['listing_id'] ?>" class="btn btn-primary-ll w-100">
                        <i class="bi bi-lock me-2"></i>Proceed to Checkout
                    </a>
                    <div class="mt-2 text-center" style="font-size:12px;color:var(--muted);">
                        <i class="bi bi-shield-check me-1 text-success"></i>
                        Secure checkout . 2% platform fee applies
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- SELLER INFO -->
    <div class="row mt-5">
        <div class="col-md-6">
            <div class="sidebar-card">
                <div class="sidebar-header"><i class="bi bi-person-badge me-2"></i>Seller Information</div>
                <div class="p-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:52px;height:52px;background:var(--primary-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                            👤
                        </div>
                        <div>
                            <h5 class="mb-0"><?= htmlspecialchars($listing['seller_name']) ?></h5>
                            <small class="text-muted">Member since <?= date('M Y', strtotime($listing['seller_joined'])) ?></small>
                        </div>
                    </div>
                    <?php if ($avg_data['total'] > 0): ?>
                        <div class="mb-2">
                            <?= starRating(round($avg_data['avg_rating'])) ?>
                            <span class="ms-2 text-muted" style="font-size:13px;">
                                <?= number_format($avg_data['avg_rating'],1) ?>/5 (<?= $avg_data['total'] ?> review<?= $avg_data['total'] != 1 ? 's' : '' ?>)
                            </span>
                        </div>
                    <?php else: ?>
                        <p class="text-muted" style="font-size:13px;">No reviews yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- REVIEWS -->
        <div class="col-md-6">
            <h4 class="section-title">Reviews</h4>
            <?php if ($reviews->num_rows > 0): ?>
                <?php while ($r = $reviews->fetch_assoc()): ?>
                <div class="bg-white rounded-ll p-3 mb-3 shadow-ll">
                    <div class="d-flex justify-content-between mb-1">
                        <strong style="font-size:14px;"><?= htmlspecialchars($r['reviewer_name']) ?></strong>
                        <span style="font-size:12px;color:var(--muted);"><?= date('d M Y', strtotime($r['created_at'])) ?></span>
                    </div>
                    <?= starRating($r['rating']) ?>
                    <?php if ($r['comment']): ?>
                        <p class="mt-2 mb-0" style="font-size:14px;"><?= htmlspecialchars($r['comment']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="text-muted">No reviews yet for this listing.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>