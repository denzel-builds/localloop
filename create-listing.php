<?php
$pageTitle = 'Post an Item';
require_once 'includes/header.php';
require_once 'config/cloudinary.php';
requireLogin();
require_once 'includes/navbar.php';

$error   = '';
$success = '';

// Load categories for dropdown
$cats = $conn->query("SELECT * FROM categories ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = sanitise($conn, $_POST['title']       ?? '');
    $description = sanitise($conn, $_POST['description'] ?? '');
    $price       = floatval($_POST['price'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $seller_id   = $_SESSION['user_id'];

    // Basic validation
    if (empty($title) || empty($description) || $price <= 0 || $category_id === 0) {
        $error = 'Please fill in all required fields and enter a valid price.';
    } else {
        $image_url = null;

        // Handle image upload to Cloudinary
        if (!empty($_FILES['image']['name'])) {
            $img_error = validateImage($_FILES['image']);
            if ($img_error) {
                $error = $img_error;
            } else {
                $image_url = uploadToCloudinary(
                    $_FILES['image']['tmp_name'],
                    $_FILES['image']['name']
                );

                // TEMPORARY DEBUG — remove this after testing
                error_log('Cloudinary result: ' . var_export($image_url, true));

                if (!$image_url) {
                    $error = 'Image upload failed. Check your Cloudinary credentials in config/cloudinary.php.';
                }
            }
        }

        // Insert listing if no errors
        if (empty($error)) {
            $stmt = $conn->prepare(
                "INSERT INTO listings (seller_id, category_id, title, description, price, image)
                 VALUES (?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param('iissds', $seller_id, $category_id, $title, $description, $price, $image_url);

            if ($stmt->execute()) {
                $new_id = $conn->insert_id;
                redirect("/listing.php?id=$new_id&posted=1");
            } else {
                $error = 'Could not save listing. Please try again.';
            }
        }
    }
}
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-plus-circle me-2"></i>Post an Item</h1>
        <p>List your goods and reach buyers in your community</p>
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
                <h4 class="mb-4" style="font-family:'Poppins',sans-serif;">Listing Details</h4>

                <form method="POST" action="" enctype="multipart/form-data">

                    <div class="mb-3">
                        <label class="form-label">Item Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control"
                               placeholder="e.g. Samsung Galaxy A32 — good condition"
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>" required>
                        <div class="form-text">Be specific — better titles get more buyers.</div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select" required>
                                <option value="">— Select category —</option>
                                <?php while ($cat = $cats->fetch_assoc()): ?>
                                <option value="<?= $cat['category_id'] ?>"
                                    <?= (isset($_POST['category_id']) && $_POST['category_id'] == $cat['category_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Price (R) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">R</span>
                                <input type="number" name="price" class="form-control price-input"
                                       placeholder="0.00" step="0.01" min="0.01"
                                       value="<?= htmlspecialchars($_POST['price'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="5"
                                  placeholder="Describe the item — condition, size, colour, pickup location..."
                                  required><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label class="form-label">Item Photo <span class="text-muted">(optional but recommended)</span></label>
                        <input type="file" name="image" id="listing_image" class="form-control"
                               accept="image/jpeg,image/png,image/webp,image/gif">
                        <div class="form-text">JPG, PNG, WebP or GIF · Max 5 MB · Uploaded securely to Cloudinary CDN</div>
                        <img id="image_preview" src="" alt="Preview"
                             class="mt-3 rounded-ll d-none"
                             style="max-height:200px;object-fit:cover;display:none !important;">
                    </div>

                    <div class="d-flex gap-3">
                        <button type="submit" class="btn btn-primary-ll">
                            <i class="bi bi-cloud-upload me-2"></i>Post Listing
                        </button>
                        <a href="/dashboard.php" class="btn btn-light">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// Show image preview
document.getElementById('listing_image').addEventListener('change', function() {
    const preview = document.getElementById('image_preview');
    if (this.files && this.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
            preview.classList.remove('d-none');
        };
        reader.readAsDataURL(this.files[0]);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
