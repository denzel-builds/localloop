<?php
$pageTitle = 'Reset Password';
require_once 'includes/header.php';
require_once 'includes/navbar.php';

if (isLoggedIn()) redirect('/dashboard.php');

$token = sanitise($conn, $_GET['token'] ?? '');
$error   = '';
$success = '';

// Validate the token immediately on page load
if (empty($token)) {
    redirect('/forgot-password.php');
}

$stmt = $conn->prepare("
    SELECT pr.*, u.user_id, u.name AS user_name
    FROM password_resets pr
    JOIN users u ON pr.email = u.email
    WHERE pr.token = ?
      AND pr.used = 0
      AND pr.expires_at > NOW()
");
$stmt->bind_param('s', $token);
$stmt->execute();
$reset = $stmt->get_result()->fetch_assoc();

$token_valid = ($reset !== null);

// Handle new password submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $token_valid) {
    $password = $_POST['password']         ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';

    if (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $email  = $reset['email'];

        // Update the user's password
        $upd = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $upd->bind_param('ss', $hashed, $email);

        if ($upd->execute()) {
            // Mark token as used so it cannot be reused
            $mark = $conn->prepare("UPDATE password_resets SET used = 1 WHERE token = ?");
            $mark->bind_param('s', $token);
            $mark->execute();

            $success = true;
        } else {
            $error = 'Could not update your password. Please try again.';
        }
    }
}
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-key me-2"></i>Set New Password</h1>
        <p>Choose a strong password for your LocalLoop account</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">

            <?php if ($success): ?>
            <!-- SUCCESS STATE -->
            <div class="text-center">
                <div style="font-size:3.5rem;margin-bottom:16px;">✅</div>
                <h3 style="font-family:'Poppins',sans-serif;font-weight:700;color:var(--primary);">
                    Password Updated!
                </h3>
                <p class="text-muted">
                    Your password has been changed successfully. 
                    You can now log in with your new password.
                </p>
                <a href="/login.php" class="btn btn-primary-ll mt-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
                </a>
            </div>

            <?php elseif (!$token_valid): ?>
            <!-- INVALID OR EXPIRED TOKEN -->
            <div class="text-center">
                <div style="font-size:3.5rem;margin-bottom:16px;">⏰</div>
                <h3 style="font-family:'Poppins',sans-serif;font-weight:700;color:#e55353;">
                    Link Expired or Invalid
                </h3>
                <p class="text-muted">
                    This password reset link has expired or has already been used. 
                    Reset links are only valid for 1 hour.
                </p>
                <a href="/forgot-password.php" class="btn btn-primary-ll mt-2">
                    <i class="bi bi-arrow-repeat me-2"></i>Request a New Link
                </a>
            </div>

            <?php else: ?>
            <!-- RESET FORM -->
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="text-center mb-4">
                <div style="font-size:3rem;margin-bottom:12px;">🔑</div>
                <h3 style="font-family:'Poppins',sans-serif;font-weight:700;">New Password</h3>
                <p class="text-muted" style="font-size:14px;">
                    Setting password for <strong><?= htmlspecialchars($reset['email']) ?></strong>
                </p>
            </div>

            <div class="form-card">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-control"
                               placeholder="At least 6 characters"
                               id="new_pass" required>
                        <div id="strength-bar" class="mt-2" style="height:4px;border-radius:4px;background:#eee;transition:all .3s;">
                            <div id="strength-fill" style="height:100%;border-radius:4px;width:0%;transition:width .3s;"></div>
                        </div>
                        <div id="strength-label" class="form-text"></div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control"
                               placeholder="Repeat your new password" required>
                    </div>
                    <button type="submit" class="btn btn-primary-ll w-100">
                        <i class="bi bi-check-lg me-2"></i>Update Password
                    </button>
                </form>
            </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>
// Password strength indicator
document.getElementById('new_pass').addEventListener('input', function() {
    const val    = this.value;
    const fill   = document.getElementById('strength-fill');
    const label  = document.getElementById('strength-label');
    let score    = 0;
    let text     = '';
    let colour   = '';

    if (val.length >= 6)                         score++;
    if (val.length >= 10)                        score++;
    if (/[A-Z]/.test(val))                       score++;
    if (/[0-9]/.test(val))                       score++;
    if (/[^A-Za-z0-9]/.test(val))               score++;

    if      (score <= 1) { text = 'Weak';   colour = '#e55353'; }
    else if (score <= 3) { text = 'Fair';   colour = '#F4A623'; }
    else if (score <= 4) { text = 'Good';   colour = '#1a73e8'; }
    else                  { text = 'Strong'; colour = '#1A7A5C'; }

    fill.style.width      = (score * 20) + '%';
    fill.style.background = colour;
    label.textContent     = val.length > 0 ? 'Strength: ' + text : '';
    label.style.color     = colour;
});
</script>

<?php require_once 'includes/footer.php'; ?>