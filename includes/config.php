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
    // For AJAX requests, don't die - let the calling script handle the error
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        // This is an AJAX request - throw the exception to be caught by the calling script
        throw $e;
    }

    // Log the error (you might want to log to a file in production)
    error_log($e->getMessage());

    // Display a user-friendly error message for regular requests
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

// M-Pesa API Configuration
$mpesa_config = [
    'env' => 'sandbox', // Use 'production' for live
    'sandbox' => [
        'consumer_key' => 'GeksgEW3yegRLr4xHZRr0FdwUfDMKWRIQZKP4UdAEzA6FMUl',
        'consumer_secret' => 'Gjm5irsF0aMPLVbMGN7WsP3MxUbWWBWowlJs3KF06WKM55DDAmgKKigfXr0Tgrke',
        'shortcode' => '174379', // Standard M-Pesa test shortcode
        'passkey' => 'bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919', // Standard test passkey
        'base_url' => 'https://sandbox.safaricom.co.ke',
        'initiator_name' => 'testapi', // For B2C/B2B APIs
        'initiator_password' => 'Safaricom999!*!', // For B2C/B2B APIs
        'test_msisdn' => '254708374149' // Test phone number (Safaricom test number)
    ],
    'production' => [
        'consumer_key' => 'YOUR_PRODUCTION_CONSUMER_KEY',
        'consumer_secret' => 'YOUR_PRODUCTION_CONSUMER_SECRET',
        'shortcode' => 'YOUR_PRODUCTION_SHORTCODE',
        'passkey' => 'YOUR_PRODUCTION_PASSKEY',
        'base_url' => 'https://api.safaricom.co.ke',
        'initiator_name' => 'YOUR_INITIATOR_NAME',
        'initiator_password' => 'YOUR_INITIATOR_PASSWORD',
        'test_msisdn' => 'YOUR_TEST_MSISDN'
    ]
];

// Set current M-Pesa configuration based on environment
$current_mpesa = $mpesa_config['sandbox']; // Default to sandbox
if (isset($mpesa_config['env']) && $mpesa_config['env'] === 'production') {
    $current_mpesa = $mpesa_config['production'];
}

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Africa/Nairobi');

// Airtel Money API Configuration
$airtel_config = [
    'env' => 'sandbox', // Use 'production' for live
    'sandbox' => [
        'consumer_key' => 'YOUR_SANDBOX_CONSUMER_KEY',
        'consumer_secret' => 'YOUR_SANDBOX_CONSUMER_SECRET',
        'shortcode' => 'YOUR_SANDBOX_SHORTCODE',
        'passkey' => 'YOUR_SANDBOX_PASSKEY',
        'base_url' => 'https://openapi-sandbox.airtel.africa',
    ],
    'production' => [
        'consumer_key' => 'YOUR_PRODUCTION_CONSUMER_KEY',
        'consumer_secret' => 'YOUR_PRODUCTION_CONSUMER_SECRET',
        'shortcode' => 'YOUR_PRODUCTION_SHORTCODE',
        'passkey' => 'YOUR_PRODUCTION_PASSKEY',
        'base_url' => 'https://openapi.airtel.africa',
    ]
];

// Set current Airtel configuration based on environment
$current_airtel = $airtel_config['sandbox']; // Default to sandbox
if (isset($airtel_config['env']) && $airtel_config['env'] === 'production') {
    $current_airtel = $airtel_config['production'];
}

// Check if M-Pesa credentials are properly configured
function isMpesaConfigured() {
    global $current_mpesa;
    return !empty($current_mpesa['consumer_key']) &&
           !empty($current_mpesa['consumer_secret']) &&
           !empty($current_mpesa['shortcode']) &&
           !empty($current_mpesa['passkey']) &&
           !preg_match('/YOUR_/', $current_mpesa['consumer_key']) &&
           !preg_match('/YOUR_/', $current_mpesa['consumer_secret']) &&
           !preg_match('/YOUR_/', $current_mpesa['shortcode']) &&
           !preg_match('/YOUR_/', $current_mpesa['passkey']);
}
