<?php
$pageTitle = 'Edit Listing';
require_once 'includes/header.php';
require_once 'config/cloudinary.php';
requireLogin();
require_once 'includes/navbar.php';

$id  = intval($_GET['id'] ?? 0);
if (!$id) redirect('/my-listings.php');

// Fetch listing — ensure it belongs to the logged-in user
$stmt = $conn->prepare("SELECT * FROM listings WHERE listing_id = ? AND seller_id = ?");
$stmt->bind_param('ii', $id, $_SESSION['user_id']);
$stmt->execute();
$listing = $stmt->get_result()->fetch_assoc();

if (!$listing) {
    redirect('/my-listings.php');
}

$cats  = $conn->query("SELECT * FROM categories ORDER BY name");
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitise($conn, $_POST['title']       ?? '');
    $description = sanitise($conn, $_POST['description'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $status      = sanitise($conn, $_POST['status'] ?? 'active');

    if (empty($title) || empty($description) || $price <= 0 || $category_id === 0) {
        $error = 'Please fill in all required fields.';
    } else {
        $image_url = $listing['image']; // keep existing image by default

        // Handle new image upload
        if (!empty($_FILES['image']['name'])) {
            $img_err = validateImage($_FILES['image']);
            if ($img_err) {
                $error = $img_err;
            } else {
                $new_url = uploadToCloudinary($_FILES['image']['tmp_name'], $_FILES['image']['name']);
                if (!$new_url) {
                    $error = 'Image upload to Cloudinary failed. Listing saved with old image.';
                } else {
                    $image_url = $new_url;
                }
            }
        }

        if (empty($error)) {
            $upd = $conn->prepare(
                "UPDATE listings SET title=?, description=?, price=?, category_id=?, image=?, status=?
                 WHERE listing_id=? AND seller_id=?"
            );
            $upd->bind_param('ssdissii',
                $title, $description, $price, $category_id, $image_url, $status, $id, $_SESSION['user_id']
            );
            if ($upd->execute()) {
                redirect("/listing.php?id=$id&updated=1");
            } else {
                $error = 'Could not update listing.';
            }
        }
    }
}
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-pencil me-2"></i>Edit Listing</h1>
        <p>Update your item details</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="form-card">
                <h4 class="mb-4" style="font-family:'Poppins',sans-serif;">Edit Listing</h4>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Item Title *</label>
                        <input type="text" name="title" class="form-control"
                               value="<?= htmlspecialchars($listing['title']) ?>" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Category *</label>
                            <select name="category_id" class="form-select" required>
                                <?php while ($cat = $cats->fetch_assoc()): ?>
                                <option value="<?= $cat['category_id'] ?>"
                                    <?= $cat['category_id'] == $listing['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Price (R) *</label>
                            <div class="input-group">
                                <span class="input-group-text">R</span>
                                <input type="number" name="price" class="form-control"
                                       step="0.01" value="<?= $listing['price'] ?>" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="active"  <?= $listing['status']=='active'  ? 'selected':'' ?>>Active</option>
                                <option value="sold"    <?= $listing['status']=='sold'    ? 'selected':'' ?>>Sold</option>
                                <option value="removed" <?= $listing['status']=='removed' ? 'selected':'' ?>>Removed</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description *</label>
                        <textarea name="description" class="form-control" rows="5" required><?= htmlspecialchars($listing['description']) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Replace Image <span class="text-muted">(leave blank to keep current)</span></label>
                        <?php if ($listing['image']): ?>
                        <div class="mb-2">
                            <img src="<?= htmlspecialchars($listing['image']) ?>"
                                 style="height:120px;border-radius:8px;object-fit:cover;" alt="Current image">
                            <div class="form-text">Current image from Cloudinary</div>
                        </div>
                        <?php endif; ?>
                        <input type="file" name="image" id="listing_image" class="form-control"
                               accept="image/jpeg,image/png,image/webp,image/gif">
                        <img id="image_preview" src="" alt="New preview"
                             style="max-height:150px;object-fit:cover;display:none;margin-top:10px;border-radius:8px;">
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary-ll">
                            <i class="bi bi-check-lg me-2"></i>Save Changes
                        </button>
                        <a href="/listing.php?id=<?= $id ?>" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
