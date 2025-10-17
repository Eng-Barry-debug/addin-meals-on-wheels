<?php
/**
 * Mark Notification as Read - AJAX Endpoint
 * Handles marking notifications as read via AJAX
 */

session_start();
require_once '../includes/config.php';
require_once '../includes/notifications.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if notification_id is provided
if (!isset($_POST['notification_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Notification ID is required']);
    exit();
}

$notification_id = (int) $_POST['notification_id'];
$user_id = $_SESSION['user_id'];

try {
    // Mark notification as read
    $success = markNotificationAsRead($notification_id, $user_id);

    if ($success) {
        // Get updated notification count
        $new_count = getUnreadNotificationCount($user_id);

        echo json_encode([
            'success' => true,
            'message' => 'Notification marked as read',
            'new_count' => $new_count
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
    }

} catch (Exception $e) {
    error_log("Error marking notification as read: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
