<?php
// Start session only if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'meals_db');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Make connection globally available
$GLOBALS['conn'] = $conn;

// PDO Connection for compatibility
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec("SET NAMES utf8mb4");
} catch(PDOException $e) {
    die("PDO Connection failed: " . $e->getMessage());
}

// Function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Check if user is logged in and active
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'active';
}

// Check if user is admin and active
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin' && isset($_SESSION['user_status']) && $_SESSION['user_status'] === 'active';
}

// Check if user account is still active (for existing sessions)
function validateUserStatus() {
    if (isset($_SESSION['user_id']) && isset($_SESSION['user_status'])) {
        if ($_SESSION['user_status'] !== 'active') {
            // Clear session and redirect to login
            session_unset();
            session_destroy();
            $_SESSION['error'] = 'Your account has been deactivated. Please contact an administrator.';
            redirect('../auth/login.php');
        }
    }
}

// Check if user is logged in, if not redirect to login
function requireLogin() {
    if (!isLoggedIn()) {
        if (isset($_SESSION['user_id']) && !isset($_SESSION['user_status'])) {
            // User session exists but status not set (legacy session)
            $_SESSION['error'] = 'Session expired. Please log in again.';
        } else if (isset($_SESSION['user_id']) && $_SESSION['user_status'] !== 'active') {
            // User is inactive
            $_SESSION['error'] = 'Your account has been deactivated. Please contact an administrator.';
        } else {
            // User not logged in
            $_SESSION['error'] = 'Please log in to access this page.';
        }
        redirect('login.php');
    }
}

// Check if user is admin, if not redirect to home
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        $_SESSION['error'] = 'You do not have permission to access this page.';
        redirect('index.php');
    }
}
