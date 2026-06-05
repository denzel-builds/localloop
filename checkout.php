<?php
$pageTitle = 'Secure Checkout';
require_once 'includes/header.php';
requireLogin();
require_once 'includes/navbar.php';

$listing_id = intval($_GET['listing_id'] ?? 0);
if (!$listing_id) redirect('/browse.php');

// Fetch listing details
$stmt = $conn->prepare("
    SELECT l.*, c.name AS category_name, u.name AS seller_name, u.user_id AS seller_id
    FROM listings l
    JOIN categories c ON l.category_id = c.category_id
    JOIN users u ON l.seller_id = u.user_id
    WHERE l.listing_id = ? AND l.status = 'active'
");
$stmt->bind_param('i', $listing_id);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();

if (!$listing) redirect('/browse.php');

// Cannot buy your own listing
if ($_SESSION['user_id'] == $listing['seller_id']) {
    redirect('/listing.php?id=' . $listing_id);
}

// Check if already ordered
$chk = $conn->prepare("SELECT order_id FROM orders WHERE buyer_id = ? AND listing_id = ?");
$chk->bind_param('ii', $_SESSION['user_id'], $listing_id);
$chk->execute();
if ($chk->get_result()->num_rows > 0) {
    redirect('/my-orders.php');
}

// Fee calculation
$item_price   = $listing['price'];
$fee_rate     = 0.02; // 2% platform fee
$platform_fee = round($item_price * $fee_rate, 2);
$total        = $item_price + $platform_fee;

$error = '';

// Handle mock payment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_name   = sanitise($conn, $_POST['card_name']   ?? '');
    $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $expiry      = sanitise($conn, $_POST['expiry']      ?? '');
    $cvv         = sanitise($conn, $_POST['cvv']         ?? '');

    // Basic validation
    if (empty($card_name) || strlen($card_number) < 16 || empty($expiry) || strlen($cvv) < 3) {
        $error = 'Please fill in all payment details correctly.';
    } else {
        // Create order with paid status
        $buyer_id = $_SESSION['user_id'];
        $qty      = 1;

        $ins = $conn->prepare("
            INSERT INTO orders 
                (buyer_id, listing_id, quantity, total_price, platform_fee, payment_status, order_status)
            VALUES 
                (?, ?, ?, ?, ?, 'paid', 'pending')
        ");
        $ins->bind_param('iiidd', $buyer_id, $listing_id, $qty, $total, $platform_fee);

        if ($ins->execute()) {
            $order_id = $conn->insert_id;
            redirect("/payment-success.php?order_id=$order_id");
        } else {
            $error = 'Payment could not be processed. Please try again.';
        }
    }
}
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-lock me-2"></i>Secure Checkout</h1>
        <p>Complete your purchase securely on LocalLoop</p>
    </div>
</div>

<div class="container py-5">

    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show mb-4">
        <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- ORDER SUMMARY (Left) -->
        <div class="col-lg-5 order-lg-2">
            <div class="bg-white rounded-ll shadow-ll p-4 mb-3">
                <h5 class="mb-4" style="font-family:'Poppins',sans-serif;border-bottom:2px solid var(--primary-lt);padding-bottom:12px;">
                    <i class="bi bi-bag me-2 text-primary-ll"></i>Order Summary
                </h5>

                <!-- Item -->
                <div class="d-flex gap-3 mb-4">
                    <?php if ($listing['image']): ?>
                        <img src="<?= htmlspecialchars($listing['image']) ?>"
                             style="width:72px;height:72px;object-fit:cover;border-radius:10px;flex-shrink:0;">
                    <?php else: ?>
                        <div style="width:72px;height:72px;background:var(--primary-lt);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:1.8rem;">🏷️</div>
                    <?php endif; ?>
                    <div>
                        <div style="font-weight:600;font-size:14px;"><?= htmlspecialchars($listing['title']) ?></div>
                        <div style="font-size:12px;color:var(--muted);">
                            Sold by: <?= htmlspecialchars($listing['seller_name']) ?>
                        </div>
                        <div style="font-size:12px;color:var(--muted);">
                            Category: <?= htmlspecialchars($listing['category_name']) ?>
                        </div>
                    </div>
                </div>

                <!-- Price breakdown -->
                <div class="border-top pt-3">
                    <div class="d-flex justify-content-between mb-2" style="font-size:14px;">
                        <span class="text-muted">Item Price</span>
                        <span><?= formatPrice($item_price) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2" style="font-size:14px;">
                        <span class="text-muted">
                            Platform Fee (2%)
                            <i class="bi bi-info-circle ms-1" 
                               title="LocalLoop charges a 2% fee to maintain platform operations and keep seller registration free."
                               data-bs-toggle="tooltip"></i>
                        </span>
                        <span class="text-warning"><?= formatPrice($platform_fee) ?></span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2 mt-2">
                        <strong style="font-size:15px;">Total</strong>
                        <strong style="font-size:1.2rem;color:var(--primary);"><?= formatPrice($total) ?></strong>
                    </div>
                </div>
            </div>

            <!-- Trust badges -->
            <div class="bg-white rounded-ll shadow-ll p-3">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <i class="bi bi-shield-check text-success fs-5"></i>
                    <span style="font-size:13px;font-weight:600;">Secured by LocalLoop</span>
                </div>
                <p style="font-size:12px;color:var(--muted);margin:0;">
                    Your payment details are encrypted. LocalLoop supports local South African payment 
                    gateways including <strong>PayFast</strong> and <strong>SnapScan</strong> for 
                    production transactions.
                </p>
            </div>
        </div>

        <!-- PAYMENT FORM (Right) -->
        <div class="col-lg-7 order-lg-1">
            <div class="bg-white rounded-ll shadow-ll p-4">
                <h5 class="mb-4" style="font-family:'Poppins',sans-serif;border-bottom:2px solid var(--primary-lt);padding-bottom:12px;">
                    <i class="bi bi-credit-card me-2 text-primary-ll"></i>Payment Details
                </h5>

                <!-- Payment method tabs -->
                <div class="d-flex gap-2 mb-4 flex-wrap">
                    <div class="border rounded p-2 px-3 d-flex align-items-center gap-2" 
                         style="border-color:var(--primary) !important;background:var(--primary-lt);cursor:pointer;">
                        <i class="bi bi-credit-card text-primary-ll"></i>
                        <span style="font-size:13px;font-weight:600;color:var(--primary);">Card</span>
                    </div>
                    <div class="border rounded p-2 px-3 d-flex align-items-center gap-2 text-muted"
                         style="cursor:default;opacity:.5;" title="Coming soon">
                        <i class="bi bi-phone"></i>
                        <span style="font-size:13px;">SnapScan</span>
                    </div>
                    <div class="border rounded p-2 px-3 d-flex align-items-center gap-2 text-muted"
                         style="cursor:default;opacity:.5;" title="Coming soon">
                        <i class="bi bi-bank"></i>
                        <span style="font-size:13px;">PayFast</span>
                    </div>
                </div>

                <form method="POST" id="payment-form">
                    <!-- Card Number -->
                    <div class="mb-3">
                        <label class="form-label">Card Number</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-credit-card"></i></span>
                            <input type="text" name="card_number" id="card_number"
                                   class="form-control" placeholder="1234 5678 9012 3456"
                                   maxlength="19" autocomplete="cc-number" required>
                        </div>
                    </div>

                    <!-- Expiry + CVV -->
                    <div class="row g-3 mb-3">
                        <div class="col-6">
                            <label class="form-label">Expiry Date</label>
                            <input type="text" name="expiry" id="expiry"
                                   class="form-control" placeholder="MM / YY"
                                   maxlength="7" autocomplete="cc-exp" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">CVV</label>
                            <div class="input-group">
                                <input type="text" name="cvv"
                                       class="form-control" placeholder="123"
                                       maxlength="3" autocomplete="cc-csc" required>
                                <span class="input-group-text" 
                                      title="3-digit code on the back of your card">
                                    <i class="bi bi-question-circle"></i>
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Cardholder Name -->
                    <div class="mb-4">
                        <label class="form-label">Cardholder Name</label>
                        <input type="text" name="card_name"
                               class="form-control" placeholder="Full name as on card"
                               autocomplete="cc-name" required>
                    </div>

                    <!-- Pay button -->
                    <button type="submit" class="btn btn-primary-ll w-100 btn-lg" id="pay-btn">
                        <i class="bi bi-lock me-2"></i>Pay <?= formatPrice($total) ?> Now
                    </button>

                    <!-- Processing state (hidden initially) -->
                    <div id="processing" class="text-center py-3 d-none">
                        <div class="spinner-border text-primary-ll mb-2" role="status"></div>
                        <div style="font-weight:600;">Processing your payment...</div>
                        <small class="text-muted">Please do not close this page</small>
                    </div>

                    <p class="text-center mt-3 mb-0" style="font-size:12px;color:var(--muted);">
                        <i class="bi bi-lock-fill me-1"></i>
                        256-bit SSL encrypted · PCI DSS compliant simulation
                    </p>
                </form>
            </div>

            <div class="mt-3 text-center">
                <a href="/listing.php?id=<?= $listing_id ?>" style="font-size:13px;color:var(--muted);">
                    <i class="bi bi-arrow-left me-1"></i>Back to listing
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Card number formatting (adds spaces every 4 digits)
document.getElementById('card_number').addEventListener('input', function() {
    let val = this.value.replace(/\D/g, '').substring(0, 16);
    this.value = val.replace(/(.{4})/g, '$1 ').trim();
});

// Expiry formatting (adds slash after MM)
document.getElementById('expiry').addEventListener('input', function() {
    let val = this.value.replace(/\D/g, '').substring(0, 4);
    if (val.length > 2) val = val.substring(0, 2) + ' / ' + val.substring(2);
    this.value = val;
});

// Show processing state on submit
document.getElementById('payment-form').addEventListener('submit', function() {
    document.getElementById('pay-btn').classList.add('d-none');
    document.getElementById('processing').classList.remove('d-none');
});

// Enable Bootstrap tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltips = document.querySelectorAll('[data-bs-toggle="tooltip"], [title]');
    tooltips.forEach(el => new bootstrap.Tooltip(el));
});
</script>

<?php require_once 'includes/footer.php'; ?>