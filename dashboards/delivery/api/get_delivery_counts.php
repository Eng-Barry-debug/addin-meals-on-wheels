<?php
// API for getting delivery counts
require_once '../../includes/config.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'delivery') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    global $pdo;

    $pendingDeliveries = getCount($pdo, 'orders', 'status = "ready_for_delivery"');
    $outForDelivery = getCount($pdo, 'orders', 'status = "out_for_delivery"');
    $completedToday = getCount($pdo, 'orders', 'status = "delivered" AND DATE(updated_at) = CURDATE()');

    echo json_encode([
        'success' => true,
        'pending' => $pendingDeliveries,
        'active' => $outForDelivery,
        'completed_today' => $completedToday,
        'timestamp' => date('Y-m-d H:i:s')
    ]);

} catch (PDOException $e) {
    error_log("Delivery counts API error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error'
    ]);
}
?>
