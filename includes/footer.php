<footer class="footer-ll">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="footer-brand">Local<span>Loop</span></div>
                <p class="mt-2" style="font-size:14px;">
                    A secure C2C marketplace built for South Africa's informal economy. 
                    Buy, sell and connect with people in your community.
                </p>
            </div>
            <div class="col-md-2">
                <h5>Marketplace</h5>
                <a href="/browse.php">Browse Listings</a>
                <a href="/browse.php?category=1">Clothing</a>
                <a href="/browse.php?category=2">Electronics</a>
                <a href="/browse.php?category=3">Furniture</a>
            </div>
            <div class="col-md-2">
                <h5>Sellers</h5>
                <a href="/create-listing.php">Post an Item</a>
                <a href="/dashboard.php">My Dashboard</a>
                <a href="/my-listings.php">My Listings</a>
            </div>
            <div class="col-md-2">
                <h5>Account</h5>
                <a href="/register.php">Register</a>
                <a href="/login.php">Login</a>
                <a href="/my-orders.php">My Orders</a>
            </div>
            <div class="col-md-2">
                <h5>Support</h5>
                <a href="#">About LocalLoop</a>
                <a href="#">Safety Tips</a>
                <a href="#">Contact Us</a>
            </div>
        </div>
        <div class="footer-bottom">
            <p class="mb-0">
                &copy; <?= date('Y') ?> LocalLoop — C2C E-Commerce Platform &nbsp;|&nbsp;
                Built for the South African informal economy
            </p>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- LocalLoop JS -->
<script src="/assets/js/main.js"></script>
</body>
</html>