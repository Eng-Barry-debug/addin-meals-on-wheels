<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'meals_db';
$db_user = 'root';     // Update with your database username
$db_pass = '';         // Update with your database password
$db_charset = 'utf8mb4';

// MySQLi Connection
try {
    $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Check connection
    if ($mysqli->connect_error) {
        throw new Exception("MySQLi Connection failed: " . $mysqli->connect_error);
    }
    
    // Set charset to ensure proper encoding
    $mysqli->set_charset($db_charset);
    
    // PDO Connection (kept for backward compatibility)
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=$db_charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    
    try {
        $pdo = new PDO($dsn, $db_user, $db_pass, $options);
    } catch (PDOException $e) {
        throw new Exception("PDO Connection failed: " . $e->getMessage());
    }
    
} catch (Exception $e) {
    // Log the error (you might want to log to a file in production)
    error_log($e->getMessage());
    
    // Display a user-friendly error message
    die("Database connection failed. Please try again later.");
}

// Helper function to close database connections
function closeConnections() {
    global $mysqli, $pdo;
    
    if (isset($mysqli) && $mysqli instanceof mysqli) {
        $mysqli->close();
    }
    
    $pdo = null;
}

// Register shutdown function to close connections
register_shutdown_function('closeConnections');
?>
