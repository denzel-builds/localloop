<?php
$pageTitle = 'Forgot Password';
require_once 'includes/header.php';
require_once 'config/mailer.php';
require_once 'includes/navbar.php';

// Redirect if already logged in
if (isLoggedIn()) redirect('/dashboard.php');

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitise($conn, $_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Check if email belongs to an active account
        $stmt = $conn->prepare(
            "SELECT user_id, name FROM users WHERE email = ? AND status = 'active'"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            // Remove any old tokens for this email
            $del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $del->bind_param('s', $email);
            $del->execute();

            // Generate a secure random 64-character token
            $token   = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            // Save token to database
            $ins = $conn->prepare(
                "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)"
            );
            $ins->bind_param('sss', $email, $token, $expires);
            $ins->execute();

            // Build the reset link
            $reset_link = SITE_URL . '/reset-password.php?token=' . $token;

            // Send the email
            $sent = sendResetEmail($email, $user['name'], $reset_link);

            if ($sent) {
                $success = 'A password reset link has been sent to <strong>'
                         . htmlspecialchars($email)
                         . '</strong>. Please check your inbox and spam folder. '
                         . 'The link expires in 1 hour.';
            } else {
                $error = 'We could not send the reset email right now. '
                       . 'Please check your mail configuration or try again later.';
            }
        } else {
            // Do not reveal whether the email exists — security best practice
            $success = 'If an account is registered with that email, '
                     . 'a reset link has been sent. Please check your inbox.';
        }
    }
}
?>

<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-envelope-lock me-2"></i>Forgot Password</h1>
        <p>We will send a reset link to your email address</p>
    </div>
</div>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">

            <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i><?= $success ?>
            </div>
            <div class="text-center mt-3">
                <a href="/login.php" class="btn btn-outline-ll btn">
                    <i class="bi bi-arrow-left me-1"></i>Back to Login
                </a>
            </div>

            <?php else: ?>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <div class="text-center mb-4">
                <div style="font-size:3rem;margin-bottom:12px;">🔐</div>
                <h3 style="font-family:'Poppins',sans-serif;font-weight:700;">Reset Password</h3>
                <p class="text-muted" style="font-size:14px;">
                    Enter the email address linked to your account and we will 
                    send you a reset link.
                </p>
            </div>

            <div class="form-card">
                <form method="POST" action="">
                    <div class="mb-4">
                        <label class="form-label">Your Email Address</label>
                        <input type="email" name="email" class="form-control"
                               placeholder="you@example.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               required autofocus>
                    </div>
                    <button type="submit" class="btn btn-primary-ll w-100">
                        <i class="bi bi-send me-2"></i>Send Reset Link
                    </button>
                </form>
                <p class="text-center mt-3 mb-0" style="font-size:14px;">
                    Remembered it? <a href="/login.php">Log in</a>
                </p>
            </div>

            <?php endif; ?>

        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>