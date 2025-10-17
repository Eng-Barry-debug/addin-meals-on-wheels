<?php
// initiate_airtel_stk_push.php - Initiate Airtel Money STK Push

// Clear any previous output
ob_clean();

// Set content type to JSON immediately
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    require_once 'includes/config.php';
    require_once 'includes/AirtelService.php';

    // Check if Airtel Money is properly configured before proceeding
    if (!isAirtelConfigured()) {
        echo json_encode([
            'success' => false,
            'message' => 'Airtel Money payment is not available yet. Please configure your Airtel Money credentials in includes/config.php first.'
        ]);
        exit;
    }

// Get JSON input
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid JSON input']);
    exit;
}

// Validate required fields
$required_fields = ['order_id', 'phone', 'amount'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty($data[$field])) {
        echo json_encode(['success' => false, 'message' => "Missing required field: {$field}"]);
        exit;
    }
}

try {
    $airtel = new AirtelService();

    // Generate account reference for this order
    $account_reference = 'ORDER-' . $data['order_id'];

    // Initiate STK Push
    $result = $airtel->initiateSTKPush(
        $data['phone'],
        $data['amount'],
        $account_reference,
        'Addins Meals Order Payment - #' . $data['order_id']
    );

    if ($result['success']) {
        // Update the order with the checkout request ID for tracking
        try {
            $stmt = $pdo->prepare("
                UPDATE orders
                SET airtel_checkout_request_id = ?, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$result['transaction_id'], $data['order_id']]);
        } catch (PDOException $e) {
            // If column doesn't exist, add it
            if (strpos($e->getMessage(), 'Unknown column') !== false) {
                $pdo->exec("ALTER TABLE orders ADD COLUMN airtel_checkout_request_id VARCHAR(100) NULL");

                // Try updating again
                $stmt = $pdo->prepare("
                    UPDATE orders
                    SET airtel_checkout_request_id = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$result['transaction_id'], $data['order_id']]);
            } else {
                throw $e;
            }
        }

        // Generate Airtel payment reference for tracking
        $airtel_reference = 'AIRTEL-' . $data['order_id'] . '-' . time();

        // Store Airtel payment details in a separate table for tracking
        // First ensure the table exists
        try {
            $stmt = $pdo->prepare("
                INSERT INTO airtel_payments (order_id, reference, shortcode, account_number, amount, phone_number, transaction_id, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$data['order_id'], $airtel_reference, '123456', '007160', $data['amount'], $data['phone'], $result['transaction_id']]);
        } catch (PDOException $e) {
            // If table doesn't exist, create it and try again
            if (strpos($e->getMessage(), 'Base table or view not found') !== false) {
                $create_table_sql = "
                    CREATE TABLE IF NOT EXISTS airtel_payments (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        order_id INT NOT NULL,
                        reference VARCHAR(100) NOT NULL,
                        shortcode VARCHAR(20) NOT NULL,
                        account_number VARCHAR(50) NOT NULL,
                        amount DECIMAL(10,2) NOT NULL,
                        phone_number VARCHAR(20),
                        transaction_code VARCHAR(50),
                        transaction_id VARCHAR(100),
                        status ENUM('pending', 'completed', 'failed', 'cancelled') DEFAULT 'pending',
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                        INDEX idx_reference (reference),
                        INDEX idx_transaction_id (transaction_id),
                        INDEX idx_status (status),
                        INDEX idx_created_at (created_at)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ";
                $pdo->exec($create_table_sql);

                // Try inserting again
                $stmt = $pdo->prepare("
                    INSERT INTO airtel_payments (order_id, reference, shortcode, account_number, amount, phone_number, transaction_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$data['order_id'], $airtel_reference, '123456', '007160', $data['amount'], $data['phone'], $result['transaction_id']]);
            } else {
                throw $e;
            }
        }

        echo json_encode([
            'success' => true,
            'message' => 'Airtel Money STK Push initiated successfully',
            'transaction_id' => $result['transaction_id'],
            'response_code' => $result['response_code'],
            'response_description' => $result['response_description']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }

} catch (Exception $e) {
    error_log('Airtel STK Push initiation error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Failed to initiate Airtel Money STK Push: ' . $e->getMessage()
    ]);
}
?>
