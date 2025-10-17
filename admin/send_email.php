<?php
// --- START DEBUG BLOCK ---
// ENABLE ALL PHP ERROR REPORTING TO CATCH THE FATAL ERROR CAUSING THE 500 STATUS
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END DEBUG BLOCK ---

// Function to handle critical JSON errors early
function criticalError(string $message, int $code = 500) {
    // Send 500 status but include the helpful message
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $message]);
    exit();
}

// 1. Check and include config.php
$configPath = '../includes/config.php';
if (!file_exists($configPath)) {
    criticalError("Critical File Error: config.php not found at expected path: {$configPath}", 500);
}
require_once $configPath;

// 2. Include PHPMailer autoloader (vendor/autoload.php)
$autoloadPath = '../vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    criticalError("Critical File Error: PHPMailer autoloader not found. Run 'composer install' and check path: {$autoloadPath}", 500);
}
require_once $autoloadPath;

// === CRITICAL CHECK 1: Ensure $pdo is initialized after config.php is included ===
if (!isset($pdo) || !($pdo instanceof PDO)) {
    criticalError('Critical Error: Database connection object ($pdo) is missing or invalid. Check config.php.', 500);
}
// ==============================================================================

// 3. Check and include EmailService.php
$emailServicePath = '../includes/EmailService.php';
if (!file_exists($emailServicePath)) {
    criticalError("Critical File Error: EmailService.php not found at expected path: {$emailServicePath}", 500);
}
require_once $emailServicePath;


header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    criticalError('Method not allowed', 405);
}

$action = $_POST['action'] ?? '';
$order_id = (int)($_POST['order_id'] ?? 0); 
$customer_email = trim($_POST['customer_email'] ?? '');

if (!$action || !$order_id || !$customer_email) {
    criticalError('Missing required parameters (action, order_id, or customer_email)', 400);
}

// Verify order exists and get order details
try {
    $stmt = $pdo->prepare("
        SELECT o.*, u.name as customer_name, u.email as customer_email, u.phone as customer_phone,
               GROUP_CONCAT(oi.item_name SEPARATOR ', ') as item_names,
               GROUP_CONCAT(oi.quantity SEPARATOR ', ') as item_quantities,
               GROUP_CONCAT(oi.price SEPARATOR ', ') as item_prices,
               GROUP_CONCAT(oi.total SEPARATOR ', ') as item_totals
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        LEFT JOIN order_items oi ON o.id = oi.order_id
        WHERE o.id = :order_id
        GROUP BY o.id
    ");

    $stmt->execute([':order_id' => $order_id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    // Calculate subtotal for safety
    if ($order) {
        $itemTotalsString = $order['item_totals'] ?? '';
        $totalItems = $itemTotalsString ? array_map('floatval', explode(', ', $itemTotalsString)) : [];
        
        $order['subtotal'] = array_sum($totalItems);
        $order['total_amount'] = floatval($order['total_amount'] ?? 0);
        $order['delivery_fee'] = floatval($order['delivery_fee'] ?? 0);
    }


    if (!$order) {
        criticalError('Order not found', 404);
    }

} catch (PDOException $e) {
    error_log("Error fetching order for email: " . $e->getMessage());
    criticalError('Database error during order fetch: ' . $e->getMessage(), 500);
}

// Initialize email service
try {
    $emailService = new EmailService();
} catch (Exception $e) {
    error_log("EmailService initialization error: " . $e->getMessage());
    criticalError('Email service not available or failed initialization. Details: ' . $e->getMessage(), 500);
}

$success = false;
$action_name = ucfirst(str_replace('_', ' ', $action));

try {
    switch ($action) {
        case 'send_invoice':
            if (!method_exists($emailService, 'sendInvoiceEmail')) {
                throw new Exception("Method sendInvoiceEmail not found in EmailService.");
            }
            $success = $emailService->sendInvoiceEmail($customer_email, $order);
            break;

        case 'send_receipt':
            if (!method_exists($emailService, 'sendReceiptEmail')) {
                throw new Exception("Method sendReceiptEmail not found in EmailService.");
            }
            $success = $emailService->sendReceiptEmail($customer_email, $order);
            break;

        default:
            criticalError('Invalid action specified.', 400);
    }

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => "{$action_name} sent successfully to {$customer_email}"
        ]);
    } else {
        // The email function returned false, but no exception was thrown.
        criticalError("Failed to send {$action_name}. Check PHP mail configuration or PHPMailer logs within EmailService.", 500);
    }

} catch (Exception $e) {
    error_log("Email sending error in endpoint: " . $e->getMessage());
    criticalError('An unexpected error occurred during email delivery: ' . $e->getMessage(), 500);
}
?>
