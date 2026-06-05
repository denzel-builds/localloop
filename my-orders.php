<?php
$pageTitle = 'My Orders';
require_once 'includes/header.php';
requireLogin();
require_once 'includes/navbar.php';

$uid         = $_SESSION['user_id'];
$review_msg  = '';
$review_err  = '';

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $listing_id = intval($_POST['listing_id'] ?? 0);
    $seller_id  = intval($_POST['seller_id']  ?? 0);
    $rating     = intval($_POST['rating']     ?? 0);
    $comment    = sanitise($conn, $_POST['comment'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $review_err = 'Please select a rating between 1 and 5.';
    } else {
        // Check user actually ordered this item
        $chk = $conn->prepare("SELECT order_id FROM orders WHERE buyer_id = ? AND listing_id = ?");
        $chk->bind_param('ii', $uid, $listing_id);
        $chk->execute();

        if ($chk->get_result()->num_rows === 0) {
            $review_err = 'You can only review items you have ordered.';
        } else {
            // Check not already reviewed
            $dup = $conn->prepare("SELECT review_id FROM reviews WHERE reviewer_id = ? AND listing_id = ?");
            $dup->bind_param('ii', $uid, $listing_id);
            $dup->execute();

            if ($dup->get_result()->num_rows > 0) {
                $review_err = 'You have already reviewed this order.';
            } else {
                $ins = $conn->prepare(
                    "INSERT INTO reviews (reviewer_id, seller_id, listing_id, rating, comment)
                     VALUES (?, ?, ?, ?, ?)"
                );
                $ins->bind_param('iiiis', $uid, $seller_id, $listing_id, $rating, $comment);
                $ins->execute()
                    ? $review_msg = 'Review submitted! Thank you.'
                    : $review_err = 'Could not save review.';
            }
        }
    }
}

// Fetch all orders for this user
$orders = $conn->query("
    SELECT o.*, l.title, l.image, l.seller_id,
           u.name AS seller_name,
           (SELECT review_id FROM reviews r
            WHERE r.reviewer_id = $uid AND r.listing_id = o.listing_id
            LIMIT 1) AS reviewed
    FROM orders o
    JOIN listings l ON o.listing_id = l.listing_id
    JOIN users u ON l.seller_id = u.user_id
    WHERE o.buyer_id = $uid
    ORDER BY o.created_at DESC
");
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-bag me-2"></i>My Orders</h1>
        <p>Track everything you have bought on LocalLoop</p>
    </div>
</div>

<div class="container py-5">

    <?php if ($review_msg): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle me-2"></i><?= $review_msg ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    <?php if ($review_err): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <i class="bi bi-exclamation-circle me-2"></i><?= $review_err ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if ($orders->num_rows > 0): ?>
    <div class="row g-4">
        <?php while ($o = $orders->fetch_assoc()): ?>
        <div class="col-12">
            <div class="bg-white rounded-ll shadow-ll p-4">
                <div class="row align-items-center g-3">
                    <!-- Image -->
                    <div class="col-auto">
                        <?php if ($o['image']): ?>
                            <img src="<?= htmlspecialchars($o['image']) ?>"
                                 style="width:80px;height:80px;object-fit:cover;border-radius:10px;">
                        <?php else: ?>
                            <div style="width:80px;height:80px;background:var(--primary-lt);border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:2rem;">🏷️</div>
                        <?php endif; ?>
                    </div>

                    <!-- Details -->
                    <div class="col">
                        <h5 class="mb-1">
                            <a href="/listing.php?id=<?= $o['listing_id'] ?>">
                                <?= htmlspecialchars($o['title']) ?>
                            </a>
                        </h5>
                        <div class="text-muted" style="font-size:13px;">
                            Seller: <strong><?= htmlspecialchars($o['seller_name']) ?></strong>
                            &nbsp;·&nbsp; Ordered: <?= date('d M Y', strtotime($o['created_at'])) ?>
                        </div>
                    </div>

                    <!-- Price + Status -->
                    <div class="col-auto text-end">
                        <div class="price mb-1"><?= formatPrice($o['total_price']) ?></div>
                        <?php
                        // Map order_status to a human-friendly message for the buyer
                        $status_labels = [
                            'pending' => ['badge-pending', 'Awaiting seller response'],
                            'approved' => ['badge-paid', 'Order approved'],
                            'declined' => ['badge-sold', 'Order Declined'],
                            'completed' => ['badge-sold', 'Completed'],
                        ];
                        $s = $status_labels[$o['order_status']] ?? ['badge-pending', 'Pending'];
                        ?>
                        <span class="status-badge <?= $s[0] ?>"  style="font-size:13px;padding:6px 14px;">
                            <?= $s[1] ?>
                        </span>

                        <?php if ($o['seller_note']): ?>
                        <div class="mt-2 p-2 rounded" style="background:#f0f7ff;font-size:13px;border-left:3px solid var(--primary);">
                            <i class="bi bi-chat-left-text me-1 text-primary-ll"></i>
                            <strong>Message from the seller:</strong> <?= htmlspecialchars($o['seller_note']) ?>
                        </div>
                        <?php elseif ($o['order_status'] === 'pending'): ?>
                        <div class="mt-2" style="font-size:12px;color:var(--muted);">
                            <i class="bi bi-clock me-1"></i>The seller has not responded to this order yet. Check back soon.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Review section -->
                <?php if (!$o['reviewed']): ?>
                <div class="mt-3 pt-3 border-top">
                    <p class="mb-2" style="font-size:14px;font-weight:600;">
                        <i class="bi bi-star me-1 text-warning"></i>Leave a review for this purchase
                    </p>
                    <form method="POST">
                        <input type="hidden" name="listing_id" value="<?= $o['listing_id'] ?>">
                        <input type="hidden" name="seller_id"  value="<?= $o['seller_id'] ?>">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div>
                                <label class="form-label mb-1" style="font-size:13px;">Rating</label>
                                <select name="rating" class="form-select form-select-sm" style="width:130px;" required>
                                    <option value="">— Stars —</option>
                                    <?php for ($i=5;$i>=1;$i--): ?>
                                    <option value="<?=$i?>"><?= str_repeat('★',$i) ?> (<?=$i?>/5)</option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="flex-grow-1">
                                <label class="form-label mb-1" style="font-size:13px;">Comment (optional)</label>
                                <input type="text" name="comment" class="form-control form-control-sm"
                                       placeholder="How was this seller or item?">
                            </div>
                            <div style="margin-top:20px;">
                                <button type="submit" name="submit_review" class="btn btn-sm btn-primary-ll">
                                    Submit Review
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class="mt-3 pt-3 border-top">
                    <small class="text-success"><i class="bi bi-check-circle me-1"></i>You have reviewed this purchase.</small>
                </div>
                <?php endif; ?>

            </div>
        </div>
        <?php endwhile; ?>
    </div>

    <?php else: ?>
    <div class="text-center py-5">
        <div style="font-size:4rem;">🛒</div>
        <h4 class="mt-3">No orders yet</h4>
        <p class="text-muted">Browse listings and place your first order.</p>
        <a href="/browse.php" class="btn btn-primary-ll">Browse Listings</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
