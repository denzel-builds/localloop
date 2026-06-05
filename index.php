<?php
$pageTitle = 'Home';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

// Fetch featured listings (latest 8 active)
$sql = "SELECT l.*, c.name AS category_name, u.name AS seller_name
        FROM listings l
        JOIN categories c ON l.category_id = c.category_id
        JOIN users u ON l.seller_id = u.user_id
        WHERE l.status = 'active'
        ORDER BY l.created_at DESC
        LIMIT 8";
$result = $conn->query($sql);

// Fetch all categories
$cats = $conn->query("SELECT * FROM categories ORDER BY name");
?>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-lg-7">
                <p style="color:var(--accent);font-weight:700;font-size:13px;letter-spacing:1px;text-transform:uppercase;">
                    🇿🇦 South Africa's Community Marketplace
                </p>
                <h1>Buy &amp; Sell Goods <br>In Your Community</h1>
                <p>LocalLoop connects buyers and sellers across South Africa's township communities. 
                   Secure, simple, and built for the local economy.</p>
                <form action="/browse.php" method="GET" class="mt-4">
                    <div class="search-bar">
                        <i class="bi bi-search me-2" style="color:#aaa;"></i>
                        <input type="text" name="q" placeholder="Search for clothing, electronics, furniture...">
                        <button type="submit">Search</button>
                    </div>
                </form>
                <div class="mt-3 d-flex gap-3 flex-wrap">
                    <span style="color:rgba(255,255,255,.65);font-size:13px;"><i class="bi bi-shield-check me-1 text-success"></i> Verified Sellers</span>
                    <span style="color:rgba(255,255,255,.65);font-size:13px;"><i class="bi bi-star-fill me-1 text-warning"></i> Buyer Reviews</span>
                    <span style="color:rgba(255,255,255,.65);font-size:13px;"><i class="bi bi-lock me-1" style="color:#7dd3fc;"></i> Secure Orders</span>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block text-center">
                <div style="font-size:9rem;line-height:1;filter:drop-shadow(0 20px 40px rgba(0,0,0,.3));">🛍️</div>
            </div>
        </div>
    </div>
</section>

<!-- CATEGORIES -->
<section class="py-4 bg-white border-bottom">
    <div class="container">
        <div class="d-flex flex-wrap gap-2 align-items-center">
            <span class="fw-600 me-2" style="font-weight:600;color:#555;">Browse:</span>
            <a href="/browse.php" class="category-pill <?= !isset($_GET['category']) ? 'active' : '' ?>">All Items</a>
            <?php
            $cats->data_seek(0);
            while ($cat = $cats->fetch_assoc()):
            ?>
            <a href="/browse.php?category=<?= $cat['category_id'] ?>" class="category-pill">
                <?= htmlspecialchars($cat['name']) ?>
            </a>
            <?php endwhile; ?>
        </div>
    </div>
</section>

<!-- FEATURED LISTINGS -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="section-title">Latest Listings</h2>
                <p class="text-muted mb-0" style="font-size:14px;">Fresh items added by your community</p>
            </div>
            <a href="/browse.php" class="btn-outline-ll btn">View All</a>
        </div>

        <?php if ($result && $result->num_rows > 0): ?>
        <div class="row g-3">
            <?php while ($listing = $result->fetch_assoc()): ?>
            <div class="col-6 col-md-4 col-lg-3">
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
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="text-center py-5">
            <div style="font-size:4rem;">📦</div>
            <h4 class="mt-3">No listings yet</h4>
            <p class="text-muted">Be the first to post something on LocalLoop!</p>
            <a href="/create-listing.php" class="btn btn-primary-ll">Post an Item</a>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="py-5 bg-white">
    <div class="container text-center">
        <h2 class="section-title mx-auto" style="display:inline-block;">How LocalLoop Works</h2>
        <p class="text-muted mt-2 mb-5">Three simple steps to buy or sell on LocalLoop</p>
        <div class="row g-4">
            <div class="col-md-4">
                <div style="font-size:3rem;margin-bottom:16px;">📝</div>
                <h5>1. Create an Account</h5>
                <p class="text-muted">Register for free. No hidden fees. Start buying or selling immediately after signing up.</p>
            </div>
            <div class="col-md-4">
                <div style="font-size:3rem;margin-bottom:16px;">🏷️</div>
                <h5>2. List or Browse</h5>
                <p class="text-muted">Post your goods with a photo and price, or browse thousands of items from community sellers.</p>
            </div>
            <div class="col-md-4">
                <div style="font-size:3rem;margin-bottom:16px;">🤝</div>
                <h5>3. Buy &amp; Connect</h5>
                <p class="text-muted">Place your order securely. Connect with the seller and complete the transaction safely.</p>
            </div>
        </div>
        <div class="mt-5">
            <?php if (!isLoggedIn()): ?>
                <a href="/register.php" class="btn btn-primary-ll me-3">Get Started Free</a>
                <a href="/browse.php" class="btn-outline-ll btn">Browse Listings</a>
            <?php else: ?>
                <a href="/create-listing.php" class="btn btn-primary-ll me-3">Post an Item</a>
                <a href="/browse.php" class="btn-outline-ll btn">Browse Listings</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>