<?php
// admin/preview_newsletter.php - Newsletter preview functionality

// Check if user is logged in and is admin
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

require_once dirname(__DIR__) . '/includes/config.php';

$campaign_id = (int)($_GET['id'] ?? 0);

if (!$campaign_id) {
    http_response_code(400);
    exit('Campaign ID required');
}

try {
    // Get newsletter campaign
    $stmt = $pdo->prepare("
        SELECT nc.*, nt.html_template
        FROM newsletter_campaigns nc
        LEFT JOIN newsletter_templates nt ON nc.template = nt.id
        WHERE nc.id = ?
    ");
    $stmt->execute([$campaign_id]);
    $campaign = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$campaign) {
        http_response_code(404);
        exit('Newsletter not found');
    }

    // Replace template placeholders
    $html = $campaign['html_template'] ?? getDefaultTemplate();

    // Replace placeholders
    $replacements = [
        '{{SUBJECT}}' => htmlspecialchars($campaign['subject']),
        '{{CONTENT}}' => $campaign['content'],
        '{{UNSUBSCRIBE_URL}}' => 'https://' . $_SERVER['HTTP_HOST'] . '/unsubscribe.php?token={{UNSUBSCRIBE_TOKEN}}',
        '{{WEBSITE_URL}}' => 'https://' . $_SERVER['HTTP_HOST'],
        '{{YEAR}}' => date('Y')
    ];

    foreach ($replacements as $placeholder => $value) {
        $html = str_replace($placeholder, $value, $html);
    }

    echo $html;

} catch (Exception $e) {
    http_response_code(500);
    echo 'Error: ' . $e->getMessage();
}

function getDefaultTemplate() {
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='utf-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1'>
        <title>{{SUBJECT}}</title>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; margin: 0; padding: 20px; background-color: #f8f9fa; }
            .container { max-width: 600px; margin: 0 auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { text-align: center; border-bottom: 3px solid #C1272D; padding-bottom: 20px; margin-bottom: 30px; }
            .logo { font-size: 24px; font-weight: bold; color: #C1272D; margin-bottom: 10px; }
            .content { margin: 20px 0; }
            .footer { margin-top: 30px; padding-top: 20px; border-top: 1px solid #eee; font-size: 12px; color: #666; text-align: center; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <div class='logo'>Addins Meals on Wheels</div>
                <h1>{{SUBJECT}}</h1>
            </div>
            <div class='content'>
                {{CONTENT}}
            </div>
            <div class='footer'>
                <p>You received this email because you subscribed to our newsletter.</p>
                <p><a href='{{UNSUBSCRIBE_URL}}'>Unsubscribe</a> | <a href='{{WEBSITE_URL}}'>Visit Website</a></p>
                <p>&copy; {{YEAR}} Addins Meals on Wheels. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>
