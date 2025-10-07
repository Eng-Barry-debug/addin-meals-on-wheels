<?php
// API for updating delivery status
require_once '../../includes/config.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'delivery') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['order_id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit();
}

$orderId = (int)$data['order_id'];
$status = trim($data['status']);

// Validate status
$validStatuses = ['accepted', 'out_for_delivery', 'delivered', 'failed'];
if (!in_array($status, $validStatuses)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    global $pdo;

    // Update order status
    $stmt = $pdo->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");
    $result = $stmt->execute([$status, $orderId]);

    if ($result) {
        // Log the activity
        require_once '../../includes/ActivityLogger.php';
        $activityLogger = new ActivityLogger($pdo);
        $activityLogger->log('delivery', 'status_update', "Order {$orderId} status changed to {$status}", 'order', $orderId);

        echo json_encode([
            'success' => true,
            'message' => "Order status updated to {$status}"
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to update order status'
        ]);
    }

} catch (PDOException $e) {
    error_log("Delivery status update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
?>
