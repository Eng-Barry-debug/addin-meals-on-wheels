<?php
// admin/process_newsletter.php - Background newsletter processing

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed');
}

// Basic authentication check
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

require_once dirname(__DIR__) . '/includes/config.php';
require_once dirname(__DIR__) . '/includes/newsletter_sender.php';

$campaign_id = (int)($_POST['campaign_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$campaign_id || $action !== 'send') {
    http_response_code(400);
    exit('Invalid request');
}

try {
    // Send the newsletter
    $result = sendNewsletterCampaign($campaign_id);

    // Update campaign with final statistics
    $stmt = $pdo->prepare("
        UPDATE newsletter_campaigns
        SET status = 'sent',
            sent_at = CURRENT_TIMESTAMP,
            total_recipients = ?,
            sent_count = ?,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$result['total'], $result['sent'], $campaign_id]);

    // Log the successful sending
    error_log("Newsletter campaign {$campaign_id} sent successfully: {$result['sent']}/{$result['total']} emails delivered");

    echo json_encode([
        'success' => true,
        'message' => "Newsletter sent to {$result['sent']} out of {$result['total']} subscribers",
        'result' => $result
    ]);

} catch (Exception $e) {
    // Update campaign status to indicate failure
    $stmt = $pdo->prepare("UPDATE newsletter_campaigns SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$campaign_id]);

    error_log("Newsletter campaign {$campaign_id} failed: " . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Newsletter sending failed: ' . $e->getMessage()
    ]);
}
?>
