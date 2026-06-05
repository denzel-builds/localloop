<?php
require_once 'includes/header.php';
requireLogin();

$id  = intval($_GET['id'] ?? 0);
if (!$id) redirect('/my-listings.php');

// Verify ownership
$stmt = $conn->prepare("SELECT * FROM listings WHERE listing_id = ? AND seller_id = ?");
$stmt->bind_param('ii', $id, $_SESSION['user_id']);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();

if (!$listing) redirect('/my-listings.php');

// Soft-delete: set status to 'removed' rather than hard DELETE
$del = $conn->prepare("UPDATE listings SET status = 'removed' WHERE listing_id = ? AND seller_id = ?");
$del->bind_param('ii', $id, $_SESSION['user_id']);
$del->execute();

redirect('/my-listings.php?deleted=1');
?>
