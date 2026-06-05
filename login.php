<?php
$pageTitle = 'Login';
require_once 'includes/header.php';

if (isLoggedIn()) redirect('/dashboard.php');

$error = '';
$msg   = htmlspecialchars($_GET['msg'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = sanitise($conn, $_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $conn->prepare("SELECT user_id, name, email, password, status FROM users WHERE email = ?");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if ($user['status'] === 'suspended') {
                $error = 'Your account has been suspended. Contact support.';
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id']   = $user['user_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email']= $user['email'];
                redirect('/dashboard.php');
            } else {
                $error = 'Incorrect password.';
            }
        } else {
            $error = 'No account found with that email.';
        }
    }
}

require_once 'includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="text-center mb-4">
                <h2 style="font-family:'Poppins',sans-serif;font-weight:700;">Welcome Back</h2>
                <p class="text-muted">Log in to your LocalLoop account</p>
            </div>

            <?php if ($msg): ?>
                <div class="alert alert-warning"><?= $msg ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="form-card">
                <form method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control"
                               placeholder="you@example.com"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                    </div>
                    <div class="mb-4">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Password</label>
                            <a href="/forgot-password.php" style="font-size:13px;">Forgot password?</a>
                        </div>
                        <input type="password" name="password" class="form-control"
                               placeholder="Enter your password" required>
                    </div>
                    <button type="submit" class="btn btn-primary-ll w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Log In
                    </button>
                </form>
                <p class="text-center mt-3 mb-0" style="font-size:14px;">
                    No account? <a href="/register.php">Create one free</a>
                </p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>