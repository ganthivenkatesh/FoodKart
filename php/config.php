<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'foodkart');

// Create database connection
function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Base URL
define('BASE_URL', 'http://localhost/WT-Project/FoodKart/');

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login_signup.php');
        exit();
    }
}

// Redirect based on role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Location: ' . BASE_URL . 'index.php');
        exit();
    }
}

// Sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Get dashboard URL based on role
function getDashboardUrl() {
    if (!isLoggedIn()) {
        return BASE_URL . 'login_signup.php';
    }
    
    switch ($_SESSION['role']) {
        case 'admin':
            return BASE_URL . 'dashboards/admin_dashboard.php';
        case 'restaurant_owner':
            return BASE_URL . 'dashboards/restaurant_dashboard.php';
        case 'delivery_agent':
            return BASE_URL . 'dashboards/delivery_dashboard.php';
        case 'customer':
        default:
            return BASE_URL . 'pages/user_home.php';
    }
}
?>
