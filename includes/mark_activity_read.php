<?php
/**
 * Mark Activity as Read - AJAX Handler
 * Handles marking activities as read via AJAX requests
 */

// Start session and check admin authentication
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database configuration
require_once '../includes/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Include activity read manager
require_once '../includes/activity_read_manager.php';

// Ensure $pdo is global within this scope
global $pdo;

header('Content-Type: application/json');

try {
    // Get activity ID from POST request
    $activity_id = isset($_POST['activity_id']) ? (int)$_POST['activity_id'] : 0;

    if (empty($activity_id)) {
        throw new Exception('Activity ID is required');
    }

    // Get current user ID
    $user_id = $_SESSION['user_id'];

    // Mark activity as read
    $success = markActivityAsRead($user_id, $activity_id);

    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Activity marked as read successfully'
        ]);
    } else {
        throw new Exception('Failed to mark activity as read');
    }

} catch (Exception $e) {
    error_log("Mark activity as read error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while marking the activity as read'
    ]);
}
?>
