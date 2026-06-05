<?php
$pageTitle = 'Browse Listings';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

// Filters
$search     = sanitise($conn, $_GET['q'] ?? '');
$cat_id     = intval($_GET['category'] ?? 0);
$min_price  = floatval($_GET['min'] ?? 0);
$max_price  = floatval($_GET['max'] ?? 0);
$sort       = sanitise($conn, $_GET['sort'] ?? 'newest');

// Build SQL
$where = ["l.status = 'active'"];
$params = [];
$types  = '';

if ($search) {
    $where[] = "(l.title LIKE ? OR l.description LIKE ?)";
    $like = "%$search%";
    $params[] = $like;
    $params[] = $like;
    $types .= 'ss';
}
if ($cat_id > 0) {
    $where[] = "l.category_id = ?";
    $params[] = $cat_id;
    $types   .= 'i';
}
if ($min_price > 0) {
    $where[] = "l.price >= ?";
    $params[] = $min_price;
    $types   .= 'd';
}
if ($max_price > 0) {
    $where[] = "l.price <= ?";
    $params[] = $max_price;
    $types   .= 'd';
}

$order_by = match($sort) {
    'price_asc'  => 'l.price ASC',
    'price_desc' => 'l.price DESC',
    'oldest'     => 'l.created_at ASC',
    default      => 'l.created_at DESC',
};

$sql = "SELECT l.*, c.name AS category_name, u.name AS seller_name
        FROM listings l
        JOIN categories c ON l.category_id = c.category_id
        JOIN users u ON l.seller_id = u.user_id
        WHERE " . implode(' AND ', $where) . "
        ORDER BY $order_by";

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Fetch categories for filter
$cats = $conn->query("SELECT * FROM categories ORDER BY name");

// Get selected category name
$cat_name = '';
if ($cat_id > 0) {
    $cr = $conn->query("SELECT name FROM categories WHERE category_id = $cat_id");
    if ($cr->num_rows) $cat_name = $cr->fetch_assoc()['name'];
}
?>

<!-- PAGE HEADER -->
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-grid me-2"></i>Browse Listings</h1>
        <p>Discover goods from sellers across South Africa</p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">

        <!-- FILTER SIDEBAR -->
        <div class="col-lg-3">
            <div class="sidebar-card mb-3">
                <div class="sidebar-header"><i class="bi bi-funnel me-2"></i>Filter Listings</div>
                <div class="p-3">
                    <form method="GET" action="">
                        <?php if ($search): ?>
                            <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>

                        <label class="form-label">Category</label>
                        <select name="category" class="form-select form-select-sm mb-3">
                            <option value="0">All Categories</option>
                            <?php while ($cat = $cats->fetch_assoc()): ?>
                                <option value="<?= $cat['category_id'] ?>"
                                    <?= $cat_id == $cat['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>

                        <label class="form-label">Price Range (R)</label>
                        <div class="d-flex gap-2 mb-3">
                            <input type="number" name="min" class="form-control form-control-sm price-input"
                                   placeholder="Min" value="<?= $min_price ?: '' ?>">
                            <input type="number" name="max" class="form-control form-control-sm price-input"
                                   placeholder="Max" value="<?= $max_price ?: '' ?>">
                        </div>

                        <label class="form-label">Sort By</label>
                        <select name="sort" class="form-select form-select-sm mb-3">
                            <option value="newest"     <?= $sort=='newest'     ? 'selected':'' ?>>Newest First</option>
                            <option value="oldest"     <?= $sort=='oldest'     ? 'selected':'' ?>>Oldest First</option>
                            <option value="price_asc"  <?= $sort=='price_asc'  ? 'selected':'' ?>>Price: Low to High</option>
                            <option value="price_desc" <?= $sort=='price_desc' ? 'selected':'' ?>>Price: High to Low</option>
                        </select>

                        <button type="submit" class="btn btn-primary-ll w-100 btn-sm">Apply Filters</button>
                        <a href="/browse.php" class="btn btn-light w-100 btn-sm mt-2">Clear Filters</a>
                    </form>
                </div>
            </div>
        </div>

        <!-- LISTINGS GRID -->
        <div class="col-lg-9">

            <!-- Results bar -->
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <strong><?= $result->num_rows ?></strong> listing<?= $result->num_rows != 1 ? 's' : '' ?> found
                    <?php if ($search): ?> for "<em><?= htmlspecialchars($search) ?></em>"<?php endif; ?>
                    <?php if ($cat_name): ?> in <em><?= htmlspecialchars($cat_name) ?></em><?php endif; ?>
                </div>
                <form method="GET" action="" class="d-flex gap-2">
                    <?php if ($cat_id): ?><input type="hidden" name="category" value="<?= $cat_id ?>"> <?php endif; ?>
                    <input type="text" name="q" class="form-control form-control-sm"
                           placeholder="Search listings..." value="<?= htmlspecialchars($search) ?>" style="width:200px;">
                    <button class="btn btn-sm btn-primary-ll">Go</button>
                </form>
            </div>

            <?php if ($result->num_rows > 0): ?>
            <div class="row g-3">
                <?php while ($listing = $result->fetch_assoc()): ?>
                <div class="col-sm-6 col-xl-4">
                    <a href="/listing.php?id=<?= $listing['listing_id'] ?>" style="text-decoration:none;">
                        <div class="listing-card">
                            <?php if ($listing['image']): ?>
                                <img src="<?= htmlspecialchars($listing['image']) ?>"
                                     class="card-img-top" alt="<?= htmlspecialchars($listing['title']) ?>">
                            <?php else: ?>
                                <div class="card-img-placeholder">🏷️</div>
                            <?php endif; ?>
                            <div class="card-body">
                                <span class="category-badge"><?= htmlspecialchars($listing['category_name']) ?></span>
                                <div class="card-title"><?= htmlspecialchars($listing['title']) ?></div>
                                <div class="price"><?= formatPrice($listing['price']) ?></div>
                                <div class="seller-name mt-1">
                                    <i class="bi bi-person me-1"></i><?= htmlspecialchars($listing['seller_name']) ?>
                                    <span class="ms-2 text-muted">
                                        <i class="bi bi-clock me-1"></i><?= date('d M', strtotime($listing['created_at'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endwhile; ?>
            </div>

            <?php else: ?>
            <div class="text-center py-5">
                <div style="font-size:4rem;">🔍</div>
                <h4 class="mt-3">No listings found</h4>
                <p class="text-muted">Try adjusting your search or filters.</p>
                <a href="/browse.php" class="btn btn-primary-ll">View All Listings</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>