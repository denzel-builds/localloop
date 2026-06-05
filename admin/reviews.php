<?php
$pageTitle = 'Manage Reviews';
require_once __DIR__ . '/includes/header.php';
requireAdminLogin();
require_once __DIR__ . '/includes/navbar.php';

$msg = '';

// Delete review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['review_id'])) {
    $rid = intval($_POST['review_id']);
    if ($rid) {
        $conn->query("DELETE FROM reviews WHERE review_id = $rid");
        $msg = 'Review deleted.';
    }
}

// Filter by rating
$filter_rating = intval($_GET['rating'] ?? 0);
$where = $filter_rating ? "WHERE r.rating = $filter_rating" : '';

$reviews = $conn->query("
    SELECT r.*,
           reviewer.name AS reviewer_name,
           seller.name   AS seller_name,
           l.title       AS listing_title
    FROM reviews r
    JOIN users reviewer ON r.reviewer_id = reviewer.user_id
    JOIN users seller   ON r.seller_id   = seller.user_id
    JOIN listings l     ON r.listing_id  = l.listing_id
    $where
    ORDER BY r.created_at DESC
");
?>

<?php if ($msg): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<!-- FILTER -->
<form method="GET" class="d-flex gap-2 mb-4 align-items-center">
    <label class="me-1" style="font-size:14px;">Filter by rating:</label>
    <?php for ($i = 1; $i <= 5; $i++): ?>
    <a href="/admin/reviews.php?rating=<?= $i ?>"
       class="btn btn-sm <?= $filter_rating==$i ? 'btn-primary-ll' : 'btn-outline-secondary' ?>">
        <?= str_repeat('★', $i) ?>
    </a>
    <?php endfor; ?>
    <a href="/admin/reviews.php" class="btn btn-sm btn-light">All</a>
    <span class="text-muted ms-2" style="font-size:13px;"><?= $reviews->num_rows ?> review(s)</span>
</form>

<div class="admin-card">
    <div class="admin-card-header">
        <h5><i class="bi bi-star me-2"></i>All Reviews</h5>
    </div>
    <table class="table table-ll mb-0">
        <thead>
            <tr>
                <th>#</th>
                <th>Listing</th>
                <th>Reviewer</th>
                <th>Seller Reviewed</th>
                <th>Rating</th>
                <th>Comment</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($r = $reviews->fetch_assoc()): ?>
        <tr>
            <td style="font-size:12px;color:var(--muted);"><?= $r['review_id'] ?></td>
            <td style="font-size:13px;">
                <a href="/listing.php?id=<?= $r['listing_id'] ?>" target="_blank">
                    <?= htmlspecialchars(substr($r['listing_title'], 0, 25)) ?>...
                </a>
            </td>
            <td style="font-size:13px;"><?= htmlspecialchars($r['reviewer_name']) ?></td>
            <td style="font-size:13px;"><?= htmlspecialchars($r['seller_name']) ?></td>
            <td><?= starRating($r['rating']) ?></td>
            <td style="font-size:13px;max-width:200px;">
                <?= $r['comment'] ? htmlspecialchars(substr($r['comment'], 0, 60)) . '...' : '<span class="text-muted">No comment</span>' ?>
            </td>
            <td style="font-size:12px;color:var(--muted);"><?= date('d M Y', strtotime($r['created_at'])) ?></td>
            <td>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="review_id" value="<?= $r['review_id'] ?>">
                    <button class="btn btn-sm btn-outline-danger confirm-delete" title="Delete review">
                        <i class="bi bi-trash"></i>
                    </button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
