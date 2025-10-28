<?php
// includes/subscribe.php - Handle newsletter subscription

require_once 'config.php';

// Ensure session is started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Initialize PDO connection
try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=$db_charset", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    if (isset($_POST['ajax'])) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    } else {
        die('Database connection failed');
    }
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    if (isset($_POST['ajax'])) {
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    } else {
        die('Method not allowed');
    }
    exit();
}

// Handle AJAX requests vs regular form submissions
$isAjax = isset($_POST['ajax']);

try {
    $email = trim($_POST['email'] ?? '');

    // Validate email
    if (empty($email)) {
        throw new Exception('Email address is required');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Please enter a valid email address');
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id, is_active FROM newsletter_subscriptions WHERE email = ?");
    $stmt->execute([$email]);
    $existing = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($existing) {
        if ($existing['is_active']) {
            throw new Exception('You are already subscribed to our newsletter');
        } else {
            // Reactivate subscription
            $stmt = $pdo->prepare("UPDATE newsletter_subscriptions SET is_active = TRUE, unsubscribed_at = NULL, updated_at = CURRENT_TIMESTAMP WHERE email = ?");
            $stmt->execute([$email]);
            $message = 'Welcome back! Your subscription has been reactivated.';
        }
    } else {
        // Insert new subscription
        $stmt = $pdo->prepare("INSERT INTO newsletter_subscriptions (email, subscription_date, is_active) VALUES (?, CURDATE(), TRUE)");
        $stmt->execute([$email]);
        $message = 'Thank you for subscribing! You will receive our latest updates and offers.';
    }

    // Success response
    if ($isAjax) {
        echo json_encode(['success' => true, 'message' => $message]);
    } else {
        // Redirect back with success message
        $_SESSION['newsletter_message'] = ['type' => 'success', 'text' => $message];
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    }

} catch (Exception $e) {
    $error = $e->getMessage();

    if ($isAjax) {
        echo json_encode(['success' => false, 'message' => $error]);
    } else {
        $_SESSION['newsletter_message'] = ['type' => 'error', 'text' => $error];
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/'));
    }
}
?>
