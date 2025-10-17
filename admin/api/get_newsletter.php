<?php
/**
 * Newsletter API Endpoint - Get Newsletter Data
 * Returns newsletter data in JSON format for editing
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

header('Content-Type: application/json');

try {
    // Get newsletter ID from query parameter
    $newsletter_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if (empty($newsletter_id)) {
        throw new Exception('Newsletter ID is required');
    }

    // Get newsletter data
    $stmt = $pdo->prepare("
        SELECT nc.*, nt.name as template_name
        FROM newsletter_campaigns nc
        LEFT JOIN newsletter_templates nt ON nc.template = nt.id
        WHERE nc.id = ?
    ");
    $stmt->execute([$newsletter_id]);
    $newsletter = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$newsletter) {
        throw new Exception('Newsletter not found');
    }

    // Format scheduled_at for datetime-local input (YYYY-MM-DDTHH:MM)
    if ($newsletter['scheduled_at']) {
        $newsletter['scheduled_at'] = date('Y-m-d\TH:i', strtotime($newsletter['scheduled_at']));
    }

    echo json_encode([
        'success' => true,
        'newsletter' => $newsletter
    ]);

} catch (Exception $e) {
    error_log("Get newsletter error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while retrieving the newsletter'
    ]);
}
?>
