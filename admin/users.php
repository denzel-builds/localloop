<?php
$pageTitle = 'Manage Users';
require_once __DIR__ . '/includes/header.php';
requireAdminLogin();
require_once __DIR__ . '/includes/navbar.php';

$msg = '';
$err = '';

// CREATE user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // SUSPEND / ACTIVATE
    if ($action === 'toggle_status') {
        $uid    = intval($_POST['user_id']);
        $status = sanitise($conn, $_POST['new_status']);
        $allowed = ['active','suspended'];
        if (in_array($status, $allowed)) {
            $conn->query("UPDATE users SET status = '$status' WHERE user_id = $uid");
            $msg = "User status updated to $status.";
        }
    }

    // DELETE user
    if ($action === 'delete_user' && hasPermission('all')) {
        $uid = intval($_POST['user_id']);
        $conn->query("DELETE FROM users WHERE user_id = $uid");
        $msg = 'User deleted.';
    }

    // ADD user
    if ($action === 'add_user' && hasPermission('all')) {
        $name  = sanitise($conn, $_POST['name'] ?? '');
        $email = sanitise($conn, $_POST['email'] ?? '');
        $pass  = $_POST['password'] ?? '';
        $role  = sanitise($conn, $_POST['role'] ?? 'both');

        if ($name && $email && strlen($pass) >= 6) {
            $hashed = password_hash($pass, PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)");
            $stmt->bind_param('ssss', $name, $email, $hashed, $role);
            $stmt->execute() ? $msg = 'User created.' : $err = 'Email may already exist.';
        } else {
            $err = 'Fill all fields and use a password of at least 6 characters.';
        }
    }
}

$search = sanitise($conn, $_GET['q'] ?? '');
$where  = $search ? "WHERE name LIKE '%$search%' OR email LIKE '%$search%'" : '';
$users  = $conn->query("SELECT * FROM users $where ORDER BY created_at DESC");
?>

<!-- MESSAGES -->
<?php if ($msg): ?>
<div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $msg ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>
<?php if ($err): ?>
<div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i><?= $err ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <form method="GET" class="d-flex gap-2">
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Search users..." value="<?= htmlspecialchars($search) ?>" style="width:220px;">
        <button class="btn btn-sm btn-primary-ll">Search</button>
        <?php if ($search): ?><a href="/admin/users.php" class="btn btn-sm btn-light">Clear</a><?php endif; ?>
    </form>
    <?php if (hasPermission('all')): ?>
    <button class="btn btn-sm btn-primary-ll" data-bs-toggle="modal" data-bs-target="#addUserModal">
        <i class="bi bi-person-plus me-1"></i>Add User
    </button>
    <?php endif; ?>
</div>

<div class="admin-card">
    <div class="admin-card-header">
        <h5><i class="bi bi-people me-2"></i>All Users (<?= $users->num_rows ?>)</h5>
    </div>
    <table class="table table-ll mb-0">
        <thead>
            <tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
        </thead>
        <tbody>
        <?php while ($u = $users->fetch_assoc()): ?>
        <tr>
            <td style="font-size:12px;color:var(--muted);"><?= $u['user_id'] ?></td>
            <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
            <td style="font-size:13px;"><?= htmlspecialchars($u['email']) ?></td>
            <td><small><?= ucfirst($u['role']) ?></small></td>
            <td>
                <span class="status-badge badge-<?= $u['status'] ?>">
                    <?= ucfirst($u['status']) ?>
                </span>
            </td>
            <td style="font-size:12px;color:var(--muted);"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="user_id"    value="<?= $u['user_id'] ?>">
                    <input type="hidden" name="action"     value="toggle_status">
                    <input type="hidden" name="new_status" value="<?= $u['status']==='active' ? 'suspended' : 'active' ?>">
                    <button class="btn btn-sm <?= $u['status']==='active' ? 'btn-outline-warning' : 'btn-outline-success' ?>"
                            title="<?= $u['status']==='active' ? 'Suspend' : 'Activate' ?>">
                        <i class="bi <?= $u['status']==='active' ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                    </button>
                </form>
                <?php if (hasPermission('all')): ?>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                    <input type="hidden" name="action"  value="delete_user">
                    <button class="btn btn-sm btn-outline-danger confirm-delete" title="Delete">
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

<!-- ADD USER MODAL -->
<?php if (hasPermission('all')): ?>
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="add_user">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="both">Buyer &amp; Seller</option>
                            <option value="buyer">Buyer only</option>
                            <option value="seller">Seller only</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-ll">Create User</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>