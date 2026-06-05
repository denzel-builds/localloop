<?php
$pageTitle = 'Manage Categories';
require_once __DIR__ . '/includes/header.php';
requireAdminLogin();
require_once __DIR__ . '/includes/navbar.php';

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // CREATE
    if ($action === 'create') {
        $name = sanitise($conn, $_POST['name'] ?? '');
        $desc = sanitise($conn, $_POST['description'] ?? '');
        if (empty($name)) {
            $err = 'Category name is required.';
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            $stmt->bind_param('ss', $name, $desc);
            $stmt->execute() ? $msg = "Category \"$name\" created." : $err = 'Could not create category.';
        }
    }

    // UPDATE
    if ($action === 'update') {
        $cid  = intval($_POST['category_id']);
        $name = sanitise($conn, $_POST['name'] ?? '');
        $desc = sanitise($conn, $_POST['description'] ?? '');
        if ($cid && $name) {
            $stmt = $conn->prepare("UPDATE categories SET name=?, description=? WHERE category_id=?");
            $stmt->bind_param('ssi', $name, $desc, $cid);
            $stmt->execute() ? $msg = 'Category updated.' : $err = 'Could not update.';
        }
    }

    // DELETE
    if ($action === 'delete' && hasPermission('all')) {
        $cid = intval($_POST['category_id']);
        // Check if category has listings
        $check = $conn->query("SELECT COUNT(*) AS c FROM listings WHERE category_id = $cid")->fetch_assoc()['c'];
        if ($check > 0) {
            $err = "Cannot delete — $check listing(s) use this category. Move them first.";
        } else {
            $conn->query("DELETE FROM categories WHERE category_id = $cid");
            $msg = 'Category deleted.';
        }
    }
}

$categories = $conn->query("
    SELECT c.*, COUNT(l.listing_id) AS listing_count
    FROM categories c
    LEFT JOIN listings l ON c.category_id = l.category_id AND l.status != 'removed'
    GROUP BY c.category_id
    ORDER BY c.name
");
?>

<?php if ($msg): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($err): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i><?= $err ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="row g-4">
    <!-- CATEGORY LIST -->
    <div class="col-lg-8">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5><i class="bi bi-tags me-2"></i>All Categories</h5>
            </div>
            <table class="table table-ll mb-0">
                <thead>
                    <tr><th>#</th><th>Name</th><th>Description</th><th>Listings</th><th>Actions</th></tr>
                </thead>
                <tbody>
                <?php while ($cat = $categories->fetch_assoc()): ?>
                <tr>
                    <td style="color:var(--muted);font-size:12px;"><?= $cat['category_id'] ?></td>
                    <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                    <td style="font-size:13px;color:var(--muted);">
                        <?= $cat['description'] ? htmlspecialchars(substr($cat['description'], 0, 50)) . '...' : '—' ?>
                    </td>
                    <td>
                        <span class="badge" style="background:var(--primary-lt);color:var(--primary);">
                            <?= $cat['listing_count'] ?>
                        </span>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary me-1"
                                data-bs-toggle="modal"
                                data-bs-target="#editModal"
                                data-id="<?= $cat['category_id'] ?>"
                                data-name="<?= htmlspecialchars($cat['name'], ENT_QUOTES) ?>"
                                data-desc="<?= htmlspecialchars($cat['description'] ?? '', ENT_QUOTES) ?>">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <?php if (hasPermission('all')): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action"      value="delete">
                            <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                            <button class="btn btn-sm btn-outline-danger confirm-delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- ADD CATEGORY FORM -->
    <div class="col-lg-4">
        <div class="admin-card">
            <div class="admin-card-header">
                <h5><i class="bi bi-plus-circle me-2"></i>Add Category</h5>
            </div>
            <div class="p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" class="form-control"
                               placeholder="e.g. Garden &amp; Outdoor" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="3"
                                  placeholder="Brief description of this category..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary-ll w-100">
                        <i class="bi bi-plus me-1"></i>Create Category
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- EDIT MODAL -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action"      value="update">
                <input type="hidden" name="category_id" id="edit_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category Name *</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" id="edit_desc" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-ll">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Populate edit modal with row data
document.getElementById('editModal').addEventListener('show.bs.modal', function(e) {
    const btn = e.relatedTarget;
    document.getElementById('edit_id').value   = btn.dataset.id;
    document.getElementById('edit_name').value = btn.dataset.name;
    document.getElementById('edit_desc').value = btn.dataset.desc;
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
