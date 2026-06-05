<?php
$pageTitle = 'Payment Successful';
require_once 'includes/header.php';
requireLogin();
require_once 'includes/navbar.php';

$order_id = intval($_GET['order_id'] ?? 0);
if (!$order_id) redirect('/browse.php');

// Fetch order details — verify it belongs to this user
$stmt = $conn->prepare("
    SELECT o.*, l.title, l.image, l.listing_id,
           u.name AS seller_name
    FROM orders o
    JOIN listings l ON o.listing_id = l.listing_id
    JOIN users u ON l.seller_id = u.user_id
    WHERE o.order_id = ? AND o.buyer_id = ?
");
$stmt->bind_param('ii', $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) redirect('/my-orders.php');
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 text-center">

            <!-- Success animation -->
            <div style="width:90px;height:90px;background:#e8f5f0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 24px;font-size:2.5rem;">
                ✅
            </div>

            <h2 style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--primary);">
                Payment Successful!
            </h2>
            <p class="text-muted">
                Your order has been placed and payment confirmed. 
                The seller will be notified and will respond shortly.
            </p>

            <!-- Order details card -->
            <div class="bg-white rounded-ll shadow-ll p-4 mt-4 text-start">
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted" style="font-size:13px;">Order Reference</span>
                    <strong>#LL<?= str_pad($order['order_id'], 5, '0', STR_PAD_LEFT) ?></strong>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted" style="font-size:13px;">Item</span>
                    <span style="font-size:14px;"><?= htmlspecialchars(substr($order['title'], 0, 35)) ?>...</span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted" style="font-size:13px;">Seller</span>
                    <span style="font-size:14px;"><?= htmlspecialchars($order['seller_name']) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-1">
                    <span class="text-muted" style="font-size:13px;">Platform Fee (2%)</span>
                    <span style="font-size:14px;"><?= formatPrice($order['platform_fee']) ?></span>
                </div>
                <div class="d-flex justify-content-between border-top pt-2 mt-2">
                    <strong>Total Paid</strong>
                    <strong style="color:var(--primary);font-size:1.1rem;"><?= formatPrice($order['total_price']) ?></strong>
                </div>
                <div class="d-flex justify-content-between mt-1">
                    <span class="text-muted" style="font-size:13px;">Status</span>
                    <span class="status-badge badge-paid">Paid</span>
                </div>
            </div>

            <!-- What happens next -->
            <div class="bg-white rounded-ll shadow-ll p-4 mt-3 text-start">
                <h6 style="font-weight:700;margin-bottom:12px;">What happens next?</h6>
                <div class="d-flex gap-3 mb-2">
                    <div style="width:28px;height:28px;background:var(--primary-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;font-weight:700;color:var(--primary);">1</div>
                    <div style="font-size:13px;">The seller will review your order and approve or decline it.</div>
                </div>
                <div class="d-flex gap-3 mb-2">
                    <div style="width:28px;height:28px;background:var(--primary-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;font-weight:700;color:var(--primary);">2</div>
                    <div style="font-size:13px;">You will see the seller's response and any message on your orders page.</div>
                </div>
                <div class="d-flex gap-3">
                    <div style="width:28px;height:28px;background:var(--primary-lt);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:13px;font-weight:700;color:var(--primary);">3</div>
                    <div style="font-size:13px;">Arrange collection or delivery directly with the seller.</div>
                </div>
            </div>

            <div class="mt-4 d-flex gap-3 justify-content-center flex-wrap">
                <a href="/my-orders.php" class="btn btn-primary-ll">
                    <i class="bi bi-bag me-2"></i>View My Orders
                </a>
                <a href="/browse.php" class="btn btn-outline-ll btn">
                    Continue Shopping
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>