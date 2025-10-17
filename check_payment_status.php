<?php
/**
 * check_payment_status.php - Check M-Pesa payment status
 * 
 * This script checks the status of an M-Pesa payment using the checkout request ID
 */

// Set content type to JSON
header('Content-Type: application/json');

// Only accept GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    // Include required files
    require_once 'includes/config.php';
    require_once 'includes/MpesaService.php';

    // Get checkout request ID from query parameters
    $checkout_request_id = isset($_GET['checkout_request_id']) ? trim($_GET['checkout_request_id']) : '';
    
    if (empty($checkout_request_id)) {
        throw new Exception('Missing checkout_request_id parameter');
    }

    // Initialize M-Pesa service
    $mpesa = new MpesaService();
    
    // Query the payment status
    $result = $mpesa->querySTKPush($checkout_request_id);

    if ($result['success']) {
        $status = strtolower($result['status'] ?? 'pending');
        
        // Update payment status in database
        try {
            $pdo->beginTransaction();
            
            // Update payment record
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET status = ?, 
                    updated_at = NOW() 
                WHERE transaction_id = ?
            ");
            $stmt->execute([$status, $checkout_request_id]);
            
            // If payment is completed, update order status
            if ($status === 'completed') {
                $stmt = $pdo->prepare("
                    UPDATE orders 
                    SET payment_status = 'completed', 
                        status = 'processing',
                        updated_at = NOW() 
                    WHERE payment_reference = ?
                ");
                $stmt->execute([$checkout_request_id]);
            }
            
            $pdo->commit();
            
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Error updating payment status: ' . $e->getMessage());
        }
        
        // Return the payment status
        echo json_encode([
            'success' => true,
            'status' => $status,
            'message' => $result['message'] ?? 'Payment status retrieved',
            'data' => $result
        ]);
    } else {
        throw new Exception($result['message'] ?? 'Failed to check payment status');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
}
