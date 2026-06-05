<?php
// ============================================================
// LocalLoop — Database Configuration
// Update these values with your hosting provider's credentials
// ============================================================

define('DB_HOST', 'sql301.infinityfree.com');
define('DB_USER', 'if0_41918856');   
define('DB_PASS', 'Tzt67LiUen');   
define('DB_NAME', 'if0_41918856_db1');       

define('SITE_NAME', 'LocalLoop');
define('SITE_URL', 'localloop.infinityfreeapp.com'); // Replace with your actual domain

// Create MySQLi connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:40px;text-align:center;">
            <h2>Connection Error</h2>
            <p>Could not connect to the database. Please check your config/db.php settings.</p>
            <small>' . $conn->connect_error . '</small>
         </div>');
}

// Set charset
$conn->set_charset("utf8mb4");

// Helper: sanitise input
function sanitise($conn, $data) {
    return htmlspecialchars(strip_tags($conn->real_escape_string(trim($data))));
}

// Helper: redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Helper: check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper: require login
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login.php?msg=Please+log+in+to+continue');
    }
}

// Helper: check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Helper: require admin login
function requireAdminLogin() {
    if (!isAdminLoggedIn()) {
        redirect('/admin/login.php');
    }
}

// Helper: format price in ZAR
function formatPrice($amount) {
    return 'R ' . number_format($amount, 2);
}

// Helper: get star rating HTML
function starRating($rating) {
    $html = '';
    for ($i = 1; $i <= 5; $i++) {
        $html .= $i <= $rating
            ? '<i class="bi bi-star-fill text-warning"></i>'
            : '<i class="bi bi-star text-warning"></i>';
    }
    return $html;
}
?>