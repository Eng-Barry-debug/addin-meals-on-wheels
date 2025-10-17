<?php
/**
 * initiate_stk_push.php - Initiate M-Pesa STK Push
 * 
 * This script handles M-Pesa STK push requests, processes payments,
 * and updates the database with transaction details.
 */

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
    // Include required files
    require_once 'includes/config.php';
    require_once 'includes/MpesaService.php';

    // Check if M-Pesa is properly configured
    if (!isMpesaConfigured()) {
        throw new Exception('M-Pesa payment is not properly configured. Please check your configuration.');
    }

    // Get and validate JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (!$data) {
        throw new Exception('Invalid JSON input received');
    }

    // Validate required fields
    $required_fields = ['phone', 'amount', 'order_id'];
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            $missing_fields[] = $field;
        }
    }

    if (!empty($missing_fields)) {
        throw new Exception('Missing required fields: ' . implode(', ', $missing_fields));
    }

    // Sanitize input
    $phone = preg_replace('/[^0-9]/', '', $data['phone']);
    $amount = (float)$data['amount'];
    $order_id = $data['order_id'];

    // Validate amount
    if ($amount <= 0) {
        throw new Exception('Invalid amount specified');
    }

    // Initialize M-Pesa service
    $mpesa = new MpesaService();

    // Generate a unique reference for this transaction
    $account_reference = 'ORDER-' . $order_id;
    $transaction_desc = 'Payment for Order #' . $order_id;

    // Initiate STK Push
    $result = $mpesa->initiateSTKPush(
        $phone,
        $amount,
        $account_reference,
        $transaction_desc
    );

    if (!$result['success']) {
        throw new Exception('Failed to initiate payment: ' . ($result['message'] ?? 'Unknown error'));
    }

    // Save transaction details to database
    try {
        // Start transaction
        $pdo->beginTransaction();

        // Insert payment record
        $stmt = $pdo->prepare("
            INSERT INTO payments (
                order_id,
                transaction_id,
                amount,
                payment_method,
                status,
                created_at,
                updated_at
            ) VALUES (?, ?, ?, 'mpesa', 'pending', NOW(), NOW())
        ");
        $stmt->execute([
            $order_id,
            $result['checkout_request_id'],
            $amount
        ]);

        // Update order with payment reference
        $stmt = $pdo->prepare("UPDATE orders SET payment_reference = ?, payment_status = 'pending' WHERE id = ?");
        $stmt->execute([$result['checkout_request_id'], $order_id]);

        // Commit transaction
        $pdo->commit();

        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Payment initiated successfully. Please check your phone to complete the payment.',
            'data' => [
                'checkout_request_id' => $result['checkout_request_id'],
                'merchant_request_id' => $result['merchant_request_id'] ?? '',
                'response_code' => $result['response_code'] ?? '',
                'response_description' => $result['response_description'] ?? '',
                'customer_message' => $result['customer_message'] ?? ''
            ]
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Error in initiate_stk_push: ' . $e->getMessage());
        throw new Exception('Failed to save payment details: ' . $e->getMessage());
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
