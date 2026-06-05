<?php
session_start();
require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: /admin/index.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitise($conn, $_POST['email']    ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare("
            SELECT a.*, r.role_name, r.permissions
            FROM admins a
            JOIN admin_roles r ON a.role_id = r.role_id
            WHERE a.email = ?
        ");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id']          = $admin['admin_id'];
            $_SESSION['admin_name']        = $admin['name'];
            $_SESSION['admin_email']       = $admin['email'];
            $_SESSION['admin_role']        = $admin['role_name'];
            $_SESSION['admin_permissions'] = $admin['permissions'];
            header('Location: /admin/index.php');
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login — LocalLoop</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="/assets/css/style.css" rel="stylesheet">
    <style>
        body { background: var(--dark); min-height: 100vh; display: flex; align-items: center; justify-content: center; }
    </style>
</head>
<body>
<div style="width:100%;max-width:400px;padding:20px;">
    <div class="text-center mb-4">
        <div style="font-family:'Poppins',sans-serif;font-size:1.8rem;font-weight:700;color:#fff;">
            Local<span style="color:var(--accent);">Loop</span>
        </div>
        <p style="color:rgba(255,255,255,.55);margin-top:4px;">Admin Panel</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-danger"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
    <?php endif; ?>

    <div class="form-card">
        <h5 class="mb-4 text-center">Admin Login</h5>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control"
                       placeholder="admin@localloop.co.za"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>
            <div class="mb-4">
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control"
                       placeholder="Admin password" required>
            </div>
            <button type="submit" class="btn btn-primary-ll w-100">
                <i class="bi bi-shield-lock me-2"></i>Log In to Admin Panel
            </button>
        </form>
        <p class="text-center mt-3 mb-0" style="font-size:13px;">
            <a href="/index.php">← Back to LocalLoop</a>
        </p>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>