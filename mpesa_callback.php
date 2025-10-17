<?php
// mpesa_callback.php - Handle M-Pesa payment confirmations

require_once 'includes/config.php';
require_once 'includes/MpesaService.php';

// Set content type to JSON for API responses
header('Content-Type: application/json');

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get raw POST data
$input = file_get_contents('php://input');

// Log the callback for debugging
error_log('M-Pesa Callback received: ' . $input);

// Decode JSON data
$data = json_decode($input, true);

if (!$data) {
    error_log('Invalid JSON received in M-Pesa callback');
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

try {
    // Process the callback based on the result code
    if (isset($data['Body']['stkCallback'])) {
        $callback = $data['Body']['stkCallback'];

        // Validate required callback fields
        if (!isset($callback['CheckoutRequestID']) || !isset($callback['ResultCode'])) {
            error_log('Invalid M-Pesa callback: missing required fields');
            echo json_encode([
                'success' => false,
                'message' => 'Invalid callback data'
            ]);
            exit;
        }

        if ($callback['ResultCode'] == 0) {
            // Payment successful
            $callback_metadata = $callback['CallbackMetadata']['Item'];

            // Extract payment details
            $amount = null;
            $mpesa_receipt_number = null;
            $transaction_date = null;
            $phone_number = null;

            foreach ($callback_metadata as $item) {
                switch ($item['Name']) {
                    case 'Amount':
                        $amount = $item['Value'];
                        break;
                    case 'MpesaReceiptNumber':
                        $mpesa_receipt_number = $item['Value'];
                        break;
                    case 'TransactionDate':
                        $transaction_date = $item['Value'];
                        break;
                    case 'PhoneNumber':
                        $phone_number = $item['Value'];
                        break;
                }
            }

            // Find the order using checkout request ID first (more reliable)
            $checkout_request_id = $data['Body']['stkCallback']['CheckoutRequestID'];

            // Query the orders table to find the matching order using checkout request ID
            $stmt = $pdo->prepare("
                SELECT o.*, mp.reference
                FROM orders o
                INNER JOIN mpesa_payments mp ON o.id = mp.order_id
                WHERE mp.checkout_request_id = ?
                LIMIT 1
            ");
            $stmt->execute([$checkout_request_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);

            // If not found by checkout request ID, try by phone number as fallback
            if (!$order) {
                $phone_number = $phone_number ?? 'unknown';
                $stmt = $pdo->prepare("
                    SELECT o.*, mp.reference
                    FROM orders o
                    LEFT JOIN mpesa_payments mp ON o.id = mp.order_id
                    WHERE o.customer_phone = ? AND o.payment_status = 'pending_payment'
                    ORDER BY o.created_at DESC LIMIT 1
                ");
                $stmt->execute([$phone_number]);
                $order = $stmt->fetch(PDO::FETCH_ASSOC);
            }

            if ($order) {
                // Update order status
                $stmt = $pdo->prepare("
                    UPDATE orders
                    SET payment_status = 'confirmed', status = 'processing', updated_at = CURRENT_TIMESTAMP
                    WHERE id = ?
                ");
                $stmt->execute([$order['id']]);

                // Update M-Pesa payment record
                $stmt = $pdo->prepare("
                    UPDATE mpesa_payments
                    SET status = 'completed', transaction_code = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE order_id = ?
                ");
                $stmt->execute([$mpesa_receipt_number, $order['id']]);

                // Log successful payment
                error_log("M-Pesa payment confirmed for order {$order['id']}: {$mpesa_receipt_number} (CheckoutRequestID: {$checkout_request_id})");

                echo json_encode([
                    'success' => true,
                    'message' => 'Payment confirmed successfully'
                ]);
            } else {
                error_log("Order not found for CheckoutRequestID: {$checkout_request_id} or phone number: {$phone_number}");
                echo json_encode([
                    'success' => false,
                    'message' => 'Order not found'
                ]);
            }

        } else {
            // Payment failed or cancelled
            $result_desc = $callback['ResultDesc'];

            // Find the order using checkout request ID for failed payments
            $checkout_request_id = $data['Body']['stkCallback']['CheckoutRequestID'];

            // Update order and payment status to failed using checkout request ID
            $stmt = $pdo->prepare("
                UPDATE orders o
                INNER JOIN mpesa_payments mp ON o.id = mp.order_id
                SET o.payment_status = 'failed', o.status = 'cancelled', o.updated_at = CURRENT_TIMESTAMP,
                    mp.status = 'failed', mp.updated_at = CURRENT_TIMESTAMP
                WHERE mp.checkout_request_id = ?
            ");
            $stmt->execute([$checkout_request_id]);

            error_log("M-Pesa payment failed: {$result_desc} for checkout request ID: {$checkout_request_id}");

            echo json_encode([
                'success' => true,
                'message' => 'Payment status updated'
            ]);
        }
    } else {
        // Invalid callback format
        error_log('Invalid M-Pesa callback format received');
        echo json_encode([
            'success' => false,
            'message' => 'Invalid callback format'
        ]);
    }

} catch (Exception $e) {
    error_log('Error processing M-Pesa callback: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error processing callback'
    ]);
}
?>
